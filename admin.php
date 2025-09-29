<?php 
// admin.php - Central Admin Dashboard

$title = 'Admin Dashboard'; 
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
    exit('Access Denied: Only Admin can view the dashboard.');
}

// ----------------------------------------------------
// 2. Lấy dữ liệu Tổng quan (Placeholder/Giả định)
// ----------------------------------------------------
// TODO: Thay thế bằng các hàm truy vấn DB thực tế từ functions.php
$stats = [
    'total_students' => 145,
    'total_instructors' => 12,
    'pending_invoices' => 24,
    'vehicles_in_maintenance' => 2,
    'pending_license_checks' => 18,
];

// ----------------------------------------------------
// 3. Cấu hình Tiêu đề Trang
// ----------------------------------------------------
$page_title = 'Welcome, Admin ' . htmlspecialchars($currentUser['name']);
$page_subtitle = 'System Overview and Quick Access Panel';
$page_actions = [
    ['href'=>'schedule.php','text'=>'+ New Course','class'=>'btn btn-success'],
    ['href'=>'invoices.php','text'=>'+ New Invoice','class'=>'btn btn-outline-primary']
];

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
        
        <h5 class="fw-bold mb-3">System Metrics</h5>
        <div class="row g-4 mb-5">
            
            <div class="col-lg-3 col-md-6">
                <a href="students.php" class="text-decoration-none card h-100 shadow-sm-hover">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Total Students</h6>
                        <h2 class="card-title text-success display-4 fw-bold"><?= $stats['total_students'] ?></h2>
                        <p class="card-text">View profiles and information</p>
                    </div>
                </a>
            </div>

            <div class="col-lg-3 col-md-6">
                <a href="instructors.php" class="text-decoration-none card h-100 shadow-sm-hover">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Total Instructors</h6>
                        <h2 class="card-title text-success display-4 fw-bold"><?= $stats['total_instructors'] ?></h2>
                        <p class="card-text">Manage profiles and schedules</p>
                    </div>
                </a>
            </div>

            <div class="col-lg-3 col-md-6">
                <a href="reports.php" class="text-decoration-none card h-100 shadow-sm-hover">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Pending Invoices</h6>
                        <h2 class="card-title text-danger display-4 fw-bold"><?= $stats['pending_invoices'] ?></h2>
                        <p class="card-text">Check payments and reports</p>
                    </div>
                </a>
            </div>

            <div class="col-lg-3 col-md-6">
                <a href="students.php" class="text-decoration-none card h-100 shadow-sm-hover">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Pending License Checks</h6>
                        <h2 class="card-title text-warning text-dark display-4 fw-bold"><?= $stats['pending_license_checks'] ?></h2>
                        <p class="card-text">Manage license verification</p>
                    </div>
                </a>
            </div>
        </div>

        <h5 class="fw-bold mb-3">Quick Management</h5>
        <div class="row g-4">
            
            <div class="col-md-6 col-lg-4">
                <div class="card h-100"><div class="card-body">
                    <h5 class="card-title mb-3">Student & License Management</h5>
                    <p class="card-text text-muted">View student information and update their license verification status.</p>
                    <a href="students.php" class="btn btn-primary w-100">View Students (<?= $stats['total_students'] ?>)</a>
                </div></div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100"><div class="card-body">
                    <h5 class="card-title mb-3">Schedule & Course Booking</h5>
                    <p class="card-text text-muted">Create new courses, assign instructors, and manage all lesson schedules.</p>
                    <a href="schedule.php" class="btn btn-primary w-100">Manage Schedule</a>
                </div></div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100"><div class="card-body">
                    <h5 class="card-title mb-3">Vehicle Management</h5>
                    <p class="card-text text-muted">Monitor vehicle status and manage maintenance schedules and assignments.</p>
                    <a href="vehicles.php" class="btn btn-primary w-100">View Vehicles (<?= $stats['vehicles_in_maintenance'] ?> in Maint.)</a>
                </div></div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100"><div class="card-body">
                    <h5 class="card-title mb-3">Financial Reports & Invoices</h5>
                    <p class="card-text text-muted">Review financial summaries, create invoices, and check payment status.</p>
                    <a href="reports.php" class="btn btn-primary w-100">View Financial Reports</a>
                </div></div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100"><div class="card-body">
                    <h5 class="card-title mb-3">Instructor Management</h5>
                    <p class="card-text text-muted">Add new instructors and manage their profiles, documents, and branches.</p>
                    <a href="instructors.php" class="btn btn-primary w-100">Manage Instructors (<?= $stats['total_instructors'] ?>)</a>
                </div></div>
            </div>

        </div>

    </main>

    <?php include __DIR__.'/partials/footer.php'; ?>
  </body>
</html>