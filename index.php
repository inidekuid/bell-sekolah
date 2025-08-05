<?php
require_once 'config.php';

// Inisialisasi dan dapatkan koneksi database
$db = get_db();

// Dapatkan jadwal aktif
$active_schedule = $db->querySingle('SELECT id, name FROM schedules WHERE is_active = 1', true);

// Dapatkan semua jadwal
$schedules = [];
$result = $db->query('SELECT id, name, is_active FROM schedules ORDER BY name');
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $schedules[] = $row;
}

// Dapatkan waktu bel untuk jadwal aktif
$bell_times = [];
if ($active_schedule) {
    $result = $db->query('SELECT id, time, event, audio_file FROM bell_times WHERE schedule_id = ' .
        $active_schedule['id'] . ' ORDER BY time');
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $bell_times[] = $row;
    }
}

// Dapatkan semua file audio
$audio_files = [];
$result = $db->query('SELECT id, name, file_path, description FROM audio_files ORDER BY name');
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $audio_files[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bel SMANSARA Otomatis v2.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* notifikasi toast */
        #toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
            transform: translateY(100px);
            opacity: 0;
            z-index: 50;
        }

        #toast-notification.show {
            transform: translateY(0);
            opacity: 1;
        }
    </style>
</head>

<body class="text-gray-800">

    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        <!-- Header -->
        <header class="flex items-center space-x-4 mb-8">
            <img src="https://placehold.co/60x60/3b82f6/ffffff?text=BS" alt="Logo" class="h-12 w-12 rounded-full">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Bel SMANSARA Otomatis</h1>
                <p class="text-sm text-gray-500">Selamat datang! Kelola jadwal, waktu, dan audio bel dengan mudah.</p>
            </div>
        </header>

        <!-- Navigasi Tab -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="flex -mb-px space-x-6" aria-label="Tabs">
                <button data-tab="schedules" class="tab-btn group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm border-blue-500 text-blue-600">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Jadwal</span>
                </button>
                <button data-tab="bell-times" class="tab-btn group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Waktu</span>
                </button>
                <button data-tab="audio-files" class="tab-btn group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.858 17.142a5 5 0 010-7.072m2.828 9.9a9 9 0 010-12.728M12 12a2 2 0 100-4 2 2 0 000 4z" />
                    </svg>
                    <span>Audio</span>
                </button>
            </nav>
        </div>

        <!-- Konten Tab -->
        <main>
            <!-- Tab Jadwal -->
            <div id="schedules" class="tab-content active">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-1">
                        <div class="bg-white p-6 rounded-lg shadow-sm">
                            <h3 class="text-lg font-semibold mb-4">Tambah Jadwal Baru</h3>
                            <form id="add-schedule-form">
                                <input type="hidden" name="action" value="add_schedule">
                                <div class="space-y-4">
                                    <div>
                                        <label for="schedule_name" class="block text-sm font-medium text-gray-700">Nama Jadwal</label>
                                        <input type="text" name="schedule_name" id="schedule_name" placeholder="Cth: Hari Senin - Jumat" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Tambah Jadwal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <div class="bg-white p-6 rounded-lg shadow-sm">
                            <h3 class="text-lg font-semibold mb-4">Jadwal Tersedia</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="schedules-table-body" class="bg-white divide-y divide-gray-200">
                                        <?php if (empty($schedules)): ?>
                                            <tr id="no-schedules-row">
                                                <td colspan="3" class="px-6 py-4 text-center text-gray-500">Tidak ada jadwal tersedia.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($schedules as $schedule): ?>
                                                <tr id="schedule-row-<?php echo $schedule['id']; ?>">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($schedule['name']); ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php if ($schedule['is_active']): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                                                        <?php else: ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Tidak Aktif</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                                                        <button data-id="<?php echo $schedule['id']; ?>" data-name="<?php echo htmlspecialchars($schedule['name']); ?>" class="edit-schedule-btn text-indigo-600 hover:text-indigo-900">Edit</button>
                                                        <?php if (!$schedule['is_active']): ?>
                                                            <form class="activate-schedule-form inline-block">
                                                                <input type="hidden" name="action" value="activate_schedule">
                                                                <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                                                <button type="submit" class="text-blue-600 hover:text-blue-900">Aktifkan</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Waktu -->
            <div id="bell-times" class="tab-content">
                <?php if ($active_schedule): ?>
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 rounded-r-lg">
                        <div class="flex">
                            <div class="py-1"><svg class="h-5 w-5 text-blue-500 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg></div>
                            <div>
                                <p class="font-bold">Jadwal Aktif: <?php echo htmlspecialchars($active_schedule['name']); ?></p>
                                <p class="text-sm">Semua waktu bel yang diatur di bawah ini berlaku untuk jadwal ini.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-1">
                            <div class="bg-white p-6 rounded-lg shadow-sm">
                                <h3 class="text-lg font-semibold mb-4">Tambah Waktu Bel</h3>
                                <form id="add-bell-time-form">
                                    <input type="hidden" name="action" value="add_bell_time">
                                    <input type="hidden" name="schedule_id" value="<?php echo $active_schedule['id']; ?>">
                                    <div class="space-y-4">
                                        <div>
                                            <label for="time" class="block text-sm font-medium text-gray-700">Waktu</label>
                                            <input type="time" name="time" id="time" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <label for="event" class="block text-sm font-medium text-gray-700">Keterangan</label>
                                            <input type="text" name="event" id="event" placeholder="Cth: Masuk Pelajaran ke-1" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <label for="audio_file" class="block text-sm font-medium text-gray-700">Audio</label>
                                            <select name="audio_file" id="audio_file" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                                <option value="">Tanpa Audio</option>
                                                <?php foreach ($audio_files as $audio): ?>
                                                    <option value="<?php echo htmlspecialchars($audio['file_path']); ?>"><?php echo htmlspecialchars($audio['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            Tambah Waktu
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <div class="bg-white p-6 rounded-lg shadow-sm">
                                <h3 class="text-lg font-semibold mb-4">Daftar Waktu Bel</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Audio</th>
                                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="bell-times-table-body" class="bg-white divide-y divide-gray-200">
                                            <?php if (empty($bell_times)): ?>
                                                <tr id="no-bell-times-row">
                                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Belum ada waktu yang diatur.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($bell_times as $bell): ?>
                                                    <tr id="bell-time-row-<?php echo $bell['id']; ?>">
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($bell['time']); ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($bell['event']); ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?php
                                                            $audio_name = 'Tanpa Audio';
                                                            if (!empty($bell['audio_file'])) {
                                                                foreach ($audio_files as $audio) {
                                                                    if ($audio['file_path'] == $bell['audio_file']) {
                                                                        $audio_name = $audio['name'];
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                            echo htmlspecialchars($audio_name);
                                                            ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                                                            <button class="edit-bell-time-btn text-indigo-600 hover:text-indigo-900"
                                                                data-id="<?php echo $bell['id']; ?>"
                                                                data-time="<?php echo htmlspecialchars($bell['time']); ?>"
                                                                data-event="<?php echo htmlspecialchars($bell['event']); ?>"
                                                                data-audio="<?php echo htmlspecialchars($bell['audio_file'] ?? ''); ?>">
                                                                Edit
                                                            </button>
                                                            <form class="delete-bell-time-form inline-block">
                                                                <input type="hidden" name="action" value="delete_bell_time">
                                                                <input type="hidden" name="bell_id" value="<?php echo $bell['id']; ?>">
                                                                <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg">
                        <div class="flex">
                            <div class="py-1"><svg class="h-5 w-5 text-yellow-500 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg></div>
                            <div>
                                <p class="font-bold">Tidak Ada Jadwal Aktif</p>
                                <p class="text-sm">Silakan aktifkan salah satu jadwal di tab 'Jadwal' untuk mulai mengatur waktu bel.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab Audio -->
            <div id="audio-files" class="tab-content">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-1">
                        <div class="bg-white p-6 rounded-lg shadow-sm">
                            <h3 class="text-lg font-semibold mb-4">Unggah Audio Baru</h3>
                            <form id="upload-audio-form" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="upload_audio">
                                <div class="space-y-4">
                                    <div>
                                        <label for="audio_name" class="block text-sm font-medium text-gray-700">Nama Tampilan</label>
                                        <input type="text" name="audio_name" id="audio_name" placeholder="Cth: Bel Masuk" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label for="audio_description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                        <input type="text" name="audio_description" id="audio_description" placeholder="Cth: Audio untuk jam masuk" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label for="audio_file_upload" class="block text-sm font-medium text-gray-700">File Audio (MP3)</label>
                                        <input type="file" name="audio_file" id="audio_file_upload" accept=".mp3" required class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    </div>
                                    <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Unggah Audio
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <div class="bg-white p-6 rounded-lg shadow-sm">
                            <h3 class="text-lg font-semibold mb-4">Audio Tersedia</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preview</th>
                                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Aksi</span></th>
                                        </tr>
                                    </thead>
                                    <tbody id="audio-files-table-body" class="bg-white divide-y divide-gray-200">
                                        <?php if (empty($audio_files)): ?>
                                            <tr id="no-audio-files-row">
                                                <td colspan="3" class="px-6 py-4 text-center text-gray-500">Belum ada audio yang diunggah.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($audio_files as $audio): ?>
                                                <tr id="audio-file-row-<?php echo $audio['id']; ?>">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($audio['name']); ?></div>
                                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($audio['description']); ?></div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <audio controls class="w-full max-w-xs">
                                                            <source src="<?php echo 'audio/' . htmlspecialchars($audio['file_path']); ?>" type="audio/mpeg">
                                                            Browser Anda tidak mendukung elemen audio.
                                                        </audio>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <form class="delete-audio-form inline-block">
                                                            <input type="hidden" name="action" value="delete_bell_audio">
                                                            <input type="hidden" name="audio_id" value="<?php echo $audio['id']; ?>">
                                                            <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Schedule Modal -->
    <div id="edit-schedule-modal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="edit-schedule-form">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Nama Jadwal</h3>
                                <div class="mt-4">
                                    <input type="hidden" name="action" value="update_schedule">
                                    <input type="hidden" id="edit-schedule-id" name="schedule_id">
                                    <div>
                                        <label for="edit-schedule-name" class="sr-only">Nama Jadwal</label>
                                        <input type="text" name="schedule_name" id="edit-schedule-name" required class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Simpan Perubahan
                        </button>
                        <button type="button" id="cancel-edit-schedule" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Bell Time Modal -->
    <div id="edit-bell-time-modal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="edit-bell-time-form">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Waktu Bel</h3>
                                <div class="mt-4 space-y-4">
                                    <input type="hidden" name="action" value="update_bell_time">
                                    <input type="hidden" id="edit-bell-time-id" name="bell_id">
                                    <div>
                                        <label for="edit-bell-time" class="block text-sm font-medium text-gray-700">Waktu</label>
                                        <input type="time" name="time" id="edit-bell-time" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label for="edit-bell-event" class="block text-sm font-medium text-gray-700">Keterangan</label>
                                        <input type="text" name="event" id="edit-bell-event" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label for="edit-bell-audio" class="block text-sm font-medium text-gray-700">Audio</label>
                                        <select name="audio_file" id="edit-bell-audio" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                            <option value="">Tanpa Audio</option>
                                            <?php foreach ($audio_files as $audio): ?>
                                                <option value="<?php echo htmlspecialchars($audio['file_path']); ?>"><?php echo htmlspecialchars($audio['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Simpan Perubahan
                        </button>
                        <button type="button" id="cancel-edit-bell-time" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast-notification" class="max-w-xs bg-gray-800 text-white text-sm rounded-lg shadow-lg p-4" role="alert">
        <div class="flex">
            <div id="toast-icon" class="w-5 h-5 mr-3">
                <!-- Icon will be inserted by JS -->
            </div>
            <p id="toast-message" class="font-medium">Pesan notifikasi.</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            // Fungsi untuk mengelola Tab
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(item => {
                        item.classList.remove('border-blue-500', 'text-blue-600');
                        item.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                    });
                    tab.classList.add('border-blue-500', 'text-blue-600');
                    tab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                    tabContents.forEach(content => content.classList.remove('active'));
                    document.getElementById(tab.dataset.tab).classList.add('active');
                });
            });

            // Fungsi untuk menampilkan notifikasi Toast
            function showToast(message, isSuccess = true) {
                const toast = document.getElementById('toast-notification');
                const toastMessage = document.getElementById('toast-message');
                const toastIcon = document.getElementById('toast-icon');

                toastMessage.textContent = message;
                if (isSuccess) {
                    toast.classList.remove('bg-red-600');
                    toast.classList.add('bg-gray-800');
                    toastIcon.innerHTML = `<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>`;
                } else {
                    toast.classList.remove('bg-gray-800');
                    toast.classList.add('bg-red-600');
                    toastIcon.innerHTML = `<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
                }

                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 3000);
            }

            // --- Modal Handling ---
            const editScheduleModal = document.getElementById('edit-schedule-modal');
            const editScheduleForm = document.getElementById('edit-schedule-form');
            const cancelEditScheduleBtn = document.getElementById('cancel-edit-schedule');

            const editBellTimeModal = document.getElementById('edit-bell-time-modal');
            const editBellTimeForm = document.getElementById('edit-bell-time-form');
            const cancelEditBellTimeBtn = document.getElementById('cancel-edit-bell-time');

            function openEditScheduleModal(id, name) {
                editScheduleForm.querySelector('#edit-schedule-id').value = id;
                editScheduleForm.querySelector('#edit-schedule-name').value = name;
                editScheduleModal.classList.remove('hidden');
            }

            function closeEditScheduleModal() {
                editScheduleModal.classList.add('hidden');
            }

            function openEditBellTimeModal(id, time, event, audio) {
                editBellTimeForm.querySelector('#edit-bell-time-id').value = id;
                editBellTimeForm.querySelector('#edit-bell-time').value = time;
                editBellTimeForm.querySelector('#edit-bell-event').value = event;
                editBellTimeForm.querySelector('#edit-bell-audio').value = audio;
                editBellTimeModal.classList.remove('hidden');
            }

            function closeEditBellTimeModal() {
                editBellTimeModal.classList.add('hidden');
            }

            cancelEditScheduleBtn.addEventListener('click', closeEditScheduleModal);
            cancelEditBellTimeBtn.addEventListener('click', closeEditBellTimeModal);

            // Fungsi umum untuk menangani submit form via Fetch API
            async function handleFormSubmit(form, event) {
                event.preventDefault();
                const formData = new FormData(form);
                const submitButton = form.querySelector('button[type="submit"]');
                const originalButtonHTML = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Processing...</span>`;

                let isSuccess = false;
                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        body: formData
                    });
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    const result = await response.json();

                    if (result.status === 'success') {
                        showToast(result.message, true);
                        if (result.action) updateUI(result.action, result.data);
                        if (result.reload) setTimeout(() => window.location.reload(), 1500);
                        form.reset();
                        isSuccess = true;
                    } else {
                        showToast(result.message, false);
                    }
                } catch (error) {
                    console.error('Fetch Error:', error);
                    showToast('Terjadi kesalahan koneksi.', false);
                } finally {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonHTML;
                }
                return isSuccess;
            }

            // Fungsi untuk memperbarui UI secara dinamis
            function updateUI(action, data) {
                switch (action) {
                    case 'add_schedule':
                        document.querySelector('#no-schedules-row')?.remove();
                        document.getElementById('schedules-table-body').insertAdjacentHTML('beforeend', `
                        <tr id="schedule-row-${data.id}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${data.name}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Tidak Aktif</span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                                <button data-id="${data.id}" data-name="${data.name}" class="edit-schedule-btn text-indigo-600 hover:text-indigo-900">Edit</button>
                                <form class="activate-schedule-form inline-block">
                                    <input type="hidden" name="action" value="activate_schedule"><input type="hidden" name="schedule_id" value="${data.id}">
                                    <button type="submit" class="text-blue-600 hover:text-blue-900">Aktifkan</button>
                                </form>
                            </td>
                        </tr>`);
                        break;
                    case 'update_schedule':
                        const scheduleRow = document.getElementById(`schedule-row-${data.id}`);
                        if (scheduleRow) {
                            scheduleRow.querySelector('td:first-child').textContent = data.name;
                            scheduleRow.querySelector('.edit-schedule-btn').dataset.name = data.name;
                        }
                        break;
                    case 'add_bell_time':
                        document.querySelector('#no-bell-times-row')?.remove();
                        document.getElementById('bell-times-table-body').insertAdjacentHTML('beforeend', `
                        <tr id="bell-time-row-${data.id}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${data.time}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${data.event}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${data.audio_name}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                               <button class="edit-bell-time-btn text-indigo-600 hover:text-indigo-900" data-id="${data.id}" data-time="${data.time}" data-event="${data.event}" data-audio="${data.audio_file}">Edit</button>
                                <form class="delete-bell-time-form inline-block">
                                    <input type="hidden" name="action" value="delete_bell_time"><input type="hidden" name="bell_id" value="${data.id}">
                                    <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                </form>
                            </td>
                        </tr>`);
                        break;
                    case 'update_bell_time':
                        const bellTimeRow = document.getElementById(`bell-time-row-${data.id}`);
                        if (bellTimeRow) {
                            const cells = bellTimeRow.querySelectorAll('td');
                            cells[0].textContent = data.time;
                            cells[1].textContent = data.event;
                            cells[2].textContent = data.audio_name;
                            const editBtn = bellTimeRow.querySelector('.edit-bell-time-btn');
                            editBtn.dataset.time = data.time;
                            editBtn.dataset.event = data.event;
                            editBtn.dataset.audio = data.audio_file;
                        }
                        break;
                    case 'delete_bell_time':
                        document.getElementById(`bell-time-row-${data.id}`)?.remove();
                        break;
                    case 'delete_bell_audio':
                        document.getElementById(`audio-file-row-${data.id}`)?.remove();
                        break;
                }
            }

            // --- Event Listeners ---
            document.getElementById('add-schedule-form').addEventListener('submit', function(e) {
                handleFormSubmit(this, e);
            });
            document.getElementById('add-bell-time-form')?.addEventListener('submit', function(e) {
                handleFormSubmit(this, e);
            });
            document.getElementById('upload-audio-form').addEventListener('submit', function(e) {
                handleFormSubmit(this, e);
            });

            editScheduleForm.addEventListener('submit', function(e) {
                handleFormSubmit(this, e).then(success => {
                    if (success) closeEditScheduleModal();
                });
            });
            editBellTimeForm.addEventListener('submit', function(e) {
                handleFormSubmit(this, e).then(success => {
                    if (success) closeEditBellTimeModal();
                });
            });

            // Event delegation untuk tombol dinamis
            document.body.addEventListener('click', function(e) {
                if (e.target.matches('.edit-schedule-btn')) {
                    openEditScheduleModal(e.target.dataset.id, e.target.dataset.name);
                }
                if (e.target.matches('.edit-bell-time-btn')) {
                    openEditBellTimeModal(e.target.dataset.id, e.target.dataset.time, e.target.dataset.event, e.target.dataset.audio);
                }
            });

            document.body.addEventListener('submit', function(e) {
                const form = e.target;
                if (form.matches('.activate-schedule-form')) {
                    if (confirm('Apakah Anda yakin ingin mengaktifkan jadwal ini? Halaman akan dimuat ulang.')) {
                        handleFormSubmit(form, e);
                    } else {
                        e.preventDefault();
                    }
                } else if (form.matches('.delete-bell-time-form')) {
                    if (confirm('Apakah Anda yakin ingin menghapus waktu bel ini?')) {
                        handleFormSubmit(form, e);
                    } else {
                        e.preventDefault();
                    }
                } else if (form.matches('.delete-audio-form')) {
                    if (confirm('Apakah Anda yakin ingin menghapus file audio ini?')) {
                        handleFormSubmit(form, e);
                    } else {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>

</body>

</html>