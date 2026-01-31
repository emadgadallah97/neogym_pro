@extends('members.cards._layout')

@section('styles')
    :root{
        --ink:#0b1220;
        --muted:#64748b;
        --paper:#ffffff;
        --line:rgba(2,6,23,.10);

        --a1:#7c3aed;  /* purple */
        --a2:#06b6d4;  /* cyan */
        --a3:#22c55e;  /* green */
        --soft:#f8fafc;
    }

    .card-aurora-wrap{
        width: 560px;
        max-width: 100%;
        margin: 0 auto;
        padding: 14px;
        border-radius: 18px;
        background:
            radial-gradient(1200px 600px at 20% 10%, rgba(124,58,237,.14), transparent 60%),
            radial-gradient(900px 500px at 80% 0%, rgba(6,182,212,.12), transparent 55%),
            radial-gradient(900px 500px at 70% 100%, rgba(34,197,94,.10), transparent 55%),
            #f1f5f9;
        border: 1px solid rgba(2,6,23,.06);
    }

    .card-aurora{
        overflow: hidden;
        border-radius: 18px;
        background: var(--paper);
        border: 1px solid var(--line);
        box-shadow: 0 20px 50px rgba(2,6,23,.12);
        position: relative;
    }

    .aurora-ribbon{
        position: absolute;
        top: -40px;
        left: -60px;
        width: 220px;
        height: 120px;
        transform: rotate(-18deg);
        background: linear-gradient(90deg, rgba(124,58,237,.95), rgba(6,182,212,.90));
        filter: blur(.0px);
        opacity: .95;
    }

    .aurora-head{
        padding: 16px 16px 12px 16px;
        background:
            linear-gradient(135deg, rgba(2,6,23,.92), rgba(2,6,23,.86)),
            radial-gradient(800px 320px at 70% 0%, rgba(6,182,212,.25), transparent 65%),
            radial-gradient(700px 300px at 15% 20%, rgba(124,58,237,.22), transparent 60%);
        color: #fff;
        position: relative;
        z-index: 1;
    }

    .aurora-head-top{
        display:flex;
        align-items:center;
        justify-content: space-between;
        gap: 12px;
    }

    .aurora-brand{
        display:flex;
        align-items:center;
        gap: 10px;
        min-width: 0;
    }

    .aurora-logo{
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: rgba(255,255,255,.10);
        border: 1px solid rgba(255,255,255,.18);
        display:flex;
        align-items:center;
        justify-content:center;
        overflow:hidden;
        flex: 0 0 auto;
        box-shadow: 0 12px 26px rgba(0,0,0,.18);
    }
    .aurora-logo img{
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .aurora-gym{
        min-width: 0;
    }
    .aurora-gym .ar{
        font-weight: 900;
        font-size: 22px;
        line-height: 1.1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .aurora-gym .en{
        font-size: 11px;
        opacity: .85;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        direction: ltr;
        text-align: left;
    }

    .aurora-chip{
        display:flex;
        align-items:center;
        gap: 8px;
        padding: 8px 10px;
        border-radius: 999px;
        background: rgba(255,255,255,.10);
        border: 1px solid rgba(255,255,255,.18);
        font-size: 11px;
        letter-spacing: .6px;
        white-space: nowrap;
    }
    .aurora-chip .dot{
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: linear-gradient(135deg, var(--a1), var(--a2));
        box-shadow: 0 0 0 3px rgba(255,255,255,.10);
    }

    .aurora-body{
        padding: 14px 16px 12px 16px;
        display:flex;
        gap: 14px;
        align-items: stretch;
    }

    .aurora-left{
        flex: 1;
        min-width: 0;
        display:flex;
        gap: 12px;
        align-items:center;
    }

    .aurora-photo{
        width: 92px;
        height: 92px;
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid rgba(2,6,23,.10);
        background: #fff;
        box-shadow: 0 14px 28px rgba(2,6,23,.10);
        flex: 0 0 auto;
        position: relative;
    }
    .aurora-photo::after{
        content:'';
        position:absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(124,58,237,.10), rgba(6,182,212,.10));
        pointer-events:none;
        opacity: .85;
    }
    .aurora-photo img{
        width: 100%;
        height: 100%;
        object-fit: cover;
        position: relative;
        z-index: 1;
    }

    .aurora-info{
        flex: 1;
        min-width: 0;
    }

    .aurora-name{
        font-weight: 900;
        font-size: 18px;
        color: var(--ink);
        line-height: 1.25;
        margin-bottom: 8px;
    }

    .aurora-grid{
        display:grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .aurora-item{
        border: 1px solid rgba(2,6,23,.08);
        background: linear-gradient(180deg, #fff, #fbfdff);
        border-radius: 14px;
        padding: 8px 10px;
        box-shadow: 0 10px 18px rgba(2,6,23,.06);
        min-width: 0;
    }

    .aurora-item .k{
        font-size: 11px;
        color: var(--muted);
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .aurora-item .v{
        font-size: 12.5px;
        color: var(--ink);
        font-weight: 800;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        direction: ltr;
        text-align: left;
    }

    .aurora-contact{
        margin-top: 10px;
        display:flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .aurora-pill{
        display:flex;
        align-items:center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: var(--soft);
        border: 1px solid rgba(2,6,23,.08);
        font-size: 12px;
        color: #0f172a;
    }
    .aurora-pill i{
        color: var(--a2);
    }

    .aurora-qr{
        width: 150px;
        flex: 0 0 auto;
        display:flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px;
        border-radius: 16px;
        background:
            radial-gradient(220px 120px at 50% 0%, rgba(124,58,237,.10), transparent 60%),
            radial-gradient(220px 120px at 50% 100%, rgba(6,182,212,.08), transparent 60%),
            #ffffff;
        border: 1px solid rgba(2,6,23,.08);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.6), 0 12px 20px rgba(2,6,23,.06);
    }

    .aurora-qr .box{
        width: 124px;
        height: 124px;
        border-radius: 14px;
        background: #fff;
        border: 1px solid rgba(2,6,23,.10);
        overflow: hidden;
        display:flex;
        align-items:center;
        justify-content:center;
    }
    .aurora-qr .box img{
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .aurora-qr .cap{
        font-size: 11px;
        color: var(--muted);
        font-weight: 800;
        letter-spacing: .6px;
    }

    .aurora-foot{
        padding: 10px 16px;
        border-top: 1px dashed rgba(2,6,23,.18);
        display:flex;
        align-items:center;
        justify-content: space-between;
        gap: 10px;
        background:
            linear-gradient(180deg, rgba(248,250,252,.5), rgba(248,250,252,.9));
    }

    .aurora-foot .left{
        font-size: 12px;
        color: var(--muted);
        font-weight: 800;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .aurora-foot .right{
        font-size: 12px;
        font-weight: 900;
        color: var(--ink);
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(124,58,237,.10);
        border: 1px solid rgba(124,58,237,.18);
        white-space: nowrap;
    }

    @media print {
        .card-aurora-wrap{
            background: #ffffff;
            border: none;
            padding: 0;
        }
        .card-aurora{
            box-shadow: none;
        }
    }
@endsection

@section('card')
    <div class="card-aurora-wrap">
        <div class="card-aurora">

            <div class="aurora-ribbon"></div>

            {{-- Header --}}
            <div class="aurora-head">
                <div class="aurora-head-top">
                    <div class="aurora-brand">
                        <div class="aurora-logo">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="logo">
                            @else
                                <i class="ri-home-gym-line" style="font-size: 28px;"></i>
                            @endif
                        </div>

                        <div class="aurora-gym">
                            <div class="ar">{{ $gymNameAr ?: 'اسم الجيم' }}</div>
                            <div class="en">{{ $gymNameEn ?: 'Gym Name' }}</div>
                        </div>
                    </div>

                    <div class="aurora-chip">
                        <span class="dot"></span>
                        <span>MEMBER PASS</span>
                    </div>
                </div>
            </div>

            {{-- Body --}}
            <div class="aurora-body">

                <div class="aurora-left">
                    <div class="aurora-photo">
                        @if($memberPhoto)
                            <img src="{{ $memberPhoto }}" alt="member">
                        @else
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%2394a3b8' d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E" alt="member">
                        @endif
                    </div>

                    <div class="aurora-info">
                        <div class="aurora-name">
                            {{ $member->first_name }} {{ $member->last_name }}
                        </div>

                        <div class="aurora-grid">
                            <div class="aurora-item">
                                <div class="k">رقم العضوية</div>
                                <div class="v">{{ $member->member_code }}</div>
                            </div>

                            <div class="aurora-item">
                                <div class="k">الفرع</div>
                                <div class="v">{{ $member->branch ? $member->branch->getTranslation('name','ar') : '-' }}</div>
                            </div>

                            <div class="aurora-item">
                                <div class="k">تاريخ الانضمام</div>
                                <div class="v">{{ optional($member->join_date)->format('Y-m-d') ?? '-' }}</div>
                            </div>

                            <div class="aurora-item">
                                <div class="k">ID</div>
                                <div class="v">{{ $member->id }}</div>
                            </div>
                        </div>

                        <div class="aurora-contact">
                            <div class="aurora-pill">
                                <i class="ri-phone-line"></i>
                                <span>{{ $member->phone ?? '-' }}</span>
                            </div>

                            @if($member->email)
                                <div class="aurora-pill">
                                    <i class="ri-mail-line"></i>
                                    <span>{{ $member->email }}</span>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>

                <div class="aurora-qr">
                    <div class="box">
                        <img src="data:image/png;base64,{{ $barcodePng }}" alt="QR Code">
                    </div>
                    <div class="cap">Scan to verify</div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="aurora-foot">
                <div class="left">Print & WhatsApp Ready</div>
                <div class="right">MEMBER • {{ $member->member_code }}</div>
            </div>

        </div>
    </div>
@endsection
