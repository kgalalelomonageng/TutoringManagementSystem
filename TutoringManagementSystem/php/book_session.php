<?php

require_once 'db_connect.php';
require_once 'auth_check.php';
require_once 'app_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        renderMessagePage('Invalid request', 'Reload the booking form and try again.', [
            'Return to booking' => 'book_session.php'
        ]);
        exit();
    }

    $programmeId = filter_input(INPUT_POST, 'programme_id', FILTER_VALIDATE_INT);
    $bookingDate = $_POST['booking_date'] ?? '';
    $bookingTime = $_POST['booking_time'] ?? '';
    $date = is_string($bookingDate) ? DateTime::createFromFormat('!Y-m-d', $bookingDate) : false;

    if (
        !$programmeId ||
        !$date ||
        $date->format('Y-m-d') !== $bookingDate ||
        $bookingDate < date('Y-m-d') ||
        !is_string($bookingTime) ||
        !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $bookingTime)
    ) {
        http_response_code(400);
        renderMessagePage('Invalid booking', 'Choose a valid programme, date, and time.', [
            'Return to booking' => 'book_session.php'
        ]);
        exit();
    }

    if ((int) $date->format('N') >= 6) {
        http_response_code(400);
        renderMessagePage('Invalid booking date', 'Bookings are only available Monday through Friday.', [
            'Return to booking' => 'book_session.php'
        ]);
        exit();
    }

    $studentId = (int) $_SESSION['student_id'];

    try {
        $conn->begin_transaction();

        $eligibility = $conn->prepare(
            "SELECT p.programme_id
             FROM programmes p
             JOIN enrolments e
               ON e.enrolment_status = 'Active'
             JOIN payments paid
               ON paid.payment_id = e.payment_id
              AND paid.programme_id = p.programme_id
              AND paid.student_id = ?
             WHERE p.programme_id = ?
             LIMIT 1"
        );
        $eligibility->bind_param('ii', $studentId, $programmeId);
        $eligibility->execute();

        if (!$eligibility->get_result()->fetch_assoc()) {
            $conn->rollback();
            http_response_code(403);
            renderMessagePage('Active enrolment required', 'Complete and verify payment for this programme before booking a session.', [
                'View programme payment' => 'payment.php',
                'Return to dashboard' => 'dashboard.php'
            ]);
            exit();
        }

        $tutorStmt = $conn->prepare(
            "SELECT a.availability_id, t.tutor_id
             FROM tutor_availability a
             JOIN tutors t ON t.tutor_id = a.tutor_id
             JOIN programmes p ON LOWER(p.programme_name) = LOWER(t.expertise_subject)
             LEFT JOIN bookings b
               ON b.tutor_id = t.tutor_id
              AND b.booking_date = a.available_date
              AND b.booking_time = a.available_time
              AND b.booking_status IN ('Requested', 'Confirmed')
             WHERE p.programme_id = ?
               AND a.available_date = ?
               AND a.available_time = ?
               AND a.availability_status = 'Available'
               AND t.availability_status = 'Available'
               AND b.booking_id IS NULL
             ORDER BY t.tutor_id
             LIMIT 1
             FOR UPDATE"
        );
        $tutorStmt->bind_param('iss', $programmeId, $bookingDate, $bookingTime);
        $tutorStmt->execute();
        $tutor = $tutorStmt->get_result()->fetch_assoc();

        if (!$tutor) {
            $conn->rollback();
            http_response_code(409);
            renderMessagePage('No tutor available', 'No available tutor with matching subject expertise has that time slot. Choose another slot.', [
                'Try another slot' => 'book_session.php'
            ]);
            exit();
        }

        $tutorId = (int) $tutor['tutor_id'];
        $bookingStmt = $conn->prepare(
            "INSERT INTO bookings
                (student_id, tutor_id, programme_id, booking_date, booking_time, booking_status)
             VALUES (?, ?, ?, ?, ?, 'Requested')"
        );
        $bookingStmt->bind_param('iiiss', $studentId, $tutorId, $programmeId, $bookingDate, $bookingTime);
        $bookingStmt->execute();
        $bookingId = (int) $conn->insert_id;
        addBookingNotifications($conn, $bookingId, 'A new session has been requested.');
        $conn->commit();
    } catch (mysqli_sql_exception | RuntimeException $exception) {
        $conn->rollback();
        error_log('Booking creation failed: ' . $exception->getMessage());
        http_response_code(500);
        renderMessagePage('Booking failed', 'The booking could not be saved. Please try again.', [
            'Return to dashboard' => 'dashboard.php'
        ]);
        exit();
    }

    renderMessagePage('Booking requested', 'Your request has been sent to the assigned tutor and administrator.', [
        'View my sessions' => 'my_bookings.php',
        'Return to dashboard' => 'dashboard.php'
    ]);
    exit();
}

