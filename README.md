Siap bro. Ini versi README yang sudah jauh lebih lengkap, rapi, dan sesuai kondisi project terakhir: flow Admin, Ketua Tim, Anggota, Daily Task, RK Anggota, Project membership fleksibel, Ketua bisa jadi anggota di project/tim lain, dan Kepala BPS masih jadi next development.

Silakan replace isi `README.md` kamu dengan ini.

````md
# 📊 BPS Monitoring System

BPS Monitoring System adalah aplikasi web berbasis Laravel untuk memantau, mengelola, dan mengevaluasi progres kinerja pegawai di lingkungan Badan Pusat Statistik (BPS). Sistem ini dibangun dengan alur kerja berbasis hierarki kinerja, mulai dari IKU, RK Ketua, Project, RK Anggota, hingga Daily Task.

Aplikasi ini menggunakan Laravel sebagai backend, Blade sebagai templating engine, Tailwind CSS untuk antarmuka, dan MySQL sebagai database utama.

---

## 🎯 Tujuan Sistem

Sistem ini dibuat untuk membantu proses monitoring kinerja pegawai secara lebih terstruktur, transparan, dan mudah dievaluasi.

Tujuan utama aplikasi:

- Mengelola pekerjaan berdasarkan struktur kinerja yang jelas.
- Memetakan IKU ke RK Ketua, Project, RK Anggota, dan Daily Task.
- Memudahkan Ketua Tim dalam membuat project dan memonitor pekerjaan anggota.
- Memudahkan Anggota dalam membuat RK pribadi dan mencatat Daily Task.
- Memberikan dashboard informatif untuk setiap role.
- Menyediakan dasar monitoring untuk Kepala BPS sebagai viewer/read-only.
- Meningkatkan transparansi progres kerja antar tim dan pegawai.

---

## 🧠 Struktur Hierarki Sistem

Flow utama sistem:

