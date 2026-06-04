# Sistem Monitoring Kinerja BPS Kota Palangka Raya

Sistem Monitoring Kinerja BPS Kota Palangka Raya adalah aplikasi web berbasis Laravel yang digunakan untuk membantu proses perencanaan, pembagian, pelaksanaan, pemantauan, dan evaluasi pekerjaan internal berbasis indikator kinerja. Sistem ini dirancang dengan alur kerja berjenjang mulai dari IKU, RK Ketua, Project, RK Anggota, IKI, hingga Daily Task.

Aplikasi ini mendukung beberapa role pengguna, yaitu Admin, Kepala, Ketua Tim, dan Anggota. Setiap role memiliki hak akses dan dashboard yang berbeda sesuai kebutuhan operasional dan monitoring.

---

## Gambaran Umum Sistem

Sistem ini dibuat untuk mengelola alur kerja kinerja secara bertingkat:

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

- **IKU** adalah indikator kinerja utama sebagai level paling atas.
- **RK Ketua** adalah rencana kinerja ketua tim yang diturunkan dari IKU.
- **Project** adalah pekerjaan/proyek yang dibuat berdasarkan RK Ketua.
- **RK Anggota** adalah rencana kerja anggota dalam suatu project.
- **IKI** adalah unit kerja/indikator individu yang menjadi level utama approval.
- **Daily Task** adalah catatan aktivitas harian dan bukti pekerjaan yang mendukung IKI.

Approval utama berada pada level **IKI**. Daily Task digunakan sebagai bukti dan proses kerja, sedangkan progress sistem dihitung secara bertingkat dari IKI yang disetujui.

---

## Tujuan Sistem

Sistem ini bertujuan untuk:

- Membantu digitalisasi monitoring kinerja internal.
- Mempermudah Admin dalam mengelola user, role, tim, IKU, dan data kerja.
- Membantu Ketua Tim dalam membuat RK Ketua, Project, RK Anggota, dan melakukan review IKI.
- Membantu Anggota dalam mencatat pekerjaan harian, mengunggah bukti, dan mengajukan IKI.
- Membantu Kepala dalam melakukan monitoring menyeluruh tanpa mengubah data.
- Menyediakan dashboard progress berbasis role.
- Mengurangi risiko pekerjaan tidak termonitor karena seluruh aktivitas tercatat dalam sistem.

---

## Role Pengguna

### 1. Admin

Admin memiliki akses pengelolaan penuh terhadap data sistem.

Hak akses utama Admin:

- Mengelola user.
- Mengelola role pengguna.
- Mengelola tim kerja.
- Import dan kelola IKU.
- Mengelola RK Ketua.
- Mengelola Project.
- Mengelola RK Anggota.
- Mengelola IKI.
- Mengelola Daily Task.
- Melihat seluruh progress sistem.
- Melakukan reset password user.
- Import data user.
- Import data IKU.
- Melakukan monitoring seluruh data.

Admin adalah role utama untuk konfigurasi dan pengelolaan sistem.

---

### 2. Kepala

Kepala adalah role monitoring. Role ini digunakan untuk melihat perkembangan kinerja tanpa melakukan perubahan data.

Hak akses utama Kepala:

- Melihat dashboard monitoring.
- Melihat data IKU.
- Melihat data RK Ketua.
- Melihat data Project.
- Melihat data RK Anggota.
- Melihat data IKI.
- Melihat data Daily Task.
- Melihat rekap progress seluruh tim.
- Melakukan monitoring kinerja secara read-only.

Kepala tidak memiliki akses create, update, delete, submit, approve, atau reject.

---

### 3. Ketua Tim

Ketua Tim adalah role pengelola pekerjaan tim.

Hak akses utama Ketua Tim:

- Melihat dashboard Ketua.
- Mengelola RK Ketua miliknya sendiri.
- Membuat Project dari RK Ketua.
- Menambahkan anggota ke Project.
- Membuat RK Anggota untuk anggota project.
- Melihat RK Anggota dari project yang dipimpin.
- Melakukan review IKI anggota.
- Approve atau reject IKI.
- Melihat Daily Task sesuai hak akses.
- Mengelola IKI pribadi jika Ketua juga menjadi anggota pada project lain melalui mode pribadi.

