<?php
require_once 'koneksi.php';

$pesan_sukses = "";
$pesan_error  = "";

// === HANDLE NOTIFIKASI PESAN DARI REDIRECT (PRG PATTERN) ===
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {$pesan_sukses = "Agenda kegiatan baru berhasil ditambahkan!";
    } elseif ($_GET['msg'] === 'updated') {$pesan_sukses = "Status agenda berhasil diperbarui menjadi Selesai!";
    } elseif ($_GET['msg'] === 'deleted') {$pesan_sukses = "Agenda berhasil dihapus!";
    }
}

// === PROSES TANDAI SELESAI (UPDATE STATUS) ===
if (isset($_GET['aksi']) && $_GET['aksi'] === 'selesai' && isset($_GET['id'])) {
    $id_agenda = (int)$_GET['id'];
    
    $stmt = mysqli_prepare($koneksi, "UPDATE agenda SET `status` = 'Selesai' WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_agenda);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: index.php?msg=updated");
        exit();
    } else {
        $pesan_error = "Gagal memperbarui status agenda.";
    }
    mysqli_stmt_close($stmt);
}

// === PROSES HAPUS AGENDA (DELETE) ===
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

// === PROSES SUBMIT FORM (PHP SERVER-SIDE VALIDATION & INSERT) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul     = trim($_POST['judul_kegiatan'] ?? '');
    $kategori  = trim($_POST['kategori'] ?? '');
    $tanggal   = trim($_POST['tanggal_kegiatan'] ?? '');
    $prioritas = trim($_POST['prioritas'] ?? 'Sedang');
    $catatan   = trim($_POST['catatan'] ?? '');

    // Validasi Sisi Server (PHP)
    if (empty($judul) || empty($kategori) || empty($tanggal)) {
        $pesan_error = "Semua kolom dengan tanda bintang (*) wajib diisi!";
    } else {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO agenda (judul_kegiatan, kategori, tanggal_kegiatan, prioritas, catatan, `status`) VALUES (?, ?, ?, ?, ?, 'Belum')");
        mysqli_stmt_bind_param($stmt, "sssss", $judul, $kategori,$tanggal, $prioritas,$catatan);

        if (mysqli_stmt_execute($stmt)) {
            // REDIRECT agar saat F5/Refresh data tidak terkirim ulang (Anti-Duplikat)
            header("Location: index.php?msg=added");
            exit();
        } else {
            $pesan_error = "Gagal menyimpan agenda: " . mysqli_error($koneksi);
        }
        mysqli_stmt_close($stmt);
    }
}

// === QUERY BACA DATA (LOGIKA TEMA 9: ORDER BY TANGGAL TERDEKAT) ===
$query_agenda = "SELECT * FROM agenda ORDER BY tanggal_kegiatan ASC";
$result_agenda = mysqli_query($koneksi,$query_agenda);

// Hitung rekap kuantitas agenda
$total_agenda  = 0;
$total_selesai = 0;
$total_belum   = 0;

