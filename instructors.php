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

// Chỉ cho phép ADMIN và STUDENT truy cập
if ($role !== 'admin' && $role !== 'student') {
    http_response_code(403);
    exit('Access Denied: Only Admin or Student can view instructor information.');
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?? 'Origin Driving School' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/theme_green.css" rel="stylesheet">
    <link rel="icon" href="data:,">
  </head>
  <body>
    <?php include __DIR__.'/partials/header.php'; ?>

    <?php
      $page_title = 'Instructor Profiles';
      $page_subtitle = $role === 'admin' ? 
        'Manage instructor qualifications, schedules and performance' : 
        'View instructor information and availability';
      $page_actions = ($role === 'admin') ? [
        ['href'=>'test_create_account.php','text'=>'+ Add Instructor','class'=>'btn btn-success']
      ] : [];
      include __DIR__.'/partials/pagebar.php';
    ?>

    <main class="container my-3">
      <div class="card"><div class="card-body">
        <h5 class="card-title">Instructor List</h5>
        <p class="text-muted mb-3">
          <?= $role === 'admin' ? 
              'Manage instructor profiles, qualifications and ratings.' : 
              'View instructor qualifications and availability for booking.' 
          ?>
        </p>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead><tr><th>Name</th><th>Qualification</th><th>Rating</th><th>Status</th><th>Availability</th><th></th></tr></thead>
            <tbody>
              <tr>
                <td>John Doe</td>
                <td>Cert IV Driver Training</td>
                <td>⭐⭐⭐⭐⭐ (4.8)</td>
                <td><span class="badge bg-success">Active</span></td>
                <td>Mon-Fri 9AM-5PM</td>
                <td class="text-end">
                  <?= $role === 'admin' ? 
                      '<a href="#" class="btn btn-sm btn-primary">Edit</a>' : 
                      '<a href="#" class="btn btn-sm btn-outline-primary">Book Lesson</a>' 
                  ?>
                </td>
              </tr>
              <tr>
                <td>Maria Smith</td>
                <td>Cert IV Driver Training</td>
                <td>⭐⭐⭐⭐⭐ (4.9)</td>
                <td><span class="badge bg-success">Active</span></td>
                <td>Tue-Sat 8AM-4PM</td>
                <td class="text-end">
                  <?= $role === 'admin' ? 
                      '<a href="#" class="btn btn-sm btn-primary">Edit</a>' : 
                      '<a href="#" class="btn btn-sm btn-outline-primary">Book Lesson</a>' 
                  ?>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div></div>
    </main>

    <?php include __DIR__.'/partials/footer.php'; ?>
  </body>
</html>
