<?php

require_once 'session.php';

if (($_SESSION['account_role'] ?? '') !== 'admin' || !isset($_SESSION['admin_id'])) {
    header('Location: ../html/login.html?error=admin_required');
    exit();
}

?>
