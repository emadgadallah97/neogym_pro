<?php


namespace App\Http\Controllers\members;

use App\Http\Controllers\Controller;
use App\Models\general\GeneralSetting;
use App\Models\general\Branch;
use App\Models\members\Member;
use App\Traits\store\file_storage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
// QR Generator (GD native)
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class memberscontroller extends Controller
{
    use file_storage;

    public function index()
    {
        $this->autoUnfreezeExpiredMembers();

        $Members = Member::with(['branch'])
            ->orderByDesc('id')
            ->get();

        $Branches = Branch::orderByDesc('id')->get();
        $Governments = \App\models\government::orderByDesc('id')->get();
        $Cities = \App\models\City::orderByDesc('id')->get();
        $Areas = \App\models\area::orderByDesc('id')->get();

        return view('members.index', compact('Members', 'Branches', 'Governments', 'Cities', 'Areas'));
    }

    public function store(Request $request)
    {
        $data = $this->validateMember($request, null);

        $member = new Member();
        $member->fill($data);

        if ($request->hasFile('photo')) {
            $member->photo = $this->file_storage($request->file('photo'), 'members');
        }

        $member->user_add = auth()->id() ?? null;
        $member->save();

        // Generate member_code after save (needs id)
        if (empty($member->member_code)) {
            $member->member_code = $this->generateMemberCode($member);
            $member->save();
        }

        $this->autoUnfreezeExpiredMembers();

        $member->load(['branch']);

        if ($request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => trans('members.created_successfully'),
                'member' => $this->memberPayload($member),
            ]);
        }

        return redirect()->back()->with('success', trans('members.created_successfully'));
    }

   public function update(Request $request, $id)
{
    $memberId = $request->input('id') ?? $id;
    $member = Member::findOrFail($memberId);

    $data = $this->validateMember($request, $member->id);

    // مهم: لا تجعل fill يغير photo (خصوصًا لو validation يرجع photo = null)
    if (array_key_exists('photo', $data)) {
        unset($data['photo']);
    }

    $oldPhoto = $member->photo;

    $member->fill($data);

    if ($request->hasFile('photo')) {
        // 1) خزّن الجديد أولاً
        $newPath = $this->file_storage($request->file('photo'), 'members');

        // 2) ثم احذف القديم
        $this->deletePublicAttachmentIfExists($oldPhoto);

        // 3) ثم احفظ المسار الجديد
        $member->photo = $newPath;
    }

    $member->user_update = auth()->id() ?? null;
    $member->save();

    $this->autoUnfreezeExpiredMembers();

    $member->load(['branch']);

    if ($request->ajax()) {
        return response()->json([
            'status' => true,
            'message' => trans('members.updated_successfully'),
            'member' => $this->memberPayload($member),
        ]);
    }

    return redirect()->back()->with('success', trans('members.updated_successfully'));
}


    public function show(Request $request, $id)
    {
        $member = Member::with(['branch', 'government', 'city', 'area'])->findOrFail($id);
        $this->autoUnfreezeExpiredMembers();

        if ($request->ajax()) {
            return response()->json([
                'status' => true,
                'member' => $this->memberPayload($member, true),
            ]);
        }

        return redirect()->route('members.index');
    }
public function destroy(Request $request, $id)
{
    $memberId = $request->input('id') ?? $id;
    $member = Member::findOrFail($memberId);

    // حذف الصورة من السيرفر عند حذف العضو
    $this->deletePublicAttachmentIfExists($member->photo);

    $member->delete();

    if ($request->ajax()) {
        return response()->json([
            'status' => true,
            'message' => trans('members.deleted_successfully'),
            'id' => $memberId,
        ]);
    }

    return redirect()->back()->with('success', trans('members.deleted_successfully'));
}

    /**
     * عرض صفحة كارت العضو
     */
public function card(Member $member)
{
    $this->autoUnfreezeExpiredMembers();

    $member->load(['branch']);

    $settings = GeneralSetting::where('status', 1)->first();

    $logoUrl = (!empty($settings?->logo)) ? url($settings->logo) : null;
    $gymNameAr = $settings ? $settings->getTranslation('name', 'ar') : '';
    $gymNameEn = $settings ? $settings->getTranslation('name', 'en') : '';

    $memberPhoto = (!empty($member->photo)) ? url($member->photo) : null;

    $pngBinary = $this->qrPngBinary($member->member_code, 220, 1);
    $barcodePng = base64_encode($pngBinary);

    $template = $settings?->member_card_template ?: GeneralSetting::defaultMemberCardTemplate();

    // تأكيد وجود القالب (fallback)
    if (!view()->exists("members.cards.$template")) {
        $template = GeneralSetting::defaultMemberCardTemplate();
    }

    return view("members.cards.$template", compact(
        'member',
        'barcodePng',
        'memberPhoto',
        'gymNameAr',
        'gymNameEn',
        'logoUrl'
    ));
}


    public function qrPng(Member $member)
    {
        $png = $this->qrPngBinary($member->member_code, 320, 1);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="member-' . $member->member_code . '.png"',
        ]);
    }

    // ===================== Helpers =====================

    /**
     * QR PNG generator using GD backend (no Imagick).
     */
/**
 * QR PNG generator using chillerlan/php-qrcode (GD backend)
 */
/**
 * QR PNG generator using chillerlan/php-qrcode (GD backend - simplified)
 */
