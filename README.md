# Sistem Monitoring Kinerja dan Aktivitas Harian BPS Kota Palangka Raya

Sistem Monitoring Kinerja dan Aktivitas Harian BPS Kota Palangka Raya adalah aplikasi web berbasis Laravel yang digunakan untuk membantu proses pencatatan, pemantauan, dan evaluasi pekerjaan pegawai secara lebih terstruktur. Sistem ini mengelola alur kerja internal mulai dari IKU, RK Ketua, Project, RK Anggota, IKI, hingga Daily Task.

Aplikasi ini dirancang untuk penggunaan internal dengan beberapa role pengguna, yaitu Admin, Kepala, Ketua Tim, dan Anggota. Setiap role memiliki hak akses, dashboard, dan fitur yang berbeda sesuai kebutuhan operasional sistem.

---

## Daftar Isi

* [Gambaran Umum Sistem](#gambaran-umum-sistem)
* [Alur Data Utama](#alur-data-utama)
* [Role dan Hak Akses](#role-dan-hak-akses)
* [Modul Utama Sistem](#modul-utama-sistem)
* [Teknologi yang Digunakan](#teknologi-yang-digunakan)
* [Persyaratan Sistem](#persyaratan-sistem)
* [Struktur Database Utama](#struktur-database-utama)
* [Clone dan Setup Project di Komputer Kantor](#clone-dan-setup-project-di-komputer-kantor)
* [Konfigurasi Environment](#konfigurasi-environment)
* [Setup Database](#setup-database)
* [Seeder dan Akun Default](#seeder-dan-akun-default)
* [File Template Import RK](#file-template-import-rk)
* [Menjalankan Project](#menjalankan-project)
* [Perintah Development](#perintah-development)
* [Panduan Maintenance](#panduan-maintenance)
* [Panduan Deploy Production](#panduan-deploy-production)
* [Troubleshooting](#troubleshooting)
* [Catatan Keamanan](#catatan-keamanan)

---

## Gambaran Umum Sistem

Sistem ini dibuat untuk mendukung monitoring kinerja pegawai dengan alur kerja bertingkat. Setiap pekerjaan tidak hanya dicatat sebagai aktivitas harian, tetapi juga dikaitkan dengan indikator, rencana kerja, project, bukti dukung, dan status review.

Alur utama sistem:

```text
IKU
↓
RK Ketua
↓
Project
↓
RK Anggota
↓
IKI
↓
Daily Task
```

Penjelasan singkat:

* **IKU** adalah Indikator Kinerja Utama sebagai level kinerja tertinggi.
* **RK Ketua** adalah rencana kerja Ketua Tim yang diturunkan dari IKU.
* **Project** adalah pekerjaan atau kegiatan yang dibuat berdasarkan RK Ketua.
* **RK Anggota** adalah rencana kerja anggota dalam suatu project.
* **IKI** adalah Indikator Kinerja Individu yang menjadi unit utama proses submit, review, approve, dan reject.
* **Daily Task** adalah catatan aktivitas harian yang mendukung penyelesaian IKI.

Approval utama dalam sistem berada pada level **IKI**. Daily Task digunakan sebagai bukti proses kerja, sedangkan progress sistem diperbarui setelah IKI disetujui oleh Ketua Tim.

---

## Alur Data Utama

Ringkasan alur data sistem:

```text
Admin menyiapkan user, tim, dan IKU
↓
Ketua Tim membuat RK Ketua
↓
Ketua Tim membuat Project berdasarkan RK Ketua
↓
Ketua Tim menambahkan anggota ke Project
↓
Ketua Tim membuat RK Anggota
↓
Anggota membuat IKI berdasarkan RK Anggota
↓
Anggota mengisi Daily Task sebagai aktivitas pendukung
↓
Anggota melampirkan bukti dukung
↓
Anggota submit IKI
↓
Ketua Tim melakukan review IKI
↓
Jika ditolak, Anggota melakukan revisi
↓
Jika disetujui, progress sistem diperbarui
↓
Kepala dan Admin dapat melakukan monitoring
```

Alur progress setelah IKI disetujui:

```text
IKI Approved
↓
Progress RK Anggota diperbarui
↓
Progress Project diperbarui
↓
Progress RK Ketua diperbarui
↓
Progress IKU termonitor
↓
Dashboard setiap role menampilkan data terbaru
```

---

## Role dan Hak Akses

### 1. Admin

Admin adalah role utama untuk konfigurasi dan pengelolaan data sistem.

Hak akses Admin:

* Mengelola user.
* Mengelola role pengguna.
* Mengelola tim kerja.
* Mengelola IKU.
* Import IKU.
* Mengelola RK Ketua.
* Mengelola Project.
* Mengelola RK Anggota.
* Mengelola IKI.
* Mengelola Daily Task.
* Melihat seluruh progress sistem.
* Melakukan reset password user.
* Melakukan import data user atau template.
* Mengakses dashboard Admin.

---

### 2. Kepala

Kepala adalah role monitoring. Role ini digunakan untuk melihat perkembangan pekerjaan tanpa melakukan perubahan data.

Hak akses Kepala:

* Melihat dashboard monitoring.
* Melihat data IKU.
* Melihat RK Ketua.
* Melihat Project.
* Melihat RK Anggota.
* Melihat IKI.
* Melihat Daily Task.
* Melihat rekap progress.
* Melakukan monitoring secara read-only.

Kepala tidak memiliki akses create, update, delete, submit, approve, atau reject.

---

### 3. Ketua Tim

Ketua Tim adalah role yang bertanggung jawab terhadap pengelolaan pekerjaan anggota dalam project yang dipimpin.

Hak akses Ketua Tim:

* Melihat dashboard Ketua Tim.
* Mengelola RK Ketua miliknya.
* Membuat Project.
* Menambahkan anggota ke Project.
* Membuat RK Anggota untuk anggota project.
* Melihat aktivitas harian anggota.
* Melakukan review IKI.
* Approve IKI.
* Reject IKI dengan catatan perbaikan.
* Bulk approve beberapa IKI sekaligus.
* Melihat Daily Task anggota sesuai project yang dipimpin.

---

### 4. Anggota

Anggota adalah role pelaksana pekerjaan.

Hak akses Anggota:

* Melihat dashboard Anggota.
* Melihat project yang diikuti.
* Melihat RK Anggota miliknya.
* Membuat dan mengelola IKI miliknya.
* Mengisi Daily Task.
* Melampirkan tautan bukti dukung.
* Export rekap kegiatan harian.
* Submit IKI untuk direview Ketua Tim.
* Melakukan revisi jika IKI ditolak.

---

## Modul Utama Sistem

### Authentication

Modul authentication mencakup:

* Login.
* Logout.
* Forgot password.
* Reset password.
* Force password change.

Register publik dinonaktifkan karena sistem bersifat internal. User dibuat oleh Admin melalui halaman manajemen user atau melalui fitur import.

---

### Dashboard

Dashboard tersedia berdasarkan role:

```text
/admin
/kepala
/ketua
/anggota
```

Setiap dashboard menampilkan data yang berbeda sesuai hak akses pengguna.

---

### User Management

Digunakan oleh Admin untuk mengelola akun pengguna.

Fitur utama:

* Melihat daftar user.
* Menambah user.
* Mengubah data user.
* Menghapus user.
* Reset password user.
* Import user.
* Mengatur role user.

---

### Team Management

Digunakan untuk mengelola tim kerja.

Fitur utama:

* Membuat tim.
* Menentukan Ketua Tim.
* Mengubah data tim.
* Menghapus tim.
* Mengelola anggota tim.
* Melihat relasi user dengan tim.

---

### IKU

IKU adalah level utama kinerja.

Fitur utama:

* Menampilkan daftar IKU.
* Menambah IKU.
* Mengubah IKU.
* Menghapus IKU.
* Import IKU dari Excel.
* Search IKU.
* Monitoring IKU.

IKU menjadi dasar pembuatan RK Ketua.

---

### RK Ketua

RK Ketua adalah rencana kerja milik Ketua Tim yang diturunkan dari IKU.

Fitur utama:

* Menampilkan daftar RK Ketua.
* Membuat RK Ketua.
* Mengubah RK Ketua.
* Menghapus RK Ketua jika belum memiliki relasi yang mengunci.
* Melihat detail RK Ketua.
* Search RK Ketua.
* Monitoring progress RK Ketua.
* Template picker RK Ketua.

RK Ketua menjadi dasar pembuatan Project.

---

### Template RK Ketua

Template RK Ketua digunakan untuk mempercepat pengisian rencana kerja.

Fitur utama:

* Import template RK Ketua dari Excel.
* Menyimpan template ke database.
* Menampilkan template picker pada form RK Ketua.
* User tetap dapat mengetik manual walaupun template tersedia.

Template tidak otomatis membuat RK Ketua. Template hanya membantu pengisian uraian RK Ketua.

---

### Project

Project dibuat berdasarkan RK Ketua.

Fitur utama:

* Menampilkan daftar project.
* Membuat project.
* Mengubah project.
* Menghapus project.
* Melihat detail project.
* Menambahkan anggota project.
* Melihat progress project.
* Search project.
* Menampilkan jadwal project jika field jadwal tersedia.

Project menjadi wadah kerja bagi anggota dan dasar pembuatan RK Anggota.

---

### RK Anggota

RK Anggota adalah rencana kerja anggota dalam suatu project.

Fitur utama:

* Menampilkan daftar RK Anggota.
* Membuat RK Anggota.
* Mengubah RK Anggota.
* Menghapus RK Anggota sesuai hak akses.
* Melihat detail RK Anggota.
* Menghubungkan RK Anggota dengan Project dan Anggota.
* Menjadi dasar pembuatan IKI.

Progress RK Anggota dihitung dari status IKI yang berkaitan. Approval utama tidak berada pada RK Anggota, tetapi berada pada level IKI.

---

### IKI

IKI adalah unit approval utama dalam sistem.

Fitur utama:

* Menampilkan daftar IKI.
* Membuat IKI.
* Mengubah IKI selama status masih dapat diedit.
* Menghapus IKI sesuai hak akses.
* Submit IKI.
* Review IKI oleh Ketua Tim.
* Approve IKI.
* Reject IKI dengan catatan perbaikan.
* Bulk approve IKI.
* Melihat status IKI.
* Menampilkan bukti final dan Daily Task pendukung.

Status IKI yang digunakan dalam sistem:

```text
draft
submitted
approved
rejected
```

---

### Daily Task

Daily Task adalah catatan aktivitas harian yang mendukung IKI.

Fitur utama:

* Menampilkan daftar Daily Task.
* Membuat Daily Task.
* Mengubah Daily Task selama IKI belum terkunci.
* Menghapus Daily Task sesuai hak akses.
* Menghubungkan Daily Task dengan IKI.
* Menyimpan uraian aktivitas harian.
* Menyimpan output atau progres pekerjaan.
* Menyimpan tautan bukti dukung.
* Filter berdasarkan pencarian, tahun, status, dan rentang tanggal.
* Export rekap kegiatan harian ke Excel.

Daily Task bukan unit approval utama, tetapi menjadi bukti proses kerja yang digunakan Ketua Tim dalam melakukan review IKI.

---

### Notification

Sistem menyediakan fitur notifikasi untuk membantu pengguna mengetahui perubahan status atau tindakan yang perlu diperhatikan.

Fitur utama:

* Melihat daftar notifikasi.
* Melihat jumlah notifikasi belum dibaca.
* Menandai satu notifikasi sebagai dibaca.
* Menandai semua notifikasi sebagai dibaca.

Notifikasi digunakan untuk mendukung proses submit, review, approve, reject, dan aktivitas terkait monitoring.

---

### Calendar Events

Sistem menyediakan endpoint calendar events untuk menampilkan data aktivitas atau agenda pada dashboard yang membutuhkan tampilan kalender.

Endpoint utama:

```text
/calendar/events
```

---

## Teknologi yang Digunakan

Project ini menggunakan:

* Laravel
* PHP
* MySQL/MariaDB
* Blade Template
* Tailwind CSS
* Vite
* JavaScript
* Laravel Breeze
* Maatwebsite Excel
* Composer
* NPM
* Git

Versi Laravel, PHP package, dan frontend package mengikuti konfigurasi pada:

```text
composer.json
package.json
```

---

## Persyaratan Sistem

Environment yang disarankan untuk development lokal:

* PHP 8.2 atau lebih baru
* Composer
* Node.js dan NPM
* MySQL atau MariaDB
* Git
* XAMPP, Laragon, Laravel Herd, Valet, atau server lokal lain
* Browser modern

Untuk komputer kantor berbasis Windows dan XAMPP, pastikan service berikut aktif:

```text
Apache
MySQL
```

---

## Struktur Database Utama

Database yang digunakan pada environment lokal:

```text
bps_monitoring
```

Tabel utama sistem:

```text
users
teams
team_members
project_members
ikus
iku_templates
rk_ketuas
rk_ketua_templates
projects
rk_anggotas
rk_anggota_templates
ikis
daily_tasks
notifications
sessions
cache
jobs
failed_jobs
migrations
```

Relasi utama sistem:

```text
IKU
↓
RK Ketua
↓
Project
↓
RK Anggota
↓
IKI
↓
Daily Task
```

---

## Clone dan Setup Project di Komputer Kantor

### 1. Clone Repository

Masuk ke folder `htdocs` jika menggunakan XAMPP:

```bash
cd C:\xampp\htdocs
```

Clone repository:

```bash
git clone https://github.com/mrcatfish75-debug/bps-monitoring.git
```

Masuk ke folder project:

```bash
cd bps-monitoring
```

---

### 2. Install Dependency Backend

Jalankan:

```bash
composer install
```

Jika Composer belum tersedia, install Composer terlebih dahulu.

---

### 3. Install Dependency Frontend

Jalankan:

```bash
npm install
```

Lalu build asset frontend:

```bash
npm run build
```

Untuk mode development, dapat menggunakan:

```bash
npm run dev
```

Catatan: folder `node_modules` dan `public/build` tidak disimpan di GitHub. Keduanya dibuat ulang melalui `npm install` dan `npm run build`.

---

### 4. Buat File Environment

Untuk Windows PowerShell:

```bash
copy .env.example .env
```

Untuk Git Bash, Linux, atau macOS:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

---

### 5. Konfigurasi Database

Pastikan file `.env` berisi konfigurasi berikut:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bps_monitoring
DB_USERNAME=root
DB_PASSWORD=
```

Jika di komputer kantor nama database berbeda, ubah bagian:

```env
DB_DATABASE=bps_monitoring
```

sesuai nama database yang digunakan.

---

### 6. Buat Database di phpMyAdmin

Buka phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Buat database baru:

```text
bps_monitoring
```

Gunakan collation:

```text
utf8mb4_unicode_ci
```

---

## Konfigurasi Environment

Contoh isi penting `.env.example` yang digunakan project:

```env
APP_NAME="Sistem Monitoring Kinerja BPS"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bps_monitoring
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@bps-monitoring.local"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
```

File `.env.example` boleh masuk GitHub. File `.env` tidak boleh masuk GitHub karena berisi konfigurasi khusus masing-masing perangkat.

---

## Setup Database

Ada dua pilihan setup database.

---

### Opsi A: Menggunakan Database Hasil Export

Opsi ini disarankan jika ingin melanjutkan data dari laptop pengembang utama.

Di laptop lama:

1. Buka phpMyAdmin.
2. Pilih database:

```text
bps_monitoring
```

3. Klik menu **Export**.
4. Pilih format **SQL**.
5. Simpan file `.sql`.

Di komputer kantor:

1. Buka phpMyAdmin.
2. Buat database baru:

```text
bps_monitoring
```

3. Klik database tersebut.
4. Pilih menu **Import**.
5. Pilih file `.sql`.
6. Jalankan import.

Jika database diimport dari file SQL, tidak perlu menjalankan `migrate:fresh` karena dapat menghapus data hasil import.

Setelah import selesai, jalankan:

```bash
php artisan optimize:clear
```

---

### Opsi B: Setup Database Kosong dari Migration dan Seeder

Opsi ini digunakan jika ingin membuat database baru tanpa data lama, tetapi tetap memiliki akun default dan template RK awal.

Jalankan:

```bash
php artisan migrate --seed
```

Atau jika database sudah pernah dibuat dan hanya ingin menjalankan seeder:

```bash
php artisan db:seed
```

Jika ingin reset seluruh database dari awal, gunakan:

```bash
php artisan migrate:fresh --seed
```

Perhatian: `migrate:fresh --seed` akan menghapus seluruh tabel dan data lama. Gunakan hanya pada environment development atau setelah backup database.

---

## Seeder dan Akun Default

Project ini menyediakan seeder untuk membantu setup awal sistem, terutama saat project baru diclone atau database dibuat ulang.

Seeder utama dipanggil melalui:

```text
database/seeders/DatabaseSeeder.php
```

Seeder yang digunakan:

```text
UserSeeder
RkKetuaTemplateSeeder
RkAnggotaTemplateSeeder
```

### Akun Default

Setelah menjalankan:

```bash
php artisan db:seed
```

atau:

```bash
php artisan migrate --seed
```

sistem akan memiliki akun default untuk 4 role utama berikut:

| Role      | Email                                         | Password    |
| --------- | --------------------------------------------- | ----------- |
| Admin     | [admin@bps.go.id](mailto:admin@bps.go.id)     | Admin12345@ |
| Kepala    | [kepala@bps.go.id](mailto:kepala@bps.go.id)   | Admin12345@ |
| Ketua Tim | [ketua@bps.go.id](mailto:ketua@bps.go.id)     | Admin12345@ |
| Anggota   | [anggota@bps.go.id](mailto:anggota@bps.go.id) | Admin12345@ |

Akun default ini disediakan agar developer, admin, atau petugas maintenance dapat langsung masuk ke sistem setelah setup awal.

Setelah sistem digunakan pada lingkungan kantor atau production, password default sebaiknya segera diganti melalui fitur manajemen user atau fitur reset password.

---

### Menjalankan Seeder Tertentu

Menjalankan hanya seeder user:

```bash
php artisan db:seed --class=UserSeeder
```

Menjalankan hanya seeder template RK Ketua:

```bash
php artisan db:seed --class=RkKetuaTemplateSeeder
```

Menjalankan hanya seeder template RK Anggota:

```bash
php artisan db:seed --class=RkAnggotaTemplateSeeder
```

---

## File Template Import RK

Project ini menyertakan file template Excel untuk mendukung import template RK Ketua dan RK Anggota.

Lokasi file template:

```text
storage/app/imports/rk-ketua.xlsx
storage/app/imports/RkAnggota.xlsx
```

File tersebut digunakan oleh:

```text
RkKetuaTemplateSeeder
RkAnggotaTemplateSeeder
```

Fungsinya:

* `rk-ketua.xlsx` digunakan untuk mengisi template RK Ketua.
* `RkAnggota.xlsx` digunakan untuk mengisi template RK Anggota.

Template ini membantu pengisian rencana kerja agar user tidak perlu mengetik seluruh uraian secara manual. Jika file template tersedia, seeder akan membaca file Excel tersebut dan mengisi data ke tabel template. Jika file tidak ditemukan, proses seeder akan melewati import template dan menampilkan peringatan tanpa menghentikan setup sistem.

Pastikan package Excel sudah terinstall melalui Composer. Package yang digunakan adalah:

```text
maatwebsite/excel
```

Jika package belum tersedia, jalankan:

```bash
composer install
```

---

## Menjalankan Project

Setelah dependency, `.env`, APP_KEY, dan database siap, jalankan:

```bash
php artisan optimize:clear
php artisan storage:link
php artisan serve
```

Akses aplikasi melalui:

```text
http://127.0.0.1:8000
```

Jika menggunakan XAMPP virtual host, sesuaikan `APP_URL` pada `.env`.

---

## Perintah Development

Menjalankan server Laravel:

```bash
php artisan serve
```

Menjalankan Vite development server:

```bash
npm run dev
```

Build asset frontend:

```bash
npm run build
```

Membersihkan cache Laravel:

```bash
php artisan optimize:clear
```

Membersihkan cache satu per satu:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

Melihat daftar route:

```bash
php artisan route:list
```

Melihat status migration:

```bash
php artisan migrate:status
```

Menjalankan migration baru:

```bash
php artisan migrate
```

Rollback migration terakhir:

```bash
php artisan migrate:rollback
```

Membuka Tinker:

```bash
php artisan tinker
```

---

## Update Project dari GitHub

Jika project sudah pernah diclone dan ingin mengambil update terbaru:

```bash
git pull origin main
```

Setelah pull, jalankan:

```bash
composer install
npm install
npm run build
php artisan migrate
php artisan optimize:clear
```

Jika perubahan hanya frontend, biasanya cukup:

```bash
npm install
npm run build
```

Jika perubahan hanya backend tanpa package baru, biasanya cukup:

```bash
php artisan optimize:clear
```

Jika ada migration baru, wajib jalankan:

```bash
php artisan migrate
```

Jika ada perubahan seeder dan ingin memperbarui data awal:

```bash
php artisan db:seed
```

---

## Panduan Maintenance

### 1. Jangan Commit File Sensitif

File yang tidak boleh masuk Git:

```text
.env
vendor/
node_modules/
storage/logs/
storage/framework/
public/build/
database/*.sqlite
```

File template Excel pada folder `storage/app/imports/` sengaja disertakan karena digunakan sebagai pendukung seeder template RK. Namun, file lain di dalam folder `storage` tetap tidak perlu dimasukkan ke repository kecuali memang dibutuhkan untuk setup project.

---

### 2. Backup Database

Backup database sebaiknya dilakukan secara rutin, terutama sebelum:

* Update fitur besar.
* Menjalankan migration baru.
* Import data.
* Deploy ke server.
* Mengubah struktur tabel.
* Menjalankan `migrate:fresh`.

Backup dapat dilakukan melalui phpMyAdmin:

```text
phpMyAdmin → pilih database → Export → SQL
```

---

### 3. Setelah Mengubah Route

Jalankan:

```bash
php artisan route:list
php artisan optimize:clear
```

---

### 4. Setelah Mengubah Blade

Jika tampilan tidak berubah, jalankan:

```bash
php artisan view:clear
php artisan optimize:clear
```

---

### 5. Setelah Mengubah Config

Jika perubahan config tidak terbaca, jalankan:

```bash
php artisan config:clear
php artisan optimize:clear
```

---

### 6. Setelah Menambah Package Composer

Jalankan:

```bash
composer install
php artisan optimize:clear
```

Lalu commit file berikut jika berubah:

```text
composer.json
composer.lock
```

---

### 7. Setelah Menambah Package NPM

Jalankan:

```bash
npm install
npm run build
```

Lalu commit file berikut jika berubah:

```text
package.json
package-lock.json
```

---

## Panduan Deploy Production

Sebelum deploy ke server production, ubah konfigurasi `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-production
```

Konfigurasi database production:

```env
DB_CONNECTION=mysql
DB_HOST=host_database
DB_PORT=3306
DB_DATABASE=nama_database_production
DB_USERNAME=user_database
DB_PASSWORD=password_database
```

Install dependency production:

```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

Jalankan migration:

```bash
php artisan migrate --force
```

Cache konfigurasi:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pastikan permission folder berikut benar:

```text
storage/
bootstrap/cache/
```

Checklist production:

* Gunakan HTTPS.
* Pastikan `APP_DEBUG=false`.
* Jangan upload `.env` ke GitHub.
* Gunakan password database yang kuat.
* Backup database sebelum deploy.
* Jalankan migration dengan hati-hati.
* Pastikan folder `storage` dan `bootstrap/cache` writable.
* Pastikan register publik tetap nonaktif.
* Pastikan akses role sudah diuji.
* Pastikan password akun default sudah diganti.
* Pastikan data sensitif tidak tampil untuk role yang tidak berwenang.

---

## Troubleshooting

### 1. Error: APP_KEY belum tersedia

Gejala:

```text
No application encryption key has been specified.
```

Solusi:

```bash
php artisan key:generate
```

---

### 2. Error: Database tidak ditemukan

Gejala:

```text
SQLSTATE[HY000] [1049] Unknown database
```

Solusi:

* Pastikan database `bps_monitoring` sudah dibuat di phpMyAdmin.
* Pastikan konfigurasi `.env` benar.
* Jalankan:

```bash
php artisan config:clear
php artisan optimize:clear
```

---

### 3. Error: Access denied for user root

Solusi:

Periksa bagian `.env`:

```env
DB_USERNAME=root
DB_PASSWORD=
```

Jika MySQL komputer kantor menggunakan password, isi `DB_PASSWORD`.

---

### 4. Error: Vite manifest not found

Gejala:

```text
Vite manifest not found
```

Solusi:

```bash
npm install
npm run build
```

Untuk development:

```bash
npm run dev
```

---

### 5. Error: Class not found atau package tidak terbaca

Solusi:

```bash
composer install
composer dump-autoload
php artisan optimize:clear
```

---

### 6. Perubahan Blade tidak muncul

Solusi:

```bash
php artisan view:clear
php artisan optimize:clear
```

---

### 7. Route baru tidak terbaca

Solusi:

```bash
php artisan route:clear
php artisan optimize:clear
php artisan route:list
```

---

### 8. Migration duplicate column

Gejala:

```text
SQLSTATE[42S21]: Column already exists
```

Solusi:

* Cek migration yang menambahkan kolom sama.
* Cek status migration:

```bash
php artisan migrate:status
```

* Jangan menjalankan `migrate:fresh` pada database yang berisi data penting.
* Backup database sebelum memperbaiki migration.

---

### 9. Data kosong setelah clone

Penyebab:

GitHub hanya menyimpan source code, bukan isi database.

Solusi:

* Gunakan `php artisan migrate --seed` untuk database baru.
* Atau export database dari perangkat lama dan import ke komputer baru.
* Pastikan `.env` mengarah ke database yang benar.

---

### 10. Seeder template RK tidak mengisi data

Kemungkinan penyebab:

* File Excel template tidak ditemukan.
* Nama file tidak sesuai.
* Package Excel belum terinstall.
* Format kolom Excel tidak sesuai dengan import class.

Pastikan file berada di:

```text
storage/app/imports/rk-ketua.xlsx
storage/app/imports/RkAnggota.xlsx
```

Lalu jalankan:

```bash
php artisan db:seed --class=RkKetuaTemplateSeeder
php artisan db:seed --class=RkAnggotaTemplateSeeder
```

---

## Catatan Akun

Sistem ini bersifat internal. Register publik dinonaktifkan. Akun dibuat melalui Admin atau melalui import user.

Akun default setelah menjalankan seeder:

```text
Admin     : admin@bps.go.id / Admin12345@
Kepala    : kepala@bps.go.id / Admin12345@
Ketua Tim : ketua@bps.go.id / Admin12345@
Anggota   : anggota@bps.go.id / Admin12345@
```

Jika menggunakan database hasil export, akun yang tersedia mengikuti isi database hasil export tersebut.

Jika menggunakan database kosong, jalankan:

```bash
php artisan migrate --seed
```

atau:

```bash
php artisan db:seed
```

agar akun default tersedia.

---

## Catatan Keamanan

Beberapa prinsip keamanan yang diterapkan:

* Authentication menggunakan Laravel.
* Route utama dilindungi middleware `auth`.
* Setiap role dibatasi menggunakan middleware role.
* Kepala hanya memiliki akses monitoring/read-only.
* Register publik dinonaktifkan.
* User dibuat oleh Admin.
* Login memiliki validasi dan pembatasan request.
* Forgot password memiliki pembatasan request.
* Logout menggunakan method POST.
* Request divalidasi pada controller.
* Ownership data dibatasi sesuai role.
* Force password change tersedia untuk user dengan password sementara/default.
* CSRF protection aktif.
* Blade menggunakan escaping default `{{ }}` untuk mengurangi risiko XSS.

Untuk keamanan penggunaan nyata, password default harus diganti setelah setup awal.

---

## Struktur Route Utama

Contoh route utama:

```text
/
login
forgot-password
reset-password
force-change-password

/admin
/admin/users
/admin/team
/admin/iku
/admin/rk-ketua
/admin/project
/admin/rk-anggota
/admin/iki
/admin/daily-task

/kepala
/kepala/iku
/kepala/rk-ketua
/kepala/project
/kepala/rk-anggota
/kepala/iki
/kepala/daily-task

/ketua
/ketua/rk-ketua
/ketua/project
/ketua/rk-anggota
/ketua/iki
/ketua/daily-task

/anggota
/anggota/project
/anggota/rk-anggota
/anggota/iki
/anggota/daily-task

/notification
/calendar/events
/export/daily-tasks
```

Untuk melihat route lengkap:

```bash
php artisan route:list
```

---

## Status Project

Project ini sudah berada pada tahap fungsional untuk kebutuhan monitoring kinerja dan aktivitas harian berbasis role.

Fitur utama yang tersedia:

* Authentication.
* Role-based access.
* Dashboard per role.
* Manajemen user.
* Manajemen tim kerja.
* Kelola dan import IKU.
* RK Ketua.
* Template RK Ketua.
* Project.
* RK Anggota.
* Template RK Anggota.
* IKI sebagai approval utama.
* Daily Task sebagai catatan aktivitas harian.
* Pelampiran bukti dukung melalui tautan.
* Submit IKI.
* Review IKI.
* Approve IKI.
* Reject IKI.
* Bulk approve IKI.
* Export rekap kegiatan harian.
* Notification.
* Dashboard monitoring Kepala.
* Calendar events endpoint.
* Progress bertingkat berdasarkan IKI yang disetujui.
* Seeder akun default untuk 4 role.
* Seeder template RK Ketua dan RK Anggota dari file Excel.

---

## Rekomendasi Pengembangan Lanjutan

Beberapa fitur yang dapat dikembangkan selanjutnya:

* Notifikasi otomatis untuk Anggota yang belum mengisi Daily Task.
* Notifikasi task tanpa bukti dukung.
* Penyempurnaan tampilan kalender aktivitas.
* Export laporan yang lebih lengkap.
* Dashboard analitik untuk Kepala.
* Audit log aktivitas user.
* Pengujian UI/UX dengan pengguna langsung.
* Optimasi query untuk data besar.
* Pengaturan permission yang lebih granular.

---

## Lisensi dan Penggunaan

Project ini dibuat untuk kebutuhan pengembangan Sistem Monitoring Kinerja dan Aktivitas Harian BPS Kota Palangka Raya.

Penggunaan, distribusi, dan pengembangan lebih lanjut perlu menyesuaikan kebijakan internal instansi atau pemilik repository.
