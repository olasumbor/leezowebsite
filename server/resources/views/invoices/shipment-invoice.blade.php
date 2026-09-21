<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">

    <style>
        @page {
            size: A4 landscape;
            margin: 25px 45px;
        }

        body {
            margin: 30px 45px;
            padding: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #111b35;
            font-size: 12px;
        }

        .top-line {
            height: 8px;
            background: #00a94f;
            margin-bottom: 50px;
        }

        .header {
            width: 100%;
        }

        .header-left {
            width: 55%;
            vertical-align: top;
        }

        .header-right {
            width: 45%;
            text-align: right;
            vertical-align: top;
        }

        .logo-area {
            height: 55px;
        }

        .logo-placeholder {
            display: inline-block;
            width: 250px;
            height: 48px;
            text-align: center;
            line-height: 48px;
            color: #777;
            font-size: 10px;
        }

        .company-name {
            color: #60728e;
            font-size: 15px;
            margin-top: 5px;
        }

        .company-info {
            color: #60728e;
            font-size: 13px;
            line-height: 20px;
        }

        .title {
            font-size: 27px;
            font-weight: bold;
            color: #111b35;
            margin-top: 0;
        }

        .receipt-number {
            color: #0099dc;
            font-size: 20px;
            font-weight: bold;
            margin-top: 10px;
        }

        .status {
            color: #e59a00;
            font-size: 12px;
            font-weight: bold;
            margin-top: 7px;
        }

        .separator {
            border-top: 1px solid #cfd6df;
            margin-top: 20px;
            margin-bottom: 16px;
        }

        /* ROUTE */

        .route {
            width: 100%;
            background: #101a35;
            color: white;
            height: 70px;
        }

        .route td {
            padding: 12px 35px;
            vertical-align: middle;
        }

        .route-label {
            font-size: 11px;
            font-weight: bold;
            color: #c5ccda;
        }

        .route-value {
            font-size: 13px;
            margin-top: 8px;
        }

        .route-middle {
            width: 30%;
            text-align: center;
            font-size: 25px;
        }

        .destination {
            text-align: right;
        }

        /* DETAILS */

        .details {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            background: #f5f7f9;
        }

        .details td {
            border: 1px solid #d1d9e1;
            height: 80px;
            vertical-align: middle;
            text-align: center;
        }

        .details-left {
            width: 50%;
        }

        .details-right {
            width: 50%;
        }

        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .details-text {
            font-size: 12px;
            line-height: 21px;
        }

        /* ITEMS */

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 27px;
        }

        .items th {
            background: #00aa4f;
            color: white;
            height: 50px;
            text-align: left;
            padding: 0 20px;
            font-size: 12px;
        }

        .items td {
            border: 1px solid #d1d9e1;
            height: 45px;
            padding: 0 20px;
            font-size: 12px;
        }

        .items .center {
            text-align: center;
        }

        .items .right {
            text-align: right;
        }

        /* FOOTER */

        .bottom-section {
            /* position: fixed; */
            left: 0;
            right: 0;
            bottom: 15px;
            width: 100%;
            margin-top: 50px;

        }

        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .terms-column {
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }

        .bank-column {
            width: 29%;
            vertical-align: top;
            padding-right: 15px;
        }

        .payment-column {
            width: 21%;
            vertical-align: top;
        }

        .bottom-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 4px;
            color: #111b35;
        }

        .terms-text {
            font-size: 9px;
            line-height: 13px;
            color: #111b35;
        }

        .bank-text {
            font-size: 10px;
            line-height: 17px;
            color: #111b35;
        }

        .payment-box {
            width: 100%;
            height: 68px;
            background: #eef8ff;
            border: 2px solid #b7dff4;
            border-radius: 7px;
        }

        .payment-table {
            width: 100%;
            height: 68px;
            border-collapse: collapse;
        }

        .payment-label {
            width: 45%;
            padding-left: 12px;
            vertical-align: middle;
            color: #60728e;
            font-size: 10px;
            font-weight: bold;
        }

        .payment-amount {
            width: 55%;
            padding-right: 12px;
            vertical-align: middle;
            text-align: right;
            color: #111b35;
            font-size: 18px;
            font-weight: bold;
        }

        .signed {
            text-align: center;
            margin-top: 12px;
            font-size: 11px;
            font-weight: bold;
            color: #111b35;
        }
    </style>
</head>

