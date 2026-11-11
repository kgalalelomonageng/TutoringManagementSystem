<?php

require_once 'session.php';
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['login'])) {
    header('Location: ../html/login.html');
    exit();
}

$emailInput = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!is_string($emailInput) || !is_string($password)) {
    header('Location: ../html/login.html?error=invalid_login');
    exit();
}

$email = trim($emailInput);

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    header('Location: ../html/login.html?error=invalid_login');
    exit();
}

$stmt = $conn->prepare(
    'SELECT student_id AS account_id, first_name AS account_name, password
     FROM students WHERE email = ?'
);
$stmt->bind_param('s', $email);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$role = 'student';

if (!$account) {
    $stmt = $conn->prepare(
        'SELECT tutor_id AS account_id, first_name AS account_name, password
         FROM tutors WHERE email = ?'
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    $role = 'tutor';
}

if (!$account) {
    $stmt = $conn->prepare(
        'SELECT admin_id AS account_id, first_name AS account_name, password
         FROM administrators WHERE email = ?'
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    $role = 'admin';
}

if (
    !$account ||
    !is_string($account['password']) ||
    !password_verify($password, $account['password'])
) {
    header('Location: ../html/login.html?error=invalid_login');
    exit();
}

session_regenerate_id(true);
$_SESSION['account_role'] = $role;

if ($role === 'admin') {
    $_SESSION['admin_id'] = (int) $account['account_id'];
    $_SESSION['admin_name'] = $account['account_name'];
    header('Location: admin_dashboard.php');
} elseif ($role === 'tutor') {
    $_SESSION['tutor_id'] = (int) $account['account_id'];
    $_SESSION['tutor_name'] = $account['account_name'];
    header('Location: tutor_dashboard.php');
} else {
    $_SESSION['student_id'] = (int) $account['account_id'];
    $_SESSION['student_name'] = $account['account_name'];
    header('Location: dashboard.php');
}

exit();

?>