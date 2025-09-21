<?php $title = 'Instructors'; ?>
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
      $page_title = 'Instructors';
      $page_subtitle = 'Profiles, qualifications and teaching schedules';
      $page_actions = [
        ['href'=>'#','text'=>'+ Add Instructor','class'=>'btn btn-success']
      ];
      include __DIR__.'/partials/pagebar.php';
    ?>

    <main class="container my-3">
      <!-- nội dung TODO -->
      <div class="card"><div class="card-body">
        <p class="text-muted mb-3">TODO: list instructors with rating & availability.</p>
      </div></div>
    </main>

    <?php include __DIR__.'/partials/footer.php'; ?>
  </body>
</html>
