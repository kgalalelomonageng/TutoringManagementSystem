<?php

require_once 'db_connect.php';
require_once 'session.php';
require_once 'app_helpers.php';

$role = $_SESSION['account_role'] ?? '';
$actorId = $role === 'student'
    ? (int) ($_SESSION['student_id'] ?? 0)
    : ($role === 'tutor' ? (int) ($_SESSION['tutor_id'] ?? 0) : (int) ($_SESSION['admin_id'] ?? 0));
$recipientRole = in_array($role, ['student', 'tutor', 'admin'], true) ? $role : '';
$recipientId = $role === 'admin' ? 0 : $actorId;

if (!$actorId || $recipientRole === '') {
    header('Location: ../html/login.html?error=login_required');
    exit();
}

$notificationsStmt = $conn->prepare(
    'SELECT n.notification_message, n.notification_date, n.read_at,
            p.programme_name, b.booking_date, b.booking_time
     FROM notifications n
     JOIN bookings b ON b.booking_id = n.booking_id
     JOIN programmes p ON p.programme_id = b.programme_id
     WHERE n.recipient_role = ? AND n.recipient_id = ?
     ORDER BY n.notification_date DESC
     LIMIT 100'
);
$notificationsStmt->bind_param('si', $recipientRole, $recipientId);
$notificationsStmt->execute();
$notifications = $notificationsStmt->get_result();
$dashboard = $role === 'student' ? 'dashboard.php' : ($role === 'tutor' ? 'tutor_dashboard.php' : 'admin_dashboard.php');

?><!DOCTYPE html>
<html lang="en-ZA">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My notifications</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <main class="dashboard">
    <h1>My notifications</h1>
    <section class="card table-card">
      <div class="table-scroll">
        <table>
          <thead><tr><th>Message</th><th>Date</th></tr></thead>
          <tbody>
          <?php if ($notifications->num_rows === 0): ?>
            <tr><td colspan="2">No notifications yet.</td></tr>
          <?php endif; ?>
          <?php while ($notification = $notifications->fetch_assoc()): ?>
            <tr>
              <td><?php echo escapeHtml(formatBookingNotificationMessage(
                  $notification['notification_message'],
                  $notification['programme_name'],
                  $notification['booking_date'],
                  $notification['booking_time']
              )); ?></td>
              <td><?php echo escapeHtml($notification['notification_date']); ?></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
    <p class="page-link"><a href="<?php echo escapeHtml($dashboard); ?>">Back to dashboard</a></p>
  </main>
</body>
</html>