$data_rows = [];
if ($result_agenda && mysqli_num_rows($result_agenda) > 0) {
    while ($r = mysqli_fetch_assoc($result_agenda)) {
        $data_rows[] =$r;
        $total_agenda++;
        if ($r['status'] === 'Selesai') {$total_selesai++;
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
    <title>Agenda Kegiatan Harian - UTS Web App</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Header Semantik -->
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-3">
            <div class="container">
                <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
                    <i class="bi bi-grid-3x3-gap-fill text-primary me-2 fs-4"></i>
                    <span>AGENDA<span class="text-primary">.SYS</span></span>
                </a>
                
                <!-- Identitas Mahasiswa -->
                <div class="d-flex align-items-center gap-2">
                    <span class="brand-badge text-white">
                        <i class="bi bi-person-fill text-info me-1"></i> Pengguna Web
                    </span>
                    <span class="badge bg-primary text-white fw-bold px-2 py-1">WELCOME</span>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content Semantik -->
    <main class="container my-5">
        
        <!-- Section Header & Rekap Geometris -->
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
                    <span id="toggleText">Tambah Agenda Baru</span>
                </button>
            </div>
        </div>

        <!-- Rekap Statistik Agenda -->
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

        <!-- Alert Server (PHP) -->
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

        <!-- Alert Client (jQuery) -->
        <div id="clientAlert" class="alert alert-warning alert-dismissible fade show card-geo d-none p-3 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-circle-fill text-warning fs-5 me-2"></i>
                <span id="clientAlertMsg"></span>
            </div>
            <button type="button" class="btn-close" id="btnCloseClientAlert" aria-label="Close"></button>
        </div>

        <!-- Form Tambah Agenda (Interactive Slide) -->
        <div id="formSection" class="card-geo mb-5" style="display: none;">
            <div class="card-geo-header">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-pencil-square me-2 text-primary"></i> Form Input Agenda Baru</h5>
            </div>
            <div class="p-4">
                <form id="agendaForm" action="index.php" method="POST" novalidate>
                    <div class="row g-3">
                        <!-- Input 1: Judul Kegiatan -->
                        <div class="col-md-6">
                            <label for="judul_kegiatan" class="form-label fw-semibold small">Judul Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="judul_kegiatan" name="judul_kegiatan" placeholder="Contoh: Revisi Laporan ADPL">
                        </div>

                        <!-- Input 2: Kategori Agenda -->
                        <div class="col-md-6">
                            <label for="kategori" class="form-label fw-semibold small">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="kategori" name="kategori">
                                <option value="">-- Pilih Kategori --</option>
                                <option value="Akademik">Akademik / Kuliah</option>
                                <option value="Organisasi">Organisasi</option>
                                <option value="Pribadi">Pribadi / Hobi</option>
                                <option value="Studi Mandiri">Studi Mandiri</option>
                            </select>
                        </div>

                        <!-- Input 3: Waktu / Tanggal -->
                        <div class="col-md-6">
                            <label for="tanggal_kegiatan" class="form-label fw-semibold small">Tanggal & Waktu Kegiatan <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="tanggal_kegiatan" name="tanggal_kegiatan">
                        </div>

                        <!-- Input 4: Tingkat Prioritas -->
                        <div class="col-md-6">
                            <label for="prioritas" class="form-label fw-semibold small">Tingkat Prioritas</label>
                            <select class="form-select" id="prioritas" name="prioritas">
                                <option value="Rendah">Rendah</option>
                                <option value="Sedang" selected>Sedang</option>
                                <option value="Tinggi">Tinggi</option>
                            </select>
                        </div>

                        <!-- Input 5: Catatan -->
                        <div class="col-12">
                            <label for="catatan" class="form-label fw-semibold small">Catatan / Detail Lokasi</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="3" placeholder="Tambahkan informasi pendukung agenda di sini..."></textarea>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <button type="button" id="btnBatal" class="btn btn-geo-outline me-2">Batal</button>
                            <button type="submit" class="btn btn-geo px-4">Simpan Agenda</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Riwayat Data Agenda -->
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
                            <?php foreach ($data_rows as$row): ?>
                                <?php 
                                    $waktu_formatted = date('d M Y - H:i', strtotime($row['tanggal_kegiatan']));$priority_class = 'badge-priority-sedang';
                                    if ($row['prioritas'] === 'Tinggi')$priority_class = 'badge-priority-tinggi';
                                    if ($row['prioritas'] === 'Rendah')$priority_class = 'badge-priority-rendah';

                                    $is_selesai = ($row['status'] === 'Selesai');
                                    $text_style =$is_selesai ? 'text-completed' : '';
                                ?>
                                <tr class="<?= $is_selesai ? 'row-completed' : ''; ?>">
                                    <td class="text-center fw-bold text-muted"><?= $no++; ?></td>
                                    
                                    <!-- Nama Agenda -->
                                    <td>
                                        <div class="fw-bold <?= $text_style; ?>"><?= htmlspecialchars($row['judul_kegiatan']); ?></div>
                                    </td>
                                    
                                    <!-- Kategori -->
                                    <td>
                                        <span class="badge badge-kategori rounded-pill px-3 py-1 fw-semibold">
                                            <?= htmlspecialchars($row['kategori']); ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Tanggal -->
                                    <td>
                                        <div class="small <?= $text_style; ?>">
                                            <i class="bi bi-clock me-1 text-primary"></i> <?= $waktu_formatted; ?> WIB
                                        </div>
                                    </td>
                                    
                                    <!-- Prioritas -->
                                    <td class="text-center">
                                        <span class="badge <?= $priority_class; ?> rounded-pill px-3 py-1 fw-semibold">
                                            <?= htmlspecialchars($row['prioritas']); ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Catatan -->
                                    <td>
                                        <span class="small <?= $text_style; ?>">
                                            <?= !empty($row['catatan']) ? htmlspecialchars($row['catatan']) : '-'; ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Status / Tombol Selesai & Hapus -->
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center gap-1">
                                            <?php if ($is_selesai): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 fw-bold small">
                                                    <i class="bi bi-check-circle-fill me-1"></i> Selesai
                                                </span>
                                            <?php else: ?>
                                                <a href="index.php?aksi=selesai&id=<?= $row['id']; ?>" 
                                                   class="btn btn-sm btn-outline-success rounded-pill px-2 py-1 fw-semibold"
                                                   title="Tandai Selesai"
                                                   onclick="return confirm('Tandai agenda \'<?= htmlspecialchars(addslashes($row['judul_kegiatan'])); ?>\' sebagai Selesai?');">
                                                    <i class="bi bi-check2-circle"></i>
                                                </a>
                                            <?php endif; ?>

                                            <!-- Tombol Hapus Agenda -->
                                            <a href="index.php?aksi=hapus&id=<?= $row['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1 fw-semibold"
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

    <!-- Footer Semantik -->
    <footer class="py-4 text-center">
        <div class="container">
            <p class="mb-0 small text-slate-400">
                &copy; <?= date('Y'); ?> Mini Web App UTS Pemrograman Web — <strong>Robby Ibrahimovic Wibowo</strong> (252410103020)
            </p>
        </div>
    </footer>

    <!-- Dependency JS: jQuery & Bootstrap 5 Bundle -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.bundle.min.css"></script>

    <!-- Custom Script -->
    <script src="script.js"></script>
</body>
</html>