<?php
// api.php - Handles all backend actions via AJAX/Fetch
require_once 'config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'Aksi tidak valid.',
    'action' => null,
    'data' => null,
    'reload' => false
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = get_db();
    $action = $_POST['action'];
    $response['action'] = $action;

    try {
        switch ($action) {
            case 'add_schedule':
                if (!empty($_POST['schedule_name'])) {
                    $name = trim($_POST['schedule_name']);
                    $stmt = $db->prepare('INSERT INTO schedules (name) VALUES (?)');
                    $stmt->bindValue(1, $name, SQLITE3_TEXT);
                    $stmt->execute();

                    $lastId = $db->lastInsertRowID();
                    $response['status'] = 'success';
                    $response['message'] = 'Jadwal berhasil ditambahkan!';
                    $response['data'] = ['id' => $lastId, 'name' => $name];
                } else {
                    $response['message'] = 'Nama jadwal tidak boleh kosong.';
                }
                break;

            case 'update_schedule':
                if (isset($_POST['schedule_id']) && !empty($_POST['schedule_name'])) {
                    $id = intval($_POST['schedule_id']);
                    $name = trim($_POST['schedule_name']);

                    $stmt = $db->prepare('UPDATE schedules SET name = ? WHERE id = ?');
                    $stmt->bindValue(1, $name, SQLITE3_TEXT);
                    $stmt->bindValue(2, $id, SQLITE3_INTEGER);
                    $stmt->execute();

                    $response['status'] = 'success';
                    $response['message'] = 'Nama jadwal berhasil diperbarui!';
                    $response['data'] = ['id' => $id, 'name' => $name];
                } else {
                    $response['message'] = 'ID Jadwal dan nama baru tidak boleh kosong.';
                }
                break;

            case 'activate_schedule':
                if (isset($_POST['schedule_id'])) {
                    $id = intval($_POST['schedule_id']);
                    $db->exec('UPDATE schedules SET is_active = 0');
                    $stmt = $db->prepare('UPDATE schedules SET is_active = 1 WHERE id = ?');
                    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
                    $stmt->execute();

                    $response['status'] = 'success';
                    $response['message'] = 'Jadwal berhasil diaktifkan! Halaman akan dimuat ulang.';
                    $response['reload'] = true; // Tell frontend to reload
                }
                break;

            case 'add_bell_time':
                if (isset($_POST['schedule_id']) && !empty($_POST['time']) && !empty($_POST['event'])) {
                    $audio_file = !empty($_POST['audio_file']) ? $_POST['audio_file'] : null;

                    $stmt = $db->prepare('INSERT INTO bell_times (schedule_id, time, event, audio_file) VALUES (?, ?, ?, ?)');
                    $stmt->bindValue(1, $_POST['schedule_id'], SQLITE3_INTEGER);
                    $stmt->bindValue(2, $_POST['time'], SQLITE3_TEXT);
                    $stmt->bindValue(3, $_POST['event'], SQLITE3_TEXT);
                    $stmt->bindValue(4, $audio_file, SQLITE3_TEXT);
                    $stmt->execute();

                    $lastId = $db->lastInsertRowID();
                    $audio_name = 'Tanpa Audio';
                    if ($audio_file) {
                        // Use prepared statements to prevent SQL injection
                        $stmt_audio = $db->prepare("SELECT name FROM audio_files WHERE file_path = :path");
                        $stmt_audio->bindValue(':path', $audio_file, SQLITE3_TEXT);
                        $result_audio = $stmt_audio->execute();
                        $audio_row = $result_audio->fetchArray(SQLITE3_ASSOC);
                        if ($audio_row) {
                            $audio_name = $audio_row['name'];
                        }
                    }

                    $response['status'] = 'success';
                    $response['message'] = 'Waktu bel berhasil ditambahkan!';
                    $response['data'] = [
                        'id' => $lastId,
                        'time' => $_POST['time'],
                        'event' => $_POST['event'],
                        'audio_name' => $audio_name
                    ];
                } else {
                    $response['message'] = 'Waktu dan keterangan harus diisi.';
                }
                break;
            case 'update_bell_time':
                if (isset($_POST['bell_id']) && !empty($_POST['time']) && !empty($_POST['event'])) {
                    $id = intval($_POST['bell_id']);
                    $time = $_POST['time'];
                    $event = $_POST['event'];
                    $audio_file = !empty($_POST['audio_file']) ? $_POST['audio_file'] : null;

                    $stmt = $db->prepare('UPDATE bell_times SET time = ?, event = ?, audio_file = ? WHERE id = ?');
                    $stmt->bindValue(1, $time, SQLITE3_TEXT);
                    $stmt->bindValue(2, $event, SQLITE3_TEXT);
                    $stmt->bindValue(3, $audio_file, SQLITE3_TEXT);
                    $stmt->bindValue(4, $id, SQLITE3_INTEGER);
                    $stmt->execute();

                    $audio_name = 'Tanpa Audio';
                    if ($audio_file) {
                        $stmt_audio = $db->prepare("SELECT name FROM audio_files WHERE file_path = :path");
                        $stmt_audio->bindValue(':path', $audio_file, SQLITE3_TEXT);
                        $result_audio = $stmt_audio->execute();
                        if ($audio_row = $result_audio->fetchArray(SQLITE3_ASSOC)) {
                            $audio_name = $audio_row['name'];
                        }
                    }

                    $response['status'] = 'success';
                    $response['message'] = 'Waktu bel berhasil diperbarui!';
                    $response['data'] = [
                        'id' => $id,
                        'time' => $time,
                        'event' => $event,
                        'audio_file' => $audio_file,
                        'audio_name' => $audio_name
                    ];
                } else {
                    $response['message'] = 'ID, Waktu, dan Keterangan tidak boleh kosong.';
                }
                break;

            case 'delete_bell_time':
                if (isset($_POST['bell_id'])) {
                    $id = intval($_POST['bell_id']);
                    $stmt = $db->prepare('DELETE FROM bell_times WHERE id = ?');
                    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
                    $stmt->execute();

                    $response['status'] = 'success';
                    $response['message'] = 'Waktu bel berhasil dihapus!';
                    $response['data'] = ['id' => $id];
                }
                break;

            case 'delete_bell_audio':
                if (isset($_POST['audio_id'])) {
                    $id = intval($_POST['audio_id']);
                    // First, get the file path to delete the actual file
                    $stmt_path = $db->prepare("SELECT file_path FROM audio_files WHERE id = :id");
                    $stmt_path->bindValue(':id', $id, SQLITE3_INTEGER);
                    $result_path = $stmt_path->execute();
                    $file_path_row = $result_path->fetchArray(SQLITE3_ASSOC);

                    if ($file_path_row && $file_path_row['file_path']) {
                        $full_path = AUDIO_PATH . '/' . $file_path_row['file_path'];
                        if (file_exists($full_path)) {
                            unlink($full_path); // Delete the file from server
                        }
                    }

                    $stmt = $db->prepare('DELETE FROM audio_files WHERE id = ?');
                    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
                    $stmt->execute();

                    $response['status'] = 'success';
                    $response['message'] = 'File audio berhasil dihapus!';
                    $response['data'] = ['id' => $id];
                    $response['reload'] = true; // Reload to update dropdowns
                }
                break;

            case 'upload_audio':
                if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] == 0) {
                    $file_extension = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
                    if ($file_extension != 'mp3') {
                        $response['message'] = "Error: Hanya file format MP3 yang diizinkan.";
                        break;
                    }

                    if (!file_exists(AUDIO_PATH)) {
                        mkdir(AUDIO_PATH, 0755, true);
                    }

                    $file_name = uniqid() . '-' . basename($_FILES['audio_file']['name']);
                    $target_file = AUDIO_PATH . '/' . $file_name;

                    if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $target_file)) {
                        $name = !empty($_POST['audio_name']) ? $_POST['audio_name'] : pathinfo($_FILES['audio_file']['name'], PATHINFO_FILENAME);
                        $description = $_POST['audio_description'] ?? '';

                        $stmt = $db->prepare('INSERT INTO audio_files (name, file_path, description) VALUES (?, ?, ?)');
                        $stmt->bindValue(1, $name, SQLITE3_TEXT);
                        $stmt->bindValue(2, $file_name, SQLITE3_TEXT);
                        $stmt->bindValue(3, $description, SQLITE3_TEXT);
                        $stmt->execute();

                        $response['status'] = 'success';
                        $response['message'] = 'File audio berhasil diunggah!';
                        $response['reload'] = true; // Reload to show new audio in table and dropdowns
                    } else {
                        $response['message'] = "Error saat memindahkan file yang diunggah.";
                    }
                } else {
                    $response['message'] = "Error saat mengunggah: " . ($_FILES['audio_file']['error'] ?? 'Tidak ada file yang dipilih.');
                }
                break;
        }
    } catch (Exception $e) {
        $response['message'] = 'Database Error: ' . $e->getMessage();
    }
}

// Echo the JSON response
echo json_encode($response);
exit();
