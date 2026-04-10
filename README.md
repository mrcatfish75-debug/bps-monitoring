# 📊 BPS Monitoring System (Laravel)

Sistem ini adalah aplikasi web untuk monitoring kinerja pegawai di Badan Pusat Statistik (BPS), dibangun menggunakan Laravel (Backend) dan Blade + Tailwind (Frontend).

---

## 🎯 Tujuan Sistem

Aplikasi ini bertujuan untuk:

- Memantau kinerja pegawai secara terstruktur
- Mengelola pekerjaan berdasarkan hierarki yang jelas
- Menyediakan transparansi progres kerja
- Memudahkan evaluasi kinerja

---

## 🧠 Struktur Sistem (Hierarki)

IKU (Indikator Kinerja Utama)  
→ RK Ketua (Target utama)  
→ Project  
→ RK Anggota  
→ Daily Task  

---

## 👥 Role User

### 1. Kepala BPS
- Read-only
- Monitoring seluruh aktivitas
- Tidak bisa edit data

### 2. Admin
- Full CRUD semua data
- Mengelola user, IKU, tim, project

### 3. Ketua Tim
- Membuat RK Ketua
- Membuat project
- Mengatur anggota tim

### 4. Anggota
- Membuat RK pribadi
- Mengisi tugas harian

---

## 🏗️ Fitur yang Sudah Dibangun

### 🔐 Authentication
- Login
- Register
- Logout
- Role-based redirect

### 👤 Role System
- Admin
- Kepala BPS
- Ketua Tim
- Anggota

### 📊 IKU Management
- Create IKU
- List IKU

### 👥 Team Management
- Create Team
- Assign Ketua Tim
- Assign Anggota

### 📁 Project Management
- Create Project
- Relasi ke RK Ketua (ongoing refactor)
- Assign anggota project

---

## ⚙️ Tech Stack

- Laravel 12
- Blade Template
- Tailwind CSS
- MySQL
- Laravel Breeze (Auth)

---

## 🧩 Database Design (Core)

### Tables:
- users
- teams
- team_members
- ikus
- rk_ketuas (in progress)
- projects
- project_members

---

## 🚧 Progress Saat Ini

✔ Auth system selesai  
✔ Role system selesai  
✔ Dashboard per role selesai  
✔ IKU system selesai  
✔ Team system selesai  
✔ Project system selesai  
🔄 RK Ketua (sedang refactor struktur)  
⏳ RK Anggota & Daily Task (belum)

---

## 🚀 Next Development

- RK Ketua (finalisasi struktur)
- RK Anggota
- Daily Task
- Progress tracking
- Dashboard analytics

---

## ⚠️ Catatan

Project ini masih dalam tahap development dan akan terus dikembangkan untuk menjadi sistem monitoring kinerja yang lengkap.

---

## 👨‍💻 Developer

Created by:  
**mrcatfish75-debug**

---

## 📌 Cara Menjalankan Project

```bash
git clone https://github.com/mrcatfish75-debug/bps-monitoring.git
cd bps-monitoring
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve