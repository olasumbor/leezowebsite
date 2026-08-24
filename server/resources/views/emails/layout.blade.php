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
            background-color: #0b1a53;
            padding: 30px;
            text-align: center;
        }
        .email-header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .email-header h1 span {
            color: #10b981;
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
            <h1><span>Leezo</span>food NG.Export</h1>
        </div>
        <div class="email-body">
            @yield('content')
        </div>
        <div class="email-footer">
            <p><strong>Leezofood Exports & Logistics</strong></p>
            <p>99, Shasha Road, Lagos, Nigeria | +234 809 499 7264</p>
            <p><a href="mailto:info@leezofood.ng">info@leezofood.ng</a> | <a href="http://localhost:8000">www.leezofood.ng</a></p>
            <p style="margin-top: 15px; font-size: 11px; color: #64748b;">© {{ date('Y') }} Leezofood Exports. All Rights Reserved.</p>
        </div>
    </div>
</body>
</html>
