<?php

/**
 * install.php - Skrip Instalasi Awal
 *
 * PENTING: Jalankan skrip ini HANYA SATU KALI untuk membuat struktur
 * database dan folder yang diperlukan.
 * Setelah berhasil, Anda bisa menghapus file ini dari server demi keamanan.
 */

// Memuat file konfigurasi untuk mendapatkan path yang benar.
require_once 'config.php';

// --- FUNGSI-FUNGSI INSTALASI ---

/**
 * Membuat folder dan file database, serta struktur tabel.
 */
function setup_database()
{
    echo "Memulai setup database...<br>";

    // Buat folder 'database' jika belum ada.
    if (!file_exists(dirname(DB_PATH))) {
        if (mkdir(dirname(DB_PATH), 0755, true)) {
            echo "Folder 'database' berhasil dibuat.<br>";
        } else {
            die("GAGAL membuat folder 'database'. Periksa izin folder (permission).<br>");
        }
    }

    // Buat folder 'audio' jika belum ada.
    if (!file_exists(AUDIO_PATH)) {
        if (mkdir(AUDIO_PATH, 0755, true)) {
            echo "Folder 'audio' berhasil dibuat.<br>";
        } else {
            die("GAGAL membuat folder 'audio'. Periksa izin folder (permission).<br>");
        }
    }

    // Dapatkan koneksi database.
    $db = get_db();

    // Buat tabel 'schedules'.
    $db->exec('
    CREATE TABLE IF NOT EXISTS schedules (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        is_active INTEGER DEFAULT 0
    );
    ');
    echo "Tabel 'schedules' berhasil dicek/dibuat.<br>";

    // Buat tabel 'bell_times'.
    $db->exec('
    CREATE TABLE IF NOT EXISTS bell_times (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        schedule_id INTEGER,
        time TEXT NOT NULL,
        event TEXT NOT NULL,
        audio_file TEXT DEFAULT NULL,
        FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE
    );
    ');
    echo "Tabel 'bell_times' berhasil dicek/dibuat.<br>";

    // Buat tabel 'audio_files'.
    $db->exec('
    CREATE TABLE IF NOT EXISTS audio_files (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        file_path TEXT UNIQUE NOT NULL,
        description TEXT
    );
    ');
    echo "Tabel 'audio_files' berhasil dicek/dibuat.<br>";

    echo "Setup database selesai.<br>";
    return $db;
}

/**
 * Mengisi database dengan data awal jika database masih kosong.
 */
function seed_initial_data($db)
{
    echo "Memeriksa data awal...<br>";
    $count = $db->querySingle('SELECT COUNT(*) FROM schedules');

    if ($count == 0) {
        echo "Database kosong, menambahkan data jadwal default...<br>";
        $db->exec("INSERT INTO schedules (name, is_active) VALUES ('Jadwal Hari Biasa', 1)");
        echo "Jadwal default berhasil ditambahkan.<br>";
    } else {
        echo "Database sudah berisi data, tidak ada data baru yang ditambahkan.<br>";
    }
}


// --- EKSEKUSI INSTALASI ---

echo "<h1>Proses Instalasi Bel Sekolah</h1>";
echo "<pre>"; // Menggunakan <pre> agar output lebih rapi

$db_connection = setup_database();
seed_initial_data($db_connection);

echo "</pre>";
echo "<h2>Instalasi Selesai!</h2>";
echo "<p>Aplikasi bel sekolah Anda sekarang siap digunakan. Silakan akses file <a href='index.php'>index.php</a>.</p>";
echo "<p style='color:red;'><b>Penting:</b> Demi keamanan, disarankan untuk menghapus file <b>install.php</b> ini dari server Anda sekarang.</p>";
