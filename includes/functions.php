<?php
/**
 * Core helper functions (branch column removed version)
 * Contains: session helpers, auth, user creation, student utilities.
 */
if (!function_exists('safeSessionStart')) {
    require_once __DIR__.'/../config/connect_db.php';

/** Start PHP session safely (idempotent) */
function safeSessionStart(){ if(session_status()===PHP_SESSION_NONE){ session_start(); } }

/** Validate email format */
function validateEmail($email){ return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }

/** Return true if email is already in users table */
function emailExists($email){
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    return (bool)$stmt->fetch();
}

/**
 * Create a student (users + students row) inside a transaction.
 * Expects keys: full_name, email, password, phone?, license_number?, address?, dob?
 */
function createStudent(array $data){
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO users (role,name,email,phone,dob,password_hash,status) VALUES ('student',?,?,?,?,?, 'active')");
        $full_name = trim($data['full_name']);
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt->execute([$full_name,$data['email'],$data['phone']??null,$data['dob']??null,$password_hash]);
        $user_id = (int)$pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO students (user_id,license_status,notes_summary,address) VALUES (?,?,?,?)");
        $license_status = !empty($data['license_number']) ? 'learner':'none';
        $address = trim($data['address'] ?? '');
        $notes = "License No: ".($data['license_number']??'Not provided');
        $stmt->execute([$user_id,$license_status,$notes,$address]);
        $pdo->commit();
        return ['success'=>true,'user_id'=>$user_id];
    } catch(Exception $e){
        $pdo->rollBack();
        return ['success'=>false,'error'=>$e->getMessage()];
    }
}

/** Authenticate by email/password; supports only admin|instructor|student */
function authenticateUser($email,$password){
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id,name,email,role,password_hash,status FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if(!$user) return ['success'=>false,'message'=>'Email not found'];
    if($user['status']!=='active') return ['success'=>false,'message'=>'Account is inactive'];
    $allowed=['admin','instructor','student'];
    if(!in_array($user['role'],$allowed)) return ['success'=>false,'message'=>'Account role not supported'];
    if(!password_verify($password,$user['password_hash'])) return ['success'=>false,'message'=>'Invalid password'];
    unset($user['password_hash']);
    return ['success'=>true,'user'=>$user];
}

/** Store minimal user info into session */
function startUserSession($user){
    safeSessionStart();
    $_SESSION['user_id']=$user['id'];
    $_SESSION['user_name']=$user['name'];
    $_SESSION['user_email']=$user['email'];
    $_SESSION['user_role']=$user['role'];
    $_SESSION['logged_in']=true;
}

/** Boolean: current session has authenticated user */
function isLoggedIn(){ safeSessionStart(); return !empty($_SESSION['logged_in']); }

/** Destroy current session (logout) */
function logoutUser(){ safeSessionStart(); session_unset(); session_destroy(); }

/** Return associative array of current user or null */
function getCurrentUser(){ 
    if(!isLoggedIn()) return null;
    return [ 'id'=>$_SESSION['user_id'], 'name'=>$_SESSION['user_name'], 'email'=>$_SESSION['user_email'], 'role'=>$_SESSION['user_role'] ]; 
}

/** Map role to landing page */
function getRedirectUrl($role){
    switch($role){
        case 'admin':return 'admin_dashboard.php';
        case 'instructor':return 'schedule.php';
        case 'student':return 'schedule.php';
        default:return 'index.php';
    }
}

/** Recursively sanitize string or array values for HTML output */
function sanitizeInput($data){
    if(is_array($data)) return array_map('sanitizeInput',$data);
    return trim(htmlspecialchars($data,ENT_QUOTES,'UTF-8'));
}

/**
 * Paginated list of student users with their core profile fields.
 * Returns: id, name, email, phone, status, created_at, updated_at,
 *          license_status, address, notes_summary.
 */
function getInfoStudents($limit=100,$offset=0){
    $pdo = db();
    $limit = max(1, (int)$limit);
    $offset = max(0, (int)$offset);

    $sql = "SELECT
                u.id,
                u.name,
                u.email,
                u.phone,
                u.status,
                u.created_at,
                u.updated_at,
                COALESCE(s.license_status, 'none') AS license_status,
                                s.address,
                                s.notes_summary
            FROM users u
            LEFT JOIN students s ON s.user_id = u.id
            WHERE u.role = 'student'
            ORDER BY u.created_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

/** Fetch a single student's full profile (users + students tables only). */
function getStudentDetails(int $studentId): ?array {
    $pdo = db();
    $sql = "SELECT
                u.id,
                u.name,
                u.email,
                u.phone,
                u.status,
                u.created_at,
                u.updated_at,
                COALESCE(s.license_status, 'none') AS license_status,
                                s.address,
                                s.notes_summary
            FROM users u
            LEFT JOIN students s ON s.user_id = u.id
            WHERE u.role = 'student' AND u.id = :id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $studentId, PDO::PARAM_INT);
    $stmt->execute();
    $student = $stmt->fetch();

    return $student ?: null;
}

