<?php
$assignmentId = isset($_GET['id']) && is_scalar($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : false;
if (!$assignmentId || $assignmentId < 1) {
    header('Location: ' . app_url('mengajar/kelola_mengajar.php'));
    exit();
}
require __DIR__ . '/form_mengajar.php';
