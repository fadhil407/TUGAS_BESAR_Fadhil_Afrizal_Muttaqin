<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../koneksi.php';

$query = mysqli_query(
    $koneksi,
    'SELECT siswa.id, siswa.user_id, siswa.nis, siswa.nama, siswa.kelas, siswa.jenis_kelamin, users.username, users.created_at
     FROM siswa
     LEFT JOIN users ON users.id = siswa.user_id
     ORDER BY siswa.id DESC'
);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1328">
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>
<div class="container mt-5 mb-5">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2>Daftar Siswa</h2>
        <a href="<?= htmlspecialchars(app_url('Tambah_user/tambah_siswa.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">+ Tambah Siswa</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Data Siswa</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Jenis Kelamin</th>
                            <th>Username</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($query) > 0): ?>
                        <?php $no = 1; while ($siswa = mysqli_fetch_assoc($query)): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($siswa['nis'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($siswa['nama'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($siswa['kelas'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($siswa['username'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($siswa['created_at'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars(app_url('Edit/edit_user.php?id=' . urlencode((string) ($siswa['user_id'] ?? $siswa['id'])) . '&role=siswa'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="<?= htmlspecialchars(app_url('Delete/hapus.php?id=' . urlencode((string) ($siswa['user_id'] ?? $siswa['id']))), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data siswa ini?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Belum ada data siswa.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../footer.php'; ?>
</body>
</html>
