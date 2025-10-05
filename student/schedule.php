<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/schedule_functions.php';
require_once __DIR__ . '/../includes/vehicels_functions.php';

safeSessionStart();

// Authentication: Ensure the user is a logged-in student
if (!isLoggedIn() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: /webproject/login.php?error=unauthorized");
    exit();
}

$studentId = $_SESSION['user_id'];
$lessons = getStudentLessons($studentId);

// Data for the booking modal form
$allCoursesForBooking = getAllCoursesForBooking();
$allBranches = getBranches();
$availableVehicles = getAvailableVehicles();

// Helper to get badge change type button (color) class  for status
function get_status_badge_class($status) {
        switch ($status) {
            case 'scheduled': return 'info';
            case 'completed': return 'success';
            case 'cancelled': return 'danger';
            case 'scheduling': return 'warning';
            default: return 'secondary';
        }
    }

// Handle form submission
$bookingResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_lesson'])) {
    // Basic validation
    $requiredFields = ['course_id', 'instructor_id', 'branch_id', 'day_booking', 'time_of_day', 'vehicle_id'];
    $errors = [];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "The " . str_replace('_', ' ', $field) . " field is required.";
        }
    }

    if (empty($errors)) {
        $bookingData = [
            'student_id' => $studentId,
            'course_id' => (int)$_POST['course_id'],
            'instructor_id' => (int)$_POST['instructor_id'],
            'branch_id' => (int)$_POST['branch_id'],
            'vehicle_id' => (int)$_POST['vehicle_id'],
            'day_booking' => $_POST['day_booking'],
            'time_of_day' => $_POST['time_of_day'],
        ];
        $bookingResult = bookLesson($bookingData);
        // Refresh lessons list if booking was successful
        if ($bookingResult['success']) {
            $lessons = getStudentLessons($studentId);
        }
    } else {
        $bookingResult = ['success' => false, 'message' => implode('<br>', $errors)];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Origin Driving School</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/webproject/assets/css/theme_green.css">
    <link rel="stylesheet" href="/webproject/assets/css/view_courses.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .select2-container {
            width: 100% !important;
        }
    </style>
</head>

<body class="courses-page">
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <div class="container mt-5">
        <div class="card" style="min-height: 350px;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">My Scheduled Lessons</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bookLessonModal">
                    <i class="fas fa-plus-circle me-2"></i>Book New Lesson
                </button>
            </div>
            <div class="card-body">
                <?php if ($bookingResult): ?>
                    <div class="alert alert-<?= $bookingResult['success'] ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                        <?= $bookingResult['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-info">
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Time of Day</th>
                                <th>Course</th>
                                <th>Instructor</th>
                                <th>Vehicle</th>
                                <th>Branch</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lessons)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">You have no scheduled lessons.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($lessons as $lesson): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($lesson['id']) ?></td>
                                        <td><?= htmlspecialchars($lesson['day_booking']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($lesson['time_of_day'])) ?></td>
                                        <td><?= htmlspecialchars($lesson['course_title']) ?></td>
                                        <td><?= htmlspecialchars($lesson['instructor_name']) ?></td>
                                        <td><?= htmlspecialchars($lesson['vehicle_plate'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($lesson['branch_name'] ?? 'N/A') ?></td>
                                        <td><span class="badge bg-<?= get_status_badge_class($lesson['status']) ?>"><?= htmlspecialchars(ucfirst($lesson['status'])) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookLessonModal" tabindex="-1" aria-labelledby="bookLessonModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookLessonModalLabel">Book a New Lesson</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bookingForm" action="schedule.php" method="POST">
                        <input type="hidden" name="book_lesson" value="1">
                        <div class="row mb-3">
                            <label for="course_id" class="col-sm-3 col-form-label">Course</label>
                            <div class="col-sm-8">
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="" selected disabled>Select a course...</option>
                                    <?php foreach ($allCoursesForBooking as $course): ?>
                                        <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="instructor_id" class="col-sm-3 col-form-label">Instructor</label>
                            <div class="col-sm-8">
                                <select class="form-select" id="instructor_id" name="instructor_id" required disabled>
                                    <option value="">Select a course first</option>
                                </select>
                                <div id="instructor-loader" class="spinner-border spinner-border-sm text-primary mt-2" role="status" style="display: none;">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="day_booking" class="col-sm-3 col-form-label">Date</label>
                            <div class="col-sm-6">
                                <input type="date" class="form-control" id="day_booking" name="day_booking" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="time_of_day" class="col-sm-3 col-form-label">Time of Day</label>
                            <div class="col-sm-8">
                                <select class="form-select" id="time_of_day" name="time_of_day" required>
                                    <option value="morning">Morning (8am - 12pm)</option>
                                    <option value="afternoon">Afternoon (1pm - 5pm)</option>
                                    <option value="evening">Evening (6pm - 9pm)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="branch_id" class="col-sm-3 col-form-label">Branch</label>
                            <div class="col-sm-8">
                                <select class="form-select" id="branch_id" name="branch_id" required>
                                    <option value="" selected disabled>Select a branch...</option>
                                    <?php foreach ($allBranches as $branch): ?>
                                        <option value="<?= $branch['id'] ?>"><?= htmlspecialchars($branch['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="vehicle_id" class="col-sm-3 col-form-label">Vehicle</label>
                            <div class="col-sm-8">
                                <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                    <option value="" selected disabled>Select a vehicle...</option>
                                    <?php foreach ($availableVehicles as $vehicle): ?>
                                        <option value="<?= $vehicle['id'] ?>"><?= htmlspecialchars($vehicle['model'] . ' (' . $vehicle['plate_no'] . ')') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div id="form-feedback" class="mt-3"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="bookingForm" class="btn btn-primary">Submit Booking</button>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('#course_id, #instructor_id, #branch_id, #time_of_day, #vehicle_id').select2({
                dropdownParent: $('#bookLessonModal')
            });

            // Set min date for date picker to today
            const today = new Date().toISOString().split('T')[0];
            $('#day_booking').attr('min', today);

            // AJAX call to fetch instructors when a course is selected
            $('#course_id').on('change', function() {
                const courseId = $(this).val();
                const $instructorSelect = $('#instructor_id');
                const $loader = $('#instructor-loader');

                $instructorSelect.prop('disabled', true).html('<option value="">Loading...</option>');
                $loader.show();

                if (!courseId) {
                    $instructorSelect.html('<option value="">Select a course first</option>').prop('disabled', true);
                    $loader.hide();
                    return;
                }

                $.ajax({
                    url: '/webproject/handlers/get_instructors_ajax.php', // We will create this file
                    type: 'GET',
                    data: {
                        course_id: courseId
                    },
                    dataType: 'json',
                    success: function(response) {
                        $instructorSelect.prop('disabled', false).empty();
                        if (response.success && response.data.length > 0) {
                            $instructorSelect.append('<option value="">Select an instructor</option>');
                            $.each(response.data, function(index, instructor) {
                                $instructorSelect.append($('<option>', {
                                    value: instructor.id,
                                    text: instructor.name
                                }));
                            });
                        } else {
                            $instructorSelect.html('<option value="">No instructors available for this course</option>');
                        }
                    },
                    error: function() {
                        $instructorSelect.html('<option value="">Failed to load instructors</option>');
                    },
                    complete: function() {
                        $loader.hide();
                    }
                });
            });
        });
    </script>
</body>

</html>