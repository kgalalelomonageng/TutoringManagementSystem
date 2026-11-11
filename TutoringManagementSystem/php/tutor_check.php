<?php

require_once 'session.php';

if (($_SESSION['account_role'] ?? '') !== 'tutor' || !isset($_SESSION['tutor_id'])) {
    header('Location: ../html/login.html?error=tutor_required');
    exit();
}

?>
