<?php

namespace App\Services;

use App\Models\WhatsAppLog;
use App\Models\WhatsAppSetting;
use App\Models\WhatsAppTemplate;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Web Session Service
 * ============================================================
 * Drop-in for any Laravel 9+ project:
 *   1. Copy app/Services/WhatsAppService.php
 *   2. Copy app/Models/WhatsApp*.php
 *   3. Copy database/migrations/whatsapp_*.php → run php artisan migrate
 *   4. Copy config/whatsapp.php
 *   5. Start node-service: cd node-service && npm install && npm start
 *   6. Visit /whatsapp/settings → connect WhatsApp via QR
 *
 * Basic usage:
 *   $wa = new WhatsAppService();
 *   $wa->sendMessage('01001234567', 'Hello');
 *   $wa->sendTemplate('welcome', '01001234567', ['name'=>'أحمد']);
 * ============================================================
 */
class WhatsAppService
{
    /** @var string */
    protected $serviceUrl;

    /** @var string */
    protected $apiKey;

    /** @var int */
    protected $timeout;

    /** @var bool */
    protected $enabled;

    /** @var bool */
    protected $logMessages;

    /** @var string */
    protected $countryCode;

    public function __construct()
    {
        $defaults = config('whatsapp.defaults', []);
        $this->serviceUrl = rtrim((string) WhatsAppSetting::get('service_url', $defaults['service_url'] ?? 'http://localhost:3001'), '/');
        $this->apiKey = (string) WhatsAppSetting::get('api_key', $defaults['api_key'] ?? '');
        $this->timeout = (int) WhatsAppSetting::get('timeout', $defaults['timeout'] ?? 30);
        $this->enabled = $this->toBool(WhatsAppSetting::get('enabled', $defaults['enabled'] ? '1' : '0'));
        $this->logMessages = $this->toBool(WhatsAppSetting::get('log_messages', ($defaults['log_messages'] ?? true) ? '1' : '0'));
        $this->countryCode = preg_replace('/\D/', '', (string) WhatsAppSetting::get('country_code', $defaults['country_code'] ?? '20')) ?: '20';
    }

