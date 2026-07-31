<?php
// =============================================
// GST INVOICE - PROFESSIONAL PHP PAGE v3
// Theme: Red · Black · White | EbizTech
// =============================================

$company = [
    'name'      => 'E BUSINESS TECHNOLOGY SOLUTIONS',
    'short'     => 'EbizTech',
    'address'   => 'Office No-89, D-Wing, 4th Floor, Dhankawadi, Pune- 411043.',
    'contact'   => '77 55 97 97 97 / 92 70 40 97 97',
    'email'     => 'info@ebiztech.in',
    'gst'       => '27AAMFE3315J1ZD',
    'logo'      => 'logo.jpeg', // Replace with your actual logo path or URL
    'website'   => 'www.ebiztech.in',
];

$bank = [
    'account_name'   => 'E BUSINESS TECHNOLOGY SOLUTIONS',
    'account_number' => '610000000062910',
    'ifsc'           => 'SRCB0000038',
    'bank_name'      => 'Saraswat Co-Op Bank Ltd.',
    'branch'         => 'Tilak Road, Pune',
];

$terms = [
    'All payments shall be made in favor of <strong>E Business Technology</strong> only.',
    'A <strong>minimum lock-in period of 6 months</strong> is mandatory from the date of service activation.',
    'Services may be suspended or terminated without prior notice in case of payment default.',
    'All charges are exclusive of applicable taxes and statutory levies.',
    'Payments must be cleared within the agreed credit period mentioned in the invoice.',
    'Hardware warranty, if applicable, shall be covered by the respective manufacturer\'s warranty policy.',
    'Any additional customization, training, or support beyond the agreed scope shall be chargeable.',
    'The customer is responsible for maintaining the required internet connectivity and infrastructure for service operation.',
    'All payments made towards setup, licensing, subscription, and support services are <strong>non-refundable</strong>.',
    'Any disputes arising from the services shall be subject to the jurisdiction of <strong>Pune, Maharashtra</strong> only.',
    'AMC (Annual Maintenance Contract) support shall be available only during the active AMC period. Upon AMC expiry, no technical support, maintenance, troubleshooting, or related services will be provided. Continued support is subject to timely AMC renewal.',
];

$iso = 'ISO 9001:2015';

$invoice = [
    'number'      => '2026/1021',
    'date'        => '29-06-2026',
    'quote_no'    => '2025/1021',
    'customer_id' => 'PUN/106',
    'due_date'    => '08-07-2026',
];

$bill_to = [
    'company' => 'Blink Finance',
    'contact' => 'Ashish Ghadge',
    'address' => 'Sai Prasad Complex, A 001/002, Mira Bhayander Link Rd, beside SAGAR SANGAM HOTEL, CHSL, Geeta Nagar, Mira Road East, THANE, Mira Bhayandar, Maharashtra 401107',
    'gst'     => '',
    'email'   => '',
    'phone'   => '',
];

$tax_rate   = 0.18;
$totalSlots = 12;

