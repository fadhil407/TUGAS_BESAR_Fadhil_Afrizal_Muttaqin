<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');

include __DIR__ . '/../koneksi.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "DELETE FROM mapel WHERE id = $id";
    mysqli_query($koneksi, $query);
    header('Location: ' . app_url('tabel_user/daftar_mapel.php'));
    exit();
}
?>