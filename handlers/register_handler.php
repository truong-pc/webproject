<?php
// require_once __DIR__ . '/../config/connect_db.php';
require_once __DIR__ . '/../includes/functions.php';

function handleRegistration() {
    $errors = [];
    $data = [];
    $success = false;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['errors' => [], 'data' => [], 'success' => false];
    }
    
    // Sanitize and collect input data
    $data = [
        'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '', // Don't sanitize password
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'dob' => sanitizeInput($_POST['dob'] ?? ''),
        'license_number' => sanitizeInput($_POST['license_number'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? '')
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
    } elseif (strlen($data['password']) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    // Optional phone validation
    if (!empty($data['phone'])) {
        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        if (strlen($phone) < 10) {
            $errors[] = 'Please enter a valid phone number';
        }
    }
    
    // Optional date of birth validation
    if (!empty($data['dob'])) {
        $dob = DateTime::createFromFormat('Y-m-d', $data['dob']);
        if (!$dob) {
            $errors[] = 'Please enter a valid date of birth';
        } else {
            $age = $dob->diff(new DateTime())->y;
            if ($age < 15) {
                $errors[] = 'You must be at least 15 years old to register';
            } elseif ($age > 100) {
                $errors[] = 'Please enter a valid date of birth';
            }
        }
    }
    
    // Create student account if no validation errors
    if (empty($errors)) {
        $result = createStudent($data);
        
        if ($result['success']) {
            $success = true;
        } else {
            $errors[] = 'Registration failed. Please try again later.';
            // Log error for debugging
            error_log("Registration error: " . $result['error']);
        }
    }
    
    return [
        'errors' => $errors,
        'data' => array_diff_key($data, ['password' => '']), // Don't return password
        'success' => $success
    ];
}