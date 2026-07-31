<?php
require_once 'db_connection.php';

class ChatModel {
    private $conn;

    public function __construct() {
        try {
            $this->conn = getConnection();
        } catch (Exception $e) {
            error_log("ChatModel Constructor Error: " . $e->getMessage());
            throw $e;
        }
    }

    // ─────────────────────────────────────────────
    // Get or create a conversation between two users
    // Always stores participant IDs in sorted order
    // ─────────────────────────────────────────────
    public function getOrCreateConversation($user1_id, $user2_id) {
        try {
            // Always sort so participant1_id < participant2_id
            $p1 = min($user1_id, $user2_id);
            $p2 = max($user1_id, $user2_id);

            $stmt = $this->conn->prepare(
                "SELECT id FROM chat_conversations
                 WHERE participant1_id = ? AND participant2_id = ?"
            );
            $stmt->execute([$p1, $p2]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                error_log("Existing conversation found: " . $row['id']);
                return $row['id'];
            }

            $stmt = $this->conn->prepare(
                "INSERT INTO chat_conversations (participant1_id, participant2_id, created_at)
                 VALUES (?, ?, NOW())"
            );
            $stmt->execute([$p1, $p2]);
            $newId = $this->conn->lastInsertId();
            error_log("New conversation created: $newId");
            return $newId;

        } catch (Exception $e) {
            error_log("getOrCreateConversation Error: " . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // Send a message
    // ─────────────────────────────────────────────
    public function sendMessage($conversation_id, $sender_id, $receiver_id, $message) {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO chat_messages
                    (conversation_id, sender_id, receiver_id, message, is_read, created_at)
                 VALUES (?, ?, ?, ?, 0, NOW())"
            );
            $result = $stmt->execute([$conversation_id, $sender_id, $receiver_id, $message]);
            return $result ? $this->conn->lastInsertId() : false;

        } catch (Exception $e) {
            error_log("sendMessage Error: " . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // Get messages for a conversation
    // PRIMARY fix: also resolves by participants as
    // fallback so stale conversation_ids still work
    // ─────────────────────────────────────────────
    public function getMessages($conversation_id, $limit = 50) {
        try {
            // ── Step 1: verify conversation exists ──
            $stmt = $this->conn->prepare(
                "SELECT id, participant1_id, participant2_id
                 FROM chat_conversations WHERE id = ?"
            );
            $stmt->execute([$conversation_id]);
            $conv = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$conv) {
                error_log("getMessages: conversation $conversation_id not found");
                return [];
            }

            // ── Step 2: count messages with this conversation_id ──
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) as cnt FROM chat_messages WHERE conversation_id = ?"
            );
            $stmt->execute([$conversation_id]);
            $cnt = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
            error_log("getMessages: $cnt messages for conversation_id=$conversation_id");

            // ── Step 3: if 0 messages, check if messages exist for
            //    these two participants under ANY conversation_id
            //    (handles data inserted without conversation_id) ──
            if ($cnt == 0) {
                $p1 = $conv['participant1_id'];
                $p2 = $conv['participant2_id'];

                $stmt = $this->conn->prepare(
                    "SELECT COUNT(*) as cnt FROM chat_messages
                     WHERE (sender_id = ? AND receiver_id = ?)
                        OR (sender_id = ? AND receiver_id = ?)"
                );
                $stmt->execute([$p1, $p2, $p2, $p1]);
                $orphanCnt = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
                error_log("getMessages: $orphanCnt orphan messages between $p1 and $p2");

                if ($orphanCnt > 0) {
                    // ── FIX: reassign those messages to this conversation ──
                    $stmt = $this->conn->prepare(
                        "UPDATE chat_messages
                         SET conversation_id = ?
                         WHERE conversation_id != ?
                           AND ((sender_id = ? AND receiver_id = ?)
                             OR (sender_id = ? AND receiver_id = ?))"
                    );
                    $stmt->execute([
                        $conversation_id, $conversation_id,
                        $p1, $p2, $p2, $p1
                    ]);
                    error_log("getMessages: reassigned $orphanCnt orphan messages to conversation $conversation_id");
                }
            }

            // ── Step 4: fetch the messages ──
            $stmt = $this->conn->prepare(
                "SELECT
                    id,
                    conversation_id,
                    sender_id,
                    receiver_id,
                    message,
                    is_read,
                    DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%s') as created_at
                 FROM chat_messages
                 WHERE conversation_id = ?
                 ORDER BY created_at ASC
                 LIMIT ?"
            );
            $stmt->execute([$conversation_id, intval($limit)]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("getMessages: returning " . count($messages) . " messages");
            return $messages ?: [];

        } catch (Exception $e) {
            error_log("getMessages Error: " . $e->getMessage() .
                      " | File: " . $e->getFile() . " Line: " . $e->getLine());
            return [];
        }
    }

    // ─────────────────────────────────────────────
    // Mark messages as read
    // ─────────────────────────────────────────────
    public function markAsRead($conversation_id, $user_id) {
        try {
            $stmt = $this->conn->prepare(
                "UPDATE chat_messages
                 SET is_read = 1, read_at = NOW()
                 WHERE conversation_id = ?
                   AND receiver_id = ?
                   AND is_read = 0"
            );
            return $stmt->execute([$conversation_id, $user_id]);

        } catch (Exception $e) {
            error_log("markAsRead Error: " . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // Get unread message count for a user
    // ─────────────────────────────────────────────
    public function getUnreadCount($user_id) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) as count FROM chat_messages
                 WHERE receiver_id = ? AND is_read = 0"
            );
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['count'] ?? 0;

        } catch (Exception $e) {
            error_log("getUnreadCount Error: " . $e->getMessage());
            return 0;
        }
    }

    // ─────────────────────────────────────────────
    // Get all conversations for a user
    // ─────────────────────────────────────────────
    public function getUserConversations($user_id) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT
                    c.id as conversation_id,
                    CASE
                        WHEN c.participant1_id = ? THEN c.participant2_id
                        ELSE c.participant1_id
                    END as other_user_id,
                    u.name            as other_user_name,
                    u.email_id        as other_user_email,
                    u.role            as other_user_role,
                    u.profile_picture,
                    (SELECT message FROM chat_messages
                     WHERE conversation_id = c.id
                     ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM chat_messages
                     WHERE conversation_id = c.id
                     ORDER BY created_at DESC LIMIT 1) as last_message_time,
                    (SELECT COUNT(*) FROM chat_messages
                     WHERE conversation_id = c.id
                       AND receiver_id = ?
                       AND is_read = 0) as unread_count
                 FROM chat_conversations c
                 JOIN users u
                   ON (u.id = CASE
                                  WHEN c.participant1_id = ? THEN c.participant2_id
                                  ELSE c.participant1_id
                              END)
                 WHERE (c.participant1_id = ? OR c.participant2_id = ?)
                   AND u.status = 'active'
                 ORDER BY last_message_time DESC"
            );
            $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $rows ?: [];

        } catch (Exception $e) {
            error_log("getUserConversations Error: " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────
    // Get all active employees (for admin new-chat modal)
    // ─────────────────────────────────────────────
    public function getAllEmployees($admin_id) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT id, name, email_id, role, profile_picture, status
                 FROM users
                 WHERE role = 'Employee'
                   AND status = 'active'
                   AND id != ?
                 ORDER BY name ASC"
            );
            $stmt->execute([$admin_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $rows ?: [];

        } catch (Exception $e) {
            error_log("getAllEmployees Error: " . $e->getMessage());
            return [];
        }
    }
}
?>