private function qrPngBinary(string $text, int $size = 320, int $margin = 1): string
{
    $options = new QROptions([
        'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'     => QRCode::ECC_H,
        'scale'        => max(5, intval($size / 50)),
        'imageBase64'  => false,
    ]);

    return (new QRCode($options))->render($text);
}




    private function validateMember(Request $request, $memberId = null): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:190'],
            'last_name' => ['required', 'string', 'max:190'],

            'branch_id' => ['required', 'integer', 'exists:branches,id'],

            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'birth_date' => ['nullable', 'date'],

            'phone' => ['required', 'string', 'max:50'],
            'phone2' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190'],

            'address' => ['required', 'string'],
            'id_government' => ['nullable', 'integer', 'exists:government,id'],
            'id_city' => ['nullable', 'integer', 'exists:city,id'],
            'id_area' => ['nullable', 'integer', 'exists:area,id'],

            'join_date' => ['required', 'date'],

            'status' => ['required', Rule::in(['active', 'inactive', 'frozen'])],
            'freeze_from' => ['nullable', 'date'],
            'freeze_to' => ['nullable', 'date'],

            'height' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:999.99'],

            'medical_conditions' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],

            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];

        $data = $request->validate($rules);

        // Frozen requires valid window
        if (($data['status'] ?? '') === 'frozen') {
            if (empty($data['freeze_from']) || empty($data['freeze_to'])) {
                abort(response()->json([
                    'status' => false,
                    'message' => trans('members.freeze_dates_required'),
                    'errors' => [
                        'freeze_from' => [trans('members.freeze_dates_required')],
                        'freeze_to' => [trans('members.freeze_dates_required')],
                    ],
                ], 422));
            }

            $from = Carbon::parse($data['freeze_from']);
            $to = Carbon::parse($data['freeze_to']);
            if ($from->gt($to)) {
                abort(response()->json([
                    'status' => false,
                    'message' => trans('members.freeze_from_must_be_before_to'),
                    'errors' => [
                        'freeze_from' => [trans('members.freeze_from_must_be_before_to')],
                    ],
                ], 422));
            }
        } else {
            $data['freeze_from'] = null;
            $data['freeze_to'] = null;
        }

        return $data;
    }

    private function generateMemberCode(Member $member): string
    {
        $time = $member->created_at ? $member->created_at->format('His') : Carbon::now()->format('His');
        return 'BR' . intval($member->branch_id) . '-' . intval($member->id) . '-' . $time;
    }

    private function autoUnfreezeExpiredMembers(): void
    {
        $today = Carbon::today();

        Member::where('status', 'frozen')
            ->whereNotNull('freeze_to')
            ->whereDate('freeze_to', '<', $today)
            ->update([
                'status' => 'active',
                'freeze_from' => null,
                'freeze_to' => null,
            ]);
    }

    private function memberPayload(Member $member, bool $withLocation = false): array
    {
        $status = $member->status ?? 'active';

        $branchName = $member->branch
            ? $member->branch->getTranslation('name', 'ar')
            : '-';

        $payload = [
            'id' => $member->id,
            'member_code' => $member->member_code,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'full_name' => $member->full_name,
            'branch_id' => $member->branch_id,
            'branch_name' => $branchName,
            'gender' => $member->gender,
            'birth_date' => optional($member->birth_date)->format('Y-m-d'),
            'phone' => $member->phone,
            'phone2' => $member->phone2,
            'whatsapp' => $member->whatsapp,
            'email' => $member->email,
            'address' => $member->address,
            'join_date' => optional($member->join_date)->format('Y-m-d'),
            'status' => $status,
            'freeze_from' => optional($member->freeze_from)->format('Y-m-d'),
            'freeze_to' => optional($member->freeze_to)->format('Y-m-d'),
            'height' => $member->height,
            'weight' => $member->weight,
            'medical_conditions' => $member->medical_conditions,
            'allergies' => $member->allergies,
            'notes' => $member->notes,
            'photo_url' => $member->public_photo_url,
            'card_url' => route('members.card', $member->id),
            'qr_png_url' => route('members.qr_png', $member->id),
        ];

        if ($withLocation) {
            $member->loadMissing(['government', 'city', 'area']);
            $payload['id_government'] = $member->id_government;
            $payload['id_city'] = $member->id_city;
            $payload['id_area'] = $member->id_area;
            $payload['government_name'] = $member->government ? $member->government->getTranslation('name', 'ar') : '-';
            $payload['city_name'] = $member->city ? $member->city->getTranslation('name', 'ar') : '-';
            $payload['area_name'] = $member->area ? $member->area->getTranslation('name', 'ar') : '-';
        }

        return $payload;
    }

   private function deletePublicAttachmentIfExists($path): void
{
    if (empty($path)) return;

    // لو path URL كامل
    $clean = parse_url($path, PHP_URL_PATH) ?: $path;
    $clean = ltrim($clean, '/');

    // حالات شائعة: storage/xxx (رابط storage:link)
    if (str_starts_with($clean, 'storage/')) {
        $diskPath = substr($clean, strlen('storage/'));
        if (Storage::disk('public')->exists($diskPath)) {
            Storage::disk('public')->delete($diskPath);
        }
        return;
    }

    // لو مسار داخل public مباشرة (مثلاً members/.. أو uploads/members/.. أو attachments/..)
    $full = public_path($clean);
    if (File::exists($full)) {
        File::delete($full);
        return;
    }

    // fallback: جرّب كأنه على public disk بدون storage/ prefix
    if (Storage::disk('public')->exists($clean)) {
        Storage::disk('public')->delete($clean);
    }
}
}
