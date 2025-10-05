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
    if (!empty($data['vehicle_id']) && !checkVehicleIsUsed($data['vehicle_id'], $data['day_booking'], $data['time_of_day'])) {
        return ['success' => false, 'message' => 'The selected vehicle is not available at that time. Please choose a different vehicle.'];
    }

    try {
        $pdo->beginTransaction();

        // 3. Insert the new lesson
        $lessonSql = "INSERT INTO lessons (student_id, course_id, instructor_id, branch_id, vehicle_id, day_booking, time_of_day, status)
                      VALUES (:student_id, :course_id, :instructor_id, :branch_id, :vehicle_id, :day_booking, :time_of_day, 'scheduled')";
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
            ':balance' => 0.00,
            ':notes' => 'Invoice for lesson booking ID: ' . $lessonId
        ]);

        $pdo->commit();
        return ['success' => true, 'message' => 'Lesson booked successfully and invoice has been generated.'];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error booking lesson: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while booking the lesson. Please try again.'];
    }
}


/**
 * Checks if a vehicle is available at a specific date and time.
 *
 * @param int|null $vehicleId
 * @param string $date
 * @param string $timeOfDay
 * @return boolean
 */
function checkVehicleIsUsed(?int $vehicleId, string $date, string $timeOfDay): bool
{
    if ($vehicleId === null) {
        return true; // No vehicle assigned, so it's "available"
    }
    $pdo = db();
    $sql = "SELECT COUNT(*) FROM lessons
            WHERE vehicle_id = :vehicleId
            AND day_booking = :day_booking
            AND time_of_day = :time_of_day
            AND status NOT IN ('cancelled', 'completed')";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':vehicleId' => $vehicleId,
            ':day_booking' => $date,
            ':time_of_day' => $timeOfDay
        ]);
        return $stmt->fetchColumn() == 0;
    } catch (Exception $e) {
        error_log("Error checking vehicle availability: " . $e->getMessage());
        return false; // Fail safe
    }
}


/**
 * Fetches all lessons.
 *
 * @return array An array of all lessons with details.
 */
function getAllLessons(): array
{
    $pdo = db();
    $sql = "SELECT 
                l.id, 
                l.day_booking, 
                l.time_of_day, 
                l.status, 
                s.name as student_name, 
                i.name as instructor_name, 
                v.plate_no as vehicle_plate,
                l.student_id,
                l.instructor_id,
                l.vehicle_id,
                l.course_id,
                c.title
            FROM lessons l
            JOIN users s ON l.student_id = s.id
            JOIN users i ON l.instructor_id = i.id
            LEFT JOIN vehicles v ON l.vehicle_id = v.id
            LEFT JOIN courses c ON l.course_id = c.id
            ORDER BY l.day_booking DESC, l.time_of_day ASC";
    try {
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching all lessons: " . $e->getMessage());
        return [];
    }
}

/**
 * Updates a lesson and its corresponding invoice.
 *
 * @param int $lesson_id
 * @param array $data
 * @return bool True on success, false on failure.
 */
function updateLessonAndInvoice(int $lesson_id, array $data): bool
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Update lesson
        $sql = "UPDATE lessons SET 
                    day_booking = :day_booking, 
                    time_of_day = :time_of_day, 
                    instructor_id = :instructor_id, 
                    vehicle_id = :vehicle_id, 
                    status = :status 
                WHERE id = :lesson_id";
        $stmt = $pdo->prepare($sql);
        
        // Handle null vehicle_id
        $vehicle_id = !empty($data['vehicle_id']) ? $data['vehicle_id'] : null;

        $params = [
            ':day_booking' => $data['day_booking'],
            ':time_of_day' => $data['time_of_day'],
            ':instructor_id' => $data['instructor_id'],
            ':vehicle_id' => $vehicle_id,
            ':status' => $data['status'],
            ':lesson_id' => $lesson_id
        ];
        $stmt->execute($params);

        // If lesson is cancelled, find and cancel the corresponding invoice
        if ($data['status'] === 'cancelled') {
            $lesson_course_stmt = $pdo->prepare("SELECT course_id, student_id FROM lessons WHERE id = ?");
            $lesson_course_stmt->execute([$lesson_id]);
            $lesson_info = $lesson_course_stmt->fetch(PDO::FETCH_ASSOC);

            if ($lesson_info) {
                $invoice_sql = "UPDATE invoices SET status = 'cancelled' 
                                WHERE student_id = ? AND course_id = ? AND status != 'paid'";
                $invoice_stmt = $pdo->prepare($invoice_sql);
                $invoice_stmt->execute([$lesson_info['student_id'], $lesson_info['course_id']]);
            }
        } elseif ($data['status'] !== 'cancelled') {
            // If the lesson status is changed to something other than 'cancelled' (e.g., rescheduled),
            // ensure the corresponding invoice is set to 'pending' so it can be processed.
            $lesson_course_stmt = $pdo->prepare("SELECT course_id, student_id FROM lessons WHERE id = ?");
            $lesson_course_stmt->execute([$lesson_id]);
            $lesson_info = $lesson_course_stmt->fetch(PDO::FETCH_ASSOC);

            if ($lesson_info) {
                // Find the related invoice and update its status to 'pending'
                // This applies if the invoice was previously 'cancelled' or another state, but not yet 'paid'.
                $invoice_sql = "UPDATE invoices SET status = 'pending' 
                                WHERE student_id = ? AND course_id = ? AND status != 'paid'";
                $invoice_stmt = $pdo->prepare($invoice_sql);
                $invoice_stmt->execute([$lesson_info['student_id'], $lesson_info['course_id']]);
            }
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error updating lesson and invoice: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetches all active instructors.
 *
 * @return array An array of active instructors.
 */
function getAllInstructors(): array
{
    $pdo = db();
    try {
        $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'instructor' AND status = 'active'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching all instructors: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetches all active vehicles.
 *
 * @return array An array of active vehicles.
 */
function getAllVehicles(): array
{
    $pdo = db();
    try {
        $stmt = $pdo->query("SELECT id, plate_no, model FROM vehicles WHERE status = 'active'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching all vehicles: " . $e->getMessage());
        return [];
    }
}
?>