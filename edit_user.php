<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../koneksi.php';

// 1. Ambil ID & Validasi User
$id = $_GET['id'] ?? 0;
$queryUser = mysqli_query($koneksi, "SELECT * FROM users WHERE id = '$id'");

if (!$queryUser || mysqli_num_rows($queryUser) == 0) {
    die("Data user tidak ditemukan.");
}

$user = mysqli_fetch_assoc($queryUser);
$role = $user['role'];

// 2. Ambil Profil Detail (Siswa / Guru)
$detail = null;
if ($role == 'siswa' || $role == 'guru') {
    $table = $role;
    $queryDetail = mysqli_query($koneksi, "SELECT * FROM $table WHERE user_id = '$id'");
    if ($queryDetail) $detail = mysqli_fetch_assoc($queryDetail);
}

// Helper untuk penanda opsi <option selected>
function isSelected($val1, $val2) {
    return $val1 === $val2 ? 'selected' : '';
}

// 3. Proses Update Data (Saat Form Dikitim)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = true;

    if ($role == 'siswa') {
        $nis      = mysqli_real_escape_string($koneksi, $_POST['nis']);
        $nama     = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $kelas    = mysqli_real_escape_string($koneksi, $_POST['kelas']);
        $jk       = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);

        $status = mysqli_query($koneksi, "UPDATE siswa SET nis='$nis', nama='$nama', kelas='$kelas', jenis_kelamin='$jk' WHERE user_id='$id'");
    } 
    elseif ($role == 'guru') {
        $nip  = mysqli_real_escape_string($koneksi, $_POST['nip']);
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $jk   = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);

        $status = mysqli_query($koneksi, "UPDATE guru SET nip='$nip', nama='$nama', jenis_kelamin='$jk' WHERE user_id='$id'");
    } 
    elseif ($role == 'admin') {
        $username = mysqli_real_escape_string($koneksi, $_POST['username']);
        $password = $_POST['password'] ?? '';

        if (!empty($password)) {
            $passHash = password_hash($password, PASSWORD_DEFAULT);
            $query    = "UPDATE users SET username='$username', password='$passHash' WHERE id='$id'";
        } else {
            $query    = "UPDATE users SET username='$username' WHERE id='$id'";
        }
        $status = mysqli_query($koneksi, $query);
    }

    if (!$status) {
        die("Gagal mengubah data " . htmlspecialchars($role) . ": " . mysqli_error($koneksi));
    }

    header('Location: ' . app_url('tabel_user/daftar_user.php'));
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1328">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-4">Edit Data</h3>

                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($role); ?>" disabled>
                    </div>

                    <?php if (($role == 'siswa' && $detail) || ($role == 'guru' && $detail) || $role == 'admin'): ?>
                        <hr>
                        <form action="" method="POST">
                            
                            <!-- FIELD UNTUK SISWA -->
                            <?php if ($role == 'siswa'): ?>
                                <h5 class="fw-bold mb-3">Data Siswa</h5>
                                <div class="mb-3">
                                    <label class="form-label">NIS</label>
                                    <input type="text" name="nis" class="form-control" value="<?= htmlspecialchars($detail['nis']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama</label>
                                    <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($detail['nama']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Kelas</label>
                                    <input type="text" name="kelas" class="form-control" value="<?= htmlspecialchars($detail['kelas']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" class="form-select" required>
                                        <option value="">-- Pilih --</option>
                                        <option value="L" <?= isSelected($detail['jenis_kelamin'], 'L'); ?>>Laki-laki</option>
                                        <option value="P" <?= isSelected($detail['jenis_kelamin'], 'P'); ?>>Perempuan</option>
                                    </select>
                                </div>

                            <!-- FIELD UNTUK GURU -->
                            <?php elseif ($role == 'guru'): ?>
                                <h5 class="fw-bold mb-3">Data Guru</h5>
                                <div class="mb-3">
                                    <label class="form-label">NIP</label>
                                    <input type="text" name="nip" class="form-control" value="<?= htmlspecialchars($detail['nip']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama</label>
                                    <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($detail['nama']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" class="form-select" required>
                                        <option value="">-- Pilih --</option>
                                        <option value="L" <?= isSelected($detail['jenis_kelamin'], 'L'); ?>>Laki-laki</option>
                                        <option value="P" <?= isSelected($detail['jenis_kelamin'], 'P'); ?>>Perempuan</option>
                                    </select>
                                </div>

                            <!-- FIELD UNTUK ADMIN -->
                            <?php elseif ($role == 'admin'): ?>
                                <h5 class="fw-bold mb-3">Data Admin</h5>
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password Baru</label>
                                    <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengganti password">
                                    <small class="text-muted">Kosongkan jika password lama tetap digunakan.</small>
                                </div>
                            <?php endif; ?>

                            <!-- TOMBOL UNTUK SEMUA ROLE -->
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                <a href="<?= htmlspecialchars(app_url('tabel_user/daftar_user.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Batal</a>
                            </div>

                        </form>

                    <?php else: ?>
                        <div class="alert alert-warning">
                            Data <?= htmlspecialchars($role); ?> tidak ditemukan.
                        </div>
                        <a href="<?= htmlspecialchars(app_url('tabel_user/daftar_user.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Kembali</a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>