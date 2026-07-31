<?php
// Turn off error display and log errors instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Add this to log all errors to a file
ini_set('error_log', 'chat_errors.log');

session_start();
require_once 'db_connection.php';
require_once 'chat_model.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized', 'session' => $_SESSION]);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'Unknown';

// Log the request for debugging
error_log("=== CHAT API REQUEST ===");
error_log("User ID: $user_id, Role: $user_role");
error_log("GET: " . print_r($_GET, true));
error_log("POST: " . print_r($_POST, true));

// Create chat model with error handling
try {
    $chat = new ChatModel();
} catch (Exception $e) {
    error_log("Failed to create ChatModel: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to initialize chat: ' . $e->getMessage()]);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// If action is empty, try to get from raw input
if (empty($action)) {
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        parse_str($input, $postData);
        $action = $postData['action'] ?? '';
    }
}

error_log("Action: " . $action);

if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Action parameter is required']);
    exit();
}

try {
    switch ($action) {
        case 'send_message':
            $conversation_id = $_POST['conversation_id'] ?? null;
            $receiver_id = $_POST['receiver_id'] ?? null;
            $message = $_POST['message'] ?? '';
            
            error_log("Send Message - Conversation: $conversation_id, Receiver: $receiver_id, Message: " . substr($message, 0, 50));
            
            if (empty($message)) {
                echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
                exit();
            }
            
            if (empty($receiver_id)) {
                echo json_encode(['success' => false, 'message' => 'Receiver ID is required']);
                exit();
            }
            
            if (!$conversation_id) {
                $conversation_id = $chat->getOrCreateConversation($user_id, $receiver_id);
                error_log("Created new conversation: $conversation_id");
            }
            
            $message_id = $chat->sendMessage($conversation_id, $user_id, $receiver_id, $message);
            
            if ($message_id) {
                echo json_encode([
                    'success' => true,
                    'message_id' => $message_id,
                    'conversation_id' => $conversation_id
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send message']);
            }
            break;
            
        case 'get_messages':
            $conversation_id = $_GET['conversation_id'] ?? null;
            $limit = $_GET['limit'] ?? 50;
            
            error_log("Get Messages - Conversation: $conversation_id, Limit: $limit");
            
            if (!$conversation_id) {
                echo json_encode(['success' => false, 'message' => 'Conversation ID required']);
                exit();
            }
            
            // Debug - check if conversation exists
            try {
                $conn = getConnection();
                $checkQuery = "SELECT id FROM chat_conversations WHERE id = ?";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->execute([$conversation_id]);
                $convExists = $checkStmt->fetch();
                
                if (!$convExists) {
                    error_log("Conversation not found: $conversation_id");
                    echo json_encode(['success' => false, 'message' => 'Conversation not found: ' . $conversation_id]);
                    exit();
                }
            } catch (Exception $e) {
                error_log("Error checking conversation: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                exit();
            }
            
            $messages = $chat->getMessages($conversation_id, $limit);
            error_log("Found " . count($messages) . " messages");
            
            echo json_encode(['success' => true, 'messages' => $messages]);
            break;
            
        case 'get_conversations':
            error_log("Get Conversations for user: $user_id");
            $conversations = $chat->getUserConversations($user_id);
            error_log("Found " . count($conversations) . " conversations");
            echo json_encode(['success' => true, 'conversations' => $conversations]);
            break;
            
        case 'mark_read':
            $conversation_id = $_POST['conversation_id'] ?? null;
            
            error_log("Mark Read - Conversation: $conversation_id, User: $user_id");
            
            if (!$conversation_id) {
                echo json_encode(['success' => false, 'message' => 'Conversation ID required']);
                exit();
            }
            
            $result = $chat->markAsRead($conversation_id, $user_id);
            echo json_encode(['success' => $result]);
            break;
            
        case 'get_unread_count':
            error_log("Get Unread Count for user: $user_id");
            $count = $chat->getUnreadCount($user_id);
            error_log("Unread count: $count");
            echo json_encode(['success' => true, 'unread_count' => $count]);
            break;
            
        default:
            error_log("Invalid action: $action");
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    // Log the error with details
    error_log("=== CHAT API ERROR ===");
    error_log("Message: " . $e->getMessage());
    error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return JSON error
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred',
        'debug' => [
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}
?>