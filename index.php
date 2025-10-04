<?php // index.php - Origin Driving School (Green Theme) ?>
<!doctype html>
<html lang="en">
  <head>
    <?php $title = 'Home'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?? 'Origin Driving School' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/theme_green.css" rel="stylesheet">
    <link rel="icon" href="data:,">
  </head>
  <body>
    <?php include __DIR__ . '/partials/header.php'; ?>

    <section class="hero-green">
      <div class="hero-content container py-5 py-lg-0">
        <span class="badge bg-success-subtle text-success-emphasis text-uppercase fw-semibold mb-3">Green fleet ready</span>
        <h1 class="display-5 fw-bold mb-3">Origin Driving School</h1>
        <p class="lead mb-4">Confident, safe and professional training tailored to every learner driver across Melbourne.</p>
        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
          <a href="register.php" class="btn btn-cta btn-lg px-4">Register Now</a>
          <a href="#features" class="btn btn-outline-primary btn-lg px-4">Explore Features</a>
        </div>
      </div>
    </section>

    <main>
      <section id="features" class="py-5">
        <div class="container">
          <div class="text-center mb-5">
            <h2 class="fw-bold" style="color:var(--g-800)">Powerful tools for modern driving schools</h2>
            <p class="text-muted mb-0">Streamline admin, keep instructors organised and help students hit the road faster.</p>
          </div>

          <div class="row g-4">
            <div class="col-lg-4 col-md-6">
              <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                  <span class="badge bg-success-subtle text-success-emphasis rounded-pill mb-3">Students</span>
                  <h5 class="card-title">Learner Profiles</h5>
                  <p class="card-text">Capture personal details, log hours, track competencies and review lesson history at a glance.</p>
                  <a href="students.php" class="btn btn-primary w-100">Go to Students</a>
                </div>
              </div>
            </div>

            <div class="col-lg-4 col-md-6">
              <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                  <span class="badge bg-success-subtle text-success-emphasis rounded-pill mb-3">Instructors</span>
                  <h5 class="card-title">Team Management</h5>
                  <p class="card-text">See credentials, availability and feedback so you can match students with the right instructor.</p>
                  <a href="instructors.php" class="btn btn-primary w-100">Go to Instructors</a>
                </div>
              </div>
            </div>

            <div class="col-lg-4 col-md-6">
              <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                  <span class="badge bg-success-subtle text-success-emphasis rounded-pill mb-3">Schedule</span>
                  <h5 class="card-title">Smart Booking</h5>
                  <p class="card-text">Coordinate lessons, manage reschedules and stay on top of upcoming sessions with ease.</p>
                  <a href="schedule.php" class="btn btn-primary w-100">Go to Schedule</a>
                </div>
              </div>
            </div>

            <div class="col-lg-6 col-md-6">
              <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                  <span class="badge bg-success-subtle text-success-emphasis rounded-pill mb-3">Invoices</span>
                  <h5 class="card-title">Billing & Payments</h5>
                  <p class="card-text">Generate invoices, record payments and keep balances clean for every learner and package.</p>
                  <a href="invoices.php" class="btn btn-primary w-100">Go to Invoices</a>
                </div>
              </div>
            </div>

            <div class="col-lg-6 col-md-6">
              <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                  <span class="badge bg-success-subtle text-success-emphasis rounded-pill mb-3">Reports</span>
                  <h5 class="card-title">Insightful Reports</h5>
                  <p class="card-text">Monitor student progress, instructor performance and financial trends to grow sustainably.</p>
                  <a href="admin_dashboard.php" class="btn btn-primary w-100">Go to Admin Dashboard</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="py-5" style="background: linear-gradient(135deg, var(--g-100), var(--g-50));">
        <div class="container">
          <div class="row g-5 align-items-center">
            <div class="col-lg-6">
              <h3 class="fw-bold mb-3" style="color:var(--g-800)">Built for team collaboration</h3>
              <p class="text-muted mb-4">Keep your office, instructors and learners aligned with real-time updates and streamlined workflows. Every feature is designed to reduce manual admin so you can focus on growing your school.</p>
              <ul class="list-unstyled">
                <li class="d-flex mb-3">
                  <span class="badge bg-success text-uppercase fw-semibold me-3">Live</span>
                  <div>
                    <h6 class="fw-semibold mb-1">Instant lesson updates</h6>
                    <p class="text-muted mb-0">Track cancellations, no-shows and rebookings without juggling multiple spreadsheets.</p>
                  </div>
                </li>
                <li class="d-flex mb-3">
                  <span class="badge bg-success text-uppercase fw-semibold me-3">Secure</span>
                  <div>
                    <h6 class="fw-semibold mb-1">Safe data storage</h6>
                    <p class="text-muted mb-0">Student info stays protected with structured access for staff and instructors.</p>
                  </div>
                </li>
                <li class="d-flex">
                  <span class="badge bg-success text-uppercase fw-semibold me-3">Ready</span>
                  <div>
                    <h6 class="fw-semibold mb-1">Assessment-ready records</h6>
                    <p class="text-muted mb-0">Export what you need for audits, compliance and student licensing checks.</p>
                  </div>
                </li>
              </ul>
            </div>
            <div class="col-lg-6">
              <div class="p-4 p-lg-5 rounded-4 shadow-sm" style="background: linear-gradient(160deg, var(--g-700), var(--g-500)); color:#ecfdf5;">
                <h4 class="fw-semibold">Need help getting started?</h4>
                <p class="mb-4">Book a free onboarding session with our support team and we will walk you through configuring instructors, lesson types and pricing.</p>
                <a href="schedule.php" class="btn btn-light btn-lg">Book a demo lesson</a>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="py-5" style="background: linear-gradient(90deg, var(--g-800), var(--g-700)); color:#ecfdf5;">
        <div class="container text-center">
          <h2 class="fw-bold mb-3">Ready to drive success?</h2>
          <p class="mb-4">Create your Origin Driving School account and start managing students, instructors and schedules in minutes.</p>
          <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
            <a href="register.php" class="btn btn-light btn-lg px-4">Start Registration</a>
            <a href="login.php" class="btn btn-outline-light btn-lg px-4">Log in to your account</a>
          </div>
        </div>
      </section>
    </main>

    <?php include __DIR__ . '/partials/footer.php'; ?>
  </body>
</html>
