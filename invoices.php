<?php $title = 'Invoices'; ?>
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
        <ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Home</a></li><li class="breadcrumb-item active">Invoices</li></ol>
      </nav>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Invoices</h1>
        <a href="#" class="btn btn-success">+ Create Invoice</a>
      </div>
      <div class="card">
        <div class="card-body">
          <p class="text-muted mb-3">TODO: danh sách hoá đơn, trạng thái & số dư; link sang chi tiết.</p>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead><tr><th>#</th><th>Student</th><th>Issue Date</th><th>Due</th><th>Total</th><th>Balance</th><th>Status</th><th></th></tr></thead>
              <tbody>
                <tr>
                  <td>INV-1001</td><td>Anna Nguyen</td><td>2025-09-10</td><td>2025-09-24</td><td>$799.00</td><td>$599.00</td>
                  <td><span class="badge bg-warning text-dark">part_paid</span></td>
                  <td class="text-end"><a href="#" class="btn btn-sm btn-primary">Open</a></td>
                </tr>
                <tr>
                  <td>INV-1002</td><td>Minh Tran</td><td>2025-09-10</td><td>2025-09-24</td><td>$449.00</td><td>$0.00</td>
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
