@extends('members.cards._layout')

@section('styles')
    :root{
        --bg:#ffffff;
        --ink:#0f172a;
        --muted:#64748b;
        --line:rgba(15,23,42,.12);
        --accent:#2563eb;
        --accent2:#10b981;
    }

    .mw-wrap{
        width: 560px;
        max-width: 100%;
        margin: 0 auto;
        padding: 12px;
        background: #f8fafc;
        border-radius: 18px;
        border: 1px solid rgba(15,23,42,.08);
    }

    .mw-card{
        background: var(--bg);
        border: 1px solid var(--line);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 18px 40px rgba(2,6,23,.10);
    }

    .mw-top{
        padding: 14px 16px;
        display:flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        border-bottom: 1px solid rgba(15,23,42,.08);
    }

    .mw-brand{
        display:flex;
        align-items:center;
        gap: 10px;
        min-width: 0;
    }

    .mw-logo{
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: #ffffff;
        border: 1px solid rgba(15,23,42,.10);
        display:flex;
        align-items:center;
        justify-content:center;
        overflow:hidden;
        flex: 0 0 auto;
    }
    .mw-logo img{ width:100%; height:100%; object-fit:cover; }

    .mw-title{ min-width:0; }
    .mw-title .ar{
        font-weight: 900;
        color: var(--ink);
        font-size: 16px;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .mw-title .en{
        color: var(--muted);
        font-size: 11px;
        direction: ltr;
        text-align: left;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .mw-tag{
        padding: 7px 10px;
        border-radius: 999px;
        background: rgba(37,99,235,.08);
        border: 1px solid rgba(37,99,235,.15);
        color: #1d4ed8;
        font-weight: 800;
        font-size: 11px;
        white-space: nowrap;
    }

    .mw-body{
        padding: 14px 16px;
        display:grid;
        grid-template-columns: 1fr 150px;
        gap: 14px;
        align-items: start;
    }

    .mw-left{
        display:flex;
        gap: 12px;
        align-items: start;
        min-width: 0;
    }

    .mw-photo{
        width: 86px;
        height: 86px;
        border-radius: 18px;
        overflow:hidden;
        border: 1px solid rgba(15,23,42,.12);
        background: #fff;
        flex: 0 0 auto;
    }
    .mw-photo img{ width:100%; height:100%; object-fit:cover; }

    .mw-info{ flex:1; min-width:0; }

    .mw-name{
        font-size: 18px;
        font-weight: 900;
        color: var(--ink);
        margin-bottom: 10px;
        line-height: 1.25;
    }

    .mw-lines{
        display:grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .mw-line{
        border: 1px solid rgba(15,23,42,.10);
        border-radius: 14px;
        padding: 8px 10px;
        background: #ffffff;
    }

    .mw-line .k{
        color: var(--muted);
        font-size: 11px;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .mw-line .v{
        color: var(--ink);
        font-size: 12.5px;
        font-weight: 800;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        direction: ltr;
        text-align: left;
    }

    .mw-contact{
        margin-top: 10px;
        display:flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .mw-pill{
        display:flex;
        align-items:center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid rgba(15,23,42,.08);
        font-size: 12px;
        color: var(--ink);
    }
    .mw-pill i{ color: var(--accent2); }

    .mw-qr{
        border: 1px solid rgba(15,23,42,.10);
        border-radius: 16px;
        padding: 10px;
        background: #ffffff;
        display:flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    .mw-qr .box{
        width: 124px;
        height: 124px;
        border-radius: 14px;
        border: 1px solid rgba(15,23,42,.12);
        overflow:hidden;
        display:flex;
        align-items:center;
        justify-content:center;
        background: #fff;
    }
    .mw-qr .box img{ width:100%; height:100%; object-fit:contain; }
    .mw-qr .cap{
        font-size: 11px;
        color: var(--muted);
        font-weight: 800;
        letter-spacing: .5px;
    }

    .mw-foot{
        padding: 10px 16px;
        display:flex;
        justify-content: space-between;
        align-items:center;
        gap: 10px;
        border-top: 1px dashed rgba(15,23,42,.18);
        background: #fbfdff;
        color: var(--muted);
        font-size: 12px;
    }
    .mw-foot .id{
        font-weight: 900;
        color: var(--ink);
        background: rgba(37,99,235,.08);
        border: 1px solid rgba(37,99,235,.15);
        padding: 6px 10px;
        border-radius: 999px;
        white-space: nowrap;
    }

    @media print{
        .mw-wrap{ background:#fff; border:none; padding:0; }
        .mw-card{ box-shadow:none; }
    }
@endsection

@section('card')
    <div class="mw-wrap">
        <div class="mw-card">

            <div class="mw-top">
                <div class="mw-brand">
                    <div class="mw-logo">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="logo">
                        @else
                            <i class="ri-home-gym-line" style="font-size: 22px; color:#0f172a;"></i>
                        @endif
                    </div>

                    <div class="mw-title">
                        <div class="ar">{{ $gymNameAr ?: 'اسم الجيم' }}</div>
                        <div class="en">{{ $gymNameEn ?: 'Gym Name' }}</div>
                    </div>
                </div>

                <div class="mw-tag">MINIMAL</div>
            </div>

            <div class="mw-body">

                <div class="mw-left">
                    <div class="mw-photo">
                        @if($memberPhoto)
                            <img src="{{ $memberPhoto }}" alt="member">
                        @else
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%2394a3b8' d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E" alt="member">
                        @endif
                    </div>

                    <div class="mw-info">
                        <div class="mw-name">{{ $member->first_name }} {{ $member->last_name }}</div>

                        <div class="mw-lines">
                            <div class="mw-line">
                                <div class="k">رقم العضوية</div>
                                <div class="v">{{ $member->member_code }}</div>
                            </div>

                            <div class="mw-line">
                                <div class="k">الفرع</div>
                                <div class="v">{{ $member->branch ? $member->branch->getTranslation('name','ar') : '-' }}</div>
                            </div>

                            <div class="mw-line">
                                <div class="k">تاريخ الانضمام</div>
                                <div class="v">{{ optional($member->join_date)->format('Y-m-d') ?? '-' }}</div>
                            </div>

                            <div class="mw-line">
                                <div class="k">ID</div>
                                <div class="v">{{ $member->id }}</div>
                            </div>
                        </div>

                        <div class="mw-contact">
                            <div class="mw-pill">
                                <i class="ri-phone-line"></i>
                                <span>{{ $member->phone ?? '-' }}</span>
                            </div>

                            @if($member->email)
                                <div class="mw-pill">
                                    <i class="ri-mail-line"></i>
                                    <span>{{ $member->email }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mw-qr">
                    <div class="box">
                        <img src="data:image/png;base64,{{ $barcodePng }}" alt="QR Code">
                    </div>
                    <div class="cap">Scan</div>
                </div>

            </div>

            <div class="mw-foot">
                <div>Print & WhatsApp Ready</div>
                <div class="id">ID: {{ $member->id }}</div>
            </div>

        </div>
    </div>
@endsection
