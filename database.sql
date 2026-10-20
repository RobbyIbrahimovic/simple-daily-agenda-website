CREATE DATABASE IF NOT EXISTS db_uts_agenda_kegiatan;
USE db_uts_agenda_kegiatan;

CREATE TABLE IF NOT EXISTS agenda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `status` ENUM ('Belum', 'Selesai') DEFAULT 'Belum',
    judul_kegiatan VARCHAR(150) NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    tanggal_kegiatan DATETIME NOT NULL,
    prioritas ENUM('Rendah', 'Sedang', 'Tinggi') DEFAULT 'Sedang',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Data Awal Uji Coba
INSERT INTO agenda (judul_kegiatan, kategori, tanggal_kegiatan, prioritas, catatan) VALUES
('Presentasi UTS ADPL Ke Asisten Praktikum', 'Akademik', '2026-10-09 16:00:00', 'Tinggi', 'Merecall isi laporan dan menyiapkan materi presentasi'),
('Asistensi ADPL Dengan Asisten Praktikum', 'Akademik', '2026-10-25 19:00:00', 'Tinggi', 'Membahas hasil presentasi ADPL dan merevisi Use Case Diagram'),
('Pengerjaan Proyek Kelompok ADPL', 'Akademik', '2026-10-22 20:30:00', 'Rendah', 'Membuat Scenario dan Activity Diagram');