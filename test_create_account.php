<?php 
require_once __DIR__ . '/handlers/test_account_handler.php';

$title = 'Test Create Account';
$result = handleTestAccountCreation();
$errors = $result['errors'];
$data = $result['data'];
$success = $result['success'];

if ($success) {
    $successMessage = "Test account created successfully! You can now login with the created credentials.";
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
    <style>
      .role-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
      }
      .test-warning {
        background: linear-gradient(45deg, #fff3cd, #f8d7da);
        border-left: 4px solid #dc3545;
      }
    </style>
  </head>
  <body>
    <main class="container my-5" style="max-width:720px;">
      
      <!-- Test Warning Banner -->
      <div class="alert test-warning mb-4">
        <h6 class="alert-heading mb-2">⚠️ Account Creation Tool</h6>
        <p class="mb-0">This tool is for account creation purposes only. It allows creating accounts with different roles (Admin, Instructor, Student) to test the application functionality.</p>
      </div>

      <h1 class="h3 text-center mb-4">Create Account</h1>
      
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <h6 class="alert-heading mb-2">Please fix the following errors:</h6>
          <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success">
          <h6 class="alert-heading mb-2">✅ Account Created Successfully!</h6>
          <p class="mb-2"><?= htmlspecialchars($successMessage) ?></p>
          <hr>
          <div class="d-flex gap-2">
            <a href="login.php" class="btn btn-success btn-sm">Go to Login</a>
            <a href="test_create_account.php" class="btn btn-outline-primary btn-sm">Create Another Account</a>
          </div>
        </div>
      <?php endif; ?>
      
      <div class="card shadow-sm">
        <div class="card-body">
          <form method="post" action="">
            <div class="row g-3">
              
              <!-- Role Selection -->
              <div class="col-12">
                <label class="form-label">Account Role <span class="text-danger">*</span></label>
                <select class="form-select" name="role" required>
                  <option value="">-- Select Role --</option>
                  <option value="admin" <?= ($data['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                    🔧 Admin (Full System Access)
                  </option>
                  <option value="instructor" <?= ($data['role'] ?? '') === 'instructor' ? 'selected' : '' ?>>
                    🚗 Instructor (Teaching & Schedule Management)
                  </option>
                  <option value="student" <?= ($data['role'] ?? '') === 'student' ? 'selected' : '' ?>>
                    📚 Student (Learning & Booking)
                  </option>
                </select>
                <div class="form-text">Choose the role for this test account</div>
              </div>

              <!-- Full Name -->
              <div class="col-12">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="full_name" 
                       value="<?= htmlspecialchars($data['full_name'] ?? '') ?>" 
                       placeholder="Enter full name" required>
              </div>

              <!-- Email -->
              <div class="col-12">
                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" class="form-control" name="email" 
                       value="<?= htmlspecialchars($data['email'] ?? '') ?>" 
                       placeholder="Enter email address" required>
              </div>

              <!-- Password -->
              <div class="col-12">
                <label class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" name="password" 
                       placeholder="Enter password" required>
                <!-- <div class="form-text">Minimum 6 characters</div> -->
              </div>

              <!-- Phone (Optional) -->
              <div class="col-12">
                <label class="form-label">Phone Number</label>
                <input type="tel" class="form-control" name="phone" 
                       value="<?= htmlspecialchars($data['phone'] ?? '') ?>" 
                       placeholder="Enter phone number (optional)">
              </div>

              <!-- Branch removed (no longer used) -->

              <!-- Submit Button -->
              <div class="col-12 d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                  🚀 Create Test Account
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Role Information - CẬP NHẬT CHỈ CÒN 3 ROLES -->
      <div class="mt-4">
        <h5>Role Information:</h5>
        <div class="row g-2">
          <div class="col-md-4">
            <div class="card border-success">
              <div class="card-body p-3">
                <h6 class="card-title text-success">🔧 Admin</h6>
                <p class="card-text small">Full system access, manage all users, reports, settings, vehicles</p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card border-success">
              <div class="card-body p-3">
                <h6 class="card-title text-success">🚗 Instructor</h6>
                <p class="card-text small">Manage teaching schedule, student progress, view student info</p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card border-primary">
              <div class="card-body p-3">
                <h6 class="card-title text-primary">📚 Student</h6>
                <p class="card-text small">View schedules, progress, make bookings, view invoices</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <div class="mt-4 text-center">
        <a href="login.php" class="btn btn-outline-secondary me-2">← Back to Login</a>
        <a href="index.php" class="btn btn-outline-primary">🏠 Home</a>
      </div>

    </main>
    <?php include __DIR__.'/partials/footer.php'; ?>
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> -->
  </body>
</html>