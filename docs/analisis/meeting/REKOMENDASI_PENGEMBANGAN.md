# Rekomendasi Pengembangan JagoGaji

Berdasarkan hasil meeting dengan HR Expert (Benny Astria), berikut rekomendasi pengembangan:

---

## A. FITUR YANG HARUS DITAMBAHKAN (CRITICAL)

### 1. Export Payroll untuk Bank Transfer
**Prioritas: CRITICAL**

Saat ini setelah payroll disetujui, belum ada cara untuk export data transfer ke bank.

**Requirement:**
- Export CSV/Excel dengan format:
  - Nama Pemilik Rekening
  - Nomor Rekening
  - Nama Bank
  - Kode Bank (untuk transfer antar bank)
  - Nominal (Gaji bersih)
- Support filter per bank (jika perusahaan punya karyawan multi-bank)
- Format sesuai dengan template BCA Bisnis/Mandiri Cash Management

**Lokasi:** `payrolls/{payroll}/export-bank`

### 2. Laporan BPJS untuk Rekonsiliasi
**Prioritas: CRITICAL**

HR membutuhkan laporan untuk mencocokkan dengan tagihan dari BPJS.

**Requirement:**
- Laporan BPJS Kesehatan per bulan:
  - Nama karyawan
  - Gaji (basis perhitungan)
  - Iuran Karyawan (1%)
  - Iuran Perusahaan (4%)
  - Total per karyawan
  - Grand Total

- Laporan BPJS Ketenagakerjaan per bulan:
  - JHT (Karyawan + Perusahaan)
  - JKK (Perusahaan)
  - JKM (Perusahaan)
  - JP (Karyawan + Perusahaan)
  - JKP (Perusahaan)
  - Total per jenis
  - Grand Total

**Lokasi:** `reports/bpjs-kesehatan`, `reports/bpjs-ketenagakerjaan`

### 3. Tracking Pulang Cepat (Early Leave)
**Prioritas: HIGH**

Selain terlambat, HR juga perlu track karyawan yang pulang lebih awal.

**Requirement:**
- Tambah field `is_early_leave` di attendances
- Tambah field `early_leave_minutes` untuk durasi
- Update laporan kehadiran dengan statistik pulang cepat
- Include di dashboard kehadiran

---

## B. FITUR YANG PERLU DIREVIEW/UPDATE

### 1. Cut-off Period Flexibility
Pastikan sistem bisa handle berbagai skenario cut-off:
- Tanggal 1 s/d akhir bulan (recommended untuk course)
- Tanggal 16 s/d 15 (beberapa perusahaan)
- Tanggal 21 s/d 20 (seperti Holland Bakery)

Cek apakah field `start_date` dan `end_date` di payrolls sudah flexible.

### 2. Setting Hari Kerja
Perlu setting per perusahaan:
- 5 hari kerja = 22 hari/bulan (untuk perhitungan pro-rata)
- 6 hari kerja = 25 hari/bulan

Ini mempengaruhi:
- Upah harian (Gaji / hari kerja)
- Pro-rata gaji karyawan baru
- Pembayaran cuti tidak terpakai

### 3. Tanggungan Keluarga BPJS
Review apakah sudah ada field untuk:
- Pasangan (cover otomatis)
- Jumlah anak (max 3 cover otomatis)
- Tanggungan tambahan (1% per orang)

### 4. Rate TER 2026
Pastikan rate tarif efektif rata-rata (TER) sudah sesuai regulasi 2026.
Ada kategori baru di tahun 2026.

---

## C. FITUR YANG SUDAH BAGUS (NILAI PLUS)

1. **Pinjaman Karyawan** - Kompetitor tidak semua punya
2. **Face Recognition** - Anti titip absen
3. **GPS Tracking** - Memastikan lokasi absen benar
4. **UI/UX Modern** - Lebih baik dari Axiopro & Sigma
5. **THR Multi-Agama** - Support Lebaran, Natal, dll
6. **Approval Workflow** - Flexible dan berjenjang
7. **Mobile API** - Sudah lengkap

---

## D. FITUR YANG BISA DITUNDA (BATCH TERPISAH)

### 1. KPI/Performance (Sudah Ada, Bisa Jadi Batch Sendiri)
Menurut Benny, ini scope besar sendiri. Axiopro jual terpisah ~250 juta hanya untuk fitur ini.

### 2. Recruitment
Proses penerimaan karyawan dari awal (posting lowongan, screening, interview, offering).

### 3. Training & Development
Record pelatihan karyawan.

### 4. Industrial Relationship
- Penyimpanan kontrak digital
- Tracking SP (Surat Peringatan)
- Proses PHK

### 5. Organisation Chart Generator
Dari database karyawan, generate org chart otomatis.

---

## E. REKOMENDASI STRUKTUR COURSE

### Batch 1: Database + Payroll Basic (Harga: ~600-700rb)
**Fitur yang dicover:**
- Master karyawan
- Departemen & Jabatan
- Gaji pokok & komponen
- BPJS Kesehatan
- BPJS Ketenagakerjaan
- PPh 21 basic
- Proses payroll sederhana
- Slip gaji

### Batch 2: Kehadiran + Cuti + Lembur (Harga: ~600-700rb)
**Fitur yang dicover:**
- Work schedule
- Clock in/out
- Face recognition
- GPS tracking
- Cuti & approval
- Lembur & perhitungan
- Integrasi ke payroll

### Batch 3: Advanced Payroll (Harga: TBD)
**Fitur yang dicover:**
- THR
- Pro-rata gaji
- Multi-bank transfer
- Laporan BPJS
- Laporan pajak untuk e-SPT
- Pinjaman karyawan
- Reimbursement

### Batch 4: KPI & Performance (Harga: TBD - Premium)
**Fitur yang dicover:**
- Period performance
- KPI setup
- Performance review
- Rating & feedback
- Laporan performance

---

## F. TIMELINE REKOMENDASI

### Sebelum Launch Course:
1. ✅ Export Bank Transfer (CRITICAL)
2. ✅ Tracking Pulang Cepat
3. ✅ Review cut-off flexibility
4. ✅ Update rate TER 2026

### Setelah Launch (Improvement):
1. Laporan BPJS
2. Dashboard alert mangkir
3. Organisation chart
4. Integrasi e-SPT

---

## G. CATATAN PENTING DARI MEETING

1. **Jangan undervalue** - Kompetitor harga 200-250 juta, course 600rb sudah sangat murah
2. **Focus ke fundamental** - Database + Payroll adalah core, yang lain bisa batch terpisah
3. **UI/UX adalah nilai jual** - JagoGaji lebih bagus dari Axiopro & Sigma
4. **Gajian tanggal 1 untuk course** - Lebih mudah kalkulasi dan dipahami
5. **HR selalu butuh Excel** - Semua laporan harus bisa export Excel
