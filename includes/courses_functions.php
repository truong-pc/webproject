<?php
/**
 * Functions for managing courses and their instructor assignments.
 */

/**
 * Fetches a list of all courses with associated branch and instructor details.
 * Instructors are returned as a comma-separated string and a JSON array of IDs.
 *
 * @return array An array of course records.
 */
function getCourses(): array {
    $pdo = db();
    try {
        $sql = "SELECT 
                    c.*, 
                    b.name AS branch_name,
                    GROUP_CONCAT(DISTINCT i.name ORDER BY i.name SEPARATOR ', ') AS instructor_names,
                    CONCAT('[', GROUP_CONCAT(DISTINCT ci.instructor_id), ']') AS instructor_ids
                FROM courses c
                LEFT JOIN branches b ON c.branch_id = b.id
                LEFT JOIN course_instructors ci ON c.id = ci.course_id
                LEFT JOIN users i ON ci.instructor_id = i.id AND i.role = 'instructor'
                GROUP BY c.id
                ORDER BY c.created_at DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // In a real app, log this error
        return [];
    }
}

/**
 * Adds a new course and assigns instructors.
 *
 * @param array $data Associative array containing course data:
 *                    'title', 'price', 'num_lessons', 'description', 'branch_id',
 *                    and 'instructors' (an array of instructor user_ids).
 * @return array Result array with 'success' (bool) and 'error' (string, if any).
 */
function addCourse(array $data): array {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // 1. Insert into courses table
        $sql = "INSERT INTO courses (title, price, num_lessons, description, branch_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['title'] ?? '',
            $data['price'] ?? 0.00,
            $data['num_lessons'] ?? 1,
            $data['description'] ?? '',
            $data['branch_id'] ?: null
        ]);
        $courseId = $pdo->lastInsertId();

        // 2. Assign instructors
        if (!empty($data['instructors']) && is_array($data['instructors'])) {
            $sql = "INSERT INTO course_instructors (course_id, instructor_id) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            foreach ($data['instructors'] as $instructorId) {
                $stmt->execute([$courseId, (int)$instructorId]);
            }
        }

        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Updates an existing course and its instructor assignments.
 *
 * @param int $courseId The ID of the course to update.
 * @param array $data Associative array with course data (see addCourse).
 * @return array Result array.
 */
function updateCourse(int $courseId, array $data): array {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // 1. Update the main course details
        $sql = "UPDATE courses SET title = ?, price = ?, num_lessons = ?, description = ?, branch_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['title'] ?? '',
            $data['price'] ?? 0.00,
            $data['num_lessons'] ?? 1,
            $data['description'] ?? '',
            $data['branch_id'] ?: null,
            $courseId
        ]);

        // 2. Clear existing instructor assignments for this course
        $stmt = $pdo->prepare("DELETE FROM course_instructors WHERE course_id = ?");
        $stmt->execute([$courseId]);

        // 3. Add new instructor assignments
        if (!empty($data['instructors']) && is_array($data['instructors'])) {
            $sql = "INSERT INTO course_instructors (course_id, instructor_id) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            foreach ($data['instructors'] as $instructorId) {
                $stmt->execute([$courseId, (int)$instructorId]);
            }
        }

        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Deletes a course.
 * The database's ON DELETE CASCADE constraint will handle related `course_instructors`.
 *
 * @param int $courseId The ID of the course to delete.
 * @return array Result array.
 */
function deleteCourse(int $courseId): array {
    $pdo = db();
    try {
        $sql = "DELETE FROM courses WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$courseId]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
