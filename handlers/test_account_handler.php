<?php
// handlers/test_account_handler.php - Handler để tạo test account với role tùy chọn
require_once __DIR__ . '/../includes/functions.php';

function handleTestAccountCreation() {
    $errors = [];
    $data = [];
    $success = false;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['errors' => [], 'data' => [], 'success' => false];
    }
    
    // Sanitize và collect input data
    $data = [
        'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '', // Không sanitize password
        'role' => sanitizeInput($_POST['role'] ?? 'student'), // Mặc định là student
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'branch_id' => sanitizeInput($_POST['branch_id'] ?? '1') // Mặc định branch 1
    ];
    
    // Validation rules
    if (empty($data['full_name'])) {
        $errors[] = 'Full name is required';
    } elseif (strlen($data['full_name']) < 3) {
        $errors[] = 'Full name must be at least 3 characters';
    }
    
    if (empty($data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!validateEmail($data['email'])) {
        $errors[] = 'Please enter a valid email address';
    } elseif (emailExists($data['email'])) {
        $errors[] = 'This email is already registered';
    }
    
    if (empty($data['password'])) {
        $errors[] = 'Password is required';
    }
    
    // Validate role - CHỈ CHO PHÉP 3 ROLES
    $allowedRoles = ['admin', 'instructor', 'student'];
    if (!in_array($data['role'], $allowedRoles)) {
        $errors[] = 'Invalid role selected. Only admin, instructor, student are allowed.';
    }
    
    // Optional phone validation
    if (!empty($data['phone'])) {
        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        if (strlen($phone) < 10) {
            $errors[] = 'Please enter a valid phone number';
        }
    }
    
    // Tạo user account nếu không có lỗi validation
    if (empty($errors)) {
        $result = createUserWithRole($data);
        
        if ($result['success']) {
            $success = true;
        } else {
            $errors[] = 'Account creation failed: ' . ($result['error'] ?? 'Unknown error');
            // Log error for debugging
            error_log("Test account creation error: " . ($result['error'] ?? 'Unknown'));
        }
    }
    
    return [
        'errors' => $errors,
        'data' => array_diff_key($data, ['password' => '']), // Không return password
        'success' => $success
    ];
}