````markdown
# 📊 BPS Monitoring System

BPS Monitoring System adalah aplikasi web berbasis Laravel untuk monitoring kinerja pegawai di lingkungan Badan Pusat Statistik (BPS). Sistem ini dibangun untuk membantu proses perencanaan, pelaksanaan, pemantauan, dan evaluasi kinerja pegawai secara lebih terstruktur, transparan, dan berbasis hierarki pekerjaan.

Aplikasi ini menggunakan Laravel sebagai backend, Blade sebagai templating engine, Tailwind CSS untuk antarmuka, dan MySQL sebagai database utama.

---

## 🎯 Tujuan Sistem

Sistem ini bertujuan untuk:

- Memantau kinerja pegawai secara terstruktur.
- Mengelola pekerjaan berdasarkan hierarki organisasi dan target kerja.
- Menyediakan transparansi progres pekerjaan.
- Memisahkan antara aktivitas harian dan progress final pekerjaan.
- Membantu ketua tim melakukan review terhadap hasil kerja anggota.
- Membantu admin mengelola data master, user, tim, IKU, RK Ketua, project, RK Anggota, dan Daily Task.
- Membantu kepala BPS melakukan monitoring secara read-only terhadap progres kinerja.

---

## 🧠 Konsep Utama Sistem

Sistem ini menggunakan pendekatan hierarki kerja sebagai berikut:

```text
IKU
→ RK Ketua
→ Project
→ RK Anggota
→ Daily Task
````

Penjelasan tiap level:

### 1. IKU — Indikator Kinerja Utama

IKU adalah indikator utama yang menjadi dasar pengukuran kinerja. IKU berada di level paling atas dan menjadi acuan target kinerja tahunan.

### 2. RK Ketua

RK Ketua adalah rencana kerja atau target kerja utama yang dibuat atau dimiliki oleh ketua tim. RK Ketua terhubung ke IKU tertentu.

### 3. Project

Project adalah turunan dari RK Ketua. Project menjadi wadah pelaksanaan pekerjaan yang melibatkan anggota tim.

### 4. RK Anggota

RK Anggota adalah unit pekerjaan utama milik anggota. RK Anggota adalah objek yang akan di-submit oleh anggota dan di-review oleh ketua.

### 5. Daily Task

Daily Task adalah catatan aktivitas harian anggota. Daily Task digunakan sebagai bukti proses kerja, bukan sebagai penentu progress final.

---

## 🔥 Prinsip Progress Final

Sistem ini menggunakan prinsip penting:

```text
Progress tidak dihitung dari jumlah Daily Task.
Progress dihitung dari RK Anggota yang sudah di-approve.
```

Artinya:

```text
Daily Task = bukti proses / monitoring harian
RK Anggota = unit kerja yang dinilai
Approval Ketua = penentu progress
```

Contoh:

```text
Project memiliki 4 RK Anggota.
2 RK Anggota sudah approved.

