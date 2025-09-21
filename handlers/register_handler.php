<?php
require_once __DIR__ . '/../config/connect.php';
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
        'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
        'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'dob' => sanitizeInput($_POST['dob'] ?? ''),
        'license_number' => sanitizeInput($_POST['license_number'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? '')
    ];

    
    
    // Validation rules
    if (empty($data['first_name'])) {
        $errors[] = 'First name is required';
    } elseif (strlen($data['first_name']) < 2) {
        $errors[] = 'First name must be at least 2 characters';
    }
    
    if (empty($data['last_name'])) {
        $errors[] = 'Last name is required';
    } elseif (strlen($data['last_name']) < 2) {
        $errors[] = 'Last name must be at least 2 characters';
    }
    
    if (empty($data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!validateEmail($data['email'])) {
        $errors[] = 'Please enter a valid email address';
    } elseif (emailExists($data['email'])) {
        $errors[] = 'This email is already registered';
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
        'data' => $data,
        'success' => $success
    ];
}