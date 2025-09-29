<?php 
$title = 'Invoices'; 
require_once __DIR__ . '/includes/functions.php';

safeSessionStart();
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = getCurrentUser();
$role = $currentUser['role'];

// Admin có thể xem tất cả invoices, Student chỉ xem invoices của mình
if ($role !== 'admin' && $role !== 'student') {
    http_response_code(403);
    exit('Access Denied: Only Admin or Student can view invoices.');
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
      $page_title = $role === 'admin' ? 'All Invoices' : 'My Invoices';
      $page_subtitle = $role === 'admin' ? 'Manage all student invoices and payments' : 'View your payment history and outstanding balances';
      $page_actions = ($role === 'admin') ? [
        ['href'=>'#','text'=>'+ Create Invoice','class'=>'btn btn-success']
      ] : [];
      include __DIR__.'/partials/pagebar.php';
    ?>

    <main class="container my-3">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Invoice List</h5>
          <p class="text-muted mb-3">
            <?= $role === 'admin' ? 
                'View all invoices, payment status and balances.' : 
                'Your payment history and current outstanding amounts.' 
            ?>
          </p>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead><tr><th>#</th><?= $role === 'admin' ? '<th>Student</th>' : '' ?><th>Issue Date</th><th>Due</th><th>Total</th><th>Balance</th><th>Status</th><th></th></tr></thead>
              <tbody>
                <tr>
                  <td>INV-1001</td>
                  <?= $role === 'admin' ? '<td>Anna Nguyen</td>' : '' ?>
                  <td>2025-09-10</td><td>2025-09-24</td><td>$799.00</td><td>$599.00</td>
                  <td><span class="badge bg-warning text-dark">part_paid</span></td>
                  <td class="text-end"><a href="#" class="btn btn-sm btn-primary">Open</a></td>
                </tr>
                <tr>
                  <td>INV-1002</td>
                  <?= $role === 'admin' ? '<td>Minh Tran</td>' : '' ?>
                  <td>2025-09-10</td><td>2025-09-24</td><td>$449.00</td><td>$0.00</td>
                  <td><span class="badge bg-success">paid</span></td>
                  <td class="text-end"><a href="#" class="btn btn-sm btn-primary">Open</a></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
    <?php include __DIR__.'/partials/footer.php'; ?>
  </body>
</html>
