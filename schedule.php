<?php 
$title = 'Schedule'; 
require_once 'config/connect_db.php';
require_once 'includes/schedule_functions.php';

// ----------------------------------------------------
// 1. Logic Phân quyền truy cập 
// ----------------------------------------------------
safeSessionStart();
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$currentUser = getCurrentUser();
$role = $currentUser['role'];

// Chỉ cho phép ADMIN truy cập trang này
if ($role !== 'admin') {
    http_response_code(403);
    exit('Access Denied: Only Admin can view the dashboard.');
}


$lessons = getAllLessons();
$instructors = getAllInstructors();
$vehicles = getAllVehicles();

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
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?? 'Origin Driving School' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="assets/css/theme_green.css" rel="stylesheet">
    <link rel="icon" href="data:,">
  </head>
  <body>
    <?php include __DIR__.'/partials/header.php'; ?>
    <main class="container my-4">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Home</a></li><li class="breadcrumb-item active">Schedule</li></ol>
      </nav>

      <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['success_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
      <?php endif; ?>
      <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['error_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
      <?php endif; ?>

      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h1 class="h3 mb-0">All Lessons</h1>
          <!-- The "+ Book Lesson" button can be re-enabled when the booking functionality is built -->
          <!-- <a href="#" class="btn btn-primary">+ Book Lesson</a> -->
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Date & Time</th>
                  <th>Course</th>
                  <th>Student</th>
                  <th>Instructor</th>
                  <th>Vehicle</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($lessons)): ?>
                    <tr><td colspan="7" class="text-center">No lessons scheduled.</td></tr>
                <?php else: ?>
                    <?php foreach ($lessons as $lesson): ?>
                    <tr>
                      <td><?= htmlspecialchars($lesson['id']) ?></td>
                      <td><?= htmlspecialchars(date('d/m/Y', strtotime($lesson['day_booking']))) ?> <?= htmlspecialchars(ucfirst($lesson['time_of_day'])) ?></td>
                      <td><?= htmlspecialchars($lesson['title']) ?></td>
                      <td><?= htmlspecialchars($lesson['student_name']) ?></td>
                      <td><?= htmlspecialchars($lesson['instructor_name']) ?></td>
                      <td><?= htmlspecialchars($lesson['vehicle_plate'] ?? 'N/A') ?></td>
                      <td><span class="badge bg-<?= get_status_badge_class($lesson['status']) ?>"><?= htmlspecialchars(ucfirst($lesson['status'])) ?></span></td>
                      <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                data-bs-toggle="modal" data-bs-target="#editLessonModal"
                                data-lesson-id="<?= $lesson['id'] ?>"
                                data-day-booking="<?= $lesson['day_booking'] ?>"
                                data-time-of-day="<?= $lesson['time_of_day'] ?>"
                                data-instructor-id="<?= $lesson['instructor_id'] ?>"
                                data-vehicle-id="<?= $lesson['vehicle_id'] ?>"
                                data-status="<?= $lesson['status'] ?>">
                          <i class="bi bi-pencil-fill"></i> Edit
                        </button>
                        <?php if ($lesson['status'] !== 'cancelled'): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal" data-bs-target="#cancelLessonModal"
                                data-lesson-id="<?= $lesson['id'] ?>">
                          <i class="bi bi-x-circle-fill"></i> Cancel
                        </button>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>

    <!-- Edit Lesson Modal -->
    <div class="modal fade" id="editLessonModal" tabindex="-1" aria-labelledby="editLessonModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form action="handlers/schedule_handler.php" method="POST">
            <div class="modal-header">
              <h5 class="modal-title" id="editLessonModalLabel">Edit Lesson</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="lesson_id" id="edit_lesson_id">
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="edit_day_booking" class="form-label">Date</label>
                  <input type="date" class="form-control" id="edit_day_booking" name="day_booking" required>
                </div>
                <div class="col-md-6">
                  <label for="edit_time_of_day" class="form-label">Time of Day</label>
                  <select class="form-select" id="edit_time_of_day" name="time_of_day" required>
                    <option value="morning">Morning</option>
                    <option value="afternoon">Afternoon</option>
                    <option value="evening">Evening</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="edit_instructor_id" class="form-label">Instructor</label>
                  <select class="form-select" id="edit_instructor_id" name="instructor_id" required>
                    <?php foreach ($instructors as $instructor): ?>
                      <option value="<?= $instructor['id'] ?>"><?= htmlspecialchars($instructor['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="edit_vehicle_id" class="form-label">Vehicle</label>
                  <select class="form-select" id="edit_vehicle_id" name="vehicle_id">
                    <option value="">Not Assigned</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                      <option value="<?= $vehicle['id'] ?>"><?= htmlspecialchars($vehicle['plate_no'] . ' - ' . $vehicle['model']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12">
                  <label for="edit_status" class="form-label">Status</label>
                  <select class="form-select" id="edit_status" name="status" required>
                    <option value="scheduling">Scheduling</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" name="update_lesson" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Cancel Lesson Modal -->
    <div class="modal fade" id="cancelLessonModal" tabindex="-1" aria-labelledby="cancelLessonModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="handlers/schedule_handler.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelLessonModalLabel">Confirm Cancellation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to cancel this lesson? This action cannot be undone and will also update the related invoice.</p>
                        <input type="hidden" name="lesson_id" id="cancel_lesson_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep It</button>
                        <button type="submit" name="cancel_lesson" class="btn btn-danger">Yes, Cancel Lesson</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__.'/partials/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var editLessonModal = document.getElementById('editLessonModal');
        editLessonModal.addEventListener('show.bs.modal', function (event) {
          var button = event.relatedTarget;
          var lessonId = button.getAttribute('data-lesson-id');
          var dayBooking = button.getAttribute('data-day-booking');
          var timeOfDay = button.getAttribute('data-time-of-day');
          var instructorId = button.getAttribute('data-instructor-id');
          var vehicleId = button.getAttribute('data-vehicle-id');
          var status = button.getAttribute('data-status');

          var modal = this;
          modal.querySelector('#edit_lesson_id').value = lessonId;
          modal.querySelector('#edit_day_booking').value = dayBooking;
          modal.querySelector('#edit_time_of_day').value = timeOfDay;
          modal.querySelector('#edit_instructor_id').value = instructorId;
          modal.querySelector('#edit_vehicle_id').value = vehicleId;
          modal.querySelector('#edit_status').value = status;
        });

        var cancelLessonModal = document.getElementById('cancelLessonModal');
        cancelLessonModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var lessonId = button.getAttribute('data-lesson-id');
            var modal = this;
            modal.querySelector('#cancel_lesson_id').value = lessonId;
        });
      });
    </script>
  </body>
</html>
