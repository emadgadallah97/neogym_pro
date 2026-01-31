@extends('members.cards._layout')

@section('styles')
    :root{
        --bg:#050816;
        --ink:#e5e7eb;
        --muted:#94a3b8;
        --c1:#22d3ee; /* cyan */
        --c2:#a78bfa; /* violet */
        --c3:#34d399; /* green */
        --line:rgba(255,255,255,.10);
    }

    .nt-wrap{
        width: 560px;
        max-width: 100%;
        margin: 0 auto;
        padding: 12px;
        border-radius: 18px;
        background:
            radial-gradient(900px 420px at 15% 0%, rgba(34,211,238,.14), transparent 60%),
            radial-gradient(900px 420px at 90% 20%, rgba(167,139,250,.12), transparent 60%),
            radial-gradient(700px 360px at 70% 100%, rgba(52,211,153,.10), transparent 55%),
            #03061a;
        border: 1px solid rgba(255,255,255,.06);
    }

    .nt-card{
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.10);
        background: linear-gradient(135deg, #050816, #0b1026);
        box-shadow: 0 22px 60px rgba(0,0,0,.55);
        position: relative;
    }

    .nt-grid{
        position:absolute;
        inset:0;
        background-image:
            linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
        background-size: 22px 22px;
        opacity: .22;
        pointer-events:none;
    }

    .nt-head{
        padding: 16px;
        display:flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(255,255,255,.10);
        position: relative;
        z-index: 1;
    }

    .nt-brand{
        display:flex;
        align-items:center;
        gap: 10px;
        min-width: 0;
    }

    .nt-logo{
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
        box-shadow: 0 0 0 2px rgba(34,211,238,.10);
    }
    .nt-logo img{ width:100%; height:100%; object-fit:cover; }

    .nt-title{ min-width:0; }
    .nt-title .ar{
        font-weight: 900;
        color: #fff;
        font-size: 18px;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .nt-title .en{
        color: var(--muted);
        font-size: 11px;
        direction: ltr;
        text-align: left;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .nt-badge{
        padding: 7px 10px;
        border-radius: 999px;
        background: rgba(34,211,238,.10);
        border: 1px solid rgba(34,211,238,.25);
        color: #a5f3fc;
        font-weight: 900;
        font-size: 11px;
        letter-spacing: 1px;
        white-space: nowrap;
        text-transform: uppercase;
    }

    .nt-body{
        padding: 14px 16px;
        display:grid;
        grid-template-columns: 1fr 150px;
        gap: 14px;
        align-items: center;
        position: relative;
        z-index: 1;
    }

    .nt-left{
        display:flex;
        gap: 12px;
        align-items: center;
        min-width: 0;
    }

    .nt-photo{
        width: 92px;
        height: 92px;
        border-radius: 18px;
        overflow:hidden;
        border: 1px solid rgba(34,211,238,.25);
        box-shadow:
            0 0 0 2px rgba(167,139,250,.10),
            0 18px 34px rgba(0,0,0,.40);
        background: rgba(255,255,255,.04);
        flex: 0 0 auto;
    }
    .nt-photo img{ width:100%; height:100%; object-fit:cover; }

    .nt-info{ flex: 1; min-width: 0; }

    .nt-name{
        font-weight: 900;
        font-size: 18px;
        color: #fff;
        margin-bottom: 10px;
        line-height: 1.25;
    }

    .nt-line{
        display:flex;
        justify-content: space-between;
        gap: 10px;
        padding: 6px 0;
        border-bottom: 1px dashed rgba(255,255,255,.14);
        font-size: 12.5px;
    }
    .nt-line .k{ color: var(--muted); white-space: nowrap; }
    .nt-line .v{
        color: var(--ink);
        font-weight: 800;
        direction: ltr;
        text-align: left;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .nt-contact{
        margin-top: 10px;
        display:flex;
        flex-wrap: wrap;
        gap: 8px;
        font-size: 12px;
    }
    .nt-pill{
        display:flex;
        align-items:center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(255,255,255,.06);
        border: 1px solid rgba(255,255,255,.12);
        color: var(--ink);
    }
    .nt-pill i{ color: var(--c3); }

    .nt-qr{
        border-radius: 16px;
        padding: 10px;
        background: rgba(255,255,255,.04);
        border: 1px solid rgba(255,255,255,.12);
        text-align:center;
    }
    .nt-qr .box{
        width: 124px;
        height: 124px;
        border-radius: 14px;
        overflow:hidden;
        background: #ffffff;
        border: 1px solid rgba(167,139,250,.25);
        display:flex;
        align-items:center;
        justify-content:center;
        margin: 0 auto;
    }
    .nt-qr .box img{ width:100%; height:100%; object-fit:contain; }
    .nt-qr .cap{
        margin-top: 8px;
        font-size: 11px;
        color: var(--muted);
        font-weight: 900;
        letter-spacing: .8px;
    }

    .nt-foot{
        padding: 10px 16px;
        border-top: 1px solid rgba(255,255,255,.10);
        display:flex;
        justify-content: space-between;
        align-items:center;
        gap: 10px;
        background: rgba(255,255,255,.02);
        color: var(--muted);
        font-size: 12px;
        position: relative;
        z-index: 1;
    }
    .nt-foot .id{
        font-weight: 900;
        color: #fff;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(167,139,250,.12);
        border: 1px solid rgba(167,139,250,.25);
        white-space: nowrap;
    }

    @media print{
        .nt-wrap{ background:#fff; border:none; padding:0; }
        .nt-card{ box-shadow:none; }
        .nt-grid{ display:none; }
    }
@endsection

@section('card')
    <div class="nt-wrap">
        <div class="nt-card">
            <div class="nt-grid"></div>

            <div class="nt-head">
                <div class="nt-brand">
                    <div class="nt-logo">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="logo">
                        @else
                            <i class="ri-home-gym-line" style="font-size: 26px; color:#fff;"></i>
                        @endif
                    </div>

                    <div class="nt-title">
                        <div class="ar">{{ $gymNameAr ?: 'اسم الجيم' }}</div>
                        <div class="en">{{ $gymNameEn ?: 'Gym Name' }}</div>
                    </div>
                </div>

                <div class="nt-badge">NEON TECH</div>
            </div>

            <div class="nt-body">

                <div class="nt-left">
                    <div class="nt-photo">
                        @if($memberPhoto)
                            <img src="{{ $memberPhoto }}" alt="member">
                        @else
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%2394a3b8' d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E" alt="member">
                        @endif
                    </div>

                    <div class="nt-info">
                        <div class="nt-name">{{ $member->first_name }} {{ $member->last_name }}</div>

                        <div class="nt-line">
                            <span class="k">رقم العضوية:</span>
                            <span class="v">{{ $member->member_code }}</span>
                        </div>

                        <div class="nt-line">
                            <span class="k">الفرع:</span>
                            <span class="v">{{ $member->branch ? $member->branch->getTranslation('name','ar') : '-' }}</span>
                        </div>

                        <div class="nt-line">
                            <span class="k">تاريخ الانضمام:</span>
                            <span class="v">{{ optional($member->join_date)->format('Y-m-d') ?? '-' }}</span>
                        </div>

                        <div class="nt-contact">
                            <div class="nt-pill">
                                <i class="ri-phone-line"></i>
                                <span>{{ $member->phone ?? '-' }}</span>
                            </div>

                            @if($member->email)
                                <div class="nt-pill">
                                    <i class="ri-mail-line"></i>
                                    <span>{{ $member->email }}</span>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>

                <div class="nt-qr">
                    <div class="box">
                        <img src="data:image/png;base64,{{ $barcodePng }}" alt="QR Code">
                    </div>
                    <div class="cap">SCAN</div>
                </div>

            </div>

            <div class="nt-foot">
                <div>Print & WhatsApp Ready</div>
                <div class="id">ID: {{ $member->id }}</div>
            </div>

        </div>
    </div>
@endsection
