#!/bin/bash

# ==============================================================================
# Skrip Instalasi Sistem Bel Sekolah Otomatis
#
# Didesain untuk distribusi Linux berbasis Debian (Ubuntu, Debian, dll.)
# Memerlukan hak akses root (sudo) untuk instalasi paket dan konfigurasi.
# ==============================================================================

# --- Fungsi dan Variabel ---

# Fungsi untuk mencetak pesan dengan warna
print_info() {
    echo -e "\n\e[1;34m[INFO]\e[0m $1"
}

print_success() {
    echo -e "\e[1;32m[SUCCESS]\e[0m $1"
}

print_warning() {
    echo -e "\e[1;33m[WARNING]\e[0m $1"
}

print_error() {
    echo -e "\e[1;31m[ERROR]\e[0m $1" >&2
}

# Fungsi untuk memeriksa apakah perintah ada
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Fungsi untuk keluar jika terjadi kesalahan
exit_on_error() {
    print_error "$1"
    exit 1
}

# --- Pengecekan Awal ---

print_info "Memulai proses instalasi Bel Sekolah Otomatis..."

# 1. Periksa apakah OS adalah Linux
if [[ "$OSTYPE" != "linux-gnu"* ]]; then
    exit_on_error "Skrip ini hanya untuk sistem operasi Linux."
fi

# 2. Periksa hak akses root
if [ "$EUID" -ne 0 ]; then
    exit_on_error "Skrip ini harus dijalankan dengan hak akses root. Coba jalankan dengan 'sudo ./install.sh'"
fi

# 3. Periksa manajer paket (hanya mendukung apt)
if ! command_exists apt-get; then
    exit_on_error "Manajer paket 'apt' tidak ditemukan. Skrip ini hanya mendukung distribusi berbasis Debian (Ubuntu, dll.)."
fi


# --- Instalasi ---

# 4. Instalasi dependensi yang diperlukan
print_info "Memperbarui daftar paket dan menginstal dependensi..."
apt-get update || exit_on_error "Gagal memperbarui daftar paket."
apt-get install -y apache2 php php-sqlite3 mpg123 || exit_on_error "Gagal menginstal paket yang diperlukan."
print_success "Dependensi berhasil diinstal."


# 5. Konfigurasi Direktori Aplikasi
DEFAULT_APP_DIR="/var/www/html/bel-sekolah"
read -p "Masukkan path direktori instalasi [Default: $DEFAULT_APP_DIR]: " APP_DIR
APP_DIR=${APP_DIR:-$DEFAULT_APP_DIR}

print_info "Membuat direktori aplikasi di '$APP_DIR'..."
mkdir -p "$APP_DIR/database" || exit_on_error "Gagal membuat direktori database."
mkdir -p "$APP_DIR/audio" || exit_on_error "Gagal membuat direktori audio."
print_success "Direktori berhasil dibuat."


# 6. Salin File Aplikasi
print_info "Menyalin file aplikasi..."
# Pastikan file-file ini ada di direktori yang sama dengan install.sh
FILES_TO_COPY=("index.php" "api.php" "config.php" "install.php" "default_bell.mp3")
for file in "${FILES_TO_COPY[@]}"; do
    if [ -f "$file" ]; then
        cp "$file" "$APP_DIR/" || exit_on_error "Gagal menyalin '$file'."
    else
        print_warning "File '$file' tidak ditemukan, melompati..."
    fi
done
print_success "File aplikasi berhasil disalin."


# 7. Jalankan skrip instalasi PHP untuk membuat database
print_info "Menjalankan skrip setup database PHP..."
php "$APP_DIR/install.php" || exit_on_error "Gagal menjalankan skrip install.php."
print_success "Database dan tabel berhasil dibuat."


# 8. Atur Izin Kepemilikan dan Hak Akses
print_info "Mengatur izin file dan direktori..."
chown -R www-data:www-data "$APP_DIR" || exit_on_error "Gagal mengubah kepemilikan."
# Atur direktori agar bisa ditulis oleh grup (untuk upload, dll.)
find "$APP_DIR" -type d -exec chmod 775 {} \;
# Atur file agar hanya bisa dibaca
find "$APP_DIR" -type f -exec chmod 664 {} \;
# Berikan izin eksekusi untuk skrip jika ada (meskipun saat ini tidak ada)
# find "$APP_DIR" -name "*.sh" -exec chmod 775 {} \;
print_success "Izin berhasil diatur."


# 9. Konfigurasi Apache Web Server
APACHE_CONF_FILE="/etc/apache2/sites-available/bel-sekolah.conf"
print_info "Membuat file konfigurasi Apache di '$APACHE_CONF_FILE'..."

cat > "$APACHE_CONF_FILE" << EOF
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot $APP_DIR

    <Directory $APP_DIR>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/bel-sekolah-error.log
    CustomLog \${APACHE_LOG_DIR}/bel-sekolah-access.log combined
</VirtualHost>
EOF
print_success "File konfigurasi Apache berhasil dibuat."

print_info "Mengaktifkan situs dan modul yang diperlukan..."
a2ensite bel-sekolah.conf || exit_on_error "Gagal mengaktifkan situs."
a2enmod rewrite || exit_on_error "Gagal mengaktifkan mod_rewrite."
systemctl restart apache2 || exit_on_error "Gagal me-restart Apache."
print_success "Apache berhasil dikonfigurasi dan di-restart."

# --- Selesai ---

print_info "INSTALASI SELESAI!"
echo "=================================================================="
print_success "Aplikasi Bel Sekolah Otomatis Anda siap digunakan."
echo "Silakan akses melalui browser Anda di:"
echo "http://<IP_SERVER_ANDA>"
print_warning "PENTING: Demi keamanan, hapus file 'install.sh' dan 'install.php' dari server Anda."
echo "=================================================================="

