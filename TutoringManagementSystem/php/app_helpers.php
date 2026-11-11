<?php

function escapeHtml($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function renderMessagePage($title, $message, $links = [])
{
    $safeTitle = escapeHtml($title);
    $safeMessage = escapeHtml($message);

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . $safeTitle . '</title><link rel="stylesheet" href="../css/style.css">';
    echo '</head><body><main class="container"><h1>' . $safeTitle . '</h1>';
    echo '<p class="page-link">' . $safeMessage . '</p>';

    foreach ($links as $label => $url) {
        echo '<p class="page-link"><a href="' . escapeHtml($url) . '">' . escapeHtml($label) . '</a></p>';
    }

    echo '</main></body></html>';
}

function requirePost()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        renderMessagePage('Method not allowed', 'Submit this form using the provided page.');
        exit;
    }
}

function getCsrfToken()
{
    require_once 'session.php';

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token)
{
    require_once 'session.php';

    return is_string($token) &&
        isset($_SESSION['csrf_token']) &&
        hash_equals($_SESSION['csrf_token'], $token);
}

function formatBookingNotificationMessage(
    string $message,
    string $programmeName,
    string $bookingDate,
    string $bookingTime
): string {
    if (preg_match('/status changed to (requested|confirmed|cancelled|completed)/i', $message, $matches)) {
        $event = 'Status changed to ' . strtolower($matches[1]) . '.';
    } elseif (stripos($message, 'awaiting tutor confirmation') !== false) {
        $event = 'Awaiting tutor confirmation.';
    } elseif (stripos($message, 'details or status') !== false) {
        $event = 'Session details or status updated by an administrator.';
    } elseif (stripos($message, 'requested by an administrator') !== false) {
        $event = 'Session requested by an administrator.';
    } elseif (stripos($message, 'requested') !== false) {
        $event = 'Session requested.';
    } else {
        $event = preg_replace('/^(?:Tutoring|Demo) session #\d+\s*/i', '', $message);
    }

    $date = DateTime::createFromFormat('!Y-m-d', $bookingDate);
    $sessionDate = $date ? $date->format('j F Y') : $bookingDate;

    return $programmeName . ' session (' . $sessionDate . ', ' .
        substr($bookingTime, 0, 5) . '): ' . $event;
}

function addBookingNotifications(mysqli $conn, int $bookingId, string $message)
{
    $participants = $conn->prepare(
        'SELECT student_id, tutor_id FROM bookings WHERE booking_id = ?'
    );
    $participants->bind_param('i', $bookingId);
    $participants->execute();
    $booking = $participants->get_result()->fetch_assoc();

    if (!$booking) {
        throw new RuntimeException('Cannot notify participants for a missing booking.');
    }

    $notification = $conn->prepare(
        'INSERT INTO notifications (booking_id, recipient_role, recipient_id, notification_message)
         VALUES (?, ?, ?, ?)'
    );

    foreach ([
        ['student', (int) $booking['student_id']],
        ['tutor', (int) $booking['tutor_id']],
        ['admin', 0]
    ] as [$role, $recipientId]) {
        $notification->bind_param('isis', $bookingId, $role, $recipientId, $message);
        $notification->execute();
    }
}

?>