/** Update student profile information across users & students tables. */
function updateStudentInfo(int $studentId, array $data): array {
    $pdo = db();

    $checkStmt = $pdo->prepare("SELECT email FROM users WHERE id = :id AND role = 'student' LIMIT 1");
    $checkStmt->bindValue(':id', $studentId, PDO::PARAM_INT);
    $checkStmt->execute();
    $currentRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$currentRecord) {
        return ['success' => false, 'error' => 'Student not found.'];
    }
    $currentEmail = $currentRecord['email'] ?? '';

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $status = $data['status'] ?? 'active';
    $license = $data['license_status'] ?? 'none';
    $address = trim($data['address'] ?? '');
    $notes = trim($data['notes_summary'] ?? '');

    if ($name === '' || $email === '') {
        return ['success' => false, 'error' => 'Name and email are required.'];
    }

    if (!validateEmail($email)) {
        return ['success' => false, 'error' => 'Invalid email format.'];
    }

    if (strcasecmp($email, (string) $currentEmail) !== 0 && emailExists($email)) {
        return ['success' => false, 'error' => 'Email is already in use by another account.'];
    }

    $allowedStatus = ['active', 'inactive'];
    if (!in_array($status, $allowedStatus, true)) {
        $status = 'active';
    }

    $allowedLicense = ['none','learner','provisional','full','overseas'];
    if (!in_array($license, $allowedLicense, true)) {
        $license = 'none';
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "UPDATE users
             SET name = :name,
                 email = :email,
                 phone = :phone,
                 status = :status
             WHERE id = :id AND role = 'student'"
        );
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone !== '' ? $phone : null,
            ':status' => $status,
            ':id' => $studentId,
        ]);

        $stmt = $pdo->prepare(
            "INSERT INTO students (user_id, license_status, address, notes_summary)
             VALUES (:id, :license_status, :address, :notes_summary)
             ON DUPLICATE KEY UPDATE
                 license_status = VALUES(license_status),
                 address = VALUES(address),
                 notes_summary = VALUES(notes_summary)"
        );
        $stmt->execute([
            ':id' => $studentId,
            ':license_status' => $license,
            ':address' => $address !== '' ? $address : null,
            ':notes_summary' => $notes !== '' ? $notes : null,
        ]);

        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/** Delete a student user (cascades to students row). */
function deleteStudent(int $studentId): array {
    $pdo = db();

    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = :id AND role = 'student' LIMIT 1");
    $checkStmt->bindValue(':id', $studentId, PDO::PARAM_INT);
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        return ['success' => false, 'error' => 'Student not found.'];
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'student'");
        $stmt->execute([':id' => $studentId]);
        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = $e->getMessage();
        if ($e instanceof \PDOException && $e->getCode() === '23000') {
            $message = 'Cannot delete student with active references (enrollments, invoices, etc.).';
        }
        return ['success' => false, 'error' => $message];
    }
}

/** Paginated list of instructor users with aggregated stats. */
function getInfoInstructors($limit = 100, $offset = 0): array {
    $pdo = db();
    $limit = max(1, (int) $limit);
    $offset = max(0, (int) $offset);

    $sql = "SELECT
                u.id,
                u.name,
                u.email,
                u.phone,
                u.status,
                u.created_at,
                u.updated_at,
                COALESCE(i.qualification, '') AS qualification,
                COALESCE(i.rating_avg, 0) AS rating_avg,
                COALESCE(ci.course_count, 0) AS course_count
            FROM users u
            LEFT JOIN instructors i ON i.user_id = u.id
            LEFT JOIN (
                SELECT instructor_id, COUNT(*) AS course_count
                FROM course_instructors
                GROUP BY instructor_id
            ) ci ON ci.instructor_id = u.id
            WHERE u.role = 'instructor'
            ORDER BY u.created_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

/** Retrieve full instructor profile with aggregates. */
function getInstructorDetails(int $instructorId): ?array {
    $pdo = db();
    $sql = "SELECT
                u.id,
                u.name,
                u.email,
                u.phone,
                u.status,
                u.created_at,
                u.updated_at,
                COALESCE(i.qualification, '') AS qualification,
                COALESCE(i.rating_avg, 0) AS rating_avg,
                COALESCE(ci.course_count, 0) AS course_count
            FROM users u
            LEFT JOIN instructors i ON i.user_id = u.id
            LEFT JOIN (
                SELECT instructor_id, COUNT(*) AS course_count
                FROM course_instructors
                GROUP BY instructor_id
            ) ci ON ci.instructor_id = u.id
            WHERE u.role = 'instructor' AND u.id = :id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $instructorId, PDO::PARAM_INT);
    $stmt->execute();
    $instructor = $stmt->fetch();

    return $instructor ?: null;
}

