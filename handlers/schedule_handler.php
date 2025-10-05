<?php
session_start();
require_once '../config/connect_db.php';
require_once '../includes/schedule_functions.php';
require_once '../includes/notification_functions.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lesson'])) {
    $lesson_id = (int)$_POST['lesson_id'];
    $day_booking = $_POST['day_booking'];
    $time_of_day = $_POST['time_of_day'];
    $instructor_id = (int)$_POST['instructor_id'];
    $vehicle_id = !empty($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null;
    $status = $_POST['status'];

    // Fetch original lesson to get student_id for notification
    $pdo = db();
    $original_lesson_stmt = $pdo->prepare("SELECT student_id FROM lessons WHERE id = ?");
    $original_lesson_stmt->execute([$lesson_id]);
    $original_lesson = $original_lesson_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$original_lesson) {
        $_SESSION['error_message'] = "Lesson not found.";
        header("Location: ../schedule.php");
        exit();
    }
    $student_id = $original_lesson['student_id'];

    $data = [
        'day_booking' => $day_booking,
        'time_of_day' => $time_of_day,
        'instructor_id' => $instructor_id,
        'vehicle_id' => $vehicle_id,
        'status' => $status,
    ];

    if (updateLessonAndInvoice($lesson_id, $data)) {
        $_SESSION['success_message'] = "Lesson updated successfully.";

        // Create notifications
        $title = "Lesson Updated";
        $content_student = "Your lesson id $lesson_id on $day_booking has been updated. Please check the schedule for details.";
        $content_instructor = "The lesson id $lesson_id with student id $student_id on $day_booking has been updated. Please check the schedule.";

        createNotification($student_id, $title, $content_student);
        createNotification($instructor_id, $title, $content_instructor);

    } else {
        $_SESSION['error_message'] = "Failed to update lesson.";
    }

    header("Location: ../schedule.php");
    exit();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_lesson'])) {
    $lesson_id = (int)$_POST['lesson_id'];
    
    // Fetch lesson details for notification
    $pdo = db();
    $lesson_stmt = $pdo->prepare("SELECT student_id, instructor_id, day_booking FROM lessons WHERE id = ?");
    $lesson_stmt->execute([$lesson_id]);
    $lesson = $lesson_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lesson) {
        $_SESSION['error_message'] = "Lesson not found.";
        header("Location: ../schedule.php");
        exit();
    }

    // The only data to update is the status
    $data = [
        'status' => 'cancelled',
        // Pass original data to avoid them being nulled
        'day_booking' => $lesson['day_booking'],
        'time_of_day' => $lesson['time_of_day'] ?? 'morning', // Default if not set
        'instructor_id' => $lesson['instructor_id'],
        'vehicle_id' => $lesson['vehicle_id'] ?? null,
    ];


    if (updateLessonAndInvoice($lesson_id, $data)) {
        $_SESSION['success_message'] = "Lesson cancelled successfully.";

        // Create notifications
        $title = "Lesson Cancelled";
        $content = "Your lesson id $lesson_id on {$lesson['day_booking']} has been cancelled.";
        createNotification($lesson['student_id'], $title, $content);
        createNotification($lesson['instructor_id'], $title, $content);

    } else {
        $_SESSION['error_message'] = "Failed to cancel lesson.";
    }
    
    header("Location: ../schedule.php");
    exit();
}

header("Location: ../schedule.php");
exit();
