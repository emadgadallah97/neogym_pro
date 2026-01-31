<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Card - {{ $member->member_code }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap');

        body {
            font-family: 'Cairo', 'Arial', sans-serif;
            background: #f8f9fa;
            padding: 20px;
        }

        .print-actions {
            text-align: center;
            margin-bottom: 20px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .print-actions {
                display: none !important;
            }
        }

        @yield('styles')
    </style>
</head>

<body>

    <div class="print-actions">
        <button class="btn btn-primary btn-lg rounded-pill px-4" onclick="window.print()">
            <i class="ri-printer-line me-2"></i>
            طباعة الكارت
        </button>
        <a href="{{ route('members.index') }}" class="btn btn-secondary btn-lg rounded-pill px-4">
            <i class="ri-arrow-right-line me-2"></i>
            رجوع
        </a>
    </div>

    @yield('card')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
