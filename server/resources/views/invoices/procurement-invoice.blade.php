<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: A4 landscape; margin: 12mm 19.5mm; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #18243a; }
        .receipt { width: 100%; }

        /* ---------- Header ---------- */
        .header { width: 100%; border-collapse: collapse; margin-bottom: 7px; }
        .header td { vertical-align: middle; padding: 0; }
        .logo-cell { width: 32%; }
        .logo { width: 180px; height: auto; }
        .title-cell { width: 50%; text-align: center; }
        .title { font-size: 22px; font-weight: bold; letter-spacing: 1px; }
        .reference-cell { width: 18%; text-align: right; vertical-align: top !important; }
        .reference { color: #2c6fd8; font-size: 12px; font-weight: bold; }
        .reference-status { color: #f28c00; font-size: 12px; font-weight: bold; }

        /* ---------- Company box + date ---------- */
        .company-info { border: 1px solid #bdcce0; background: #f4f7fb; padding: 5px 10px 3px 10px; font-size: 9px; line-height: 11px; }
        .date { text-align: right; font-weight: bold; font-size: 10px; line-height: 11px; margin: 6px 0 3px 0; }

        /* ---------- Customer / schedule ---------- */
        .details { width: 100%; border-collapse: collapse; margin-bottom: 7px; }
        .details td { width: 50%; border: 1px solid #bdcce0; vertical-align: middle; text-align: center; padding: 7px; }
        .details-left { width: 50%; }
        .details-right { width: 50%; }
        .details-title { font-size: 10px; line-height: 11px; margin-bottom: 10px; }
        .details-content { font-size: 10px; line-height: 11px; }

        /* ---------- Items table ---------- */
        .items { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .items th { background: #00a64f; color: #ffffff; border: 1px solid #ffffff; padding: 7px 6px; text-align: left; vertical-align: middle; font-size: 8.5px; font-weight: bold; }
        .items td { border: 1px solid #bdcce0; padding: 8px 6px; height: 22px; vertical-align: middle; font-size: 9px; line-height: 11px; }
        .items .description { font-weight: bold; }
        .items .category, .items .supplier { line-height: 11px; }
        .col-description { width: 15.3%; }
        .col-category { width: 11.3%; }
        .col-supplier { width: 12.6%; }
        .col-quantity { width: 9.6%; }
        .col-weight { width: 10.5%; }
        .col-rate { width: 10%; }
        .col-cost { width: 10.9%; }
        .col-shipment { width: 10.6%; }
        .col-transport { width: 8.5%; }

        /* ---------- Footer ---------- */
        .footer { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .footer td { vertical-align: top; border-top: 1px solid #bdcce0; padding-top: 8px; }
        .terms { width: 43.3%; padding-right: 8px !important; }
        .bank { width: 27.8%; padding-right: 10px !important; }
        .summary { width: 28.9%; }
        .footer-title { font-size: 9px; font-weight: normal; line-height: 10.4px; margin: 0; }
        .footer-text { font-size: 9px; line-height: 10.4px; }
        .summary-table { width: 100%; border-collapse: collapse; background: #edf4fd; border: 1px solid #5b9be6; }
        .summary-table td { padding: 6px 8px; font-size: 9px; border-top: none; }
        .summary-label { font-weight: bold; width: 58%; }
        .summary-value { text-align: right; font-weight: bold; }
        .total-row td { padding: 9px 8px; border-top: 1px solid #5b9be6; font-size: 10px; font-weight: bold; }

        /* ---------- Signature ---------- */
        .signature { width: 100%; text-align: right; margin-top: 26px; padding-right: 8px; font-weight: bold; font-size: 10px; }
    </style>
</head>
<body>
    <div class="receipt">
        <table class="header">
            <tr>
                <td class="logo-cell">
@php
$logoPath = public_path('logo-leezo.NG.png');
$logoSrc = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : 'logo-leezo.NG.png';
@endphp
                    <img src="{{ $logoSrc }}" class="logo" alt="Logo">
                </td>
                <td class="title-cell">
                    <div class="title">PROCUREMENT RECEIPT</div>
                </td>
                <td class="reference-cell">
                    <div class="reference">{{ $procurement->procurement_id ?? '' }}</div>
                    <div class="reference-status">{{ strtoupper($procurement->status ?? 'PROCESSING') }}</div>
                </td>
            </tr>
        </table>
        <div class="company-info">
            LEEZFOOD NG. EXPORT &amp; LOGISTICS<br>
            Shop 8, Kingscourt Estate, Shasha Akowonjo, Lagos, Nigeria<br>
            Email: leezo.integratedserviceslimited@gmail.com
        </div>
        <div class="date">
            DATE: {{ $procurement->receipt_date ? \Carbon\Carbon::parse($procurement->receipt_date)->format('F d, Y') : ($procurement->created_at ? \Carbon\Carbon::parse($procurement->created_at)->format('F d, Y') : date('F d, Y')) }}
        </div>
        <table class="details">
            <tr>
                <td class="details-left">
                    <div class="details-title">CUSTOMER DETAILS</div>
                    <div class="details-content">
                        Name: {{ $procurement->name ?? '' }}<br>
                        Email: {{ $procurement->email ?? '' }}<br>
                        Phone: {{ $procurement->phone ?? '' }}
                    </div>
                </td>
                <td class="details-right">
                    <div class="details-title">PROCUREMENT SCHEDULE</div>
                    <div class="details-content">
                        Request Date: {{ $procurement->request_date ? \Carbon\Carbon::parse($procurement->request_date)->format('M d, Y') : '—' }}<br>
                        Expected Delivery: {{ $procurement->expected_delivery ? \Carbon\Carbon::parse($procurement->expected_delivery)->format('M d, Y') : '—' }}<br>
                        Delivered Date: {{ $procurement->delivery_date ? \Carbon\Carbon::parse($procurement->delivery_date)->format('M d, Y') : '—' }}<br>
                        Receipt Date: {{ $procurement->receipt_date ? \Carbon\Carbon::parse($procurement->receipt_date)->format('M d, Y') : '—' }}
                    </div>
                </td>
            </tr>
        </table>
        <table class="items">
            <thead>
                <tr>
                    <th class="col-description">ITEM DESCRIPTION</th>
                    <th class="col-category">CATEGORY</th>
                    <th class="col-supplier">SUPPLIER</th>
                    <th class="col-quantity">QUANTITY</th>
                    <th class="col-weight">WEIGHT (KG)</th>
                    <th class="col-rate">RATE</th>
                    <th class="col-cost">COST</th>
                    <th class="col-shipment">SHIPMENT FEE</th>
                    <th class="col-transport">TRANSPORTATION</th>
                </tr>
            </thead>
            <tbody>
@foreach($procurement->items ?? [] as $item)
@php
$cat = trim($item->category ?? '');
$catParts = $cat !== '' ? preg_split('/\s+/', $cat, 2) : [];
$sup = trim($item->supplier ?? '');
$supParts = $sup !== '' ? preg_split('/\s+/', $sup, 2) : [];
$qtyRaw = $item->quantity ?? null;
$qtyStr = '';
if ($qtyRaw !== null && $qtyRaw !== '') { $qtyStr = (string)(int)$qtyRaw; }
$weightRaw = $item->weight ?? null;
$weightStr = '';
if ($weightRaw !== null && $weightRaw !== '') { $weightStr = rtrim(rtrim(number_format((float)$weightRaw, 2, '.', ''), '0'), '.') . ' kg'; }
$rateRaw = $item->rate ?? null;
$rateStr = '—';
if ($rateRaw !== null && $rateRaw !== '' && (float)$rateRaw != 0) { $rateStr = '₦' . number_format((float)$rateRaw); }
$costRaw = $item->cost ?? null;
if (($costRaw === null || $costRaw === '') && $qtyRaw !== null && $qtyRaw !== '' && $rateRaw !== null && $rateRaw !== '') { $costRaw = (float)$qtyRaw * (float)$rateRaw; }
$costStr = '—';
if ($costRaw !== null && $costRaw !== '' && (float)$costRaw != 0) { $costStr = '₦' . number_format((float)$costRaw); }
$shipRaw = $item->shipment_fee ?? null;
$shipStr = '—';
if ($shipRaw !== null && $shipRaw !== '' && (float)$shipRaw != 0) { $shipStr = '₦' . number_format((float)$shipRaw); }
$transRaw = $item->transportation ?? null;
$transStr = '—';
if ($transRaw !== null && $transRaw !== '' && (float)$transRaw != 0) { $transStr = '₦' . number_format((float)$transRaw); }
@endphp
                <tr>
                    <td class="description">{{ $item->description ?? '' }}</td>
                    <td class="category">
@if(count($catParts) === 2)
{!! e($catParts[0]) !!}<br>
{!! e($catParts[1]) !!}
@elseif(count($catParts) === 1)
{{ $catParts[0] }}
@endif
                    </td>
                    <td class="supplier">
@if(count($supParts) === 2)
{!! e($supParts[0]) !!}<br>
{!! e($supParts[1]) !!}
@elseif(count($supParts) === 1)
{{ $supParts[0] }}
@endif
                    </td>
                    <td>{{ $qtyStr }}</td>
                    <td>{{ $weightStr }}</td>
                    <td>{{ $rateStr }}</td>
                    <td>{{ $costStr }}</td>
                    <td>{{ $shipStr }}</td>
                    <td>{{ $transStr }}</td>
                </tr>
@endforeach
            </tbody>
        </table>
        <table class="footer">
            <tr>
                <td class="terms">
                    <div class="footer-title">TERMS &amp; CONDITIONS</div>
                    <div class="footer-text">
                        The border service agency of any county maintains the right
                        to open and inspect any package from shipment.
                        LEEZFOOD NG. EXPORT is not responsible for any item
                        removed, opened, or qualified unit. We are not responsible
                        for any delay in transit and it is beyond our control.
                    </div>
                </td>
                <td class="bank">
                    <div class="footer-title">COMPANY BANK DETAILS</div>
                    <div class="footer-text">
                        Bank: {{ $bank_name ?? \App\Models\Setting::get('bank_name', 'Guaranty Trust Bank') }}<br>
                        Account Name: {{ $bank_account_name ?? \App\Models\Setting::get('bank_account_name', 'Leezo integrated') }}<br>
                        Account Number: {{ $bank_account_number ?? \App\Models\Setting::get('bank_account_number', '0900779403') }}
                    </div>
                </td>
                <td class="summary">
                    <table class="summary-table">
                        <tr>
                            <td class="summary-label">PROCUREMENT COST</td>
                            <td class="summary-value">₦{{ number_format((float)($procurement->total_cost ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label">SHIPMENT FEE</td>
                            <td class="summary-value">₦{{ number_format((float)($procurement->total_shipment_fee ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label">TRANSPORTATION</td>
                            <td class="summary-value">₦{{ number_format((float)($procurement->total_transportation ?? 0), 2) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="summary-label">TOTAL PAYMENT</td>
                            <td class="summary-value">₦{{ number_format((float)($procurement->grand_total ?? 0), 2) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <div class="signature">
            Signed by Management
        </div>
    </div>
</body>
</html>