<?php
// students.php - Student Management (Admin) / Student Info (Instructor)

$title = 'Students';
require_once __DIR__ . '/includes/functions.php';

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
    exit('Access Denied: You do not have permission to view this page.');
}

$messages = ['success' => [], 'error' => []];
$statusOptions = ['active' => 'Active', 'inactive' => 'Inactive'];
$licenseOptions = [
    'none' => 'No License',
    'learner' => 'Learner Permit',
    'provisional' => 'Provisional',
    'full' => 'Full License',
    'overseas' => 'Overseas License',
];

$selectedStudent = null;
$hasPostErrors = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_student') {
        $studentId = (int)($_POST['student_id'] ?? 0);

        $result = updateStudentInfo($studentId, [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
            'license_status' => $_POST['license_status'] ?? 'none',
            'address' => trim($_POST['address'] ?? ''),
            'notes_summary' => trim($_POST['notes_summary'] ?? ''),
        ]);

        if ($result['success'] ?? false) {
            header('Location: students.php?msg=updated');
            exit;
        }

        $messages['error'][] = $result['error'] ?? 'Unable to update student.';
        $hasPostErrors = true;
        $selectedStudent = getStudentDetails($studentId) ?? ['id' => $studentId];
        $selectedStudent = array_merge($selectedStudent, [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'status' => $_POST['status'] ?? ($selectedStudent['status'] ?? 'active'),
            'license_status' => $_POST['license_status'] ?? ($selectedStudent['license_status'] ?? 'none'),
            'address' => trim($_POST['address'] ?? ''),
            'notes_summary' => trim($_POST['notes_summary'] ?? ''),
        ]);
    } elseif ($action === 'delete_student') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        if ($studentId <= 0) {
            $messages['error'][] = 'Invalid student selected for deletion.';
            $hasPostErrors = true;
        } else {
            $result = deleteStudent($studentId);
            if ($result['success'] ?? false) {
                header('Location: students.php?msg=deleted');
                exit;
            }
            $messages['error'][] = $result['error'] ?? 'Unable to delete student.';
            $hasPostErrors = true;
        }
    }
}

if (!$hasPostErrors && isset($_GET['msg'])) {
    if ($_GET['msg'] === 'updated') {
        $messages['success'][] = 'Student record updated successfully.';
    } elseif ($_GET['msg'] === 'deleted') {
        $messages['success'][] = 'Student removed successfully.';
    }
}

// ----------------------------------------------------
// 2. Logic Lấy dữ liệu và Giao diện
// ----------------------------------------------------
$students = getInfoStudents(100, 0);

// Hàm định nghĩa class badge cho Status
$badgeClass = static function (string $status): string {
    return match ($status) {
        'active' => 'bg-success',
        'inactive' => 'bg-secondary',
        default => 'bg-info',
    };
};

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?? 'Origin Driving School' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/theme_green.css" rel="stylesheet">
    <link rel="icon" href="data:,\">
</head>