Ketua Tim bertanggung jawab memastikan pekerjaan anggota berjalan sesuai RK Ketua dan Project yang dibuat.

---

### 4. Anggota

Anggota adalah role pelaksana pekerjaan.

Hak akses utama Anggota:

- Melihat dashboard Anggota.
- Melihat project yang diikuti.
- Melihat RK Anggota miliknya.
- Membuat atau mengelola IKI miliknya.
- Membuat Daily Task.
- Mengunggah bukti pekerjaan.
- Submit IKI untuk direview Ketua Tim.
- Melakukan revisi jika IKI dikembalikan oleh Ketua Tim.

Anggota berfokus pada pelaksanaan pekerjaan dan pelaporan bukti aktivitas harian.

---

## Flow Utama Sistem

Alur utama sistem:

```text
Start
↓
User Login
↓
Sistem Mengecek Role
↓
User Masuk Dashboard Sesuai Role
```

### Flow Admin

```text
Admin Login
↓
Dashboard Admin
↓
Kelola User dan Role
↓
Kelola Tim Kerja
↓
Import / Kelola IKU
↓
Kelola RK Ketua
↓
Kelola Project
↓
Monitoring Seluruh Progress
```

### Flow Kepala

```text
Kepala Login
↓
Dashboard Kepala
↓
Monitoring IKU
↓
Monitoring RK Ketua
↓
Monitoring Project
↓
Monitoring RK Anggota
↓
Monitoring IKI dan Daily Task
↓
Melihat Rekap Progress
```

### Flow Ketua Tim

```text
Ketua Login
↓
Dashboard Ketua
↓
Pilih IKU / RK Ketua
↓
Buat Project
↓
Tambah Anggota Project
↓
Buat RK Anggota
↓
RK Anggota Masuk ke Akun Anggota
↓
Menunggu Anggota Submit IKI
↓
Review IKI
↓
Valid?
    ↓ Ya
    Approve IKI
    ↓
    Progress Diperbarui

    ↓ Tidak
    Reject / Kembalikan ke Anggota
    ↓
    Anggota Revisi
```

### Flow Anggota

```text
Anggota Login
↓
Dashboard Anggota
↓
Lihat Project / RK Anggota
↓
Buat atau Update IKI
↓
Input Daily Task
↓
Upload Bukti Pekerjaan
↓
Submit IKI
↓
Menunggu Review Ketua
↓
Jika Ditolak: Revisi
↓
Jika Disetujui: Progress Naik
```

### Flow Progress

```text
IKI Approved
↓
Update Progress RK Anggota
↓
Update Progress Project
↓
Update Progress RK Ketua
↓
Update Monitoring IKU
↓
Update Dashboard Anggota
↓
Update Dashboard Ketua
↓
Update Dashboard Kepala
↓
Update Dashboard Admin
```

---

## Modul dan Halaman Utama

### Authentication

Halaman authentication meliputi:

- Login.
- Forgot password.
- Reset password.
- Force password change.
- Logout.

Register publik dinonaktifkan karena sistem bersifat internal. User dibuat oleh Admin melalui halaman manajemen user.

---

### Dashboard

Dashboard tersedia sesuai role:

- `/admin`
- `/kepala`
- `/ketua`
- `/anggota`

Setiap dashboard menampilkan informasi yang relevan dengan hak akses masing-masing role.

---

### User Management

Digunakan oleh Admin untuk mengelola akun pengguna.

Fitur utama:

- Melihat daftar user.
- Menambah user.
- Mengubah data user.
- Menghapus user.
- Reset password user.
- Import user.

---

### Team Management

Digunakan untuk mengelola tim kerja.

Fitur utama:

- Membuat tim.
- Menentukan ketua tim.
- Mengubah data tim.
- Menghapus tim.
- Melihat relasi tim dengan user.

---

### IKU

IKU adalah level utama kinerja.

Fitur utama:

- Menampilkan daftar IKU.
- Menambah IKU.
- Mengubah IKU.
- Menghapus IKU.
- Import IKU dari Excel.
- Search IKU.
- Monitoring IKU.

IKU menjadi dasar pembuatan RK Ketua.

---

### RK Ketua

RK Ketua adalah rencana kinerja milik Ketua Tim yang diturunkan dari IKU.

