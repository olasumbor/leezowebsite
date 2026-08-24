<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procurement Receipt - {{ $procurement->procurement_id }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f172a;
            --accent: #2563eb;
            --accent-light: #eff6ff;
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
            background-color: #1d4ed8;
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
            background: linear-gradient(90deg, #2563eb, #3b82f6, #60a5fa);
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

        .status-completed {
            background-color: var(--success-bg);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-pending, .status-in-progress {
            background-color: var(--warning-bg);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .status-cancelled {
            background-color: var(--danger-bg);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
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
            border: 1px solid rgba(37, 99, 235, 0.15);
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
            border-top: 2px solid rgba(37, 99, 235, 0.2);
            margin-top: 0.5rem;
            padding-top: 0.75rem;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--primary);
        }

        .currency-symbol {
            font-weight: 700;
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
            <strong style="font-size: 0.95rem; color: #0f172a;">Leezofood Procurement Receipt</strong>
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
                <div class="brand-icon">L</div>
                <div>
                    <div class="brand-title">LEEZOFOOD</div>
                    <div class="brand-subtitle">Ng.Export & Logistics</div>
                </div>
            </div>
            <div class="doc-title-block">
                <div class="doc-title">PROCUREMENT RECEIPT</div>
                <div class="doc-id">{{ $procurement->procurement_id }}</div>
                @php
                    $statusClass = match(strtolower($procurement->status ?? 'pending')) {
                        'completed' => 'status-completed',
                        'in progress', 'in_progress', 'approved' => 'status-in-progress',
                        'cancelled' => 'status-cancelled',
                        default => 'status-pending'
                    };
                @endphp
                <span class="status-badge {{ $statusClass }}">
                    {{ ucfirst($procurement->status ?? 'Pending') }}
                </span>
            </div>
        </div>

        <!-- DETAILS GRID -->
        <div class="details-grid">
            <div class="info-card">
                <h4>Customer Details</h4>
                <p><strong>Name:</strong> {{ $procurement->name ?? ($procurement->user->name ?? 'N/A') }}</p>
                <p><strong>Email:</strong> {{ $procurement->email ?? ($procurement->user->email ?? 'N/A') }}</p>
                <p><strong>Phone:</strong> {{ $procurement->phone ?? ($procurement->user->phone ?? 'N/A') }}</p>
            </div>
            <div class="info-card">
                <h4>Procurement Schedule</h4>
                <p><strong>Request Date:</strong> {{ $procurement->created_at ? $procurement->created_at->format('M d, Y') : 'N/A' }}</p>
                <p><strong>Expected Delivery:</strong> {{ $procurement->expected_date ? \Carbon\Carbon::parse($procurement->expected_date)->format('M d, Y') : '—' }}</p>
                <p><strong>Delivered Date:</strong> {{ $procurement->delivered_date ? \Carbon\Carbon::parse($procurement->delivered_date)->format('M d, Y') : '—' }}</p>
            </div>
        </div>

        <!-- ITEM TABLE -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Quantity</th>
                        <th class="text-right">Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>{{ $procurement->details ?? 'Procurement Request' }}</strong></td>
                        <td>{{ $procurement->category ?? 'General Procurement' }}</td>
                        <td>{{ $procurement->supplier ?? '—' }}</td>
                        <td>{{ $procurement->quantity ?? '1' }}</td>
                        <td class="text-right">
                            @if(isset($procurement->cost) && is_numeric($procurement->cost))
                                <span class="currency-symbol">₦</span>{{ number_format($procurement->cost, 2) }}
                            @elseif(!empty($procurement->cost) && $procurement->cost !== '—')
                                {{ $procurement->cost }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- TOTAL SECTION -->
        <div class="total-section">
            <div class="total-box">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>
                        @if(isset($procurement->cost) && is_numeric($procurement->cost))
                            <span class="currency-symbol">₦</span>{{ number_format($procurement->cost, 2) }}
                        @else
                            {{ $procurement->cost ?? '—' }}
                        @endif
                    </span>
                </div>
                <div class="total-row">
                    <span>Processing Fee</span>
                    <span><span class="currency-symbol">₦</span>0.00</span>
                </div>
                <div class="total-row final">
                    <span>Total Amount</span>
                    <span>
                        @if(isset($procurement->cost) && is_numeric($procurement->cost))
                            <span class="currency-symbol">₦</span>{{ number_format($procurement->cost, 2) }}
                        @else
                            {{ $procurement->cost ?? '—' }}
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer-section">
            <div class="footer-notes">
                <p><strong>Thank you for choosing Leezofood Ng.Export!</strong></p>
                <p>This is an official computer-generated receipt for procurement operations. For any inquiries, please contact support@lezofood.com.</p>
            </div>
            <div class="stamp-badge">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                OFFICIAL RECORD
            </div>
        </div>
    </div>

</body>
</html>
