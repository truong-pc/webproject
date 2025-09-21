<?php

/**
 * Validation và helper functions cho Origin Driving School
 */

/**
 * Safely start session if not already started
 */
function safeSessionStart() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if email already exists in database
 */
function emailExists($email) {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch() !== false;
}

/**
 * Create a new student account
 */
function createStudent($data) {
    $pdo = db();
    
    try {
        $pdo->beginTransaction();
        
        // Insert into users table
        $stmt = $pdo->prepare("
            INSERT INTO users (role, name, email, phone, password_hash, branch_id, status) 
            VALUES ('student', ?, ?, ?, ?, '1', 'active')
        ");
        
        $full_name = trim($data['first_name'] . ' ' . $data['last_name']);
        $password_hash = password_hash('admin', PASSWORD_DEFAULT); // Default password: admin

        $stmt->execute([
            $full_name,
            $data['email'],
            $data['phone'] ?? null,
            $password_hash
        ]);
        
        $user_id = $pdo->lastInsertId();
        
        // Insert into students table
        $stmt = $pdo->prepare("
            INSERT INTO students (user_id, license_status, notes_summary) 
            VALUES (?, ?, ?)
        ");
        
        $license_status = !empty($data['license_number']) ? 'learner' : 'none';
        $notes = "Address: " . ($data['address'] ?? 'Not provided') . 
                "\nDOB: " . ($data['dob'] ?? 'Not provided') . 
                "\nLicense No: " . ($data['license_number'] ?? 'Not provided');
        
        $stmt->execute([$user_id, $license_status, $notes]);
        
        $pdo->commit();
        return ['success' => true, 'user_id' => $user_id];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Authenticate user login
 */
function authenticateUser($email, $password) {
    $pdo = db();
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.role, u.password_hash, u.status, b.name as branch_name
        FROM users u 
        LEFT JOIN branches b ON b.id = u.branch_id 
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'message' => 'Email not found'];
    }
    
    if ($user['status'] !== 'active') {
        return ['success' => false, 'message' => 'Account is inactive'];
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid password'];
    }
    
    // Remove password hash from returned data
    unset($user['password_hash']);
    
    return ['success' => true, 'user' => $user];
}

/**
 * Start user session
 */
function startUserSession($user) {
    safeSessionStart();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['branch_name'] = $user['branch_name'];
    $_SESSION['logged_in'] = true;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return session_status() === PHP_SESSION_ACTIVE && 
           isset($_SESSION['logged_in']) && 
           $_SESSION['logged_in'] === true;
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
        'branch_name' => $_SESSION['branch_name']
    ];
}

/**
 * Logout user
 */
function logoutUser() {
    safeSessionStart();
    session_destroy();
}

/**
 * Redirect based on user role
 */
function getRedirectUrl($role) {
    switch ($role) {
        case 'admin':
        case 'staff':
            return 'reports.php';
        case 'instructor':
            return 'schedule.php';
        case 'student':
            return 'students.php';
        default:
            return 'index.php';
    }
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
}