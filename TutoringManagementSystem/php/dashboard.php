<?php

require_once 'auth_check.php';
require_once 'app_helpers.php';

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student dashboard</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <main class="dashboard">
    <h1>Welcome, <?php echo escapeHtml($_SESSION['student_name']); ?></h1>
    <p class="page-link">Tutoring Booking &amp; Student Management System</p>
    <nav class="dashboard-grid" aria-label="Student actions">
      <a href="book_session.php">Book a session</a>
      <a href="payment.php">Programme payment</a>
      <a href="my_bookings.php">My sessions</a>
      <a href="my_notifications.php">Notifications</a>
      <a href="logout.php">Log out</a>
    </nav>
  </main>
</body>
</html>