    /**
     * @param  mixed  $value
     * @return bool
     */
    protected function toBool($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        $v = strtolower(trim((string) $value));

        return in_array($v, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param  string  $phone
     * @return array{valid:bool, formatted:string}
     */
    public function normalizePhone($phone)
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (strpos($digits, '00') === 0) {
            $digits = substr($digits, 2);
        }
        if ($digits !== '' && $digits[0] === '0') {
            $digits = $this->countryCode . substr($digits, 1);
        }
        $len = strlen($digits);
        $valid = $len >= 7 && $len <= 15;

        return ['valid' => $valid, 'formatted' => $digits];
    }

    /**
     * @return \Illuminate\Http\Client\PendingRequest
     */
    private function http()
    {
        return Http::withHeaders(['x-api-key' => $this->apiKey])
            ->timeout($this->timeout)
            ->baseUrl($this->serviceUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus()
    {
        try {
            $response = $this->http()->get('/api/status');

            return [
                'success' => true,
                'data' => $response->json(),
            ];
        } catch (ConnectionException $e) {
            return ['success' => false, 'message' => 'خدمة الواتساب غير متاحة حالياً'];
        } catch (\Exception $e) {
            Log::error('WhatsApp: ' . $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck()
    {
        try {
            $response = $this->http()->get('/health');
            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'message' => 'فشل فحص الخدمة'];
        } catch (ConnectionException $e) {
            return ['success' => false, 'message' => 'خدمة الواتساب غير متاحة حالياً'];
        } catch (\Exception $e) {
            Log::error('WhatsApp: ' . $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function initialize(bool $resetSession = false)
    {
        try {
            $response = $this->http()
                ->asJson()
                ->post('/api/initialize', [
                    'reset_session' => $resetSession,
                ]);
            if ($response->successful()) {
                return $response->json();
            }

            return ['success' => false, 'message' => $response->body()];
        } catch (ConnectionException $e) {
            return ['success' => false, 'message' => 'خدمة الواتساب غير متاحة حالياً'];
        } catch (\Exception $e) {
            Log::error('WhatsApp: ' . $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function logout()
    {
        try {
            $response = $this->http()->post('/api/logout');
            if ($response->successful()) {
                return $response->json();
            }

            return ['success' => false, 'message' => $response->body()];
        } catch (ConnectionException $e) {
            return ['success' => false, 'message' => 'خدمة الواتساب غير متاحة حالياً'];
        } catch (\Exception $e) {
            Log::error('WhatsApp: ' . $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param  string  $phone
     * @return array<string, mixed>
     */
    public function validateNumber($phone)
    {
        try {
            $response = $this->http()->post('/api/validate-number', [
                'phone' => $phone,
            ]);
            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'message' => $response->body()];
        } catch (ConnectionException $e) {
            return ['success' => false, 'message' => 'خدمة الواتساب غير متاحة حالياً'];
        } catch (\Exception $e) {
            Log::error('WhatsApp: ' . $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param  string  $phone
     * @param  string  $message
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public function sendMessage($phone, $message, array $meta = [])
    {
        if (! $this->enabled) {
            return ['success' => false, 'message' => 'خدمة الواتساب معطلة من الإعدادات'];
        }
        $norm = $this->normalizePhone($phone);
        if (! $norm['valid']) {
            return ['success' => false, 'message' => 'رقم الهاتف غير صالح'];
        }
        $logText = $this->logMessages ? $message : '—';
        $log = WhatsAppLog::create([
            'phone' => $norm['formatted'],
            'template_key' => isset($meta['template_key']) ? (string) $meta['template_key'] : null,
            'message' => $logText,
            'status' => WhatsAppLog::STATUS_PENDING,
            'related_id' => isset($meta['related_id']) ? (int) $meta['related_id'] : null,
            'related_type' => isset($meta['related_type']) ? (string) $meta['related_type'] : null,
            'sent_by' => isset($meta['sent_by']) ? (int) $meta['sent_by'] : null,
        ]);

        try {
            $response = $this->http()->post('/api/send', [
                'phone' => $norm['formatted'],
                'message' => $message,
            ]);
            $payload = $response->json();
            if ($response->successful() && ! empty($payload['success'])) {
                $log->update([
                    'status' => WhatsAppLog::STATUS_SENT,
                    'message_id' => isset($payload['messageId']) ? (string) $payload['messageId'] : null,
                    'sent_at' => now(),
                ]);

                return array_merge(['success' => true], $payload);
            }
            $err = is_array($payload) && isset($payload['message']) ? (string) $payload['message'] : $response->body();
            $log->update([
                'status' => WhatsAppLog::STATUS_FAILED,
                'error' => $err,
            ]);

            return ['success' => false, 'message' => $err];
        } catch (ConnectionException $e) {
            $log->update([
                'status' => WhatsAppLog::STATUS_FAILED,
                'error' => 'connection',
            ]);

            return ['success' => false, 'message' => 'خدمة الواتساب غير متاحة حالياً'];
        } catch (\Exception $e) {
            Log::error('WhatsApp: ' . $e->getMessage());
            $log->update([
                'status' => WhatsAppLog::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param  string  $key
     * @param  string  $phone
     * @param  array<string, string>  $data
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public function sendTemplate($key, $phone, array $data, array $meta = [])
    {
        $rendered = WhatsAppTemplate::getRendered($key, $data);
        if ($rendered === null) {
            return ['success' => false, 'message' => 'القالب غير موجود أو غير مفعّل'];
        }
        $meta['template_key'] = $key;

        return $this->sendMessage($phone, $rendered, $meta);
    }

    /**
     * @param  array<int, array{phone:string, message:string}>  $messages
     * @param  int|null  $delayMs
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public function sendBulk(array $messages, $delayMs = null, array $meta = [])
    {
        if (! $this->enabled) {
            return ['success' => false, 'message' => 'خدمة الواتساب معطلة من الإعدادات'];
        }
        $maxBulk = (int) WhatsAppSetting::get('max_bulk', config('whatsapp.defaults.max_bulk', 50));
        if (count($messages) > $maxBulk) {
            return [
                'success' => false,
                'message' => "الحد الأقصى {$maxBulk} رسالة في المرة الواحدة",
            ];
        }
        if ($delayMs === null) {
            $delayMs = (int) WhatsAppSetting::get('bulk_delay', config('whatsapp.defaults.bulk_delay', 1500));
        }

        $logs = [];
        $outMessages = [];
        foreach ($messages as $row) {
            if (! is_array($row) || ! isset($row['phone'], $row['message'])) {
                continue;
            }
            $norm = $this->normalizePhone($row['phone']);
            if (! $norm['valid']) {
                continue;
            }
            $logText = $this->logMessages ? (string) $row['message'] : '—';
            $logs[] = WhatsAppLog::create([
                'phone' => $norm['formatted'],
                'template_key' => isset($meta['template_key']) ? (string) $meta['template_key'] : null,
                'message' => $logText,
                'status' => WhatsAppLog::STATUS_PENDING,
                'related_id' => isset($meta['related_id']) ? (int) $meta['related_id'] : null,
                'related_type' => isset($meta['related_type']) ? (string) $meta['related_type'] : null,
                'sent_by' => isset($meta['sent_by']) ? (int) $meta['sent_by'] : null,
            ]);
            $outMessages[] = [
                'phone' => $norm['formatted'],
                'message' => (string) $row['message'],
            ];
        }

        if (count($outMessages) === 0) {
            return ['success' => false, 'message' => 'لا توجد أرقام صالحة للإرسال'];
        }

        try {
            $response = $this->http()->post('/api/send-bulk', [
                'messages' => $outMessages,
                'delay_ms' => $delayMs,
            ]);
            $payload = $response->json();
            if (! $response->successful() || ! is_array($payload)) {
                foreach ($logs as $log) {
                    $log->update([
                        'status' => WhatsAppLog::STATUS_FAILED,
                        'error' => $response->body(),
                    ]);
                }

                return ['success' => false, 'message' => $response->body()];
            }
            if (isset($payload['results']) && is_array($payload['results'])) {
                $i = 0;
                foreach ($payload['results'] as $res) {
                    $log = $logs[$i] ?? null;
                    if ($log) {
                        if (! empty($res['success'])) {
                            $log->update([
                                'status' => WhatsAppLog::STATUS_SENT,
                                'message_id' => isset($res['messageId']) ? (string) $res['messageId'] : null,
                                'sent_at' => now(),
                            ]);
                        } else {
                            $log->update([
                                'status' => WhatsAppLog::STATUS_FAILED,
                                'error' => isset($res['error']) ? (string) $res['error'] : 'failed',
                            ]);
                        }
                    }
                    $i++;
                }
            }

            return ['success' => true, 'data' => $payload];
        } catch (ConnectionException $e) {
            foreach ($logs as $log) {
                $log->update([
                    'status' => WhatsAppLog::STATUS_FAILED,
                    'error' => 'connection',
                ]);
            }

            return ['success' => false, 'message' => 'خدمة الواتساب غير متاحة حالياً'];
        } catch (\Exception $e) {
            Log::error('WhatsApp: ' . $e->getMessage());
            foreach ($logs as $log) {
                $log->update([
                    'status' => WhatsAppLog::STATUS_FAILED,
                    'error' => $e->getMessage(),
                ]);
            }

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ── DOMAIN HELPERS (gym-specific — delete section in other projects) ─────────

    /**
     * @param  string  $phone
     * @param  array<string, string>  $data
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public function sendWelcome($phone, array $data, array $meta = [])
    {
        return $this->sendTemplate('welcome', $phone, $data, $meta);
    }

    /**
     * @param  string  $phone
     * @param  array<string, string>  $data
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public function sendRenewalReminder($phone, array $data, array $meta = [])
    {
        return $this->sendTemplate('renewal_reminder', $phone, $data, $meta);
    }

    /**
     * @param  string  $phone
     * @param  array<string, string>  $data
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public function sendPaymentConfirmation($phone, array $data, array $meta = [])
    {
        return $this->sendTemplate('payment_confirmation', $phone, $data, $meta);
    }

    /**
     * @param  string  $phone
     * @param  array<string, string>  $data
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public function sendBirthday($phone, array $data, array $meta = [])
    {
        return $this->sendTemplate('birthday', $phone, $data, $meta);
    }

    // ─────────────────────────────────────────────────────────────────────────────
}
