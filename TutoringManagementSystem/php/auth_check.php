<?php

require_once 'session.php';

if (
    !isset($_SESSION['student_id']) ||
    ($_SESSION['account_role'] ?? '') !== 'student'
)
{
    header("Location: ../html/login.html?error=login_required");
    exit();
}

?>