Progress Project = 2 / 4 × 100 = 50%
```

Daily Task tidak menaikkan progress secara langsung. Daily Task hanya membantu ketua melihat proses kerja sebelum menyetujui atau menolak RK Anggota.

---

## 🔄 Flow Kerja Utama

### Flow Anggota

```text
Anggota login
→ melihat RK Anggota miliknya
→ mengisi Daily Task harian
→ jika pekerjaan dianggap selesai, anggota submit RK Anggota
→ status RK Anggota berubah menjadi submitted
→ menunggu review ketua
```

### Flow Ketua

```text
Ketua login
→ melihat RK Anggota dari project yang dia pimpin
→ membuka detail RK Anggota
→ melihat Daily Task sebagai bukti proses
→ approve atau reject RK Anggota
```

### Flow Sistem

```text
Jika RK Anggota approved:
→ RK Anggota dianggap selesai
→ progress RK Anggota menjadi 100%
→ progress Project ikut naik
→ progress RK Ketua ikut naik
→ progress IKU ikut naik
```

Jika RK Anggota rejected:

```text
→ anggota dapat memperbaiki pekerjaan
→ Daily Task dapat ditambah atau diedit kembali
→ anggota submit ulang
→ ketua review ulang
```

---

## 👥 Role User

Sistem memiliki beberapa role utama.

### 1. Admin

Admin memiliki akses paling luas untuk mengelola data sistem.

Hak akses admin:

* Mengelola user.
* Mengelola tim.
* Mengelola IKU.
* Mengelola RK Ketua.
* Mengelola project.
* Mengelola RK Anggota.
* Mengelola Daily Task.
* Melakukan testing submit, approve, dan reject jika route admin diaktifkan.
* Melihat seluruh data sistem.

Admin bertanggung jawab terhadap data master dan konfigurasi awal.

---

### 2. Kepala BPS

Kepala BPS bersifat monitoring/read-only.

Hak akses kepala:

* Melihat dashboard monitoring.
* Melihat progres kinerja.
* Tidak melakukan edit data operasional.
* Tidak melakukan approval RK Anggota.
* Tidak mengelola Daily Task.

Role ini dirancang untuk kebutuhan pemantauan kinerja secara umum.

---

### 3. Ketua

Ketua adalah user yang memimpin project tertentu.

Hak akses ketua:

* Melihat project yang dipimpinnya.
* Melihat RK Anggota di bawah project yang dipimpinnya.
* Melihat Daily Task anggota sebagai bukti proses.
* Approve RK Anggota jika statusnya submitted.
* Reject RK Anggota dengan catatan penolakan.
* Tidak mengedit atau menghapus RK Anggota.
* Tidak approve atau reject Daily Task satu per satu.

Ketua melakukan approval pada level RK Anggota, bukan pada level Daily Task.

---

### 4. Anggota

Anggota adalah user pelaksana pekerjaan.

Hak akses anggota:

* Melihat RK Anggota miliknya sendiri.
* Mengisi Daily Task untuk RK Anggota miliknya.
* Submit RK Anggota jika sudah memiliki minimal satu Daily Task.
* Melihat status approval RK Anggota.
* Melakukan revisi jika RK Anggota ditolak.
* Tidak approve pekerjaan sendiri.
* Tidak melihat RK Anggota milik anggota lain.

---

## 🧩 Status RK Anggota

RK Anggota memiliki empat status utama:

```text
draft
submitted
approved
rejected
```

### draft

Status awal saat RK Anggota dibuat.

Pada status ini:

* Anggota dapat menambahkan Daily Task.
* Anggota dapat submit RK Anggota.
* Ketua hanya dapat melihat.

### submitted

Status setelah anggota submit RK Anggota.

Pada status ini:

* Daily Task menjadi read-only.
* RK Anggota tidak bisa diedit oleh anggota.
* Ketua dapat approve atau reject.
* Progress masih 0%.

### approved

Status ketika ketua menyetujui RK Anggota.

Pada status ini:

* RK Anggota dianggap selesai.
* Progress RK Anggota menjadi 100%.
* Progress Project, RK Ketua, dan IKU ikut naik.
* Daily Task tetap read-only.

### rejected

Status ketika ketua menolak RK Anggota.

Pada status ini:

* Ketua wajib memberi catatan penolakan.
* Anggota dapat melakukan revisi.
* Daily Task dapat ditambah atau diedit kembali.
* Anggota dapat submit ulang.

---

## 📌 Daily Task Rules

Daily Task digunakan sebagai bukti proses kerja harian.

### Daily Task boleh dibuat jika:

```text
RK Anggota masih draft atau rejected.
```

### Daily Task tidak boleh diedit jika:

```text
RK Anggota sudah submitted atau approved.
```

### Daily Task tidak menentukan progress.

Progress tidak dihitung dari:

```text
jumlah Daily Task selesai / total Daily Task
```

Progress dihitung dari:

```text
jumlah RK Anggota approved / total RK Anggota
```

### Ketua tidak approve Daily Task

Sistem sebelumnya memiliki konsep approval Daily Task, tetapi flow terbaru memindahkan approval final ke RK Anggota.

Daily Task sekarang berfungsi sebagai:

* Catatan aktivitas.
* Bukti proses.
* Evidence kerja.
* Bahan review ketua.

---

## 🏗️ Fitur yang Sudah Dibangun

### 🔐 Authentication

* Login.
* Register.
* Logout.
* Role-based redirect.
* Middleware role-based access.

### 👤 User & Role System

Role yang digunakan:

```text
admin
kepala
ketua
anggota
```

Catatan: role `ketua_tim` sudah diseragamkan menjadi `ketua`.

### 📊 Dashboard

Dashboard per role sudah tersedia:

* Dashboard admin.
* Dashboard kepala.
* Dashboard ketua.
* Dashboard anggota.

### 📈 IKU Management

Fitur IKU:

* List IKU.
* Create/update IKU.
* Relasi IKU ke RK Ketua.
* Progress IKU berdasarkan RK Anggota approved.

### 👥 Team Management

Fitur team:

* Create team.
* Assign anggota.
* Relasi user dengan team.
* Relasi team dengan RK Ketua dan project.

### 🧑‍💼 RK Ketua Management

Fitur RK Ketua:

* Create RK Ketua.
* Relasi RK Ketua ke IKU.
* Relasi RK Ketua ke team.
* Relasi RK Ketua ke user ketua.
* Progress RK Ketua berdasarkan RK Anggota approved.

### 📁 Project Management

Fitur project:

* Create project.
* Relasi project ke RK Ketua.
* Relasi project ke team.
* Relasi project ke leader/ketua.
* Assign anggota project.
* Progress project berdasarkan RK Anggota approved.

Catatan penting:

```text
leader_id project harus berasal dari user role ketua.
```

Project tidak boleh otomatis memakai `auth()->id()` jika admin yang membuat project, karena itu akan menyebabkan leader project menjadi admin.

### 🧾 RK Anggota Management

Fitur RK Anggota:

* Admin dapat membuat RK Anggota.
* RK Anggota terhubung ke project.
* RK Anggota terhubung ke user anggota.
* RK Anggota memiliki status approval.
* Anggota dapat melihat RK miliknya.
* Anggota dapat submit RK.
* Ketua dapat melihat RK dari project yang dia pimpin.
* Ketua dapat approve/reject RK Anggota.
* Progress RK Anggota menjadi 100% jika approved.

### 🗓️ Daily Task Management

Fitur Daily Task:

* Admin dapat melihat semua Daily Task.
* Admin dapat menambah/edit/delete Daily Task.
* Anggota dapat melihat Daily Task miliknya.
* Anggota dapat menambah Daily Task untuk RK miliknya.
* Ketua dapat melihat Daily Task dari project yang dia pimpin.
* Ketua hanya read-only pada Daily Task.
* Daily Task menjadi read-only setelah RK Anggota submitted/approved.

### 🔔 Notification & Activity Log

Struktur awal sudah tersedia untuk:

* Notification model.
* Activity log model.
* Helper function.
* Tracking aktivitas seperti approve/reject task atau RK.

Fitur ini masih dapat dikembangkan lebih lanjut untuk notifikasi approval RK Anggota.

---

## 🧮 Progress Calculation

### RK Anggota Progress

```text
approved = 100%
selain approved = 0%
```

### Project Progress

```text
jumlah RK Anggota approved / total RK Anggota dalam project × 100
```

### RK Ketua Progress

```text
jumlah RK Anggota approved di semua project milik RK Ketua
/
total RK Anggota di semua project milik RK Ketua
× 100
```

### IKU Progress

```text
jumlah RK Anggota approved di bawah semua RK Ketua pada IKU
/
total RK Anggota di bawah semua RK Ketua pada IKU
× 100
```

---

## 🧱 Database Design

Core tables:

```text
users
teams
team_members
team_user
ikus
rk_ketuas
projects
project_members
rk_anggotas
daily_tasks
activity_logs
notifications
cache
jobs
sessions
migrations
```

### Tabel penting

#### users

Menyimpan data user dan role.

#### teams

Menyimpan data tim.

#### ikus

Menyimpan indikator kinerja utama.

#### rk_ketuas

Menyimpan RK Ketua yang terhubung ke IKU, team, dan user ketua.

#### projects

Menyimpan project yang terhubung ke RK Ketua, team, leader, dan anggota.

#### rk_anggotas

Menyimpan RK Anggota.

Field approval penting:

```text
status
submitted_at
approved_at
approved_by
final_evidence
rejection_note
```

#### daily_tasks

Menyimpan aktivitas harian anggota sebagai bukti proses.

---

## ⚙️ Tech Stack

* Laravel 12
* PHP
* Blade Template
* Tailwind CSS
* MySQL
* Laravel Breeze
* Composer
* NPM / Vite
* Git & GitHub

---

## 📂 Struktur Folder Penting

```text
app/Http/Controllers
app/Models
app/Http/Middleware
database/migrations
database/seeders
resources/views
resources/views/rk_anggota
resources/views/daily_task
resources/views/rk_ketua
resources/views/project
resources/views/iku
routes/web.php
routes/api.php
```

---

## 🚧 Progress Saat Ini

### Sudah selesai

```text
✔ Authentication
✔ Role system
✔ Role middleware
✔ Dashboard per role
✔ IKU management
✔ Team management
✔ RK Ketua management
✔ Project management
✔ RK Anggota management
✔ Daily Task management
✔ RK Anggota approval flow
✔ Daily Task role-based access
✔ Progress berbasis RK Anggota approved
✔ Role ketua sudah diseragamkan
✔ Route admin/anggota/ketua
✔ View RK Anggota role-aware
✔ View Daily Task role-aware
```

### Flow utama yang sudah berjalan

```text
Anggota membuat Daily Task
→ anggota submit RK Anggota
→ ketua melihat RK Anggota
→ ketua melihat Daily Task sebagai bukti proses
→ ketua approve/reject RK Anggota
→ progress naik jika approved
```

---

## ⚠️ Catatan Teknis Penting

### 1. Project leader harus user role ketua

Bug yang pernah terjadi:

```text
Project dibuat oleh admin
→ leader_id tersimpan sebagai admin
→ ketua tidak bisa melihat RK Anggota project tersebut
```

Solusi:

```text
leader_id project harus mengikuti user_id dari RK Ketua,
bukan auth()->id().
```

Di `ProjectController`, hindari:

```php
'leader_id' => auth()->id()
```

Gunakan:

```php
'leader_id' => $rk->user_id
```

### 2. Jangan gunakan Daily Task sebagai dasar progress

Daily Task hanya untuk monitoring.

Progress harus tetap dihitung dari RK Anggota yang approved.

### 3. Route name masih perlu dirapikan

Saat ini beberapa route resource dipakai di prefix berbeda:

```text
/admin/rk-anggota
/anggota/rk-anggota
/ketua/rk-anggota
```

Beberapa route name masih sama seperti:

```text
rk-anggota.index
daily-task.index
```

Ke depan sebaiknya dibuat lebih eksplisit:

```php
->names('admin.rk-anggota')
->names('anggota.rk-anggota')
->names('ketua.rk-anggota')
```

Hal yang sama juga berlaku untuk Daily Task.

### 4. Sidebar perlu dibuat role-based

Saat ini menu sidebar masih perlu dirapikan agar setiap role hanya melihat menu yang relevan.

Rekomendasi:

```text
Admin:
Dashboard, Users, Team, IKU, RK Ketua, Project, RK Anggota, Daily Task

