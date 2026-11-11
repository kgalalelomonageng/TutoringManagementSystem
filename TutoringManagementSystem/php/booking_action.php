<?php

require_once 'db_connect.php';
require_once 'session.php';
require_once 'app_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    renderMessagePage('Method not allowed', 'Submit this action using the session page.');
    exit();
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    renderMessagePage('Invalid request', 'Reload the session page and try again.', [
        'My sessions' => 'my_bookings.php'
    ]);
    exit();
}

$role = $_SESSION['account_role'] ?? '';
$bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
$newStatus = $_POST['booking_status'] ?? '';
$actorId = $role === 'student'
    ? (int) ($_SESSION['student_id'] ?? 0)
    : ($role === 'tutor' ? (int) ($_SESSION['tutor_id'] ?? 0) : (int) ($_SESSION['admin_id'] ?? 0));

if (!in_array($role, ['student', 'tutor', 'admin'], true) || !$actorId || !$bookingId) {
    http_response_code(403);
    renderMessagePage('Not authorized', 'Sign in with an account allowed to manage this session.', [
        'Login' => '../html/login.html'
    ]);
    exit();
}

$allowedTransitions = [
    'student' => [
        'Requested' => ['Cancelled'],
        'Confirmed' => ['Cancelled']
    ],
    'tutor' => [
        'Requested' => ['Confirmed', 'Cancelled'],
        'Confirmed' => ['Completed', 'Cancelled']
    ],
    'admin' => [
        'Requested' => ['Confirmed', 'Cancelled'],
        'Confirmed' => ['Completed', 'Cancelled']
    ]
];

try {
    $conn->begin_transaction();
    $bookingStmt = $conn->prepare(
        'SELECT booking_id, student_id, tutor_id, booking_status
         FROM bookings WHERE booking_id = ? FOR UPDATE'
    );
    $bookingStmt->bind_param('i', $bookingId);
    $bookingStmt->execute();
    $booking = $bookingStmt->get_result()->fetch_assoc();

    $ownsBooking = $booking && (
        ($role === 'student' && (int) $booking['student_id'] === $actorId) ||
        ($role === 'tutor' && (int) $booking['tutor_id'] === $actorId) ||
        $role === 'admin'
    );
    $transitions = $booking
        ? ($allowedTransitions[$role][$booking['booking_status']] ?? [])
        : [];

    if (!$ownsBooking || !is_string($newStatus) || !in_array($newStatus, $transitions, true)) {
        $conn->rollback();
        http_response_code(400);
        renderMessagePage('Session not updated', 'This session is unavailable or the requested status change is not allowed.', [
            'My sessions' => 'my_bookings.php'
        ]);
        exit();
    }

    $update = $conn->prepare('UPDATE bookings SET booking_status = ? WHERE booking_id = ?');
    $update->bind_param('si', $newStatus, $bookingId);
    $update->execute();
    addBookingNotifications(
        $conn,
        $bookingId,
        'Session status changed to ' . strtolower($newStatus) . '.'
    );
    $conn->commit();
} catch (mysqli_sql_exception | RuntimeException $exception) {
    $conn->rollback();
    error_log('Booking status update failed: ' . $exception->getMessage());
    http_response_code(500);
    renderMessagePage('Session update failed', 'The session status could not be saved.', [
        'My sessions' => 'my_bookings.php'
    ]);
    exit();
}

renderMessagePage('Session updated', 'The session was updated and notifications were recorded for all participants.', [
    'My sessions' => 'my_bookings.php'
]);

?>
