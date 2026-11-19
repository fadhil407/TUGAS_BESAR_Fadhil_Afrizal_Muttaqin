<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../koneksi.php';

$limit = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$totalMapel = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM mapel"));
$totalMapel = (int) $totalMapel['total'];
$totalPages = (int) ceil($totalMapel / $limit);

$query = mysqli_query($koneksi, "SELECT mapel.*, guru.nama AS nama_guru FROM mapel LEFT JOIN guru ON mapel.guru_id = guru.id ORDER BY mapel.id DESC LIMIT $limit OFFSET $offset");
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Mata Pelajaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1328">
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>

<div class="container mt-4 mb-5">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2>Data Mata Pelajaran</h2>
        <a href="<?= htmlspecialchars(app_url('Tambah_user/tambah_mapel.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">+ Tambah Mapel Baru</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Daftar Mapel</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
            <thead class="table-dark text-center">
                <tr>
                    <th style="width: 8%;">ID</th>
                    <th style="width: 20%;">Kode Mapel</th>
                    <th>Nama Mapel</th>
                    <th>Nama Guru</th>
                    <th style="width: 18%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($query) > 0) {
                    $no = $offset + 1;
                    while ($mapel = mysqli_fetch_assoc($query)) {
                ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($mapel['kode_mapel']); ?></td>
                            <td><?= htmlspecialchars($mapel['nama_mapel']); ?></td>
                            <td><?= htmlspecialchars($mapel['nama_guru'] ?? 'Belum ada guru'); ?></td>
                            <td class="text-center">
                                <!-- Tombol Edit -->
                                <a href="<?= htmlspecialchars(app_url('Edit/edit_mapel.php?id=' . (int) $mapel['id']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-warning btn-sm">Edit</a>
                                
                                <!-- Tombol Hapus -->
                                <a href="<?= htmlspecialchars(app_url('Delete/hapus.mapel.php?id=' . (int) $mapel['id']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus mapel ini?')">Hapus</a>
                            </td>
                        </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center'>Belum ada data mapel.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small">
                    Menampilkan <?= min($totalMapel, $offset + 1); ?>-<?= min($totalMapel, $offset + $limit); ?> dari <?= $totalMapel ?> data
                </div>

                <nav aria-label="Pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= max(1, $page - 1); ?>">Sebelumnya</a>
                        </li>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= min($totalPages, $page + 1); ?>">Berikutnya</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/footer.php';
?>
</body>
</html>