```text
IKU
→ RK Ketua
→ Project
→ RK Anggota
→ Daily Task
````

Penjelasan:

1. **IKU** adalah Indikator Kinerja Utama yang menjadi target besar organisasi.
2. **RK Ketua** adalah rencana kerja Ketua Tim yang mengacu pada IKU tertentu.
3. **Project** adalah pekerjaan turunan dari RK Ketua yang dikelola oleh Ketua Tim.
4. **RK Anggota** adalah rencana kerja pribadi anggota/pelaksana dalam sebuah project.
5. **Daily Task** adalah catatan aktivitas harian untuk menyelesaikan RK Anggota.

---

## 👥 Role User

Sistem memiliki beberapa role utama:

```text
Admin
Kepala BPS
Ketua Tim
Anggota
```

---

## 🔐 Role & Hak Akses

### 1. Admin

Admin memiliki akses penuh untuk mengelola data utama sistem.

Admin dapat:

* Mengelola user.
* Mengelola team.
* Mengelola IKU.
* Mengelola RK Ketua.
* Mengelola project.
* Mengelola RK Anggota.
* Mengelola Daily Task.
* Melihat seluruh data lintas role.
* Melakukan import data user dan IKU.
* Melihat dashboard global sistem.

Admin digunakan sebagai pengelola utama sistem.

---

### 2. Kepala BPS

Kepala BPS dirancang sebagai role monitoring/read-only.

Kepala BPS dapat:

* Melihat ringkasan seluruh aktivitas.
* Melihat progres project.
* Melihat progres kinerja berdasarkan IKU.
* Melihat dashboard monitoring.
* Melihat data tanpa melakukan perubahan.

Kepala BPS tidak boleh:

* Membuat data.
* Mengedit data.
* Menghapus data.
* Approve/reject RK.

Status saat ini: **laman Kepala BPS menjadi fokus development berikutnya**.

---

### 3. Ketua Tim

Ketua Tim memiliki dua konteks kerja:

```text
1. Sebagai Ketua/Reviewer
2. Sebagai Anggota/Pelaksana di project lain
```

Ini penting karena dalam sistem ini, user dengan role `ketua` tetap bisa menjadi anggota/pelaksana pada project lain.

#### Ketua Tim sebagai Ketua/Reviewer

Ketua Tim dapat:

* Membuat RK Ketua.
* Mengedit RK Ketua miliknya.
* Melihat detail RK Ketua beserta daftar project terkait.
* Membuat project dari RK Ketua miliknya.
* Mengatur anggota project.
* Mengedit/menghapus project yang dia pimpin.
* Melihat RK Anggota dari project yang dia pimpin.
* Approve/reject RK Anggota dari project yang dia pimpin.
* Memonitor Daily Task anggota pada project yang dia pimpin.

Ketua Tim tidak boleh:

* Mengedit project yang bukan dia pimpin.
* Menghapus project yang bukan dia pimpin.
* Approve/reject RK miliknya sendiri.
* Mengelola Daily Task milik anggota secara langsung.
* Melihat project yang tidak dia pimpin dan tidak dia ikuti.

#### Ketua Tim sebagai Pelaksana

Ketua Tim juga bisa menjadi anggota project lain.

Dalam konteks ini, Ketua Tim dapat:

* Melihat project yang dia ikuti sebagai anggota.
* Membuat RK pribadi.
* Membuat Daily Task pribadi.
* Submit RK pribadi untuk direview Ketua Tim project tersebut.

Mode ini diakses melalui:

```text
/ketua/rk-anggota?mode=mine
/ketua/daily-task?mode=mine
```

---

### 4. Anggota

Anggota adalah pelaksana pekerjaan dalam project.

Anggota dapat:

* Melihat dashboard pribadi.
* Melihat project yang dia ikuti.
* Membuat RK pribadi pada project yang dia ikuti.
* Membuat lebih dari satu RK dalam satu project.
* Membuat Daily Task untuk RK miliknya sendiri.
* Submit RK setelah memiliki minimal satu Daily Task.
* Melihat status RK: Draft, Submitted, Approved, Rejected.
* Mengedit RK dan Daily Task selama status RK masih Draft atau Rejected.

Anggota tidak boleh:

* Melihat project yang tidak dia ikuti.
* Melihat RK milik user lain.
* Melihat Daily Task milik user lain.
* Membuat project.
* Mengedit project.
* Menghapus project.
* Approve/reject RK.
* Mengedit RK setelah Submitted atau Approved.
* Mengedit Daily Task setelah RK Submitted atau Approved.

Flow kerja anggota:

```text
Project Saya
→ RK Pribadi Saya
→ Daily Task Saya
→ Submit RK
→ Review Ketua Tim
→ Approved / Rejected
```

---

## 🏗️ Fitur yang Sudah Dibangun

### 🔐 Authentication

Fitur auth menggunakan Laravel Breeze.

Fitur tersedia:

* Login.
* Register.
* Logout.
* Role-based redirect.
* Middleware role.
* Redirect dashboard berdasarkan role.

Role redirect:

```text
admin   → /admin
ketua   → /ketua
anggota → /anggota
kepala  → /kepala
```

---

## 📊 Dashboard

### Dashboard Admin

Dashboard Admin menampilkan ringkasan global sistem.

Informasi yang ditampilkan:

* Total user.
* Total team.
* Total IKU.
* Total project.
* Total Daily Task.
* Average progress project.
* Recent Daily Task.
* Statistik project per bulan.
* Statistik task per bulan.

---

### Dashboard Ketua Tim

Dashboard Ketua Tim sudah mendukung dua konteks:

```text
1. Mode Ketua/Reviewer
2. Pekerjaan Saya
```

Informasi sebagai Ketua/Reviewer:

* Total project yang dipimpin.
* Total RK Ketua.
* Total RK Anggota dari project yang dipimpin.
* Total Daily Task anggota.
* Progress rata-rata project.
* RK Anggota yang menunggu review.
* Daily Task terbaru dari project yang dipimpin.

Informasi sebagai Pelaksana:

* Project yang dia ikuti sebagai anggota.
* RK pribadi miliknya.
* Daily Task pribadi.
* Status RK pribadi: Draft, Submitted, Approved, Rejected.

---

### Dashboard Anggota

Dashboard Anggota sudah dibuat informatif dan sesuai flow anggota.

Informasi yang ditampilkan:

* Total Project Saya.
* Total RK Pribadi.
* Total Daily Task.
* RK yang perlu Daily Task.
* Progress pribadi.
* Status RK: Draft, Submitted, Approved, Rejected.
* Project terbaru yang diikuti.
* RK pribadi terbaru.
* Daily Task terbaru.
* Quick action ke Project Saya, RK Pribadi Saya, dan Daily Task Saya.

Dashboard Anggota hanya menampilkan data milik user login.

---

### Dashboard Kepala BPS

Dashboard Kepala BPS saat ini sudah tersedia secara dasar, tetapi masih menjadi fokus pengembangan berikutnya.

Rencana pengembangan Dashboard Kepala BPS:

* Monitoring seluruh IKU.
* Monitoring progress project.
* Monitoring kinerja per team.
* Monitoring RK Ketua.
* Monitoring RK Anggota.
* Monitoring Daily Task.
* View-only detail project.
* Grafik progress dan statistik kinerja.
* Filter tahun, IKU, team, dan status.

---

## 🎯 IKU Management

IKU adalah target utama yang menjadi dasar RK Ketua.

Fitur IKU:

* List IKU.
* Create IKU.
* Edit IKU.
* Delete IKU.
* Import IKU.
* Filter berdasarkan tahun.
* Search IKU.
* Relasi dengan RK Ketua.

Struktur umum IKU:

```text
IKU
- id
- name
- year
- target/value fields
```

---

## 👥 Team Management

Team digunakan untuk mengelompokkan Ketua Tim dan konteks IKU/tim kerja.

Fitur Team:

* List team.
* Create team.
* Edit team.
* Delete team.
* Assign Ketua Tim.
* View detail team.
* Melihat IKU/RK Ketua/project terkait team.

Catatan penting:

Jumlah anggota team tidak lagi menjadi sumber utama dalam project. Membership project sekarang fleksibel berdasarkan tabel `project_members`.

Dengan kata lain:

```text
Team = struktur unit kerja / konteks tim
Project Members = anggota aktual yang mengerjakan project
```

Ketua Tim dapat menjadi anggota di project/tim lain jika dimasukkan ke `project_members`.

---

## 📌 RK Ketua

RK Ketua adalah rencana kerja milik Ketua Tim yang diturunkan dari IKU.

Fitur RK Ketua:

* List RK Ketua.
* Create RK Ketua.
* Edit RK Ketua.
* Delete RK Ketua.
* View detail RK Ketua dalam modal.
* Melihat IKU terkait.
* Melihat team terkait.
* Melihat daftar project dari RK Ketua.
* Melihat progress rata-rata project.
* Search/filter RK Ketua.

Aturan akses RK Ketua:

Admin:

* Bisa melihat dan mengelola seluruh RK Ketua.

Ketua Tim:

* Hanya bisa mengelola RK Ketua miliknya sendiri.

View RK Ketua sekarang tidak langsung redirect ke laman project, tetapi membuka modal detail yang menampilkan:

* IKU.
* Team.
* Ketua.
* Deskripsi RK Ketua.
* Jumlah project.
* Progress rata-rata.
* Daftar project terkait.
* Tombol menuju laman project.

---

## 📁 Project Management

Project adalah turunan dari RK Ketua.

Fitur Project:

* List project.
* Create project.
* Edit project.
* Delete project.
* View detail project.
* Assign anggota project.
* Search/filter project dengan AJAX.
* Filter berdasarkan tahun.
* Filter berdasarkan team.
* Filter berdasarkan RK Ketua.
* Search instan tanpa tombol filter.
* Progress project.
* Export project untuk admin.

Aturan akses Project:

Admin:

* Bisa mengelola semua project.

Ketua Tim:

* Bisa membuat project dari RK Ketua miliknya.
* Bisa mengedit/menghapus project yang dia pimpin.
* Bisa melihat project yang dia pimpin.
* Bisa melihat project yang dia ikuti sebagai anggota.

Anggota:

* Hanya bisa melihat project yang dia ikuti.
* Tidak bisa create/edit/delete project.

Project membership memakai tabel:

```text
project_members
```

Bukan lagi hanya berdasarkan `team_members`.

Hal ini memungkinkan:

* Anggota dari role `anggota` masuk project.
* User role `ketua` juga bisa menjadi anggota/pelaksana di project lain.
* Anggota project berbeda-beda untuk setiap project.
* Satu team bisa memiliki banyak project dengan anggota berbeda.

---

## 📝 RK Anggota / RK Pribadi

RK Anggota adalah rencana kerja pribadi user dalam sebuah project.

Fitur RK Anggota:

* List RK Anggota.
* Create RK Anggota.
* Edit RK Anggota.
* Delete RK Anggota.
* View detail RK Anggota.
* Submit RK.
* Approve RK.
* Reject RK.
* Catatan penolakan.
* Search/filter RK Anggota.
* AJAX instant filter tanpa reload halaman.
* Pagination AJAX.
* Progress berdasarkan Daily Task.
* Menampilkan Daily Task pendukung.

Aturan RK Anggota:

Admin:

* Bisa melihat semua RK Anggota.
* Bisa membuat/mengedit/menghapus RK Anggota.
* Bisa approve/reject jika dibutuhkan.

Ketua mode reviewer:

* Bisa melihat RK Anggota dari project yang dia pimpin.
* Bisa approve/reject RK Anggota dari project yang dia pimpin.
* Tidak boleh approve/reject RK miliknya sendiri.

Ketua mode mine:

* Bisa membuat RK pribadi.
* Bisa mengedit/menghapus RK pribadi selama Draft/Rejected.
* Bisa submit RK pribadi setelah punya Daily Task.

Anggota:

* Bisa membuat RK pribadi.
* Bisa membuat lebih dari satu RK dalam project yang sama.
* Bisa mengedit/menghapus RK selama Draft/Rejected.
* Bisa submit RK setelah minimal punya satu Daily Task.
* Tidak bisa approve/reject.

Status RK Anggota:

```text
draft
submitted
approved
rejected
```

Flow status:

```text
Draft
→ Submitted
→ Approved