<body>

    <!-- TOP GREEN LINE -->
    <div class="top-line"></div>

    <!-- HEADER -->
    <table class="header">
        <tr>
            <td class="header-left">

                <div class="logo-area">
                    <div class="logo-placeholder">
                        @php
                        $logoPath = public_path('logo-leezo.NG.png');
                        $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,' .
                        base64_encode(file_get_contents($logoPath)) :
                        '/logo-leezo.NG.png';
                        @endphp
                        <img src="{{ $logoSrc }}" alt="Leezofood Logo"
                            style="max-height: 52px; width: auto; background: #ffffff; padding: 6px 12px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.15);">
                    </div>
                </div>

                <div class="company-name">
                    LEEZOFODNG. EXPORT &amp; LOGISTICS
                </div>

                <div class="company-info">
                    Shop 8, Kingscourt Estate - Shasha Akowonjo, Lagos, Nigeria<br>
                    leezo integratedserviceslimited@gmail.com
                </div>

            </td>

            <td class="header-right">

                <div class="title">
                    SHIPMENT INVOICE
                </div>

                <div class="receipt-number">
                    {{ $invoice_number ?? 'INV-000017' }}
                </div>

                <div class="status">
                    {{ $status ?? 'PENDING' }}
                </div>

            </td>
        </tr>
    </table>

    <div class="separator"></div>

    <!-- ROUTE -->
    <table class="route">
        <tr>
            <td>
                <div class="route-label">ORIGIN</div>
                <div class="route-value">{{ $shipment->origin ?? 'N/A' }}</div>
            </td>

            <td class="route-middle">
                ✈ &nbsp; →
            </td>

            <td class="destination">
                <div class="route-label">DESTINATION</div>
                <div class="route-value">{{ $shipment->destination ?? 'N/A' }}</div>
            </td>
        </tr>
    </table>

    <!-- RECIPIENT + SCHEDULE -->
    <table class="details">
        <tr>
            <td class="details-left">

                <div class="section-title">
                    RECIPIENT DETAILS
                </div>

                <div class="details-text">
                    Name: {{ $customer_name ?? 'Valued Customer' }}<br>
                    Destination: {{ $shipment->destination ?? 'N/A' }}<br>
                    Shipper Account: {{ $shipper_account ?? 'N/A' }}
                </div>

            </td>

            <td class="details-right">

                <div class="section-title">
                    SHIPMENT SCHEDULE
                </div>

                <div class="details-text">
                    Service Type: {{ $shipment->service ?? ($shipment->shipment_type ? ucfirst($shipment->shipment_type) . ' Freight' : 'Air Freight') }}<br>
                    Shipped Date: {{ $shipment->shipped_date ? \Carbon\Carbon::parse($shipment->shipped_date)->format('d M Y') : '—' }}<br>
                    Delivered Date: {{ $shipment->delivered_date ? \Carbon\Carbon::parse($shipment->delivered_date)->format('d M Y') : '—' }}
                </div>

            </td>
        </tr>
    </table>

    <!-- ITEMS -->
    <table class="items">
        <thead>
            <tr>
                <th style="width: 32%;">ITEMS</th>
                <th style="width: 16%; text-align:center;">QUANTITY</th>
                <th style="width: 16%; text-align:center;">WEIGHT</th>
                <th style="width: 18%; text-align:center;">RATE</th>
                <th style="width: 18%; text-align:right;">COST</th>
            </tr>
        </thead>

        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td class="center">{{ $item['quantity'] !== null ? $item['quantity'] : '—' }}</td>
                <td class="center">{{ $item['weight'] !== null ? $item['weight'] . ' kg' : '—' }}</td>
                <td class="center">{{ $item['rate'] !== null ? '₦' . number_format($item['rate']) : '—' }}</td>
                <td class="right">{{ $item['cost'] !== null ? '₦' . number_format($item['cost']) : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- FOOTER -->
    <!-- BOTTOM SECTION -->
    <div class="bottom-section">

        <table class="bottom-table">
            <tr>

                <!-- TERMS & CONDITIONS -->
                <td class="terms-column">

                    <div class="bottom-title">
                        Terms &amp; Conditions
                    </div>

                    <div class="terms-text">
                        The border service agency of any county maintains the right to
                        open and inspect any package from this shipment.<br>

                        LEEZOFOODNG. EXPORT is not responsible for any item removed,
                        opened, or qualified unfit.<br>

                        We are not responsible for any delay in transit and it is beyond
                        our control.
                    </div>

                </td>

                <!-- BANK DETAILS -->
                <td class="bank-column">

                    <div class="bottom-title">
                        Company Bank Details
                    </div>

                    <div class="bank-text">
                        Bank: {{ $bank_name }}<br>
                        Account Name: {{ $bank_account_name }}<br>
                        Account Number: {{ $bank_account_number }}
                    </div>

                </td>

                <!-- TOTAL PAYMENT -->
                <td class="payment-column">

                    <div class="payment-box">

                        <table class="payment-table">
                            <tr>
                                <td class="payment-label">
                                    TOTAL PAYMENT
                                </td>
                                <td class="payment-amount">
                                    ₦{{ number_format($total_amount ?? 0) }}
                                </td>
                            </tr>
                        </table>

                    </div>

                    <div class="signed">
                        Signed by Management
                    </div>

                </td>

            </tr>
        </table>

    </div>
</body>

</html>