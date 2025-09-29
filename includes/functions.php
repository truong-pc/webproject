<?php
/**
 * Central functions file cho Origin Driving School
 * 
 * File này là SINGLE SOURCE cho:
 * - Database connection (qua function db())
 * - Tất cả helper functions
 * 
 * Các file khác CHỈ CẦN require file này, KHÔNG require connect_db.php trực tiếp
 */
require_once __DIR__ . '/../config/connect_db.php';

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
        
        $full_name = trim($data['full_name']);
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

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
 * Authenticate user login - CHỈ HỖ TRỢ 3 ROLES
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
    
    // Chỉ cho phép 3 loại role: admin, instructor, student
    $allowedRoles = ['admin', 'instructor', 'student'];
    if (!in_array($user['role'], $allowedRoles)) {
        return ['success' => false, 'message' => 'Account role not supported'];
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
            return 'admin_dashboard.php';
        case 'instructor':
            return 'schedule.php';
        case 'student':
            return 'schedule.php';
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

/**
 * Get all students with pagination
 */
function getInfoStudents($limit = 100, $offset = 0) {
    $pdo = db();
    
    // Validate và sanitize parameters
    $limit = (int) $limit;
    $offset = (int) $offset;
    
    // Build query với concatenation cho LIMIT/OFFSET
    $sql = "
        SELECT u.id, u.name, u.email, u.phone, u.status, u.created_at,
               s.license_status, b.name AS branch_name
        FROM users u
        LEFT JOIN students s ON s.user_id = u.id
        LEFT JOIN branches b ON b.id = u.branch_id
        WHERE u.role = 'student'
        ORDER BY u.created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}


/**
 * Get upcoming lessons for a specific student
 * Assumptions: there is a `schedule` table with columns: id, start_time, student_id, instructor_id, vehicle_id, status
 * and `users` table contains student/instructor names. This function is defensive if vehicle or instructor missing.
 *
 * @param int $studentId
 * @param int $limit
 * @return array
 */
function getUpcomingLessons(int $studentId, int $limit = 5): array {
    $pdo = db();

    $sql = "
        SELECT s.id, s.start_time, s.status,
               stu.name AS student_name,
               inst.name AS instructor_name,
               v.reg_number AS vehicle_reg
        FROM schedule s
        LEFT JOIN users stu ON stu.id = s.student_id
        LEFT JOIN users inst ON inst.id = s.instructor_id
        LEFT JOIN vehicles v ON v.id = s.vehicle_id
        WHERE s.student_id = :student_id AND s.start_time >= NOW()
        ORDER BY s.start_time ASC
        LIMIT :limit
    ";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        // In case the assumed schema does not exist, return empty array
        return [];
    }
}

/**
 * Fetch student-specific details from students table by user_id
 * Returns associative array with keys e.g. license_status, notes_summary
 */
function getStudentByUserId(int $userId): array {
    $pdo = db();
    try {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Create a new user account with specified role - CHỈ HỖ TRỢ 3 ROLES
 */
function createUserWithRole($data) {
    $pdo = db();
    
    // Validate role - chỉ cho phép 3 roles
    $allowedRoles = ['admin', 'instructor', 'student'];
    if (!in_array($data['role'], $allowedRoles)) {
        return ['success' => false, 'error' => 'Invalid role. Only admin, instructor, student are allowed.'];
    }
    
    try {
        $pdo->beginTransaction();
        
        // Insert into users table with specified role
        $stmt = $pdo->prepare("
            INSERT INTO users (role, name, email, phone, password_hash, branch_id, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $full_name = trim($data['full_name']);
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $role = $data['role'];
        $branch_id = $data['branch_id'] ?? 1;

        $stmt->execute([
            $role,
            $full_name,
            $data['email'],
            $data['phone'] ?? null,
            $password_hash,
            $branch_id
        ]);
        
        $user_id = $pdo->lastInsertId();
        
        // Insert vào bảng con tương ứng dựa trên role
        if ($role === 'student') {
            // Insert vào students table
            $stmt = $pdo->prepare("
                INSERT INTO students (user_id, license_status, notes_summary) 
                VALUES (?, 'none', ?)
            ");
            $notes = "Test student account created via test interface";
            $stmt->execute([$user_id, $notes]);
        } elseif ($role === 'instructor') {
            // Insert vào instructors table (nếu có)
            // Tạm thời skip vì chưa rõ cấu trúc bảng instructors
            // Có thể thêm sau
        }
        // Admin chỉ cần record trong users table
        
        $pdo->commit();
        return ['success' => true, 'user_id' => $user_id, 'role' => $role];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}