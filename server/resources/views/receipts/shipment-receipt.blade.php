<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Receipt - {{ $shipment->tracking_id }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f172a;
            --accent: #0284c7;
            --accent-light: #f0f9ff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
            --success: #10b981;
            --success-bg: #ecfdf5;
            --warning: #f59e0b;
            --warning-bg: #fffbeb;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #f1f5f9;
            color: var(--text-dark);
            line-height: 1.5;
            padding: 2rem 1rem;
        }

        .no-print-bar {
            max-width: 800px;
            margin: 0 auto 1.5rem auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.25rem;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--accent);
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #0369a1;
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }

        .btn-secondary:hover {
            background-color: #cbd5e1;
        }

        .receipt-card {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            padding: 3rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            position: relative;
            overflow: hidden;
        }

        .receipt-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #0284c7, #38bdf8, #0ea5e9);
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 2rem;
            border-bottom: 2px dashed var(--border-color);
            margin-bottom: 2rem;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            background: var(--primary);
            color: #ffffff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.25rem;
        }

        .brand-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.02em;
        }

        .brand-subtitle {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .doc-title-block {
            text-align: right;
        }

        .doc-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .doc-id {
            font-family: monospace;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--accent);
            margin-top: 0.25rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 0.5rem;
        }

        .status-delivered {
            background-color: var(--success-bg);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-in_transit, .status-pending {
            background-color: var(--warning-bg);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .route-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .route-location {
            flex: 1;
        }

        .route-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 0.25rem;
        }

        .route-name {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .route-arrow {
            padding: 0 1.5rem;
            font-size: 1.5rem;
            color: #38bdf8;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background: var(--bg-light);
            padding: 1.25rem;
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        .info-card h4 {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
        }

        .info-card p {
            font-size: 0.9rem;
            color: var(--text-dark);
            margin-bottom: 0.35rem;
        }

        .info-card p strong {
            font-weight: 600;
        }

        .table-container {
            margin-bottom: 2rem;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: var(--primary);
            color: #ffffff;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.85rem 1rem;
            text-align: left;
        }

        td {
            padding: 1rem;
            font-size: 0.9rem;
            border-bottom: 1px solid var(--border-color);
            background-color: #ffffff;
        }

        tr:nth-child(even) td {
            background-color: var(--bg-light);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .total-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 2.5rem;
        }

        .total-box {
            width: 320px;
            background: var(--accent-light);
            padding: 1.25rem;
            border-radius: 12px;
            border: 1px solid rgba(2, 132, 199, 0.15);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.35rem 0;
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        .total-row.final {
            border-top: 2px solid rgba(2, 132, 199, 0.2);
            margin-top: 0.5rem;
            padding-top: 0.75rem;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--primary);
        }

        .currency-symbol {
            font-weight: 700;
        }

        .events-section {
            margin-bottom: 2rem;
            background: var(--bg-light);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 1.25rem;
        }

        .events-section h4 {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
        }

        .event-item {
            display: flex;
            gap: 1rem;
            padding-bottom: 0.75rem;
            margin-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.85rem;
        }

        .event-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .event-time {
            font-weight: 600;
            color: var(--accent);
            min-width: 110px;
        }

        .footer-section {
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-notes {
            font-size: 0.8rem;
            color: var(--text-muted);
            max-width: 450px;
        }

        .stamp-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: 2px dashed #94a3b8;
            border-radius: 8px;
            color: #475569;
            font-weight: 800;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            opacity: 0.85;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .receipt-card {
                box-shadow: none;
                padding: 1rem;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <strong style="font-size: 0.95rem; color: #0f172a;">Leezofood Shipment Receipt & Waybill</strong>
            <p style="font-size: 0.8rem; color: #64748b;">Ready to print or save as PDF document</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print / Save PDF
            </button>
            <button onclick="window.close()" class="btn btn-secondary">Close</button>
        </div>
    </div>

    <div class="receipt-card">
        <!-- HEADER -->
        <div class="header-section">
            <div class="brand-logo">
                @php
                    $logoPath = public_path('logo-leezo.NG.png');
                    $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '/logo-leezo.NG.png';
                @endphp
                <img src="{{ $logoSrc }}" alt="Leezofood Logo" style="max-height: 48px; width: auto;">
                <div>
                    <div class="brand-title">LEEZOFOOD</div>
                    <div class="brand-subtitle">Ng.Export & Logistics</div>
                </div>
            </div>

            <div class="doc-title-block">
                <div class="doc-title">SHIPMENT WAYBILL</div>
                <div class="doc-id">{{ $shipment->tracking_id }}</div>
                @php
                    $statusClass = match(strtolower($shipment->status ?? 'pending')) {
                        'delivered' => 'status-delivered',
                        default => 'status-in_transit'
                    };
                @endphp
                <span class="status-badge {{ $statusClass }}">
                    {{ ucfirst(str_replace('_', ' ', $shipment->status ?? 'Pending')) }}
                </span>
            </div>
        </div>

        <!-- ROUTE BANNER -->
        <div class="route-banner">
            <div class="route-location">
                <div class="route-label">Origin</div>
                <div class="route-name">{{ $shipment->origin ?? 'N/A' }}</div>
            </div>
            <div class="route-arrow">✈ ➔</div>
            <div class="route-location" style="text-align: right;">
                <div class="route-label">Destination</div>
                <div class="route-name">{{ $shipment->destination ?? 'N/A' }}</div>
            </div>
        </div>

        <!-- DETAILS GRID -->
        <div class="details-grid">
            <div class="info-card">
                <h4>Recipient Details</h4>
                <p><strong>Name:</strong> {{ $shipment->recipient ?? ($shipment->user->name ?? 'N/A') }}</p>
                <p><strong>Destination:</strong> {{ $shipment->destination ?? 'N/A' }}</p>
                <p><strong>Shipper Account:</strong> {{ $shipment->user->email ?? 'N/A' }}</p>
            </div>
            <div class="info-card">
                <h4>Shipment Schedule</h4>
                <p><strong>Service Type:</strong> {{ $shipment->service ?? 'Air Freight' }}</p>
                <p><strong>Shipped Date:</strong> {{ $shipment->created_at ? $shipment->created_at->format('M d, Y') : 'N/A' }}</p>
                <p><strong>Delivered Date:</strong> {{ $shipment->delivered_date ? \Carbon\Carbon::parse($shipment->delivered_date)->format('M d, Y') : '—' }}</p>
            </div>
        </div>

        <!-- PACKAGE TABLE -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tracking Reference</th>
                        <th>Service</th>
                        <th>Weight</th>
                        <th>Packages</th>
                        <th class="text-right">Shipping Fee</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>{{ $shipment->tracking_id }}</strong></td>
                        <td>{{ $shipment->service ?? 'Standard Cargo' }}</td>
                        <td>{{ $shipment->weight ? $shipment->weight . ' kg' : '—' }}</td>
                        <td>{{ $shipment->packages ?? '1' }}</td>
                        <td class="text-right">
                            @if(isset($shipment->shipping_cost) && is_numeric($shipment->shipping_cost))
                                <span class="currency-symbol">₦</span>{{ number_format($shipment->shipping_cost, 2) }}
                            @elseif(!empty($shipment->shipping_cost) && $shipment->shipping_cost !== '—')
                                {{ $shipment->shipping_cost }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- RECENT TRACKING EVENTS SUMMARY -->
        @if(isset($shipment->events) && count($shipment->events) > 0)
        <div class="events-section">
            <h4>Tracking Event History</h4>
            @foreach($shipment->events->take(3) as $event)
            <div class="event-item">
                <div class="event-time">{{ $event->created_at ? $event->created_at->format('M d, H:i') : '' }}</div>
                <div>
                    <strong>{{ $event->location ?? 'Hub' }}</strong> — {{ $event->description }}
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- TOTAL SECTION -->
        <div class="total-section">
            <div class="total-box">
                <div class="total-row">
                    <span>Freight Charges</span>
                    <span>
                        @if(isset($shipment->shipping_cost) && is_numeric($shipment->shipping_cost))
                            <span class="currency-symbol">₦</span>{{ number_format($shipment->shipping_cost, 2) }}
                        @else
                            {{ $shipment->shipping_cost ?? '—' }}
                        @endif
                    </span>
                </div>
                <div class="total-row">
                    <span>Documentation & Tax</span>
                    <span><span class="currency-symbol">₦</span>0.00</span>
                </div>
                <div class="total-row final">
                    <span>Total Amount Paid</span>
                    <span>
                        @if(isset($shipment->shipping_cost) && is_numeric($shipment->shipping_cost))
                            <span class="currency-symbol">₦</span>{{ number_format($shipment->shipping_cost, 2) }}
                        @else
                            {{ $shipment->shipping_cost ?? '—' }}
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer-section">
            <div class="footer-notes">
                <p><strong>Official Leezofood Waybill & Logistics Receipt</strong></p>
                <p>Goods received for transport subject to company terms and conditions. Track status online at lezofood.com.</p>
            </div>
            <div class="stamp-badge">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                VERIFIED SHIPMENT
            </div>
        </div>
    </div>

</body>
</html>
