<?php
session_start();
require_once 'db_connection.php';
require_once 'chat_model.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header('Location: login.php');
    exit();
}

$admin_id = $_SESSION['user_id'];
$chat = new ChatModel();

// Get user info
$user_query = "SELECT id, name, role FROM users WHERE id = ?";
$conn = getConnection();
$stmt = $conn->prepare($user_query);
$stmt->execute([$admin_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all employees for starting new chat
$employees = $chat->getAllEmployees($admin_id);
$conversations = $chat->getUserConversations($admin_id);
$unread_count = $chat->getUnreadCount($admin_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chat - Inventory Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* All existing styles remain unchanged */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        .main-content { margin-left: 250px; padding: 20px; height: 100vh; }
        .chat-container { display: flex; height: calc(100vh - 40px); background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .chat-sidebar { width: 340px; border-right: 1px solid #e0e0e0; display: flex; flex-direction: column; background: #f8f9fa; }
        .chat-header { padding: 20px; border-bottom: 1px solid #e0e0e0; background: white; display: flex; justify-content: space-between; align-items: center; }
        .chat-header h2 { font-size: 18px; color: #333; }
        .chat-header h2 i { color: #2196F3; margin-right: 8px; }
        .unread-badge { background: #ff4444; color: white; border-radius: 50%; padding: 2px 8px; font-size: 12px; font-weight: bold; }
        .new-chat-btn { margin: 10px 15px; padding: 10px; background: #2196F3; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: background 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .new-chat-btn:hover { background: #1976D2; }
        .conversations-list { flex: 1; overflow-y: auto; padding: 10px; }
        .conversations-list h4 { padding: 10px; color: #666; font-size: 14px; border-bottom: 1px solid #e0e0e0; }
        .conversations-list h4 i { margin-right: 8px; }
        .conversation-item { display: flex; align-items: center; padding: 12px; border-radius: 8px; cursor: pointer; transition: background 0.2s; margin-bottom: 4px; }
        .conversation-item:hover { background: #e9ecef; }
        .conversation-item.active { background: #e3f2fd; }
        .conversation-avatar { width: 45px; height: 45px; border-radius: 50%; background: #2196F3; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; margin-right: 12px; flex-shrink: 0; }
        .conversation-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .conversation-info { flex: 1; min-width: 0; }
        .conversation-name { font-weight: 600; color: #333; font-size: 14px; }
        .conversation-role { font-size: 11px; color: #999; }
        .conversation-last-message { color: #666; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .conversation-meta { text-align: right; flex-shrink: 0; }
        .conversation-time { font-size: 11px; color: #999; }
        .unread-count { background: #2196F3; color: white; border-radius: 50%; padding: 2px 7px; font-size: 11px; font-weight: bold; margin-top: 4px; }
        .no-conversations { text-align: center; padding: 40px 20px; color: #999; }
        .no-conversations i { font-size: 48px; margin-bottom: 15px; }
        .chat-main { flex: 1; display: flex; flex-direction: column; background: white; }
        .chat-main-header { padding: 20px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; }
        .chat-user-info h3 { font-size: 16px; color: #333; }
        .chat-user-info span { font-size: 12px; color: #4CAF50; }
        .chat-actions button { background: none; border: none; color: #666; cursor: pointer; padding: 8px; border-radius: 50%; transition: background 0.2s; }
        .chat-actions button:hover { background: #e9ecef; }
        .chat-messages { flex: 1; overflow-y: auto; padding: 20px; background: #fafafa; }
        .no-conversation { text-align: center; padding: 60px 20px; color: #999; }
        .no-conversation i { font-size: 64px; margin-bottom: 20px; color: #ddd; }
        .message { margin-bottom: 15px; display: flex; flex-direction: column; }
        .message.sent { align-items: flex-end; }
        .message.received { align-items: flex-start; }
        .message-text { max-width: 70%; padding: 10px 15px; border-radius: 18px; word-wrap: break-word; font-size: 14px; line-height: 1.4; }
        .message.sent .message-text { background: #2196F3; color: white; border-bottom-right-radius: 4px; }
        .message.received .message-text { background: white; color: #333; border-bottom-left-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .message-time { font-size: 11px; color: #999; margin-top: 4px; padding: 0 5px; }
        .message.sent .message-time { color: #999; }
        .chat-input-area { padding: 20px; border-top: 1px solid #e0e0e0; background: white; }
        .chat-input-wrapper { display: flex; gap: 10px; }
        .chat-input-wrapper textarea { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 8px; resize: none; font-family: inherit; font-size: 14px; transition: border-color 0.2s; }
        .chat-input-wrapper textarea:focus { outline: none; border-color: #2196F3; }
        .chat-input-wrapper button { padding: 10px 24px; background: #2196F3; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: background 0.2s; white-space: nowrap; }
        .chat-input-wrapper button:hover:not(:disabled) { background: #1976D2; }
        .chat-input-wrapper button:disabled { opacity: 0.6; cursor: not-allowed; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background: white; border-radius: 12px; width: 450px; max-height: 80vh; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.3); }
        .modal-header { padding: 20px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { color: #333; }
        .modal-header button { background: none; border: none; font-size: 24px; cursor: pointer; color: #999; }
        .modal-header button:hover { color: #333; }
        .modal-body { padding: 20px; max-height: 500px; overflow-y: auto; }
        .modal-body input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 15px; font-size: 14px; }
        .modal-body input[type="text"]:focus { outline: none; border-color: #2196F3; }
        .employee-item { display: flex; align-items: center; padding: 12px; border-radius: 8px; cursor: pointer; transition: background 0.2s; margin-bottom: 4px; }
        .employee-item:hover { background: #e9ecef; }
        .employee-avatar { width: 40px; height: 40px; border-radius: 50%; background: #4CAF50; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; margin-right: 12px; flex-shrink: 0; }
        .employee-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .employee-info { flex: 1; }
        .employee-name { font-weight: 600; color: #333; font-size: 14px; }
        .employee-designation { font-size: 12px; color: #999; }
        .employee-role-badge { background: #4CAF50; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .debug-toggle { margin-left: 10px; font-size: 12px; color: #999; cursor: pointer; }
        .debug-toggle:hover { color: #333; }
        .debug-indicator { display: none; background: #ff9800; color: white; font-size: 10px; padding: 2px 6px; border-radius: 4px; margin-left: 8px; font-weight: bold; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 10px; } .chat-sidebar { width: 100%; max-height: 200px; } .chat-container { flex-direction: column; height: calc(100vh - 20px); } .modal-content { width: 95%; } }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
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
                
                <button class="new-chat-btn" onclick="showNewChatModal()">
                    <i class="fas fa-plus"></i> New Chat with Employee
                </button>
                
                <div class="conversations-list" id="conversationsList">
                    <h4><i class="fas fa-history"></i> Conversations</h4>
                    <?php if(count($conversations) > 0): ?>
                        <?php foreach($conversations as $conv): ?>
                            <div class="conversation-item" data-conv-id="<?php echo $conv['conversation_id']; ?>" onclick="loadConversation(<?php echo $conv['conversation_id']; ?>, <?php echo $conv['other_user_id']; ?>, '<?php echo addslashes($conv['other_user_name']); ?>')">
                                <div class="conversation-avatar">
                                    <?php if(isset($conv['profile_picture']) && $conv['profile_picture']): ?>
                                        <img src="<?php echo $conv['profile_picture']; ?>" alt="<?php echo $conv['other_user_name']; ?>">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($conv['other_user_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="conversation-info">
                                    <div class="conversation-name"><?php echo htmlspecialchars($conv['other_user_name']); ?></div>
                                    <div class="conversation-role"><?php echo htmlspecialchars($conv['other_user_role'] ?? 'Employee'); ?></div>
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
                            <p style="font-size: 12px; margin-top: 5px;">Click "New Chat with Employee" to start</p>
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
                        <button onclick="toggleDebug()" title="Toggle Debug Info" class="debug-toggle" id="debugToggle">
                            <i class="fas fa-bug"></i>
                            <span id="debugIndicator" class="debug-indicator">ON</span>
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
                <h3><i class="fas fa-user"></i> Start New Chat with Employee</h3>
                <button onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="text" id="employeeSearch" placeholder="Search employees..." onkeyup="filterEmployees()">
                <div id="employeeList">
                    <?php if(count($employees) > 0): ?>
                        <?php foreach($employees as $emp): ?>
                            <div class="employee-item" onclick="startChat(<?php echo $emp['id']; ?>, '<?php echo addslashes($emp['name']); ?>')">
                                <div class="employee-avatar">
                                    <?php if(isset($emp['profile_picture']) && $emp['profile_picture']): ?>
                                        <img src="<?php echo $emp['profile_picture']; ?>" alt="<?php echo $emp['name']; ?>">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($emp['name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="employee-info">
                                    <div class="employee-name"><?php echo htmlspecialchars($emp['name']); ?></div>
                                    <div class="employee-designation"><?php echo htmlspecialchars($emp['email_id'] ?? ''); ?></div>
                                </div>
                                <span class="employee-role-badge">Employee</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; color: #999;">
                            <i class="fas fa-user-slash" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <p>No employees available to chat</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // =============================================
        // GLOBALS
        // =============================================
        var currentConversationId = null;
        var currentReceiverId = null;
        var currentReceiverName = '';
        var currentUserId = <?php echo $admin_id; ?>;
        var pollingInterval = null;
        var debugMode = false; // OFF by default

        // =============================================
        // LOAD CONVERSATION
        // =============================================
        function loadConversation(conversationId, receiverId, receiverName) {
            console.log('Loading conversation:', conversationId, receiverId, receiverName);
            
            currentConversationId = conversationId;
            currentReceiverId = receiverId;
            currentReceiverName = receiverName;
            
            document.getElementById('chatUserName').textContent = receiverName;
            document.getElementById('chatUserStatus').textContent = 'Online';
            document.getElementById('chatInputArea').style.display = 'block';
            
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
            
            document.getElementById('chatMessages').innerHTML = '<div style="text-align: center; padding: 20px; color: #999;"><i class="fas fa-spinner fa-spin"></i> Loading messages...</div>';
            
            loadMessages(conversationId);
            
            pollingInterval = setInterval(function() {
                if (currentConversationId) {
                    loadMessages(currentConversationId, true);
                    updateUnreadCount();
                }
            }, 5000);
            
            document.querySelectorAll('.conversation-item').forEach(function(item) {
                item.classList.remove('active');
                if (item.getAttribute('data-conv-id') == conversationId) {
                    item.classList.add('active');
                }
            });
        }

        // =============================================
        // EXTRACT MESSAGES FROM ANY RESPONSE STRUCTURE
        // =============================================
        function extractMessages(data) {
            function findArray(obj) {
                if (Array.isArray(obj)) {
                    if (obj.length > 0 && obj[0] && (obj[0].sender_id || obj[0].message || obj[0].created_at)) {
                        return obj;
                    }
                    for (var i = 0; i < obj.length; i++) {
                        var found = findArray(obj[i]);
                        if (found) return found;
                    }
                } else if (obj && typeof obj === 'object') {
                    if (obj.messages && Array.isArray(obj.messages)) return obj.messages;
                    if (obj.data && Array.isArray(obj.data)) return obj.data;
                    if (obj.results && Array.isArray(obj.results)) return obj.results;
                    if (obj.chat && Array.isArray(obj.chat)) return obj.chat;
                    for (var key in obj) {
                        if (obj.hasOwnProperty(key)) {
                            var found = findArray(obj[key]);
                            if (found) return found;
                        }
                    }
                }
                return null;
            }

            var found = findArray(data);
            if (found && Array.isArray(found)) {
                if (found.length === 0 || found[0].sender_id || found[0].message) {
                    return found;
                }
            }
            return null;
        }

        // =============================================
        // LOAD MESSAGES – IMPROVED
        // =============================================
        function loadMessages(conversationId, append = false) {
            var url = 'chat_api.php?action=get_messages&conversation_id=' + conversationId + '&limit=50';
            console.log('Fetching messages from:', url);
            
            fetch(url)
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    console.log('RAW API RESPONSE:', JSON.stringify(data, null, 2));
                    
                    if (data.success === true) {
                        var messages = extractMessages(data);
                        console.log('Extracted messages:', messages);
                        
                        if (messages && messages.length > 0) {
                            renderMessages(messages, append);
                            markAsRead(conversationId);
                        } else {
                            if (!append) {
                                document.getElementById('chatMessages').innerHTML = 
                                    '<div class="no-conversation">' +
                                    '<i class="fas fa-comment-dots"></i>' +
                                    '<p>No messages yet. Start the conversation!</p>' +
                                    '</div>';
                            }
                            
                            // Show debug info only if debug mode is ON
                            if (debugMode && !append) {
                                var hasMessagesKey = data.hasOwnProperty('messages') && Array.isArray(data.messages);
                                var hasDataKey = data.hasOwnProperty('data') && Array.isArray(data.data);
                                var debugMsg = '';
                                if (hasMessagesKey) {
                                    debugMsg = 'API returned an empty "messages" array.';
                                } else if (hasDataKey) {
                                    debugMsg = 'API returned an empty "data" array.';
                                } else {
                                    debugMsg = 'No messages array found in the response.';
                                }
                                
                                var debugInfo = '<div style="background:#fff3cd;padding:10px;border-radius:8px;margin-bottom:10px;font-size:12px;color:#856404;">' +
                                    '<strong>Debug:</strong> ' + debugMsg + '<br>' +
                                    'Keys received: ' + Object.keys(data).join(', ') +
                                    '<br><pre style="background:#eee;padding:5px;border-radius:4px;overflow:auto;max-height:150px;">' + 
                                    JSON.stringify(data, null, 2) + '</pre>' +
                                    '</div>';
                                var container = document.getElementById('chatMessages');
                                container.innerHTML = debugInfo + container.innerHTML;
                            }
                        }
                    } else {
                        console.error('API error:', data.message || 'Unknown error');
                        if (!append) {
                            document.getElementById('chatMessages').innerHTML = 
                                '<div style="text-align: center; padding: 40px 20px; color: #f44336;">' +
                                '<i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 15px;"></i>' +
                                '<p>Error: ' + (data.message || 'Unknown error') + '</p>' +
                                '<button onclick="refreshMessages()" style="margin-top: 15px; padding: 8px 20px; background: #2196F3; color: white; border: none; border-radius: 5px; cursor: pointer;">' +
                                '<i class="fas fa-sync"></i> Retry</button>' +
                                '</div>';
                        }
                    }
                })
                .catch(function(error) {
                    console.error('Fetch error:', error);
                    if (!append) {
                        document.getElementById('chatMessages').innerHTML = 
                            '<div style="text-align: center; padding: 40px 20px; color: #f44336;">' +
                            '<i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 15px;"></i>' +
                            '<p>Error loading messages: ' + error.message + '</p>' +
                            '<button onclick="refreshMessages()" style="margin-top: 15px; padding: 8px 20px; background: #2196F3; color: white; border: none; border-radius: 5px; cursor: pointer;">' +
                            '<i class="fas fa-sync"></i> Retry</button>' +
                            '</div>';
                    }
                });
        }

        // =============================================
        // RENDER MESSAGES
        // =============================================
        function renderMessages(messages, append = false) {
            var container = document.getElementById('chatMessages');
            
            if (!append) {
                container.innerHTML = '';
            }
            
            if (!messages || !Array.isArray(messages) || messages.length === 0) {
                if (!append) {
                    container.innerHTML = 
                        '<div class="no-conversation">' +
                        '<i class="fas fa-comment-dots"></i>' +
                        '<p>No messages yet. Start the conversation!</p>' +
                        '</div>';
                }
                return;
            }
            
            var noConv = container.querySelector('.no-conversation');
            if (noConv) {
                container.innerHTML = '';
            }
            
            messages.sort(function(a, b) {
                var timeA = new Date(a.created_at || 0);
                var timeB = new Date(b.created_at || 0);
                return timeA - timeB;
            });
            
            for (var i = 0; i < messages.length; i++) {
                var msg = messages[i];
                var isSent = (msg.sender_id == currentUserId);
                var messageDiv = document.createElement('div');
                messageDiv.className = 'message ' + (isSent ? 'sent' : 'received');
                
                var messageText = msg.message || msg.content || msg.text || '';
                var timeStamp = msg.created_at || msg.timestamp || new Date().toISOString();
                
                var readStatus = '';
                if (isSent) {
                    if (msg.is_read == 1) {
                        readStatus = ' <i class="fas fa-check-double" style="color: #4CAF50;"></i>';
                    } else {
                        readStatus = ' <i class="fas fa-check"></i>';
                    }
                }
                
                messageDiv.innerHTML = 
                    '<div class="message-text">' + escapeHtml(messageText) + '</div>' +
                    '<div class="message-time">' + formatTime(timeStamp) + readStatus + '</div>';
                container.appendChild(messageDiv);
            }
            
            container.scrollTop = container.scrollHeight;
        }

        // =============================================
        // SEND MESSAGE
        // =============================================
        function sendMessage() {
            var input = document.getElementById('messageInput');
            var message = input.value.trim();
            
            if (!message) {
                return;
            }
            
            if (!currentConversationId || !currentReceiverId) {
                alert('Please select a conversation first');
                return;
            }
            
            var sendBtn = document.getElementById('sendButton');
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            var formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('conversation_id', currentConversationId);
            formData.append('receiver_id', currentReceiverId);
            formData.append('message', message);
            
            fetch('chat_api.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                console.log('Send message response:', data);
                if (data.success) {
                    input.value = '';
                    loadMessages(currentConversationId, false);
                    updateConversations();
                } else {
                    alert('Failed to send message: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                alert('Failed to send message. Please try again.');
            })
            .finally(function() {
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
            });
        }

        // =============================================
        // MARK AS READ
        // =============================================
        function markAsRead(conversationId) {
            var formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('conversation_id', conversationId);
            
            fetch('chat_api.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    updateUnreadCount();
                }
            })
            .catch(function(error) {
                console.error('Error marking as read:', error);
            });
        }

        // =============================================
        // UPDATE CONVERSATIONS LIST
        // =============================================
        function updateConversations() {
            fetch('chat_api.php?action=get_conversations')
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    console.log('Conversations response:', data);
                    if (data.success) {
                        var container = document.getElementById('conversationsList');
                        var conversations = data.conversations || data.data || [];
                        
                        var header = container.querySelector('h4');
                        container.innerHTML = '';
                        if (header) {
                            container.appendChild(header);
                        } else {
                            var newHeader = document.createElement('h4');
                            newHeader.innerHTML = '<i class="fas fa-history"></i> Conversations';
                            container.appendChild(newHeader);
                        }
                        
                        if (conversations.length > 0) {
                            for (var i = 0; i < conversations.length; i++) {
                                var conv = conversations[i];
                                var convDiv = document.createElement('div');
                                convDiv.className = 'conversation-item';
                                convDiv.setAttribute('data-conv-id', conv.conversation_id);
                                
                                if (conv.conversation_id == currentConversationId) {
                                    convDiv.classList.add('active');
                                }
                                
                                (function(id, uid, name) {
                                    convDiv.onclick = function() {
                                        loadConversation(id, uid, name);
                                    };
                                })(conv.conversation_id, conv.other_user_id, conv.other_user_name);
                                
                                var avatarText = conv.other_user_name ? conv.other_user_name.charAt(0).toUpperCase() : '?';
                                var lastMessageText = conv.last_message ? conv.last_message.substring(0, 30) + '...' : 'No messages yet';
                                var timeText = conv.last_message_time ? formatTime(conv.last_message_time) : '';
                                var unreadHtml = conv.unread_count > 0 ? '<div class="unread-count">' + conv.unread_count + '</div>' : '';
                                
                                convDiv.innerHTML = 
                                    '<div class="conversation-avatar">' + avatarText + '</div>' +
                                    '<div class="conversation-info">' +
                                        '<div class="conversation-name">' + escapeHtml(conv.other_user_name || 'Unknown') + '</div>' +
                                        '<div class="conversation-role">' + escapeHtml(conv.other_user_role || 'Employee') + '</div>' +
                                        '<div class="conversation-last-message">' + escapeHtml(lastMessageText) + '</div>' +
                                    '</div>' +
                                    '<div class="conversation-meta">' +
                                        '<div class="conversation-time">' + timeText + '</div>' +
                                        unreadHtml +
                                    '</div>';
                                container.appendChild(convDiv);
                            }
                        } else {
                            container.innerHTML = 
                                '<h4><i class="fas fa-history"></i> Conversations</h4>' +
                                '<div class="no-conversations">' +
                                    '<i class="fas fa-comment-slash"></i>' +
                                    '<p>No conversations yet</p>' +
                                    '<p style="font-size: 12px; margin-top: 5px;">Click "New Chat with Employee" to start</p>' +
                                '</div>';
                        }
                    }
                })
                .catch(function(error) {
                    console.error('Error updating conversations:', error);
                });
        }

        // =============================================
        // UPDATE UNREAD COUNT
        // =============================================
        function updateUnreadCount() {
            fetch('chat_api.php?action=get_unread_count')
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    console.log('Unread count response:', data);
                    if (data.success) {
                        var badge = document.getElementById('unreadBadge');
                        var unreadCount = data.unread_count || data.count || 0;
                        if (unreadCount > 0) {
                            if (badge) {
                                badge.textContent = unreadCount;
                                badge.style.display = 'inline';
                            }
                        } else if (badge) {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(function(error) {
                    console.error('Error updating unread count:', error);
                });
        }

        // =============================================
        // NEW CHAT MODAL
        // =============================================
        function showNewChatModal() {
            document.getElementById('newChatModal').style.display = 'flex';
            document.getElementById('employeeSearch').value = '';
            filterEmployees();
        }

        function closeModal() {
            document.getElementById('newChatModal').style.display = 'none';
        }

        function filterEmployees() {
            var search = document.getElementById('employeeSearch').value.toLowerCase();
            var items = document.querySelectorAll('.employee-item');
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                var name = item.querySelector('.employee-name')?.textContent.toLowerCase() || '';
                var email = item.querySelector('.employee-designation')?.textContent.toLowerCase() || '';
                if (name.includes(search) || email.includes(search)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            }
        }

        // =============================================
        // START CHAT WITH EMPLOYEE
        // =============================================
        function startChat(employeeId, employeeName) {
            console.log('Starting chat with:', employeeId, employeeName);
            
            fetch('chat_api.php?action=get_conversations')
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        var conversations = data.conversations || data.data || [];
                        var existingConv = null;
                        for (var i = 0; i < conversations.length; i++) {
                            if (conversations[i].other_user_id == employeeId) {
                                existingConv = conversations[i];
                                break;
                            }
                        }
                        
                        if (existingConv) {
                            loadConversation(existingConv.conversation_id, employeeId, employeeName);
                            closeModal();
                        } else {
                            var formData = new FormData();
                            formData.append('action', 'send_message');
                            formData.append('receiver_id', employeeId);
                            formData.append('message', 'Hello! I would like to chat.');
                            
                            fetch('chat_api.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(function(response) {
                                return response.json();
                            })
                            .then(function(data) {
                                console.log('New conversation response:', data);
                                if (data.success) {
                                    loadConversation(data.conversation_id, employeeId, employeeName);
                                    closeModal();
                                } else {
                                    alert('Failed to start chat: ' + (data.message || 'Unknown error'));
                                }
                            })
                            .catch(function(error) {
                                console.error('Error:', error);
                                alert('Failed to start chat. Please try again.');
                            });
                        }
                    }
                })
                .catch(function(error) {
                    console.error('Error checking conversations:', error);
                    alert('Failed to check existing conversations.');
                });
        }

        // =============================================
        // REFRESH MESSAGES
        // =============================================
        function refreshMessages() {
            if (currentConversationId) {
                loadMessages(currentConversationId, false);
            }
        }

        // =============================================
        // TOGGLE DEBUG MODE
        // =============================================
        function toggleDebug() {
            debugMode = !debugMode;
            console.log('Debug mode:', debugMode);
            var indicator = document.getElementById('debugIndicator');
            if (debugMode) {
                indicator.style.display = 'inline-block';
            } else {
                indicator.style.display = 'none';
            }
            if (currentConversationId) {
                loadMessages(currentConversationId, false);
            }
        }

        // =============================================
        // KEY PRESS HANDLER
        // =============================================
        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }

        // =============================================
        // UTILITY FUNCTIONS
        // =============================================
        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatTime(timestamp) {
            if (!timestamp) return '';
            try {
                var date = new Date(timestamp);
                if (isNaN(date.getTime())) return '';
                var now = new Date();
                var diff = now - date;
                if (diff < 60000) return 'Just now';
                if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
                if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch(e) {
                return '';
            }
        }

        // =============================================
        // CLOSE MODAL ON OUTSIDE CLICK
        // =============================================
        window.onclick = function(event) {
            var modal = document.getElementById('newChatModal');
            if (event.target == modal) {
                closeModal();
            }
        };

        // =============================================
        // AUTO-LOAD FIRST CONVERSATION
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            <?php if(count($conversations) > 0): ?>
                var firstConv = <?php echo json_encode($conversations[0]); ?>;
                loadConversation(firstConv.conversation_id, firstConv.other_user_id, firstConv.other_user_name);
            <?php endif; ?>
            updateUnreadCount();
            // Ensure debug indicator is hidden initially
            document.getElementById('debugIndicator').style.display = 'none';
        });

        console.log('Admin Chat loaded. User ID:', currentUserId);
    </script>
</body>
</html>