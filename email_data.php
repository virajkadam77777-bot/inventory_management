<?php
// ============================================================
//  Email Data Module – uses shared db_connection.php
// ============================================================

require_once 'db_connection.php';   // Provides $db (PDO) and constants

// ------------------------------------------------------------------
//  Handle AJAX requests – JSON responses
// ------------------------------------------------------------------
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'getAll') {
    header('Content-Type: application/json; charset=utf-8');
    $stmt = $db->query("SELECT id, sender_name, sender_email, subject, content, status, date FROM emails ORDER BY date DESC");
    $emails = $stmt->fetchAll();
    echo json_encode($emails);
    exit;
}

if ($action === 'save') {
    header('Content-Type: application/json; charset=utf-8');
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = $data['id'] ?? null;
    $senderName = trim($data['senderName'] ?? '');
    $senderEmail = trim($data['senderEmail'] ?? '');
    $subject = trim($data['subject'] ?? '');
    $content = trim($data['content'] ?? '');
    $status = $data['status'] ?? 'unread';
    $date = $data['date'] ?? date('Y-m-d H:i:s');

    if (empty($senderName) || empty($senderEmail) || empty($subject)) {
        http_response_code(400);
        echo json_encode(['error' => 'Sender name, email, and subject are required.']);
        exit;
    }

    if ($id) {
        $stmt = $db->prepare("UPDATE emails SET sender_name=?, sender_email=?, subject=?, content=?, status=?, date=? WHERE id=?");
        $stmt->execute([$senderName, $senderEmail, $subject, $content, $status, $date, $id]);
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO emails (sender_name, sender_email, subject, content, status, date) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$senderName, $senderEmail, $subject, $content, $status, $date]);
        echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    }
    exit;
}

