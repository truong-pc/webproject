<?php $title = 'Vehicles'; ?>
<?php 
require_once __DIR__ . '/includes/functions.php';

safeSessionStart();
$currentUser = getCurrentUser();
if (!isLoggedIn() || ($currentUser['role'] ?? null) !== 'admin') {
  http_response_code(403);
  exit('Access Denied: Only Admin can manage vehicles.');
}

$page_title = 'Vehicle Management';
$page_subtitle = 'Manage the fleet of training vehicles.';
$primary_action = [
  'href' => '#',
  'text' => '+ Add Vehicle',
  'class' => 'btn btn-success'
];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?? 'Origin Driving School' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/theme_green.css" rel="stylesheet">
    <link rel="icon" href="data:,\">
  </head>
  <body>
    <?php include __DIR__.'/partials/header.php'; ?>

    <div class="pagebar py-3 mb-3">
      <div class="container d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
        <div>
          <h1 class="title h4"><?= htmlspecialchars($page_title) ?></h1>
          <p class="subtitle mb-0"><?= htmlspecialchars($page_subtitle) ?></p>
        </div>
        <div class="actions d-flex gap-2">
          <a href="<?= htmlspecialchars($primary_action['href']) ?>" class="<?= htmlspecialchars($primary_action['class']) ?>">
            <?= htmlspecialchars($primary_action['text']) ?>
          </a>
        </div>
      </div>
    </div>

    <main class="container my-3">
        <div class="card"><div class="card-body">
            <h5 class="card-title">Vehicle List</h5>
            <p class="text-muted mb-3">TODO: Liệt kê danh sách phương tiện (Make, Model, License Plate, Status, Instructor Assigned).</p>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Plate</th><th>Make/Model</th><th>Year</th><th>Status</th><th>Assigned Instructor</th><th></th></tr></thead>
                    <tbody>
                        <tr><td>1ABC123</td><td>Toyota Corolla</td><td>2022</td><td><span class="badge bg-success">Active</span></td><td>John Doe</td><td class="text-end"><a href="#" class="btn btn-sm btn-primary">Edit</a></td></tr>
                        <tr><td>2DEF456</td><td>Mazda 3</td><td>2020</td><td><span class="badge bg-danger">Maintenance</span></td><td>N/A</td><td class="text-end"><a href="#" class="btn btn-sm btn-primary">Edit</a></td></tr>
                    </tbody>
                </table>
            </div>
        </div></div>
    </main>

    <?php include __DIR__.'/partials/footer.php'; ?>
  </body>
</html>