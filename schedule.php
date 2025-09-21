<?php $title = 'Schedule'; ?>
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
        <ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Home</a></li><li class="breadcrumb-item active">Schedule</li></ol>
      </nav>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Schedule</h1>
        <a href="#" class="btn btn-primary">+ Book Lesson</a>
      </div>
      <div class="row g-3">
        <div class="col-lg-4">
          <div class="card h-100">
            <div class="card-body">
              <h5 class="card-title">Filters</h5>
              <div class="mb-3">
                <label class="form-label">Instructor</label>
                <select class="form-select"><option>All</option><option>John Doe</option><option>Maria Smith</option></select>
              </div>
              <div class="mb-3">
                <label class="form-label">Date</label>
                <input type="date" class="form-control">
              </div>
              <button class="btn btn-outline-secondary w-100">Apply</button>
            </div>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="card h-100">
            <div class="card-body">
              <h5 class="card-title">Upcoming Lessons</h5>
              <div class="table-responsive">
                <table class="table table-striped align-middle">
                  <thead><tr><th>Time</th><th>Student</th><th>Instructor</th><th>Vehicle</th><th>Status</th><th></th></tr></thead>
                  <tbody>
                    <tr><td>21/09 09:00</td><td>Anna Nguyen</td><td>John Doe</td><td>1ABC123</td><td><span class="badge bg-info">scheduled</span></td>
                      <td class="text-end"><a href="#" class="btn btn-sm btn-outline-primary">Reschedule</a> <a href="#" class="btn btn-sm btn-outline-danger">Cancel</a></td></tr>
                    <tr><td>21/09 10:30</td><td>Minh Tran</td><td>John Doe</td><td>1ABC123</td><td><span class="badge bg-info">scheduled</span></td>
                      <td class="text-end"><a href="#" class="btn btn-sm btn-outline-primary">Reschedule</a> <a href="#" class="btn btn-sm btn-outline-danger">Cancel</a></td></tr>
                  </tbody>
                </table>
              </div>
              <p class="text-muted">TODO: calendar view (tuần/ngày) hoặc tích hợp JS calendar.</p>
            </div>
          </div>
        </div>
      </div>
    </main>
    <?php include __DIR__.'/partials/footer.php'; ?>
  </body>
</html>
