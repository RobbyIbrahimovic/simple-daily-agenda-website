<!-- Index.php - Halaman Utama Aplikasi Agenda Harian -->
<?php
require_once 'koneksi.php';

$pesan_sukses = "";
$pesan_error  = "";

$is_edit = false;
$data_edit = [
    'id' => '',
    'judul_kegiatan' => '',
    'kategori' => '',
    'tanggal_kegiatan' => '',
    'prioritas' => 'Sedang',
    'catatan' => ''
];

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {
        $pesan_sukses = "Agenda kegiatan baru berhasil ditambahkan!";
    } elseif ($_GET['msg'] === 'updated') {
        $pesan_sukses = "Status agenda berhasil diperbarui menjadi Selesai!";
    } elseif ($_GET['msg'] === 'updated_future') {
        $pesan_sukses = "Catatan: Agenda masa depan telah ditandai Selesai lebih awal!";
    } elseif ($_GET['msg'] === 'uncompleted') {
        $pesan_sukses = "Status agenda berhasil dikembalikan menjadi Belum Selesai!";
    } elseif ($_GET['msg'] === 'edited') {
        $pesan_sukses = "Data agenda berhasil diperbarui!";
    } elseif ($_GET['msg'] === 'deleted') {
        $pesan_sukses = "Agenda berhasil dihapus!";
    }
}

if (isset($_GET['aksi']) && $_GET['aksi'] === 'edit' && isset($_GET['id'])) {
    $id_req = (int)$_GET['id'];
    $stmt_e = mysqli_prepare($koneksi, "SELECT * FROM agenda WHERE id = ?");
    mysqli_stmt_bind_param($stmt_e, "i", $id_req);
    mysqli_stmt_execute($stmt_e);
    $res_e = mysqli_stmt_get_result($stmt_e);
    if ($row_e = mysqli_fetch_assoc($res_e)) {
        $is_edit = true;
        $data_edit = $row_e;
    }
    mysqli_stmt_close($stmt_e);
}

if (isset($_GET['aksi']) && $_GET['aksi'] === 'selesai' && isset($_GET['id'])) {
    $id_agenda = (int)$_GET['id'];

    $stmt_chk = mysqli_prepare($koneksi, "SELECT tanggal_kegiatan FROM agenda WHERE id = ?");
    mysqli_stmt_bind_param($stmt_chk, "i", $id_agenda);
    mysqli_stmt_execute($stmt_chk);
    $res_chk = mysqli_stmt_get_result($stmt_chk);
    $row_chk = mysqli_fetch_assoc($res_chk);
    mysqli_stmt_close($stmt_chk);

    if ($row_chk) {
        $waktu_agenda = strtotime($row_chk['tanggal_kegiatan']);
        $sekarang = time();

        $stmt = mysqli_prepare($koneksi, "UPDATE agenda SET `status` = 'Selesai' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_agenda);

        if (mysqli_stmt_execute($stmt)) {
            $msg = ($waktu_agenda > $sekarang) ? "updated_future" : "updated";
            header("Location: index.php?msg=" . $msg);
            exit();
        } else {
            $pesan_error = "Gagal memperbarui status agenda.";
        }
        mysqli_stmt_close($stmt);
    }
}

if (isset($_GET['aksi']) && $_GET['aksi'] === 'batal' && isset($_GET['id'])) {
    $id_agenda = (int)$_GET['id'];
    
    $stmt = mysqli_prepare($koneksi, "UPDATE agenda SET `status` = 'Belum' WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_agenda);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: index.php?msg=uncompleted");
        exit();
    } else {
        $pesan_error = "Gagal mengembalikan status agenda.";
    }
    mysqli_stmt_close($stmt);
}