Kepala:
Dashboard, Monitoring IKU, Monitoring Project, Monitoring Progress

Ketua:
Dashboard, Project, RK Anggota, Daily Task

Anggota:
Dashboard, RK Anggota, Daily Task
```

---

## 🚀 Next Development

### Prioritas 1 — Finalisasi ProjectController

Pastikan saat admin membuat project:

```text
leader_id = user ketua dari RK Ketua
```

Bukan:

```text
leader_id = admin yang sedang login
```

Hal yang perlu dicek:

* `ProjectController@store`
* `ProjectController@update`
* `ProjectController@show`
* `ProjectController@getMembers`

### Prioritas 2 — Rapikan Route Names

Refactor route agar tidak bentrok.

Contoh:

```php
Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('rk-anggota', RkAnggotaController::class);
});

Route::prefix('anggota')->name('anggota.')->group(function () {
    Route::resource('rk-anggota', RkAnggotaController::class)->only(['index', 'show']);
});

Route::prefix('ketua')->name('ketua.')->group(function () {
    Route::resource('rk-anggota', RkAnggotaController::class)->only(['index', 'show']);
});
```

### Prioritas 3 — Sidebar Role-Based

Update navigation agar setiap role hanya melihat menu sesuai wewenangnya.

### Prioritas 4 — Dashboard Analytics

Tambahkan ringkasan:

* Total IKU.
* Total RK Ketua.
* Total Project.
* Total RK Anggota.
* Total Daily Task.
* RK submitted.
* RK approved.
* RK rejected.
* Project progress.
* IKU progress.

### Prioritas 5 — Notification System

Kembangkan notifikasi untuk:

* Anggota submit RK.
* Ketua approve RK.
* Ketua reject RK.
* Anggota melakukan revisi.
* Admin membuat RK baru.

### Prioritas 6 — Activity Log

Catat aktivitas penting:

```text
create RK Anggota
submit RK Anggota
approve RK Anggota
reject RK Anggota
create Daily Task
update Daily Task
delete Daily Task
```

### Prioritas 7 — Export Report

Tambahkan export:

* Export IKU progress.
* Export project progress.
* Export RK Anggota status.
* Export Daily Task evidence.

Format:

```text
Excel
PDF
```

### Prioritas 8 — Hardening Authorization

Pastikan semua controller memiliki guard server-side.

Contoh:

* Anggota tidak boleh melihat RK orang lain.
* Ketua tidak boleh edit RK Anggota.
* Ketua hanya bisa approve project yang dia pimpin.
* Admin dapat manage data master.
* Kepala hanya read-only.

### Prioritas 9 — Testing

Tambahkan test untuk:

* Role middleware.
* Daily Task ownership.
* RK submit validation.
* RK approve/reject.
* Progress calculation.
* Project leader assignment.

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

Untuk Windows PowerShell:

```powershell
copy .env.example .env
```

Generate app key:

```bash
php artisan key:generate
```

Atur database di `.env`:

```env
DB_DATABASE=bps_monitoring
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

