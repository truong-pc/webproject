<?php 
// partials/header.php 
// Include functions for safe session start
require_once __DIR__ . '/../includes/functions.php';

// Check login status
safeSessionStart();
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$currentUser = $isLoggedIn ? [
    'name' => $_SESSION['user_name'] ?? '',
    'role' => $_SESSION['user_role'] ?? ''
] : null;
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-green">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">Origin Driving</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php
        function active($p){ return basename($_SERVER['PHP_SELF']) === $p ? 'active' : ''; }
        ?>

<?php if ($isLoggedIn): ?>
    <?php $role = $currentUser['role']; ?>

    <?php if ($role === 'admin'): ?>
        <li class="nav-item"><a class="nav-link <?= active('admin_dashboard.php') ?>" href="admin_dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?= active('students.php') ?>" href="students.php">Students</a></li>
        <li class="nav-item"><a class="nav-link <?= active('instructors.php') ?>" href="instructors.php">Instructors</a></li>
        <li class="nav-item"><a class="nav-link <?= active('vehicles.php') ?>" href="vehicles.php">Vehicles</a></li>
        <li class="nav-item"><a class="nav-link <?= active('schedule.php') ?>" href="schedule.php">Schedule</a></li>
        <li class="nav-item"><a class="nav-link <?= active('invoices.php') ?>" href="invoices.php">Invoices</a></li>
        <li class="nav-item"><a class="nav-link <?= active('reports.php') ?>" href="reports.php">Reports</a></li>

    <?php elseif ($role === 'instructor'): ?>
        <li class="nav-item"><a class="nav-link <?= active('index.php') ?>" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link <?= active('schedule.php') ?>" href="schedule.php">My Schedule</a></li>
        <li class="nav-item"><a class="nav-link <?= active('students.php') ?>" href="students.php">Students Info</a></li>

    <?php elseif ($role === 'student'): ?>
        <li class="nav-item"><a class="nav-link <?= active('index.php') ?>" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link <?= active('schedule.php') ?>" href="schedule.php">My Schedule</a></li>
        <li class="nav-item"><a class="nav-link <?= active('instructors.php') ?>" href="instructors.php">Instructors Info</a></li>
        <li class="nav-item"><a class="nav-link <?= active('invoices.php') ?>" href="invoices.php">My Invoices</a></li> 
    <?php endif; ?>

    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
          <?= htmlspecialchars($currentUser['name']) ?> (<?= htmlspecialchars($role) ?>)
        </a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="login.php?action=logout">Logout</a></li>
        </ul>
    </li>

<?php else: ?>
    <li class="nav-item"><a class="nav-link <?= active('login.php') ?>" href="login.php">Login</a></li>
    <li class="nav-item"><a class="nav-link <?= active('register.php') ?>" href="register.php">Register</a></li>
<?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