if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus' && isset($_GET['id'])) {
    $id_agenda = (int)$_GET['id'];
    
    $stmt = mysqli_prepare($koneksi, "DELETE FROM agenda WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_agenda);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: index.php?msg=deleted");
        exit();
    } else {
        $pesan_error = "Gagal menghapus agenda.";
    }
    mysqli_stmt_close($stmt);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_edit   = isset($_POST['id_edit']) ? (int)$_POST['id_edit'] : 0;
    $judul     = trim($_POST['judul_kegiatan'] ?? '');
    $kategori  = trim($_POST['kategori'] ?? '');
    $tanggal   = trim($_POST['tanggal_kegiatan'] ?? '');
    $prioritas = trim($_POST['prioritas'] ?? 'Sedang');
    $catatan   = trim($_POST['catatan'] ?? '');

    $waktu_input = strtotime($tanggal);
    $sekarang    = time();

    if (empty($judul) || empty($kategori) || empty($tanggal)) {
        $pesan_error = "Semua kolom dengan tanda bintang (*) wajib diisi!";
    } elseif ($waktu_input < $sekarang) {
        $pesan_error = "Waktu kegiatan tidak boleh di masa lalu! Silakan pilih tanggal & waktu mendatang.";
    } else {
        if ($id_edit > 0) {
            $stmt = mysqli_prepare($koneksi, "UPDATE agenda SET judul_kegiatan = ?, kategori = ?, tanggal_kegiatan = ?, prioritas = ?, catatan = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sssssi", $judul, $kategori, $tanggal, $prioritas, $catatan, $id_edit);
            $redirect_msg = "edited";
        } else {
            $stmt = mysqli_prepare($koneksi, "INSERT INTO agenda (judul_kegiatan, kategori, tanggal_kegiatan, prioritas, catatan, `status`) VALUES (?, ?, ?, ?, ?, 'Belum')");
            mysqli_stmt_bind_param($stmt, "sssss", $judul, $kategori, $tanggal, $prioritas, $catatan);
            $redirect_msg = "added";
        }

        if (mysqli_stmt_execute($stmt)) {
            header("Location: index.php?msg=" . $redirect_msg);
            exit();
        } else {
            $pesan_error = "Gagal menyimpan agenda: " . mysqli_error($koneksi);
        }
        mysqli_stmt_close($stmt);
    }
}

$query_agenda = "SELECT * FROM agenda ORDER BY tanggal_kegiatan ASC";
$result_agenda = mysqli_query($koneksi, $query_agenda);

$total_agenda  = 0;
$total_selesai = 0;
$total_belum   = 0;

