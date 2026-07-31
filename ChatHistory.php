<?php
require_once 'config.php';

class ChatHistory {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Save a new chat message
     */
    public function saveMessage($sessionId, $senderId, $content, $receiverId = null, $type = 'text', $metadata = []) {
        $sql = "INSERT INTO chat_history 
                (session_id, sender_id, receiver_id, message_type, content, metadata) 
                VALUES (:session_id, :sender_id, :receiver_id, :message_type, :content, :metadata)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':session_id'   => $sessionId,
            ':sender_id'    => $senderId,
            ':receiver_id'  => $receiverId,
            ':message_type' => $type,
            ':content'      => $content,
            ':metadata'     => json_encode($metadata) // store as JSON string
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Fetch messages for a specific session (conversation)
     */
    public function getMessagesBySession($sessionId, $limit = 50, $offset = 0) {
        $sql = "SELECT * FROM chat_history 
                WHERE session_id = :session_id 
                  AND is_deleted = 0 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':session_id', $sessionId);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        $messages = $stmt->fetchAll();
        
        // Decode metadata JSON back to associative array
        foreach ($messages as &$msg) {
            $msg['metadata'] = json_decode($msg['metadata'], true);
        }
        
        return $messages;
    }

    /**
     * Fetch all messages sent by or received by a specific user
     */
    public function getMessagesByUser($userId, $limit = 50) {
        $sql = "SELECT * FROM chat_history 
                WHERE (sender_id = :user_id OR receiver_id = :user_id)
                  AND is_deleted = 0 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        $messages = $stmt->fetchAll();
        foreach ($messages as &$msg) {
            $msg['metadata'] = json_decode($msg['metadata'], true);
        }
        return $messages;
    }

    /**
     * Mark a message as delivered
     */
    public function markAsDelivered($messageId) {
        $sql = "UPDATE chat_history SET delivered_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $messageId]);
    }

    /**
     * Mark a message as read
     */
    public function markAsRead($messageId) {
        $sql = "UPDATE chat_history SET read_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $messageId]);
    }

    /**
     * Soft-delete a message (hide it)
     */
    public function softDeleteMessage($messageId) {
        $sql = "UPDATE chat_history SET is_deleted = 1 WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $messageId]);
    }

    /**
     * Permanently delete a message (hard delete)
     */
    public function hardDeleteMessage($messageId) {
        $sql = "DELETE FROM chat_history WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $messageId]);
    }
}
?>