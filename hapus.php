<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
include __DIR__ . '/../koneksi.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    echo "<script>alert('ID tidak valid'); window.location.href=" . json_encode(app_url('tabel_user/daftar_users.php')) . ";</script>";
    exit;
}

$userQuery = mysqli_query($koneksi, "SELECT role FROM users WHERE id = $id LIMIT 1");
if (!$userQuery || mysqli_num_rows($userQuery) == 0) {
    echo "<script>alert('Data tidak ditemukan'); window.location.href=" . json_encode(app_url('tabel_user/daftar_users.php')) . ";</script>";
    exit;
}

$user = mysqli_fetch_assoc($userQuery);
$role = $user['role'];

mysqli_begin_transaction($koneksi);

try {
    if ($role == 'guru') {
        $deleteProfil = mysqli_query($koneksi, "DELETE FROM guru WHERE user_id = $id");
    } elseif ($role == 'siswa') {
        $deleteProfil = mysqli_query($koneksi, "DELETE FROM siswa WHERE user_id = $id");
    } else {
        $deleteProfil = true;
    }

    if (!$deleteProfil) {
        throw new Exception(mysqli_error($koneksi));
    }

    $deleteUser = mysqli_query($koneksi, "DELETE FROM users WHERE id = $id");
    if (!$deleteUser) {
        throw new Exception(mysqli_error($koneksi));
    }

    mysqli_commit($koneksi);

    echo "
    <script>
      alert('Data berhasil dihapus');
      window.location.href = " . json_encode(app_url('tabel_user/daftar_users.php')) . ";
    </script>
    ";
    exit;
} catch (Exception $e) {
    mysqli_rollback($koneksi);

    echo "
    <script>
      alert('Data gagal dihapus: " . addslashes($e->getMessage()) . "');
      window.location.href = " . json_encode(app_url('tabel_user/daftar_users.php')) . ";
    </script>
    ";
    exit;
}
?>