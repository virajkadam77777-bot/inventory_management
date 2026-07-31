<?php
// ==============================================
// Employee Portal Page
// File: employee_portal.php
// ==============================================

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information from session
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : (isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'John Anderson');
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Senior Developer';
$user_id = $_SESSION['user_id'];

// Handle logout if requested
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    // Destroy all session data
    session_unset();
    session_destroy();
    
    // Redirect to login page
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal - StockMaster Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }

        /* Header */
        .top-header {
            background: white;
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }

        .header-title h1 {
            font-size: 24px;
            color: #0f172a;
            font-weight: 700;
        }

        .header-title p {
            color: #64748b;
            font-size: 13px;
            margin-top: 5px;
        }

        .header-actions {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .notification-icon {
            position: relative;
            cursor: pointer;
        }

        .notification-icon i {
            font-size: 22px;
            color: #64748b;
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 20px;
        }

        /* ========== DASHBOARD SECTION ========== */
        .section {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-info h3 {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .stat-info .number {
            font-size: 32px;
            font-weight: 800;
            color: #0f172a;
        }

        .stat-icon {
            width: 55px;
            height: 55px;
            background: rgba(45, 212, 191, 0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 28px;
            color: #2dd4bf;
        }

        /* Quick Actions */
        .quick-actions {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .quick-actions h3 {
            font-size: 18px;
            color: #0f172a;
            margin-bottom: 20px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            border: 1px solid #e2e8f0;
        }

        .action-btn:hover {
            background: #2dd4bf;
        }

        .action-btn:hover span,
        .action-btn:hover i {
            color: white;
        }

        .action-btn i {
            font-size: 22px;
            color: #2dd4bf;
        }

        .action-btn span {
            font-size: 14px;
            font-weight: 500;
            color: #0f172a;
        }

        /* Recent Activity */
        .recent-activity {
            background: white;
            border-radius: 20px;
            padding: 25px;
            border: 1px solid #e2e8f0;
        }

        .recent-activity h3 {
            font-size: 18px;
            color: #0f172a;
            margin-bottom: 20px;
        }

        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-date {
            color: #64748b;
            font-size: 13px;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-present {
            background: #dcfce7;
            color: #166534;
        }

        .status-absent {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ========== ATTENDANCE SECTION ========== */
        .attendance-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .time-display {
            font-size: 52px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
            font-family: monospace;
        }

        .date-display {
            font-size: 18px;
            color: #64748b;
            margin-bottom: 30px;
        }

        .check-btn {
            background: linear-gradient(135deg, #2dd4bf, #14b8a6);
            color: white;
            border: none;
            padding: 14px 35px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .check-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(45, 212, 191, 0.3);
        }

        .attendance-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .summary-card h4 {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 10px;
        }

        .summary-card .number {
            font-size: 32px;
            font-weight: 800;
            color: #0f172a;
        }

        .attendance-history {
            background: white;
            border-radius: 20px;
            padding: 25px;
            border: 1px solid #e2e8f0;
        }

        .attendance-history h3 {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .history-table {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }

        th {
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }

        /* ========== LEAVE SECTION ========== */
        .leave-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .leave-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .leave-card h3 {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 10px;
        }

        .leave-card .number {
            font-size: 36px;
            font-weight: 800;
            color: #2dd4bf;
        }

        .apply-leave-form {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #334155;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #2dd4bf;
        }

        .submit-btn {
            background: linear-gradient(135deg, #2dd4bf, #14b8a6);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .leave-history {
            background: white;
            border-radius: 20px;
            padding: 25px;
            border: 1px solid #e2e8f0;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background: #dcfce7;
            color: #166534;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ========== CHAT SECTION ========== */
        .chat-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 25px;
            height: calc(100vh - 200px);
        }

        .chat-sidebar {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .chat-search {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .chat-search input {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .chat-users {
            list-style: none;
        }

        .chat-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 1px solid #f1f5f9;
        }

        .chat-user:hover {
            background: #f8fafc;
        }

        .chat-user.active {
            background: rgba(45, 212, 191, 0.1);
        }

        .user-avatar-small {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #2dd4bf, #14b8a6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .user-info h4 {
            font-size: 14px;
            color: #0f172a;
        }

        .user-info p {
            font-size: 11px;
            color: #64748b;
        }

        .chat-main {
            background: white;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            min-height: 400px;
            max-height: 500px;
        }

        .message {
            margin-bottom: 20px;
            display: flex;
        }

        .message.sent {
            justify-content: flex-end;
        }

        .message-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 14px;
        }

        .message.received .message-bubble {
            background: #f1f5f9;
            color: #0f172a;
        }

        .message.sent .message-bubble {
            background: #2dd4bf;
            color: white;
        }

        .message-time {
            font-size: 10px;
            color: #64748b;
            margin-top: 5px;
        }

        .chat-input-area {
            padding: 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 15px;
        }

        .chat-input-area input {
            flex: 1;
            padding: 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
        }

        .send-btn {
            background: #2dd4bf;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            color: white;
            cursor: pointer;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 2000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success {
            border-left: 4px solid #22c55e;
        }

        .toast.error {
            border-left: 4px solid #ef4444;
        }

        /* Confirmation Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 3000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .modal h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #0f172a;
        }

        .modal p {
            color: #64748b;
            margin-bottom: 25px;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .modal-btn {
            padding: 10px 25px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        .modal-btn.confirm {
            background: #ef4444;
            color: white;
        }

        .modal-btn.cancel {
            background: #e2e8f0;
            color: #334155;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid, .actions-grid, .leave-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            .chat-container {
                grid-template-columns: 1fr;
                height: auto;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .stats-grid, .actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Include Sidebar from external file -->
    <?php include 'sidebar1.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-header">
            <div class="header-title">
                <h1 id="pageTitle">Employee Dashboard</h1>
                <p id="pageSubtitle">Welcome back, <?php echo htmlspecialchars($user_name); ?>! Here's your work summary</p>
            </div>
            <div class="header-actions">
                <div class="notification-icon" onclick="showToast('You have 3 new notifications', 'success')">
                    <i class="far fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
            </div>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard" class="section active">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Present Days</h3>
                        <div class="number">18</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Absent Days</h3>
                        <div class="number">2</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-calendar-times"></i></div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Pending Leaves</h3>
                        <div class="number">1</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Remaining Leaves</h3>
                        <div class="number">12</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-umbrella-beach"></i></div>
                </div>
            </div>

            <div class="quick-actions">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="actions-grid">
                    <div class="action-btn" onclick="showSection('attendance')">
                        <i class="fas fa-fingerprint"></i>
                        <span>Mark Attendance</span>
                    </div>
                    <div class="action-btn" onclick="showSection('leave')">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Apply Leave</span>
                    </div>
                    <div class="action-btn" onclick="showSection('chat')">
                        <i class="fas fa-comment-dots"></i>
                        <span>Send Message</span>
                    </div>
                    <div class="action-btn" onclick="showToast('Profile update feature coming soon!', 'success')">
                        <i class="fas fa-user-edit"></i>
                        <span>Update Profile</span>
                    </div>
                </div>
            </div>

            <div class="recent-activity">
                <h3><i class="fas fa-history"></i> Recent Activity</h3>
                <div class="activity-item">
                    <span class="activity-date">Monday, Dec 11, 2024</span>
                    <span class="status-badge status-present">Present</span>
                </div>
                <div class="activity-item">
                    <span class="activity-date">Tuesday, Dec 10, 2024</span>
                    <span class="status-badge status-present">Present</span>
                </div>
                <div class="activity-item">
                    <span class="activity-date">Monday, Dec 9, 2024</span>
                    <span class="status-badge status-absent">Absent</span>
                </div>
                <div class="activity-item">
                    <span class="activity-date">Friday, Dec 8, 2024</span>
                    <span class="status-badge status-present">Present</span>
                </div>
            </div>
        </div>

        <!-- Attendance Section -->
        <div id="attendance" class="section">
            <div class="attendance-card">
                <div class="time-display" id="currentTime">--:--:--</div>
                <div class="date-display" id="currentDate"></div>
                <button class="check-btn" id="checkBtn" onclick="markAttendance()">
                    <i class="fas fa-sign-in-alt"></i> Check In
                </button>
            </div>

            <div class="attendance-summary">
                <div class="summary-card">
                    <h4>Present Days</h4>
                    <div class="number">18</div>
                </div>
                <div class="summary-card">
                    <h4>Absent Days</h4>
                    <div class="number">2</div>
                </div>
                <div class="summary-card">
                    <h4>Late Arrivals</h4>
                    <div class="number">1</div>
                </div>
            </div>

            <div class="attendance-history">
                <h3><i class="fas fa-history"></i> Attendance History</h3>
                <div class="history-table">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Day</th><th>Check In</th><th>Check Out</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Dec 11, 2024</td><td>Monday</span></td><td>09:15 AM</span></td><td>06:30 PM</span><td><span class="status-badge status-present">Present</span></span></td>
                            <tr>
                            <tr><td>Dec 10, 2024</span><td>Tuesday</span><td>09:00 AM</span><td>06:45 PM</span><td><span class="status-badge status-present">Present</span></span></span>
                            </tr>
                            <tr><td>Dec 9, 2024</span><td>Monday</span><td>-</span><td>-</span><td><span class="status-badge status-absent">Absent</span></span></span>
                            </tr>
                            <tr><td>Dec 8, 2024</span><td>Friday</span><td>09:20 AM</span><td>06:15 PM</span><td><span class="status-badge status-present">Present</span></span></span>
                            </tr>
                            <tr><td>Dec 7, 2024</span><td>Thursday</span><td>09:10 AM</span><td>06:30 PM</span><td><span class="status-badge status-present">Present</span></span></span>
                            </tr>
                        </tbody>
                    20
                </div>
            </div>
        </div>

        <!-- Leave Section -->
        <div id="leave" class="section">
            <div class="leave-stats">
                <div class="leave-card"><h3>Total Leaves</h3><div class="number">15</div></div>
                <div class="leave-card"><h3>Leaves Taken</h3><div class="number">3</div></div>
                <div class="leave-card"><h3>Remaining Leaves</h3><div class="number">12</div></div>
            </div>

            <div class="apply-leave-form">
                <h3 style="margin-bottom: 20px;"><i class="fas fa-paper-plane"></i> Apply for Leave</h3>
                <form id="leaveForm" onsubmit="return false;">
                    <div class="form-group"><label>Leave Type</label><select id="leaveType"><option>Sick Leave</option><option>Casual Leave</option><option>Earned Leave</option><option>Unpaid Leave</option></select></div>
                    <div class="form-group"><label>From Date</label><input type="date" id="fromDate"></div>
                    <div class="form-group"><label>To Date</label><input type="date" id="toDate"></div>
                    <div class="form-group"><label>Reason</label><textarea rows="3" id="reason" placeholder="Please provide reason for leave..."></textarea></div>
                    <button type="button" class="submit-btn" onclick="applyLeave()">Submit Leave Request</button>
                </form>
            </div>

            <div class="leave-history">
                <h3><i class="fas fa-history"></i> Leave History</h3>
                <div class="history-table">
                    <table>
                        <thead><tr><th>Leave Type</th><th>From Date</th><th>To Date</th><th>Days</th><th>Status</th></tr></thead>
                        <tbody id="leaveHistoryBody">
                            <tr><td>Sick Leave</span><td>Dec 1, 2024</span><td>Dec 3, 2024</span><td>3</span><td><span class="status-badge status-approved">Approved</span></span></td>
                            <tr><td>Casual Leave</span><td>Nov 15, 2024</span><td>Nov 16, 2024</span><td>2</span><td><span class="status-badge status-approved">Approved</span></span></span>
                            <tr>
                            <tr><td>Earned Leave</span><td>Dec 20, 2024</span><td>Dec 25, 2024</span><td>6</span><td><span class="status-badge status-pending">Pending</span></span></span>
                            </tr>
                        </tbody>
                    20
                </div>
            </div>
        </div>

        <!-- Chat Section -->
        <div id="chat" class="section">
            <div class="chat-container">
                <div class="chat-sidebar">
                    <div class="chat-search"><input type="text" id="chatSearch" placeholder="Search conversations..."></div>
                    <ul class="chat-users" id="chatUsersList">
                        <li class="chat-user active" data-user="Sarah Johnson" data-role="HR Manager"><div class="user-avatar-small"><i class="fas fa-user"></i></div><div class="user-info"><h4>Sarah Johnson</h4><p>HR Manager</p></div></li>
                        <li class="chat-user" data-user="Michael Chen" data-role="Project Lead"><div class="user-avatar-small"><i class="fas fa-user"></i></div><div class="user-info"><h4>Michael Chen</h4><p>Project Lead</p></div></li>
                        <li class="chat-user" data-user="Emily Davis" data-role="Team Member"><div class="user-avatar-small"><i class="fas fa-user"></i></div><div class="user-info"><h4>Emily Davis</h4><p>Team Member</p></div></li>
                        <li class="chat-user" data-user="David Wilson" data-role="IT Support"><div class="user-avatar-small"><i class="fas fa-user"></i></div><div class="user-info"><h4>David Wilson</h4><p>IT Support</p></div></li>
                    </ul>
                </div>
                <div class="chat-main">
                    <div class="chat-header"><div class="user-avatar-small"><i class="fas fa-user"></i></div><div class="user-info"><h4 id="chatUserName">Sarah Johnson</h4><p id="chatUserRole">HR Manager</p></div></div>
                    <div class="chat-messages" id="chatMessages">
                        <div class="message received"><div class="message-bubble">Hi <?php echo htmlspecialchars($user_name); ?>! Hope you're doing well.</div></div>
                        <div class="message sent"><div class="message-bubble">Hello Sarah! Yes, I'm doing great. How can I help you?</div></div>
                        <div class="message received"><div class="message-bubble">Just wanted to remind you about the team meeting tomorrow at 10 AM.</div></div>
                        <div class="message sent"><div class="message-bubble">Thanks for the reminder! I'll be there.</div></div>
                    </div>
                    <div class="chat-input-area"><input type="text" id="messageInput" placeholder="Type your message..."><button class="send-btn" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal">
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to logout?</p>
            <div class="modal-buttons">
                <button class="modal-btn cancel" onclick="closeLogoutModal()">Cancel</button>
                <button class="modal-btn confirm" onclick="confirmLogout()">Logout</button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast" class="toast"><i class="fas" id="toastIcon"></i><span id="toastMessage"></span></div>

    <script>
        // ========== LOGOUT FUNCTIONALITY ==========
        function showLogoutModal() {
            document.getElementById('logoutModal').classList.add('active');
        }
        
        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.remove('active');
        }
        
        function confirmLogout() {
            // Redirect to same page with logout parameter
            window.location.href = 'employee_portal.php?logout=1';
        }
        
        // Close modal when clicking outside
        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLogoutModal();
            }
        });
        
        // ========== SECTION NAVIGATION ==========
        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(section => section.classList.remove('active'));
            document.getElementById(sectionId).classList.add('active');
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            document.querySelector(`.nav-link[data-section="${sectionId}"]`).classList.add('active');
            
            const titles = {
                'dashboard': { title: 'Employee Dashboard', subtitle: 'Welcome back, <?php echo htmlspecialchars($user_name); ?>! Here\'s your work summary' },
                'attendance': { title: 'Attendance Management', subtitle: 'Mark your daily attendance and view history' },
                'leave': { title: 'Leave Management', subtitle: 'Apply for leave and track your requests' },
                'chat': { title: 'Team Chat', subtitle: 'Communicate with your team members' }
            };
            if (titles[sectionId]) {
                document.getElementById('pageTitle').textContent = titles[sectionId].title;
                document.getElementById('pageSubtitle').textContent = titles[sectionId].subtitle;
            }
        }
        
        // ========== ATTENDANCE FUNCTIONS ==========
        let isCheckedIn = false;
        
        function updateTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
        
        function markAttendance() {
            const checkBtn = document.getElementById('checkBtn');
            if (!isCheckedIn) {
                isCheckedIn = true;
                checkBtn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Check Out';
                checkBtn.style.background = 'linear-gradient(135deg, #f59e0b, #d97706)';
                showToast('Checked in successfully at ' + new Date().toLocaleTimeString(), 'success');
            } else {
                isCheckedIn = false;
                checkBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Check In';
                checkBtn.style.background = 'linear-gradient(135deg, #2dd4bf, #14b8a6)';
                showToast('Checked out successfully at ' + new Date().toLocaleTimeString(), 'success');
            }
        }
        
        // ========== LEAVE FUNCTIONS ==========
        function applyLeave() {
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;
            const reason = document.getElementById('reason').value;
            
            if (!fromDate || !toDate) { showToast('Please select both from and to dates', 'error'); return; }
            if (!reason) { showToast('Please provide a reason for leave', 'error'); return; }
            
            const leaveType = document.getElementById('leaveType').value;
            const start = new Date(fromDate), end = new Date(toDate);
            const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
            const formattedFrom = start.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const formattedTo = end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            
            const newRow = document.createElement('tr');
            newRow.innerHTML = `<td>${leaveType}</span><td>${formattedFrom}</span><td>${formattedTo}</span><td>${days}</span><td><span class="status-badge status-pending">Pending</span></span>`;
            document.getElementById('leaveHistoryBody').insertBefore(newRow, document.getElementById('leaveHistoryBody').firstChild);
            
            document.getElementById('fromDate').value = '';
            document.getElementById('toDate').value = '';
            document.getElementById('reason').value = '';
            showToast('Leave request submitted successfully!', 'success');
        }
        
        // ========== CHAT FUNCTIONS ==========
        function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            if (message === '') { showToast('Please enter a message', 'error'); return; }
            
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message sent';
            messageDiv.innerHTML = `<div class="message-bubble">${escapeHtml(message)}<div class="message-time">${new Date().toLocaleTimeString()}</div></div>`;
            chatMessages.appendChild(messageDiv);
            
            setTimeout(() => {
                const replyDiv = document.createElement('div');
                replyDiv.className = 'message received';
                replyDiv.innerHTML = `<div class="message-bubble">Thanks for your message! I'll get back to you soon.<div class="message-time">${new Date().toLocaleTimeString()}</div></div>`;
                chatMessages.appendChild(replyDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }, 1000);
            
            messageInput.value = '';
            chatMessages.scrollTop = chatMessages.scrollHeight;
            showToast('Message sent successfully', 'success');
        }
        
        function escapeHtml(text) { const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
        
        // Chat user selection
        document.querySelectorAll('.chat-user').forEach(user => {
            user.addEventListener('click', function() {
                document.querySelectorAll('.chat-user').forEach(u => u.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('chatUserName').textContent = this.getAttribute('data-user');
                document.getElementById('chatUserRole').textContent = this.getAttribute('data-role');
                document.getElementById('chatMessages').innerHTML = '<div class="message received"><div class="message-bubble">Hello! How can I help you today?</div></div>';
                showToast(`Now chatting with ${this.getAttribute('data-user')}`, 'success');
            });
        });
        
        // Chat search
        document.getElementById('chatSearch')?.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('.chat-user').forEach(user => {
                user.style.display = user.getAttribute('data-user').toLowerCase().includes(term) ? 'flex' : 'none';
            });
        });
        
        // ========== TOAST NOTIFICATION ==========
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            document.getElementById('toastIcon').className = 'fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle');
            document.getElementById('toastMessage').textContent = message;
            toast.className = `toast ${type} show`;
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
        
        // ========== MOBILE SIDEBAR TOGGLE ==========
        const mobileToggle = document.getElementById('mobileToggle');
        const sidebar = document.getElementById('employeeSidebar');
        
        function checkMobile() {
            if (window.innerWidth <= 768) {
                if (mobileToggle) mobileToggle.style.display = 'flex';
                if (sidebar) sidebar.classList.remove('open');
            } else {
                if (mobileToggle) mobileToggle.style.display = 'none';
                if (sidebar) sidebar.classList.remove('open');
            }
        }
        
        if (mobileToggle) {
            mobileToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
        }
        
        // ========== SIDEBAR NAVIGATION ==========
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                showSection(this.getAttribute('data-section'));
                if (window.innerWidth <= 768 && sidebar) sidebar.classList.remove('open');
            });
        });
        
        // ========== INITIALIZATION ==========
        updateTime();
        setInterval(updateTime, 1000);
        window.addEventListener('resize', checkMobile);
        checkMobile();
        
        document.getElementById('messageInput')?.addEventListener('keypress', function(e) { if (e.key === 'Enter') sendMessage(); });
        
        console.log('Employee Portal Loaded Successfully!');
    </script>
</body>   
</html>