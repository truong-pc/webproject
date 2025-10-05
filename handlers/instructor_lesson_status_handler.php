<?php
require_once __DIR__ . '/../config/connect_db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/schedule_functions.php';
require_once __DIR__ . '/../includes/notification_functions.php';

safeSessionStart();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /webproject/instructor/schedule.php');
    exit();
}

if (!isLoggedIn() || empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header('Location: /webproject/login.php?error=unauthorized');
    exit();
}

$instructorId = (int)$_SESSION['user_id'];
$lessonId = isset($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : 0;
$newStatus = isset($_POST['status']) ? trim($_POST['status']) : '';

if ($lessonId <= 0 || $newStatus === '') {
    $_SESSION['error_message'] = 'Invalid request.';
    header('Location: /webproject/instructor/schedule.php');
    exit();
}

$pdo = db();
// Fetch lesson to get student id for notifications & verify existence
$lessonStmt = $pdo->prepare('SELECT student_id, instructor_id, day_booking FROM lessons WHERE id = :id LIMIT 1');
$lessonStmt->execute([':id' => $lessonId]);
$lesson = $lessonStmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) {
    $_SESSION['error_message'] = 'Lesson not found.';
    header('Location: /webproject/instructor/schedule.php');
    exit();
}

if ((int)$lesson['instructor_id'] !== $instructorId) {
    $_SESSION['error_message'] = 'You are not allowed to modify this lesson.';
    header('Location: /webproject/instructor/schedule.php');
    exit();
}

if (updateLessonStatus($lessonId, $instructorId, $newStatus)) {
    $_SESSION['success_message'] = 'Lesson status updated.';

    // Notifications
    $title = 'Lesson Status Changed';
    $contentStudent = "Your lesson ID {$lessonId} on {$lesson['day_booking']} status changed to {$newStatus}.";
    $contentInstructor = "You changed status of lesson ID {$lessonId} to {$newStatus}.";
    createNotification((int)$lesson['student_id'], $title, $contentStudent);
    createNotification($instructorId, $title, $contentInstructor);
} else {
    $_SESSION['error_message'] = 'Failed to update lesson status.';
}

header('Location: /webproject/instructor/schedule.php');
exit();
