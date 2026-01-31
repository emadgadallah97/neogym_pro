@extends('members.cards._layout')

@section('styles')
    :root{
        --ink:#f8fafc;
        --muted:#cbd5e1;

        --gold:#f59e0b;
        --gold2:#fbbf24;

        --bg1:#070a12;
        --bg2:#0b1220;

        --line:rgba(255,255,255,.12);
        --soft:rgba(255,255,255,.06);
        --panel:#0b1220; /* لوحة بيانات داكنة ثابتة */
    }

    .bg-card, .bg-card *{ box-sizing: border-box; }
    .bg-card a, .bg-card a:hover{ color: inherit; text-decoration: none; }

    .bg-wrap{
        width: 560px;
        max-width: 100%;
        margin: 0 auto;
        padding: 12px;
        background:
            radial-gradient(1100px 520px at 15% 0%, rgba(245,158,11,.10), transparent 60%),
            radial-gradient(900px 520px at 85% 20%, rgba(251,191,36,.08), transparent 60%),
            linear-gradient(180deg, #0b1220, #070a12);
        border-radius: 18px;
        border: 1px solid rgba(255,255,255,.06);
    }

    .bg-card{
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.10);
        box-shadow: 0 20px 55px rgba(0,0,0,.45);
        background: linear-gradient(135deg, var(--bg1), var(--bg2));
        position: relative;
        color: var(--ink);
    }

    .bg-sheen{
        position:absolute;
        inset:-60px -90px auto auto;
        width: 320px;
        height: 320px;
        background: radial-gradient(circle at 30% 30%, rgba(245,158,11,.22), transparent 62%);
        transform: rotate(12deg);
        pointer-events:none;
        opacity:.9;
    }

    .bg-head{
        padding: 16px;
        display:flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(255,255,255,.10);
        background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.00));
        position: relative;
        z-index: 1;
    }

    .bg-brand{
        display:flex;
        align-items:center;
        gap: 10px;
        min-width: 0;
    }

    .bg-logo{
        width: 54px;
        height: 54px;
        border-radius: 16px;
        background: rgba(255,255,255,.06);
        border: 1px solid rgba(255,255,255,.14);
        display:flex;
        align-items:center;
        justify-content:center;
        overflow:hidden;
        flex: 0 0 auto;
        box-shadow: 0 0 0 2px rgba(245,158,11,.08);
    }
    .bg-logo img{ width:100%; height:100%; object-fit:cover; }

    .bg-title{ min-width:0; }
    .bg-title .ar{
        font-weight: 900;
        color: #fff;
        font-size: 18px;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .bg-title .en{
        color: rgba(248,250,252,.75);
        font-size: 11px;
        direction: ltr;
        text-align: left;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .bg-badge{
        padding: 7px 10px;
        border-radius: 999px;
        background: rgba(245,158,11,.14);
        border: 1px solid rgba(245,158,11,.28);
        color: #fde68a;
        font-weight: 900;
        font-size: 11px;
        letter-spacing: .8px;
        white-space: nowrap;
    }

    .bg-body{
        padding: 14px 16px;
        display:grid;
        grid-template-columns: 120px 1fr 150px; /* صورة - بيانات - QR */
        gap: 14px;
        align-items: center;
        position: relative;
        z-index: 1;
    }

    .bg-photo{
        width: 120px;
        height: 120px;
        border-radius: 18px;
        overflow:hidden;
        border: 1px solid rgba(245,158,11,.35);
        box-shadow: 0 16px 30px rgba(0,0,0,.35);
        background: rgba(255,255,255,.05);
    }
    .bg-photo img{ width:100%; height:100%; object-fit:cover; }

    /* لوحة البيانات الداكنة */
    .bg-center{
        border-radius: 16px;
        background:
            radial-gradient(260px 140px at 20% 0%, rgba(245,158,11,.10), transparent 60%),
            linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02)),
            var(--panel);
        border: 1px solid rgba(245,158,11,.22);
        padding: 12px 12px;
        min-width: 0;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.10);
    }

    .bg-name{
        font-weight: 900;
        font-size: 18px;
        color: #fff;
        margin-bottom: 10px;
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .bg-row{
        display:flex;
        justify-content: space-between;
        gap: 10px;
        padding: 7px 0;
        border-bottom: 1px dashed rgba(255,255,255,.14);
        font-size: 12.5px;
    }
    .bg-row:last-child{ border-bottom: none; }

    .bg-row .k{
        color: rgba(248,250,252,.70);
        white-space: nowrap;
    }

    .bg-row .v{
        color: #fff;
        font-weight: 900;
        direction: ltr;
        text-align: left;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .bg-contact{
        margin-top: 10px;
        display:flex;
        flex-wrap: wrap;
        gap: 8px;
        font-size: 12px;
    }

    .bg-pill{
        display:flex;
        align-items:center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(0,0,0,.18);
        border: 1px solid rgba(255,255,255,.12);
        color: #fff;
        max-width: 100%;
    }
    .bg-pill i{ color: var(--gold2); }
    .bg-pill span{
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        direction: ltr;
        text-align: left;
    }

    .bg-qr{
        width: 150px;
        flex: 0 0 auto;
        text-align:center;
        padding: 10px;
        border-radius: 16px;
        background: rgba(255,255,255,.04);
        border: 1px solid rgba(255,255,255,.12);
    }
    .bg-qr .box{
        width: 124px;
        height: 124px;
        border-radius: 14px;
        overflow:hidden;
        background: #ffffff;
        border: 1px solid rgba(245,158,11,.25);
        display:flex;
        align-items:center;
        justify-content:center;
        margin: 0 auto;
    }
    .bg-qr .box img{ width:100%; height:100%; object-fit:contain; }
    .bg-qr .cap{
        margin-top: 8px;
        font-size: 11px;
        color: rgba(248,250,252,.70);
        font-weight: 900;
        letter-spacing: .7px;
    }

    .bg-foot{
        padding: 10px 16px;
        border-top: 1px solid rgba(255,255,255,.10);
        display:flex;
        justify-content: space-between;
        align-items:center;
        gap: 10px;
        color: rgba(248,250,252,.70);
        font-size: 12px;
        background: rgba(255,255,255,.02);
        position: relative;
        z-index: 1;
    }
    .bg-foot .id{
        color: #fff;
        font-weight: 900;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(245,158,11,.12);
        border: 1px solid rgba(245,158,11,.25);
        white-space: nowrap;
    }

    @media print{
        .bg-wrap{ background:#fff; border:none; padding:0; }
        .bg-card{ box-shadow:none; }
        .bg-sheen{ display:none; }
    }
@endsection

@section('card')
    <div class="bg-wrap">
        <div class="bg-card">
            <div class="bg-sheen"></div>

            <div class="bg-head">
                <div class="bg-brand">
                    <div class="bg-logo">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="logo">
                        @else
                            <i class="ri-home-gym-line" style="font-size: 26px; color:#fff;"></i>
                        @endif
                    </div>

                    <div class="bg-title">
                        <div class="ar">{{ $gymNameAr ?: 'اسم الجيم' }}</div>
                        <div class="en">{{ $gymNameEn ?: 'Gym Name' }}</div>
                    </div>
                </div>

                <div class="bg-badge">BLACK • GOLD</div>
            </div>

            <div class="bg-body">

                {{-- Photo --}}
                <div class="bg-photo">
                    @if($memberPhoto)
                        <img src="{{ $memberPhoto }}" alt="member">
                    @else
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%2394a3b8' d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E" alt="member">
                    @endif
                </div>

                {{-- Center Info (داكن واضح) --}}
                <div class="bg-center">
                    <div class="bg-name">{{ $member->first_name }} {{ $member->last_name }}</div>

                    <div class="bg-row">
                        <span class="k">رقم العضوية:</span>
                        <span class="v">{{ $member->member_code }}</span>
                    </div>

                    <div class="bg-row">
                        <span class="k">الفرع:</span>
                        <span class="v">{{ $member->branch ? $member->branch->getTranslation('name','ar') : '-' }}</span>
                    </div>

                    <div class="bg-row">
                        <span class="k">تاريخ الانضمام:</span>
                        <span class="v">{{ optional($member->join_date)->format('Y-m-d') ?? '-' }}</span>
                    </div>

                    <div class="bg-contact">
                        <div class="bg-pill">
                            <i class="ri-phone-line"></i>
                            <span>{{ $member->phone ?? '-' }}</span>
                        </div>

                        @if($member->email)
                            <div class="bg-pill">
                                <i class="ri-mail-line"></i>
                                <span>{{ $member->email }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- QR --}}
                <div class="bg-qr">
                    <div class="box">
                        <img src="data:image/png;base64,{{ $barcodePng }}" alt="QR Code">
                    </div>
                    <div class="cap">SCAN</div>
                </div>

            </div>

            <div class="bg-foot">
                <div>Print & WhatsApp Ready</div>
                <div class="id">ID: {{ $member->id }}</div>
            </div>

        </div>
    </div>
@endsection
