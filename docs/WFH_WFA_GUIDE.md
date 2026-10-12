# Panduan Sistem WFH & WFA (Work From Home & Work From Anywhere)

Buku panduan ini menjelaskan cara kerja, konfigurasi, dan penggunaan fitur absensi jarak jauh (WFH/WFA) pada sistem HRIS.

---

## 📋 Ikhtisar Sistem
Sistem ini memungkinkan pegawai untuk melakukan absensi di luar kantor dengan validasi berbasis GPS. Ada tiga mode kerja utama:
1.  **WFO (Work From Office)**: Absensi wajib dilakukan di dalam radius kantor yang ditentukan.
2.  **WFH (Work From Home)**: Absensi dilakukan di rumah pegawai dengan radius toleransi tertentu.
3.  **WFA (Work From Anywhere)**: Absensi dapat dilakukan di mana saja (biasanya untuk dinas luar atau tugas lapangan) hanya dengan verifikasi koordinat GPS.

---

## ⚙️ Konfigurasi Admin

### 1. Mengatur Radius WFH
Admin dapat menentukan seberapa jauh pegawai boleh berada dari titik pusat rumah mereka saat melakukan absensi WFH.
*   Buka menu **Pengaturan HRIS > Kehadiran**.
*   Cari kolom **Radius Toleransi WFH**.
*   Input jarak dalam satuan meter (contoh: `100` untuk 100 meter).
*   Klik **Simpan**.

### 2. Mengelola Jadwal Pegawai
Fitur WFH/WFA hanya aktif jika pegawai memiliki jadwal untuk mode tersebut pada hari yang bersangkutan.
*   Buka menu **Kepegawaian > Jadwal WFH/WFA**.
*   Admin dapat menambahkan jadwal harian untuk pegawai tertentu.
*   Pilih Pegawai, Tanggal, dan Mode Kerja (**WFH** atau **WFA**).
*   Pastikan status jadwal adalah **Approved** agar bisa digunakan untuk absensi.

### 3. Monitoring Lokasi Rumah Pegawai
Admin dapat memantau titik koordinat rumah yang didaftarkan pegawai.
*   Buka menu **Kepegawaian > Lokasi WFH Pegawai**.
*   Di sini Admin bisa melakukan **Reset Lokasi** jika pegawai perlu mengubah titik rumah mereka (karena pindah rumah, dsb).

---

## 📱 Panduan Pegawai (Portal)

### 1. Mendaftarkan Lokasi Rumah (Wajib untuk WFH)
Sebelum bisa melakukan absensi WFH, pegawai harus mendaftarkan koordinat rumahnya.
*   Login ke **Portal Pegawai**.
*   Buka menu **Pengaturan Lokasi**.
*   Pastikan Anda sedang berada di rumah saat melakukan ini.
*   Sistem akan mendeteksi lokasi Anda saat ini. Klik **Simpan Lokasi WFH**.
*   **Catatan**: Pendaftaran hanya bisa dilakukan satu kali secara mandiri. Perubahan selanjutnya harus menghubungi Admin HR.

### 2. Cara Melakukan Absensi
*   Buka menu **Absensi** di Portal.
*   Sistem akan otomatis mendeteksi jadwal Anda hari ini.
*   Jika jadwal Anda **WFH**: Klik **Clock In**. Sistem akan memvalidasi posisi GPS Anda terhadap lokasi rumah yang terdaftar.
*   Jika jadwal Anda **WFA**: Klik **Clock In**. Sistem akan merekam lokasi Anda tanpa validasi radius.
*   Jika tidak ada jadwal khusus, sistem akan meminta Anda melakukan absensi **WFO** (di radius kantor).

---

## 🔍 Logika Validasi Teknis
Sistem menggunakan algoritma **Haversine** untuk menghitung jarak antara dua titik koordinat bumi.

*   **Validasi WFH**: 
    `Jarak (GPS Saat Ini, Lokasi Rumah) <= Radius Perusahaan`
*   **Validasi WFO**: 
    `Jarak (GPS Saat Ini, Lokasi Kantor Terdekat) <= Radius Kantor`
*   **Validasi WFA**: 
    `Selalu Valid` (Hanya mencatat data koordinat).

---

## 🛠️ Referensi Teknis (Untuk Developer)

Bagian ini merangkum perubahan struktur data dan kode yang diimplementasikan untuk mendukung fitur ini.

### 1. Database Migrations
Terdapat 4 migrasi utama yang ditambahkan:
*   `employee_schedules.php`: Membuat tabel `employee_schedules` untuk menyimpan jadwal kerja (WFO/WFH/WFA) dan status persetujuan.
*   `users_location.php`: Membuat tabel `user_locations` untuk menyimpan koordinat rumah pegawai.
*   `add_work_mode_to_attendances_table.php`: Menambahkan kolom `work_mode` pada tabel `attendances` untuk mencatat mode kerja saat absensi.
*   `add_wfh_radius_to_companies_table.php`: Menambahkan kolom `wfh_radius` pada tabel `companies` untuk pengaturan radius global perusahaan.

### 2. Model & Logika Inti
*   **[UserLocation.php](app/Models/UserLocation.php)**: Implementasi rumus **Haversine** dalam metode `distanceTo()` dan `isWithinRadius()` untuk validasi jarak berbasis meter.
*   **[EmployeeSchedule.php](app/Models/EmployeeSchedule.php)**: Relasi ke User dan penentuan status aktif jadwal.

### 3. Controller & Validasi Absensi
Perubahan logika utama terjadi pada proses **Clock In**:
*   **[EmployeePortal/AttendanceController.php](app/Http/Controllers/EmployeePortal/AttendanceController.php)**:
    *   Mengambil jadwal aktif hari ini melalui `EmployeeSchedule`.
    *   Jika mode `WFH`, memvalidasi lokasi terhadap `UserLocation` milik pegawai.
    *   Jika mode `WFO`, memvalidasi lokasi terhadap `OfficeLocation` yang ditugaskan.
*   **[EmployeePortal/UserLocationController.php](app/Http/Controllers/EmployeePortal/UserLocationController.php)**: Menangani pendaftaran koordinat rumah satu kali oleh pegawai.

---

> [!IMPORTANT]
> Pastikan fitur GPS/Lokasi pada perangkat atau browser dalam keadaan aktif dan diberikan izin akses (Allow Location) saat menggunakan fitur ini.
