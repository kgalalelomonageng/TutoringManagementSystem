<?php

require_once 'tutor_check.php';
require_once 'app_helpers.php';

?><!DOCTYPE html>
<html lang="en-ZA">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tutor dashboard</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <main class="dashboard">
    <h1>Welcome, <?php echo escapeHtml($_SESSION['tutor_name']); ?></h1>
    <p class="page-link">Your tutor workspace</p>
    <nav class="dashboard-grid" aria-label="Tutor actions">
      <a href="tutor_availability.php">Manage my availability</a>
      <a href="my_bookings.php">View my assigned sessions</a>
      <a href="my_notifications.php">View notifications</a>
      <a href="logout.php">Log out</a>
    </nav>
  </main>
</body>
</html>
