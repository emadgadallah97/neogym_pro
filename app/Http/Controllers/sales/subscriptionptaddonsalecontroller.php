<?php

namespace App\Http\Controllers\sales;

use App\Http\Controllers\Controller;
use App\Models\sales\MemberSubscription;
use App\Models\sales\MemberSubscriptionPtAddon;
use App\Models\sales\Payment;
use App\Models\sales\Invoice;
use App\Models\employee\Employee;
use App\Models\general\TrainerSessionPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class SubscriptionPtAddonSaleController extends Controller
{
    private function assertEligible(MemberSubscription $sub): void
    {
        $status = (string)($sub->status ?? '');

        if ($status !== 'active') {
            throw ValidationException::withMessages([
                'subscription' => [trans('sales.pt_addons_only_active') ?? 'متاح فقط للاشتراكات النشطة'],
            ]);
        }

        // الممنوع فقط: وجود PT Add-on على نفس الاشتراك وله حصص متبقية > 0
        $hasOpenPtAddon = MemberSubscriptionPtAddon::query()
            ->where('member_subscription_id', (int)$sub->id)
            ->whereRaw('COALESCE(sessions_remaining, 0) > 0')
            ->exists();

        if ($hasOpenPtAddon) {
            throw ValidationException::withMessages([
                'subscription' => [trans('sales.pt_addons_already_exists') ?? 'لا يمكن إضافة PT لأن هناك PT بحصص متبقية على نفس الاشتراك'],
            ]);
        }
    }

    private function getTrainerSessionPriceFromTable(int $trainerId): float
    {
        $pricing = TrainerSessionPricing::query()
            ->where('trainer_id', $trainerId)
            ->orderByDesc('id')
            ->first();

        return $pricing ? (float)$pricing->session_price : 0.0;
    }

    public function ajaxTrainerSessionPrice(Request $request)
    {
        $request->validate([
            'trainerid' => ['required', 'integer', 'exists:employees,id'],
        ]);

        $trainerId = (int)$request->input('trainerid');
        $price = $this->getTrainerSessionPriceFromTable($trainerId);

        return response()->json([
            'ok' => true,
            'data' => [
                'trainerid' => $trainerId,
                'sessionprice' => round((float)$price, 2),
            ],
        ]);
    }

    public function create($id)
    {
        $subscription = MemberSubscription::with(['member', 'branch', 'plan'])->findOrFail($id);

        try {
            $this->assertEligible($subscription);
        } catch (ValidationException $e) {
            return redirect()->route('sales.index')->with('error', $e->getMessage());
        }

        $branchId = (int)$subscription->branch_id;

        $Coaches = Employee::query()
            ->where('is_coach', 1)
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            })
            ->orderByDesc('id')
            ->get();

        $paymentMethods = ['cash','card','transfer','instapay','ewallet','cheque','other'];

        return view('sales.pt_addons.create', compact('subscription', 'Coaches', 'paymentMethods'));
    }

    public function store(Request $request, $id)
    {
        $subscription = MemberSubscription::with(['member', 'branch'])->findOrFail($id);

        $this->assertEligible($subscription);

        $branchId = (int)$subscription->branch_id;
        $memberId = (int)$subscription->member_id;

        $validated = $request->validate([
            'trainer_id' => ['required', 'integer', 'exists:employees,id'],
            'sessions_count' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', 'in:cash,card,transfer,instapay,ewallet,cheque,other'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $trainerId = (int)$validated['trainer_id'];
        $sessionsCount = (int)$validated['sessions_count'];

        $coachOk = Employee::query()
            ->where('id', $trainerId)
            ->where('is_coach', 1)
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            })
            ->exists();

        if (!$coachOk) {
            return redirect()->back()
                ->withInput()
                ->with('error', trans('sales.trainer_not_in_branch') ?? 'المدرب غير مرتبط بهذا الفرع');
        }

        $paidAt = !empty($validated['paid_at'])
            ? Carbon::parse($validated['paid_at'])
            : Carbon::now();

        $paymentMethod = $validated['payment_method'];

        $trainer = Employee::query()->find($trainerId);
        $trainerName = $trainer?->full_name ?? trim(($trainer?->first_name ?? '') . ' ' . ($trainer?->last_name ?? ''));
        $trainerName = trim($trainerName) !== '' ? $trainerName : ('Trainer#' . $trainerId);

        DB::beginTransaction();
        try {
            // حماية من سباق الطلبات: إعادة التحقق داخل الترانزاكشن
            $hasOpenPtAddon = MemberSubscriptionPtAddon::query()
                ->where('member_subscription_id', (int)$subscription->id)
                ->whereRaw('COALESCE(sessions_remaining, 0) > 0')
                ->exists();

            if ($hasOpenPtAddon) {
                DB::rollBack();
                return redirect()->back()
                    ->withInput()
                    ->with('error', trans('sales.pt_addons_already_exists') ?? 'لا يمكن إضافة PT لأن هناك PT بحصص متبقية على نفس الاشتراك');
            }

            $sessionPrice = (float)$this->getTrainerSessionPriceFromTable($trainerId);
            $totalAmount = max(0, $sessionPrice * $sessionsCount);

            if ($totalAmount <= 0) {
                DB::rollBack();
                return redirect()->back()
                    ->withInput()
                    ->with('error', trans('sales.pt_addons_total_zero') ?? 'الإجمالي لا يمكن أن يكون صفر');
            }

            // 1) Create Add-on
            $addon = MemberSubscriptionPtAddon::create([
                'member_subscription_id' => (int)$subscription->id,
                'trainer_id' => $trainerId,
                'session_price' => $sessionPrice,
                'sessions_count' => $sessionsCount,
                'sessions_remaining' => $sessionsCount,
                'total_amount' => $totalAmount,
                'notes' => null, // سنملأها بعد إنشاء الدفع والفاتورة
            ]);

            // 2) Create Payment (Reference + Notes تلقائي)
            $payment = Payment::create([
                'member_id' => $memberId,
                'member_subscription_id' => (int)$subscription->id,
                'amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'status' => 'paid',
                'paid_at' => $paidAt,
                'reference' => 'PTA#' . (int)$addon->id . '-SUB#' . (int)$subscription->id,
                'notes' => null,
                'user_add' => Auth::id(),
            ]);

            // 3) Create Invoice
            $tmpInvoiceNumber = 'INV-TMP-' . date('YmdHis') . '-' . substr((string)uniqid(), -6);

            $invoice = Invoice::create([
                'invoice_number' => $tmpInvoiceNumber,
                'member_id' => $memberId,
                'branch_id' => $branchId,
                'member_subscription_id' => (int)$subscription->id,
                'currency_id' => null,
                'subtotal' => $totalAmount,
                'discount_total' => 0,
                'total' => $totalAmount,
                'status' => 'paid',
                'issued_at' => Carbon::now(),
                'paid_at' => $paidAt,
                'notes' => null,
                'user_add' => Auth::id(),
            ]);

            $finalInvoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad((string)$invoice->id, 6, '0', STR_PAD_LEFT);
            $invoice->invoice_number = $finalInvoiceNumber;
            $invoice->save();

            // 4) Final auto notes (ربط واضح)
            $autoNotes =
                'PT Add-on Sale | ' .
                'Sub#' . (int)$subscription->id . ' | ' .
                'Member#' . $memberId . ' | ' .
                'Branch#' . $branchId . ' | ' .
                'Trainer: ' . $trainerName . ' (#' . $trainerId . ') | ' .
                'Sessions=' . $sessionsCount . ' | ' .
                'SessionPrice=' . number_format($sessionPrice, 2, '.', '') . ' | ' .
                'Total=' . number_format($totalAmount, 2, '.', '') . ' | ' .
                'PTAddon#' . (int)$addon->id . ' | ' .
                'Payment#' . (int)$payment->id . ' (ref:' . ($payment->reference ?? '-') . ') | ' .
                'Invoice#' . (int)$invoice->id . ' (' . $invoice->invoice_number . ')';

            // Reference أقصر ومحدد يربط الدفع بالفاتورة والـ add-on
            $finalPaymentRef = 'PTA#' . (int)$addon->id . '-INV#' . (int)$invoice->id . '-SUB#' . (int)$subscription->id;
            $payment->reference = mb_substr($finalPaymentRef, 0, 100);

            $addon->notes = $autoNotes;
            $addon->save();

            $payment->notes = $autoNotes;
            $payment->save();

            $invoice->notes = $autoNotes;
            $invoice->save();

            DB::commit();
            return redirect()->route('sales.index')->with('success', trans('sales.pt_addons_saved') ?? 'تم إضافة PT بنجاح');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            $msg = config('app.debug')
                ? ($e->getMessage() ?? 'Error')
                : (trans('sales.somethingwentwrong') ?? 'حدث خطأ');

            return redirect()->back()->withInput()->with('error', $msg);
        }
    }
}
