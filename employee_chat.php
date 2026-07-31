<?php
session_start();
require_once 'db_connection.php';
require_once 'chat_model.php';

// Check if user is logged in and is employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Employee') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$chat = new ChatModel();

// Get user info
$user_query = "SELECT id, name, role FROM users WHERE id = ?";
$conn = getConnection();
$stmt = $conn->prepare($user_query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$conversations = $chat->getUserConversations($user_id);
$unread_count = $chat->getUnreadCount($user_id);

// Get all admins for employee to chat with
$admin_query = "SELECT id, name, email_id, role, profile_picture FROM users WHERE role = 'Admin' AND status = 'active' AND id != ? ORDER BY name ASC";
$stmt = $conn->prepare($admin_query);
$stmt->execute([$user_id]);
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Chat - Inventory Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            height: 100vh;
        }
        .chat-container {
            display: flex;
            height: calc(100vh - 40px);
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .chat-sidebar {
            width: 340px;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            background: #f8f9fa;
        }
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-header h2 {
            font-size: 18px;
            color: #333;
        }
        .chat-header h2 i {
            color: #4CAF50;
            margin-right: 8px;
        }
        .unread-badge {
            background: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: bold;
        }
        .new-chat-btn {
            margin: 10px 15px;
            padding: 10px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .new-chat-btn:hover {
            background: #45a049;
        }
        .conversations-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }
        .conversations-list h4 {
            padding: 10px;
            color: #666;
            font-size: 14px;
            border-bottom: 1px solid #e0e0e0;
        }
        .conversations-list h4 i {
            margin-right: 8px;
        }
        .conversation-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 4px;
        }
        .conversation-item:hover {
            background: #e9ecef;
        }
        .conversation-item.active {
            background: #e3f2fd;
        }
        .conversation-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #4CAF50;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .conversation-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .conversation-info {
            flex: 1;
            min-width: 0;
        }
        .conversation-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .conversation-role {
            font-size: 11px;
            color: #999;
        }
        .conversation-last-message {
            color: #666;
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .conversation-meta {
            text-align: right;
            flex-shrink: 0;
        }
        .conversation-time {
            font-size: 11px;
            color: #999;
        }
        .unread-count {
            background: #4CAF50;
            color: white;
            border-radius: 50%;
            padding: 2px 7px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 4px;
        }
        .no-conversations {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        .no-conversations i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }
        .chat-main-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }
        .chat-user-info h3 {
            font-size: 16px;
            color: #333;
        }
        .chat-user-info span {
            font-size: 12px;
            color: #4CAF50;
        }
        .chat-actions button {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background 0.2s;
        }
        .chat-actions button:hover {
            background: #e9ecef;
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #fafafa;
        }
        .no-conversation {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .no-conversation i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ddd;
        }
        .message {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        .message.sent {
            align-items: flex-end;
        }
        .message.received {
            align-items: flex-start;
        }
        .message-text {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            word-wrap: break-word;
            font-size: 14px;
            line-height: 1.4;
        }
        .message.sent .message-text {
            background: #4CAF50;
            color: white;
            border-bottom-right-radius: 4px;
        }
        .message.received .message-text {
            background: white;
            color: #333;
            border-bottom-left-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .message-time {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
            padding: 0 5px;
        }
        .message.sent .message-time {
            color: #999;
        }
        .chat-input-area {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            background: white;
        }
        .chat-input-wrapper {
            display: flex;
            gap: 10px;
        }
        .chat-input-wrapper textarea {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: none;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .chat-input-wrapper textarea:focus {
            outline: none;
            border-color: #4CAF50;
        }
        .chat-input-wrapper button {
            padding: 10px 24px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .chat-input-wrapper button:hover:not(:disabled) {
            background: #45a049;
        }
        .chat-input-wrapper button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 450px;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            color: #333;
        }
        .modal-header button {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        .modal-header button:hover {
            color: #333;
        }
        .modal-body {
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
        }
        .modal-body input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .modal-body input[type="text"]:focus {
            outline: none;
            border-color: #4CAF50;
        }
        .admin-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 4px;
        }
        .admin-item:hover {
            background: #e9ecef;
        }
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #2196F3;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .admin-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .admin-info {
            flex: 1;
        }
        .admin-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .admin-email {
            font-size: 12px;
            color: #999;
        }
        .admin-role-badge {
            background: #2196F3;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 10px;
            }
            .chat-sidebar {
                width: 100%;
                max-height: 200px;
            }
            .chat-container {
                flex-direction: column;
                height: calc(100vh - 20px);
            }
            .modal-content {
                width: 95%;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar1.php'; ?>
    
    <div class="main-content">
        <div class="chat-container">
            <!-- Sidebar -->
            <div class="chat-sidebar">
                <div class="chat-header">
                    <h2><i class="fas fa-comments"></i> Messages</h2>
                    <?php if($unread_count > 0): ?>
                        <span class="unread-badge" id="unreadBadge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- New Chat Button -->
                <button class="new-chat-btn" onclick="showNewChatModal()">
                    <i class="fas fa-plus"></i> New Chat with Admin
                </button>
                
                <!-- Conversations -->
                <div class="conversations-list" id="conversationsList">
                    <h4><i class="fas fa-history"></i> Conversations</h4>
                    <?php if(count($conversations) > 0): ?>
                        <?php foreach($conversations as $conv): ?>
                            <div class="conversation-item" onclick="loadConversation(<?php echo $conv['conversation_id']; ?>, <?php echo $conv['other_user_id']; ?>, '<?php echo addslashes($conv['other_user_name']); ?>')">
                                <div class="conversation-avatar">
                                    <?php if(isset($conv['profile_picture']) && $conv['profile_picture']): ?>
                                        <img src="<?php echo $conv['profile_picture']; ?>" alt="<?php echo $conv['other_user_name']; ?>">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($conv['other_user_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="conversation-info">
                                    <div class="conversation-name"><?php echo htmlspecialchars($conv['other_user_name']); ?></div>
                                    <div class="conversation-role"><?php echo htmlspecialchars($conv['other_user_role'] ?? 'Admin'); ?></div>
                                    <div class="conversation-last-message">
                                        <?php echo htmlspecialchars($conv['last_message'] ? substr($conv['last_message'], 0, 30) . '...' : 'No messages yet'); ?>
                                    </div>
                                </div>
                                <div class="conversation-meta">
                                    <div class="conversation-time">
                                        <?php echo $conv['last_message_time'] ? date('h:i A', strtotime($conv['last_message_time'])) : ''; ?>
                                    </div>
                                    <?php if($conv['unread_count'] > 0): ?>
                                        <div class="unread-count"><?php echo $conv['unread_count']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-conversations">
                            <i class="fas fa-comment-slash"></i>
                            <p>No conversations yet</p>
                            <p style="font-size: 12px; margin-top: 5px;">Click "New Chat with Admin" to start</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Chat Main Area -->
            <div class="chat-main">
                <div class="chat-main-header" id="chatMainHeader">
                    <div class="chat-user-info">
                        <h3 id="chatUserName">Select a conversation</h3>
                        <span id="chatUserStatus"></span>
                    </div>
                    <div class="chat-actions">
                        <button onclick="refreshMessages()" title="Refresh">
                            <i class="fas fa-sync"></i>
                        </button>
                    </div>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <div class="no-conversation">
                        <i class="fas fa-comment-dots"></i>
                        <p>Select a conversation to start chatting</p>
                    </div>
                </div>
                
                <div class="chat-input-area" id="chatInputArea" style="display: none;">
                    <div class="chat-input-wrapper">
                        <textarea id="messageInput" rows="3" placeholder="Type your message here..." onkeydown="handleKeyPress(event)"></textarea>
                        <button onclick="sendMessage()" id="sendButton">
                            <i class="fas fa-paper-plane"></i> Send
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Chat Modal -->
    <div class="modal" id="newChatModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-tie"></i> Start New Chat with Admin</h3>
                <button onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="text" id="adminSearch" placeholder="Search admins..." onkeyup="filterAdmins()">
                <div id="adminList">
                    <?php if(count($admins) > 0): ?>
                        <?php foreach($admins as $admin): ?>
                            <div class="admin-item" onclick="startChat(<?php echo $admin['id']; ?>, '<?php echo addslashes($admin['name']); ?>')">
                                <div class="admin-avatar">
                                    <?php if(isset($admin['profile_picture']) && $admin['profile_picture']): ?>
                                        <img src="<?php echo $admin['profile_picture']; ?>" alt="<?php echo $admin['name']; ?>">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($admin['name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="admin-info">
                                    <div class="admin-name"><?php echo htmlspecialchars($admin['name']); ?></div>
                                    <div class="admin-email"><?php echo htmlspecialchars($admin['email_id'] ?? ''); ?></div>
                                </div>
                                <span class="admin-role-badge">Admin</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; color: #999;">
                            <i class="fas fa-user-slash" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <p>No admins available to chat</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentConversationId = null;
        let currentReceiverId = null;
        let currentReceiverName = '';
        let currentUserId = <?php echo $user_id; ?>;
        
        // Load conversation
        function loadConversation(conversationId, receiverId, receiverName) {
            currentConversationId = conversationId;
            currentReceiverId = receiverId;
            currentReceiverName = receiverName;
            
            document.getElementById('chatUserName').textContent = receiverName;
            document.getElementById('chatUserStatus').textContent = 'Online';
            document.getElementById('chatInputArea').style.display = 'block';
            
            loadMessages(conversationId);
            
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            
            document.querySelectorAll('.conversation-item').forEach(item => {
                if (item.onclick && item.onclick.toString().includes(conversationId)) {
                    item.classList.add('active');
                }
            });
        }
        
        // Load messages
        function loadMessages(conversationId, append = false) {
            fetch(`chat_api.php?action=get_messages&conversation_id=${conversationId}&limit=50`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderMessages(data.messages, append);
                        markAsRead(conversationId);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Render messages
        function renderMessages(messages, append = false) {
            const container = document.getElementById('chatMessages');
            
            if (!append) {
                container.innerHTML = '';
            }
            
            if (messages.length === 0) {
                container.innerHTML = `
                    <div class="no-conversation">
                        <i class="fas fa-comment-dots"></i>
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                `;
                return;
            }
            
            const noConv = container.querySelector('.no-conversation');
            if (noConv) {
                container.innerHTML = '';
            }
            
            messages.forEach(msg => {
                const isSent = msg.sender_id == currentUserId;
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${isSent ? 'sent' : 'received'}`;
                messageDiv.innerHTML = `
                    <div class="message-text">${escapeHtml(msg.message)}</div>
                    <div class="message-time">
                        ${formatTime(msg.created_at)}
                        ${isSent && msg.is_read ? ' <i class="fas fa-check-double" style="color: #4CAF50;"></i>' : ''}
                        ${isSent && !msg.is_read ? ' <i class="fas fa-check"></i>' : ''}
                    </div>
                `;
                container.appendChild(messageDiv);
            });
            
            container.scrollTop = container.scrollHeight;
        }
        
        // Send message
        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message) {
                return;
            }
            
            if (!currentConversationId || !currentReceiverId) {
                alert('Please select a conversation first');
                return;
            }
            
            const sendBtn = document.getElementById('sendButton');
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('conversation_id', currentConversationId);
            formData.append('receiver_id', currentReceiverId);
            formData.append('message', message);
            
            fetch('chat_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    appendMessage(message, new Date().toISOString());
                    updateConversations();
                } else {
                    alert('Failed to send message: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to send message. Please try again.');
            })
            .finally(() => {
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
            });
        }
        
        // Append message
        function appendMessage(message, timestamp) {
            const container = document.getElementById('chatMessages');
            const noConv = container.querySelector('.no-conversation');
            if (noConv) {
                container.innerHTML = '';
            }
            
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message sent';
            messageDiv.innerHTML = `
                <div class="message-text">${escapeHtml(message)}</div>
                <div class="message-time">
                    ${formatTime(timestamp)}
                    <i class="fas fa-check"></i>
                </div>
            `;
            container.appendChild(messageDiv);
            container.scrollTop = container.scrollHeight;
        }
        
        // Mark as read
        function markAsRead(conversationId) {
            const formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('conversation_id', conversationId);
            
            fetch('chat_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateUnreadCount();
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Update conversations
        function updateConversations() {
            fetch('chat_api.php?action=get_conversations')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('conversationsList');
                        const header = container.querySelector('h4');
                        container.innerHTML = '';
                        container.appendChild(header);
                        
                        if (data.conversations.length > 0) {
                            data.conversations.forEach(conv => {
                                const convDiv = document.createElement('div');
                                convDiv.className = 'conversation-item';
                                if (conv.conversation_id == currentConversationId) {
                                    convDiv.classList.add('active');
                                }
                                convDiv.onclick = function() {
                                    loadConversation(conv.conversation_id, conv.other_user_id, conv.other_user_name);
                                };
                                convDiv.innerHTML = `
                                    <div class="conversation-avatar">
                                        ${conv.other_user_name.charAt(0).toUpperCase()}
                                    </div>
                                    <div class="conversation-info">
                                        <div class="conversation-name">${escapeHtml(conv.other_user_name)}</div>
                                        <div class="conversation-role">${escapeHtml(conv.other_user_role || 'Admin')}</div>
                                        <div class="conversation-last-message">${escapeHtml(conv.last_message ? conv.last_message.substring(0, 30) + '...' : 'No messages yet')}</div>
                                    </div>
                                    <div class="conversation-meta">
                                        <div class="conversation-time">${conv.last_message_time ? formatTime(conv.last_message_time) : ''}</div>
                                        ${conv.unread_count > 0 ? `<div class="unread-count">${conv.unread_count}</div>` : ''}
                                    </div>
                                `;
                                container.appendChild(convDiv);
                            });
                        } else {
                            container.innerHTML = `
                                <h4><i class="fas fa-history"></i> Conversations</h4>
                                <div class="no-conversations">
                                    <i class="fas fa-comment-slash"></i>
                                    <p>No conversations yet</p>
                                    <p style="font-size: 12px; margin-top: 5px;">Click "New Chat with Admin" to start</p>
                                </div>
                            `;
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Update unread count
        function updateUnreadCount() {
            fetch('chat_api.php?action=get_unread_count')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.getElementById('unreadBadge');
                        if (data.unread_count > 0) {
                            if (badge) {
                                badge.textContent = data.unread_count;
                                badge.style.display = 'inline';
                            }
                        } else if (badge) {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Show new chat modal
        function showNewChatModal() {
            document.getElementById('newChatModal').style.display = 'flex';
            document.getElementById('adminSearch').value = '';
            filterAdmins();
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('newChatModal').style.display = 'none';
        }
        
        // Filter admins
        function filterAdmins() {
            const search = document.getElementById('adminSearch').value.toLowerCase();
            const items = document.querySelectorAll('.admin-item');
            
            items.forEach(item => {
                const name = item.querySelector('.admin-name').textContent.toLowerCase();
                const email = item.querySelector('.admin-email').textContent.toLowerCase();
                if (name.includes(search) || email.includes(search)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        // Start chat with admin
        function startChat(adminId, adminName) {
            // Check if conversation already exists
            fetch(`chat_api.php?action=get_conversations`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let existingConv = data.conversations.find(
                            conv => conv.other_user_id == adminId
                        );
                        
                        if (existingConv) {
                            loadConversation(existingConv.conversation_id, adminId, adminName);
                        } else {
                            // Create new conversation
                            const formData = new FormData();
                            formData.append('action', 'send_message');
                            formData.append('receiver_id', adminId);
                            formData.append('message', 'Hello! I would like to chat.');
                            
                            fetch('chat_api.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    loadConversation(data.conversation_id, adminId, adminName);
                                    closeModal();
                                } else {
                                    alert('Failed to start chat: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Failed to start chat. Please try again.');
                            });
                        }
                        closeModal();
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Refresh messages
        function refreshMessages() {
            if (currentConversationId) {
                loadMessages(currentConversationId);
            }
        }
        
        // Handle key press
        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }
        
        // Utility functions
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatTime(timestamp) {
            if (!timestamp) return '';
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) return 'Just now';
            if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
            if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
            
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('newChatModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        // Start polling
        setInterval(() => {
            if (currentConversationId) {
                loadMessages(currentConversationId, true);
                updateUnreadCount();
            }
            updateConversations();
        }, 5000);
    </script>
</body>
</html>