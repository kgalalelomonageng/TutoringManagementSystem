<?php

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['register'])) {
    header('Location: ../html/register.html');
    exit();
}

$firstNameInput = $_POST['first_name'] ?? '';
$lastNameInput = $_POST['last_name'] ?? '';
$emailInput = $_POST['email'] ?? '';
$phoneInput = $_POST['phone'] ?? '';
$plainPassword = $_POST['password'] ?? '';

if (
    !is_string($firstNameInput) ||
    !is_string($lastNameInput) ||
    !is_string($emailInput) ||
    !is_string($phoneInput) ||
    !is_string($plainPassword)
) {
    header('Location: ../html/register.html?error=invalid_registration');
    exit();
}

$firstName = trim($firstNameInput);
$lastName = trim($lastNameInput);
$email = trim($emailInput);
$phone = trim($phoneInput);

if (
    strlen($firstName) < 2 ||
    strlen($lastName) < 2 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !preg_match('/^[0-9]{10}$/', $phone) ||
    strlen($plainPassword) < 8
) {
    header('Location: ../html/register.html?error=invalid_registration');
    exit();
}

$password = password_hash($plainPassword, PASSWORD_DEFAULT);
$stmt = $conn->prepare(
    'INSERT INTO students
        (first_name, last_name, email, phone, password, registration_date)
     VALUES (?, ?, ?, ?, ?, CURDATE())'
);
$stmt->bind_param('sssss', $firstName, $lastName, $email, $phone, $password);

try {
    $stmt->execute();
} catch (mysqli_sql_exception $exception) {
    if ($exception->getCode() === 1062) {
        header('Location: ../html/register.html?error=email_exists');
        exit();
    }

    error_log('Student registration failed: ' . $exception->getMessage());
    header('Location: ../html/register.html?error=registration_failed');
    exit();
}

header('Location: ../html/login.html?registered=1');
exit();

?>