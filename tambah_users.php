<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'])) {
    $role = $_POST['role'];
    $redirectPage = [
        'admin' => 'Tambah_user/tambah_admin.php',
        'guru'  => 'Tambah_user/tambah_guru.php',
        'siswa' => 'Tambah_user/tambah_siswa.php',
    ];

    $target = $redirectPage[$role] ?? 'tabel_user/daftar_user.php';
    header('Location: ' . app_url($target));
    exit;
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pilih Role User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1328">
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>

<div class="container role-wrapper">
    <div class="form-card">
        <h3 class="text-center">Pilih Role User</h3>
        <form action="" method="POST">
            <div class="mb-4">
                <label for="role" class="form-label fw-semibold">Pilih role yang ingin ditambahkan</label>
                <select name="role" id="role" class="form-select" required>
                    <option value="">-- Pilih Role --</option>
                    <option value="admin">Admin</option>
                    <option value="guru">Guru</option>
                    <option value="siswa">Siswa</option>
                </select>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?= htmlspecialchars(app_url('tabel_user/daftar_user.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Lanjut</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
</body>
</html>