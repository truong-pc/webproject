<?php
function createNotification(int $user_id, string $title, string $content): bool
{
    $pdo = db();
    try {
        $sql = "INSERT INTO notifications (user_id, title, content, is_read) VALUES (?, ?, ?, FALSE)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$user_id, $title, $content]);
    } catch (PDOException $e) {
        error_log("Notification creation failed: " . $e->getMessage());
        return false;
    }
}

function get_notifications_by_user($user_id)
{
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function mark_notification_as_read($notification_id, $user_id)
{
    $pdo = db();
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    return $stmt->execute([$notification_id, $user_id]);
}

