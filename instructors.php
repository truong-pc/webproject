<?php
$title = 'Instructors';
require_once __DIR__ . '/includes/functions.php';

safeSessionStart();
if (!isLoggedIn()) {
  header('Location: login.php');
  exit;
}

$currentUser = getCurrentUser();
$role = $currentUser['role'];

if ($role !== 'admin') {
  http_response_code(403);
  exit('Access Denied: Only Admin can view instructor information.');
}

$messages = ['success' => [], 'error' => []];
$statusOptions = ['active' => 'Active', 'inactive' => 'Inactive'];
$selectedInstructor = null;
$hasPostErrors = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'update_instructor') {
    $instructorId = (int) ($_POST['instructor_id'] ?? 0);

    $result = updateInstructorInfo($instructorId, [
      'name' => trim($_POST['name'] ?? ''),
      'email' => trim($_POST['email'] ?? ''),
      'phone' => trim($_POST['phone'] ?? ''),
      'status' => $_POST['status'] ?? 'active',
      'qualification' => trim($_POST['qualification'] ?? ''),
      'rating_avg' => $_POST['rating_avg'] ?? null,
    ]);

    if ($result['success'] ?? false) {
      header('Location: instructors.php?msg=updated');
      exit;
    }

  $messages['error'][] = $result['error'] ?? 'Unable to update instructor.';
  $hasPostErrors = true;
    $selectedInstructor = getInstructorDetails($instructorId) ?? ['id' => $instructorId];
    $selectedInstructor = array_merge($selectedInstructor, [
      'name' => trim($_POST['name'] ?? ''),
      'email' => trim($_POST['email'] ?? ''),
      'phone' => trim($_POST['phone'] ?? ''),
      'status' => $_POST['status'] ?? ($selectedInstructor['status'] ?? 'active'),
      'qualification' => trim($_POST['qualification'] ?? ''),
      'rating_avg' => $_POST['rating_avg'] ?? ($selectedInstructor['rating_avg'] ?? null),
    ]);
  } elseif ($action === 'delete_instructor') {
    $instructorId = (int) ($_POST['instructor_id'] ?? 0);
    if ($instructorId <= 0) {
      $messages['error'][] = 'Invalid instructor selected for deletion.';
      $hasPostErrors = true;
    } else {
      $result = deleteInstructor($instructorId);
      if ($result['success'] ?? false) {
        header('Location: instructors.php?msg=deleted');
        exit;
      }
      $messages['error'][] = $result['error'] ?? 'Unable to delete instructor.';
      $hasPostErrors = true;
    }
  }
}

if (!$hasPostErrors && isset($_GET['msg'])) {
  if ($_GET['msg'] === 'updated') {
    $messages['success'][] = 'Instructor record updated successfully.';
  } elseif ($_GET['msg'] === 'deleted') {
    $messages['success'][] = 'Instructor removed successfully.';
  }
}

$instructors = getInfoInstructors(100, 0);

?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $title ?? 'Origin Driving School' ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/theme_green.css" rel="stylesheet">
  <link rel="icon" href="data:,"></link>
</head>

