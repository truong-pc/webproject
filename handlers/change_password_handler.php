<?php
session_start();
require_once '../config/connect_db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    if ($new_password !== $confirm_new_password) {
        echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
        exit;
    }

    $user = get_user_by_id($user_id);

    if (!$user || !password_verify($current_password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect current password.']);
        exit;
    }

    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    if ($stmt->execute([$new_password_hash, $user_id])) {
        echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to change password.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
