@extends('layouts.master_table')

@section('title', 'إعدادات واتساب')
@section('content')

<style>
    .wa-settings-wrap { border-radius: 12px; }
    .wa-settings-wrap .nav-tabs.wa-nav-tabs { border-bottom: 1px solid rgba(0,0,0,.08); flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; gap: 0.25rem; }
    .wa-settings-wrap .nav-tabs.wa-nav-tabs .nav-link { white-space: nowrap; border-radius: 8px 8px 0 0; padding: 0.65rem 1rem; color: #6c757d; }
    .wa-settings-wrap .nav-tabs.wa-nav-tabs .nav-link.active { color: #198754; font-weight: 600; background: #fff; border-color: rgba(0,0,0,.08) rgba(0,0,0,.08) #fff; }
    .wa-settings-wrap .tab-content { overflow-x: hidden; }
    .wa-border-left-success { border-left: 4px solid #28a745 !important; }
    .wa-border-left-warning { border-left: 4px solid #ffc107 !important; }
    .wa-border-left-danger { border-left: 4px solid #dc3545 !important; }
    .wa-border-left-info { border-left: 4px solid #17a2b8 !important; }
    .wa-border-top-success { border-top: 4px solid #28a745 !important; }
    .wa-border-top-danger { border-top: 4px solid #dc3545 !important; }
    .wa-border-top-warning { border-top: 4px solid #ffc107 !important; }
    .wa-border-top-info { border-top: 4px solid #17a2b8 !important; }
    .wa-chip { cursor: pointer; }
    #waBulkBar { position: fixed; bottom: 0; left: 0; right: 0; z-index: 1030; }
    @media (max-width: 575.98px) {
        .wa-settings-wrap .nav-tabs.wa-nav-tabs .nav-link { padding: 0.5rem 0.65rem; font-size: 0.875rem; }
    }
</style>

<div class="container-fluid px-2 px-lg-3" dir="rtl" lang="ar">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card wa-settings-wrap shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 pb-0 pt-3 px-3 px-md-4">
            <h4 class="mb-1 fw-semibold">إعدادات واتساب</h4>
            <p class="text-muted small mb-0">ربط خدمة Node، القوالب، وسجلات الإرسال والاستقبال</p>
        </div>
        <div class="card-body p-0">
    <ul class="nav nav-tabs wa-nav-tabs px-3 px-md-4 pt-3 mb-0 bg-light" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="tab-connection-link" href="#tab-connection" role="tab" data-bs-toggle="tab" data-bs-target="#tab-connection">الاتصال</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-service-link" href="#tab-service" role="tab" data-bs-toggle="tab" data-bs-target="#tab-service">إعدادات الخدمة</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-templates-link" href="#tab-templates" role="tab" data-bs-toggle="tab" data-bs-target="#tab-templates">قوالب الرسائل</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-logs-link" href="#tab-logs" role="tab" data-bs-toggle="tab" data-bs-target="#tab-logs">سجلات الرسائل</a>
        </li>
    </ul>

    <div class="tab-content border-top bg-white p-3 p-md-4">
        {{-- Tab 1: الاتصال --}}
        <div class="tab-pane fade show active" id="tab-connection" role="tabpanel">
            <div class="card mb-3 wa-border-left-info">
                <div class="card-body">
                    <h5 class="card-title">حالة الاتصال</h5>
                    <p class="mb-2">
                        <span id="waConnBadge" class="badge badge-secondary">جاري التحميل...</span>
                        <span id="waConnText" class="mr-2 text-muted"></span>
                    </p>
                    <dl class="row mb-0" id="waConnInfo" style="display:none;">
                        <dt class="col-sm-3">الاسم</dt>
                        <dd class="col-sm-9" id="waInfoName">—</dd>
                        <dt class="col-sm-3">الهاتف</dt>
                        <dd class="col-sm-9" id="waInfoPhone">—</dd>
                    </dl>
                    <div class="mt-3">
                        <button type="button" class="btn btn-success mr-2" id="waBtnInit">تهيئة / مسح QR</button>
                        <button type="button" class="btn btn-outline-danger mr-2" id="waBtnLogout">تسجيل خروج</button>
                        <button type="button" class="btn btn-outline-info" id="waBtnHealth">فحص الخدمة <span id="waHealthBadge" class="badge badge-light ml-1 d-none"></span></button>
                    </div>
                </div>
            </div>
            <div class="card wa-border-left-warning d-none" id="waQrCard">
                <div class="card-body text-center">
                    <h5 class="card-title">امسح رمز QR من تطبيق واتساب</h5>
                    <img id="waQrImg" src="" alt="QR" class="img-fluid mx-auto d-block" style="max-width:280px;">
                </div>
            </div>
            @php
                $waIncomingSec = (string) config('whatsapp.internal_webhook_secret', '');
                $waShowIncomingHint = ($waIncomingSec === '' || $waIncomingSec === 'WHATSAPP_INTERNAL_SECRET' || strlen($waIncomingSec) < 24);
            @endphp
            @if($waShowIncomingHint)
            <div class="alert alert-info small mb-0 mt-3 text-start">
                <strong>الرسائل الواردة:</strong> لعرض ردود العملاء في شاشة المحادثات، أضف في ملف <code>.env</code> الخاص بـ Laravel القيمة
                <code>WHATSAPP_INTERNAL_SECRET</code> (نص سري قوي)، وفي <code>.env</code> خدمة Node نفس القيمة تحت
                <code>WHATSAPP_INTERNAL_SECRET</code> مع
                <code>LARAVEL_INCOMING_WEBHOOK_URL={{ url('/api/whatsapp/incoming') }}</code>
                ثم أعد تشغيل Node. تختفي هذه الرسالة تلقائياً بعد ضبط سرّ قوي (24 حرفاً فأكثر) في Laravel.
            </div>
            @endif
        </div>

        {{-- Tab 2: إعدادات الخدمة --}}
        <div class="tab-pane fade" id="tab-service" role="tabpanel">
            <form method="post" action="{{ route('whatsapp.save-settings') }}" id="waSettingsForm">
                @csrf
                <div class="form-group">
                    <label for="service_url">عنوان خدمة Node</label>
                    <input type="url" name="service_url" id="service_url" class="form-control @error('service_url') is-invalid @enderror"
                           value="{{ old('service_url', $settings['service_url'] ?? '') }}" required>
                    @error('service_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="api_key">مفتاح API</label>
                    <div class="input-group">
                        <input type="password" name="api_key" id="api_key" class="form-control @error('api_key') is-invalid @enderror"
                               value="{{ old('api_key', $settings['api_key'] ?? '') }}" required minlength="8">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="waToggleApiKey">إظهار</button>
                        </div>
                        @error('api_key')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="country_code">رمز الدولة</label>
                        <input type="text" name="country_code" id="country_code" class="form-control"
                               value="{{ old('country_code', $settings['country_code'] ?? '20') }}" required maxlength="4">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="timeout">مهلة الطلب (ثانية)</label>
                        <input type="number" name="timeout" id="timeout" class="form-control" min="5" max="120"
                               value="{{ old('timeout', $settings['timeout'] ?? 30) }}" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="max_bulk">الحد الأقصى للإرسال الجماعي</label>
                        <input type="number" name="max_bulk" id="max_bulk" class="form-control" min="1" max="200"
                               value="{{ old('max_bulk', $settings['max_bulk'] ?? 50) }}" required>
                        <small class="text-warning d-none" id="maxBulkWarn">قيمة أعلى من 50 قد تزيد خطر الحظر.</small>
                    </div>
                </div>
                <div class="form-group">
                    <label for="bulk_delay">تأخير بين الرسائل الجماعية: <span id="bulkDelayVal">{{ old('bulk_delay', $settings['bulk_delay'] ?? 1500) }}</span> ms</label>
                    <input type="range" class="custom-range" name="bulk_delay" id="bulk_delay" min="500" max="5000" step="100"
                           value="{{ old('bulk_delay', $settings['bulk_delay'] ?? 1500) }}">
                </div>
                <div class="form-group">
                    <label for="test_phone">رقم اختبار (اختياري)</label>
                    <input type="text" name="test_phone" id="test_phone" class="form-control" maxlength="20"
                           value="{{ old('test_phone', $settings['test_phone'] ?? '') }}">
                </div>
                <div class="custom-control custom-switch mb-2">
                    <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" value="1"
                           {{ old('enabled', ($settings['enabled'] ?? '1') === '1') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="enabled">تفعيل الإرسال من Laravel</label>
                </div>
                <div class="custom-control custom-switch mb-3">
                    <input type="checkbox" class="custom-control-input" id="log_messages" name="log_messages" value="1"
                           {{ old('log_messages', ($settings['log_messages'] ?? '1') === '1') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="log_messages">تسجيل نص الرسائل في السجلات</label>
                </div>
                <button type="submit" class="btn btn-primary mr-2">حفظ الإعدادات</button>
                <button type="button" class="btn btn-outline-info" id="waBtnTestConn">اختبار الاتصال</button>
            </form>
        </div>

        {{-- Tab 3: قوالب --}}
        <div class="tab-pane fade" id="tab-templates" role="tabpanel">
            <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addTemplateModal">إضافة قالب</button>

            <div class="accordion mb-3" id="varAccordion">
                <div class="card">
                    <div class="card-header" id="headingVars">
                        <h5 class="mb-0">
                            <button class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVars" aria-expanded="false">
                                مرجع المتغيرات في القوالب
                            </button>
                        </h5>
                    </div>
                    <div id="collapseVars" class="collapse" data-bs-parent="#varAccordion">
                        <div class="card-body">
                            <p class="small text-muted mb-0">استخدم الصيغة <code>{name}</code> داخل نص القالب. انقر على الشارة لنسخ اسم المتغير.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-lg-2 g-3">
                @foreach($templates as $tpl)
                    <div class="col">
                        <div class="card mb-3" data-template-id="{{ $tpl->id }}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>{{ $tpl->label }}</span>
                                <code class="small">{{ $tpl->key }}</code>
                            </div>
                            <div class="card-body">
                                <form class="waTplForm">
                                    <div class="form-group">
                                        <label>العنوان</label>
                                        <input type="text" class="form-control tpl-label" value="{{ $tpl->label }}">
                                    </div>
                                    <div class="form-group">
                                        <label>النص</label>
                                        <textarea class="form-control tpl-body" rows="5">{{ $tpl->body }}</textarea>
                                    </div>
                                    @if(is_array($tpl->variables))
                                        <div class="mb-2">
                                            @foreach($tpl->variables as $var)
                                                <span class="badge badge-secondary mr-1 wa-chip tpl-var-chip">{{ $var }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="custom-control custom-switch mb-2">
                                        <input type="checkbox" class="custom-control-input tpl-active" id="tpl_act_{{ $tpl->id }}"
                                               {{ $tpl->is_active ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="tpl_act_{{ $tpl->id }}">مفعّل</label>
                                    </div>
                                </form>
                            </div>
                            <div class="card-footer">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-primary waTplSave">حفظ</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary waTplPreview" data-key="{{ $tpl->key }}">معاينة</button>
                                    @if(!$tpl->is_system)
                                        <button type="button" class="btn btn-sm btn-outline-danger waTplDelete">حذف</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Tab 4: سجلات --}}
        <div class="tab-pane fade" id="tab-logs" role="tabpanel">
            <div class="row mb-3">
                @php $st = \App\Models\WhatsAppLog::todayStats(); @endphp
                <div class="col-md-3 mb-2">
                    <div class="card wa-border-top-info"><div class="card-body py-2 text-center"><strong id="waStatTotal">{{ $st['total'] }}</strong><div class="small text-muted">إجمالي اليوم</div></div></div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="card wa-border-top-success"><div class="card-body py-2 text-center"><strong id="waStatSent">{{ $st['sent'] }}</strong><div class="small text-muted">مُرسل</div></div></div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="card wa-border-top-danger"><div class="card-body py-2 text-center"><strong id="waStatFailed">{{ $st['failed'] }}</strong><div class="small text-muted">فشل</div></div></div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="card wa-border-top-warning"><div class="card-body py-2 text-center"><strong id="waStatPending">{{ $st['pending'] }}</strong><div class="small text-muted">معلق</div></div></div>
                </div>
            </div>

            <form class="form-inline mb-3" id="waLogsFilter" onsubmit="return false;">
                <select name="status" id="logFilterStatus" class="form-control form-control-sm mr-2">
                    <option value="">كل الحالات</option>
                    <option value="sent">مرسل</option>
                    <option value="failed">فشل</option>
                    <option value="pending">معلق</option>
                    <option value="received">وارد</option>
                </select>
                <input type="text" name="phone" id="logFilterPhone" class="form-control form-control-sm mr-2" placeholder="الهاتف">
                <input type="date" name="date_from" id="logFilterFrom" class="form-control form-control-sm mr-2">
                <input type="date" name="date_to" id="logFilterTo" class="form-control form-control-sm mr-2">
                <button type="button" class="btn btn-sm btn-primary mr-2" id="waLogsApply">تصفية</button>
                <a href="{{ route('whatsapp.export-logs') }}" class="btn btn-sm btn-outline-secondary" id="waLogsExport">تصدير CSV</a>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-sm table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:36px;"><input type="checkbox" id="waLogCheckAll"></th>
                            <th>#</th>
                            <th>الهاتف</th>
                            <th>القالب</th>
                            <th>الرسالة</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody id="waLogsBody"></tbody>
                </table>
            </div>
            <nav>
                <ul class="pagination pagination-sm justify-content-center" id="waLogsPager"></ul>
            </nav>
        </div>
    </div>
        </div>
    </div>

{{-- Add template modal --}}
<div class="modal fade" id="addTemplateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">قالب جديد</h5>
                <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>المفتاح (إنجليزي، أرقام، _)</label>
                    <input type="text" class="form-control" id="newTplKey" placeholder="my_template">
                </div>
                <div class="form-group">
                    <label>العنوان</label>
                    <input type="text" class="form-control" id="newTplLabel">
                </div>
                <div class="form-group">
                    <label>النص</label>
                    <textarea class="form-control" id="newTplBody" rows="4"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-success" id="waNewTplSave">حفظ</button>
            </div>
        </div>
    </div>
</div>

{{-- Preview modal --}}
<div class="modal fade" id="previewTemplateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">معاينة القالب</h5>
                <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <pre class="bg-light p-3 rounded mb-0" id="waPreviewPre" style="white-space:pre-wrap;"></pre>
            </div>
        </div>
    </div>
</div>

<div id="waBulkBar" class="bg-dark text-white p-2 d-none text-center">
    <span id="waBulkSelectedCount" class="mr-2">0</span> محدد
    <button type="button" class="btn btn-sm btn-danger mr-2" id="waBulkDelete">حذف المحدد</button>
    <button type="button" class="btn btn-sm btn-outline-light" id="waBulkCancel">إلغاء</button>
</div>

</div>

@php
    $waTemplatesBase = preg_replace('#/\d+$#', '', route('whatsapp.templates.update', ['id' => 0]));
@endphp
<script>
(function() {
    const WA_ROUTES = {
        status: @json(route('whatsapp.status')),
        initialize: @json(route('whatsapp.initialize')),
        logout: @json(route('whatsapp.logout')),
        health: @json(route('whatsapp.health')),
        stats: @json(route('whatsapp.stats')),
        logs: @json(route('whatsapp.logs')),
        exportLogs: @json(route('whatsapp.export-logs')),
        bulkDeleteLogs: @json(route('whatsapp.logs.bulk-delete')),
        templatesStore: @json(route('whatsapp.templates.store')),
        templatesBase: @json($waTemplatesBase),
        templatesPreview: @json(route('whatsapp.templates.preview')),
    };

    function waModalShow(id) {
        var el = document.getElementById(id);
        if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        }
    }
    function waModalHide(id) {
        var el = document.getElementById(id);
        if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).hide();
        }
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    async function postJSON(url, data) {
        const r = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'},
            body: JSON.stringify(data || {})
        });
        return r.json();
    }

    async function putJSON(url, data) {
        const r = await fetch(url, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'},
            body: JSON.stringify(data || {})
        });
        return r.json();
    }

    async function deleteJSON(url, data) {
        const r = await fetch(url, {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'},
            body: JSON.stringify(data || {})
        });
        return r.json();
    }

    function showToast(message, type) {
        type = type || 'success';
        const map = { success: 'alert-success', error: 'alert-danger', warning: 'alert-warning', info: 'alert-info' };
        let c = document.getElementById('waToastContainer');
        if (!c) {
            c = document.createElement('div');
            c.id = 'waToastContainer';
            c.style.cssText = 'position:fixed;top:16px;left:50%;transform:translateX(-50%);z-index:20000;min-width:280px;max-width:90%;';
            document.body.appendChild(c);
        }
        const el = document.createElement('div');
        el.className = 'alert ' + (map[type] || map.success) + ' shadow-sm';
        el.setAttribute('role', 'alert');
        el.textContent = message;
        c.appendChild(el);
        setTimeout(function() {
            el.style.opacity = '0';
            el.style.transition = 'opacity 0.4s';
            setTimeout(function() { el.remove(); }, 400);
        }, 3000);
    }

    function tplUrl(id) { return WA_ROUTES.templatesBase + '/' + id; }

    window.formDirty = false;
    document.querySelectorAll('#waSettingsForm input, #waSettingsForm textarea, #waSettingsForm select').forEach(function(el) {
        el.addEventListener('change', function() { window.formDirty = true; });
        el.addEventListener('input', function() { window.formDirty = true; });
    });

    document.querySelectorAll('.wa-nav-tabs .nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (!href || href.charAt(0) !== '#') {
                return;
            }
            var servicePane = document.getElementById('tab-service');
            var serviceActive = servicePane && servicePane.classList.contains('active');
            if (window.formDirty && serviceActive && href !== '#tab-service') {
                if (!confirm('توجد تغييرات غير محفوظة في إعدادات الخدمة. المتابعة؟')) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                }
            }
        }, true);
    });

    document.addEventListener('shown.bs.tab', function(e) {
        var t = e.target;
        if (!t || !t.classList || !t.classList.contains('nav-link')) {
            return;
        }
        if (t.getAttribute('href') === '#tab-logs') {
            loadLogs(1);
            refreshStats();
        }
    });

    document.getElementById('waSettingsForm').addEventListener('submit', function() { window.formDirty = false; });

    document.getElementById('waToggleApiKey').addEventListener('click', function() {
        const inp = document.getElementById('api_key');
        inp.type = inp.type === 'password' ? 'text' : 'password';
        this.textContent = inp.type === 'password' ? 'إظهار' : 'إخفاء';
    });

    const bulkDelay = document.getElementById('bulk_delay');
    const bulkDelayVal = document.getElementById('bulkDelayVal');
    bulkDelay.addEventListener('input', function() { bulkDelayVal.textContent = this.value; });

    const maxBulk = document.getElementById('max_bulk');
    const maxBulkWarn = document.getElementById('maxBulkWarn');
    function syncMaxBulkWarn() {
        const v = parseInt(maxBulk.value, 10) || 0;
        maxBulkWarn.classList.toggle('d-none', v <= 50);
    }
    maxBulk.addEventListener('input', syncMaxBulkWarn);
    syncMaxBulkWarn();

    async function loadConnStatus() {
        const res = await fetch(WA_ROUTES.status, { headers: { 'Accept': 'application/json' } });
        const j = await res.json();
        if (!j.success || !j.data) {
            document.getElementById('waConnBadge').className = 'badge badge-danger';
            document.getElementById('waConnBadge').textContent = 'غير متصل بالخدمة';
            return;
        }
        const d = j.data;
        const st = d.status || 'unknown';
        const errHint = d.last_error ? String(d.last_error) : '';
        const badge = document.getElementById('waConnBadge');
        const map = {
            ready: ['badge-success', 'جاهز'],
            qr_ready: ['badge-warning', 'في انتظار مسح QR'],
            authenticated: ['badge-info', 'تم المصادقة'],
            initializing: ['badge-warning', 'جاري التهيئة'],
            disconnected: ['badge-secondary', 'غير متصل']
        };
        const m = map[st] || ['badge-secondary', st];
        badge.className = 'badge ' + m[0];
        badge.textContent = m[1];
        const connTxt = document.getElementById('waConnText');
        if (connTxt) {
            connTxt.textContent = (st === 'disconnected' && errHint) ? errHint : '';
            connTxt.classList.toggle('text-danger', !!(st === 'disconnected' && errHint));
            connTxt.classList.toggle('text-muted', !(st === 'disconnected' && errHint));
        }

        const qrCard = document.getElementById('waQrCard');
        const qrImg = document.getElementById('waQrImg');
        if (st === 'qr_ready' && d.qr) {
            qrCard.classList.remove('d-none');
            qrImg.src = d.qr;
        } else {
            qrCard.classList.add('d-none');
            qrImg.src = '';
        }

        const infoDl = document.getElementById('waConnInfo');
        if (d.info && d.info.phone) {
            infoDl.style.display = '';
            document.getElementById('waInfoName').textContent = d.info.pushname || '—';
            document.getElementById('waInfoPhone').textContent = d.info.phone || '—';
        } else {
            infoDl.style.display = 'none';
        }
    }

    document.getElementById('waBtnInit').addEventListener('click', async function() {
        const btn = this;
        btn.disabled = true;
        const r = await postJSON(WA_ROUTES.initialize, { reset_session: true });
        showToast(
            r.message || (r.success ? 'جاري التهيئة… انتظر ظهور رمز QR (قد يستغرق نصف دقيقة)' : 'فشل'),
            r.success ? 'success' : 'error'
        );
        await loadConnStatus();
        let n = 0;
        const poll = setInterval(async function() {
            n++;
            await loadConnStatus();
            try {
                const res = await fetch(WA_ROUTES.status, { headers: { 'Accept': 'application/json' } });
                const j = await res.json();
                if (j.success && j.data) {
                    const st = j.data.status;
                    if ((st === 'qr_ready' && j.data.qr) || st === 'ready') {
                        clearInterval(poll);
                        btn.disabled = false;
                        return;
                    }
                    if (st === 'disconnected' && j.data.last_error) {
                        clearInterval(poll);
                        btn.disabled = false;
                        return;
                    }
                }
            } catch (e) { /* ignore */ }
            if (n >= 40) {
                clearInterval(poll);
                btn.disabled = false;
            }
        }, 2000);
    });
    document.getElementById('waBtnLogout').addEventListener('click', async function() {
        if (!confirm('تسجيل الخروج من واتساب على هذا السيرفر؟')) return;
        const r = await postJSON(WA_ROUTES.logout, {});
        showToast(r.message || '', r.success ? 'success' : 'error');
        loadConnStatus();
    });
    document.getElementById('waBtnHealth').addEventListener('click', async function() {
        const t0 = performance.now();
        const r = await fetch(WA_ROUTES.health, { headers: { 'Accept': 'application/json' } });
        const ms = Math.round(performance.now() - t0);
        const j = await r.json();
        const b = document.getElementById('waHealthBadge');
        b.classList.remove('d-none');
        b.textContent = j.success ? (ms + ' ms') : 'خطأ';
        showToast(j.success ? ('الخدمة تعمل — ' + ms + ' ms') : (j.message || 'فشل'), j.success ? 'info' : 'error');
    });
    document.getElementById('waBtnTestConn').addEventListener('click', async function() {
        document.getElementById('waBtnHealth').click();
    });

    let logsPage = 1;
    let logsMeta = {};

    function buildExportUrl() {
        const p = new URLSearchParams();
        const st = document.getElementById('logFilterStatus').value;
        const ph = document.getElementById('logFilterPhone').value;
        const df = document.getElementById('logFilterFrom').value;
        const dt = document.getElementById('logFilterTo').value;
        if (st) p.set('status', st);
        if (ph) p.set('phone', ph);
        if (df) p.set('date_from', df);
        if (dt) p.set('date_to', dt);
        return WA_ROUTES.exportLogs + (p.toString() ? ('?' + p.toString()) : '');
    }
    document.getElementById('waLogsExport').addEventListener('click', function(e) {
        this.href = buildExportUrl();
    });

    async function loadLogs(page) {
        logsPage = page || 1;
        const p = new URLSearchParams();
        p.set('page', logsPage);
        const st = document.getElementById('logFilterStatus').value;
        const ph = document.getElementById('logFilterPhone').value;
        const df = document.getElementById('logFilterFrom').value;
        const dt = document.getElementById('logFilterTo').value;
        if (st) p.set('status', st);
        if (ph) p.set('phone', ph);
        if (df) p.set('date_from', df);
        if (dt) p.set('date_to', dt);
        const res = await fetch(WA_ROUTES.logs + '?' + p.toString(), { headers: { 'Accept': 'application/json' } });
        const j = await res.json();
        const tbody = document.getElementById('waLogsBody');
        tbody.innerHTML = '';
        if (!j.success || !j.data) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">لا بيانات</td></tr>';
            return;
        }
        logsMeta = j.meta || {};
        function escHtml(s) {
            return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        j.data.forEach(function(row) {
            const tr = document.createElement('tr');
            let badge = 'badge-secondary';
            if (row.status === 'sent') badge = 'badge-success';
            else if (row.status === 'failed') badge = 'badge-danger';
            else if (row.status === 'pending') badge = 'badge-warning';
            else if (row.status === 'received') badge = 'badge-info';
            const msg = row.message ? String(row.message) : '';
            const msgShort = msg.length > 60 ? msg.substring(0, 60) + '…' : msg;
            tr.innerHTML =
                '<td><input type="checkbox" class="wa-log-check" value="' + row.id + '"></td>' +
                '<td>' + row.id + '</td>' +
                '<td>' + escHtml(row.phone) + '</td>' +
                '<td>' + escHtml(row.template_key || '—') + '</td>' +
                '<td>' + escHtml(msgShort) + '</td>' +
                '<td><span class="badge ' + badge + '">' + escHtml(row.status) + '</span></td>' +
                '<td>' + escHtml(row.created_at || '') + '</td>';
            tbody.appendChild(tr);
        });
        wireLogChecks();

        const pager = document.getElementById('waLogsPager');
        pager.innerHTML = '';
        const liPrev = document.createElement('li');
        liPrev.className = 'page-item' + (logsMeta.current_page <= 1 ? ' disabled' : '');
        liPrev.innerHTML = '<a class="page-link" href="#">السابق</a>';
        liPrev.querySelector('a').addEventListener('click', function(e) {
            e.preventDefault();
            if (logsMeta.current_page > 1) loadLogs(logsMeta.current_page - 1);
        });
        pager.appendChild(liPrev);
        const liInfo = document.createElement('li');
        liInfo.className = 'page-item disabled';
        liInfo.innerHTML = '<span class="page-link">' + (logsMeta.current_page || 1) + ' / ' + (logsMeta.last_page || 1) + '</span>';
        pager.appendChild(liInfo);
        const liNext = document.createElement('li');
        liNext.className = 'page-item' + (logsMeta.current_page >= logsMeta.last_page ? ' disabled' : '');
        liNext.innerHTML = '<a class="page-link" href="#">التالي</a>';
        liNext.querySelector('a').addEventListener('click', function(e) {
            e.preventDefault();
            if (logsMeta.current_page < logsMeta.last_page) loadLogs(logsMeta.current_page + 1);
        });
        pager.appendChild(liNext);
    }

    function wireLogChecks() {
        document.querySelectorAll('.wa-log-check').forEach(function(cb) {
            cb.addEventListener('change', updateBulkBar);
        });
    }
    document.getElementById('waLogCheckAll').addEventListener('change', function() {
        document.querySelectorAll('.wa-log-check').forEach(function(cb) { cb.checked = this.checked; }.bind(this));
        updateBulkBar();
    });

    function updateBulkBar() {
        const sel = document.querySelectorAll('.wa-log-check:checked');
        const bar = document.getElementById('waBulkBar');
        document.getElementById('waBulkSelectedCount').textContent = sel.length;
        bar.classList.toggle('d-none', sel.length === 0);
    }

    document.getElementById('waBulkCancel').addEventListener('click', function() {
        document.querySelectorAll('.wa-log-check').forEach(function(cb) { cb.checked = false; });
        document.getElementById('waLogCheckAll').checked = false;
        updateBulkBar();
    });
    document.getElementById('waBulkDelete').addEventListener('click', async function() {
        const ids = Array.from(document.querySelectorAll('.wa-log-check:checked')).map(function(cb) { return parseInt(cb.value, 10); });
        if (!ids.length) return;
        if (!confirm('حذف السجلات المحددة؟')) return;
        const r = await deleteJSON(WA_ROUTES.bulkDeleteLogs, { ids: ids });
        showToast(r.success ? 'تم الحذف' : 'فشل', r.success ? 'success' : 'error');
        document.getElementById('waBulkCancel').click();
        loadLogs(logsPage);
    });

    document.getElementById('waLogsApply').addEventListener('click', function() { loadLogs(1); });

    async function refreshStats() {
        const res = await fetch(WA_ROUTES.stats, { headers: { 'Accept': 'application/json' } });
        const j = await res.json();
        if (j.success && j.data) {
            document.getElementById('waStatTotal').textContent = j.data.total;
            document.getElementById('waStatSent').textContent = j.data.sent;
            document.getElementById('waStatFailed').textContent = j.data.failed;
            document.getElementById('waStatPending').textContent = j.data.pending;
        }
    }

    document.querySelectorAll('.waTplSave').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            const card = btn.closest('.card');
            const id = card.getAttribute('data-template-id');
            const body = {
                label: card.querySelector('.tpl-label').value,
                body: card.querySelector('.tpl-body').value,
                is_active: card.querySelector('.tpl-active').checked
            };
            const r = await putJSON(tplUrl(id), body);
            showToast(r.success ? 'تم حفظ القالب' : (r.message || 'فشل'), r.success ? 'success' : 'error');
        });
    });
    document.querySelectorAll('.waTplDelete').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            const card = btn.closest('.card');
            const id = card.getAttribute('data-template-id');
            if (!confirm('حذف هذا القالب؟')) return;
            const r = await deleteJSON(tplUrl(id), {});
            showToast(r.success ? 'تم الحذف' : (r.message || 'فشل'), r.success ? 'success' : 'error');
            if (r.success) card.closest('.col-md-6').remove();
        });
    });
    document.querySelectorAll('.waTplPreview').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            const key = btn.getAttribute('data-key');
            const r = await postJSON(WA_ROUTES.templatesPreview, { key: key, data: {} });
            document.getElementById('waPreviewPre').textContent = r.preview || '(فارغ)';
            waModalShow('previewTemplateModal');
        });
    });

    document.querySelectorAll('.tpl-var-chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            const t = chip.textContent.trim();
            navigator.clipboard.writeText(t).then(function() { showToast('تم النسخ: ' + t, 'info'); });
        });
    });

    document.getElementById('waNewTplSave').addEventListener('click', async function() {
        const key = document.getElementById('newTplKey').value.trim();
        const label = document.getElementById('newTplLabel').value.trim();
        const body = document.getElementById('newTplBody').value;
        const r = await postJSON(WA_ROUTES.templatesStore, { key: key, label: label, body: body, is_active: true });
        showToast(r.success ? 'تم إنشاء القالب — أعد تحميل الصفحة' : (r.message || JSON.stringify(r)), r.success ? 'success' : 'error');
        if (r.success) {
            waModalHide('addTemplateModal');
            setTimeout(function() { location.reload(); }, 600);
        }
    });

    loadConnStatus();
    setInterval(loadConnStatus, 8000);
})();
</script>
@endsection
