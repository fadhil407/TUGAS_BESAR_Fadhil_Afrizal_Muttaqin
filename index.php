<?php
require_once __DIR__ . '/auth.php';

if (!empty($_SESSION['login'])) {
    redirect_to_role_dashboard();
}

header('Location: ' . app_url('login.php'));
exit();
