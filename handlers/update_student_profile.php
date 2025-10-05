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
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $dob = trim($_POST['dob']);

    // Basic validation
    if (empty($name) || empty($phone) || empty($dob)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, dob = ? WHERE id = ?");
        if ($stmt->execute([$name, $phone, $dob, $user_id])) {
            // Update session variable if name changed
            if ($_SESSION['user_name'] !== $name) {
                $_SESSION['user_name'] = $name;
            }
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile.']);
        }
    } catch (PDOException $e) {
        // Log error properly in a real application
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
