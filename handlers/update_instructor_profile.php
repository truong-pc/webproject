<?php
require_once '../config/connect_db.php';
require_once '../includes/functions.php';

safeSessionStart();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isLoggedIn() || getCurrentUser()['role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Access Denied.']);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$dob = trim($_POST['dob'] ?? '');

if (empty($name) || empty($phone) || empty($dob)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("UPDATE users SET name = :name, phone = :phone, dob = :dob WHERE id = :id AND role = 'instructor'");
    $stmt->execute([
        ':name' => $name,
        ':phone' => $phone,
        ':dob' => $dob,
        ':id' => $instructor_id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
    } else {
        // This could mean the data was the same, or the user was not found.
        // For security, we can just say it was successful if no error was thrown.
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully (no changes detected).']);
    }
} catch (PDOException $e) {
    // Log error properly in a real application
    error_log("Profile update failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating your profile.']);
}
