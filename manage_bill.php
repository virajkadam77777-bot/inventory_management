<?php
// ==============================================
// Manage Bill Page
// File: manage_bill.php
// Displays all bills (Invoices & Quotations) 
// grouped by Agency, in a card/folder view.
// ==============================================

// Start session and include necessary files (if any)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Sample Data (simulate database) ---
// In a real app, fetch from DB tables: agencies, invoices, quotations
$agencies = [
    ['id' => 1, 'name' => 'Blink Finance', 'code' => 'BLINK'],
    ['id' => 2, 'name' => 'Sk mind tech info', 'code' => 'SKMIND'],
    ['id' => 3, 'name' => 'Apex Solutions', 'code' => 'APEX'],
    ['id' => 4, 'name' => 'Vision IT Services', 'code' => 'VISION'],
];

// Sample bills (invoices + quotations) with agency_id, type, number, date, amount, status
$bills = [
    // Agency 1 - Blink Finance
    ['id' => 1, 'agency_id' => 1, 'type' => 'invoice', 'number' => 'INV-2026-001', 'date' => '2026-06-15', 'amount' => 45000.00, 'status' => 'Paid'],
    ['id' => 2, 'agency_id' => 1, 'type' => 'invoice', 'number' => 'INV-2026-002', 'date' => '2026-07-20', 'amount' => 23000.00, 'status' => 'Unpaid'],
    ['id' => 3, 'agency_id' => 1, 'type' => 'quotation', 'number' => 'QUO-2026-101', 'date' => '2026-05-10', 'amount' => 12000.00, 'status' => 'Accepted'],
    ['id' => 4, 'agency_id' => 1, 'type' => 'quotation', 'number' => 'QUO-2026-102', 'date' => '2026-08-05', 'amount' => 8000.00, 'status' => 'Pending'],
    // Agency 2 - Sk mind tech info
    ['id' => 5, 'agency_id' => 2, 'type' => 'invoice', 'number' => 'INV-2026-003', 'date' => '2026-06-25', 'amount' => 75000.00, 'status' => 'Paid'],
    ['id' => 6, 'agency_id' => 2, 'type' => 'quotation', 'number' => 'QUO-2026-201', 'date' => '2026-07-01', 'amount' => 50000.00, 'status' => 'Converted'],
    ['id' => 7, 'agency_id' => 2, 'type' => 'invoice', 'number' => 'INV-2026-004', 'date' => '2026-08-10', 'amount' => 32000.00, 'status' => 'Unpaid'],
    // Agency 3 - Apex Solutions
    ['id' => 8, 'agency_id' => 3, 'type' => 'quotation', 'number' => 'QUO-2026-301', 'date' => '2026-05-20', 'amount' => 15000.00, 'status' => 'Rejected'],
    ['id' => 9, 'agency_id' => 3, 'type' => 'invoice', 'number' => 'INV-2026-005', 'date' => '2026-07-15', 'amount' => 28000.00, 'status' => 'Paid'],
    // Agency 4 - Vision IT Services
    ['id' => 10, 'agency_id' => 4, 'type' => 'invoice', 'number' => 'INV-2026-006', 'date' => '2026-08-01', 'amount' => 66000.00, 'status' => 'Unpaid'],
];

