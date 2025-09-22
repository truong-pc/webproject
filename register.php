<?php 
require_once __DIR__ . '/handlers/register_handler.php';

$title = 'Register';
$result = handleRegistration();
$errors = $result['errors'];
$data = $result['data'];
$success = $result['success'];

if ($success) {
    header('Location: login.php?registered=1');
    exit;
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
    <main class="container my-5" style="max-width:720px;">
      <h1 class="h3 text-center mb-4">Student Registration</h1>
      
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
      
      <div class="card shadow-sm">
        <div class="card-body">
          <form method="post" action="">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Full name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="full_name" 
                       value="<?= htmlspecialchars($data['full_name'] ?? '') ?>" 
                       placeholder="Enter your full name" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" name="email" 
                       value="<?= htmlspecialchars($data['email'] ?? '') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" name="password" 
                       placeholder="Minimum 6 characters" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" name="phone" 
                       value="<?= htmlspecialchars($data['phone'] ?? '') ?>" placeholder="e.g., 0412 345 678">
              </div>
              <div class="col-md-6">
                <label class="form-label">Date of Birth</label>
                <input type="date" class="form-control" name="dob" 
                       value="<?= htmlspecialchars($data['dob'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Learner Licence No.</label>
                <input type="text" class="form-control" name="license_number" 
                       value="<?= htmlspecialchars($data['license_number'] ?? '') ?>" placeholder="Optional">
              </div>
              <div class="col-md-6">
                <label class="form-label">Address</label>
                <input type="text" class="form-control" name="address" 
                       value="<?= htmlspecialchars($data['address'] ?? '') ?>" placeholder="Full address (optional)">
              </div>
            </div>
            <button type="submit" class="btn btn-success w-100 mt-4">Create Account</button>
          </form>
          <div class="text-center mt-3">
            <small>Already have an account? <a href="login.php">Sign in here</a></small>
          </div>
        </div>
      </div>
    </main>
    <?php include __DIR__.'/partials/footer.php'; ?>
  </body>
</html>
