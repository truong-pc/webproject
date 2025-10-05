<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/invoice_functions.php';

safeSessionStart();

// Only allow POST requests from authenticated admins
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied']);
    exit;
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$result = addPaymentAndNotify($input);

echo json_encode($result);
?>