<body>
  <?php include __DIR__ . '/partials/header.php'; ?>

  <div class="pagebar py-3 mb-3">
    <div class="container d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
      <div>
        <h1 class="title h4">Instructor Profiles</h1>
        <p class="subtitle">Manage instructor qualifications, schedules and performance</p>
      </div>
      <div class="actions d-flex gap-2">
        <a href="test_create_account.php" class="btn btn-success">+ Add Instructor</a>
      </div>
    </div>
  </div>

  <div class="toast-container position-fixed top-0 end-0 p-3" id="messageToastContainer" style="z-index: 1080;">
    <?php foreach ($messages['success'] as $index => $message): ?>
      <div class="toast align-items-center text-bg-success border-0 auto-toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4500">
        <div class="d-flex">
          <div class="toast-body">
            <?= htmlspecialchars($message) ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    <?php endforeach; ?>
    <?php foreach ($messages['error'] as $index => $message): ?>
      <div class="toast align-items-center text-bg-danger border-0 auto-toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5500">
        <div class="d-flex">
          <div class="toast-body">
            <?= htmlspecialchars($message) ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <main class="container my-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-3">
          <h5 class="card-title mb-0">Instructor List</h5>
          <small class="text-muted">Showing <?= count($instructors) ?> instructors</small>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Qualification</th>
                <th class="text-center">Rating</th>
                <th class="text-center">Courses</th>
                <th class="text-center">Upcoming Lessons</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($instructors)): ?>
                <tr>
                  <td colspan="9" class="text-center text-muted">No instructors found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($instructors as $index => $instructor): ?>
                  <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($instructor['name']) ?></td>
                    <td><?= htmlspecialchars($instructor['email']) ?></td>
                    <td><?= htmlspecialchars($instructor['phone'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($instructor['qualification'] ?? '—') ?></td>
                    <td class="text-center"><?= $instructor['rating_avg'] !== null ? number_format((float) $instructor['rating_avg'], 1) : '—' ?></td>
                    <td class="text-center"><?= (int) ($instructor['course_count'] ?? 0) ?></td>
                    <td class="text-center"><?= (int) ($instructor['future_lessons'] ?? 0) ?></td>
                    <td class="text-end">
                      <div class="btn-group" role="group">
                        <button
                          type="button"
                          class="btn btn-sm btn-primary"
                          data-action="view-instructor"
                          data-instructor-id="<?= (int) $instructor['id'] ?>"
                          data-instructor-name="<?= htmlspecialchars($instructor['name'], ENT_QUOTES) ?>"
                          data-instructor-email="<?= htmlspecialchars($instructor['email'], ENT_QUOTES) ?>"
                          data-instructor-phone="<?= htmlspecialchars($instructor['phone'] ?? '', ENT_QUOTES) ?>"
                          data-instructor-status="<?= htmlspecialchars($instructor['status'], ENT_QUOTES) ?>"
                          data-instructor-qualification="<?= htmlspecialchars($instructor['qualification'] ?? '', ENT_QUOTES) ?>"
                          data-instructor-rating="<?= htmlspecialchars((string) ($instructor['rating_avg'] ?? ''), ENT_QUOTES) ?>"
                          data-instructor-courses="<?= (int) ($instructor['course_count'] ?? 0) ?>"
                          data-instructor-lessons="<?= (int) ($instructor['future_lessons'] ?? 0) ?>"
                          data-instructor-created="<?= htmlspecialchars($instructor['created_at'] ?? '', ENT_QUOTES) ?>"
                        >View</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-instructor-id="<?= (int) $instructor['id'] ?>" data-instructor-name="<?= htmlspecialchars($instructor['name']) ?>" onclick="confirmDeleteInstructor(this)">Delete</button>
                      </div>
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

  <?php
  $modalInstructor = $selectedInstructor ?? [
    'id' => '',
    'name' => '',
    'email' => '',
    'phone' => '',
    'status' => 'active',
    'qualification' => '',
    'rating_avg' => null,
    'course_count' => 0,
    'future_lessons' => 0,
    'created_at' => null,
  ];
  $modalCreatedAt = $modalInstructor['created_at'] ?? null;
  $modalCreatedDisplay = $modalCreatedAt ? date('d/m/Y', strtotime($modalCreatedAt)) : 'Not recorded';
  ?>

  <div class="modal fade" id="instructorModal" tabindex="-1" aria-labelledby="instructorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <form class="modal-content" method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="instructorModalLabel">Instructor Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="update_instructor">
          <input type="hidden" name="instructor_id" id="modalInstructorId" value="<?= htmlspecialchars((string) $modalInstructor['id']) ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="modalInstructorName" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="modalInstructorName" name="name" value="<?= htmlspecialchars($modalInstructor['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label for="modalInstructorEmail" class="form-label">Email</label>
              <input type="email" class="form-control" id="modalInstructorEmail" name="email" value="<?= htmlspecialchars($modalInstructor['email'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label for="modalInstructorPhone" class="form-label">Phone</label>
              <input type="text" class="form-control" id="modalInstructorPhone" name="phone" value="<?= htmlspecialchars($modalInstructor['phone'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label for="modalInstructorStatus" class="form-label">Account Status</label>
              <select class="form-select" id="modalInstructorStatus" name="status">
                <?php foreach ($statusOptions as $value => $label): ?>
                  <option value="<?= $value ?>" <?= (($modalInstructor['status'] ?? 'active') === $value) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label for="modalInstructorRating" class="form-label">Rating (0-5)</label>
              <input type="number" step="0.1" min="0" max="5" class="form-control" id="modalInstructorRating" name="rating_avg" value="<?= htmlspecialchars($modalInstructor['rating_avg'] !== null ? (float) $modalInstructor['rating_avg'] : '') ?>">
            </div>
            <div class="col-md-12">
              <label for="modalInstructorQualification" class="form-label">Qualification</label>
              <input type="text" class="form-control" id="modalInstructorQualification" name="qualification" value="<?= htmlspecialchars($modalInstructor['qualification'] ?? '') ?>">
            </div>
            <div class="col-12">
              <dl class="row small text-muted mb-0">
                <dt class="col-sm-4">Created</dt>
                <dd class="col-sm-8" id="modalInstructorCreatedDisplay"><?= htmlspecialchars($modalCreatedDisplay) ?></dd>
                <dt class="col-sm-4">Course Assignments</dt>
                <dd class="col-sm-8" id="modalInstructorCoursesDisplay"><?= (int) ($modalInstructor['course_count'] ?? 0) ?></dd>
                <dt class="col-sm-4">Upcoming Lessons</dt>
                <dd class="col-sm-8" id="modalInstructorLessonsDisplay"><?= (int) ($modalInstructor['future_lessons'] ?? 0) ?></dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <?php include __DIR__ . '/partials/footer.php'; ?>

  <form method="post" id="deleteInstructorForm" class="d-none">
    <input type="hidden" name="action" value="delete_instructor">
    <input type="hidden" name="instructor_id" id="deleteInstructorId" value="">
  </form>

  <script>
    function confirmDeleteInstructor(button) {
      const instructorId = button.getAttribute('data-instructor-id');
      const instructorName = button.getAttribute('data-instructor-name');
      if (confirm(`Are you sure you want to delete instructor "${instructorName}"? This action cannot be undone.`)) {
        document.getElementById('deleteInstructorId').value = instructorId;
        document.getElementById('deleteInstructorForm').submit();
      }
    }

    document.addEventListener('DOMContentLoaded', function () {
      const toastElements = Array.from(document.querySelectorAll('.auto-toast'));
      const toastInstances = toastElements.map((element) => new bootstrap.Toast(element));

      const showToasts = () => {
        toastInstances.forEach((instance) => instance.show());
      };

      if (toastInstances.length > 0) {
        showToasts();
      }

      const modalElement = document.getElementById('instructorModal');
      if (!modalElement) {
        return;
      }

      const modalInstance = new bootstrap.Modal(modalElement);
      const fieldRefs = {
        id: modalElement.querySelector('#modalInstructorId'),
        name: modalElement.querySelector('#modalInstructorName'),
        email: modalElement.querySelector('#modalInstructorEmail'),
        phone: modalElement.querySelector('#modalInstructorPhone'),
        status: modalElement.querySelector('#modalInstructorStatus'),
        rating: modalElement.querySelector('#modalInstructorRating'),
        qualification: modalElement.querySelector('#modalInstructorQualification'),
      };
      const statRefs = {
        created: modalElement.querySelector('#modalInstructorCreatedDisplay'),
        courses: modalElement.querySelector('#modalInstructorCoursesDisplay'),
        lessons: modalElement.querySelector('#modalInstructorLessonsDisplay'),
      };

      const formatDate = (value) => {
        if (!value) {
          return 'Not recorded';
        }
        const parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) {
          return value;
        }
        return parsed.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
      };

      const populateModal = (data) => {
        if (!data) {
          return;
        }
        fieldRefs.id.value = data.id ?? '';
        fieldRefs.name.value = data.name ?? '';
        fieldRefs.email.value = data.email ?? '';
        fieldRefs.phone.value = data.phone ?? '';
        fieldRefs.status.value = data.status ?? 'active';
        fieldRefs.rating.value = data.rating ?? '';
        fieldRefs.qualification.value = data.qualification ?? '';

        statRefs.created.textContent = formatDate(data.created);
        statRefs.courses.textContent = Number.parseInt(data.courses ?? 0, 10) || 0;
        statRefs.lessons.textContent = Number.parseInt(data.lessons ?? 0, 10) || 0;
      };

      const openInstructorModal = (data) => {
        populateModal(data);
        modalInstance.show();
      };

      document.querySelectorAll('[data-action="view-instructor"]').forEach((button) => {
        button.addEventListener('click', () => {
          const dataset = button.dataset;
          openInstructorModal({
            id: dataset.instructorId || '',
            name: dataset.instructorName || '',
            email: dataset.instructorEmail || '',
            phone: dataset.instructorPhone || '',
            status: dataset.instructorStatus || 'active',
            rating: dataset.instructorRating || '',
            qualification: dataset.instructorQualification || '',
            courses: dataset.instructorCourses || 0,
            lessons: dataset.instructorLessons || 0,
            created: dataset.instructorCreated || '',
          });
        });
      });

      <?php if ($selectedInstructor): ?>
      openInstructorModal(<?= json_encode([
        'id' => (string) ($modalInstructor['id'] ?? ''),
        'name' => $modalInstructor['name'] ?? '',
        'email' => $modalInstructor['email'] ?? '',
        'phone' => $modalInstructor['phone'] ?? '',
        'status' => $modalInstructor['status'] ?? 'active',
        'rating' => $modalInstructor['rating_avg'] !== null ? (float) $modalInstructor['rating_avg'] : '',
        'qualification' => $modalInstructor['qualification'] ?? '',
        'courses' => (int) ($modalInstructor['course_count'] ?? 0),
        'lessons' => (int) ($modalInstructor['future_lessons'] ?? 0),
        'created' => $modalInstructor['created_at'] ?? '',
      ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);
      <?php endif; ?>
    });
  </script>
</body>

</html>
