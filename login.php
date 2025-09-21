<?php 
require_once __DIR__ . '/handlers/login_handler.php';

// Check if already logged in
session_start();
if (isLoggedIn()) {
    $user = getCurrentUser();
    header('Location: ' . getRedirectUrl($user['role']));
    exit;
}

$title = 'Login';
$result = handleLogin();
$errors = $result['errors'];
$data = $result['data'];
$success = $result['success'];

if ($success && !empty($result['redirect_url'])) {
    header('Location: ' . $result['redirect_url']);
    exit;
}

// Check for registration success message
$registered = isset($_GET['registered']) && $_GET['registered'] == '1';
$logged_out = isset($_GET['logged_out']) && $_GET['logged_out'] == '1';
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
    <main class="container my-5" style="max-width:520px;">
      <h1 class="h3 text-center mb-4">Sign in</h1>
      
      <?php if ($registered): ?>
        <div class="alert alert-success">
          <h6 class="alert-heading mb-1">Registration successful!</h6>
          <p class="mb-0">Your account has been created. You can now sign in with your email and the default password: <code>student123</code></p>
        </div>
      <?php endif; ?>
      
      <?php if ($logged_out): ?>
        <div class="alert alert-info">
          You have been successfully logged out.
        </div>
      <?php endif; ?>
      
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
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
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" 
                     value="<?= htmlspecialchars($data['email'] ?? '') ?>" required autofocus>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" class="form-control" name="password" required>
              <div class="form-text">
                For new students, the default password is: <code>student123</code>
              </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Sign In</button>
          </form>
          <div class="text-center mt-3">
            <small>New student? <a href="register.php">Register here</a></small>
          </div>
        </div>
      </div>
    </main>
    <?php include __DIR__.'/partials/footer.php'; ?>
  </body>
</html>
