<?php
// logic tìm kiếm giảng viên theo từng khóa học
// handlers/get_instructors_ajax.php 
require_once __DIR__ . '/../includes/schedule_functions.php';

header('Content-Type: application/json');

if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid course ID.']);
    exit;
}

$courseId = (int)$_GET['course_id'];
$instructors = getInstructorsForCourse($courseId);

if (!empty($instructors)) {
    echo json_encode(['success' => true, 'data' => $instructors]);
} else {
    echo json_encode(['success' => false, 'message' => 'No instructors found for this course.']);
}
