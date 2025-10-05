<?php 

require_once '../config/connect_db.php';
require_once '../includes/notification_functions.php';
require_once '../includes/functions.php';

safeSessionStart();
if (!isLoggedIn()) {
  header('Location: ../login.php');
  exit;
}

$currentUser = getCurrentUser();
$role = $currentUser['role'];

// Chỉ cho phép instructor truy cập trang này
if ($role !== 'instructor') {
  http_response_code(403);
  exit('Access Denied: Only Instructors can view the dashboard.');
}

$instructor_id = $_SESSION['user_id'];
$instructor = get_user_by_id($instructor_id);
$notifications = get_notifications_by_user($instructor_id);

$title = 'Instructor Dashboard'; 
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?? 'Origin Driving School' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="../assets/css/theme_green.css" rel="stylesheet">
    <link rel="icon" href="data:,\">
    <style>
    .notification-item { cursor: pointer; }
    .notification-item.unread { font-weight: bold; }
    </style>
  </head>
  <body>
    <?php include __DIR__.'/../partials/header.php'; ?>
    <div class="container-fluid mt-4">
      <div class="row">
        <div class="col-md-4">
          <h4>Notifications</h4>
          <div class="list-group" id="notification-list">
            <?php if (!empty($notifications)): ?>
              <?php foreach ($notifications as $notification) : ?>
                <a href="#" class="list-group-item list-group-item-action notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" data-id="<?php echo $notification['id']; ?>">
                  <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h5>
                    <small><?php echo date('d M Y', strtotime($notification['created_at'])); ?></small>
                  </div>
                  <p class="mb-1 notification-content" style="display:none;">&nbsp;<?php echo htmlspecialchars($notification['content']); ?></p>
                  <?php if (!$notification['is_read']) : ?>
                    <span class="badge bg-primary rounded-pill">New</span>
                  <?php endif; ?>
                </a>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="text-muted">You have no notifications.</p>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-md-8">
          <div id="account-details">
            <h4>Account Information</h4>
            <form id="update-profile-form">
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($instructor['email']); ?>" readonly>
              </div>
              <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($instructor['name']); ?>" required readonly>
              </div>
              <div class="mb-3">
                <label for="phone" class="form-label">Phone</label>
                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($instructor['phone']); ?>" readonly>
              </div>
              <div class="mb-3">
                <label for="dob" class="form-label">Date of Birth</label>
                <input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($instructor['dob']); ?>" readonly>
              </div>
              <button type="button" id="edit-profile-btn" class="btn btn-secondary">Edit Profile</button>
              <button type="submit" id="save-profile-btn" class="btn btn-primary" style="display: none;">Save Changes</button>
            </form>
            <div id="profile-update-message" class="mt-3"></div>
            <hr>
            <h4>Change Password</h4>
            <form id="change-password-form">
              <div class="mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password" class="form-control" id="current_password" name="current_password" required>
              </div>
              <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
              </div>
              <div class="mb-3">
                <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
              </div>
              <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
            <div id="password-change-message" class="mt-3"></div>
          </div>
          <div id="notification-content-display" style="display: none;">
            <button id="back-to-profile" class="btn btn-secondary mb-3">Back to Profile</button>
            <h4 id="notification-title"></h4>
            <p id="notification-body"></p>
          </div>
        </div>
      </div>
    </div>
    <?php include __DIR__.'/../partials/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
    $(function() {
      // Notification click
      $('.notification-item').on('click', function(e) {
        e.preventDefault();
        const $item = $(this);
        const notificationId = $item.data('id');
        const title = $item.find('h5').text();
        const content = $item.find('.notification-content').text();
        $('#account-details').hide();
        $('#notification-content-display').show();
        $('#notification-title').text(title);
        $('#notification-body').text(content);
        if ($item.hasClass('unread')) {
          $.ajax({
            url: '../handlers/mark_notification_read.php',
            type: 'POST',
            dataType: 'json',
            data: { notification_id: notificationId },
            success: function(res) {
              if (res && res.success) {
                $item.removeClass('unread');
                $item.find('.badge').remove();
              }
            }
          });
        }
      });
      // Back button
      $('#back-to-profile').on('click', function() {
        $('#notification-content-display').hide();
        $('#account-details').show();
      });
      // Enable editing (except email)
      $('#edit-profile-btn').on('click', function() {
        $('#name, #phone, #dob').prop('readonly', false);
        $(this).hide();
        $('#save-profile-btn').show();
      });
      // Update profile AJAX (FIX: use dataType json & remove JSON.parse)
      $('#update-profile-form').on('submit', function(e) {
        e.preventDefault();
        $('#profile-update-message').empty();
        $.ajax({
          url: '../handlers/update_instructor_profile.php',
            type: 'POST',
            dataType: 'json',
            data: $(this).serialize(),
            success: function(res) {
              const messageClass = res.success ? 'alert-success' : 'alert-danger';
              $('#profile-update-message').html('<div class="alert ' + messageClass + '">' + res.message + '</div>');
              if (res.success) {
                $('#update-profile-form input').prop('readonly', true);
                $('#save-profile-btn').hide();
                $('#edit-profile-btn').show();
              }
            },
            error: function(xhr) {
              $('#profile-update-message').html('<div class="alert alert-danger">Error updating profile.</div>');
              console.error('Profile update error', xhr.responseText);
            }
        });
      });
      // Change password AJAX (FIX: use dataType json & remove JSON.parse)
      $('#change-password-form').on('submit', function(e) {
        e.preventDefault();
        $('#password-change-message').empty();
        const newPassword = $('#new_password').val();
        const confirmPassword = $('#confirm_new_password').val();
        if (newPassword !== confirmPassword) {
          $('#password-change-message').html('<div class="alert alert-danger">New passwords do not match.</div>');
          return;
        }
        $.ajax({
          url: '../handlers/change_password_handler.php',
          type: 'POST',
          dataType: 'json',
          data: $(this).serialize(),
          success: function(res) {
            const messageClass = res.success ? 'alert-success' : 'alert-danger';
            $('#password-change-message').html('<div class="alert ' + messageClass + '">' + res.message + '</div>');
            if (res.success) {
              $('#change-password-form')[0].reset();
            }
          },
          error: function(xhr) {
            $('#password-change-message').html('<div class="alert alert-danger">Error changing password.</div>');
            console.error('Password change error', xhr.responseText);
          }
        });
      });
    });
    </script>
  </body>
</html>