<?php
class ChatSystem {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all conversations for a user
     */
    public function getUserConversations($user_id) {
        $sql = "SELECT 
                    m.conversation_id,
                    m.sender_id,
                    m.receiver_id,
                    m.message as last_message,
                    m.created_at as last_message_time,
                    CASE 
                        WHEN m.sender_id = :user_id THEN m.receiver_id 
                        ELSE m.sender_id 
                    END as other_user_id,
                    u.username as other_username,
                    u.role as other_role,
                    u.last_activity,
                    COUNT(CASE WHEN m.is_read = 0 AND m.receiver_id = :user_id THEN 1 END) as unread_count,
                    (u.last_activity > UNIX_TIMESTAMP() - 300) as is_online
                FROM messages m
                JOIN users u ON u.id = CASE 
                    WHEN m.sender_id = :user_id THEN m.receiver_id 
                    ELSE m.sender_id 
                END
                WHERE m.conversation_id IN (
                    SELECT DISTINCT conversation_id 
                    FROM messages 
                    WHERE sender_id = :user_id OR receiver_id = :user_id
                )
                AND m.created_at = (
                    SELECT MAX(created_at) 
                    FROM messages m2 
                    WHERE m2.conversation_id = m.conversation_id
                )
                GROUP BY m.conversation_id
                ORDER BY m.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get unread message count for a user
     */
    public function getUnreadCount($user_id) {
        $sql = "SELECT COUNT(*) as total 
                FROM messages 
                WHERE receiver_id = :user_id AND is_read = 0";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $result = $stmt->fetch();
        return $result ? $result['total'] : 0;
    }
    
    /**
     * Get messages for a conversation
     */
    public function getMessages($conversation_id, $user_id, $limit = 50) {
        // Mark messages as read
        $sql = "UPDATE messages 
                SET is_read = 1, read_at = NOW() 
                WHERE conversation_id = :conversation_id AND receiver_id = :user_id AND is_read = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':conversation_id' => $conversation_id, ':user_id' => $user_id]);
        
        // Get messages
        $sql = "SELECT m.*, u.username, u.role 
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = :conversation_id
                ORDER BY m.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':conversation_id', $conversation_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $messages = $stmt->fetchAll();
        
        // Return in ascending order for display
        return array_reverse($messages);
    }
    
    /**
     * Send a message
     */
    public function sendMessage($conversation_id, $sender_id, $receiver_id, $message) {
        $sql = "INSERT INTO messages (conversation_id, sender_id, receiver_id, message, created_at) 
                VALUES (:conversation_id, :sender_id, :receiver_id, :message, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':conversation_id' => $conversation_id,
            ':sender_id' => $sender_id,
            ':receiver_id' => $receiver_id,
            ':message' => $message
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Get or create a conversation between two users
     */
    public function getOrCreateConversation($user1_id, $user2_id) {
        // Check if conversation exists
        $sql = "SELECT DISTINCT conversation_id 
                FROM messages 
                WHERE (sender_id = :user1_id AND receiver_id = :user2_id) 
                   OR (sender_id = :user2_id AND receiver_id = :user1_id)
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user1_id' => $user1_id, ':user2_id' => $user2_id]);
        $result = $stmt->fetch();
        
        if ($result) {
            return $result['conversation_id'];
        }
        
        // Create new conversation
        $conversation_id = $this->createConversation();
        
        // Add first message to establish conversation
        $this->sendMessage($conversation_id, $user1_id, $user2_id, '');
        
        return $conversation_id;
    }
    
    /**
     * Create a new conversation
     */
    private function createConversation() {
        // You can store conversations in a separate table if needed
        // For now, we'll use a timestamp-based ID
        return time() . '_' . rand(1000, 9999);
    }
    
    /**
     * Get online users
     */
    public function getOnlineUsers($exclude_user_id = null) {
        $sql = "SELECT id, username, role, last_activity 
                FROM users 
                WHERE last_activity > UNIX_TIMESTAMP() - 300";
        
        if ($exclude_user_id) {
            $sql .= " AND id != :exclude_user_id";
        }
        
        $sql .= " ORDER BY username ASC";
        
        $stmt = $this->pdo->prepare($sql);
        if ($exclude_user_id) {
            $stmt->execute([':exclude_user_id' => $exclude_user_id]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get users by role
     */
    public function getUsersByRole($role) {
        $sql = "SELECT id, username, role, last_activity 
                FROM users 
                WHERE role = :role 
                ORDER BY username ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':role' => $role]);
        return $stmt->fetchAll();
    }
}
?>