Jalankan backend:

```bash
php artisan serve
```

Jalankan frontend:

```bash
npm run dev
```

Buka aplikasi:

```text
http://127.0.0.1:8000
```

---

## 🧪 Alur Testing Manual

### Test Daily Task dan RK Approval

1. Login sebagai admin.
2. Buat IKU.
3. Buat RK Ketua.
4. Buat Project.
5. Buat RK Anggota.
6. Login sebagai anggota.
7. Buat Daily Task untuk RK Anggota.
8. Submit RK Anggota.
9. Login sebagai ketua.
10. Buka RK Anggota.
11. Klik View untuk melihat Daily Task.
12. Approve atau Reject.
13. Pastikan progress berubah jika approved.

### Expected Result

```text
Draft → Submitted → Approved
Progress: 0% → 100%
```

Atau:

```text
Draft → Submitted → Rejected → Submitted → Approved
```

---

## 👨‍💻 Developer

Created by:

```text
mrcatfish75-debug
```

GitHub:

```text
https://github.com/mrcatfish75-debug/bps-monitoring
```

---

## 📌 Status Project

Project ini masih dalam tahap development aktif.

Fokus saat ini:

```text
menyempurnakan workflow approval RK Anggota,
memastikan progress akurat,
merapikan role-based navigation,
dan menyiapkan dashboard monitoring yang lebih informatif.
```

````

Setelah kamu paste ke `README.md`, jalankan:

```bash
git add README.md
git commit -m "Update README with current system progress and next steps"
git push
````
