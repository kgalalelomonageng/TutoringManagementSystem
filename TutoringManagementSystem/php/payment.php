<?php

require_once 'db_connect.php';
require_once 'auth_check.php';
require_once 'app_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        renderMessagePage('Invalid request', 'Reload the payment form and try again.', [
            'Return to payment' => 'payment.php'
        ]);
        exit();
    }

    $programmeId = filter_input(INPUT_POST, 'programme_id', FILTER_VALIDATE_INT);

    if (!$programmeId) {
        http_response_code(400);
        renderMessagePage('Invalid payment', 'Choose a valid programme.', [
            'Return to payment' => 'payment.php'
        ]);
        exit();
    }

    $programmeStmt = $conn->prepare(
        'SELECT programme_name, fee_amount FROM programmes WHERE programme_id = ?'
    );
    $programmeStmt->bind_param('i', $programmeId);
    $programmeStmt->execute();
    $programme = $programmeStmt->get_result()->fetch_assoc();

    if (!$programme) {
        http_response_code(400);
        renderMessagePage('Programme unavailable', 'Select a programme that is currently offered.', [
            'Return to payment' => 'payment.php'
        ]);
        exit();
    }

    $studentId = (int) $_SESSION['student_id'];
    $amount = (float) $programme['fee_amount'];

    try {
        $conn->begin_transaction();

        $paymentStmt = $conn->prepare(
            "INSERT INTO payments (student_id, programme_id, amount_paid, payment_date, payment_status)
             VALUES (?, ?, ?, CURDATE(), 'Pending')"
        );
        $paymentStmt->bind_param('iid', $studentId, $programmeId, $amount);
        $paymentStmt->execute();
        $paymentId = $conn->insert_id;

        $enrolmentStmt = $conn->prepare(
            "INSERT INTO enrolments
                (payment_id, enrolment_date, enrolment_status)
             VALUES (?, CURDATE(), 'Pending')"
        );
        $enrolmentStmt->bind_param('i', $paymentId);
        $enrolmentStmt->execute();
        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        error_log('Programme payment recording failed: ' . $exception->getMessage());
        http_response_code(500);
        renderMessagePage('Payment record failed', 'The payment and enrolment could not be recorded. Please try again.', [
            'Return to dashboard' => 'dashboard.php'
        ]);
        exit();
    }

    renderMessagePage(
        'Payment submitted',
        'Recorded ' . $programme['programme_name'] . ' at R' . number_format($amount, 2) . '. An administrator must verify it before enrolment is activated.',
        [
            'Return to dashboard' => 'dashboard.php',
            'Record another payment' => 'payment.php'
        ]
    );
    exit();
}

$programmes = $conn->query('SELECT programme_id, programme_name, fee_amount FROM programmes ORDER BY programme_name');

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Programme payment</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <main class="container">
    <h1>Programme payment</h1>
    <p class="page-link">Submit the payment amount for administrator verification. This form does not charge a card.</p>
    <form id="paymentForm" action="payment.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo escapeHtml(getCsrfToken()); ?>">
      <div>
        <label for="programme_id">Select programme</label>
        <select id="programme_id" name="programme_id" required>
          <option value="">Select a programme</option>
          <?php while ($programme = $programmes->fetch_assoc()): ?>
            <option value="<?php echo (int) $programme['programme_id']; ?>">
              <?php echo escapeHtml($programme['programme_name']); ?> — R<?php echo number_format((float) $programme['fee_amount'], 2); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <button type="submit">Submit for verification</button>
      <p class="page-link"><a href="dashboard.php">Back to dashboard</a></p>
    </form>
  </main>
  <script src="../js/main.js"></script>
</body>
</html>
