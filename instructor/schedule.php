<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/schedule_functions.php';
require_once __DIR__ . '/../includes/vehicels_functions.php';

safeSessionStart();
if (!isLoggedIn() || ($_SESSION['user_role'] ?? '') !== 'instructor') {
    header('Location: /webproject/login.php?error=unauthorized');
    exit();
}

$instructorId = (int)$_SESSION['user_id'];
$lessons = getInstructorLessons($instructorId);

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
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="card" style="min-height: 350px;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">My Lessons</h2>
                <span class="text-muted small">You can only change the status.</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-info">
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Vehicle</th>
                                <th>Status</th>
                                <th class="text-end">Update</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($lessons)): ?>
                            <tr><td colspan="8" class="text-center">No lessons assigned.</td></tr>
                        <?php else: ?>
                            <?php foreach ($lessons as $lesson): ?>
                                <tr>
                                    <td><?= htmlspecialchars($lesson['id']) ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($lesson['day_booking']))) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($lesson['time_of_day'])) ?></td>
                                    <td><?= htmlspecialchars($lesson['student_name']) ?></td>
                                    <td><?= htmlspecialchars($lesson['course_title'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($lesson['vehicle_plate'] ?? 'N/A') ?></td>
                                    <td><span class="badge bg-<?= get_status_badge_class($lesson['status']) ?>"><?= htmlspecialchars(ucfirst($lesson['status'])) ?></span></td>
                                    <td class="text-end">
                                        <form class="d-flex gap-2 justify-content-end" method="POST" action="/webproject/handlers/instructor_lesson_status_handler.php">
                                            <input type="hidden" name="lesson_id" value="<?= (int)$lesson['id'] ?>">
                                            <select name="status" class="form-select form-select-sm" style="max-width:140px;">
                                                <?php
                                                    $statuses = ['scheduling','scheduled','completed','cancelled'];
                                                    foreach ($statuses as $st) {
                                                        $sel = $st === $lesson['status'] ? 'selected' : '';
                                                        echo "<option value='$st' $sel>" . ucfirst($st) . "</option>";
                                                    }
                                                ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</html>