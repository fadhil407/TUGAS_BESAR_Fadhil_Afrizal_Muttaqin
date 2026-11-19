<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../koneksi.php';

$error = '';
$guruList = mysqli_query($koneksi, "SELECT id, nama FROM guru ORDER BY nama ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kodeMapel = trim($_POST['kode_mapel'] ?? '');
    $namaMapel = trim($_POST['nama_mapel'] ?? '');
    $guruId = (int) ($_POST['guru_id'] ?? 0);

    if ($kodeMapel === '' || $namaMapel === '' || $guruId <= 0) {
        $error = 'Semua field harus diisi dan pilih guru dengan benar.';
    } else {
        $kodeMapel = mysqli_real_escape_string($koneksi, $kodeMapel);
        $namaMapel = mysqli_real_escape_string($koneksi, $namaMapel);

        $query = "INSERT INTO mapel (kode_mapel, nama_mapel, guru_id) VALUES ('$kodeMapel', '$namaMapel', $guruId)";

        if (mysqli_query($koneksi, $query)) {
            header('Location: ' . app_url('tabel_user/daftar_mapel.php'));
            exit;
        }

        $error = 'Gagal menyimpan data mapel: ' . mysqli_error($koneksi);
    }
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Mata Pelajaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1328">
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>

<div class="container mt-4 mb-5">
    <div class="form-card">
        <h3 class="text-center">Tambah Mata Pelajaran</h3>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label for="kode_mapel" class="form-label fw-semibold">Kode Mata Pelajaran</label>
                <input type="text" class="form-control" id="kode_mapel" name="kode_mapel" placeholder="Contoh: MP001" value="<?= htmlspecialchars($_POST['kode_mapel'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="nama_mapel" class="form-label fw-semibold">Nama Mata Pelajaran</label>
                <input type="text" class="form-control" id="nama_mapel" name="nama_mapel" placeholder="Contoh: Matematika" value="<?= htmlspecialchars($_POST['nama_mapel'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="guru_id" class="form-label fw-semibold">Pilih Guru</label>
                <select class="form-select" id="guru_id" name="guru_id" required>
                    <option value="">-- Pilih Guru --</option>
                    <?php while ($guru = mysqli_fetch_assoc($guruList)): ?>
                        <option value="<?= (int) $guru['id']; ?>" <?= ((int) ($_POST['guru_id'] ?? 0) === (int) $guru['id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($guru['nama']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?= htmlspecialchars(app_url('tabel_user/daftar_mapel.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
</body>
</html>