Draft
→ Submitted
→ Rejected
→ Draft/Rejected dapat diperbaiki
→ Submitted ulang
```

Aturan penting:

* RK Draft bisa diedit.
* RK Rejected bisa diedit.
* RK Submitted tidak bisa diedit.
* RK Approved tidak bisa diedit.
* RK hanya bisa submit jika sudah memiliki minimal satu Daily Task.
* RK Rejected dapat diperbaiki dan disubmit ulang.

---

## ✅ Daily Task

Daily Task adalah catatan progres/aktivitas harian untuk menyelesaikan RK Anggota.

Fitur Daily Task:

* List Daily Task.
* Create Daily Task.
* Edit Daily Task.
* Delete Daily Task.
* View detail Daily Task.
* Link bukti kerja.
* Filter tanggal.
* Search.
* Modal create/edit/view.
* Read-only setelah RK Submitted/Approved.

Field utama Daily Task:

* RK Anggota.
* Tanggal pelaksanaan.
* Aktivitas.
* Link bukti kerja.
* Created at.
* Updated at.

Catatan:

Field `output` masih dipertahankan sebagai legacy compatibility, tetapi UI utama sekarang fokus pada:

```text
activity
evidence_url
date
```

Aturan Daily Task:

Admin:

* Bisa mengelola Daily Task.

Anggota:

* Hanya bisa melihat dan mengelola Daily Task miliknya sendiri.
* Daily Task hanya bisa dibuat untuk RK miliknya.
* Daily Task hanya bisa dibuat jika RK masih Draft atau Rejected.
* Daily Task hanya bisa diedit/dihapus jika RK masih Draft atau Rejected.

Ketua:

* Dalam mode reviewer, Ketua hanya monitoring Daily Task anggota dari project yang dia pimpin.
* Dalam mode mine, Ketua bisa membuat Daily Task pribadi untuk RK miliknya sendiri.

Aturan tanggal:

```text
Tanggal Daily Task tidak boleh sebelum hari ini.
Tanggal boleh hari ini atau setelahnya.
```

Approval Daily Task sudah tidak digunakan. Approval dilakukan pada level RK Anggota.

---

## 🔎 Search & Filter

Beberapa halaman sudah menggunakan pencarian/filter instan.

### Project

Filter Project:

* Tahun.
* Team.
* RK Ketua.
* Keyword search.

Search Project sudah menggunakan AJAX instan:

* Tidak perlu klik tombol Filter.
* Data tabel langsung berubah.
* View/Edit/Delete tetap mengikuti role permission.
* Pagination lama disembunyikan saat hasil AJAX tampil.

### RK Anggota

Filter RK Anggota:

* Project.
* User/Anggota untuk admin dan ketua reviewer.
* Keyword search.

Search RK Anggota menggunakan AJAX instan:

* Tidak perlu klik tombol Filter.
* Mengambil ulang HTML halaman yang sama.
* Replace tbody dan pagination.
* Tetap memakai permission dari controller.
* Mode `mine` tetap aman.

### Daily Task

Filter Daily Task:

* Search aktivitas/RK/project/user/link bukti.
* Start date.
* End date.

Daily Task masih menggunakan filter GET biasa, tetapi sudah mendukung pencarian dan filter tanggal.

---

## 🔔 Notification

Sistem sudah memiliki menu notification.

Fitur yang tersedia:

* List notification.
* Unread count.
* Mark as read.
* Mark all as read.
* Badge unread notification di topbar/sidebar.

---

## 🧭 Layout & UI/UX

UI menggunakan:

* Blade.
* Tailwind CSS.
* Modal-based CRUD.
* AJAX fetch untuk detail modal.
* Vue 3 CDN untuk drawer/sidebar interaktif.
* Lucide Icons untuk icon menu.
* Responsive layout.

Sidebar terbaru:

* Tidak selalu tampil permanen.
* Muncul sebagai drawer/popup setelah klik tombol menu.
* Bisa ditutup dengan tombol X.
* Bisa ditutup dengan overlay.
* Bisa ditutup dengan tombol menu toggle.
* Menu anggota sudah menampilkan Project Saya.
* Icon menu menggunakan Lucide, bukan emoji.

Menu anggota:

```text
Dashboard
Project Saya
RK Pribadi Saya
Daily Task Saya
Notifications
```

Menu Ketua Tim:

```text
Dashboard

