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

// Chỉ cho phép ADMIN và INSTRUCTOR truy cập trang này
if ($role !== 'admin' && $role !== 'instructor') {
    http_response_code(403);
    exit('Access Denied: You do not have permission to view this page.');
}

// ----------------------------------------------------
// 2. Logic Lấy dữ liệu và Giao diện
// ----------------------------------------------------
// TODO: Lấy danh sách students (dùng getInfoStudents() đã có trong functions.php)
$students = getInfoStudents(100, 0); // Ví dụ: lấy 100 học viên đầu tiên

// Hàm định nghĩa class badge cho Status
$badgeClass = static function (string $status): string {
    return match ($status) {
      'active' => 'bg-success',
      'inactive' => 'bg-secondary',
      'pending' => 'bg-warning text-dark',
      default => 'bg-info',
    };
};

$page_title = 'Student Profiles';
$page_subtitle = 'View and manage student information';

// Chỉ Admin mới có nút thêm học viên
$page_actions = ($role === 'admin') ? [
    ['href'=>'register.php','text'=>'+ Add Student','class'=>'btn btn-success'],
    ['href'=>'#','text'=>'Export CSV','class'=>'btn btn-outline-primary']
] : [];

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
    <?php include __DIR__.'/partials/header.php'; ?>

    <?php include __DIR__.'/partials/pagebar.php'; ?>

    <main class="container my-3">
        <div class="card"><div class="card-body">
            <h5 class="card-title">Student List</h5>
            <p class="text-muted mb-3">
                <?= $role === 'admin' ? 
                    'As Admin, you can manage license status. ' : 
                    'As Instructor, you can view student contact details.' 
                ?>
            </p>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Account Status</th>
                            <th>License Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="7" class="text-center text-muted">No students found.</td></tr>
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
                                    <?php if ($role === 'admin'): ?>
                                        <select class="form-select form-select-sm" onchange="updateLicenseStatus(<?= $student['id'] ?>, this.value)">
                                            <option value="pending" <?= ($student['license_status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="verified" <?= ($student['license_status'] ?? '') === 'verified' ? 'selected' : '' ?>>Verified</option>
                                            <option value="expired" <?= ($student['license_status'] ?? '') === 'expired' ? 'selected' : '' ?>>Expired</option>
                                        </select>
                                    <?php else: ?>
                                        <span class="badge <?= $badgeClass($student['license_status'] ?? 'pending') ?>">
                                            <?= htmlspecialchars($student['license_status'] ?? 'pending') ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-primary" href="#">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted mt-2 mb-0">TODO: Pagination và logic cập nhật trạng thái giấy phép.</p>
        </div></div>
    </main>

    <?php include __DIR__.'/partials/footer.php'; ?>
    
    <script>
    function updateLicenseStatus(studentId, newStatus) {
        console.log(`Updating student ${studentId} license status to: ${newStatus}`);
        // TODO: Thực hiện AJAX call để gọi API (ví dụ: update_license_status.php)
        alert(`Admin action: License for student ${studentId} is set to ${newStatus}. (Need backend API)`);
    }
    </script>
  </body>
</html>