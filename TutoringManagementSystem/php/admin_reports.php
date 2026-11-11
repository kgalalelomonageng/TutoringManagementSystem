<?php

require_once 'db_connect.php';
require_once 'admin_check.php';
require_once 'app_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        renderMessagePage('Invalid request', 'Reload the payment report and try again.', [
            'Payment report' => 'admin_reports.php'
        ]);
        exit();
    }

    $paymentId = filter_input(INPUT_POST, 'payment_id', FILTER_VALIDATE_INT);
    if (!$paymentId) {
        http_response_code(400);
        renderMessagePage('Invalid payment', 'Choose a valid payment record.', [
            'Payment report' => 'admin_reports.php'
        ]);
        exit();
    }

    try {
        $conn->begin_transaction();
        $paymentStmt = $conn->prepare(
            "UPDATE payments SET payment_status = 'Paid'
             WHERE payment_id = ? AND payment_status = 'Pending'"
        );
        $paymentStmt->bind_param('i', $paymentId);
        $paymentStmt->execute();

        if ($paymentStmt->affected_rows !== 1) {
            $conn->rollback();
            renderMessagePage('Payment not verified', 'This payment is no longer pending or does not exist.', [
                'Payment report' => 'admin_reports.php'
            ]);
            exit();
        }

        $enrolmentStmt = $conn->prepare(
            "UPDATE enrolments SET enrolment_status = 'Active'
             WHERE payment_id = ? AND enrolment_status = 'Pending'"
        );
        $enrolmentStmt->bind_param('i', $paymentId);
        $enrolmentStmt->execute();
        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        error_log('Payment verification failed: ' . $exception->getMessage());
        http_response_code(500);
        renderMessagePage('Payment verification failed', 'The payment could not be verified.', [
            'Payment report' => 'admin_reports.php'
        ]);
        exit();
    }

    renderMessagePage('Payment verified', 'The payment and associated enrolment are now active.', [
        'Payment report' => 'admin_reports.php'
    ]);
    exit();
}

$payments = $conn->query(
    'SELECT p.payment_id, p.amount_paid, p.payment_date, p.payment_status,
            s.first_name, s.last_name, pr.programme_name
     FROM payments p
     JOIN students s ON s.student_id = p.student_id
     JOIN programmes pr ON pr.programme_id = p.programme_id
     ORDER BY p.payment_date DESC, p.payment_id DESC
     LIMIT 200'
);

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment report</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <main class="dashboard">
    <h1>Payment report</h1>
    <section class="card table-card">
      <div class="table-scroll">
        <table>
          <thead><tr><th>ID</th><th>Student</th><th>Programme</th><th>Amount</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
          <?php while ($payment = $payments->fetch_assoc()): ?>
            <tr>
              <td><?php echo (int) $payment['payment_id']; ?></td>
              <td><?php echo escapeHtml($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
              <td><?php echo escapeHtml($payment['programme_name']); ?></td>
              <td>R<?php echo number_format((float) $payment['amount_paid'], 2); ?></td>
              <td><?php echo escapeHtml($payment['payment_date']); ?></td>
              <td><?php echo escapeHtml($payment['payment_status']); ?></td>
              <td>
                <?php if ($payment['payment_status'] === 'Pending'): ?>
                  <form method="POST" action="admin_reports.php">
                    <input type="hidden" name="csrf_token" value="<?php echo escapeHtml(getCsrfToken()); ?>">
                    <input type="hidden" name="payment_id" value="<?php echo (int) $payment['payment_id']; ?>">
                    <button class="button-small button-verify" type="submit">Verify</button>
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
    <p class="page-link"><a href="admin_dashboard.php">Back to admin dashboard</a></p>
  </main>
</body>
</html>
