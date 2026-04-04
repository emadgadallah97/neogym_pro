@extends('layouts.master_table')

@section('title', 'واتساب')
@section('content')

<style>
    .wa-page { border-radius: 12px; }
    .wa-status-header {
        background: linear-gradient(135deg, #0f5132 0%, #198754 45%, #20c997 100%);
        color: #fff;
        padding: 1rem 1.25rem;
        border-radius: 12px 12px 0 0;
    }
    .wa-status-header .text-white-75 { color: rgba(255,255,255,.85) !important; }
    .wa-conn-dot {
        width: 12px; height: 12px; border-radius: 50%;
        display: inline-block; flex-shrink: 0;
        box-shadow: 0 0 0 3px rgba(255,255,255,.25);
        animation: wa-pulse 2s ease-in-out infinite;
    }
    .wa-conn-dot.ready { background: #9fffc8; animation: none; }
    .wa-conn-dot.warn { background: #ffc107; }
    .wa-conn-dot.bad { background: #f8d7da; animation: none; }
    @keyframes wa-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .55; }
    }
    /* ارتفاع محدد + min-height:0 على الأبناء يمكّن #waMsgScroll من التمرير العمودي */
    .wa-main-split {
        min-height: min(560px, calc(100vh - 220px));
        height: min(640px, calc(100vh - 200px));
        max-height: calc(100vh - 180px);
        align-items: stretch;
    }
    .wa-sidebar {
        width: 300px; min-width: 280px; max-width: 340px;
        background: #f8faf9;
        border-inline-end: 1px solid rgba(0,0,0,.08);
        overflow-x: hidden;
        min-height: 0;
        max-height: 100%;
    }
    .wa-chat-col {
        flex: 1 1 0%;
        min-width: 0;
        min-height: 0;
        max-height: 100%;
        overflow: hidden;
    }
    #waChatPanel {
        min-height: 0 !important;
        overflow: hidden !important;
    }
    #waMsgScroll {
        flex: 1 1 0% !important;
        min-height: 0 !important;
        max-height: 100%;
        overflow-y: auto !important;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        touch-action: pan-y;
    }
    .wa-composer {
        flex-shrink: 0;
    }
    #waConvList {
        overflow-x: hidden;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    @media (max-width: 991.98px) {
        .wa-sidebar {
            width: 100%; max-width: none; min-height: 0;
            max-height: min(280px, 38vh);
            border-inline-end: none; border-bottom: 1px solid rgba(0,0,0,.08);
        }
        .wa-main-split {
            flex-direction: column;
            height: auto;
            min-height: min(520px, calc(100vh - 200px));
            max-height: calc(100vh - 160px);
        }
        .wa-chat-col {
            flex: 1 1 auto;
            min-height: min(360px, 52vh);
            max-height: min(560px, calc(100vh - 320px));
        }
    }
    .wa-conv-item {
        cursor: pointer;
        border-radius: 10px;
        margin: 0 .5rem .35rem;
        padding: .65rem .5rem .65rem .65rem !important;
        border: 1px solid transparent;
        transition: background .15s, border-color .15s, box-shadow .15s;
        max-width: 100%;
        box-sizing: border-box;
        align-items: flex-start !important;
    }
    .wa-conv-body {
        min-width: 0;
        flex: 1 1 0;
        overflow: hidden;
    }
    .wa-conv-preview {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
        overflow-wrap: anywhere;
        line-height: 1.4;
        max-height: 2.85em;
    }
    .wa-conv-side {
        flex: 0 0 auto;
        width: 3.5rem;
        min-width: 3.5rem;
        text-align: center;
    }
    .wa-conv-clock { font-weight: 700; font-size: .8rem; color: #198754; line-height: 1.2; }
    .wa-conv-day { font-size: .68rem; color: #6c757d; line-height: 1.2; margin-top: 2px; }
    .wa-conv-item:hover { background: #fff; border-color: rgba(25,135,84,.2); box-shadow: 0 2px 8px rgba(0,0,0,.04); }
    .wa-conv-item.active {
        background: #fff;
        border-color: #198754;
        box-shadow: 0 2px 12px rgba(25,135,84,.12);
    }
    .wa-avatar {
        width: 44px; height: 44px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .85rem;
        flex-shrink: 0;
    }
    .wa-chat-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,.08);
        padding: .75rem 1rem;
    }
    .wa-msg-area {
        background-color: #e5ddd5;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23c8c4bc' fill-opacity='.18'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .wa-bubble-sent { background: #d9fdd3; border-radius: 12px 12px 4px 12px; max-width: 72%; padding: 10px 14px; box-shadow: 0 1px 2px rgba(0,0,0,.06); }
    .wa-bubble-in { background: #fff; border-radius: 12px 12px 12px 4px; max-width: 72%; padding: 10px 14px; box-shadow: 0 1px 2px rgba(0,0,0,.06); }
    .wa-bubble-fail { border-inline-end: 3px solid #dc3545; }
    .wa-composer { background: #fff; border-top: 1px solid rgba(0,0,0,.08); padding: .75rem 1rem; }
    .wa-composer textarea { border-radius: 10px; border: 1px solid rgba(0,0,0,.12); }
    .wa-empty-card { max-width: 360px; padding: 2rem; }
</style>

<div class="container-fluid px-2 px-lg-3" dir="rtl" lang="ar">
    <div class="card wa-page border-0 shadow-sm mb-3">
        {{-- شريط حالة الاتصال --}}
        <div class="wa-status-header">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-3 flex-grow-1 min-w-0">
                    <span class="wa-conn-dot bad" id="waConnDot" title="حالة الاتصال"></span>
                    <div class="min-w-0">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="fw-semibold fs-6" id="waConnTitle">جاري التحقق من الخدمة…</span>
                            <span class="badge rounded-pill bg-light text-dark" id="waIdxStatusBadge">…</span>
                        </div>
                        <div class="small text-white-75 text-truncate mt-1" id="waConnSub">انتظر حتى يكتمل الربط مع خادم واتساب</div>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-light text-success" id="waBtnRefreshStatus" title="تحديث الحالة">
                        <i class="fas fa-sync-alt"></i> <span class="d-none d-sm-inline">تحديث</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#waQrModal" id="waOpenQrBtnTop" title="عرض QR">
                        <i class="fas fa-qrcode"></i> QR
                    </button>
                    <a href="{{ route('whatsapp.settings') }}" class="btn btn-sm btn-outline-light" title="الإعدادات"><i class="fas fa-cog"></i></a>
                    <button type="button" class="btn btn-sm btn-warning text-dark fw-semibold" data-bs-toggle="modal" data-bs-target="#waNewMsgModal">
                        <i class="fas fa-plus ms-1"></i> رسالة جديدة
                    </button>
                </div>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 pt-2 border-top border-white border-opacity-25">
                <small class="text-white-75" id="waLastSync">آخر تحديث: —</small>
                <small class="text-white-75 d-none d-md-inline" id="waIdxStatusText"></small>
            </div>
        </div>

        <div class="d-flex flex-column flex-lg-row wa-main-split bg-white rounded-bottom overflow-hidden">
            {{-- قائمة المحادثات --}}
            <div class="wa-sidebar d-flex flex-column">
                <div class="p-3 pb-2">
                    <label class="form-label small text-muted mb-1 fw-semibold">المحادثات</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="search" class="form-control border-start-0" id="waConvSearch" placeholder="بحث برقم أو نص…">
                    </div>
                </div>
                <div class="overflow-auto flex-fill px-1 pb-2" id="waConvList"></div>
            </div>

            {{-- منطقة الدردشة --}}
            <div class="flex-fill d-flex flex-column min-w-0 wa-chat-col">
                <div id="waEmptyState" class="flex-fill d-flex align-items-center justify-content-center text-muted wa-msg-area">
                    <div class="text-center wa-empty-card">
                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:72px;height:72px;">
                            <i class="fab fa-whatsapp text-success" style="font-size:2rem;"></i>
                        </div>
                        <h6 class="fw-semibold text-dark">مرحباً بك في واتساب</h6>
                        <p class="small mb-3">اختر محادثة من القائمة أو ابدأ رسالة جديدة. تأكد من حالة الاتصال بالأعلى قبل الإرسال.</p>
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#waNewMsgModal">رسالة جديدة</button>
                        <button type="button" class="btn btn-link btn-sm text-decoration-none" data-bs-toggle="modal" data-bs-target="#waQrModal" id="waOpenQrBtn">عرض رمز QR</button>
                    </div>
                </div>

                <div id="waChatPanel" class="d-none flex-fill flex-column min-h-0">
                    <div class="wa-chat-header d-flex align-items-center gap-3">
                        <div class="wa-avatar bg-success text-white" id="waChatAvatar">WA</div>
                        <div class="flex-fill min-w-0">
                            <div class="fw-semibold text-truncate" id="waChatPhone">—</div>
                            <small class="text-muted" id="waWaCheck">واتساب</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-light border" id="waChatRefresh" title="تحديث الرسائل"><i class="fas fa-sync-alt"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="waChatClearSel">إغلاق</button>
                    </div>
                    <div class="flex-fill overflow-auto p-3 wa-msg-area" id="waMsgScroll"></div>
                    <div class="wa-composer">
                        <div class="d-flex align-items-center mb-2 flex-wrap gap-2">
                            <select class="form-select form-select-sm" id="waTplPicker" style="max-width: 220px;">
                                <option value="">— إدراج قالب —</option>
                                @foreach($templates as $t)
                                    <option value="{{ $t->key }}">{{ $t->label }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted ms-auto" id="charCount">0/4096</small>
                        </div>
                        <div class="d-flex align-items-end gap-2">
                            <textarea class="form-control flex-fill" id="waMsgInput" rows="1" placeholder="اكتب رسالتك…" style="resize:none;overflow-y:auto;max-height:140px;"></textarea>
                            <button type="button" class="btn btn-success px-3" id="waBtnSend" title="إرسال"><i class="fas fa-paper-plane"></i></button>
                            <button type="button" class="btn btn-outline-secondary" id="bulkBtn" data-bs-toggle="modal" data-bs-target="#waBulkModal" title="إرسال جماعي"><i class="fas fa-users"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- QR Modal --}}
<div class="modal fade" id="waQrModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ربط واتساب</h5>
                <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <div id="waQrSpinner" class="spinner-border text-success d-none" role="status"></div>
                <img id="waQrModalImg" src="" alt="QR" class="img-fluid d-none">
                <ol class="text-right small text-muted mt-2 mb-0 pr-3">
                    <li>افتح واتساب على الهاتف</li>
                    <li>الإعدادات ← الأجهزة المرتبطة</li>
                    <li>اربط جهازاً وامسح الرمز</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- New message modal --}}
<div class="modal fade" id="waNewMsgModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">رسالة جديدة</h5>
                <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>الهاتف</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="waNewPhone">
                        <div class="input-group-append">
                            <span class="input-group-text" id="waNewPhoneInd"><i class="fas fa-question text-muted"></i></span>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" name="waNewSrc" id="waNewSrcTpl" value="tpl" data-bs-toggle="collapse" data-bs-target="#waNewTplCollapse" checked>
                        <label class="custom-control-label" for="waNewSrcTpl">قالب</label>
                    </div>
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" name="waNewSrc" id="waNewSrcFree" value="free" data-bs-toggle="collapse" data-bs-target="#waNewFreeCollapse">
                        <label class="custom-control-label" for="waNewSrcFree">نص حر</label>
                    </div>
                </div>
                <div id="waNewTplCollapse" class="collapse show">
                    <div class="form-group">
                        <select class="form-control" id="waNewTplSelect">
                            @foreach($templates as $t)
                                <option value="{{ $t->key }}">{{ $t->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="border rounded p-2 small bg-light" id="waNewTplPreview">—</div>
                </div>
                <div id="waNewFreeCollapse" class="collapse">
                    <textarea class="form-control" id="waNewFreeText" rows="4" maxlength="4096"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success btn-block" id="waNewSendBtn">إرسال</button>
            </div>
        </div>
    </div>
</div>

{{-- Bulk modal --}}
<div class="modal fade" id="waBulkModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إرسال جماعي</h5>
                <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>أرقام (سطر لكل رقم)</label>
                    <textarea class="form-control" id="waBulkPhones" rows="6"></textarea>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-1" id="waBulkParse">تحليل الأرقام</button>
                </div>
                <p class="small mb-2"><span class="badge badge-success mr-1" id="waBulkValidN">0</span> صالح
                    <span id="waBulkInvalidWrap" class="d-none mr-2">غير صالح: <span id="waBulkInvalidList"></span></span>
                </p>
                <div class="form-group">
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" name="waBulkSrc" id="waBulkSrcTpl" value="tpl" data-bs-toggle="collapse" data-bs-target="#waBulkTplCol" checked>
                        <label class="custom-control-label" for="waBulkSrcTpl">قالب</label>
                    </div>
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" name="waBulkSrc" id="waBulkSrcFree" value="free" data-bs-toggle="collapse" data-bs-target="#waBulkFreeCol">
                        <label class="custom-control-label" for="waBulkSrcFree">نص مخصص</label>
                    </div>
                </div>
                <div id="waBulkTplCol" class="collapse show">
                    <select class="form-control" id="waBulkTplSelect">
                        @foreach($templates as $t)
                            <option value="{{ $t->key }}">{{ $t->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="waBulkFreeCol" class="collapse">
                    <textarea class="form-control" id="waBulkMsgText" rows="3" maxlength="4096"></textarea>
                </div>
                <div class="form-group mt-3">
                    <label>التأخير بين الرسائل: <span id="waBulkDelayLab">1500</span> ms</label>
                    <input type="range" class="custom-range" id="waBulkDelay" min="500" max="5000" step="100" value="1500">
                    <small class="text-warning d-none" id="waBulkDelayWarn">تأخير أقل من 1000ms قد يزيد خطر الحظر.</small>
                </div>
                <p class="small text-muted mb-2" id="waBulkEst">—</p>
                <div class="progress d-none mb-2" id="waBulkProgWrap" style="height: 24px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" id="waBulkProg" style="width:0%">0%</div>
                </div>
                <div id="waBulkResult" class="d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="waBulkSendBtn">بدء الإرسال</button>
            </div>
        </div>
    </div>
</div>

@php
    $waConversationBase = preg_replace('#/\d+$#', '', route('whatsapp.conversation', ['phone' => '0']));
@endphp
<script>
(function() {
    const WA_ROUTES = {
        status: @json(route('whatsapp.status')),
        initialize: @json(route('whatsapp.initialize')),
        conversations: @json(route('whatsapp.conversations')),
        conversationBase: @json($waConversationBase),
        send: @json(route('whatsapp.send')),
        sendBulk: @json(route('whatsapp.send-bulk')),
        validateNumber: @json(route('whatsapp.validate-number')),
        templatesPreview: @json(route('whatsapp.templates.preview')),
        settings: @json(route('whatsapp.settings')),
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function waIdxModalShow(id) {
        var el = document.getElementById(id);
        if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        }
    }
    function waIdxModalHide(id) {
        var el = document.getElementById(id);
        if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).hide();
        }
    }
    function waCollapseShow(id) {
        var el = document.getElementById(id);
        if (el && typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
            bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).show();
        }
    }
    function waCollapseHide(id) {
        var el = document.getElementById(id);
        if (el && typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
            bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).hide();
        }
    }

    async function postJSON(url, data) {
        const r = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'},
            body: JSON.stringify(data || {})
        });
        return r.json();
    }

    function showToast(message, type) {
        type = type || 'success';
        const map = { success: 'alert-success', error: 'alert-danger', warning: 'alert-warning', info: 'alert-info' };
        let c = document.getElementById('waIdxToastContainer');
        if (!c) {
            c = document.createElement('div');
            c.id = 'waIdxToastContainer';
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

    let currentPhone = null;
    let statusInterval = null;
    let convInterval = null;
    let qrPollInterval = null;

    function conversationUrl(phone) {
        return WA_ROUTES.conversationBase + '/' + encodeURIComponent(phone);
    }

    function scrollToBottom(el) {
        el.scrollTop = el.scrollHeight;
    }

    function formatLogError(msg) {
        if (!msg) return '';
        var s = String(msg);
        if (s.indexOf('detached Frame') !== -1 || s.indexOf('Target closed') !== -1 || s.indexOf('Session closed') !== -1) {
            return 'انقطعت جلسة واتساب ويب. أوقف أي نسخة مكررة من خدمة Node، ثم من الإعدادات سجّل خروج/تهيئة وامسح QR من جديد.';
        }
        if (s.indexOf('Protocol error') !== -1) {
            return 'خطأ اتصال بالمتصفح المضمن. أعد تهيئة الجلسة من لوحة واتساب.';
        }
        return s;
    }

    function renderBubble(log) {
        const isInbound = log.status === 'received';
        const isSent = !isInbound && (log.status === 'sent' || log.status === 'pending');
        const failed = log.status === 'failed';
        const dt = log.sent_at || log.created_at || '';
        const timeStr = typeof dt === 'string' ? dt.substring(11, 16) : '';
        let statusIcon = '';
        if (isInbound) statusIcon = '<i class="fas fa-arrow-down text-primary" style="font-size:12px;" title="وارد"></i>';
        else if (log.status === 'sent') statusIcon = '<i class="fas fa-check text-muted" style="font-size:12px;"></i>';
        else if (log.status === 'pending') statusIcon = '<i class="fas fa-clock text-warning" style="font-size:12px;"></i>';
        else if (failed) statusIcon = '<i class="fas fa-exclamation-circle text-danger" style="font-size:12px;"></i>';

        const align = isInbound ? 'justify-content-start' : 'justify-content-end';
        const bubbleClass = isInbound ? 'wa-bubble-in' : 'wa-bubble-sent';
        const failClass = failed ? ' wa-bubble-fail' : '';
        const tplBadge = log.template_key
            ? '<span class="badge bg-info bg-opacity-75 me-1">' + String(log.template_key).replace(/</g, '') + '</span>'
            : '';
        const inBadge = isInbound ? '<span class="badge bg-secondary me-1">وارد</span>' : '';

        return '<div class="d-flex ' + align + ' mb-2">' +
            '<div class="' + bubbleClass + failClass + '">' +
            inBadge + tplBadge +
            '<p class="mb-1 text-break">' + String(log.message || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p>' +
            (failed && log.error ? '<small class="text-danger d-block">' + String(formatLogError(log.error)).replace(/</g, '&lt;') + '</small>' : '') +
            '<div class="d-flex justify-content-end align-items-center">' +
            '<small class="text-muted ms-1">' + timeStr + '</small>' +
            '<span class="me-1">' + statusIcon + '</span>' +
            '</div></div></div>';
    }

    function setLastSync(ok) {
        var el = document.getElementById('waLastSync');
        if (!el) return;
        var t = new Date().toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        el.textContent = ok ? ('آخر تحديث: ' + t) : ('تعذر التحديث — ' + t);
    }

    function updateStatusUI(data) {
        const badge = document.getElementById('waIdxStatusBadge');
        const txt = document.getElementById('waIdxStatusText');
        const dot = document.getElementById('waConnDot');
        const title = document.getElementById('waConnTitle');
        const sub = document.getElementById('waConnSub');

        if (!data) {
            if (dot) { dot.className = 'wa-conn-dot bad'; }
            if (title) { title.textContent = 'تعذر الاتصال بخدمة واتساب'; }
            if (sub) { sub.textContent = 'تأكد من تشغيل خدمة Node والرابط في الإعدادات، ثم اضغط «تحديث».'; }
            if (badge) {
                badge.className = 'badge rounded-pill bg-danger';
                badge.textContent = 'غير متاح';
            }
            if (txt) { txt.textContent = ''; }
            setLastSync(false);
            return;
        }

        const st = data.status || 'unknown';
        const lastErr = data.last_error ? String(data.last_error) : '';
        const badgeStyles = {
            ready: ['rounded-pill bg-white text-success fw-semibold', 'جاهز'],
            qr_ready: ['rounded-pill bg-warning text-dark fw-semibold', 'مسح QR'],
            authenticated: ['rounded-pill bg-info text-dark fw-semibold', 'مصادقة'],
            initializing: ['rounded-pill bg-warning text-dark fw-semibold', 'تهيئة'],
            disconnected: ['rounded-pill bg-secondary', 'غير متصل']
        };
        const bs = badgeStyles[st] || ['rounded-pill bg-light text-dark', st];
        if (badge) {
            badge.className = 'badge ' + bs[0];
            badge.textContent = bs[1];
        }
        if (txt) { txt.textContent = ''; }

        if (dot) {
            dot.className = 'wa-conn-dot ' + (st === 'ready' ? 'ready' : (st === 'qr_ready' || st === 'initializing' || st === 'authenticated' ? 'warn' : 'bad'));
        }
        if (title && sub) {
            if (st === 'ready') {
                var pn = (data.info && data.info.pushname) ? data.info.pushname : 'متصل';
                var ph = (data.info && data.info.phone) ? data.info.phone : '';
                title.textContent = pn;
                sub.textContent = ph ? ('+' + ph + ' · يمكنك إرسال الرسائل الآن') : 'الجلسة جاهزة — يمكنك إرسال الرسائل';
            } else if (st === 'qr_ready') {
                title.textContent = 'في انتظار مسح رمز QR';
                sub.textContent = 'من الهاتف: واتساب ← الإعدادات ← الأجهزة المرتبطة ← ربط جهاز';
            } else if (st === 'authenticated') {
                title.textContent = 'تمت المصادقة';
                sub.textContent = 'جاري إكمال الربط…';
            } else if (st === 'initializing') {
                title.textContent = 'جاري تهيئة واتساب';
                sub.textContent = 'قد يستغرق الأمر بضع لحظات، يرجى الانتظار';
            } else {
                title.textContent = 'غير متصل بجلسة واتساب';
                sub.textContent = lastErr
                    ? lastErr
                    : 'من الإعدادات: تهيئة الاتصال أو امسح QR لربط الحساب';
            }
        }

        setLastSync(true);

        const qrModalEl = document.getElementById('waQrModal');
        const img = document.getElementById('waQrModalImg');
        const spin = document.getElementById('waQrSpinner');
        if (st === 'qr_ready' && data.qr) {
            img.src = data.qr;
            img.classList.remove('d-none');
            spin.classList.add('d-none');
            if (qrModalEl && !qrModalEl.classList.contains('show')) {
                waIdxModalShow('waQrModal');
            }
            startQrPoll();
        } else {
            img.classList.add('d-none');
            img.src = '';
            var waitingForQr = (st === 'initializing' || st === 'authenticated' || (st === 'qr_ready' && !data.qr));
            if (waitingForQr) {
                spin.classList.remove('d-none');
            } else {
                spin.classList.add('d-none');
                stopQrPoll();
            }
            if (st === 'ready') {
                waIdxModalHide('waQrModal');
            }
        }
    }

    function startQrPoll() {
        stopQrPoll();
        qrPollInterval = setInterval(loadStatus, 4000);
    }
    function stopQrPoll() {
        if (qrPollInterval) {
            clearInterval(qrPollInterval);
            qrPollInterval = null;
        }
    }

    async function loadStatus() {
        try {
            const res = await fetch(WA_ROUTES.status, { headers: { 'Accept': 'application/json' } });
            if (res.status === 429) {
                showToast('تم تجاوز حد الطلبات مؤقتاً — انتظر ثم اضغط «تحديث»', 'warning');
                setLastSync(false);
                return null;
            }
            const j = await res.json();
            if (j.success && j.data) {
                updateStatusUI(j.data);
                return j.data;
            }
            updateStatusUI(null);
        } catch (e) {
            updateStatusUI(null);
        }
        return null;
    }

    function initialsFromPhone(phone) {
        const d = String(phone || '').replace(/\D/g, '');
        return d.length >= 2 ? d.substr(-2) : 'WA';
    }

    async function loadConversations() {
        const q = document.getElementById('waConvSearch').value.trim();
        const url = WA_ROUTES.conversations + (q ? ('?q=' + encodeURIComponent(q)) : '');
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const j = await res.json();
        const box = document.getElementById('waConvList');
        box.innerHTML = '';
        if (!j.success || !j.data || !j.data.length) {
            box.innerHTML = '<div class="p-3 text-muted small text-center">لا محادثات</div>';
            return;
        }
        function escAttr(s) {
            return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
        }
        function escHtml(s) {
            return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
        j.data.forEach(function(row) {
            const div = document.createElement('div');
            div.className = 'wa-conv-item d-flex gap-2' + (currentPhone === row.phone ? ' active' : '');
            div.setAttribute('data-phone', row.phone);
            const initials = initialsFromPhone(row.phone);
            const fc = row.failed_count ? '<span class="badge bg-danger rounded-pill">' + row.failed_count + '</span>' : '';
            const lt = row.last_time || '';
            const ld = row.last_date || '';
            const prev = row.preview || '';
            div.innerHTML =
                '<div class="wa-avatar bg-success text-white flex-shrink-0">' + escHtml(initials) + '</div>' +
                '<div class="wa-conv-body">' +
                '<div class="fw-semibold text-dark text-truncate mb-1" title="' + escAttr(row.phone) + '">' + escHtml(row.phone) + '</div>' +
                '<div class="wa-conv-preview text-muted" title="' + escAttr(prev) + '">' + escHtml(prev) + '</div>' +
                '</div>' +
                '<div class="wa-conv-side d-flex flex-column align-items-center gap-1 pt-1">' +
                '<span class="wa-conv-clock">' + escHtml(lt) + '</span>' +
                '<span class="wa-conv-day">' + escHtml(ld) + '</span>' +
                (fc ? '<div class="mt-1">' + fc + '</div>' : '') +
                '</div>';
            div.addEventListener('click', function() { selectConversation(row.phone); });
            box.appendChild(div);
        });
    }

    function selectConversation(phone) {
        currentPhone = phone;
        document.querySelectorAll('.wa-conv-item').forEach(function(el) {
            el.classList.toggle('active', el.getAttribute('data-phone') === phone);
        });
        document.getElementById('waEmptyState').classList.add('d-none');
        var chat = document.getElementById('waChatPanel');
        chat.classList.remove('d-none');
        chat.classList.add('d-flex');
        document.getElementById('waChatPhone').textContent = phone;
        document.getElementById('waChatAvatar').textContent = initialsFromPhone(phone);
        loadMessages(phone);
        checkWaRegistered(phone);
    }

    async function checkWaRegistered(phone) {
        const j = await postJSON(WA_ROUTES.validateNumber, { phone: phone });
        const el = document.getElementById('waWaCheck');
        if (j.success && j.data) {
            if (j.data.hasWhatsApp === true) el.textContent = 'مسجل في واتساب';
            else if (j.data.hasWhatsApp === false) el.textContent = 'قد لا يكون مسجلاً';
            else el.textContent = 'واتساب (غير متأكد)';
        }
    }

    async function loadMessages(phone) {
        const res = await fetch(conversationUrl(phone), { headers: { 'Accept': 'application/json' } });
        const j = await res.json();
        const area = document.getElementById('waMsgScroll');
        area.innerHTML = '';
        if (!j.success || !j.data) return;
        let lastDate = '';
        j.data.forEach(function(log) {
            const d = log.created_at ? String(log.created_at).substring(0, 10) : '';
            if (d && d !== lastDate) {
                lastDate = d;
                const sep = document.createElement('div');
                sep.className = 'text-center mb-2';
                sep.innerHTML = '<span class="badge bg-white text-secondary shadow-sm px-3 py-2 rounded-pill">' + d + '</span>';
                area.appendChild(sep);
            }
            const wrap = document.createElement('div');
            wrap.innerHTML = renderBubble(log);
            area.appendChild(wrap.firstElementChild);
        });
        scrollToBottom(area);
    }

    async function sendMessage() {
        const msg = document.getElementById('waMsgInput').value.trim();
        if (!currentPhone) {
            showToast('اختر محادثة أولاً', 'warning');
            return;
        }
        if (!msg) return;
        const r = await postJSON(WA_ROUTES.send, { phone: currentPhone, message: msg });
        if (r.success) {
            showToast('تم الإرسال', 'success');
            document.getElementById('waMsgInput').value = '';
            syncCharCount();
            loadMessages(currentPhone);
            loadConversations();
        } else {
            showToast(r.message || 'فشل الإرسال', 'error');
        }
    }

    function syncCharCount() {
        const v = document.getElementById('waMsgInput').value.length;
        document.getElementById('charCount').textContent = v + '/4096';
    }

    function initPolling() {
        if (statusInterval) clearInterval(statusInterval);
        if (convInterval) clearInterval(convInterval);
        statusInterval = setInterval(function() {
            loadStatus();
        }, 15000);
        convInterval = setInterval(function() {
            if (document.visibilityState === 'hidden') {
                return;
            }
            loadConversations();
        }, 25000);
    }

    let validateTimer = null;
    function validatePhoneUI(inputEl, indicatorEl) {
        clearTimeout(validateTimer);
        validateTimer = setTimeout(async function() {
            const phone = inputEl.value.trim();
            if (!phone) {
                indicatorEl.innerHTML = '<i class="fas fa-question text-muted"></i>';
                return;
            }
            const j = await postJSON(WA_ROUTES.validateNumber, { phone: phone });
            if (j.success && j.data && j.data.valid) {
                indicatorEl.innerHTML = '<i class="fas fa-check text-success"></i>';
            } else {
                indicatorEl.innerHTML = '<i class="fas fa-times text-danger"></i>';
            }
        }, 600);
    }

    async function insertTemplate(key) {
        if (!key) return;
        const r = await postJSON(WA_ROUTES.templatesPreview, { key: key, data: {} });
        if (r.success && r.preview) {
            document.getElementById('waMsgInput').value = r.preview;
            syncCharCount();
        }
    }

    document.getElementById('waBtnSend').addEventListener('click', sendMessage);
    document.getElementById('waMsgInput').addEventListener('input', function() {
        syncCharCount();
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
    document.getElementById('waTplPicker').addEventListener('change', function() {
        insertTemplate(this.value);
    });
    document.getElementById('waChatRefresh').addEventListener('click', function() {
        if (currentPhone) loadMessages(currentPhone);
    });
    document.getElementById('waChatClearSel').addEventListener('click', function() {
        currentPhone = null;
        var chat = document.getElementById('waChatPanel');
        chat.classList.add('d-none');
        chat.classList.remove('d-flex');
        document.getElementById('waEmptyState').classList.remove('d-none');
        document.querySelectorAll('.wa-conv-item').forEach(function(el) { el.classList.remove('active'); });
    });

    document.getElementById('waConvSearch').addEventListener('input', function() {
        loadConversations();
    });

    document.getElementById('waBtnRefreshStatus').addEventListener('click', function() {
        loadStatus();
        loadConversations();
    });
    ['waOpenQrBtn', 'waOpenQrBtnTop'].forEach(function(btnId) {
        var b = document.getElementById(btnId);
        if (b) {
            b.addEventListener('click', async function() {
                var sp = document.getElementById('waQrSpinner');
                if (sp) sp.classList.remove('d-none');
                var data = await loadStatus();
                if (data && data.status === 'ready') {
                    if (sp) sp.classList.add('d-none');
                    showToast('الجلسة متصلة بالفعل — لا حاجة لمسح QR', 'info');
                    return;
                }
                if (data && (data.status === 'initializing' || data.status === 'authenticated' || (data.status === 'qr_ready' && data.qr))) {
                    startQrPoll();
                    return;
                }
                var r = await postJSON(WA_ROUTES.initialize, { reset_session: true });
                if (!r.success) {
                    showToast(r.message || 'فشل طلب التهيئة', 'error');
                    if (sp) sp.classList.add('d-none');
                    await loadStatus();
                    return;
                }
                await loadStatus();
                startQrPoll();
            });
        }
    });

    document.getElementById('waNewPhone').addEventListener('input', function() {
        validatePhoneUI(this, document.getElementById('waNewPhoneInd'));
    });

    document.getElementById('waNewTplSelect').addEventListener('change', async function() {
        const r = await postJSON(WA_ROUTES.templatesPreview, { key: this.value, data: {} });
        document.getElementById('waNewTplPreview').textContent = r.preview || '—';
    });

    document.getElementById('waNewSendBtn').addEventListener('click', async function() {
        const phone = document.getElementById('waNewPhone').value.trim();
        const useTpl = document.getElementById('waNewSrcTpl').checked;
        let message = '';
        if (useTpl) {
            const key = document.getElementById('waNewTplSelect').value;
            const r = await postJSON(WA_ROUTES.templatesPreview, { key: key, data: {} });
            message = r.preview || '';
        } else {
            message = document.getElementById('waNewFreeText').value;
        }
        if (!phone || !message) {
            showToast('أكمل الحقول', 'warning');
            return;
        }
        const r = await postJSON(WA_ROUTES.send, { phone: phone, message: message });
        showToast(r.success ? 'تم الإرسال' : (r.message || 'فشل'), r.success ? 'success' : 'error');
        if (r.success) {
            waIdxModalHide('waNewMsgModal');
            selectConversation(r.phone || phone.replace(/\D/g, ''));
            loadConversations();
        }
    });

    document.getElementById('waBulkDelay').addEventListener('input', function() {
        document.getElementById('waBulkDelayLab').textContent = this.value;
        document.getElementById('waBulkDelayWarn').classList.toggle('d-none', parseInt(this.value, 10) >= 1000);
        updateBulkEst();
    });

    let bulkValidPhones = [];
    document.getElementById('waBulkParse').addEventListener('click', function() {
        const lines = document.getElementById('waBulkPhones').value.split(/\r?\n/).map(function(l) { return l.trim(); }).filter(Boolean);
        bulkValidPhones = [];
        const invalid = [];
        lines.forEach(function(line) {
            const digits = line.replace(/\D/g, '');
            if (digits.length >= 7 && digits.length <= 15) bulkValidPhones.push(digits);
            else invalid.push(line);
        });
        document.getElementById('waBulkValidN').textContent = String(bulkValidPhones.length);
        const wrap = document.getElementById('waBulkInvalidWrap');
        if (invalid.length) {
            wrap.classList.remove('d-none');
            document.getElementById('waBulkInvalidList').innerHTML = invalid.map(function(x) {
                return '<span class="badge badge-danger mr-1">' + String(x).replace(/</g, '') + '</span>';
            }).join(' ');
        } else {
            wrap.classList.add('d-none');
        }
        updateBulkEst();
    });

    function updateBulkEst() {
        const n = bulkValidPhones.length;
        const d = parseInt(document.getElementById('waBulkDelay').value, 10) || 1500;
        const sec = n > 0 ? Math.ceil((n * d) / 1000) : 0;
        const min = Math.ceil(sec / 60);
        document.getElementById('waBulkEst').textContent = n
            ? ('سيتم الإرسال لـ ' + n + ' رقم — المدة التقديرية: حوالي ' + min + ' دقيقة')
            : '—';
    }

    document.getElementById('waBulkSendBtn').addEventListener('click', async function() {
        if (!bulkValidPhones.length) {
            showToast('حلّل الأرقام أولاً', 'warning');
            return;
        }
        const useTpl = document.getElementById('waBulkSrcTpl').checked;
        let text = '';
        let templateKey = null;
        if (useTpl) {
            templateKey = document.getElementById('waBulkTplSelect').value;
            const pr = await postJSON(WA_ROUTES.templatesPreview, { key: templateKey, data: {} });
            text = pr.preview || '';
        } else {
            text = document.getElementById('waBulkMsgText').value;
        }
        if (!text) {
            showToast('أدخل نص الرسالة أو اختر قالباً', 'warning');
            return;
        }
        const delay = parseInt(document.getElementById('waBulkDelay').value, 10) || 1500;
        const messages = bulkValidPhones.map(function(p) { return { phone: p, message: text }; });
        const progWrap = document.getElementById('waBulkProgWrap');
        const prog = document.getElementById('waBulkProg');
        const resBox = document.getElementById('waBulkResult');
        progWrap.classList.remove('d-none');
        resBox.classList.add('d-none');
        prog.style.width = '30%';
        prog.textContent = '…';
        const payload = { messages: messages, delay_ms: delay };
        if (templateKey) payload.template_key = templateKey;
        const r = await postJSON(WA_ROUTES.sendBulk, payload);
        prog.style.width = '100%';
        prog.textContent = '100%';
        let html = '';
        if (r.success && r.data) {
            html = '<div class="alert alert-info">تم: ' + r.data.sent + ' — فشل: ' + r.data.failed + '</div>';
        } else {
            html = '<div class="alert alert-danger">' + (r.message || 'فشل') + '</div>';
        }
        resBox.innerHTML = html;
        resBox.classList.remove('d-none');
        showToast(r.success ? 'اكتمل الإرسال الجماعي' : (r.message || 'فشل'), r.success ? 'success' : 'error');
        loadConversations();
    });

    document.querySelectorAll('input[name="waNewSrc"]').forEach(function(inp) {
        inp.addEventListener('change', function() {
            if (this.value === 'tpl') {
                waCollapseShow('waNewTplCollapse');
                waCollapseHide('waNewFreeCollapse');
            } else {
                waCollapseShow('waNewFreeCollapse');
                waCollapseHide('waNewTplCollapse');
            }
        });
    });
    document.querySelectorAll('input[name="waBulkSrc"]').forEach(function(inp) {
        inp.addEventListener('change', function() {
            if (this.value === 'tpl') {
                waCollapseShow('waBulkTplCol');
                waCollapseHide('waBulkFreeCol');
            } else {
                waCollapseShow('waBulkFreeCol');
                waCollapseHide('waBulkTplCol');
            }
        });
    });

    loadStatus();
    loadConversations();
    initPolling();
    syncCharCount();
    if (document.getElementById('waNewTplSelect').options.length) {
        document.getElementById('waNewTplSelect').dispatchEvent(new Event('change'));
    }
})();
</script>
@endsection
