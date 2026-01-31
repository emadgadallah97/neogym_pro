@extends('members.cards._layout')

@section('styles')
    .card-wrap{
        width: 420px;
        max-width: 100%;
        margin: 0 auto;
        background: #ffffff;
        border: 1px solid rgba(15,23,42,.10);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 18px 40px rgba(2,6,23,.08);
    }

    .top{
        padding: 14px;
        background: #0b1f4b;
        color:#fff;
        display:flex;
        align-items:center;
        justify-content: space-between;
        gap: 10px;
    }

    .top .brand{
        display:flex;
        align-items:center;
        gap: 10px;
        min-width: 0;
    }

    .logo{
        width: 52px;
        height: 52px;
        border-radius: 12px;
        background: rgba(255,255,255,.12);
        overflow:hidden;
        border: 1px solid rgba(255,255,255,.20);
        flex: 0 0 auto;
        display:flex;
        align-items:center;
        justify-content:center;
    }
    .logo img{ width:100%; height:100%; object-fit:cover; }

    .brand-name{
        min-width: 0;
    }
    .brand-name .ar{
        font-weight: 900;
        font-size: 18px;
        line-height: 1.1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .brand-name .en{
        font-size: 11px;
        opacity: .85;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .badge-x{
        font-size: 11px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(255,255,255,.14);
        border: 1px solid rgba(255,255,255,.18);
        white-space: nowrap;
    }

    .body{
        padding: 14px;
        display:flex;
        gap: 12px;
        align-items:center;
    }

    .photo{
        width: 76px;
        height: 76px;
        border-radius: 14px;
        overflow:hidden;
        border: 3px solid #1373c3;
        flex: 0 0 auto;
        background:#fff;
    }
    .photo img{ width:100%; height:100%; object-fit:cover; }

    .info{
        flex:1;
        min-width: 0;
    }

    .name{
        font-weight: 900;
        font-size: 16px;
        color:#0b1f4b;
        margin-bottom: 6px;
        line-height: 1.2;
    }

    .kv{
        display:flex;
        justify-content: space-between;
        gap: 8px;
        font-size: 12px;
        padding: 4px 0;
        border-bottom: 1px dashed rgba(148,163,184,.45);
    }
    .kv .k{ color:#64748b; white-space: nowrap; }
    .kv .v{ color:#0f172a; font-weight: 800; text-align:left; white-space: nowrap; overflow:hidden; text-overflow: ellipsis; }

    .qr{
        width: 96px;
        flex: 0 0 auto;
        text-align:center;
    }
    .qr .box{
        width: 96px;
        height: 96px;
        border-radius: 12px;
        border: 1px solid rgba(148,163,184,.45);
        overflow:hidden;
        background:#fff;
    }
    .qr .box img{ width:100%; height:100%; object-fit:contain; }

    .footer{
        padding: 10px 14px;
        background: #f8fafc;
        border-top: 1px solid rgba(15,23,42,.08);
        font-size: 12px;
        color:#0b1f4b;
        display:flex;
        justify-content: space-between;
        align-items:center;
    }
@endsection

@section('card')
    <div class="card-wrap">
        <div class="top">
            <div class="brand">
                <div class="logo">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="logo">
                    @else
                        <i class="ri-home-gym-line" style="font-size: 24px;"></i>
                    @endif
                </div>

                <div class="brand-name">
                    <div class="ar">{{ $gymNameAr ?: 'اسم الجيم' }}</div>
                    <div class="en">{{ $gymNameEn ?: 'Gym Name' }}</div>
                </div>
            </div>

            <div class="badge-x">CARD</div>
        </div>

        <div class="body">
            <div class="photo">
                @if($memberPhoto)
                    <img src="{{ $memberPhoto }}" alt="member">
                @else
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%2394a3b8' d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E" alt="member">
                @endif
            </div>

            <div class="info">
                <div class="name">{{ $member->first_name }} {{ $member->last_name }}</div>

                <div class="kv">
                    <span class="k">الكود</span>
                    <span class="v">{{ $member->member_code }}</span>
                </div>

                <div class="kv">
                    <span class="k">الفرع</span>
                    <span class="v">{{ $member->branch ? $member->branch->getTranslation('name','ar') : '-' }}</span>
                </div>

                <div class="kv">
                    <span class="k">الهاتف</span>
                    <span class="v">{{ $member->phone ?? '-' }}</span>
                </div>
            </div>

            <div class="qr">
                <div class="box">
                    <img src="data:image/png;base64,{{ $barcodePng }}" alt="QR Code">
                </div>
            </div>
        </div>

        <div class="footer">
            <div>ID: {{ $member->id }}</div>
            <div>{{ optional($member->join_date)->format('Y-m-d') ?? '-' }}</div>
        </div>
    </div>
@endsection
