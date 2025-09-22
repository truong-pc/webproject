<?php $title = 'Students'; ?>
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
      
      $students = getInfoStudents();

      $page_title = 'Students';
      $page_subtitle = 'Register, view and manage student profiles';
      $page_actions = [
        ['href'=>'register.php','text'=>'+ Add Student','class'=>'btn btn-success'],
        ['href'=>'#','text'=>'Export CSV','class'=>'btn btn-outline-primary']
      ];
      
      include __DIR__.'/partials/pagebar.php';

      $badgeClass = static function (string $status): string {
        return match ($status) {
          'active' => 'bg-success',
          'inactive' => 'bg-secondary',
          default => 'bg-info',
        };
      };
    ?>

    <main class="container my-3">
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Home</a></li><li class="breadcrumb-item active">Students</li></ol>
      </nav>

      <div class="card mb-3">
        <div class="card-body">
          <form class="row g-2">
            <div class="col-md-4"><input class="form-control" placeholder="Search name/email"></div>
            <div class="col-md-3">
              <select class="form-select">
                <option value="">All Status</option>
                <option>active</option><option>inactive</option>
              </select>
            </div>
            <div class="col-md-3">
              <select class="form-select">
                <option value="">All Branches</option>
                <option>Head Office</option>
              </select>
            </div>
            <div class="col-md-2 d-grid">
              <button class="btn btn-primary">Filter</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th></th></tr></thead>
              <tbody>
                <?php if ($students): ?>
                  <?php foreach ($students as $index => $student): ?>
                    <tr>
                      <td><?= $index + 1 ?></td>
                      <td><?= htmlspecialchars($student['name']) ?></td>
                      <td><?= htmlspecialchars($student['email']) ?></td>
                      <td><?= htmlspecialchars($student['phone'] ?? '-') ?></td>
                      <td>
                        <span class="badge <?= $badgeClass($student['status']) ?>">
                          <?= htmlspecialchars($student['status']) ?>
                        </span>
                      </td>
                      <td class="text-end">
                        <a class="btn btn-sm btn-primary" href="#">View</a>
                        <a class="btn btn-sm btn-outline-secondary" href="#">Edit</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">No students found. Import the schema and seed data first.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <p class="text-muted mt-2 mb-0">TODO: Pagination</p>
        </div>
      </div>
    </main>

    <?php include __DIR__.'/partials/footer.php'; ?>
  </body>
</html>