<body>
    <?php include __DIR__ . '/partials/header.php'; ?>

    <div class="pagebar py-3 mb-3">
        <div class="container d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
                <h1 class="title h4">Students</h1>
                <div class="actions d-flex gap-2">
                    <a href="register.php" class="btn btn-success">+ Add Student</a>
                    <a href="#" class="btn btn-outline-primary">Export CSV</a>
                </div>
        </div>
    </div>

    <?php $hasMessages = !empty($messages['success']) || !empty($messages['error']); ?>
    <?php if ($hasMessages): ?>
        <div class="container mb-3 d-flex justify-content-end">
            <button type="button" class="btn btn-primary" id="liveToastBtn">Show error or success</button>
        </div>
    <?php endif; ?>

    <div class="toast-container position-fixed top-0 end-0 p-3" id="messageToastContainer" style="z-index: 1080;">
        <?php foreach ($messages['success'] as $message): ?>
            <div class="toast align-items-center text-bg-success border-0 auto-toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4500">
                <div class="d-flex">
                    <div class="toast-body">
                        <?= htmlspecialchars($message) ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endforeach; ?>
        <?php foreach ($messages['error'] as $message): ?>
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
                    <h5 class="card-title mb-0">Student List</h5>
                    <small class="text-muted">Showing <?= count($students) ?> students</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Account Status</th>
                                <th>License</th>
                                <th class="text-center">Enrollments</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No students found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $index => $student): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($student['name']) ?></td>
                                        <td><?= htmlspecialchars($student['email']) ?></td>
                                        <td><?= htmlspecialchars($student['phone'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge <?= $badgeClass($student['status']) ?>">
                                                <?= htmlspecialchars($student['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= htmlspecialchars($licenseOptions[$student['license_status']] ?? ucfirst($student['license_status'] ?? 'none')) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?= (int)($student['total_enrollments'] ?? 0) ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-primary"
                                                    data-action="view-student"
                                                    data-student-id="<?= (int)$student['id'] ?>"
                                                    data-student-name="<?= htmlspecialchars($student['name'], ENT_QUOTES) ?>"
                                                    data-student-email="<?= htmlspecialchars($student['email'], ENT_QUOTES) ?>"
                                                    data-student-phone="<?= htmlspecialchars($student['phone'] ?? '', ENT_QUOTES) ?>"
                                                    data-student-status="<?= htmlspecialchars($student['status'], ENT_QUOTES) ?>"
                                                    data-student-license="<?= htmlspecialchars($student['license_status'] ?? 'none', ENT_QUOTES) ?>"
                                                    data-student-address="<?= htmlspecialchars($student['address'] ?? '', ENT_QUOTES) ?>"
                                                    data-student-notes="<?= htmlspecialchars($student['notes_summary'] ?? '', ENT_QUOTES) ?>"
                                                    data-student-total="<?= (int)($student['total_enrollments'] ?? 0) ?>"
                                                    data-student-active="<?= (int)($student['active_enrollments'] ?? 0) ?>"
                                                    data-student-completed="<?= (int)($student['completed_enrollments'] ?? 0) ?>"
                                                    data-student-created="<?= htmlspecialchars($student['created_at'] ?? '', ENT_QUOTES) ?>"
                                                >View</button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-student-id="<?= (int)$student['id'] ?>" data-student-name="<?= htmlspecialchars($student['name']) ?>" onclick="confirmDeleteStudent(this)">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted mt-2 mb-0">TODO: Pagination và logic cập nhật trạng thái giấy phép.</p>
            </div>
        </div>
    </main>

    <?php
    $modalStudent = $selectedStudent ?? [
        'id' => '',
        'name' => '',
        'email' => '',
        'phone' => '',
        'dob' => '',
        'status' => 'active',
        'license_status' => 'none',
        'address' => '',
        'notes_summary' => '',
        'total_enrollments' => 0,
        'active_enrollments' => 0,
        'completed_enrollments' => 0,
        'created_at' => null,
    ];
    $modalCreatedAt = $modalStudent['created_at'] ?? null;
    $modalCreatedDisplay = $modalCreatedAt ? date('d/m/Y', strtotime($modalCreatedAt)) : 'Not recorded';
    ?>

    <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentModalLabel">Student Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_student">
                    <input type="hidden" name="student_id" id="modalStudentId" value="<?= htmlspecialchars((string)$modalStudent['id']) ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modalStudentName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="modalStudentName" name="name" value="<?= htmlspecialchars($modalStudent['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalStudentEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="modalStudentEmail" name="email" value="<?= htmlspecialchars($modalStudent['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="modalStudentPhone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="modalStudentPhone" name="phone" value="<?= htmlspecialchars($modalStudent['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="modalStudentDOB" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="modalStudentDOB" name="dob" value="<?= htmlspecialchars($modalStudent['dob'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="modalStudentStatus" class="form-label">Account Status</label>
                            <select class="form-select" id="modalStudentStatus" name="status">
                                <?php foreach ($statusOptions as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= (($modalStudent['status'] ?? 'active') === $value) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="modalStudentLicense" class="form-label">License Status</label>
                            <select class="form-select" id="modalStudentLicense" name="license_status">
                                <?php foreach ($licenseOptions as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= (($modalStudent['license_status'] ?? 'none') === $value) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    
                        <div class="col-md-6">
                            <label for="modalStudentAddress" class="form-label">Address</label>
                            <textarea class="form-control" id="modalStudentAddress" name="address" rows="2"><?= htmlspecialchars($modalStudent['address'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="modalStudentNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="modalStudentNotes" name="notes_summary" rows="2"><?= htmlspecialchars($modalStudent['notes_summary'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <dl class="row small text-muted mb-0">
                                <dt class="col-sm-5">Created</dt>
                                <dd class="col-sm-7" id="modalStudentCreatedDisplay"><?= htmlspecialchars($modalCreatedDisplay) ?></dd>
                                <dt class="col-sm-5">Total Enrollments</dt>
                                <dd class="col-sm-7" id="modalStudentTotalDisplay"><?= (int)($modalStudent['total_enrollments'] ?? 0) ?></dd>
                                <dt class="col-sm-5">Active Enrollments</dt>
                                <dd class="col-sm-7" id="modalStudentActiveDisplay"><?= (int)($modalStudent['active_enrollments'] ?? 0) ?></dd>
                                <dt class="col-sm-5">Completed Enrollments</dt>
                                <dd class="col-sm-7" id="modalStudentCompletedDisplay"><?= (int)($modalStudent['completed_enrollments'] ?? 0) ?></dd>
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

    <form method="post" id="deleteStudentForm" class="d-none">
        <input type="hidden" name="action" value="delete_student">
        <input type="hidden" name="student_id" id="deleteStudentId" value="">
    </form>

    <script>
        /**
         * Show a native confirm dialog before removing a student.
         * If the admin confirms, populate the hidden delete form and submit it.
         */
        function confirmDeleteStudent(button) {
            const studentId = button.getAttribute('data-student-id');
            const studentName = button.getAttribute('data-student-name');
            if (confirm(`Are you sure you want to delete student "${studentName}"? This action cannot be undone.`)) {
                document.getElementById('deleteStudentId').value = studentId;
                document.getElementById('deleteStudentForm').submit();
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            // --- Toast notifications (success + error) ---
            const toastElements = Array.from(document.querySelectorAll('.auto-toast'));
            const toastInstances = toastElements.map((element) => new bootstrap.Toast(element));
            const toastTriggerButton = document.getElementById('liveToastBtn');

            /** Automatically display all queued toasts. */
            const showToasts = () => {
                toastInstances.forEach((instance) => instance.show());
            };

            if (toastInstances.length > 0) {
                showToasts();
                if (toastTriggerButton) {
                    // Allow manual replay of the latest messages.
                    toastTriggerButton.addEventListener('click', showToasts);
                }
            } else if (toastTriggerButton) {
                toastTriggerButton.classList.add('d-none');
            }

            // --- Student profile modal setup ---
            const modalElement = document.getElementById('studentModal');
            if (!modalElement) {
                return;
            }

            const modalInstance = new bootstrap.Modal(modalElement);
            // Cached references to form fields for quick population.
            const fieldRefs = {
                id: modalElement.querySelector('#modalStudentId'),
                name: modalElement.querySelector('#modalStudentName'),
                email: modalElement.querySelector('#modalStudentEmail'),
                phone: modalElement.querySelector('#modalStudentPhone'),
                status: modalElement.querySelector('#modalStudentStatus'),
                license: modalElement.querySelector('#modalStudentLicense'),
                address: modalElement.querySelector('#modalStudentAddress'),
                notes: modalElement.querySelector('#modalStudentNotes'),
            };
            // References to read-only stats displayed in the modal footer.
            const statRefs = {
                created: modalElement.querySelector('#modalStudentCreatedDisplay'),
                total: modalElement.querySelector('#modalStudentTotalDisplay'),
                active: modalElement.querySelector('#modalStudentActiveDisplay'),
                completed: modalElement.querySelector('#modalStudentCompletedDisplay'),
            };

            /** Format timestamps into a readable date string. */
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

            /** Push fetched dataset values into the modal fields and stat labels. */
            const populateModal = (data) => {
                if (!data) {
                    return;
                }
                fieldRefs.id.value = data.id ?? '';
                fieldRefs.name.value = data.name ?? '';
                fieldRefs.email.value = data.email ?? '';
                fieldRefs.phone.value = data.phone ?? '';
                fieldRefs.status.value = data.status ?? 'active';
                fieldRefs.license.value = data.license ?? 'none';
                fieldRefs.address.value = data.address ?? '';
                fieldRefs.notes.value = data.notes ?? '';

                statRefs.created.textContent = formatDate(data.created);
                statRefs.total.textContent = Number.parseInt(data.total ?? 0, 10) || 0;
                statRefs.active.textContent = Number.parseInt(data.active ?? 0, 10) || 0;
                statRefs.completed.textContent = Number.parseInt(data.completed ?? 0, 10) || 0;
            };

            /** Populate the modal with data and display it to the admin. */
            const openStudentModal = (data) => {
                populateModal(data);
                modalInstance.show();
            };

            // Bind each "View" button to open the modal with its row data snapshot.
            document.querySelectorAll('[data-action="view-student"]').forEach((button) => {
                button.addEventListener('click', () => {
                    const dataset = button.dataset;
                    openStudentModal({
                        id: dataset.studentId || '',
                        name: dataset.studentName || '',
                        email: dataset.studentEmail || '',
                        phone: dataset.studentPhone || '',
                        status: dataset.studentStatus || 'active',
                        license: dataset.studentLicense || 'none',
                        address: dataset.studentAddress || '',
                        notes: dataset.studentNotes || '',
                        total: dataset.studentTotal || 0,
                        active: dataset.studentActive || 0,
                        completed: dataset.studentCompleted || 0,
                        created: dataset.studentCreated || '',
                    });
                });
            });

            <?php if ($selectedStudent): ?>
            // If validation failed, reopen the modal prefilled with submitted data.
            openStudentModal(<?= json_encode([
                'id' => (string)($modalStudent['id'] ?? ''),
                'name' => $modalStudent['name'] ?? '',
                'email' => $modalStudent['email'] ?? '',
                'phone' => $modalStudent['phone'] ?? '',
                'status' => $modalStudent['status'] ?? 'active',
                'license' => $modalStudent['license_status'] ?? 'none',
                'address' => $modalStudent['address'] ?? '',
                'notes' => $modalStudent['notes_summary'] ?? '',
                'total' => (int)($modalStudent['total_enrollments'] ?? 0),
                'active' => (int)($modalStudent['active_enrollments'] ?? 0),
                'completed' => (int)($modalStudent['completed_enrollments'] ?? 0),
                'created' => $modalStudent['created_at'] ?? '',
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);
            <?php endif; ?>
        });
    </script>
</body>

</html>