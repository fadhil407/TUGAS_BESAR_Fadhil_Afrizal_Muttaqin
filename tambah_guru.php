<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip = trim($_POST['nip'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $jk = $_POST['jk'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nip === '' || $nama === '' || $jk === '' || $username === '' || $password === '') {
        $error = 'Semua field harus diisi.';
    } else {
        $nip = mysqli_real_escape_string($koneksi, $nip);
        $nama = mysqli_real_escape_string($koneksi, $nama);
        $jk = mysqli_real_escape_string($koneksi, $jk);
        $username = mysqli_real_escape_string($koneksi, $username);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $queryUser = "INSERT INTO users (username, password, role, created_at) VALUES ('$username', '$passwordHash', 'guru', NOW())";
        if (mysqli_query($koneksi, $queryUser)) {
            $userId = mysqli_insert_id($koneksi);
            $queryGuru = "INSERT INTO guru (nip, nama, jenis_kelamin, user_id) VALUES ('$nip', '$nama', '$jk', '$userId')";

            if (mysqli_query($koneksi, $queryGuru)) {
                header('Location: ' . app_url('tabel_user/daftar_user.php'));
                exit;
            }

            $error = 'Gagal menyimpan data guru: ' . mysqli_error($koneksi);
        } else {
            $error = 'Gagal menyimpan data akun guru: ' . mysqli_error($koneksi);
        }
    }
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Guru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1328">
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>

<div class="container mt-4 mb-5">
    <div class="form-card">
        <h3 class="text-center">Form Tambah Guru</h3>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-section">
                <h4>Data Profil Guru</h4>
                <div class="mb-3">
                    <label for="nip" class="form-label fw-semibold">NIP</label>
                    <input type="text" id="nip" name="nip" class="form-control" placeholder="Contoh: 198203152008011009" value="<?= htmlspecialchars($_POST['nip'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="nama" class="form-label fw-semibold">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" class="form-control" placeholder="Contoh: Fadhil Afrizal, S.Pd" value="<?= htmlspecialchars($_POST['nama'] ?? ''); ?>" required>
                </div>

                <p class="mb-1 fw-semibold">Jenis Kelamin:</p>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="jk" id="jkL" value="L" <?= (($_POST['jk'] ?? '') === 'L') ? 'checked' : ''; ?> required>
                    <label class="form-check-label" for="jkL">Laki-laki</label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="jk" id="jkP" value="P" <?= (($_POST['jk'] ?? '') === 'P') ? 'checked' : ''; ?> required>
                    <label class="form-check-label" for="jkP">Perempuan</label>
                </div>
            </div>

            <div class="form-section">
                <h4>Data Kredensial Akun</h4>
                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Contoh: fadhil_guru" value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>" required>
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