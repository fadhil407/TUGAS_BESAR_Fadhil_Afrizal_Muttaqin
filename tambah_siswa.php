<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = trim($_POST['nis'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $kelas = trim($_POST['kelas'] ?? '');
    $jk = $_POST['jk'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nis === '' || $nama === '' || $kelas === '' || $jk === '' || $username === '' || $password === '') {
        $error = 'Semua field harus diisi.';
    } else {
        $nis = mysqli_real_escape_string($koneksi, $nis);
        $nama = mysqli_real_escape_string($koneksi, $nama);
        $kelas = mysqli_real_escape_string($koneksi, $kelas);
        $jk = mysqli_real_escape_string($koneksi, $jk);
        $username = mysqli_real_escape_string($koneksi, $username);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $queryUser = "INSERT INTO users (username, password, role, created_at) VALUES ('$username', '$passwordHash', 'siswa', NOW())";
        if (mysqli_query($koneksi, $queryUser)) {
            $userId = mysqli_insert_id($koneksi);
            $querySiswa = "INSERT INTO siswa (nis, nama, kelas, jenis_kelamin, user_id) VALUES ('$nis', '$nama', '$kelas', '$jk', '$userId')";

            if (mysqli_query($koneksi, $querySiswa)) {
                header('Location: ' . app_url('tabel_user/daftar_user.php'));
                exit;
            }

            $error = 'Gagal menyimpan data siswa: ' . mysqli_error($koneksi);
        } else {
            $error = 'Gagal menyimpan data akun siswa: ' . mysqli_error($koneksi);
        }
    }
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1328">
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>

<div class="container mt-4 mb-5">
    <div class="form-card">
        <h3 class="text-center">Form Tambah Siswa</h3>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-section">
                <h4>Data Profil Siswa</h4>
                <div class="mb-3">
                    <label for="nis" class="form-label fw-semibold">NIS</label>
                    <input type="text" id="nis" name="nis" class="form-control" placeholder="Contoh: 12345678" value="<?= htmlspecialchars($_POST['nis'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="nama" class="form-label fw-semibold">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" class="form-control" placeholder="Contoh: Fadhil Afrizal" value="<?= htmlspecialchars($_POST['nama'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="kelas" class="form-label fw-semibold">Kelas</label>
                    <input type="text" id="kelas" name="kelas" class="form-control" placeholder="Contoh: XI RPL 1" value="<?= htmlspecialchars($_POST['kelas'] ?? ''); ?>" required>
                </div>

                <p class="mb-1 fw-semibold">Jenis Kelamin:</p>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="jk" id="jkL" value="L" <?= (($_POST['jk'] ?? '') === 'L') ? 'checked' : ''; ?> required>
                    <label class="form-check-label" for="jkL">Laki-laki</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="jk" id="jkP" value="P" <?= (($_POST['jk'] ?? '') === 'P') ? 'checked' : ''; ?> required>
                    <label class="form-check-label" for="jkP">Perempuan</label>
                </div>
            </div>

            <div class="form-section">
                <h4>Data Kredensial Akun</h4>
                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Contoh: Fadhil_123" value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?= htmlspecialchars(app_url('tabel_user/daftar_user.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
</body>
</html>