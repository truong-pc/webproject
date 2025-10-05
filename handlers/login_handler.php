<?php
// require_once __DIR__ . '/../config/connect_db.php';
require_once __DIR__ . '/../includes/functions.php';

function handleLogin() {
    $errors = [];
    $data = [];
    $success = false;
    $redirect_url = '';
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['errors' => [], 'data' => [], 'success' => false, 'redirect_url' => ''];
    }
    
    // Sanitize input
    $data = [
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '' // Don't sanitize password
    ];
    
    // Basic validation
    if (empty($data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!validateEmail($data['email'])) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($data['password'])) {
        $errors[] = 'Password is required';
    }
    
    // Attempt authentication if no validation errors
    if (empty($errors)) {
        $result = authenticateUser($data['email'], $data['password']);
        
        if ($result['success']) {
            // Start session
            startUserSession($result['user']);
            $success = true;
            $redirect_url = getRedirectUrl($result['user']['role']);
        } else {
            $errors[] = $result['message'];
        }
    }
    
    return [
        'errors' => $errors,
        'data' => ['email' => $data['email']], // Don't return password
        'success' => $success,
        'redirect_url' => $redirect_url
    ];
}

/**
 * Handle logout
 */
function handleLogout() {
    logoutUser();
    header('Location: login.php?logged_out=1');
    exit;
}

// Handle logout if requested
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    handleLogout();
}