Fitur utama:

- Menampilkan RK Ketua.
- Membuat RK Ketua.
- Mengubah RK Ketua.
- Menghapus RK Ketua jika belum memiliki project.
- Melihat detail RK Ketua.
- Search RK Ketua.
- Monitoring progress RK Ketua.
- Template picker RK Ketua dari data Excel yang sudah diimport.

RK Ketua menjadi dasar pembuatan Project.

---

### Template RK Ketua

Sistem mendukung template RK Ketua untuk mempercepat pengisian rencana kinerja.

Fitur utama:

- Import template RK Ketua dari Excel.
- Menyimpan template ke database.
- Menampilkan template picker di form tambah RK Ketua.
- User tetap dapat mengetik manual walaupun template tersedia.

Template ini tidak membuat RK Ketua otomatis. Template hanya membantu mengisi deskripsi RK Ketua.

---

### Project

Project dibuat berdasarkan RK Ketua.

Fitur utama:

- Menampilkan daftar project.
- Membuat project.
- Mengubah project.
- Menghapus project.
- Melihat detail project.
- Menambahkan anggota project.
- Melihat progress project.
- Export project.
- Search project.

Project menjadi wadah kerja bagi anggota dan dasar pembuatan RK Anggota.

---

### RK Anggota

RK Anggota adalah rencana kerja anggota dalam suatu Project.

Fitur utama:

- Menampilkan RK Anggota.
- Membuat RK Anggota.
- Mengubah RK Anggota.
- Menghapus RK Anggota sesuai hak akses.
- Melihat detail RK Anggota.
- Legacy route submit/approve/reject masih dipertahankan agar flow lama tidak rusak.
- Progress RK Anggota dihitung dari IKI.

Approval utama tidak lagi berada di RK Anggota, tetapi pada level IKI.

---

### IKI

IKI adalah unit approval utama dalam sistem.

Fitur utama:

- Menampilkan daftar IKI.
- Membuat IKI.
- Mengubah IKI.
- Menghapus IKI sesuai hak akses.
- Submit IKI.
- Review IKI oleh Ketua.
- Approve IKI.
- Reject IKI.
- Melihat status IKI.
- Menampilkan bukti dan Daily Task terkait.

Status IKI digunakan untuk menghitung progress RK Anggota, Project, dan RK Ketua.

---

### Daily Task

Daily Task adalah catatan aktivitas harian yang mendukung IKI.

Fitur utama:

- Menampilkan Daily Task.
- Membuat Daily Task.
- Mengubah Daily Task.
- Menghapus Daily Task sesuai hak akses.
- Menghubungkan Daily Task ke IKI.
- Upload bukti pekerjaan.
- Melihat aktivitas harian anggota.

Daily Task tidak langsung menjadi approval utama, tetapi menjadi bukti dan pendukung IKI.

---

### Notification

Sistem memiliki fitur notifikasi untuk membantu pengguna mengetahui perubahan status.

Fitur utama:

- Melihat daftar notifikasi.
- Melihat jumlah notifikasi belum dibaca.
- Tandai satu notifikasi sebagai dibaca.
- Tandai semua notifikasi sebagai dibaca.

Notifikasi digunakan untuk mendukung proses submit, review, approve, dan reject.

---

### Calendar Events

Sistem menyediakan endpoint calendar events untuk menampilkan agenda atau event terkait dashboard.

---

## Struktur Route Utama