Mode Ketua Tim:
- RK Ketua
- Project Tim
- Review RK Anggota
- Monitoring Daily Task

Pekerjaan Saya:
- RK Pribadi Saya
- Daily Task Saya

Notifications
```

---

## ⚙️ Tech Stack

Backend:

* Laravel 12
* PHP 8+
* MySQL
* Eloquent ORM
* Laravel Middleware
* Laravel Breeze Authentication

Frontend:

* Blade Template
* Tailwind CSS
* JavaScript Fetch API
* Vue 3 CDN
* Lucide Icons CDN
* Vite

Tools:

* Composer
* NPM
* Laravel Artisan

---

## 🧩 Core Database Design

Tabel utama:

```text
users
teams
team_members
ikus
rk_ketuas
projects
project_members
rk_anggotas
daily_tasks
notifications
```

---

## 🗃️ Ringkasan Relasi Database

### User

User memiliki role:

```text
admin
kepala
ketua
anggota
```

Relasi penting:

```text
User hasMany RkKetua
User hasMany RkAnggota
User belongsToMany Project melalui project_members
```

---

### Team

Team adalah struktur unit kerja.

Relasi:

```text
Team belongsTo User sebagai leader
Team hasMany RkKetua
Team hasMany Project
Team belongsToMany User melalui team_members
```

Catatan:

`team_members` bukan sumber utama anggota project. Anggota project ditentukan oleh `project_members`.

---

### IKU

Relasi:

```text
IKU hasMany RkKetua
```

---

### RK Ketua

Relasi:

```text
RkKetua belongsTo IKU
RkKetua belongsTo Team
RkKetua belongsTo User sebagai ketua
RkKetua hasMany Project
```

---

### Project

Relasi:

```text
Project belongsTo Team
Project belongsTo User sebagai leader
Project belongsTo RkKetua
Project belongsToMany User sebagai members melalui project_members
Project hasMany RkAnggota
```

---

### RK Anggota

Relasi:

```text
RkAnggota belongsTo Project
RkAnggota belongsTo User
RkAnggota hasMany DailyTask
RkAnggota belongsTo User sebagai approver
```

---

### Daily Task

Relasi:

```text
DailyTask belongsTo RkAnggota
```

---

## 🔐 Security & Authorization Rules

Sistem menggunakan kombinasi:

* Middleware auth.
* Middleware role.
* Scope query berdasarkan role.
* Authorization manual di controller.
* Ownership check.
* Project membership check.
* Project leader check.
* Status lock.

Contoh aturan penting:

* Anggota hanya melihat project yang dia ikuti.
* Anggota hanya melihat RK miliknya sendiri.
* Anggota hanya melihat Daily Task miliknya sendiri.
* Ketua hanya review RK Anggota dari project yang dia pimpin.
* Ketua tidak boleh approve RK miliknya sendiri.
* Ketua bisa menjadi anggota project lain.
* Daily Task tidak bisa diubah setelah RK Submitted/Approved.
* RK tidak bisa diubah setelah Submitted/Approved.
* Project hanya bisa diedit oleh admin atau ketua yang memimpin project tersebut.

---

## 🚧 Progress Saat Ini

Status development saat ini:

```text
✅ Authentication
✅ Role-based redirect
✅ Role middleware
✅ Dashboard Admin
✅ Dashboard Ketua Tim
✅ Dashboard Anggota
✅ IKU Management
✅ Team Management
✅ RK Ketua Management
✅ Project Management
✅ Project Member Assignment
✅ RK Anggota Management
✅ RK Anggota Approval/Reject
✅ Daily Task Management
✅ Notification System
✅ AJAX Project Filter
✅ AJAX RK Anggota Filter
✅ Responsive Sidebar Drawer
🔄 Dashboard Kepala BPS / Monitoring Kepala
```

---

## 🚀 Next Development

Fokus development berikutnya:

### 1. Laman Kepala BPS

Target:

* Dashboard monitoring global.
* Monitoring IKU.
* Monitoring Team.
* Monitoring Project.
* Monitoring RK Ketua.
* Monitoring RK Anggota.
* Monitoring Daily Task.
* Grafik progress.
* Filter tahun.
* Filter team.
* Filter IKU.
* View detail read-only.

### 2. Analytics & Reporting

Rencana fitur:

* Grafik progres per IKU.
* Grafik progres per team.
* Grafik jumlah project per bulan.
* Grafik Daily Task per bulan.
* Export laporan.
* Summary performa pegawai.

### 3. UI/UX Final Polish

Rencana:

* Konsistensi semua modal.
* Konsistensi empty state.
* Konsistensi AJAX search.
* Loading state lebih halus.
* Notification UX.
* Mobile responsiveness.

### 4. Hardening

Rencana:

* Policy/Gate Laravel.
* Form Request validation.
* Unit/feature tests.
* Seeders role demo.
* Audit log aktivitas.
* Better error handling.

---

## 📌 Important Business Rules

Beberapa aturan bisnis utama:

1. Ketua Tim bisa menjadi anggota/pelaksana di project lain.
2. Role `ketua` tidak selalu berarti reviewer dalam semua konteks.
3. Reviewer ditentukan oleh `projects.leader_id`.
4. Pelaksana project ditentukan oleh `project_members`.
5. Team hanya struktur kerja, bukan sumber tunggal anggota project.
6. Anggota project bersifat fleksibel per project.
7. Satu anggota bisa memiliki lebih dari satu RK Anggota dalam satu project.
8. RK Anggota bisa memiliki banyak Daily Task.
9. Daily Task adalah bukti proses kerja.
10. Approval dilakukan di level RK Anggota, bukan Daily Task.
11. RK hanya bisa submit setelah memiliki minimal satu Daily Task.
12. Daily Task hanya bisa dibuat saat RK Draft/Rejected.
13. Data Submitted/Approved bersifat read-only untuk pelaksana.
14. Kepala BPS adalah read-only monitoring role.

---

## 📌 Cara Menjalankan Project

Clone repository:

```bash
git clone https://github.com/mrcatfish75-debug/bps-monitoring.git
cd bps-monitoring
```

Install dependency PHP:

```bash
composer install
```

Install dependency frontend:

```bash
npm install
```

Copy environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Atur konfigurasi database di `.env`:

```env
DB_DATABASE=bps_monitoring
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migration:

