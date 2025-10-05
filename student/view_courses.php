<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/courses_functions.php';

// Fetch all courses with their instructors
$courses = getCourses();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - Origin Driving School</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/webproject/assets/css/theme_green.css">
    <link rel="stylesheet" href="/webproject/assets/css/view_courses.css">
</head>
<body class="courses-page">

    <?php include __DIR__ . '/../partials/header.php'; ?>

    <div class="container courses-container">
        <h1>Our Driving Courses</h1>
        <p class="lead">Find the perfect course to start your driving journey with us.</p>

        <div class="courses-grid">
            <?php if (empty($courses)): ?>
                <div class="col">
                    <p class="text-white">No courses are available at the moment. Please check back later.</p>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <div class="course-card">
                        <div class="course-card-header">
                            <h3><?php echo sanitizeInput($course['title']); ?></h3>
                        </div>
                        <div class="course-card-body">
                            <p class="price">Price: $<?php echo number_format($course['price'], 2); ?></p>
                            <p class="lessons">Lessons Included: <?php echo (int)$course['num_lessons']; ?></p>
                            <p class="description"><?php echo sanitizeInput($course['description']); ?></p>
                            <?php if (!empty($course['instructor_names'])): ?>
                                <p class="instructors">
                                    <strong>Instructors:</strong> <?php echo sanitizeInput($course['instructor_names']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="course-card-footer">
                            <a href="schedule.php" class="btn btn-book-now">Book Now</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
