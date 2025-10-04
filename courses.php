<?php
$title = 'Courses';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/courses_functions.php';

safeSessionStart();
$currentUser = getCurrentUser();
if (!isLoggedIn() || ($currentUser['role'] ?? null) !== 'admin') {
  http_response_code(403);
  exit('Access Denied: Only Admin can manage courses.');
}

$messages = ['success' => [], 'error' => []];
$selectedCourse = null;
$hasPostErrors = false;

// Handle form submissions for add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $data = [
        'title' => $_POST['title'] ?? '',
        'price' => $_POST['price'] ?? 0,
        'num_lessons' => $_POST['num_lessons'] ?? 1,
        'description' => $_POST['description'] ?? '',
        'branch_id' => $_POST['branch_id'] ?? null,
        'instructors' => $_POST['instructors'] ?? [],
    ];

    if ($action === 'save_course') {
        $courseId = (int)($_POST['course_id'] ?? 0);

        if ($courseId > 0) { // Update
            $result = updateCourse($courseId, $data);
            if ($result['success']) {
                header('Location: courses.php?msg=updated');
                exit;
            }
            $messages['error'][] = $result['error'] ?? 'Failed to update course.';
        } else { // Add
            $result = addCourse($data);
            if ($result['success']) {
                header('Location: courses.php?msg=added');
                exit;
            }
            $messages['error'][] = $result['error'] ?? 'Failed to add course.';
        }
        
        $hasPostErrors = true;
        $selectedCourse = array_merge($data, ['id' => $courseId]);
        $selectedCourse['instructor_ids'] = json_encode($data['instructors']);

    } elseif ($action === 'delete_course') {
        $courseId = (int)($_POST['course_id'] ?? 0);
        $result = deleteCourse($courseId);
        if ($result['success']) {
            header('Location: courses.php?msg=deleted');
            exit;
        }
        $messages['error'][] = $result['error'] ?? 'Failed to delete course.';
        $hasPostErrors = true;
    }
}

// Display messages from GET params
if (!$hasPostErrors && isset($_GET['msg'])) {
    $msgMap = [
        'added' => 'Course added successfully.',
        'updated' => 'Course updated successfully.',
        'deleted' => 'Course deleted successfully.',
    ];
    if (isset($msgMap[$_GET['msg']])) {
        $messages['success'][] = $msgMap[$_GET['msg']];
    }
}

// Fetch data for display
$courses = getCourses();
$branches = getBranches();
$instructors = getInfoInstructors(999); // Get all instructors

