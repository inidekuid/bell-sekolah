<?php

/**
 * bell_runner.php - Skrip untuk memeriksa dan membunyikan bel.
 * Skrip ini dimaksudkan untuk dijalankan setiap menit oleh cronjob atau Task Scheduler.
 */

// Mengatur zona waktu ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Memuat konfigurasi
// Menggunakan __DIR__ untuk memastikan path selalu benar, tidak peduli dari mana skrip dijalankan.
require_once __DIR__ . '/config.php';

// Fungsi untuk mencatat log
function write_log($message)
{
    $logFile = __DIR__ . '/bell.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

write_log("Skrip dimulai...");

try {
    $db = get_db();

    // 1. Dapatkan jadwal yang sedang aktif
    $active_schedule = $db->querySingle('SELECT id, name FROM schedules WHERE is_active = 1', true);

    if (!$active_schedule) {
        write_log("Tidak ada jadwal aktif yang ditemukan. Skrip berhenti.");
        exit;
    }

    write_log("Jadwal aktif ditemukan: {$active_schedule['name']} (ID: {$active_schedule['id']})");

    // 2. Dapatkan waktu saat ini dalam format HH:MM
    $current_time = date('H:i');
    write_log("Waktu saat ini: $current_time");

    // 3. Cari waktu bel yang cocok di jadwal aktif
    $stmt = $db->prepare('SELECT event, audio_file FROM bell_times WHERE schedule_id = :schedule_id AND time = :current_time');
    $stmt->bindValue(':schedule_id', $active_schedule['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':current_time', $current_time, SQLITE3_TEXT);

    $result = $stmt->execute();
    $bell_to_ring = $result->fetchArray(SQLITE3_ASSOC);

    // 4. Jika ada bel yang cocok, bunyikan!
    if ($bell_to_ring) {
        $event = $bell_to_ring['event'];
        $audio_file = $bell_to_ring['audio_file'];

        write_log("Waktu bel cocok! Event: '$event'.");

        if (!empty($audio_file)) {
            $audio_path = AUDIO_PATH . '/' . $audio_file;

            if (file_exists($audio_path)) {
                write_log("Memainkan audio: $audio_path");

                // Perintah untuk memainkan audio.
                // Di Windows, kita perlu path lengkap ke mpg123.exe
                // Di Linux, mpg123 biasanya ada di PATH sistem.
                $player_command = 'mpg123'; // Default untuk Linux
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    // Asumsikan mpg123.exe ada di dalam folder yang sama dengan php.exe
                    $player_command = '"' . dirname(PHP_BINARY) . '\mpg123.exe"';
                }

                // Gunakan escapeshellarg untuk keamanan
                $command = $player_command . ' ' . escapeshellarg($audio_path);

                // Jalankan perintah
                shell_exec($command);

                write_log("Audio selesai dimainkan.");
            } else {
                write_log("Error: File audio tidak ditemukan di '$audio_path'.");
            }
        } else {
            write_log("Tidak ada file audio yang diatur untuk event ini.");
        }
    } else {
        write_log("Tidak ada jadwal bel untuk waktu saat ini.");
    }
} catch (Exception $e) {
    write_log("EXCEPTION: " . $e->getMessage());
}

write_log("Skrip selesai.");
