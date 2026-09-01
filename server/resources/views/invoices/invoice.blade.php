<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice_number ?? 'INV-000017' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #15803d;
            --emerald-green: #059669;
            --light-green-bg: #ecfdf5;
            --text-dark: #111827;
            --text-muted: #6b7280;
            --text-light: #9ca3af;
            --border-gray: #e5e7eb;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f3f4f6;
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
            background-color: var(--primary-green);
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #166534;
        }

        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }

        .btn-secondary:hover {
            background-color: #d1d5db;
        }

        .invoice-card {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 0;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        /* GREEN TOP BANNER */
        .green-banner {
            background: var(--primary-green);
            color: #ffffff;
            padding: 1.5rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand-section {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-badge {
            background: #ffffff;
            color: var(--primary-green);
            font-weight: 800;
            font-size: 1.1rem;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            text-align: center;
            line-height: 1.1;
        }

        .brand-name {
            font-size: 1.2rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.01em;
        }

        .brand-slogan {
            font-size: 0.7rem;
            color: #dcfce7;
            font-style: italic;
        }

        .banner-center {
            text-align: center;
        }

        .banner-title {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            color: #ffffff;
        }

        .banner-inv-num {
            font-size: 0.85rem;
            color: #dcfce7;
            margin-top: 0.1rem;
        }

        .banner-company-details {
            text-align: right;
            font-size: 0.75rem;
            color: #f0fdf4;
            line-height: 1.4;
        }

        .invoice-body {
            padding: 2.5rem;
        }

        /* TOP BALANCE DUE BAR */
        .top-summary-bar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
        }

        .customer-name {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text-dark);
        }

        .balance-due-top {
            text-align: right;
        }

        .balance-due-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--emerald-green);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .balance-due-amount {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--emerald-green);
        }

        /* METADATA GRID */
        .meta-grid {
            display: flex;
            justify-content: flex-end;
            gap: 2rem;
            margin-bottom: 2.5rem;
            font-size: 0.85rem;
        }

        .meta-table {
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 0.2rem 0.5rem;
        }

        .meta-label {
            color: var(--text-muted);
            text-align: right;
        }

        .meta-value {
            font-weight: 600;
            color: var(--text-dark);
            text-align: right;
        }

        /* ITEMS TABLE */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        .items-table th {
            color: var(--emerald-green);
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.75rem 0.5rem;
            border-bottom: 1.5px solid var(--border-gray);
            text-align: left;
        }

        .items-table th.col-num {
            width: 40px;
        }

        .items-table th.col-amount {
            text-align: right;
        }

        .items-table td {
            padding: 1.25rem 0.5rem;
            border-bottom: 1px solid var(--border-gray);
            vertical-align: top;
            font-size: 0.9rem;
        }

        .item-title {
            font-weight: 600;
            color: var(--text-dark);
        }

        .item-subtext {
            font-size: 0.75rem;
            color: var(--text-light);
            margin-top: 0.25rem;
            text-align: right;
        }

        .amount-cell {
            text-align: right;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* PAYMENTS AND TOTALS */
        .bottom-financials {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 3rem;
            gap: 2rem;
        }

        .payment-box {
            font-size: 0.82rem;
            color: var(--text-muted);
            line-height: 1.6;
            max-width: 380px;
        }

        .payment-box strong {
            color: var(--text-dark);
        }

        .bank-details-block {
            margin-top: 1rem;
            font-size: 0.85rem;
            color: var(--text-dark);
            line-height: 1.5;
        }

        .totals-block {
            width: 320px;
            font-size: 0.88rem;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 0.4rem 0;
            color: var(--text-dark);
        }

        .totals-row.subtotal {
            color: var(--text-muted);
        }

        .totals-row.total-bold {
            font-weight: 800;
            font-size: 0.95rem;
        }

        .balance-due-highlight {
            background-color: var(--light-green-bg);
            padding: 0.75rem 1rem;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
            color: var(--emerald-green);
            font-weight: 800;
            font-size: 1.05rem;
        }

        /* TERMS AND CONDITIONS */
        .terms-section {
            border-top: 1px solid var(--border-gray);
            padding-top: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .terms-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .terms-text {
            font-size: 0.75rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* SIGNATURE SECTION */
        .signature-section {
            display: flex;
            justify-content: flex-start;
            align-items: flex-end;
            padding-top: 1rem;
        }

        .signature-block {
            text-align: left;
        }

        .signature-image {
            font-family: 'Dancing Script', cursive;
            font-size: 2rem;
            color: #1e293b;
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .signature-line {
            width: 180px;
            height: 1px;
            background-color: var(--border-gray);
            margin-bottom: 0.5rem;
        }

        .signer-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .signer-title {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .invoice-card {
                box-shadow: none;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <strong style="font-size: 0.95rem; color: #111827;">Official Invoice - {{ $invoice_number ?? 'INV-000017' }}</strong>
            <p style="font-size: 0.8rem; color: #6b7280;">Ready to print or save as PDF document</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print / Save PDF
            </button>
            <button onclick="window.close()" class="btn btn-secondary">Close</button>
        </div>
    </div>

    <div class="invoice-card">
        <!-- GREEN TOP BANNER -->
        <div class="green-banner">
            <div class="brand-section">
                @php
                    $logoPath = public_path('logo-leezo.NG.png');
                    $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '/logo-leezo.NG.png';
                @endphp
                <img src="{{ $logoSrc }}" alt="Leezofood Logo" style="max-height: 52px; width: auto; background: #ffffff; padding: 6px 12px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.15);">
            </div>

            <div class="banner-center">
                <div class="banner-title">INVOICE</div>
                <div class="banner-inv-num">{{ $invoice_number ?? 'INV-000017' }}</div>
            </div>

            <div class="banner-company-details">
                <div>Shop 8, Kingscourt Estate</div>
                <div>Shasha Akowonjo, Lagos, Nigeria</div>
                <div>leezointegratedserviceslimited@gmail.com</div>
            </div>
        </div>


        <div class="invoice-body">
            <!-- TOP CUSTOMER & BALANCE DUE BAR -->
            <div class="top-summary-bar">
                <div class="customer-name">
                    {{ $customer_name ?? 'Mickas' }}
                </div>
                <div class="balance-due-top">
                    <div class="balance-due-label">BALANCE DUE</div>
                    <div class="balance-due-amount">NGN{{ number_format($total_amount ?? 3521000, 2) }}</div>
                </div>
            </div>

            <!-- METADATA GRID -->
            <div class="meta-grid">
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">Invoice#</td>
                        <td class="meta-value">{{ $invoice_number ?? 'INV-000017' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Invoice Date</td>
                        <td class="meta-value">{{ $invoice_date ?? '24 Jun 2026' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Terms</td>
                        <td class="meta-value">Due on Receipt</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Due Date</td>
                        <td class="meta-value">{{ $due_date ?? '24 Jun 2026' }}</td>
                    </tr>
                </table>
            </div>

            <!-- ITEMS TABLE -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="col-num">#</th>
                        <th>ITEM & DESCRIPTION</th>
                        <th class="col-amount">AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                        @foreach($items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div class="item-title">{{ $item['name'] }}</div>
                            </td>
                            <td class="amount-cell">
                                <div>NGN{{ number_format($item['amount'], 2) }}</div>
                                @if(isset($item['subtext']))
                                <div class="item-subtext">{{ $item['subtext'] }}</div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                </tbody>
            </table>

            <!-- BOTTOM FINANCIALS AND PAYMENTS -->
            @php
                $bankAccountNumber = $bank_account_number ?? \App\Models\Setting::get('bank_account_number', '0900779403');
                $bankAccountName = $bank_account_name ?? \App\Models\Setting::get('bank_account_name', 'Leezoe integrated');
                $bankName = $bank_name ?? \App\Models\Setting::get('bank_name', 'Guaranty Trust Bank.');
            @endphp
            <div class="bottom-financials">
                <div>
                    <div class="bank-details-block">
                        {{ $bankAccountNumber }}<br>
                        {{ $bankAccountName }}<br>
                        {{ $bankName }}
                    </div>
                    <div class="payment-box" style="margin-top: 0.75rem;">
                        Thanks for your business. Please make your payment to<br>
                        <strong>{{ $bankAccountNumber }}</strong> (<strong>{{ $bankAccountName }}</strong> - {{ $bankName }})
                    </div>
                </div>

                <div class="totals-block">
                    <div class="totals-row subtotal">
                        <span>Sub Total</span>
                        <span>{{ number_format($total_amount ?? 3521000, 2) }}</span>
                    </div>
                    <div class="totals-row total-bold">
                        <span>Total</span>
                        <span>NGN{{ number_format($total_amount ?? 3521000, 2) }}</span>
                    </div>
                    <div class="balance-due-highlight">
                        <span>Balance Due</span>
                        <span>NGN{{ number_format($total_amount ?? 3521000, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- TERMS & CONDITIONS -->
            <div class="terms-section">
                <div class="terms-title">Terms & Conditions</div>
                <div class="terms-text">
                    The border service agency of any county maintains the right to open and inspect any package from this shipment. LEEZOFOODNG. EXPORT is not responsible for any item removed, opened,or qualified unfit. We are not responsible for any delay in transit and it is beyond our control.
                </div>
            </div>

            <!-- SIGNATURE -->
            <!-- <div class="signature-section">
                <div class="signature-block">
                    <div class="signature-image">Vanessa Attah</div>
                    <div class="signature-line"></div>
                    <div class="signer-name">Vanessa Attah.</div>
                    <div class="signer-title">Authorized Signature</div>
                </div>
            </div> -->
        </div>
    </div>

</body>
</html>