$rows = [
    ['product' => 'GATEWAY',       'desc' => '32 Port GSM Gateway',                                                                                   'period' => '01-06-2026', 'qty' => 1, 'price' => 23000],
    ['product' => 'DIALER SERVER', 'desc' => 'Dell PowerEdge-R710, 16 Core, 16GB RAM, 1TB HDD (Refurbished) - Dialer Server',                        'period' => '01-06-2026', 'qty' => 1, 'price' => 0],
    ['product' => 'AMC',           'desc' => 'Domestic Call Center Open Source Omni-channel Contact Center Suite - Installation, Configuration & AMC', 'period' => '01-06-2026', 'qty' => 1, 'price' => 0],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GST Invoice <?= htmlspecialchars($invoice['number']) ?> - EbizTech</title>
    <style>
        /* ================================================================
           RESET & BASE
        ================================================================ */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }
        :root {
            --red: #c0152a;
            --red-dark: #8a0e1e;
            --red-light: #f8e8ea;
            --black: #111111;
            --gray-dark: #333333;
            --gray: #666666;
            --gray-light: #aaaaaa;
            --border: #e0e0e0;
            --bg: #f0f0f0;
            --white: #ffffff;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            background: var(--bg);
            color: var(--black);
            line-height: 1.5;
        }

        /* ================================================================
           TOOLBAR
        ================================================================ */
        #toolbar {
            background: var(--black);
            color: #fff;
            padding: 9px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            z-index: 200;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .5);
        }
        #toolbar .tb-brand {
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--red);
            flex: 1;
        }
        #toolbar .tb-brand span {
            color: #fff;
            font-weight: 400
        }

        .btn {
            padding: 6px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: .5px;
            transition: all .18s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn:hover {
            filter: brightness(1.15);
            transform: translateY(-1px)
        }
        .btn-green {
            background: #1a9148;
            color: #fff
        }
        .btn-red {
            background: var(--red);
            color: #fff
        }
        .btn-col {
            background: #2d6bcf;
            color: #fff
        }
        .btn-addcol {
            background: #8338a8;
            color: #fff
        }
        .btn-print {
            background: #e07b00;
            color: #fff
        }
        /* New Save & Download buttons */
        .btn-save {
            background: #0ea5e9;
            color: #fff;
        }
        .btn-download {
            background: #8b5cf6;
            color: #fff;
        }
        .btn-save:hover, .btn-download:hover {
            filter: brightness(1.15);
            transform: translateY(-1px);
        }

        /* ---- GST Toggle Tab ---- */
        .btn-gst {
            background: #444;
            color: #aaa;
            border: 1.5px solid transparent;
            border-radius: 20px;
            padding: 5px 18px;
            font-size: 11.5px;
            font-weight: 800;
            letter-spacing: 0.5px;
            transition: all 0.25s;
            cursor: pointer;
            text-transform: uppercase;
        }
        .btn-gst:hover {
            filter: brightness(1.3);
            transform: translateY(-1px);
        }
        .btn-gst.active {
            background: var(--red);
            color: #fff;
            border-color: var(--red-dark);
            box-shadow: 0 0 0 2px rgba(192, 21, 42, 0.35);
        }
        .btn-gst.inactive {
            background: #2a2a2a;
            color: #ccc;
            border-color: #555;
        }
        .btn-gst.inactive:hover {
            background: #3a3a3a;
            color: #fff;
        }

        /* ================================================================
           PANELS
        ================================================================ */
        .panel {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 12px 16px;
            position: absolute;
            top: 52px;
            z-index: 300;
            min-width: 200px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .18);
            display: none;
        }
        #colPanel {
            right: 230px
        }
        #addColPanel {
            right: 10px
        }
        .panel .panel-title {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--red);
            text-transform: uppercase;
            margin-bottom: 8px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 5px;
        }
        .panel label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 3px 0;
            cursor: pointer;
            color: #333;
            font-size: 12.5px;
        }
        .panel input[type=text] {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 5px 8px;
            font-size: 12px;
            margin-bottom: 6px;
        }
        .panel .btn-sm {
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 700;
            background: var(--red);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        /* ================================================================
           INVOICE WRAPPER
        ================================================================ */
        #invoiceWrap {
            max-width: 960px;
            margin: 24px auto;
            background: var(--white);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 30px rgba(0, 0, 0, .18);
        }

        /* RED STRIPES */
        .stripe-top {
            height: 7px;
            background: linear-gradient(90deg, var(--red-dark) 0%, var(--red) 60%, #e84060 100%);
        }

        /* WATERMARK */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 100px;
            font-weight: 900;
            letter-spacing: 8px;
            color: rgba(192, 21, 42, .045);
            pointer-events: none;
            user-select: none;
            white-space: nowrap;
            z-index: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            text-transform: uppercase;
        }

        .inv-body {
            position: relative;
            z-index: 1;
            padding: 24px 32px 18px
        }

        /* ================================================================
           HEADER  — logo LEFT-TOP, company info below logo
        ================================================================ */
        .inv-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 16px;
            margin-bottom: 16px;
            border-bottom: 2.5px solid var(--black);
        }

        /* Left block */
        .co-left {
            display: flex;
            flex-direction: column;
            max-width: 420px
        }

        /* Logo sits at very top-left - ENLARGED & POLISHED */
        .co-logo-wrap {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .co-logo {
            max-height: 100px;
            max-width: 260px;
            object-fit: contain;
            display: block;
            border-radius: 6px;
            background: #fff;
            padding: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: 0.2s;
        }
        .co-logo-text {
            font-size: 28px;
            font-weight: 900;
            color: var(--red);
            letter-spacing: 3px;
            margin-bottom: 8px;
            text-transform: uppercase;
            display: none;
        }
        .co-name {
            font-size: 15px;
            font-weight: 800;
            color: var(--black);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .co-info {
            color: var(--gray);
            font-size: 12px;
            line-height: 1.8
        }
        .co-gst {
            color: var(--black);
            font-weight: 700;
            font-size: 12px;
            margin-top: 4px;
            border-top: 1px dashed var(--border);
            padding-top: 4px;
            transition: opacity 0.25s, max-height 0.25s;
        }
        .co-gst.hidden-gst {
            opacity: 0;
            max-height: 0;
            margin-top: 0;
            padding-top: 0;
            border-top: none;
            overflow: hidden;
            pointer-events: none;
        }

        /* Right block */
        .inv-right {
            text-align: right;
            min-width: 250px
        }
        .inv-title-word {
            font-size: 42px;
            font-weight: 900;
            letter-spacing: 5px;
            color: var(--red);
            text-transform: uppercase;
            line-height: 1;
            margin-bottom: 6px;
        }

        /* ISO seal - bigger, prominent */
        .iso-seal {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 2.5px solid var(--red);
            border-radius: 6px;
            padding: 5px 14px;
            margin-bottom: 12px;
            color: var(--red);
            font-size: 14px;
            font-weight: 900;
            letter-spacing: 2px;
            background: var(--red-light);
        }
        .iso-cert-text {
            font-size: 9px;
            font-weight: 700;
            color: var(--gray);
            letter-spacing: 1px;
            text-transform: uppercase;
            display: block;
            line-height: 1;
            margin-top: 1px;
        }

        .meta-table {
            border-collapse: collapse;
            margin-left: auto
        }
        .meta-table td {
            padding: 3px 5px;
            font-size: 12px
        }
        .meta-table .ml {
            color: var(--gray);
            font-weight: 600;
            text-align: right;
            white-space: nowrap;
        }
        .meta-table .mv {
            font-weight: 700;
            color: var(--black);
            background: var(--red-light);
            border-left: 3px solid var(--red);
            padding-left: 8px;
            border-radius: 0 3px 3px 0;
            min-width: 120px;
        }
        .meta-table .mv[contenteditable]:focus {
            background: #fffbe6;
            outline: 1.5px solid var(--red);
        }

        /* ================================================================
           BILL TO BAND
        ================================================================ */
        .bill-band {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin-bottom: 0;
            border: 1.5px solid var(--black);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 0;
        }
        .bill-section {
            padding: 12px 16px
        }
        .bill-section:first-child {
            border-right: 1.5px solid var(--black)
        }
        .bill-head {
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 2px;
            color: var(--white);
            background: var(--black);
            text-transform: uppercase;
            padding: 5px 16px;
            margin: -12px -16px 10px;
        }
        .bill-company {
            font-size: 14px;
            font-weight: 800;
            color: var(--red);
            margin-bottom: 3px
        }
        .bill-info {
            font-size: 12px;
            color: var(--gray-dark);
            line-height: 1.8
        }
        [contenteditable]:focus {
            background: #fffbe6;
            outline: 1.5px solid var(--red);
            border-radius: 3px;
        }

        /* ================================================================
           PRODUCT TABLE
        ================================================================ */
        .table-wrap {
            overflow-x: auto;
            margin-bottom: 0
        }
        #productTable {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            border-top: 2.5px solid var(--red);
        }
        #productTable thead tr {
            background: var(--black);
            color: #fff
        }
        #productTable th {
            padding: 10px 10px;
            font-weight: 700;
            letter-spacing: .3px;
            white-space: nowrap;
            border-right: 1px solid rgba(255, 255, 255, .12);
            font-size: 11.5px;
        }
        #productTable th:last-child {
            border-right: none
        }
        #productTable td {
            padding: 8px 10px;
            border-bottom: 1px solid var(--border);
            border-right: 1px solid var(--border);
            vertical-align: top;
        }
        #productTable td:last-child {
            border-right: none
        }
        #productTable tbody tr:nth-child(odd) {
            background: #fff
        }
        #productTable tbody tr:nth-child(even) {
            background: #fafafa
        }
        #productTable tbody tr:hover {
            background: #fff0f1
        }
        #productTable .td-no {
            text-align: center;
            font-weight: 700;
            color: var(--red);
            background: var(--red-light) !important;
            width: 36px;
        }
        #productTable .td-amount {
            text-align: right;
            font-weight: 700;
            color: var(--black);
            white-space: nowrap;
        }
        #productTable .td-qty,
        #productTable .td-price {
            text-align: right
        }
        .row-hidden {
            display: none !important
        }
        .col-hidden {
            display: none !important
        }

        .row-actions {
            text-align: center;
            white-space: nowrap;
            width: 62px
        }
        .row-actions button {
            border: none;
            background: none;
            cursor: pointer;
            font-size: 13px;
            padding: 1px 3px;
            border-radius: 3px;
            transition: background .15s;
        }
        .row-actions button:hover {
            background: #f0f0f0
        }

        /* ================================================================
           BOTTOM GRID – two rows: Bank+Summary (row1), Terms (row2)
        ================================================================ */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: auto auto;
            gap: 0;
            border-top: 2.5px solid var(--black);
        }

        .bot-cell {
            padding: 14px 14px
        }

        .bot-cell:nth-child(2) {
            grid-column: 1 / 2;
            grid-row: 1;
        }
        .bot-cell:nth-child(3) {
            grid-column: 2 / 3;
            grid-row: 1;
            border-left: 1.5px solid var(--border);
        }
        .bot-cell:nth-child(1) {
            grid-column: 1 / -1;
            grid-row: 2;
            border-top: 1.5px solid var(--border);
        }

        .sec-head {
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--white);
            background: var(--red);
            padding: 5px 10px;
            margin: -14px -14px 10px;
            display: block;
        }

        /* Terms list */
        .terms-list {
            list-style: none;
            padding: 0;
            counter-reset: term-counter
        }
        .terms-list li {
            padding: 3px 0 3px 20px;
            position: relative;
            font-size: 10.5px;
            color: #444;
            line-height: 1.55;
            border-bottom: 1px dotted #e8e8e8;
            counter-increment: term-counter;
        }
        .terms-list li::before {
            content: counter(term-counter) '.';
            position: absolute;
            left: 0;
            top: 3px;
            color: var(--red);
            font-weight: 800;
            font-size: 10px;
        }
        .terms-list li strong {
            color: var(--black)
        }

        /* Bank */
        .bank-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 5px 10px;
            font-size: 11.5px
        }
        .bank-lbl {
            color: var(--gray);
            font-weight: 600;
            white-space: nowrap
        }
        .bank-val {
            font-weight: 700;
            color: var(--black)
        }

        /* Totals */
        .totals-table {
            width: 100%;
            border-collapse: collapse
        }
        .totals-table td {
            padding: 5px 8px;
            font-size: 12px
        }
        .totals-table .tl {
            color: var(--gray);
            font-size: 11.5px
        }
        .totals-table .tv {
            text-align: right;
            font-weight: 700
        }
        .totals-table .td-divider td {
            border-top: 1px solid var(--border)
        }
        .total-final {
            background: var(--black) !important
        }
        .total-final td {
            color: #fff !important;
            font-size: 15px !important;
            font-weight: 900 !important;
            padding: 9px 8px !important;
        }
        .total-final .tl {
            color: #bbb !important;
            font-size: 12px !important
        }
        .total-final .tv {
            color: var(--red) !important
        }

        /* GST row hiding */
        .gst-row {
            transition: opacity 0.25s, max-height 0.25s;
        }
        .gst-row.hidden-gst {
            display: none !important;
        }

        /* ================================================================
           FOOTER
        ================================================================ */
        .inv-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 13px 32px 18px;
            border-top: 2px solid var(--black);
            background: #fafafa;
        }
        .footer-left {
            font-size: 11px;
            color: var(--gray)
        }
        .footer-left strong {
            color: var(--black);
            font-size: 12px
        }
        .sig-box {
            text-align: center;
            min-width: 190px
        }
        .sig-company {
            font-size: 10px;
            color: var(--gray);
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: .5px
        }
        .sig-line {
            border-top: 1.5px solid var(--black);
            padding-top: 5px;
            font-size: 11px;
            color: var(--gray-dark);
            margin-top: 38px;
        }

        /* ================================================================
           PRINT
        ================================================================ */
        @media print {
            body {
                background: #fff
            }
            #toolbar,
            .panel {
                display: none !important
            }
            #invoiceWrap {
                margin: 0;
                box-shadow: none;
                border: none
            }
            .inv-body {
                padding: 12px 18px 10px
            }
            .row-actions {
                display: none !important
            }
            .col-hidden,
            .row-hidden {
                display: none !important
            }
            .stripe-top {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .sec-head {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            [contenteditable] {
                outline: none !important;
                background: transparent !important
            }
            .watermark {
                opacity: .035;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact
            }
            @page {
                size: A4;
                margin: 8mm
            }
            .co-gst.hidden-gst {
                opacity: 0 !important;
                max-height: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                border: none !important;
            }
            .gst-row.hidden-gst {
                display: none !important;
            }
        }

        /* Toast notification */
        .toast {
            visibility: hidden;
            min-width: 250px;
            background: #333;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 12px 20px;
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
            opacity: 0;
            transition: opacity 0.5s, visibility 0.5s;
        }
        .toast.show {
            visibility: visible;
            opacity: 1;
        }
        .toast.success {
            background: #16a34a;
        }
        .toast.error {
            background: #dc2626;
        }
    </style>
</head>
<body>

    <!-- ============================================================
         TOOLBAR
    ============================================================ -->
    <div id="toolbar">
        <div class="tb-brand">EBIZ<span>TECH</span> &middot; Invoice Builder</div>

        <!-- Add Row -->
        <button class="btn btn-green" onclick="addRow()">+ Add Row</button>

        <!-- ====== NEW: Without GST Bill Tab ====== -->
        <button class="btn btn-gst inactive" id="gstToggleBtn" onclick="toggleGst()">
            Without GST Bill
        </button>

        <button class="btn btn-red" onclick="removeLastVisible()">- Hide Row</button>
        <button class="btn btn-col" onclick="togglePanel('colPanel')">Columns</button>
        <button class="btn btn-addcol" onclick="togglePanel('addColPanel')">+ Add Column</button>
        <button class="btn btn-print" onclick="window.print()">Print / PDF</button>

        <!-- NEW: Save Bill -->
        <button class="btn btn-save" onclick="saveBill()"><i class="fas fa-save"></i> Save Bill</button>
        <!-- NEW: Download PDF (triggers print dialog) -->
        <button class="btn btn-download" onclick="downloadPDF()"><i class="fas fa-file-pdf"></i> Download PDF</button>

        <div id="colPanel" class="panel">
            <div class="panel-title">Show / Hide Columns</div>
        </div>

        <div id="addColPanel" class="panel">
            <div class="panel-title">Add New Column</div>
            <input type="text" id="newColName" placeholder="Column header name...">
            <button class="btn-sm" onclick="addColumn()">Add Column</button>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <!-- ============================================================
         INVOICE
    ============================================================ -->
    <div id="invoiceWrap">
        <div class="stripe-top"></div>
        <div class="watermark"><?= htmlspecialchars($company['short']) ?></div>

        <div class="inv-body">

            <!-- HEADER -->
            <div class="inv-header">

                <!-- LEFT: logo top (ENLARGED), then company info -->
                <div class="co-left">
                    <div class="co-logo-wrap">
                        <img src="<?= htmlspecialchars($company['logo']) ?>" class="co-logo" alt="<?= htmlspecialchars($company['name']) ?>" onerror="this.style.display='none';document.querySelector('.co-logo-text').style.display='block'">
                        <div class="co-logo-text"><?= htmlspecialchars($company['short']) ?></div>
                    </div>
                    <div class="co-name"><?= htmlspecialchars($company['name']) ?></div>
                    <div class="co-info">
                        <?= htmlspecialchars($company['address']) ?><br>
                        Contact: <?= htmlspecialchars($company['contact']) ?><br>
                        Email: <?= htmlspecialchars($company['email']) ?>&nbsp;&nbsp;|&nbsp;&nbsp;Web: <?= htmlspecialchars($company['website']) ?>
                    </div>
                    <!-- GST No. — toggled by "Without GST Bill" -->
                    <div class="co-gst" id="companyGst">GST No.: <?= htmlspecialchars($company['gst']) ?></div>
                </div>

                <!-- RIGHT: INVOICE title + ISO + meta -->
                <div class="inv-right">
                    <div class="inv-title-word">Invoice</div>

                    <!-- ISO Badge - Big & Prominent -->
                    <div style="margin-bottom:12px">
                        <span class="iso-seal">
                            <span style="font-size:20px;line-height:1">&#9679;</span>
                            <span>
                                <?= htmlspecialchars($iso) ?>
                                <span class="iso-cert-text">Certified Company</span>
                            </span>
                        </span>
                    </div>

                    <table class="meta-table">
                        <tr>
                            <td class="ml">Invoice No.</td>
                            <td class="mv" contenteditable="true" id="invoiceNumber"><?= htmlspecialchars($invoice['number']) ?></td>
                        </tr>
                        <tr>
                            <td class="ml">Date</td>
                            <td class="mv" contenteditable="true" id="invoiceDate"><?= htmlspecialchars($invoice['date']) ?></td>
                        </tr>
                        <tr>
                            <td class="ml">Quote No.</td>
                            <td class="mv" contenteditable="true"><?= htmlspecialchars($invoice['quote_no']) ?></td>
                        </tr>
                        <tr>
                            <td class="ml">Customer ID</td>
                            <td class="mv" contenteditable="true"><?= htmlspecialchars($invoice['customer_id']) ?></td>
                        </tr>
                        <tr>
                            <td class="ml">Due Date</td>
                            <td class="mv" contenteditable="true" id="dueDate"><?= htmlspecialchars($invoice['due_date']) ?></td>
                        </tr>
                    </table>
                </div>
            </div><!-- /.inv-header -->

            <!-- BILL TO -->
            <div class="bill-band">
                <div class="bill-section">
                    <div class="bill-head">Bill To</div>
                    <div class="bill-company" contenteditable="true" id="billCompany"><?= htmlspecialchars($bill_to['company']) ?></div>
                    <div class="bill-info">
                        <div contenteditable="true" id="billContact"><?= htmlspecialchars($bill_to['contact']) ?></div>
                        <div contenteditable="true" id="billAddress"><?= htmlspecialchars($bill_to['address']) ?></div>
                    </div>
                </div>
                <div class="bill-section">
                    <div class="bill-head">Client Details</div>
                    <div class="bill-info">
                        <div><strong>GST No. :</strong> <span contenteditable="true" id="billGst"><?= htmlspecialchars($bill_to['gst'] ?: '—') ?></span></div>
                        <div><strong>Email &nbsp;&nbsp;:</strong> <span contenteditable="true" id="billEmail"><?= htmlspecialchars($bill_to['email'] ?: '—') ?></span></div>
                        <div><strong>Phone &nbsp;:</strong> <span contenteditable="true" id="billPhone"><?= htmlspecialchars($bill_to['phone'] ?: '—') ?></span></div>
                    </div>
                </div>
            </div>

            <!-- PRODUCT TABLE -->
            <div class="table-wrap">
                <table id="productTable">
                    <thead>
                        <tr id="theadRow">
                            <th data-col="no" style="width:36px;text-align:center">#</th>
                            <th data-col="product">Product / Service</th>
                            <th data-col="desc">Description</th>
                            <th data-col="period">Billing Period</th>
                            <th data-col="qty" style="width:60px;text-align:right">Qty</th>
                            <th data-col="price" style="width:105px;text-align:right">Unit Price (Rs.)</th>
                            <th data-col="amount" style="width:115px;text-align:right">Amount (Rs.)</th>
                            <th data-col="actions" class="no-col-toggle" style="width:62px;text-align:center">Act.</th>
                        </tr>
                    </thead>
                    <tbody id="productBody">
                        <?php
                        for ($i = 0; $i < $totalSlots; $i++) {
                            $r   = $rows[$i] ?? null;
                            $cls = ($r === null) ? 'row-hidden' : '';
                            $amt = $r ? ($r['qty'] * $r['price']) : 0;
                            echo "<tr data-row=\"" . ($i + 1) . "\" class=\"$cls\">";
                            echo "<td data-col=\"no\" class=\"td-no\">" . ($r ? ($i + 1) : '') . "</td>";
                            echo "<td data-col=\"product\" contenteditable=\"true\">" . ($r ? htmlspecialchars($r['product']) : '') . "</td>";
                            echo "<td data-col=\"desc\"    contenteditable=\"true\">" . ($r ? htmlspecialchars($r['desc']) : '') . "</td>";
                            echo "<td data-col=\"period\"  contenteditable=\"true\">" . ($r ? htmlspecialchars($r['period']) : '') . "</td>";
                            echo "<td data-col=\"qty\"     contenteditable=\"true\" class=\"td-qty num-cell\" onblur=\"recalcRow(this)\">" . ($r ? $r['qty'] : '') . "</td>";
                            echo "<td data-col=\"price\"   contenteditable=\"true\" class=\"td-price num-cell\" onblur=\"recalcRow(this)\">" . ($r ? $r['price'] : '') . "</td>";
                            echo "<td data-col=\"amount\"  class=\"td-amount amount-cell\">" . ($r && $amt > 0 ? number_format($amt, 2) : '') . "</td>";
                            echo "<td data-col=\"actions\" class=\"row-actions no-col-toggle\">";
                            echo "<button onclick=\"showRow(" . ($i + 1) . ")\" title=\"Show row\">Show</button> ";
                            echo "<button onclick=\"hideRow(" . ($i + 1) . ")\" title=\"Hide row\">Hide</button>";
                            echo "</td></tr>\n";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        </div><!-- /.inv-body -->

        <!-- BOTTOM GRID – Terms now on its own row below Bank + Summary -->
        <div class="bottom-grid">

            <!-- TERMS – full width, second row -->
            <div class="bot-cell">
                <span class="sec-head">Terms &amp; Conditions</span>
                <ol class="terms-list">
                    <?php foreach ($terms as $t): ?>
                        <li><?= $t ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>

            <!-- BANK – first row, left column -->
            <div class="bot-cell">
                <span class="sec-head">Bank Details</span>
                <div class="bank-grid">
                    <span class="bank-lbl">Account Name</span><span class="bank-val"><?= htmlspecialchars($bank['account_name']) ?></span>
                    <span class="bank-lbl">Account No.</span> <span class="bank-val"><?= htmlspecialchars($bank['account_number']) ?></span>
                    <span class="bank-lbl">IFSC Code</span>   <span class="bank-val"><?= htmlspecialchars($bank['ifsc']) ?></span>
                    <span class="bank-lbl">Bank Name</span>   <span class="bank-val"><?= htmlspecialchars($bank['bank_name']) ?></span>
                    <span class="bank-lbl">Branch</span>      <span class="bank-val"><?= htmlspecialchars($bank['branch']) ?></span>
                </div>
            </div>

            <!-- SUMMARY – first row, right column -->
            <div class="bot-cell">
                <span class="sec-head">Summary</span>
                <table class="totals-table">
                    <tr>
                        <td class="tl">Subtotal</td>
                        <td class="tv" id="t_sub">Rs. 0.00</td>
                    </tr>
                    <tr>
                        <td class="tl">Discount</td>
                        <td class="tv">Rs. 0.00</td>
                    </tr>
                    <tr class="td-divider">
                        <td class="tl">Sub Less Discount</td>
                        <td class="tv" id="t_less">Rs. 0.00</td>
                    </tr>
                    <!-- GST row – toggled by "Without GST Bill" -->
                    <tr class="gst-row" id="gstRow">
                        <td class="tl">
                            GST (<?= ($tax_rate * 100) ?>%)<br>
                            <small style="font-size:10px;color:#aaa">CGST <?= ($tax_rate * 50) ?>% + SGST <?= ($tax_rate * 50) ?>%</small>
                        </td>
                        <td class="tv" id="t_tax">Rs. 0.00</td>
                    </tr>
                    <tr>
                        <td class="tl">Other Charges</td>
                        <td class="tv">Rs. 0.00</td>
                    </tr>
                    <tr class="total-final">
                        <td class="tl">GRAND TOTAL</td>
                        <td class="tv" id="t_grand">Rs. 0.00</td>
                    </tr>
                </table>
            </div>

        </div><!-- /.bottom-grid -->

        <!-- FOOTER -->
        <div class="inv-footer">
            <div class="footer-left">
                <strong><?= htmlspecialchars($company['name']) ?></strong><br>
                <?= htmlspecialchars($company['address']) ?><br>
                Generated: <?= date('d M Y, h:i A') ?>
            </div>
            <div class="sig-box">
                <div class="sig-company">For <?= htmlspecialchars($company['name']) ?></div>
                <div class="sig-line">Authorized Signatory</div>
            </div>
        </div>

        <div class="stripe-top"></div>
    </div><!-- /#invoiceWrap -->

    <!-- ============================================================
         SCRIPTS
    ============================================================ -->
    <script>
        // ============================================================
        const TAX_RATE = <?= $tax_rate ?>;
        const TOTAL_SLOTS = <?= $totalSlots ?>;

        // ---- GST toggle state ----
        let gstEnabled = true; // true = with GST, false = without GST

        let COLUMNS = [
            { col: 'no', label: '# No.' },
            { col: 'product', label: 'Product/Service' },
            { col: 'desc', label: 'Description' },
            { col: 'period', label: 'Billing Period' },
            { col: 'qty', label: 'Qty' },
            { col: 'price', label: 'Unit Price' },
            { col: 'amount', label: 'Amount' },
        ];

        // ---- PANEL TOGGLE ----
        function togglePanel(id) {
            ['colPanel', 'addColPanel'].forEach(p => {
                const el = document.getElementById(p);
                el.style.display = (p === id && el.style.display !== 'block') ? 'block' : 'none';
            });
        }
        document.addEventListener('click', e => {
            if (!e.target.closest('.panel') && !e.target.closest('.btn')) {
                document.querySelectorAll('.panel').forEach(p => p.style.display = 'none');
            }
        });

        // ---- COLUMN PANEL ----
        function buildColPanel() {
            const panel = document.getElementById('colPanel');
            panel.querySelectorAll('label').forEach(l => l.remove());
            COLUMNS.forEach(c => {
                const lbl = document.createElement('label');
                const chk = document.createElement('input');
                chk.type = 'checkbox';
                chk.checked = true;
                chk.dataset.col = c.col;
                chk.addEventListener('change', () => toggleColumn(c.col, chk.checked));
                lbl.appendChild(chk);
                lbl.appendChild(document.createTextNode(' ' + c.label));
                panel.appendChild(lbl);
            });
        }

        function toggleColumn(col, visible) {
            document.querySelectorAll(`[data-col="${col}"]`).forEach(el => {
                el.classList.toggle('col-hidden', !visible);
            });
        }

        // ---- ADD CUSTOM COLUMN ----
        function addColumn() {
            const input = document.getElementById('newColName');
            const name = input.value.trim();
            if (!name) { alert('Please enter a column name.'); return; }
            const key = 'custom_' + Date.now();

            const thead = document.getElementById('theadRow');
            const actTh = thead.querySelector('th[data-col="actions"]');
            const th = document.createElement('th');
            th.dataset.col = key;
            th.textContent = name;
            thead.insertBefore(th, actTh);

            document.querySelectorAll('#productBody tr').forEach(row => {
                const actTd = row.querySelector('td[data-col="actions"]');
                const td = document.createElement('td');
                td.dataset.col = key;
                td.contentEditable = 'true';
                row.insertBefore(td, actTd);
            });

            COLUMNS.push({ col: key, label: name });
            buildColPanel();
            document.querySelectorAll('#colPanel input[type=checkbox]').forEach(chk => {
                const first = document.querySelector(`[data-col="${chk.dataset.col}"]`);
                chk.checked = first ? !first.classList.contains('col-hidden') : true;
            });

            input.value = '';
            document.getElementById('addColPanel').style.display = 'none';
        }

        // ---- ROW MANAGEMENT ----
        function getRows() { return document.querySelectorAll('#productBody tr') }

        function showRow(n) {
            const r = document.querySelector(`#productBody tr[data-row="${n}"]`);
            if (r) { r.classList.remove('row-hidden');
                renumber();
                recalcAll(); }
        }

        function hideRow(n) {
            const r = document.querySelector(`#productBody tr[data-row="${n}"]`);
            if (r) { r.classList.add('row-hidden');
                renumber();
                recalcAll(); }
        }

        function addRow() {
            for (const r of getRows()) {
                if (r.classList.contains('row-hidden')) {
                    r.classList.remove('row-hidden');
                    r.querySelectorAll('[contenteditable]').forEach(c => c.textContent = '');
                    r.querySelector('.amount-cell').textContent = '';
                    renumber();
                    recalcAll();
                    return;
                }
            }
            const tbody = document.getElementById('productBody');
            const idx = tbody.rows.length + 1;
            const tr = document.createElement('tr');
            tr.dataset.row = idx;

            let html = `<td data-col="no" class="td-no"></td>`;
            COLUMNS.forEach(c => {
                if (c.col === 'no') return;
                const isNum = c.col === 'qty' || c.col === 'price';
                const isAmt = c.col === 'amount';
                const cls = isNum ? 'num-cell ' + (c.col === 'qty' ? 'td-qty' : 'td-price') : (isAmt ?
                    'amount-cell td-amount' : '');
                const blur = isNum ? 'onblur="recalcRow(this)"' : '';
                const ce = !isAmt ? 'contenteditable="true"' : '';
                html += `<td data-col="${c.col}" ${ce} class="${cls}" ${blur}></td>`;
            });
            html += `<td data-col="actions" class="row-actions no-col-toggle">
                <button onclick="showRow(${idx})" title="Show">Show</button>
                <button onclick="hideRow(${idx})" title="Hide">Hide</button>
              </td>`;
            tr.innerHTML = html;
            tbody.appendChild(tr);

            COLUMNS.forEach(c => {
                const chk = document.querySelector(`#colPanel input[data-col="${c.col}"]`);
                if (chk && !chk.checked) toggleColumn(c.col, false);
            });
            renumber();
            recalcAll();
        }

        function removeLastVisible() {
            const vis = [...getRows()].filter(r => !r.classList.contains('row-hidden'));
            if (!vis.length) return;
            vis[vis.length - 1].classList.add('row-hidden');
            renumber();
            recalcAll();
        }

        function renumber() {
            let n = 1;
            getRows().forEach(r => {
                if (r.classList.contains('row-hidden')) return;
                r.querySelector('[data-col="no"]').textContent = n++;
            });
        }

        // ---- CALCULATIONS ----
        function recalcRow(cell) {
            const row = cell.closest('tr');
            const qty = parseFloat(row.querySelector('[data-col="qty"]').textContent) || 0;
            const price = parseFloat(row.querySelector('[data-col="price"]').textContent) || 0;
            const amt = qty * price;
            row.querySelector('[data-col="amount"]').textContent = amt > 0 ? fmt(amt) : '';
            recalcAll();
        }

        function recalcAll() {
            let sub = 0;
            getRows().forEach(r => {
                if (r.classList.contains('row-hidden')) return;
                const t = r.querySelector('[data-col="amount"]').textContent.replace(/,/g, '');
                sub += parseFloat(t) || 0;
            });

            // GST or no GST
            const tax = gstEnabled ? sub * TAX_RATE : 0;
            const grand = sub + tax;

            document.getElementById('t_sub').textContent = fmtRs(sub);
            document.getElementById('t_less').textContent = fmtRs(sub);
            document.getElementById('t_tax').textContent = fmtRs(tax);
            document.getElementById('t_grand').textContent = fmtRs(grand);
        }

        function fmt(v) { return v.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') }

        function fmtRs(v) { return 'Rs. ' + fmt(v) }

        // ---- GST TOGGLE ----
        function toggleGst() {
            gstEnabled = !gstEnabled;
            const btn = document.getElementById('gstToggleBtn');
            const gstRow = document.getElementById('gstRow');
            const companyGst = document.getElementById('companyGst');

            if (gstEnabled) {
                // WITH GST
                btn.textContent = 'Without GST Bill';
                btn.className = 'btn btn-gst inactive';
                gstRow.classList.remove('hidden-gst');
                companyGst.classList.remove('hidden-gst');
                companyGst.style.display = 'block';
            } else {
                // WITHOUT GST
                btn.textContent = 'With GST Bill';
                btn.className = 'btn btn-gst active';
                gstRow.classList.add('hidden-gst');
                companyGst.classList.add('hidden-gst');
                companyGst.style.display = 'none';
            }
            recalcAll();
        }

        // ---- SAVE BILL ----
        function saveBill() {
            // Collect data
            const billNumber = document.getElementById('invoiceNumber').innerText.trim();
            const billDate = document.getElementById('invoiceDate').innerText.trim();
            const dueDate = document.getElementById('dueDate').innerText.trim();
            const companyName = document.getElementById('billCompany').innerText.trim();
            const contact = document.getElementById('billContact').innerText.trim();
            const address = document.getElementById('billAddress').innerText.trim();
            const gst = document.getElementById('billGst').innerText.trim();
            const email = document.getElementById('billEmail').innerText.trim();
            const phone = document.getElementById('billPhone').innerText.trim();

            // Get product rows data
            const rows = [];
            document.querySelectorAll('#productBody tr:not(.row-hidden)').forEach(tr => {
                const product = tr.querySelector('[data-col="product"]')?.innerText.trim() || '';
                const desc = tr.querySelector('[data-col="desc"]')?.innerText.trim() || '';
                const period = tr.querySelector('[data-col="period"]')?.innerText.trim() || '';
                const qty = parseFloat(tr.querySelector('[data-col="qty"]')?.innerText.trim()) || 0;
                const price = parseFloat(tr.querySelector('[data-col="price"]')?.innerText.trim()) || 0;
                const amount = parseFloat(tr.querySelector('[data-col="amount"]')?.innerText.replace(/,/g, '')) || 0;
                rows.push({ product, desc, period, qty, price, amount });
            });

            // Get totals
            const subtotal = parseFloat(document.getElementById('t_sub').innerText.replace(/[Rs.,\s]/g, '')) || 0;
            const tax = parseFloat(document.getElementById('t_tax').innerText.replace(/[Rs.,\s]/g, '')) || 0;
            const grandTotal = parseFloat(document.getElementById('t_grand').innerText.replace(/[Rs.,\s]/g, '')) || 0;

            // Determine status (e.g., based on due date or manual)
            const status = 'Unpaid'; // Default, can be changed later

            // Prepare payload
            const payload = {
                bill_type: 'invoice',
                bill_number: billNumber,
                bill_date: billDate,
                due_date: dueDate,
                agency_name: companyName,
                contact: contact,
                address: address,
                gst: gst,
                email: email,
                phone: phone,
                rows: rows,
                subtotal: subtotal,
                tax: tax,
                grand_total: grandTotal,
                status: status
            };

            // Send via fetch to save_bill.php
            fetch('save_bill.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Bill saved successfully!', 'success');
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error saving bill: ' + error.message, 'error');
            });
        }

        // ---- DOWNLOAD PDF ----
        function downloadPDF() {
            // Trigger print dialog which allows saving as PDF
            window.print();
        }

        // ---- TOAST NOTIFICATION ----
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type + ' show';
            setTimeout(() => {
                toast.className = 'toast';
            }, 4000);
        }

        // ---- INIT ----
        buildColPanel();
        recalcAll();

        // ensure GST toggle starts in correct state (with GST)
        document.addEventListener('DOMContentLoaded', function() {
            // force initial state: with GST
            gstEnabled = true;
            const btn = document.getElementById('gstToggleBtn');
            btn.textContent = 'Without GST Bill';
            btn.className = 'btn btn-gst inactive';
            document.getElementById('gstRow').classList.remove('hidden-gst');
            document.getElementById('companyGst').classList.remove('hidden-gst');
            document.getElementById('companyGst').style.display = 'block';
            recalcAll();
        });
    </script>

</body>
</html>