@extends('members.cards._layout')

@section('styles')
    .member-card-wrap{
        width: 560px;
        max-width: 100%;
        margin: 0 auto;
        padding: 10px;
        background: #f6f8ff;
        border-radius: 16px;
    }

    .member-card{
        width: 100%;
        border-radius: 18px;
        overflow: hidden;
        background: #ffffff;
        border: 1px solid rgba(15,23,42,.10);
        box-shadow: 0 18px 40px rgba(2,6,23,.10);
    }

    .member-card-top{
        background: linear-gradient(135deg, #0b1f4b 0%, #1373c3 55%, #0ea5a5 100%);
        padding: 14px;
        color: #fff;
        display:flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }

    .gym-logo{
        width: 100px;
        height: 100px;
        border-radius: 12px;
        background: rgba(255,255,255,.12);
        display:flex;
        align-items:center;
        justify-content:center;
        overflow:hidden;
        border: 1px solid rgba(255,255,255,.22);
        flex: 0 0 auto;
    }
    .gym-logo img{
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .gym-title .gym-name-ar{
        font-weight: 700;
        font-size: 40px;
        line-height: 1.1;
    }
    .gym-title .gym-name-en{
        font-size: 12px;
        opacity: .9;
    }

    .card-badge{
        font-size: 11px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(255,255,255,.18);
        border: 1px solid rgba(255,255,255,.25);
        letter-spacing: .9px;
        white-space: nowrap;
    }

    .member-card-body{
        padding: 16px;
        display:flex;
        gap: 14px;
        align-items: center;
    }

    .member-photo{
        width: 96px;
        height: 96px;
        border-radius: 50%;
        overflow: hidden;
        border: 4px solid #1373c3;
        box-shadow: 0 10px 24px rgba(19,115,195,.22);
        flex: 0 0 auto;
        background: #fff;
    }
    .member-photo img{
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .member-info{
        flex:1;
        min-width: 0;
    }

    .member-name{
        font-size: 18px;
        font-weight: 900;
        color: #0b1f4b;
        margin-bottom: 6px;
        line-height: 1.25;
    }

    .member-line{
        display:flex;
        justify-content: space-between;
        gap: 10px;
        font-size: 12.5px;
        padding: 4px 0;
        border-bottom: 1px dashed rgba(148,163,184,.45);
    }
    .member-line .k{
        color:#64748b;
        white-space: nowrap;
    }
    .member-line .v{
        color:#0f172a;
        font-weight: 700;
        text-align: left;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .member-contact{
        margin-top: 8px;
        display:flex;
        flex-direction: column;
        gap: 4px;
        font-size: 12px;
        color:#334155;
    }
    .member-contact i{
        color:#0ea5a5;
        margin-left: 6px;
    }

    .member-qr{
        width: 130px;
        flex: 0 0 auto;
        text-align: center;
    }
    .qr-box{
        width: 130px;
        height: 130px;
        border-radius: 14px;
        border: 1px solid rgba(148,163,184,.45);
        background: #fff;
        display:flex;
        align-items:center;
        justify-content:center;
        overflow:hidden;
        box-shadow: 0 10px 22px rgba(2,6,23,.08);
    }
    .qr-box img{
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .qr-text{
        margin-top: 6px;
        font-size: 11px;
        color: #64748b;
        font-weight: 700;
        letter-spacing: .8px;
    }

    .member-card-footer{
        background: #f1f5ff;
        padding: 10px 14px;
        display:flex;
        justify-content: space-between;
        align-items:center;
        font-size: 12px;
        color:#0b1f4b;
        border-top: 1px solid rgba(15,23,42,.08);
    }
    .member-card-footer .foot-left{
        font-weight: 700;
        opacity: .9;
    }
    .member-card-footer .foot-right{
        font-weight: 900;
        color:#1373c3;
    }
@endsection

@section('card')
    <div class="member-card-wrap" id="memberCardCanvas">
        <div class="member-card">

            {{-- Top Bar --}}
            <div class="member-card-top">
                <div class="d-flex align-items-center gap-2">
                    <div class="gym-logo">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="logo">
                        @else
                            <i class="ri-home-gym-line" style="font-size: 48px;"></i>
                        @endif
                    </div>
                    <div class="gym-title">
                        <div class="gym-name-ar">{{ $gymNameAr ?: 'اسم الجيم' }}</div>
                        <div class="gym-name-en">{{ $gymNameEn ?: 'Gym Name' }}</div>
                    </div>
                </div>

                <div class="card-badge">
                    MEMBERSHIP
                </div>
            </div>

            {{-- Body --}}
            <div class="member-card-body">
                <div class="member-photo">
                    @if($memberPhoto)
                        <img src="{{ $memberPhoto }}" alt="member">
                    @else
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%2394a3b8' d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E" alt="member">
                    @endif
                </div>

                <div class="member-info">
                    <div class="member-name">
                        {{ $member->first_name }} {{ $member->last_name }}
                    </div>

                    <div class="member-line">
                        <span class="k">رقم العضوية:</span>
                        <span class="v">{{ $member->member_code }}</span>
                    </div>

                    <div class="member-line">
                        <span class="k">الفرع:</span>
                        <span class="v">{{ $member->branch ? $member->branch->getTranslation('name','ar') : '-' }}</span>
                    </div>

                    <div class="member-line">
                        <span class="k">تاريخ الانضمام:</span>
                        <span class="v">{{ optional($member->join_date)->format('Y-m-d') ?? '-' }}</span>
                    </div>

                    <div class="member-contact">
                        <div><i class="ri-phone-line"></i> {{ $member->phone ?? '-' }}</div>
                        @if($member->email)
                            <div><i class="ri-mail-line"></i> {{ $member->email }}</div>
                        @endif
                    </div>
                </div>

                {{-- QR --}}
                <div class="member-qr">
                    <div class="qr-box">
                        <img src="data:image/png;base64,{{ $barcodePng }}" alt="QR Code">
                    </div>
                    <div class="qr-text">Scan</div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="member-card-footer">
                <div class="foot-left">
                    Print & WhatsApp Ready
                </div>
                <div class="foot-right">
                    ID: {{ $member->id }}
                </div>
            </div>

        </div>
    </div>
@endsection
