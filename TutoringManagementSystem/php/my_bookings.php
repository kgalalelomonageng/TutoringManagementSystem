<?php

require_once 'db_connect.php';
require_once 'session.php';
require_once 'app_helpers.php';

$role = $_SESSION['account_role'] ?? '';
$actorId = $role === 'student'
    ? (int) ($_SESSION['student_id'] ?? 0)
    : ($role === 'tutor' ? (int) ($_SESSION['tutor_id'] ?? 0) : 0);

if (!$actorId || !in_array($role, ['student', 'tutor'], true)) {
    header('Location: ../html/login.html?error=login_required');
    exit();
}

$ownerColumn = $role === 'student' ? 'b.student_id' : 'b.tutor_id';
$query = $conn->prepare(
    "SELECT b.booking_id, b.booking_date, b.booking_time, b.booking_status,
            p.programme_name,
            CONCAT(s.first_name, ' ', s.last_name) AS student_name,
            CONCAT(t.first_name, ' ', t.last_name) AS tutor_name
     FROM bookings b
     JOIN programmes p ON p.programme_id = b.programme_id
     JOIN students s ON s.student_id = b.student_id
     JOIN tutors t ON t.tutor_id = b.tutor_id
     WHERE {$ownerColumn} = ?
     ORDER BY b.booking_date DESC, b.booking_time DESC
     LIMIT 200"
);
$query->bind_param('i', $actorId);
$query->execute();
$bookings = $query->get_result();
$displayName = $role === 'student' ? ($_SESSION['student_name'] ?? 'Student') : ($_SESSION['tutor_name'] ?? 'Tutor');

?><!DOCTYPE html>
<html lang="en-ZA">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My tutoring sessions</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <main class="dashboard">
    <h1>My tutoring sessions</h1>
    <p class="page-link">Welcome, <?php echo escapeHtml($displayName); ?>.</p>
    <section class="card table-card">
      <div class="table-scroll">
        <table>
          <thead><tr><th>Date</th><th>Time</th><th>Programme</th><th><?php echo $role === 'student' ? 'Tutor' : 'Student'; ?></th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
          <?php if ($bookings->num_rows === 0): ?>
            <tr><td colspan="6">No sessions have been booked yet.</td></tr>
          <?php endif; ?>
          <?php while ($booking = $bookings->fetch_assoc()): ?>
            <tr>
              <td><?php echo escapeHtml($booking['booking_date']); ?></td>
              <td><?php echo escapeHtml(substr($booking['booking_time'], 0, 5)); ?></td>
              <td><?php echo escapeHtml($booking['programme_name']); ?></td>
              <td><?php echo escapeHtml($role === 'student' ? $booking['tutor_name'] : $booking['student_name']); ?></td>
              <td><?php echo escapeHtml($booking['booking_status']); ?></td>
              <td class="table-actions">
                <?php if ($role === 'student' && in_array($booking['booking_status'], ['Requested', 'Confirmed'], true)): ?>
                  <form method="POST" action="booking_action.php">
                    <input type="hidden" name="csrf_token" value="<?php echo escapeHtml(getCsrfToken()); ?>">
                    <input type="hidden" name="booking_id" value="<?php echo (int) $booking['booking_id']; ?>">
                    <input type="hidden" name="booking_status" value="Cancelled">
                    <button class="button-small" type="submit">Cancel</button>
                  </form>
                <?php elseif ($role === 'tutor' && $booking['booking_status'] === 'Requested'): ?>
                  <form method="POST" action="booking_action.php">
                    <input type="hidden" name="csrf_token" value="<?php echo escapeHtml(getCsrfToken()); ?>">
                    <input type="hidden" name="booking_id" value="<?php echo (int) $booking['booking_id']; ?>">
                    <input type="hidden" name="booking_status" value="Confirmed">
                    <button type="submit">Confirm</button>
                  </form>
                  <form method="POST" action="booking_action.php">
                    <input type="hidden" name="csrf_token" value="<?php echo escapeHtml(getCsrfToken()); ?>">
                    <input type="hidden" name="booking_id" value="<?php echo (int) $booking['booking_id']; ?>">
                    <input type="hidden" name="booking_status" value="Cancelled">
                    <button class="button-small" type="submit">Decline</button>
                  </form>
                <?php elseif ($role === 'tutor' && $booking['booking_status'] === 'Confirmed'): ?>
                  <form method="POST" action="booking_action.php">
                    <input type="hidden" name="csrf_token" value="<?php echo escapeHtml(getCsrfToken()); ?>">
                    <input type="hidden" name="booking_id" value="<?php echo (int) $booking['booking_id']; ?>">
                    <input type="hidden" name="booking_status" value="Completed">
                    <button type="submit">Mark completed</button>
                  </form>
                  <form method="POST" action="booking_action.php">
                    <input type="hidden" name="csrf_token" value="<?php echo escapeHtml(getCsrfToken()); ?>">
                    <input type="hidden" name="booking_id" value="<?php echo (int) $booking['booking_id']; ?>">
                    <input type="hidden" name="booking_status" value="Cancelled">
                    <button class="button-small" type="submit">Cancel</button>
                  </form>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
    <p class="page-link">
      <a href="<?php echo $role === 'student' ? 'dashboard.php' : 'tutor_dashboard.php'; ?>">Back to dashboard</a>
    </p>
  </main>
</body>
</html>
