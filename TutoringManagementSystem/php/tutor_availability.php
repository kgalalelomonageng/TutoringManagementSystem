<?php

require_once 'db_connect.php';
require_once 'session.php';
require_once 'app_helpers.php';

$role = $_SESSION['account_role'] ?? '';
$adminId = (int) ($_SESSION['admin_id'] ?? 0);
$tutorId = (int) ($_SESSION['tutor_id'] ?? 0);

if (!(($role === 'admin' && $adminId) || ($role === 'tutor' && $tutorId))) {
    header('Location: ../html/login.html?error=login_required');
    exit();
}

$message = '';
$isError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $isError = true;
        $message = 'This request expired. Reload the page and try again.';
    } else {
        $selectedTutorId = $role === 'tutor'
            ? $tutorId
            : filter_var($_POST['tutor_id'] ?? null, FILTER_VALIDATE_INT);
        $availableDate = $_POST['available_date'] ?? '';
        $availableTime = $_POST['available_time'] ?? '';
        $status = $_POST['availability_status'] ?? '';
        $date = is_string($availableDate) ? DateTime::createFromFormat('!Y-m-d', $availableDate) : false;

        if (
            !$selectedTutorId ||
            !$date ||
            $date->format('Y-m-d') !== $availableDate ||
            $availableDate < date('Y-m-d') ||
            !is_string($availableTime) ||
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $availableTime) ||
            !in_array($status, ['Available', 'Unavailable'], true)
        ) {
            http_response_code(400);
            $isError = true;
            $message = 'Enter a valid tutor, date, time, and availability status.';
        } else {
            $availabilityStmt = $conn->prepare(
                'INSERT INTO tutor_availability (tutor_id, available_date, available_time, availability_status)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE availability_status = VALUES(availability_status)'
            );
            $availabilityStmt->bind_param('isss', $selectedTutorId, $availableDate, $availableTime, $status);

            try {
                $availabilityStmt->execute();
                $message = 'Availability updated.';
            } catch (mysqli_sql_exception $exception) {
                error_log('Tutor availability update failed: ' . $exception->getMessage());
                http_response_code(500);
                $isError = true;
                $message = 'The tutor availability could not be saved.';
            }
        }
    }
}

if ($role === 'admin') {
    $tutors = $conn->query(
        'SELECT tutor_id, first_name, last_name, expertise_subject
         FROM tutors ORDER BY first_name, last_name'
    );
    $slotsStmt = $conn->prepare(
        'SELECT a.available_date, a.available_time, a.availability_status,
                CONCAT(t.first_name, " ", t.last_name) AS tutor_name, t.expertise_subject
         FROM tutor_availability a
         JOIN tutors t ON t.tutor_id = a.tutor_id
         WHERE a.available_date >= CURDATE()
         ORDER BY a.available_date, a.available_time
         LIMIT 200'
    );
    $slotsStmt->execute();
} else {
    $tutors = null;
    $slotsStmt = $conn->prepare(
        'SELECT available_date, available_time, availability_status, NULL AS tutor_name, NULL AS expertise_subject
         FROM tutor_availability
         WHERE tutor_id = ? AND available_date >= CURDATE()
         ORDER BY available_date, available_time
         LIMIT 200'
    );
    $slotsStmt->bind_param('i', $tutorId);
    $slotsStmt->execute();
}
$slots = $slotsStmt->get_result();
$dashboard = $role === 'admin' ? 'admin_dashboard.php' : 'tutor_dashboard.php';

?><!DOCTYPE html>
<html lang="en-ZA">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $role === 'admin' ? 'Tutor availability' : 'My availability'; ?></title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <main class="dashboard">
    <h1><?php echo $role === 'admin' ? 'Tutor availability' : 'Manage my availability'; ?></h1>
    <?php if ($message !== ''): ?>
      <p class="<?php echo $isError ? 'status-message setup-error' : 'status-message'; ?>" role="status"><?php echo escapeHtml($message); ?></p>
    <?php endif; ?>
    <section class="card">
      <h2>Add or update a time slot</h2>
      <form id="availabilityForm" method="POST" action="tutor_availability.php">
        <input type="hidden" name="csrf_token" value="<?php echo escapeHtml(getCsrfToken()); ?>">
        <?php if ($role === 'admin'): ?>
          <div>
            <label for="tutor_id">Tutor</label>
            <select id="tutor_id" name="tutor_id" required>
              <option value="">Select a tutor</option>
              <?php while ($tutor = $tutors->fetch_assoc()): ?>
                <option value="<?php echo (int) $tutor['tutor_id']; ?>">
                  <?php echo escapeHtml($tutor['first_name'] . ' ' . $tutor['last_name'] . ' — ' . $tutor['expertise_subject']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
        <?php endif; ?>
        <div>
          <label for="available_date">Available date</label>
          <input id="available_date" type="date" name="available_date" min="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div>
          <label for="available_time">Available time</label>
          <input id="available_time" type="time" name="available_time" required>
        </div>
        <div>
          <label for="availability_status">Status</label>
          <select id="availability_status" name="availability_status" required>
            <option value="Available">Available</option>
            <option value="Unavailable">Unavailable</option>
          </select>
        </div>
        <button type="submit">Save availability</button>
      </form>
    </section>
    <section class="card table-card">
      <h2>Upcoming availability</h2>
      <div class="table-scroll">
        <table>
          <thead><tr><?php if ($role === 'admin'): ?><th>Tutor</th><th>Subject</th><?php endif; ?><th>Date</th><th>Time</th><th>Status</th></tr></thead>
          <tbody>
          <?php if ($slots->num_rows === 0): ?>
            <tr><td colspan="<?php echo $role === 'admin' ? 5 : 3; ?>">No upcoming availability has been set.</td></tr>
          <?php endif; ?>
          <?php while ($slot = $slots->fetch_assoc()): ?>
            <tr>
              <?php if ($role === 'admin'): ?>
                <td><?php echo escapeHtml($slot['tutor_name']); ?></td>
                <td><?php echo escapeHtml($slot['expertise_subject']); ?></td>
              <?php endif; ?>
              <td><?php echo escapeHtml($slot['available_date']); ?></td>
              <td><?php echo escapeHtml(substr($slot['available_time'], 0, 5)); ?></td>
              <td><?php echo escapeHtml($slot['availability_status']); ?></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
    <p class="page-link"><a href="<?php echo $dashboard; ?>">Back to dashboard</a></p>
  </main>
  <script src="../js/main.js"></script>
</body>
</html>