if ($action === 'delete') {
    header('Content-Type: application/json; charset=utf-8');
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    if ($id) {
        $stmt = $db->prepare("DELETE FROM emails WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid ID']);
    }
    exit;
}

// ---- No action – serve the HTML page ----
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>StockMaster Pro – Email Data</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        /* (CSS omitted for brevity – same as before) */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f4f7fc; padding: 30px; }
        .app-container { max-width: 1400px; margin: 0 auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 28px; }
        .page-header h1 { font-size: 28px; font-weight: 700; color: #0b2b4a; letter-spacing: -0.3px; }
        .page-header h1 i { color: #2a7de1; margin-right: 10px; }
        .header-actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn { padding: 10px 22px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s ease; text-decoration: none; }
        .btn-primary { background: #2a7de1; color: #fff; }
        .btn-primary:hover { background: #1b5fb0; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(42,125,225,0.30); }
        .btn-outline { background: transparent; border: 1.5px solid #cbd5e1; color: #1e293b; }
        .btn-outline:hover { background: #e9edf2; border-color: #94a3b8; }
        .stats-bar { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 28px; }
        .stat-card { background: #fff; padding: 18px 28px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); flex: 1 1 160px; display: flex; align-items: center; gap: 16px; border-left: 5px solid #2a7de1; }
        .stat-card .stat-icon { font-size: 28px; color: #2a7de1; width: 44px; text-align: center; }
        .stat-card .stat-info h4 { font-size: 13px; font-weight: 500; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
        .stat-card .stat-info .number { font-size: 26px; font-weight: 700; color: #0b2b4a; }
        .stat-card.green { border-left-color: #1e9b5e; }
        .stat-card.green .stat-icon { color: #1e9b5e; }
        .stat-card.orange { border-left-color: #e68a2e; }
        .stat-card.orange .stat-icon { color: #e68a2e; }
        .stat-card.purple { border-left-color: #7c3aed; }
        .stat-card.purple .stat-icon { color: #7c3aed; }
        .filter-bar { background: #fff; padding: 18px 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; flex-wrap: wrap; align-items: center; gap: 16px; margin-bottom: 28px; }
        .filter-bar .search-wrap { flex: 2 1 260px; display: flex; align-items: center; background: #f1f5f9; border-radius: 30px; padding: 0 16px; transition: 0.2s; }
        .filter-bar .search-wrap:focus-within { background: #fff; box-shadow: 0 0 0 2px #2a7de1; }
        .filter-bar .search-wrap i { color: #94a3b8; font-size: 15px; }
        .filter-bar .search-wrap input { border: none; background: transparent; padding: 12px 12px; font-size: 14px; width: 100%; outline: none; }
        .filter-bar .filter-group { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .filter-bar .filter-group select { padding: 10px 14px; border-radius: 30px; border: 1.5px solid #e2e8f0; background: #fff; font-size: 13px; font-weight: 500; color: #1e293b; outline: none; cursor: pointer; }
        .filter-bar .filter-group select:focus { border-color: #2a7de1; }
        .table-wrap { background: #fff; border-radius: 14px; box-shadow: 0 4px 16px rgba(0,0,0,0.04); overflow-x: auto; padding: 4px 0 0 0; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; min-width: 720px; }
        thead { background: #f8faff; border-bottom: 2px solid #e9edf4; }
        thead th { text-align: left; padding: 18px 20px; font-weight: 600; color: #334155; font-size: 12px; text-transform: uppercase; letter-spacing: 0.4px; }
        tbody tr { border-bottom: 1px solid #eef2f7; transition: background 0.1s; }
        tbody tr:hover { background: #f8faff; }
        tbody td { padding: 16px 20px; vertical-align: middle; color: #1e293b; }
        .email-subject { font-weight: 600; color: #0b2b4a; }
        .email-sender { display: flex; align-items: center; gap: 10px; }
        .email-sender .avatar { width: 34px; height: 34px; border-radius: 50%; background: #dbeafe; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #2a7de1; font-size: 14px; flex-shrink: 0; }
        .badge { display: inline-block; padding: 4px 14px; border-radius: 30px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }
        .badge-read { background: #dcfce7; color: #166534; }
        .badge-unread { background: #fee2e2; color: #991b1b; }
        .badge-important { background: #fef3c7; color: #92400e; }
        .badge-draft { background: #e2e8f0; color: #475569; }
        .action-btns { display: flex; gap: 6px; }
        .action-btns button { border: none; background: transparent; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 14px; transition: 0.15s; color: #64748b; }
        .action-btns button:hover { background: #eef2f7; color: #0b2b4a; }
        .action-btns .btn-view:hover { color: #2a7de1; }
        .action-btns .btn-delete:hover { color: #d14c4c; }
        .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
        .empty-state i { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
        .empty-state h3 { font-weight: 500; color: #475569; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.40); backdrop-filter: blur(4px); z-index: 999; justify-content: center; align-items: center; padding: 24px; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: #fff; border-radius: 18px; max-width: 700px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 32px 36px; box-shadow: 0 24px 60px rgba(0,0,0,0.20); animation: modalIn 0.25s ease; }
        @keyframes modalIn { 0% { opacity: 0; transform: scale(0.96) translateY(16px); } 100% { opacity: 1; transform: scale(1) translateY(0); } }
        .modal-box .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .modal-box .modal-header h2 { font-size: 22px; color: #0b2b4a; }
        .modal-box .modal-header .close-btn { background: none; border: none; font-size: 26px; color: #94a3b8; cursor: pointer; transition: 0.2s; padding: 0 6px; }
        .modal-box .modal-header .close-btn:hover { color: #0b2b4a; transform: rotate(90deg); }
        .modal-box .form-group { margin-bottom: 18px; }
        .modal-box .form-group label { display: block; font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 5px; }
        .modal-box .form-group input, .modal-box .form-group select, .modal-box .form-group textarea { width: 100%; padding: 11px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; transition: 0.2s; background: #fff; }
        .modal-box .form-group input:focus, .modal-box .form-group select:focus, .modal-box .form-group textarea:focus { border-color: #2a7de1; box-shadow: 0 0 0 3px rgba(42,125,225,0.12); }
        .modal-box .form-group textarea { resize: vertical; min-height: 80px; }
        .modal-box .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .modal-box .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid #eef2f7; }
        @media (max-width: 700px) { body { padding: 16px; } .page-header h1 { font-size: 22px; } .modal-box { padding: 24px 18px; } .modal-box .form-row { grid-template-columns: 1fr; } .stats-bar .stat-card { flex: 1 1 100%; } .filter-bar { flex-direction: column; align-items: stretch; } .filter-bar .filter-group { justify-content: stretch; } .filter-bar .filter-group select { flex: 1; } .header-actions .btn { padding: 8px 16px; font-size: 13px; } }
        .modal-box::-webkit-scrollbar { width: 6px; }
        .modal-box::-webkit-scrollbar-track { background: #f1f5f9; }
        .modal-box::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>
<div class="app-container">
    <div class="page-header">
        <h1><i class="fas fa-envelope"></i> Email Data</h1>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openComposeModal()"><i class="fas fa-plus"></i> Compose</button>
            <button class="btn btn-outline" onclick="refreshTable()"><i class="fas fa-sync-alt"></i> Refresh</button>
            <button class="btn btn-outline" onclick="exportData()"><i class="fas fa-download"></i> Export</button>
        </div>
    </div>
    <div class="stats-bar">
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-inbox"></i></div><div class="stat-info"><h4>Total Emails</h4><span class="number" id="totalEmails">0</span></div></div>
        <div class="stat-card green"><div class="stat-icon"><i class="fas fa-envelope-open"></i></div><div class="stat-info"><h4>Read</h4><span class="number" id="readCount">0</span></div></div>
        <div class="stat-card orange"><div class="stat-icon"><i class="fas fa-envelope"></i></div><div class="stat-info"><h4>Unread</h4><span class="number" id="unreadCount">0</span></div></div>
        <div class="stat-card purple"><div class="stat-icon"><i class="fas fa-star"></i></div><div class="stat-info"><h4>Important</h4><span class="number" id="importantCount">0</span></div></div>
    </div>
    <div class="filter-bar">
        <div class="search-wrap"><i class="fas fa-search"></i><input type="text" id="searchInput" placeholder="Search by subject, sender, or content..." oninput="applyFilters()" /></div>
        <div class="filter-group">
            <select id="statusFilter" onchange="applyFilters()">
                <option value="all">All Status</option>
                <option value="read">Read</option>
                <option value="unread">Unread</option>
                <option value="important">Important</option>
                <option value="draft">Draft</option>
            </select>
            <select id="sortFilter" onchange="applyFilters()">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="subject">Subject A→Z</option>
            </select>
            <button class="btn btn-outline" onclick="clearFilters()" style="padding:8px 18px;"><i class="fas fa-times"></i> Clear</button>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th style="width:30px;">#</th><th>Sender</th><th>Subject</th><th>Status</th><th>Date</th><th style="text-align:center;">Actions</th></tr></thead>
            <tbody id="emailTableBody"></tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="composeModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-pen-fancy" style="margin-right:10px;color:#2a7de1;"></i> Compose Email</h2>
            <button class="close-btn" onclick="closeComposeModal()">&times;</button>
        </div>
        <form id="emailForm" onsubmit="saveEmail(event)">
            <input type="hidden" id="editId" value="" />
            <div class="form-row">
                <div class="form-group"><label for="senderName">Sender Name *</label><input type="text" id="senderName" placeholder="e.g. John Doe" required /></div>
                <div class="form-group"><label for="senderEmail">Sender Email *</label><input type="email" id="senderEmail" placeholder="john@company.com" required /></div>
            </div>
            <div class="form-group"><label for="emailSubject">Subject *</label><input type="text" id="emailSubject" placeholder="Meeting agenda for Q3" required /></div>
            <div class="form-group"><label for="emailContent">Content</label><textarea id="emailContent" rows="4" placeholder="Write your message here..."></textarea></div>
            <div class="form-row">
                <div class="form-group"><label for="emailStatus">Status</label><select id="emailStatus"><option value="unread">Unread</option><option value="read">Read</option><option value="important">Important</option><option value="draft">Draft</option></select></div>
                <div class="form-group"><label for="emailDate">Date</label><input type="datetime-local" id="emailDate" /></div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeComposeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn"><i class="fas fa-save"></i> Save Email</button>
            </div>
        </form>
    </div>
</div>

<script>
// =============================================================
//  EMAIL DATA – JavaScript (identical to previous version)
// =============================================================

let emails = [];

async function loadEmails() {
    try {
        const res = await fetch('?action=getAll');
        if (!res.ok) throw new Error('Failed to fetch');
        emails = await res.json();
        renderTable();
        updateStats();
    } catch (err) {
        console.error(err);
        alert('Error loading emails. Check server connection.');
    }
}

function renderTable(data) {
    const list = data || emails;
    const tbody = document.getElementById('emailTableBody');
    if (!list || list.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-inbox"></i><h3>No emails found</h3><p style="color:#94a3b8;margin-top:4px;">Compose a new email to get started.</p></div></td></tr>`;
        return;
    }
    let html = '';
    list.forEach((e, idx) => {
        const statusBadge = getStatusBadge(e.status);
        const avatar = e.sender_name ? e.sender_name.charAt(0).toUpperCase() : '?';
        const dateDisplay = e.date ? e.date.replace('T', ' ').slice(0, 16) : '—';
        html += `<tr>
            <td style="font-weight:500;color:#94a3b8;">${idx + 1}</td>
            <td><div class="email-sender"><div class="avatar">${avatar}</div><div><div style="font-weight:500;">${escapeHtml(e.sender_name || 'Unknown')}</div><div style="font-size:12px;color:#94a3b8;">${escapeHtml(e.sender_email || '')}</div></div></div></td>
            <td><div class="email-subject">${escapeHtml(e.subject || '(no subject)')}</div><div style="font-size:12px;color:#94a3b8;max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml((e.content || '').slice(0, 60))}${e.content && e.content.length > 60 ? '…' : ''}</div></td>
            <td>${statusBadge}</td>
            <td style="font-size:13px;color:#475569;">${dateDisplay}</td>
            <td style="text-align:center;"><div class="action-btns" style="justify-content:center;"><button class="btn-view" title="View" onclick="viewEmail(${e.id})"><i class="fas fa-eye"></i></button><button class="btn-view" title="Edit" onclick="editEmail(${e.id})"><i class="fas fa-edit"></i></button><button class="btn-delete" title="Delete" onclick="deleteEmail(${e.id})"><i class="fas fa-trash"></i></button></div></td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function getStatusBadge(status) {
    const map = {
        'read': '<span class="badge badge-read"><i class="fas fa-check-circle" style="margin-right:4px;"></i>Read</span>',
        'unread': '<span class="badge badge-unread"><i class="fas fa-circle" style="margin-right:4px;font-size:8px;"></i>Unread</span>',
        'important': '<span class="badge badge-important"><i class="fas fa-star" style="margin-right:4px;"></i>Important</span>',
        'draft': '<span class="badge badge-draft"><i class="fas fa-pencil-alt" style="margin-right:4px;"></i>Draft</span>'
    };
    return map[status] || `<span class="badge badge-draft">${status}</span>`;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function updateStats() {
    const total = emails.length;
    const read = emails.filter(e => e.status === 'read').length;
    const unread = emails.filter(e => e.status === 'unread').length;
    const important = emails.filter(e => e.status === 'important').length;
    document.getElementById('totalEmails').textContent = total;
    document.getElementById('readCount').textContent = read;
    document.getElementById('unreadCount').textContent = unread;
    document.getElementById('importantCount').textContent = important;
}

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase().trim();
    const status = document.getElementById('statusFilter').value;
    const sort = document.getElementById('sortFilter').value;
    let filtered = [...emails];
    if (search) {
        filtered = filtered.filter(e =>
            (e.subject && e.subject.toLowerCase().includes(search)) ||
            (e.sender_name && e.sender_name.toLowerCase().includes(search)) ||
            (e.sender_email && e.sender_email.toLowerCase().includes(search)) ||
            (e.content && e.content.toLowerCase().includes(search))
        );
    }
    if (status !== 'all') filtered = filtered.filter(e => e.status === status);
    if (sort === 'newest') filtered.sort((a, b) => (a.date || '').localeCompare(b.date || '') * -1);
    else if (sort === 'oldest') filtered.sort((a, b) => (a.date || '').localeCompare(b.date || ''));
    else if (sort === 'subject') filtered.sort((a, b) => (a.subject || '').localeCompare(b.subject || ''));
    renderTable(filtered);
    updateStats();
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = 'all';
    document.getElementById('sortFilter').value = 'newest';
    applyFilters();
}

async function saveEmail(e) {
    e.preventDefault();
    const id = parseInt(document.getElementById('editId').value) || null;
    const senderName = document.getElementById('senderName').value.trim();
    const senderEmail = document.getElementById('senderEmail').value.trim();
    const subject = document.getElementById('emailSubject').value.trim();
    const content = document.getElementById('emailContent').value.trim();
    const status = document.getElementById('emailStatus').value;
    let date = document.getElementById('emailDate').value;
    if (!senderName || !senderEmail || !subject) {
        alert('Please fill in Sender Name, Email, and Subject.');
        return;
    }
    if (!date) date = new Date().toISOString().slice(0, 16);
    const payload = { id, senderName, senderEmail, subject, content, status, date };
    try {
        const res = await fetch('?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
            closeComposeModal();
            loadEmails();
        } else {
            alert('Error: ' + (result.error || 'Unknown error'));
        }
    } catch (err) {
        alert('Server error: ' + err.message);
    }
}

async function deleteEmail(id) {
    if (!confirm('Delete this email permanently?')) return;
    try {
        const res = await fetch('?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const result = await res.json();
        if (result.success) loadEmails();
        else alert('Delete failed: ' + (result.error || ''));
    } catch (err) {
        alert('Server error: ' + err.message);
    }
}

function viewEmail(id) {
    const email = emails.find(e => e.id === id);
    if (!email) return;
    alert(`📧 From: ${email.sender_name} <${email.sender_email}>\n📌 Subject: ${email.subject}\n📅 Date: ${email.date || '—'}\n🏷️ Status: ${email.status}\n\n📝 Content:\n${email.content || '(empty)'}`);
    if (email.status === 'unread') updateStatus(id, 'read');
}

async function updateStatus(id, newStatus) {
    const email = emails.find(e => e.id === id);
    if (!email) return;
    const payload = {
        id: id,
        senderName: email.sender_name,
        senderEmail: email.sender_email,
        subject: email.subject,
        content: email.content,
        status: newStatus,
        date: email.date
    };
    try {
        await fetch('?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        loadEmails();
    } catch (err) {
        console.error(err);
    }
}

function editEmail(id) {
    const email = emails.find(e => e.id === id);
    if (!email) return;
    document.getElementById('editId').value = id;
    document.getElementById('senderName').value = email.sender_name || '';
    document.getElementById('senderEmail').value = email.sender_email || '';
    document.getElementById('emailSubject').value = email.subject || '';
    document.getElementById('emailContent').value = email.content || '';
    document.getElementById('emailStatus').value = email.status || 'unread';
    document.getElementById('emailDate').value = email.date || '';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit" style="margin-right:10px;color:#2a7de1;"></i> Edit Email';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update Email';
    document.getElementById('composeModal').classList.add('active');
}

function openComposeModal() {
    document.getElementById('editId').value = '';
    document.getElementById('senderName').value = '';
    document.getElementById('senderEmail').value = '';
    document.getElementById('emailSubject').value = '';
    document.getElementById('emailContent').value = '';
    document.getElementById('emailStatus').value = 'unread';
    document.getElementById('emailDate').value = new Date().toISOString().slice(0, 16);
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-pen-fancy" style="margin-right:10px;color:#2a7de1;"></i> Compose Email';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Save Email';
    document.getElementById('composeModal').classList.add('active');
}

function closeComposeModal() {
    document.getElementById('composeModal').classList.remove('active');
}

function refreshTable() { loadEmails(); }

function exportData() {
    const dataStr = JSON.stringify(emails, null, 2);
    const blob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `emails_${new Date().toISOString().slice(0,10)}.json`;
    a.click();
    URL.revokeObjectURL(url);
}

document.getElementById('composeModal').addEventListener('click', function(e) {
    if (e.target === this) closeComposeModal();
});

loadEmails();
console.log('✅ StockMaster Pro – Email Data module loaded with shared db_connection.php');
</script>
</body>
</html>