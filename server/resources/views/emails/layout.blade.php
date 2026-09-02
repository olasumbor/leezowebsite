<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Leezofood Exports & Logistics' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333333;
            -webkit-font-smoothing: antialiased;
        }
        .email-container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }
        .email-header {
            background-color: #ffffff;
            padding: 25px 30px;
            text-align: center;
            border-bottom: 2px solid #10b981;
        }
        .email-header img {
            max-height: 55px;
            width: auto;
            display: block;
            margin: 0 auto;
        }
        .email-body {
            padding: 35px 30px;
        }
        .email-body h2 {
            color: #0b1a53;
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .email-body p {
            font-size: 15px;
            line-height: 1.6;
            color: #4b5563;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #f8fafc;
            border-left: 4px solid #10b981;
            padding: 16px 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .info-box p {
            margin: 5px 0;
            font-size: 14px;
        }
        .info-box strong {
            color: #0b1a53;
        }
        .action-btn {
            display: inline-block;
            background-color: #10b981;
            color: #ffffff !important;
            padding: 12px 28px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
            margin-top: 15px;
            text-align: center;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            background-color: #e0f2fe;
            color: #0369a1;
        }
        .status-badge.completed { background-color: #dcfce7; color: #15803d; }
        .status-badge.pending { background-color: #fef3c7; color: #b45309; }
        .email-footer {
            background-color: #0f172a;
            color: #94a3b8;
            padding: 25px 30px;
            text-align: center;
            font-size: 13px;
        }
        .email-footer a {
            color: #10b981;
            text-decoration: none;
        }
        .email-footer p {
            margin: 5px 0;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <img src="{{ (isset($message) && method_exists($message, 'embed') && file_exists(public_path('logo-leezo.NG.png'))) ? $message->embed(public_path('logo-leezo.NG.png')) : config('app.frontend_url') . '/images/logo-leezo.NG.png' }}" alt="Leezofood Exports" style="max-height: 55px; width: auto; display: block; margin: 0 auto;">
        </div>
        <div class="email-body">
            @yield('content')
        </div>
        <div class="email-footer">
            <p><strong>Leezofood Exports & Logistics</strong></p>
            <p>Shop 8, Kingscourt Estate, Shasha Akowonjo, Lagos, Nigeria</p>
            <p>+234 813 671 0716, +234 703 989 0112</p>
            <p><a href="mailto:leezointegratedserviceslimited@gmail.com">leezointegratedserviceslimited@gmail.com</a></p>
            <p style="margin-top: 15px; font-size: 11px; color: #64748b;">© {{ date('Y') }} Leezofood Exports. All Rights Reserved.</p>
        </div>
    </div>
</body>
</html>
