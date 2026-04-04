<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppSetting;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class WhatsAppController extends Controller
{
    /** @var WhatsAppService */
    protected $whatsApp;

    public function __construct(WhatsAppService $whatsApp)
    {
        $this->whatsApp = $whatsApp;
    }

    /**
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $templates = WhatsAppTemplate::where('is_active', true)->orderBy('label')->get(['key', 'label']);

        return view('whatsapp.index', compact('templates'));
    }

    /**
     * @return \Illuminate\View\View
     */
    public function settings()
    {
        $templates = WhatsAppTemplate::orderBy('key')->get();
        $settings = WhatsAppSetting::allAsArray();

        return view('whatsapp.settings', compact('templates', 'settings'));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'service_url' => 'required|url',
            'api_key' => 'required|string|min:8',
            'country_code' => 'required|digits_between:1,4',
            'bulk_delay' => 'required|integer|min:500|max:5000',
            'timeout' => 'required|integer|min:5|max:120',
            'max_bulk' => 'required|integer|min:1|max:200',
            'test_phone' => 'nullable|string|max:20',
            'enabled' => 'nullable|boolean',
            'log_messages' => 'nullable|boolean',
        ]);

        WhatsAppSetting::set('service_url', $validated['service_url']);
        WhatsAppSetting::set('api_key', $validated['api_key']);
        WhatsAppSetting::set('country_code', $validated['country_code']);
        WhatsAppSetting::set('bulk_delay', (string) $validated['bulk_delay']);
        WhatsAppSetting::set('timeout', (string) $validated['timeout']);
        WhatsAppSetting::set('max_bulk', (string) $validated['max_bulk']);
        WhatsAppSetting::set('test_phone', $validated['test_phone'] ?? '');
        WhatsAppSetting::set('enabled', $request->boolean('enabled') ? '1' : '0');
        WhatsAppSetting::set('log_messages', $request->boolean('log_messages') ? '1' : '0');

        WhatsAppSetting::clearAllCache();
        $this->whatsApp = new WhatsAppService();

        return redirect()
            ->route('whatsapp.settings')
            ->with('success', 'تم حفظ الإعدادات');
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function status()
    {
        return response()->json($this->whatsApp->getStatus());
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function initialize(Request $request)
    {
        return response()->json(
            $this->whatsApp->initialize($request->boolean('reset_session'))
        );
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        return response()->json($this->whatsApp->logout());
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:30',
            'message' => 'required|string|max:4096',
        ]);

        $meta = ['sent_by' => Auth::id()];

        return response()->json(
            $this->whatsApp->sendMessage($validated['phone'], $validated['message'], $meta)
        );
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendBulk(Request $request)
    {
        $validated = $request->validate([
            'messages' => 'required|array',
            'messages.*.phone' => 'required|string|max:30',
            'messages.*.message' => 'required|string|max:4096',
            'delay_ms' => 'nullable|integer|min:0|max:10000',
            'template_key' => 'nullable|string|max:100',
        ]);

        $meta = ['sent_by' => Auth::id()];
        if (! empty($validated['template_key'])) {
            $meta['template_key'] = $validated['template_key'];
        }

        return response()->json(
            $this->whatsApp->sendBulk(
                $validated['messages'],
                isset($validated['delay_ms']) ? (int) $validated['delay_ms'] : null,
                $meta
            )
        );
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateNumber(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:30',
        ]);

        return response()->json($this->whatsApp->validateNumber($validated['phone']));
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function health()
    {
        return response()->json($this->whatsApp->healthCheck());
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        return response()->json([
            'success' => true,
            'data' => WhatsAppLog::todayStats(),
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function logs(Request $request)
    {
        $query = WhatsAppLog::query()->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->get('phone') . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $perPage = (int) $request->get('per_page', 25);
        if ($perPage < 5) {
            $perPage = 5;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    /**
     * @param  string  $phone
     * @return \Illuminate\Http\JsonResponse
     */
    public function conversation($phone)
    {
        $phone = rawurldecode($phone);
        $logs = WhatsAppLog::where('phone', $phone)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function conversations(Request $request)
    {
        $search = $request->get('q', '');

        $latestIds = WhatsAppLog::query()
            ->selectRaw('MAX(id) as mid')
            ->groupBy('phone')
            ->pluck('mid');

        $items = WhatsAppLog::whereIn('id', $latestIds)
            ->orderByDesc('created_at')
            ->get();

        if ($search !== '') {
            $items = $items->filter(function ($row) use ($search) {
                return mb_stripos($row->phone, $search) !== false
                    || mb_stripos($row->message, $search) !== false;
            })->values();
        }

        $payload = [];
        foreach ($items as $log) {
            $failedCount = WhatsAppLog::where('phone', $log->phone)
                ->where('status', WhatsAppLog::STATUS_FAILED)
                ->count();
            $payload[] = [
                'phone' => $log->phone,
                'preview' => mb_substr($log->message, 0, 80),
                'last_at' => $log->created_at ? $log->created_at->toIso8601String() : null,
                'last_time' => $log->created_at ? $log->created_at->format('H:i') : '',
                'last_date' => $log->created_at ? $log->created_at->format('d/m/Y') : '',
                'failed_count' => $failedCount,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:100|regex:/^[a-z0-9_]+$/|unique:whats_app_templates,key',
            'label' => 'required|string|max:255',
            'body' => 'required|string',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $template = WhatsAppTemplate::create([
            'key' => $validated['key'],
            'label' => $validated['label'],
            'body' => $validated['body'],
            'variables' => $validated['variables'] ?? [],
            'is_active' => $request->boolean('is_active', true),
            'is_system' => false,
        ]);

        return response()->json(['success' => true, 'data' => $template]);
    }

    /**
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTemplate(Request $request, $id)
    {
        $template = WhatsAppTemplate::findOrFail($id);

        $validated = $request->validate([
            'label' => 'sometimes|required|string|max:255',
            'body' => 'sometimes|required|string',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if (isset($validated['label'])) {
            $template->label = $validated['label'];
        }
        if (isset($validated['body'])) {
            $template->body = $validated['body'];
        }
        if (array_key_exists('variables', $validated)) {
            $template->variables = $validated['variables'];
        }
        if ($request->has('is_active')) {
            $template->is_active = $request->boolean('is_active');
        }
        $template->save();

        return response()->json(['success' => true, 'data' => $template->fresh()]);
    }

    /**
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyTemplate($id)
    {
        $template = WhatsAppTemplate::findOrFail($id);
        if ($template->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف قالب النظام',
            ], 403);
        }
        $template->delete();

        return response()->json(['success' => true]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function previewTemplate(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:100',
            'data' => 'nullable|array',
        ]);
        $data = $validated['data'] ?? [];
        $rendered = WhatsAppTemplate::getRendered($validated['key'], $data);

        return response()->json([
            'success' => $rendered !== null,
            'preview' => $rendered,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportLogs(Request $request)
    {
        $query = WhatsAppLog::query()->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->get('phone') . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $filename = 'whatsapp_logs_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return Response::stream(function () use ($query) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['id', 'phone', 'template_key', 'message', 'status', 'message_id', 'error', 'sent_at', 'created_at']);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->id,
                        $row->phone,
                        $row->template_key,
                        $row->message,
                        $row->status,
                        $row->message_id,
                        $row->error,
                        $row->sent_at ? $row->sent_at->format('Y-m-d H:i:s') : '',
                        $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '',
                    ]);
                }
            });

            fclose($out);
        }, 200, $headers);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDeleteLogs(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        WhatsAppLog::whereIn('id', $validated['ids'])->delete();

        return response()->json(['success' => true]);
    }

    /**
     * استقبال رسالة واردة من خدمة Node (whatsapp-web.js) — بدون جلسة مستخدم.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function incomingMessage(Request $request)
    {
        $expected = (string) config('whatsapp.internal_webhook_secret', '');
        if ($expected === '') {
            return response()->json(['success' => false, 'message' => 'Webhook not configured'], 503);
        }

        $header = (string) $request->header('X-WhatsApp-Internal', '');
        if ($header === '' || ! hash_equals($expected, $header)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'phone' => 'required|string|max:30',
            'message' => 'required|string|max:8192',
            'message_id' => 'nullable|string|max:255',
        ]);

        $phone = preg_replace('/\D/', '', $validated['phone']);
        if (strlen($phone) < 7 || strlen($phone) > 20) {
            return response()->json(['success' => false, 'message' => 'Invalid phone'], 422);
        }

        if (! empty($validated['message_id'])) {
            $exists = WhatsAppLog::where('message_id', $validated['message_id'])->exists();
            if ($exists) {
                return response()->json(['success' => true, 'duplicate' => true]);
            }
        }

        WhatsAppLog::create([
            'phone' => $phone,
            'template_key' => null,
            'message' => $validated['message'],
            'status' => WhatsAppLog::STATUS_RECEIVED,
            'message_id' => $validated['message_id'] ?? null,
            'sent_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
