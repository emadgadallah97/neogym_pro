@extends('members.cards._layout')

@section('styles')
    :root{
        --ink:#0b1220;
        --muted:#64748b;
        --paper:#ffffff;
        --line:rgba(2,6,23,.10);

        --indigo:#4f46e5;
        --coral:#fb7185;
        --mint:#22c55e;

        --bg:#f7f8ff;
    }

    .ms-wrap{
        width: 560px;
        max-width: 100%;
        margin: 0 auto;
        padding: 12px;
        border-radius: 18px;
        background:
            radial-gradient(900px 420px at 10% 0%, rgba(79,70,229,.12), transparent 60%),
            radial-gradient(900px 420px at 90% 10%, rgba(251,113,133,.12), transparent 60%),
            linear-gradient(180deg, #ffffff, var(--bg));
        border: 1px solid rgba(2,6,23,.06);
    }

    .ms-card{
        border-radius: 18px;
        overflow: hidden;
        background: var(--paper);
        border: 1px solid rgba(2,6,23,.10);
        box-shadow: 0 22px 55px rgba(2,6,23,.12);
        position: relative;
    }

    .ms-top{
        padding: 14px 16px;
        display:flex;
        justify-content: space-between;
        align-items:center;
        gap: 12px;
        background:
            linear-gradient(135deg, rgba(79,70,229,.10), rgba(251,113,133,.08)),
            #ffffff;
        border-bottom: 1px solid rgba(2,6,23,.08);
    }

    .ms-brand{
        display:flex;
        align-items:center;
        gap: 10px;
        min-width: 0;
    }

    .ms-logo{
        width: 52px;
        height: 52px;
        border-radius: 16px;
        overflow:hidden;
        background: rgba(255,255,255,.85);
        border: 1px solid rgba(2,6,23,.10);
        display:flex;
        align-items:center;
        justify-content:center;
        flex: 0 0 auto;
        box-shadow: 0 12px 24px rgba(2,6,23,.10);
    }
    .ms-logo img{ width:100%; height:100%; object-fit:cover; }

    .ms-title{ min-width:0; }
    .ms-title .ar{
        font-weight: 900;
        font-size: 18px;
        color: var(--ink);
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ms-title .en{
        font-size: 11px;
        color: var(--muted);
        direction: ltr;
        text-align: left;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ms-chip{
        display:flex;
        align-items:center;
        gap: 8px;
        padding: 8px 10px;
        border-radius: 999px;
        background: rgba(79,70,229,.10);
        border: 1px solid rgba(79,70,229,.18);
        font-size: 11px;
        font-weight: 900;
        color: #312e81;
        letter-spacing: .8px;
        white-space: nowrap;
    }
    .ms-chip .dot{
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: linear-gradient(135deg, var(--indigo), var(--coral));
        box-shadow: 0 0 0 3px rgba(79,70,229,.10);
    }

    .ms-body{
        padding: 14px 16px 12px 16px;
        display:grid;
        grid-template-columns: 175px 1fr;
        gap: 14px;
        align-items: stretch;
    }

    /* Right panel (QR + core info) */
    .ms-right{
        border-radius: 16px;
        border: 1px solid rgba(2,6,23,.08);
        background:
            radial-gradient(240px 140px at 50% 0%, rgba(79,70,229,.10), transparent 60%),
            #ffffff;
        padding: 12px;
        display:flex;
        flex-direction: column;
        gap: 10px;
        align-items: center;
        justify-content: center;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.7);
    }

    .ms-qr{
        width: 132px;
        height: 132px;
        border-radius: 16px;
        background: #ffffff;
        border: 1px solid rgba(2,6,23,.10);
        overflow:hidden;
        display:flex;
        align-items:center;
        justify-content:center;
        box-shadow: 0 12px 20px rgba(2,6,23,.08);
    }
    .ms-qr img{ width:100%; height:100%; object-fit:contain; }

    .ms-code{
        width: 100%;
        text-align: center;
        font-weight: 900;
        color: var(--ink);
        font-size: 12px;
        padding: 8px 10px;
        border-radius: 14px;
        background: rgba(2,6,23,.03);
        border: 1px solid rgba(2,6,23,.08);
        direction: ltr;
    }

    .ms-mini{
        width: 100%;
        display:grid;
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .ms-mini-item{
        display:flex;
        justify-content: space-between;
        gap: 10px;
        padding: 8px 10px;
        border-radius: 14px;
        background: rgba(255,255,255,.8);
        border: 1px solid rgba(2,6,23,.08);
        font-size: 12px;
    }
    .ms-mini-item .k{ color: var(--muted); white-space: nowrap; }
    .ms-mini-item .v{
        color: var(--ink);
        font-weight: 900;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        direction: ltr;
        text-align: left;
    }

    /* Left panel (photo + details) */
    .ms-left{
        border-radius: 16px;
        border: 1px solid rgba(2,6,23,.08);
        background: #ffffff;
        padding: 12px;
        display:flex;
        flex-direction: column;
        gap: 10px;
        min-width: 0;
    }

    .ms-profile{
        display:flex;
        align-items:center;
        gap: 12px;
        min-width: 0;
    }

    .ms-photo{
        width: 92px;
        height: 92px;
        border-radius: 20px;
        overflow:hidden;
        border: 1px solid rgba(2,6,23,.10);
        background: #fff;
        flex: 0 0 auto;
        box-shadow: 0 14px 28px rgba(2,6,23,.10);
        position: relative;
    }
    .ms-photo::after{
        content:'';
        position:absolute;
        inset:0;
        background: linear-gradient(135deg, rgba(251,113,133,.10), rgba(79,70,229,.10));
        pointer-events:none;
        opacity: .9;
    }
    .ms-photo img{ width:100%; height:100%; object-fit:cover; position: relative; z-index: 1; }

    .ms-namebox{ min-width:0; flex:1; }
    .ms-name{
        font-weight: 900;
        color: var(--ink);
        font-size: 18px;
        line-height: 1.25;
        margin-bottom: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ms-sub{
        display:flex;
        align-items:center;
        gap: 8px;
        flex-wrap: wrap;
        color: var(--muted);
        font-size: 12px;
    }
    .ms-sub .badge2{
        padding: 5px 10px;
        border-radius: 999px;
        background: rgba(34,197,94,.10);
        border: 1px solid rgba(34,197,94,.18);
        color: #166534;
        font-weight: 900;
        white-space: nowrap;
    }

    .ms-lines{
        display:grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .ms-line{
        border: 1px solid rgba(2,6,23,.08);
        background: linear-gradient(180deg, #ffffff, #fbfdff);
        border-radius: 14px;
        padding: 8px 10px;
        min-width: 0;
    }
    .ms-line .k{
        color: var(--muted);
        font-size: 11px;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ms-line .v{
        color: var(--ink);
        font-weight: 900;
        font-size: 12.5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        direction: ltr;
        text-align: left;
    }

    .ms-contact{
        display:flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 2px;
    }

    .ms-pill{
        display:flex;
        align-items:center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(2,6,23,.03);
        border: 1px solid rgba(2,6,23,.08);
        color: var(--ink);
        font-size: 12px;
        max-width: 100%;
    }
    .ms-pill i{ color: var(--indigo); }
    .ms-pill span{
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        direction: ltr;
        text-align: left;
    }

    .ms-foot{
        padding: 10px 16px;
        border-top: 1px dashed rgba(2,6,23,.18);
        display:flex;
        justify-content: space-between;
        align-items:center;
        gap: 10px;
        background: #fbfdff;
        color: var(--muted);
        font-size: 12px;
    }
    .ms-foot .id{
        font-weight: 900;
        color: var(--ink);
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(251,113,133,.10);
        border: 1px solid rgba(251,113,133,.18);
        white-space: nowrap;
    }

    @media print{
        .ms-wrap{ background:#fff; border:none; padding:0; }
        .ms-card{ box-shadow:none; }
    }
@endsection

@section('card')
    <div class="ms-wrap">
        <div class="ms-card">

            {{-- Header --}}
            <div class="ms-top">
                <div class="ms-brand">
                    <div class="ms-logo">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="logo">
                        @else
                            <i class="ri-home-gym-line" style="font-size: 26px; color:#0b1220;"></i>
                        @endif
                    </div>

                    <div class="ms-title">
                        <div class="ar">{{ $gymNameAr ?: 'اسم الجيم' }}</div>
                        <div class="en">{{ $gymNameEn ?: 'Gym Name' }}</div>
                    </div>
                </div>

                <div class="ms-chip">
                    <span class="dot"></span>
                    <span>NEOGYM PRO</span>
                </div>
            </div>

            {{-- Body --}}
            <div class="ms-body">

                {{-- Right: QR + core --}}
                <div class="ms-right">
                    <div class="ms-qr">
                        <img src="data:image/png;base64,{{ $barcodePng }}" alt="QR Code">
                    </div>

                    <div class="ms-code">{{ $member->member_code }}</div>

                    <div class="ms-mini">
                        <div class="ms-mini-item">
                            <span class="k">الفرع</span>
                            <span class="v">{{ $member->branch ? $member->branch->getTranslation('name','ar') : '-' }}</span>
                        </div>

                        <div class="ms-mini-item">
                            <span class="k">تاريخ الانضمام</span>
                            <span class="v">{{ optional($member->join_date)->format('Y-m-d') ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Left: profile + details --}}
                <div class="ms-left">

                    <div class="ms-profile">
                        <div class="ms-photo">
                            @if($memberPhoto)
                                <img src="{{ $memberPhoto }}" alt="member">
                            @else
                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%2394a3b8' d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E" alt="member">
                            @endif
                        </div>

                        <div class="ms-namebox">
                            <div class="ms-name">{{ $member->first_name }} {{ $member->last_name }}</div>
                            <div class="ms-sub">
                            </div>
                        </div>
                    </div>

                    <div class="ms-lines">
                        <div class="ms-line">
                            <div class="k">رقم العضوية</div>
                            <div class="v">{{ $member->member_code }}</div>
                        </div>

                        <div class="ms-line">
                            <div class="k">ID</div>
                            <div class="v">{{ $member->id }}</div>
                        </div>
                    </div>

                    <div class="ms-contact">
                        <div class="ms-pill">
                            <i class="ri-phone-line"></i>
                            <span>{{ $member->phone ?? '-' }}</span>
                        </div>

                        @if($member->email)
                            <div class="ms-pill">
                                <i class="ri-mail-line"></i>
                                <span>{{ $member->email }}</span>
                            </div>
                        @endif
                    </div>

                </div>

            </div>

            {{-- Footer --}}
            <div class="ms-foot">
                <div>Scan the QR to verify member</div>
                <div class="id">ID: {{ $member->id }}</div>
            </div>

        </div>
    </div>
@endsection