Contoh struktur route:

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
/admin/stats

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
```

---

## Keamanan Sistem

Beberapa prinsip keamanan yang digunakan:

- Sistem menggunakan authentication Laravel.
- Route utama dilindungi middleware `auth`.
- Setiap role memiliki middleware role masing-masing.
- Kepala hanya memiliki akses monitoring/read-only.
- Register publik dinonaktifkan.
- User dibuat oleh Admin.
- Forgot password diberi rate limit.
- Login diberi rate limit.
- Route search dan endpoint tertentu diberi pembatasan akses.
- Logout menggunakan method POST.
- Validasi request dilakukan pada controller.
- Pembatasan ownership dilakukan pada controller sesuai role.
- Force password change tersedia untuk user dengan password sementara/default.
- CSRF protection aktif melalui Laravel.
- Blade menggunakan escaping default `{{ }}` untuk mengurangi risiko XSS.

---

## Teknologi yang Digunakan

Project ini menggunakan:

- Laravel
- PHP
- MySQL
- Blade Template
- Tailwind CSS
- Vite
- Laravel Breeze
- Maatwebsite Excel
- JavaScript
- Composer
- NPM

---

## Persyaratan Sistem

Disarankan menggunakan environment berikut:

- PHP 8.2 atau lebih baru
- Composer
- Node.js dan NPM
- MySQL/MariaDB
- Laravel 11/12
- Web server lokal seperti Laravel Herd, Laragon, XAMPP, atau Valet

---

## Instalasi Project

Clone repository:

```bash
git clone https://github.com/username/nama-repository.git
cd nama-repository
```

Install dependency PHP:

```bash
composer install
```

Install dependency frontend:

```bash
npm install
```

Copy file environment:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Atur konfigurasi database di `.env`:

```env
DB_DATABASE=nama_database
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migration:

```bash
php artisan migrate
```

Jalankan seeder jika tersedia:

```bash
php artisan db:seed
```

Buat symbolic link storage:

```bash
php artisan storage:link
```

Build frontend:

```bash
npm run build
```

Jalankan server lokal:

```bash
php artisan serve
```

Akses aplikasi:

```text
http://127.0.0.1:8000
```

---

## Perintah Development

Menjalankan Laravel server:

```bash
php artisan serve
```

Menjalankan Vite development:

```bash
npm run dev
```

Membersihkan cache:

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

Melihat route:

```bash
php artisan route:list
```

---

## Catatan Akun

Sistem ini bersifat internal. Register publik dimatikan. Akun dibuat oleh Admin melalui menu Users atau melalui fitur import user.

Jika tersedia seeder user, gunakan akun default dari seeder project. Jika belum tersedia, buat user admin melalui seeder, tinker, atau import user.

---

## Rekomendasi Production

Sebelum deploy ke server production, pastikan:

```env
APP_ENV=production
APP_DEBUG=false
```

Checklist production:

- Gunakan HTTPS.
- Gunakan password database yang kuat.
- Jangan commit file `.env`.
- Jangan commit file upload/storage pribadi.
- Jalankan `php artisan config:cache`.
- Jalankan `php artisan route:cache` jika route sudah stabil.
- Pastikan permission folder `storage` dan `bootstrap/cache` benar.
- Pastikan register publik tetap nonaktif.
- Pastikan backup database berjalan rutin.
- Pastikan upload file memiliki validasi ukuran dan tipe file.
- Pastikan akses storage publik hanya untuk file yang memang boleh dilihat user.
- Pastikan setiap controller membatasi ownership data sesuai role.

---

## Status Project

Project ini sudah berada pada tahap selesai secara fungsional untuk kebutuhan monitoring kinerja berbasis role.

Fitur utama yang sudah tersedia:

- Authentication dan role-based access.
- Dashboard per role.
- Manajemen user.
- Manajemen tim kerja.
- Import dan kelola IKU.
- RK Ketua.
- Template picker RK Ketua.
- Project.
- RK Anggota.
- IKI sebagai approval utama.
- Daily Task sebagai bukti kerja.
- Monitoring progress bertingkat.
- Notification.
- Mode monitoring Kepala.
- Register publik dinonaktifkan.
- Basic route security hardening.

---

## Ringkasan Alur Data

```text
Admin membuat/menyiapkan user, tim, dan IKU
↓
Admin/Ketua membuat RK Ketua dari IKU
↓
Ketua membuat Project dari RK Ketua
↓
Ketua menambahkan anggota project
↓
Ketua membuat RK Anggota
↓
Anggota membuat IKI dan Daily Task
↓
Anggota submit IKI
↓
Ketua review IKI
↓
Jika ditolak, anggota revisi
↓
Jika disetujui, progress sistem diperbarui
↓
Kepala dan Admin melakukan monitoring
```

---

## Lisensi

Project ini dibuat untuk kebutuhan internal pengembangan sistem monitoring kinerja BPS Kota Palangka Raya.

Sesuaikan bagian lisensi ini dengan kebijakan repository atau instansi masing-masing.
