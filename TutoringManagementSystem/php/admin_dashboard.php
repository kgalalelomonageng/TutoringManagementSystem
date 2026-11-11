<?php

require_once 'admin_check.php';
require_once 'app_helpers.php';

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administrator dashboard</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <main class="dashboard">
    <h1>Welcome, <?php echo escapeHtml($_SESSION['admin_name']); ?></h1>
    <nav class="dashboard-grid" aria-label="Administration">
      <a href="manage.php?type=students">Manage students</a>
      <a href="manage.php?type=tutors">Manage tutors</a>
      <a href="manage.php?type=programmes">Manage programmes</a>
      <a href="manage.php?type=bookings">Manage bookings</a>
      <a href="tutor_availability.php">Tutor availability</a>
      <a href="admin_reports.php">Payment report</a>
      <a href="my_notifications.php">Notifications</a>
      <a href="logout.php">Log out</a>
    </nav>
  </main>
</body>
</html>