$programmesStmt = $conn->prepare(
    "SELECT DISTINCT p.programme_id, p.programme_name
     FROM programmes p
     JOIN enrolments e
       ON e.enrolment_status = 'Active'
     JOIN payments paid
       ON paid.payment_id = e.payment_id
      AND paid.programme_id = p.programme_id
      AND paid.student_id = ?
     ORDER BY p.programme_name"
);
$programmesStmt->bind_param('i', $_SESSION['student_id']);
$programmesStmt->execute();
$programmes = $programmesStmt->get_result();
$slotsStmt = $conn->prepare(
    "SELECT p.programme_id, a.available_date, a.available_time,
            CONCAT(t.first_name, ' ', t.last_name) AS tutor_name
     FROM tutor_availability a
     JOIN tutors t ON t.tutor_id = a.tutor_id
     JOIN programmes p ON LOWER(p.programme_name) = LOWER(t.expertise_subject)
     JOIN enrolments e
       ON e.enrolment_status = 'Active'
     JOIN payments paid
       ON paid.payment_id = e.payment_id
      AND paid.programme_id = p.programme_id
      AND paid.student_id = ?
     LEFT JOIN bookings b
       ON b.tutor_id = t.tutor_id
      AND b.booking_date = a.available_date
      AND b.booking_time = a.available_time
      AND b.booking_status IN ('Requested', 'Confirmed')
     WHERE a.available_date >= CURDATE()
       AND WEEKDAY(a.available_date) < 5
       AND a.availability_status = 'Available'
       AND t.availability_status = 'Available'
       AND b.booking_id IS NULL
     ORDER BY p.programme_id, a.available_date, a.available_time, t.first_name"
);
$slotsStmt->bind_param('i', $_SESSION['student_id']);
$slotsStmt->execute();
$availableSlots = $slotsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

?><!DOCTYPE html>
<html lang="en-ZA">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book a tutoring session</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <main class="container">
    <h1>Book a tutoring session</h1>
    <p class="page-link">Only verified programme enrolments can book. Tutor subject and availability are matched automatically.</p>
    <?php if ($programmes->num_rows > 0): ?>
      <form action="book_session.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo escapeHtml(getCsrfToken()); ?>">
        <div>
          <label for="programme_id">Enrolled programme</label>
          <select id="programme_id" name="programme_id" required>
            <option value="">Select a programme</option>
            <?php while ($programme = $programmes->fetch_assoc()): ?>
              <option value="<?php echo (int) $programme['programme_id']; ?>"><?php echo escapeHtml($programme['programme_name']); ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <label for="booking_date">Date (weekdays only)</label>
          <select id="booking_date" name="booking_date" required disabled>
            <option value="">Select a programme first</option>
          </select>
        </div>
        <div>
          <label for="booking_time">Available time and tutor</label>
          <select id="booking_time" name="booking_time" required disabled>
            <option value="">Select a date first</option>
          </select>
        </div>
        <button type="submit">Request session</button>
      </form>
    <?php else: ?>
      <p class="status-message">You need an active programme enrolment before booking.</p>
      <p class="page-link"><a href="payment.php">View programme payment options</a></p>
    <?php endif; ?>
    <p class="page-link"><a href="dashboard.php">Back to dashboard</a></p>
  </main>
  <script id="availableSlots" type="application/json"><?php
    echo json_encode(
        $availableSlots,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
    );
  ?></script>
  <script src="../js/main.js"></script>
</body>
</html>