// Group bills by agency
$agencyBills = [];
foreach ($bills as $bill) {
    $agencyBills[$bill['agency_id']][] = $bill;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bill - StockMaster Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ============================================================
           RESET & BASE
        ============================================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            margin-left: 280px; /* sidebar width */
            padding: 30px 30px 50px;
            min-height: 100vh;
        }
        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 20px 15px;
            }
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }
        .page-header h1 {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #0f172a;
        }
        .page-header h1 i {
            color: #2dd4bf;
            margin-right: 10px;
        }
        .page-header .subtitle {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }
        .breadcrumb {
            font-size: 13px;
            color: #94a3b8;
        }
        .breadcrumb a {
            color: #2dd4bf;
            text-decoration: none;
        }

        /* ============================================================
           AGENCY CARDS (Folders)
        ============================================================ */
        .agency-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .agency-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 20px 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s ease;
            border: 2px solid transparent;
            position: relative;
        }
        .agency-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
            border-color: #2dd4bf;
        }
        .agency-card .folder-icon {
            font-size: 48px;
            color: #2dd4bf;
            margin-bottom: 8px;
        }
        .agency-card .agency-name {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
        }
        .agency-card .agency-code {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 2px;
        }
        .agency-card .bill-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #2dd4bf;
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
        }
        .agency-card.active {
            border-color: #2dd4bf;
            background: #f0fdfa;
        }

        /* ============================================================
           BILL DETAILS PANEL
        ============================================================ */
        .bill-panel {
            margin-top: 30px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 20px 24px 30px;
            display: none;
        }
        .bill-panel.active {
            display: block;
        }
        .bill-panel .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .bill-panel .panel-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
        }
        .bill-panel .panel-header h2 i {
            color: #2dd4bf;
            margin-right: 8px;
        }
        .bill-panel .panel-header .close-panel {
            background: none;
            border: none;
            font-size: 22px;
            color: #94a3b8;
            cursor: pointer;
            transition: color 0.2s;
        }
        .bill-panel .panel-header .close-panel:hover {
            color: #ef4444;
        }

        /* Month-wise grouping */
        .month-group {
            margin-bottom: 25px;
        }
        .month-group .month-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            background: #f8fafc;
            padding: 8px 16px;
            border-radius: 8px;
            margin-bottom: 12px;
            border-left: 4px solid #2dd4bf;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }
        .bill-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .bill-table th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
        }
        .bill-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .bill-table tr:hover td {
            background: #f8fafc;
        }
        .bill-table .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-invoice {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .badge-quotation {
            background: #fef3c7;
            color: #b45309;
        }
        .badge-paid {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-unpaid {
            background: #fee2e2;
            color: #b91c1c;
        }
        .badge-pending {
            background: #fef9c3;
            color: #854d0e;
        }
        .badge-accepted {
            background: #dbeafe;
            color: #1e40af;
        }
        .badge-converted {
            background: #e0e7ff;
            color: #3730a3;
        }
        .badge-rejected {
            background: #f3e8e8;
            color: #991b1b;
        }
        .badge-default {
            background: #e2e8f0;
            color: #475569;
        }
        .bill-table .amount {
            font-weight: 700;
            color: #0f172a;
        }
        .bill-table .type-icon {
            margin-right: 5px;
        }
        .no-bills {
            text-align: center;
            color: #94a3b8;
            padding: 30px 0;
        }

        /* ============================================================
           RESPONSIVE TWEAKS
        ============================================================ */
        @media (max-width: 600px) {
            .agency-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            }
            .bill-table {
                font-size: 12px;
            }
            .bill-table th,
            .bill-table td {
                padding: 6px 8px;
            }
        }

        /* ============================================================
           SIDEBAR OVERRIDE (if included separately)
           We assume sidebar.php is included, but we are independent.
           We'll add a note to include it.
        ============================================================ */
        /* If sidebar.php is included, we need to adjust body margin */
        /* This style works with sidebar fixed at 280px width */
    </style>
</head>
<body>
    <!-- In a real admin panel, sidebar.php is included here -->
    <!-- For this standalone page, we assume the sidebar is already present -->
    <!-- We'll just place a note, but the sidebar is not included in this file -->

    <div class="page-header">
        <div>
            <h1><i class="fas fa-folder-open"></i> Manage Bills</h1>
            <div class="subtitle">View all Invoices and Quotations grouped by Agency</div>
        </div>
        <div class="breadcrumb">
            <a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a> / Manage Bills
        </div>
    </div>

    <!-- Agency Cards Grid -->
    <div class="agency-grid" id="agencyGrid">
        <?php foreach ($agencies as $agency): 
            $count = isset($agencyBills[$agency['id']]) ? count($agencyBills[$agency['id']]) : 0;
        ?>
        <div class="agency-card" data-agency-id="<?= $agency['id'] ?>" onclick="selectAgency(<?= $agency['id'] ?>, '<?= htmlspecialchars($agency['name']) ?>')">
            <div class="folder-icon"><i class="fas fa-folder"></i></div>
            <div class="agency-name"><?= htmlspecialchars($agency['name']) ?></div>
            <div class="agency-code"><?= htmlspecialchars($agency['code']) ?></div>
            <div class="bill-count"><?= $count ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Bill Details Panel -->
    <div class="bill-panel" id="billPanel">
        <div class="panel-header">
            <h2><i class="fas fa-file-invoice"></i> <span id="selectedAgencyName">Agency</span> – Bills</h2>
            <button class="close-panel" onclick="closePanel()"><i class="fas fa-times"></i></button>
        </div>
        <div id="billContent">
            <!-- dynamically filled -->
        </div>
    </div>

    <script>
        // Data passed from PHP
        const agencyBills = <?= json_encode($agencyBills) ?>;
        const agencies = <?= json_encode($agencies) ?>;

        // Helper to format month-year
        function formatMonthYear(dateStr) {
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleString('default', { month: 'long', year: 'numeric' });
        }

        // Helper to get status badge class
        function getStatusClass(status) {
            const map = {
                'Paid': 'badge-paid',
                'Unpaid': 'badge-unpaid',
                'Pending': 'badge-pending',
                'Accepted': 'badge-accepted',
                'Converted': 'badge-converted',
                'Rejected': 'badge-rejected'
            };
            return map[status] || 'badge-default';
        }

        // Helper to get type badge class
        function getTypeClass(type) {
            return type === 'invoice' ? 'badge-invoice' : 'badge-quotation';
        }

        // Render bills for selected agency
        function renderBills(agencyId) {
            const container = document.getElementById('billContent');
            const bills = agencyBills[agencyId] || [];

            if (bills.length === 0) {
                container.innerHTML = `<div class="no-bills"><i class="fas fa-inbox fa-3x" style="opacity:0.3;display:block;margin-bottom:10px;"></i> No bills found for this agency.</div>`;
                return;
            }

            // Sort bills by date descending (newest first)
            bills.sort((a, b) => new Date(b.date) - new Date(a.date));

            // Group by month-year
            const groups = {};
            bills.forEach(bill => {
                const key = formatMonthYear(bill.date);
                if (!groups[key]) groups[key] = [];
                groups[key].push(bill);
            });

            // Build HTML
            let html = '';
            for (const [month, billsInMonth] of Object.entries(groups)) {
                html += `<div class="month-group">
                    <div class="month-title"><i class="far fa-calendar-alt" style="margin-right:8px;"></i>${month}</div>
                    <div class="table-responsive">
                        <table class="bill-table">
                            <thead>
                                <tr>
                                    <th>Bill #</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>`;
                billsInMonth.forEach(bill => {
                    const typeIcon = bill.type === 'invoice' ? 'fa-file-invoice' : 'fa-file-invoice-dollar';
                    const typeLabel = bill.type.charAt(0).toUpperCase() + bill.type.slice(1);
                    html += `<tr>
                        <td><strong>${htmlspecialchars(bill.number)}</strong></td>
                        <td><span class="badge ${getTypeClass(bill.type)}"><i class="fas ${typeIcon} type-icon"></i>${typeLabel}</span></td>
                        <td>${bill.date}</td>
                        <td class="amount">Rs. ${Number(bill.amount).toFixed(2)}</td>
                        <td><span class="badge ${getStatusClass(bill.status)}">${bill.status}</span></td>
                        <td>
                            <a href="#" onclick="viewBill('${bill.type}', ${bill.id})" style="color:#2dd4bf;text-decoration:none;font-weight:600;">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>`;
                });
                html += `</tbody></table></div></div>`;
            }

            container.innerHTML = html;
        }

        // Helper (simple) – for demo, just alert
        function viewBill(type, id) {
            alert(`Viewing ${type} with ID: ${id}\n(Implement actual view link)`);
            // In real app, redirect to view page: e.g., view_invoice.php?id=... or view_quotation.php?id=...
        }

        // Select agency
        function selectAgency(agencyId, agencyName) {
            // Highlight card
            document.querySelectorAll('.agency-card').forEach(card => {
                card.classList.remove('active');
                if (card.dataset.agencyId == agencyId) {
                    card.classList.add('active');
                }
            });

            // Show panel
            const panel = document.getElementById('billPanel');
            panel.classList.add('active');
            document.getElementById('selectedAgencyName').textContent = agencyName;

            // Render bills
            renderBills(agencyId);
        }

        // Close panel
        function closePanel() {
            document.getElementById('billPanel').classList.remove('active');
            document.querySelectorAll('.agency-card').forEach(card => card.classList.remove('active'));
        }

        // Optional: Auto-select first agency if any
        document.addEventListener('DOMContentLoaded', function() {
            // If there are agencies, select the first one by default
            if (agencies.length > 0) {
                const first = agencies[0];
                selectAgency(first.id, first.name);
            }
        });

        // Simple htmlspecialchars for JS (not fully secure but fine for demo)
        function htmlspecialchars(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>

    <!-- In production, include sidebar.php before this page -->
    <!-- For standalone testing, we show a note -->
    <div style="margin-top: 30px; padding: 15px; background: #e2e8f0; border-radius: 8px; font-size: 13px; color: #475569; text-align: center;">
        <i class="fas fa-info-circle"></i> This page is designed to be used with the admin sidebar. 
        Include <strong>sidebar.php</strong> before this content for full integration.
    </div>
</body>
</html>