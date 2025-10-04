<?php 
//Central Student Dashboard

$title = 'Student Dashboard'; 

?>




<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?? 'Origin Driving School' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/theme_green.css" rel="stylesheet">
    <link rel="icon" href="data:,\">
  </head>
  <body>
    <?php include __DIR__.'/../partials/header.php'; ?>






    <?php include __DIR__.'/../partials/footer.php'; ?>
  </body>
</html>