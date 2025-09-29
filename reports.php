<?php $title = 'Reports'; ?>
// Tệp: reports.php & invoices.php (Thêm sau các require_once)

require_once __DIR__ . '/includes/functions.php'; // Đảm bảo đã có

safeSessionStart();
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    http_response_code(403);
    exit('Access Denied: Only Admin can view this page.');
}
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
    <main class="container my-4">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Home</a></li><li class="breadcrumb-item active">Reports</li></ol>
      </nav>
      <h1 class="h3 mb-3">Reports</h1>
      <div class="row g-3">
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-body">
              <h5 class="card-title">Student Progress</h5>
              <p class="text-muted">TODO: bảng tiến độ theo khoá (JOIN view <code>v_student_progress</code>).</p>
              <a href="#" class="btn btn-outline-primary">Export CSV</a>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-body">
              <h5 class="card-title">Financial Summary</h5>
              <p class="text-muted">TODO: tổng thu/đã trả/còn nợ (JOIN view <code>v_financial_summary</code>).</p>
              <a href="#" class="btn btn-outline-primary">Export CSV</a>
            </div>
          </div>
        </div>
      </div>
    </main>
    <?php include __DIR__.'/partials/footer.php'; ?>
  </body>
</html>