```bash
php artisan migrate
```

Opsional jika tersedia seeder:

```bash
php artisan db:seed
```

Jalankan Vite:

```bash
npm run dev
```

Jalankan Laravel server:

```bash
php artisan serve
```

Akses aplikasi:

```text
http://127.0.0.1:8000
```

---

## 🧹 Useful Artisan Commands

Clear cache:

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

Cek route:

```bash
php artisan route:list
```

Cek route tertentu:

```bash
php artisan route:list | findstr "project"
php artisan route:list | findstr "rk-anggota"
php artisan route:list | findstr "daily-task"
```

---

## 📂 Struktur Folder Penting

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php
│   │   ├── IkuController.php
│   │   ├── TeamController.php
│   │   ├── ProjectController.php
│   │   ├── RkKetuaController.php
│   │   ├── RkAnggotaController.php
│   │   ├── DailyTaskController.php
│   │   └── NotificationController.php
│   └── Middleware/
│       └── RoleMiddleware.php
│
├── Models/
│   ├── User.php
│   ├── Team.php
│   ├── Iku.php
│   ├── Project.php
│   ├── RkKetua.php
│   ├── RkAnggota.php
│   ├── DailyTask.php
│   └── Notification.php
│
resources/
├── views/
│   ├── dashboard/
│   │   ├── admin.blade.php
│   │   ├── ketua.blade.php
│   │   ├── anggota.blade.php
│   │   └── kepala.blade.php
│   ├── iku/
│   ├── team/
│   ├── project/
│   ├── rk_ketua/
│   ├── rk_anggota/
│   ├── daily_task/
│   ├── notification/
│   └── layouts/
│       └── app.blade.php
│
routes/
└── web.php
```

---

## 🧪 Recommended Testing Flow

### Test Admin

```text
Login admin
→ Kelola user
→ Kelola team
→ Kelola IKU
→ Buat RK Ketua
→ Buat project
→ Assign anggota project
→ Cek RK Anggota
→ Cek Daily Task
```

### Test Ketua Tim

```text
Login ketua
→ Buat RK Ketua
→ Buat project
→ Pilih anggota project
→ Review RK Anggota submitted
→ Approve/reject RK
→ Monitoring Daily Task
```

### Test Ketua sebagai Anggota

```text
Login ketua
→ Masuk RK Pribadi Saya
→ Buat RK pribadi dari project yang dia ikuti
→ Buat Daily Task Saya
→ Submit RK
```

### Test Anggota

```text
Login anggota
→ Buka Project Saya
→ View project
→ Buat RK Pribadi Saya
→ Buat Daily Task Saya
→ Submit RK
→ Tunggu review Ketua Tim
```

### Test Kepala BPS

```text
Login kepala
→ Buka dashboard kepala
→ Monitoring data read-only
```

---

## ⚠️ Development Notes

Project ini masih dalam tahap development aktif.

Fokus utama saat ini adalah menyelesaikan role Kepala BPS agar dapat menjadi dashboard monitoring read-only yang lengkap.

Beberapa bagian yang masih dapat dikembangkan:

* Policy/Gate Laravel.
* Seeder user demo.
* Export laporan.
* Dashboard Kepala BPS.
* Testing otomatis.
* Audit log.
* Notifikasi yang lebih detail.
* Grafik analytics yang lebih lengkap.

---

## 👨‍💻 Developer

Created by:

**mrcatfish75-debug**

Repository:

```text
https://github.com/mrcatfish75-debug/bps-monitoring
```

---

## 📄 License

Project ini dibuat untuk kebutuhan pengembangan sistem monitoring kinerja BPS. Sesuaikan lisensi penggunaan berdasarkan kebutuhan organisasi.

````