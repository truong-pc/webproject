<?php
// Secure change password handler
require_once '../config/connect_db.php';
require_once '../includes/functions.php';

safeSessionStart();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_new_password'] ?? '';

if ($new !== $confirm) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
    exit;
}

// (Removed minimum length validation by request)

$user = get_user_by_id($userId);
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

if (!password_verify($current, $user['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect current password.']);
    exit;
}

// Prevent reusing the same password
if (password_verify($new, $user['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'New password must be different from current password.']);
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id LIMIT 1');
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt->execute([':hash' => $hash, ':id' => $userId]);

    if ($stmt->rowCount() === 1) {
        echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
    } else {
        // rowCount 0 means same hash or no change; treat as success to avoid leaking info
        echo json_encode(['success' => true, 'message' => 'Password updated (no change detected).']);
    }
} catch (Exception $e) {
    error_log('Password change error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error changing password.']);
}