$page_title = 'Course Management';
$page_subtitle = 'Manage all driving courses and instructor assignments.';
$primary_action = [
  'text' => '+ Add Course',
  'class' => 'btn btn-success',
  'attrs' => 'data-bs-toggle="modal" data-bs-target="#courseModal"'
];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?? 'Origin Driving School' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link href="assets/css/theme_green.css" rel="stylesheet">
    <link rel="icon" href="data:,\">
  </head>
  <body>
    <?php include __DIR__.'/partials/header.php'; ?>

    <div class="pagebar py-3 mb-3">
      <div class="container d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
        <div>
          <h1 class="title h4"><?= htmlspecialchars($page_title) ?></h1>
          <p class="subtitle mb-0"><?= htmlspecialchars($page_subtitle) ?></p>
        </div>
        <div class="actions d-flex gap-2">
          <button type="button" class="<?= htmlspecialchars($primary_action['class']) ?>" <?= $primary_action['attrs'] ?>>
            <?= htmlspecialchars($primary_action['text']) ?>
          </button>
        </div>
      </div>
    </div>

    <main class="container my-3">
        <div class="card"><div class="card-body">
            <h5 class="card-title">Course List</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Title</th><th>Price</th><th>Lessons</th><th>Branch</th><th>Instructors</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr><td colspan="6" class="text-center text-muted">No courses found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?= htmlspecialchars($course['title']) ?></td>
                                <td>$<?= number_format($course['price'], 2) ?></td>
                                <td><?= htmlspecialchars($course['num_lessons']) ?></td>
                                <td><?= htmlspecialchars($course['branch_name'] ?? 'N/A') ?></td>
                                <td><small><?= htmlspecialchars($course['instructor_names'] ?? 'Not assigned') ?></small></td>
                                <td class="text-end">
                                    <button 
                                        type="button" 
                                        class="btn btn-sm btn-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#courseModal"
                                        data-course='<?= htmlspecialchars(json_encode($course), ENT_QUOTES, 'UTF-8') ?>'
                                    >Edit</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $course['id'] ?>, '<?= htmlspecialchars($course['title']) ?>')">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div></div>
    </main>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="courseModal" tabindex="-1" aria-labelledby="courseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="courseModalLabel">Add/Edit Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_course">
                    <input type="hidden" name="course_id" id="modalCourseId" value="<?= htmlspecialchars($selectedCourse['id'] ?? '') ?>">
                    
                    <div class="mb-3">
                        <label for="modalTitle" class="form-label">Course Title</label>
                        <input type="text" class="form-control" id="modalTitle" name="title" value="<?= htmlspecialchars($selectedCourse['title'] ?? '') ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="modalPrice" class="form-label">Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="modalPrice" name="price" step="0.01" value="<?= htmlspecialchars($selectedCourse['price'] ?? '0.00') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="modalNumLessons" class="form-label">Number of Lessons</label>
                            <input type="number" class="form-control" id="modalNumLessons" name="num_lessons" value="<?= htmlspecialchars($selectedCourse['num_lessons'] ?? '1') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="modalBranch" class="form-label">Branch</label>
                        <select class="form-select" id="modalBranch" name="branch_id">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($branches as $branch): ?>
                            <option value="<?= $branch['id'] ?>" <?= (($selectedCourse['branch_id'] ?? '') == $branch['id']) ? 'selected' : '' ?>><?= htmlspecialchars($branch['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="modalInstructors" class="form-label">Assignable Instructors</label>
                        <select class="form-select" id="modalInstructors" name="instructors[]" multiple="multiple">
                            <?php foreach ($instructors as $instructor): ?>
                            <option value="<?= $instructor['id'] ?>"><?= htmlspecialchars($instructor['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="modalDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="modalDescription" name="description" rows="3"><?= htmlspecialchars($selectedCourse['description'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Course</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="post" class="d-none">
        <input type="hidden" name="action" value="delete_course">
        <input type="hidden" name="course_id" id="deleteCourseId">
    </form>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
      <div id="notificationToast" class="toast align-items-center" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body"></div>
          <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    </div>

    <?php include __DIR__.'/partials/footer.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#modalInstructors').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#courseModal')
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const courseModal = document.getElementById('courseModal');
        const modalTitle = courseModal.querySelector('.modal-title');
        const modalForm = courseModal.querySelector('form');
        const modalCourseId = courseModal.querySelector('#modalCourseId');
        const modalInstructors = $('#modalInstructors');

        courseModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const courseData = button.getAttribute('data-course');

            if (courseData) { // Editing
                modalTitle.textContent = 'Edit Course';
                const course = JSON.parse(courseData);
                
                modalForm.querySelector('#modalCourseId').value = course.id;
                modalForm.querySelector('#modalTitle').value = course.title;
                modalForm.querySelector('#modalPrice').value = course.price;
                modalForm.querySelector('#modalNumLessons').value = course.num_lessons;
                modalForm.querySelector('#modalBranch').value = course.branch_id || '';
                modalForm.querySelector('#modalDescription').value = course.description || '';
                
                const instructorIds = course.instructor_ids ? JSON.parse(course.instructor_ids) : [];
                modalInstructors.val(instructorIds).trigger('change');

            } else { // Adding
                modalTitle.textContent = 'Add Course';
                modalForm.reset();
                modalInstructors.val(null).trigger('change');
                modalCourseId.value = '';
            }
        });

        <?php
        $all_messages = array_merge(
            array_map(fn($m) => ['type' => 'success', 'text' => $m], $messages['success']),
            array_map(fn($m) => ['type' => 'error', 'text' => $m], $messages['error'])
        );
        if (!empty($all_messages)):
            $notification = $all_messages[0];
        ?>
        const toastEl = document.getElementById('notificationToast');
        const toastBodyEl = toastEl.querySelector('.toast-body');
        const toast = new bootstrap.Toast(toastEl);
        const notificationType = '<?= $notification['type'] ?>';
        const notificationMessage = '<?= addslashes(htmlspecialchars($notification['text'])) ?>';

        toastEl.classList.remove('text-bg-danger', 'text-bg-success');
        toastEl.classList.add(notificationType === 'success' ? 'text-bg-success' : 'text-bg-danger');
        toastBodyEl.textContent = notificationMessage;
        toast.show();
        <?php endif; ?>

        <?php if ($hasPostErrors): ?>
        const errorModal = new bootstrap.Modal(courseModal);
        errorModal.show();
        <?php endif; ?>
    });

    function confirmDelete(id, title) {
        if (confirm(`Are you sure you want to delete the course "${title}"?`)) {
            document.getElementById('deleteCourseId').value = id;
            document.getElementById('deleteForm').submit();
        }
    }
    </script>
  </body>
</html>