/** Update instructor profile (users + instructors tables). */
function updateInstructorInfo(int $instructorId, array $data): array {
    $pdo = db();

    $checkStmt = $pdo->prepare("SELECT email FROM users WHERE id = :id AND role = 'instructor' LIMIT 1");
    $checkStmt->bindValue(':id', $instructorId, PDO::PARAM_INT);
    $checkStmt->execute();
    $currentRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$currentRecord) {
        return ['success' => false, 'error' => 'Instructor not found.'];
    }
    $currentEmail = $currentRecord['email'] ?? '';

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $status = $data['status'] ?? 'active';
    $qualification = trim($data['qualification'] ?? '');
    $rating = $data['rating_avg'] ?? null;

    if ($name === '' || $email === '') {
        return ['success' => false, 'error' => 'Name and email are required.'];
    }

    if (!validateEmail($email)) {
        return ['success' => false, 'error' => 'Invalid email format.'];
    }

    if (strcasecmp($email, (string) $currentEmail) !== 0 && emailExists($email)) {
        return ['success' => false, 'error' => 'Email is already in use by another account.'];
    }

    $allowedStatus = ['active', 'inactive'];
    if (!in_array($status, $allowedStatus, true)) {
        $status = 'active';
    }

    $ratingValue = null;
    if ($rating !== null && $rating !== '') {
        $ratingValue = (float) $rating;
        if ($ratingValue < 0) {
            $ratingValue = 0;
        }
        if ($ratingValue > 5) {
            $ratingValue = 5;
        }
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "UPDATE users
             SET name = :name,
                 email = :email,
                 phone = :phone,
                 status = :status
             WHERE id = :id AND role = 'instructor'"
        );
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone !== '' ? $phone : null,
            ':status' => $status,
            ':id' => $instructorId,
        ]);

        $stmt = $pdo->prepare(
            "INSERT INTO instructors (user_id, qualification, rating_avg)
             VALUES (:id, :qualification, :rating)
             ON DUPLICATE KEY UPDATE
                 qualification = VALUES(qualification),
                 rating_avg = VALUES(rating_avg)"
        );
        $stmt->execute([
            ':id' => $instructorId,
            ':qualification' => $qualification !== '' ? $qualification : null,
            ':rating' => $ratingValue !== null ? $ratingValue : null,
        ]);

        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/** Delete instructor user record. */
function deleteInstructor(int $instructorId): array {
    $pdo = db();

    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = :id AND role = 'instructor' LIMIT 1");
    $checkStmt->bindValue(':id', $instructorId, PDO::PARAM_INT);
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        return ['success' => false, 'error' => 'Instructor not found.'];
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'instructor'");
        $stmt->execute([':id' => $instructorId]);
        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = $e->getMessage();
        if ($e instanceof \PDOException && $e->getCode() === '23000') {
            $message = 'Cannot delete instructor with active references (courses, lessons, etc.).';
        }
        return ['success' => false, 'error' => $message];
    }
}

/** Upcoming lessons for a student (defensive if tables missing) */
function getUpcomingLessons(int $studentId,int $limit=5):array{
    $pdo=db();
    $sql="SELECT s.id,s.start_time,s.status,stu.name AS student_name,inst.name AS instructor_name,v.reg_number AS vehicle_reg
          FROM schedule s
          LEFT JOIN users stu ON stu.id=s.student_id
          LEFT JOIN users inst ON inst.id=s.instructor_id
          LEFT JOIN vehicles v ON v.id=s.vehicle_id
          WHERE s.student_id=:sid AND s.start_time>=NOW()
          ORDER BY s.start_time ASC LIMIT :lim";
    try{ $st=$pdo->prepare($sql);
         $st->bindValue(':sid',$studentId,PDO::PARAM_INT);
         $st->bindValue(':lim',$limit,PDO::PARAM_INT);
         $st->execute(); return $st->fetchAll(); 
        }catch(Exception $e){ 
            return []; 
        }
}

/** Student supplemental record by user_id (may return empty array) */
function getStudentByUserId(int $userId):array{
    $pdo=db();
    try{ $st=$pdo->prepare("SELECT * FROM students WHERE user_id=? LIMIT 1");
        $st->execute([$userId]);
        $r=$st->fetch(); return $r?:[]; 
    }catch(Exception $e){ 
        return []; 
    }
}

/** Generic user creation (admin|instructor|student); creates students row if needed */
function createUserWithRole($data){
    $pdo=db();
    $allowed=['admin','instructor','student'];
    if(!in_array($data['role'],$allowed)) return ['success'=>false,'error'=>'Invalid role. Only admin, instructor, student are allowed.'];
    try{ $pdo->beginTransaction();
        $st=$pdo->prepare("INSERT INTO users (role,name,email,phone,dob,password_hash,status) VALUES (?,?,?,?,?,?, 'active')");
        $full=trim($data['full_name']);
        $hash=password_hash($data['password'],PASSWORD_DEFAULT);
        $role=$data['role'];
        $st->execute([$role,$full,$data['email'],$data['phone']??null,$data['dob']??null,$hash]);
        $uid=(int)$pdo->lastInsertId();
        if($role==='student'){
            $st=$pdo->prepare("INSERT INTO students (user_id,license_status,notes_summary) VALUES (?, 'none', ?)");
            $notes='Test student account created via test interface';
            $st->execute([$uid,$notes]);
        }
        $pdo->commit();
        return ['success'=>true,'user_id'=>$uid,'role'=>$role];
    }catch(Exception $e){ 
        $pdo->rollBack(); 
        return ['success'=>false,'error'=>$e->getMessage()]; 
    }
}
}