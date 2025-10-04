<?php
require_once __DIR__ . '/../config/connect_db.php';
require_once __DIR__ . '/functions.php';
/**
 * Schedule-related database functions.
 */

/**
 * Fetches all scheduled lessons for a specific student.
 *
 * @param int $studentId The ID of the student.
 * @return array An array of lesson details.
 */
function getStudentLessons(int $studentId): array
{
    $pdo = db();
    $sql = "SELECT
                l.id,
                l.day_booking,
                l.time_of_day,
                l.status,
                c.title AS course_title,
                i.name AS instructor_name,
                v.plate_no AS vehicle_plate,
                b.name AS branch_name
            FROM
                lessons l
            JOIN
                courses c ON l.course_id = c.id
            JOIN
                users i ON l.instructor_id = i.id AND i.role = 'instructor'
            LEFT JOIN
                vehicles v ON l.vehicle_id = v.id
            LEFT JOIN
                branches b ON l.branch_id = b.id
            WHERE
                l.student_id = :studentId
            ORDER BY
                l.day_booking ASC, l.time_of_day ASC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':studentId', $studentId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // In a real app, you'd log this error.
        error_log("Error fetching student lessons: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetches all available courses for the booking form.
 *
 * @return array
 */
function getAllCoursesForBooking(): array
{
    $pdo = db();
    // This function now fetches all courses, not just those the student is enrolled in.
    // This allows a student to book their first lesson for any available course.
    $sql = "SELECT id, title FROM courses ORDER BY title ASC";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching all courses for booking: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetches all instructors assigned to a specific course.
 *
 * @param int $courseId
 * @return array
 */
function getInstructorsForCourse(int $courseId): array
{
    $pdo = db();
    $sql = "SELECT u.id, u.name
            FROM users u
            JOIN course_instructors ci ON u.id = ci.instructor_id
            WHERE u.role = 'instructor' AND u.status = 'active' AND ci.course_id = :courseId
            ORDER BY u.name ASC";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':courseId', $courseId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching instructors for course: " . $e->getMessage());
        return [];
    }
}

/**
 * Checks if an instructor is already booked for a specific date and time.
 *
 * @param int $instructorId
 * @param string $date 'YYYY-MM-DD'
 * @param string $timeOfDay 'morning', 'afternoon', 'evening'
 * @return bool True if available, false if booked.
 */
function checkInstructorAvailability(int $instructorId, string $date, string $timeOfDay): bool
{
    $pdo = db();
    $sql = "SELECT COUNT(*) FROM lessons
            WHERE instructor_id = :instructorId
            AND day_booking = :day_booking
            AND time_of_day = :time_of_day
            AND status != 'cancelled'";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':instructorId' => $instructorId,
            ':day_booking' => $date,
            ':time_of_day' => $timeOfDay
        ]);
        return $stmt->fetchColumn() == 0;
    } catch (Exception $e) {
        error_log("Error checking instructor availability: " . $e->getMessage());
        return false; // Fail safe: assume not available on error
    }
}

/**
 * Books a new lesson and creates a corresponding invoice.
 *
 * @param array $data Expected keys: student_id, course_id, instructor_id, branch_id, day_booking, time_of_day
 * @return array ['success' => bool, 'message' => string]
 */
function bookLesson(array $data): array
{
    $pdo = db();

    // 1. Validate instructor availability
    if (!checkInstructorAvailability($data['instructor_id'], $data['day_booking'], $data['time_of_day'])) {
        return ['success' => false, 'message' => 'The selected instructor is not available at that time. Please choose a different time or instructor.'];
    }

    // 2. Validate vehicle availability
    if (!checkVehicleAvailability($data['vehicle_id'], $data['day_booking'], $data['time_of_day'])) {
        return ['success' => false, 'message' => 'The selected vehicle is not available at that time. Please choose a different vehicle.'];
    }

    try {
        $pdo->beginTransaction();

        // 3. Insert the new lesson
        $lessonSql = "INSERT INTO lessons (student_id, course_id, instructor_id, branch_id, vehicle_id, day_booking, time_of_day, status)
                      VALUES (:student_id, :course_id, :instructor_id, :branch_id, :vehicle_id, :day_booking, :time_of_day, 'scheduling')";
        $lessonStmt = $pdo->prepare($lessonSql);
        $lessonStmt->execute([
            ':student_id' => $data['student_id'],
            ':course_id' => $data['course_id'],
            ':instructor_id' => $data['instructor_id'],
            ':branch_id' => $data['branch_id'],
            ':vehicle_id' => $data['vehicle_id'],
            ':day_booking' => $data['day_booking'],
            ':time_of_day' => $data['time_of_day'],
        ]);
        $lessonId = $pdo->lastInsertId();

        // 4. Fetch course price
        $courseSql = "SELECT price FROM courses WHERE id = :course_id";
        $courseStmt = $pdo->prepare($courseSql);
        $courseStmt->execute([':course_id' => $data['course_id']]);
        $course = $courseStmt->fetch(PDO::FETCH_ASSOC);

        if (!$course) {
            throw new Exception("Course not found.");
        }
        $coursePrice = $course['price'];

        // 5. Create a new invoice
        $invoiceSql = "INSERT INTO invoices (student_id, course_id, issue_date, due_date, total, balance, status, notes)
                       VALUES (:student_id, :course_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), :total, :balance, 'pending', :notes)";
        $invoiceStmt = $pdo->prepare($invoiceSql);
        $invoiceStmt->execute([
            ':student_id' => $data['student_id'],
            ':course_id' => $data['course_id'],
            ':total' => $coursePrice,
            ':balance' => $coursePrice,
            ':notes' => 'Invoice automatically generated for new lesson booking (Lesson ID: ' . $lessonId . ')'
        ]);

        $pdo->commit();
        return ['success' => true, 'message' => 'Lesson booked successfully and invoice has been generated.'];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error booking lesson: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while booking the lesson. Please try again. Details: ' . $e->getMessage()];
    }
}
?>