$data_rows = [];
if ($result_agenda && mysqli_num_rows($result_agenda) > 0) {
    while ($r = mysqli_fetch_assoc($result_agenda)) {
        $data_rows[] = $r;
        $total_agenda++;
        if ($r['status'] === 'Selesai') {
            $total_selesai++;
        } else {
            $total_belum++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Kegiatan Harian - Daily Agenda App</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-3">
            <div class="container">
                <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
                    <i class="bi bi-grid-3x3-gap-fill text-primary me-2 fs-4"></i>
                    <span>AGENDA<span class="text-primary">.SYS</span></span>
                </a>
                
                <div class="d-flex align-items-center gap-2">
                    <span class="brand-badge text-white">
                        <i class="bi bi-person-fill text-info me-1"></i> Pengguna Web
                    </span>
                    <span class="badge bg-primary text-white fw-bold px-2 py-1">WELCOME</span>
                </div>
            </div>
        </nav>
    </header>

    <main class="container my-5">
        
        <div class="row align-items-center mb-4 g-3">
            <div class="col-lg-6">
                <h2 class="fw-bold text-dark mb-1 d-flex align-items-center">
                    <i class="bi bi-calendar2-week me-2 text-primary"></i> Agenda Kegiatan Harian
                </h2>
                <p class="text-muted mb-0 small">Diurutkan secara otomatis berdasarkan waktu pelaksanaan terdekat.</p>
            </div>
            
            <div class="col-lg-6 text-lg-end">
                <button id="btnToggleForm" class="btn btn-geo shadow-sm">
                    <i class="bi bi-plus-square-fill me-2" id="iconToggle"></i>
                    <span id="toggleText"><?= $is_edit ? 'Tutup Form Edit' : 'Tambah Agenda Baru'; ?></span>
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="summary-card shadow-sm">
                    <div class="text-muted small fw-semibold text-uppercase">Total Agenda</div>
                    <div class="fs-3 fw-bold text-dark"><?= $total_agenda; ?> <span class="fs-6 fw-normal text-muted">kegiatan</span></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card shadow-sm" style="border-left-color: #0284c7;">
                    <div class="text-muted small fw-semibold text-uppercase">Mendatang / Belum</div>
                    <div class="fs-3 fw-bold text-primary"><?= $total_belum; ?> <span class="fs-6 fw-normal text-muted">agenda</span></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card shadow-sm" style="border-left-color: #10b981;">
                    <div class="text-muted small fw-semibold text-uppercase">Selesai</div>
                    <div class="fs-3 fw-bold text-success"><?= $total_selesai; ?> <span class="fs-6 fw-normal text-muted">terlaksana</span></div>
                </div>
            </div>
        </div>

        <?php if (!empty($pesan_sukses)): ?>
            <div class="alert alert-success alert-dismissible fade show card-geo p-3 mb-4" role="alert" id="serverAlert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill text-success fs-5 me-2"></i>
                    <div><?= $pesan_sukses; ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show card-geo p-3 mb-4" role="alert" id="serverAlert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5 me-2"></i>
                    <div><?= $pesan_error; ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div id="clientAlert" class="alert alert-warning alert-dismissible fade show card-geo d-none p-3 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-circle-fill text-warning fs-5 me-2"></i>
                <span id="clientAlertMsg"></span>
            </div>
            <button type="button" class="btn-close" id="btnCloseClientAlert" aria-label="Close"></button>
        </div>

        <div id="formSection" class="card-geo mb-5" style="<?= $is_edit ? 'display: block;' : 'display: none;'; ?>">
            <div class="card-geo-header">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-pencil-square me-2 text-primary"></i> 
                    <?= $is_edit ? 'Edit Agenda Kegiatan' : 'Form Input Agenda Baru'; ?>
                </h5>
            </div>
            <div class="p-4">
                <form id="agendaForm" action="index.php" method="POST" novalidate>
                    <input type="hidden" name="id_edit" value="<?= htmlspecialchars($data_edit['id']); ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="judul_kegiatan" class="form-label fw-semibold small">Judul Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="judul_kegiatan" name="judul_kegiatan" 
                                   value="<?= htmlspecialchars($data_edit['judul_kegiatan']); ?>" 
                                   placeholder="Contoh: Revisi Laporan ADPL">
                        </div>

                        <div class="col-md-6">
                            <label for="kategori" class="form-label fw-semibold small">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="kategori" name="kategori">
                                <option value="">-- Pilih Kategori --</option>
                                <option value="Akademik" <?= $data_edit['kategori'] === 'Akademik' ? 'selected' : ''; ?>>Akademik / Kuliah</option>
                                <option value="Organisasi" <?= $data_edit['kategori'] === 'Organisasi' ? 'selected' : ''; ?>>Organisasi</option>
                                <option value="Pribadi" <?= $data_edit['kategori'] === 'Pribadi' ? 'selected' : ''; ?>>Pribadi / Hobi</option>
                                <option value="Studi Mandiri" <?= $data_edit['kategori'] === 'Studi Mandiri' ? 'selected' : ''; ?>>Studi Mandiri</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="tanggal_kegiatan" class="form-label fw-semibold small">Tanggal & Waktu Kegiatan <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="tanggal_kegiatan" name="tanggal_kegiatan" 
                                   value="<?= !empty($data_edit['tanggal_kegiatan']) ? date('Y-m-d\TH:i', strtotime($data_edit['tanggal_kegiatan'])) : ''; ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="prioritas" class="form-label fw-semibold small">Tingkat Prioritas</label>
                            <select class="form-select" id="prioritas" name="prioritas">
                                <option value="Rendah" <?= $data_edit['prioritas'] === 'Rendah' ? 'selected' : ''; ?>>Rendah</option>
                                <option value="Sedang" <?= $data_edit['prioritas'] === 'Sedang' ? 'selected' : ''; ?>>Sedang</option>
                                <option value="Tinggi" <?= $data_edit['prioritas'] === 'Tinggi' ? 'selected' : ''; ?>>Tinggi</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="catatan" class="form-label fw-semibold small">Catatan / Detail Lokasi</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="3" placeholder="Tambahkan informasi pendukung agenda di sini..."><?= htmlspecialchars($data_edit['catatan']); ?></textarea>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <?php if ($is_edit): ?>
                                <a href="index.php" class="btn btn-geo-outline me-2">Batal Edit</a>
                            <?php else: ?>
                                <button type="button" id="btnBatal" class="btn btn-geo-outline me-2">Batal</button>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-geo px-4"><?= $is_edit ? 'Perbarui Agenda' : 'Simpan Agenda'; ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card-geo">
            <div class="card-geo-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-list-task me-2 text-primary"></i> Daftar Agenda Terjadwal</h5>
                <span class="badge bg-dark text-white fw-semibold"><?= $total_agenda; ?> Record</span>
            </div>
            
            <div class="table-responsive">
                <table class="table table-geo align-middle">
                    <thead>
                        <tr>
                            <th scope="col" class="text-center" style="width: 50px;">No</th>
                            <th scope="col">Nama Agenda</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Tanggal & Waktu</th>
                            <th scope="col" class="text-center">Prioritas</th>
                            <th scope="col">Catatan</th>
                            <th scope="col" class="text-center" style="width: 150px;">Aksi / Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($data_rows) > 0): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($data_rows as $row): ?>
                                <?php 
                                    $waktu_formatted = date('d M Y - H:i', strtotime($row['tanggal_kegiatan'])); 
                                    $priority_class = 'badge-priority-sedang';
                                    if ($row['prioritas'] === 'Tinggi') $priority_class = 'badge-priority-tinggi';
                                    if ($row['prioritas'] === 'Rendah') $priority_class = 'badge-priority-rendah';

                                    $is_selesai = ($row['status'] === 'Selesai');
                                    $text_style = $is_selesai ? 'text-completed' : '';
                                ?>
                                <tr class="<?= $is_selesai ? 'row-completed' : ''; ?>">
                                    <td class="text-center fw-bold text-muted"><?= $no++; ?></td>
                                    
                                    <td>
                                        <div class="fw-bold <?= $text_style; ?>"><?= htmlspecialchars($row['judul_kegiatan']); ?></div>
                                    </td>
                                    
                                    <td>
                                        <span class="badge badge-kategori rounded-pill px-3 py-1 fw-semibold">
                                            <?= htmlspecialchars($row['kategori']); ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <div class="small <?= $text_style; ?>">
                                            <i class="bi bi-clock me-1 text-primary"></i> <?= $waktu_formatted; ?> WIB
                                        </div>
                                    </td>
                                    
                                    <td class="text-center">
                                        <span class="badge <?= $priority_class; ?> rounded-pill px-3 py-1 fw-semibold">
                                            <?= htmlspecialchars($row['prioritas']); ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <span class="small <?= $text_style; ?>">
                                            <?= !empty($row['catatan']) ? htmlspecialchars($row['catatan']) : '-'; ?>
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center gap-1">
                                            <?php if ($is_selesai): ?>
                                                <a href="index.php?aksi=batal&id=<?= $row['id']; ?>" 
                                                   class="btn btn-sm btn-success rounded-circle d-inline-flex align-items-center justify-content-center"
                                                   style="width: 32px; height: 32px;"
                                                   title="Selesai (Klik untuk kembalikan ke Belum Selesai)"
                                                   onclick="return confirm('Kembalikan status agenda \'<?= htmlspecialchars(addslashes($row['judul_kegiatan'])); ?>\' menjadi Belum Selesai?');">
                                                    <i class="bi bi-check-lg"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="index.php?aksi=selesai&id=<?= $row['id']; ?>" 
                                                   class="btn btn-sm btn-outline-success rounded-circle d-inline-flex align-items-center justify-content-center"
                                                   style="width: 32px; height: 32px;"
                                                   title="Tandai Selesai"
                                                   onclick="return konfirmasiSelesai('<?= htmlspecialchars(addslashes($row['judul_kegiatan'])); ?>', '<?= $row['tanggal_kegiatan']; ?>');">
                                                    <i class="bi bi-check2"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="index.php?aksi=edit&id=<?= $row['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                                               style="width: 32px; height: 32px;"
                                               title="Edit Agenda">
                                                <i class="bi bi-pencil"></i>
                                            </a>

                                            <a href="index.php?aksi=hapus&id=<?= $row['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger rounded-circle d-inline-flex align-items-center justify-content-center"
                                               style="width: 32px; height: 32px;"
                                               title="Hapus Agenda"
                                               onclick="return confirm('Yakin ingin menghapus agenda \'<?= htmlspecialchars(addslashes($row['judul_kegiatan'])); ?>\'?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox display-5 d-block mb-2 text-secondary"></i>
                                    Belum ada agenda yang tersimpan. Klik tombol "Tambah Agenda Baru" untuk menambahkan data.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <footer class="py-4 text-center">
        <div class="container">
            <p class="mb-0 small text-slate-400">
                &copy; <?= date('Y'); ?> Mini Web App UTS Pemrograman Web — <strong>Robby Ibrahimovic Wibowo</strong> (252410103020)
            </p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.bundle.min.css"></script>

    <script src="script.js"></script>
</body>
</html>