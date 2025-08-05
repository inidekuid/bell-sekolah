# School Bell System

## Deskripsi
Aplikasi "School Bell System" adalah sistem otomatisasi bel sekolah yang memungkinkan penjadwalan bel sekolah dan pemutaran file audio secara otomatis.

## Prasyarat
Pastikan Anda memiliki prasyarat berikut sebelum memulai instalasi:
1. Server web (misalnya Apache)
2. PHP (versi 7.4 atau lebih baru)
3. SQLite3
4. Git (untuk mengkloning repositori)
kantortu

## Langkah-langkah Instalasi

### 1. Instalasi Prasyarat
Instal Apache, PHP, dan SQLite3 jika belum terpasang:

```sh
sudo apt update
sudo apt install apache2 php libapache2-mod-php php-sqlite3 git -y
```

## 2. Kloning Repositori
Kloning repositori aplikasi ke direktori web server:
```sh
cd /var/www/html
sudo git clone https://github.com/username/bell-sekolah.git
```

Gantilah `username` dengan nama pengguna GitHub Anda.

## 3. Setel Izin Direktori
Setel izin direktori agar web server dapat menulis ke database:
```sh
sudo chown -R www-data:www-data /var/www/html/bell-sekolah/database
sudo chmod -R 755 /var/www/html/bell-sekolah/database
```
## 4. Konfigurasi Apache
Buat file konfigurasi virtual host untuk aplikasi:
```sh
sudo nano /etc/apache2/sites-available/bell-sekolah.conf
```
Isi file dengan konfigurasi berikut:
```
<VirtualHost *:80>
    ServerAdmin admin@example.com
    DocumentRoot /var/www/html/bell-sekolah
    ServerName bell-sekolah.local

    <Directory /var/www/html/bell-sekolah>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/bell-sekolah_error.log
    CustomLog ${APACHE_LOG_DIR}/bell-sekolah_access.log combined
</VirtualHost>
```

Aktifkan situs dan mod_rewrite:
```sh
sudo a2ensite bell-sekolah.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## 5. Tambahkan Entri ke File Hosts
Tambahkan entri ke file /etc/hosts untuk mengakses aplikasi melalui nama domain lokal:
```sh
sudo nano /etc/hosts
```

Tambahkan baris berikut:
```sh
127.0.0.1 bell-sekolah.local
```

6. Inisialisasi Database
Akses aplikasi melalui browser dengan membuka `http://bell-sekolah.local`. Aplikasi akan secara otomatis menginisialisasi database dan membuat jadwal default jika belum ada.

7. Menjalankan Cron Job
Tambahkan cron job untuk menjalankan cron.php setiap menit:
```sh
sudo crontab -e
```
Tambahkan baris berikut:
```sh
* * * * * /usr/bin/php /var/www/html/bell-sekolah/cron.php
```

8. Selesai
Aplikasi "School Bell System" sekarang sudah terinstal dan siap digunakan. Anda dapat mengaksesnya melalui `http://bell-sekolah.local`.

Troubleshooting
Jika Anda mengalami masalah, periksa log Apache di `/var/log/apache2/` untuk informasi lebih lanjut.
