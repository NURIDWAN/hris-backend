# Sesi 8: Demo Payroll End-to-End — Dari Kehadiran Sampai Slip Gaji Dibayar

> **Durasi**: 2-3 jam
> **Tanggal**: 23 April 2026
> **Prasyarat**: GajiPro running (`php artisan serve`), sudah ada data karyawan & demo seeder
> **Tujuan**: Mampu melakukan presentasi fitur payroll secara end-to-end — mulai dari setup settings, input kehadiran, proses payroll, adjustment, sampai paid & cetak slip gaji.

---

## Daftar Isi

1. [Persiapan Demo](#1-persiapan-demo)
2. [Step 1: Setup PPh 21 (Pajak)](#2-step-1-setup-pph-21-pajak)
3. [Step 2: Setup BPJS Ketenagakerjaan](#3-step-2-setup-bpjs-ketenagakerjaan)
4. [Step 3: Setup BPJS Kesehatan](#4-step-3-setup-bpjs-kesehatan)
5. [Step 4: Setup Payroll Settings](#5-step-4-setup-payroll-settings)
6. [Step 5: Setup Komponen Gaji](#6-step-5-setup-komponen-gaji)
7. [Step 6: Assign Gaji Karyawan](#7-step-6-assign-gaji-karyawan)
8. [Step 7: Input Data Kehadiran](#8-step-7-input-data-kehadiran)
9. [Step 8: Buat & Proses Payroll](#9-step-8-buat--proses-payroll)
10. [Step 9: Review Hasil Kalkulasi](#10-step-9-review-hasil-kalkulasi)
11. [Step 10: Adjustment Slip Gaji](#11-step-10-adjustment-slip-gaji)
12. [Step 11: Approve Payroll](#12-step-11-approve-payroll)
13. [Step 12: Tandai Dibayar & Export](#13-step-12-tandai-dibayar--export)
14. [Step 13: Cetak Slip Gaji](#14-step-13-cetak-slip-gaji)
15. [Ringkasan Alur Status](#15-ringkasan-alur-status)
16. [Troubleshooting](#16-troubleshooting)

---

## 1. Persiapan Demo

### Pastikan Aplikasi Berjalan

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite (untuk CSS/JS)
npm run dev
```

### Login sebagai Admin

Buka http://localhost:8000/login dan login dengan akun admin perusahaan demo.

### Cek Data Karyawan

Pastikan sudah ada minimal 2-3 karyawan aktif di menu **Karyawan**. Jika belum, buat dulu via menu Karyawan > Tambah Karyawan.

### Urutan Navigasi Demo

```
Settings → PPh 21 → BPJS TK → BPJS Kes → Payroll Settings
    ↓
Komponen Gaji → Gaji Karyawan
    ↓
Kehadiran (input absensi)
    ↓
Payroll → Buat → Proses → Review → Adjust → Approve → Bayar
    ↓
Slip Gaji (lihat, cetak PDF)
```

---

## 2. Step 1: Setup PPh 21 (Pajak)

### Navigasi
**Menu**: Pengaturan > PPh 21
**URL**: http://localhost:8000/pph21-settings

### Yang Perlu Dilakukan

1. **Aktifkan PPh 21** — toggle `is_active` ke aktif
2. **Pilih Metode Perhitungan**:
   - **TER (Tarif Efektif Rata-rata)** — Metode baru, lebih sederhana ✅ Rekomendasi
   - **Progressive** — Metode tradisional dengan 5 layer tarif
3. **Initialize Data**:
   - Klik **"Inisialisasi PTKP"** — mengisi tabel PTKP (Penghasilan Tidak Kena Pajak)
   - Klik **"Inisialisasi Tarif Progresif"** — mengisi 5 bracket tarif pajak
   - Klik **"Inisialisasi Tarif TER"** — mengisi 129 baris tarif TER (43 per kategori A/B/C)
4. **Setting Tambahan**:
   - `npwp_discount_rate`: 20% (penalti jika karyawan tidak punya NPWP)
   - `tax_burden`: `employee` (pajak ditanggung karyawan)

### Penjelasan untuk Presentasi

> "PPh 21 adalah pajak penghasilan yang dipotong dari gaji karyawan setiap bulan. Di GajiPro, kita support 2 metode:
> - **TER**: Cukup kalikan gross salary × tarif TER sesuai bracket. Simpel.
> - **Progressive**: Hitung PKP tahunan, terapkan 5 layer tarif (5%-35%), bagi 12.
>
> Tarif TER dibagi 3 kategori berdasarkan status PTKP karyawan:
> - **Kategori A**: TK/0, TK/1, K/0
> - **Kategori B**: TK/2, TK/3, K/1, K/2
> - **Kategori C**: K/3, K/I/0 s.d K/I/3"

### Data PTKP 2024

| Status | PTKP/Tahun | PTKP/Bulan |
|--------|-----------|------------|
| TK/0 | Rp 54.000.000 | Rp 4.500.000 |
| K/0 | Rp 58.500.000 | Rp 4.875.000 |
| K/1 | Rp 63.000.000 | Rp 5.250.000 |
| K/2 | Rp 67.500.000 | Rp 5.625.000 |
| K/3 | Rp 72.000.000 | Rp 6.000.000 |

### Tarif Progresif (UU HPP)

| PKP Tahunan | Tarif |
|-------------|-------|
| 0 - 60 Juta | 5% |
| 60 - 250 Juta | 15% |
| 250 - 500 Juta | 25% |
| 500 Juta - 5 Miliar | 30% |
| > 5 Miliar | 35% |

---

## 3. Step 2: Setup BPJS Ketenagakerjaan

### Navigasi
**Menu**: Pengaturan > BPJS Ketenagakerjaan
**URL**: http://localhost:8000/bpjs-tk-settings

### Yang Perlu Dilakukan

1. **Aktifkan BPJS TK**
2. **Set Rate** (standar nasional):

| Program | Company | Employee | Total |
|---------|---------|----------|-------|
| **JHT** (Jaminan Hari Tua) | 3,7% | 2% | 5,7% |
| **JP** (Jaminan Pensiun) | 2% | 1% | 3% |
| **JKK** (Jaminan Kecelakaan Kerja) | 0,24%* | - | 0,24% |
| **JKM** (Jaminan Kematian) | 0,3% | - | 0,3% |

> *JKK rate tergantung risiko pekerjaan. 0,24% untuk risiko sangat rendah (kantor).

3. **Klik "Inisialisasi Rate JKK"** — mengisi 5 level risiko JKK
4. **Set Batas Maksimum JP**: Rp 10.042.300 (ceiling gaji untuk JP)

### Penjelasan untuk Presentasi

> "BPJS Ketenagakerjaan ada 4 program. Yang dipotong dari gaji karyawan hanya JHT (2%) dan JP (1%). Sisanya ditanggung perusahaan. JKK rate-nya tergantung tingkat risiko industri."

---

## 4. Step 3: Setup BPJS Kesehatan

### Navigasi
**Menu**: Pengaturan > BPJS Kesehatan
**URL**: http://localhost:8000/bpjs-kes-settings

### Yang Perlu Dilakukan

1. **Aktifkan BPJS Kesehatan**
2. **Set Rate**:
   - Company rate: **4%**
   - Employee rate: **1%**
   - Total: **5%**
3. **Set Batas Gaji**:
   - Minimum: **Rp 2.900.000** (UMK DKI Jakarta)
   - Maksimum: **Rp 12.000.000** (ceiling)

### Penjelasan untuk Presentasi

> "BPJS Kesehatan total 5% dari gaji. Karyawan hanya bayar 1%, perusahaan 4%. Ada batas gaji minimum dan maksimum untuk basis perhitungan."

### Contoh Perhitungan

```
Gaji Rp 10.000.000:
- Employee: 10.000.000 × 1% = Rp 100.000 (dipotong dari gaji)
- Company: 10.000.000 × 4% = Rp 400.000 (ditanggung perusahaan)

Gaji Rp 15.000.000 (melebihi ceiling):
- Employee: 12.000.000 × 1% = Rp 120.000 (basis pakai ceiling)
- Company: 12.000.000 × 4% = Rp 480.000
```

---

## 5. Step 4: Setup Payroll Settings

### Navigasi
**Menu**: Pengaturan > Payroll
**URL**: http://localhost:8000/payroll-settings

### Yang Perlu Dilakukan

1. **Tipe Siklus**: `monthly` (bulanan)
2. **Hari Cutoff**: 1 (awal bulan)
3. **Hari Kerja Default**: 22 hari
4. **Auto Deduct**:
   - ✅ Auto potong keterlambatan (`auto_deduct_late`)
   - ✅ Auto potong absen (`auto_deduct_absent`)
5. **Setting Keterlambatan**:
   - Threshold: 3 hari (baru dipotong setelah telat 3x)
   - Tipe: `fixed` / `daily_rate` / `percentage`
   - Jumlah: Rp 50.000 (jika fixed)
6. **Setting Absen**:
   - Tipe: `daily_rate` (gaji harian × hari absen)
7. **Include**:
   - ✅ Include overtime
   - ✅ Include reimbursement

### Penjelasan untuk Presentasi

> "Payroll settings mengontrol bagaimana gaji dihitung. Kita bisa atur apakah keterlambatan dan absen otomatis dipotong, dan bagaimana cara menghitungnya."

---

## 6. Step 5: Setup Komponen Gaji

### Navigasi
**Menu**: Kompensasi > Komponen Gaji
**URL**: http://localhost:8000/salary-components

### Yang Perlu Dilakukan

Buat beberapa komponen gaji:

#### Komponen Pendapatan (Earning)

| Nama | Kode | Tipe | Kategori | Kena Pajak | Berbasis Kehadiran |
|------|------|------|----------|------------|-------------------|
| Tunjangan Jabatan | TJ-JABATAN | Earning | Fixed | Ya | Tidak |
| Tunjangan Transport | TJ-TRANSPORT | Earning | Benefit | Ya | Ya (per hari hadir) |
| Tunjangan Makan | TJ-MAKAN | Earning | Benefit | Ya | Ya (per hari hadir) |
| Insentif Kinerja | INSENTIF | Earning | Variable | Ya | Tidak |

#### Komponen Potongan (Deduction)

| Nama | Kode | Tipe | Kategori |
|------|------|------|----------|
| Pinjaman Karyawan | LOAN | Deduction | Loan |

### Penjelasan untuk Presentasi

> "Komponen gaji adalah building block dari slip gaji. Ada yang tetap (fixed), ada yang berdasarkan kehadiran (attendance-based). Yang attendance-based dihitung otomatis: rate per hari × jumlah hari hadir."

---

## 7. Step 6: Assign Gaji Karyawan

### Navigasi
**Menu**: Kompensasi > Gaji Karyawan
**URL**: http://localhost:8000/employee-salaries

### Yang Perlu Dilakukan

Untuk setiap karyawan:

1. Klik **Tambah Gaji Karyawan**
2. Isi:
   - Pilih karyawan
   - Gaji Pokok: misal Rp 10.000.000
   - Metode pembayaran: Transfer / Tunai
   - Info bank (jika transfer)
   - Tanggal efektif
3. **Assign Komponen**:
   - Centang komponen yang berlaku
   - Set jumlah per komponen:
     - Tunjangan Jabatan: Rp 2.000.000
     - Tunjangan Transport: Rp 25.000/hari
     - Tunjangan Makan: Rp 33.000/hari

### Contoh Setup Karyawan

```
Karyawan: Budi Santoso
├── Gaji Pokok: Rp 10.000.000
├── Tunjangan Jabatan: Rp 2.000.000 (tetap)
├── Tunjangan Transport: Rp 25.000 × hari hadir
├── Tunjangan Makan: Rp 33.000 × hari hadir
├── Status PTKP: K/1 (Kawin, 1 tanggungan)
└── NPWP: Ada
```

---

## 8. Step 7: Input Data Kehadiran

### Navigasi
**Menu**: Kehadiran > Daftar Kehadiran
**URL**: http://localhost:8000/attendances

### Cara Input Kehadiran

Ada 3 cara:

#### A. Manual Entry oleh Admin
1. Klik **Tambah Kehadiran**
2. Pilih karyawan, tanggal, jam masuk/keluar
3. Sistem otomatis hitung status (tepat waktu / telat)

#### B. Clock In/Out via Portal Karyawan
1. Login sebagai karyawan di http://localhost:8000/portal
2. Klik tombol **Clock In** (saat masuk)
3. Klik tombol **Clock Out** (saat pulang)
4. GPS & waktu tercatat otomatis

#### C. Import Data (jika ada mesin fingerprint)
1. Menu Import > Import Kehadiran
2. Upload file CSV/Excel

### Contoh Data Kehadiran untuk Demo

Buat data kehadiran selama 1 bulan (misal April 2026):

| Karyawan | Hadir | Telat | Absen | Cuti | Lembur |
|----------|-------|-------|-------|------|--------|
| Budi | 20/22 | 2 hari | 0 | 0 | 5 jam |
| Sari | 22/22 | 0 | 0 | 0 | 0 |
| Andi | 18/22 | 1 hari | 2 | 1 | 3 jam |

### Penjelasan untuk Presentasi

> "Data kehadiran menjadi input utama payroll. Sistem menghitung otomatis: berapa hari hadir (untuk tunjangan attendance-based), berapa hari telat (untuk potongan), dan berapa jam lembur (untuk uang lembur)."

---

## 9. Step 8: Buat & Proses Payroll

### Navigasi
**Menu**: Payroll > Daftar Payroll
**URL**: http://localhost:8000/payrolls

### Step 8a: Buat Payroll Baru

1. Klik **Buat Payroll**
2. Isi form:
   - Nama: "Gaji April 2026"
   - Bulan: April
   - Tahun: 2026
   - Periode: 1 Apr - 30 Apr 2026
   - Catatan: (opsional)
3. Klik **Simpan**
4. Status: **Draft** ⬜

### Step 8b: Proses (Hitung Gaji)

1. Di halaman detail payroll, klik **Proses Gaji**
2. Konfirmasi dialog muncul → klik **Ya, Proses**
3. Sistem mulai menghitung untuk setiap karyawan:

```
PROSES KALKULASI PER KARYAWAN:

1. Ambil data kehadiran bulan April
2. Hitung komponen gaji:
   - Gaji Pokok: Rp 10.000.000
   - Tj. Jabatan: Rp 2.000.000 (fixed)
   - Tj. Transport: Rp 25.000 × 20 hari = Rp 500.000
   - Tj. Makan: Rp 33.000 × 20 hari = Rp 660.000

3. Total Pendapatan: Rp 3.160.000
4. Gross Salary: Rp 10.000.000 + Rp 3.160.000 = Rp 13.160.000

5. Hitung Potongan BPJS:
   - BPJS Kesehatan (1%): Rp 131.600
   - BPJS JHT (2%): Rp 263.200
   - BPJS JP (1%): Rp 131.600

6. Hitung PPh 21 (TER method):
   - Kategori A (K/1 = K/0 → A), Gross 13.16 juta
   - TER rate: ~2% → Rp 263.200

7. Potongan Telat: 2 hari × Rp 50.000 = Rp 100.000

8. Net Salary = 13.160.000 - 526.400 (BPJS) - 263.200 (PPh21) - 100.000
            = Rp 12.270.400
```

4. Status berubah: **Terhitung** 🟡

### Penjelasan untuk Presentasi

> "Satu klik Proses, semua gaji dihitung otomatis. Sistem membaca data kehadiran, menghitung komponen gaji, BPJS, PPh 21, potongan telat/absen, dan menghasilkan slip gaji untuk setiap karyawan."

---

## 10. Step 9: Review Hasil Kalkulasi

### Navigasi
Di halaman detail payroll (setelah diproses), lihat tabel karyawan.

### Yang Perlu Dicek

1. **Tabel Ringkasan per Karyawan**:
   - Nama, Departemen, Gaji Pokok, Pendapatan, Potongan, Gaji Bersih
2. **Klik icon mata** 👁 untuk lihat detail slip gaji
3. Di halaman slip gaji, cek:
   - ✅ Gaji Pokok benar
   - ✅ Komponen pendapatan sesuai (attendance-based terhitung benar)
   - ✅ BPJS terhitung
   - ✅ PPh 21 terhitung
   - ✅ Potongan telat/absen (jika ada)
   - ✅ Total bersih masuk akal

### Penjelasan untuk Presentasi

> "Setelah proses selesai, admin bisa review detail per karyawan. Cek apakah semua komponen sudah benar sebelum approve."

---

## 11. Step 10: Adjustment Slip Gaji (FITUR BARU! ✨)

### Navigasi
Di halaman slip gaji karyawan atau tabel karyawan di payroll, klik tombol **Adjust Gaji** (icon pensil 🖊).

**URL**: http://localhost:8000/payroll-items/{id}/edit

### Kapan Perlu Adjustment?

- Karyawan dapat **bonus kinerja** bulan ini
- Ada **kasbon/pinjaman** yang harus dipotong
- Ada **insentif** proyek khusus
- Koreksi jumlah komponen tertentu

### Cara Adjustment

#### A. Ubah Gaji Pokok
1. Di form adjustment, ubah field "Gaji Pokok"
2. BPJS & PPh21 akan dihitung ulang otomatis

#### B. Ubah Jumlah Komponen Existing
1. Edit angka di field komponen yang sudah ada
2. Komponen **BPJS** dan **PPh 21** ditandai "(Otomatis)" — tidak bisa diedit manual

#### C. Tambah Komponen Baru
1. Scroll ke bagian **"Tambah Komponen Baru"**
2. Isi:
   - Nama: "Bonus Kinerja Q1"
   - Tipe: Pendapatan
   - Kategori: Variabel
   - Jumlah: Rp 2.000.000
   - Kena Pajak: Ya
3. Klik **Tambah Komponen**

#### D. Hapus Komponen Manual
1. Klik icon 🗑 di samping komponen yang ingin dihapus
2. Konfirmasi → komponen dihapus
3. **Catatan**: Komponen BPJS & PPh21 TIDAK bisa dihapus (auto-calculated)

#### E. Simpan
1. Klik **Simpan Adjustment**
2. Sistem otomatis:
   - Recalculate total pendapatan
   - Recalculate BPJS (berdasarkan gross baru)
   - Recalculate PPh 21 (berdasarkan gross baru)
   - Update total potongan
   - Update gaji bersih
   - Update total di parent payroll
3. Redirect ke halaman slip gaji (sudah terupdate)

### Komponen Editable vs Read-Only

| Kategori | Bisa Diedit? | Alasan |
|----------|-------------|--------|
| Fixed (tetap) | ✅ Ya | Komponen tetap bisa di-adjust |
| Variable | ✅ Ya | Conditional components |
| Benefit/Tunjangan | ✅ Ya | Tunjangan bisa diubah |
| Overtime/Lembur | ✅ Ya | Koreksi jam lembur |
| Loan/Pinjaman | ✅ Ya | Cicilan kasbon |
| Penalty/Denda | ✅ Ya | Potongan denda |
| Other | ✅ Ya | Custom component |
| BPJS | ❌ Otomatis | Dihitung ulang dari gross baru |
| PPh 21 | ❌ Otomatis | Dihitung ulang dari gross baru |
| Insurance | ❌ Otomatis | Dihitung ulang otomatis |

### Penjelasan untuk Presentasi

> "Setelah payroll dihitung, admin masih bisa melakukan adjustment per karyawan. Misalnya menambah bonus, potong kasbon, atau koreksi jumlah. BPJS dan PPh 21 otomatis dihitung ulang berdasarkan gross salary yang baru. Fitur ini hanya tersedia saat status Terhitung — sebelum Approve."

---

## 12. Step 11: Approve Payroll

### Navigasi
Di halaman detail payroll, klik **Setujui Payroll**.

### Proses

1. Klik **Setujui**
2. Konfirmasi → klik **Ya, Setujui**
3. Sistem:
   - Status payroll: **Terhitung** → **Disetujui** 🔵
   - Semua payroll item status → `approved`
   - Catat `approved_by` dan `approved_at`
4. **Setelah approve, adjustment TIDAK bisa dilakukan lagi**

### Penjelasan untuk Presentasi

> "Approve adalah konfirmasi bahwa semua gaji sudah benar. Setelah approve, tidak ada lagi perubahan. Ini seperti 'tanda tangan persetujuan' dari manager."

---

## 13. Step 12: Tandai Dibayar & Export

### Step 12a: Export Data Bank

1. Klik **Export Bank** (tersedia sejak status Terhitung)
2. Download file Excel/CSV berisi:
   - Nama karyawan
   - No. rekening
   - Bank
   - Jumlah transfer
3. Upload file ini ke internet banking untuk transfer massal

### Step 12b: Tandai Dibayar

1. Setelah transfer selesai, klik **Tandai Dibayar**
2. Konfirmasi → klik **Ya, Tandai Dibayar**
3. Sistem:
   - Status: **Disetujui** → **Dibayar** ✅
   - Semua payroll item → `paid`
   - Catat `paid_by`, `paid_at`, `payment_date`

### Penjelasan untuk Presentasi

> "Setelah approve, admin export data bank untuk transfer massal. Setelah transfer selesai, tandai sebagai dibayar. Ini menutup siklus payroll bulan ini."

---

## 14. Step 13: Cetak Slip Gaji

### Navigasi
Di halaman slip gaji karyawan.

### Opsi

1. **Download PDF** — klik tombol "Download PDF" → file PDF terdownload
2. **Cetak Langsung** — klik tombol "Cetak" → browser print dialog

### Info di Slip Gaji

```
┌─────────────────────────────────────────────────┐
│  NAMA PERUSAHAAN                    SLIP GAJI    │
│  Alamat Perusahaan                  April 2026   │
│                                     PAY000120...  │
├─────────────────────────────────────────────────┤
│  Nama: Budi Santoso    Jabatan: Manager          │
│  NIK: EMP001           Status: Dibayar ✅         │
│  Dept: IT              Periode: 1-30 Apr 2026    │
├─────────────────────────────────────────────────┤
│  Ringkasan Kehadiran                             │
│  Hari Kerja: 22 | Hadir: 20 | Telat: 2          │
│  Absen: 0 | Cuti: 0 | Lembur: 5 jam             │
├───────────────────┬─────────────────────────────┤
│  PENDAPATAN       │  POTONGAN                    │
│  Gaji Pokok  10jt │  BPJS Kes    131.600        │
│  Tj Jabatan   2jt │  BPJS JHT    263.200        │
│  Tj Transport 500k│  BPJS JP     131.600        │
│  Tj Makan    660k │  Potong Telat 100.000       │
│  Bonus      2.0jt │  PPh 21      xxx.xxx        │
│  ──────────────── │  ────────────────────        │
│  Total    15.16jt │  Total     xxx.xxx          │
├─────────────────────────────────────────────────┤
│  GAJI BERSIH (Take Home Pay)                     │
│  Rp xx.xxx.xxx                                   │
│  Transfer - BCA - 1234567890                     │
└─────────────────────────────────────────────────┘
```

### Penjelasan untuk Presentasi

> "Slip gaji bisa didownload PDF atau langsung dicetak. Semua komponen ditampilkan transparan — karyawan bisa lihat detail pendapatan dan potongannya."

---

## 15. Ringkasan Alur Status

```
PAYROLL LIFECYCLE:

  ┌──────┐    Proses    ┌──────────┐   Approve   ┌──────────┐   Bayar   ┌────────┐
  │ DRAFT│ ──────────►  │CALCULATED│ ──────────►  │ APPROVED │ ────────► │  PAID  │
  │  ⬜  │              │    🟡    │              │    🔵    │           │   ✅   │
  └──────┘              └──────────┘              └──────────┘           └────────┘
     │                       │                         │
     │                       │ ◄── Adjust ──►          │
     │                       │   (per karyawan)        │
     │                       │                         │
     └───────────────────────┴─────────────────────────┘
                        Batalkan (ke Cancelled ❌)
```

### Apa yang Bisa Dilakukan di Setiap Status

| Status | Aksi yang Tersedia |
|--------|-------------------|
| **Draft** ⬜ | Edit info payroll, Proses, Batalkan, Hapus |
| **Calculated** 🟡 | Review, **Adjust per karyawan**, Approve, Export Bank, Batalkan |
| **Approved** 🔵 | Tandai Dibayar, Export Bank, Batalkan |
| **Paid** ✅ | Lihat slip, Download PDF, Export Bank |
| **Cancelled** ❌ | Lihat saja (read-only) |

---

## 16. Troubleshooting

### "Payroll tidak bisa diproses"
- Pastikan status masih **Draft**
- Pastikan ada karyawan aktif dengan gaji yang sudah di-assign

### "PPh 21 = 0 padahal gaji tinggi"
- Cek menu PPh 21 Settings → pastikan sudah aktif
- Jika pakai TER → pastikan TER rates sudah di-initialize
- Cek data karyawan → pastikan `tax_status` sudah diisi (TK/0, K/1, dll)

### "BPJS = 0"
- Cek BPJS TK Settings → pastikan sudah aktif
- Cek BPJS Kes Settings → pastikan sudah aktif

### "Tunjangan attendance-based = 0"
- Pastikan ada data kehadiran untuk periode tersebut
- Cek komponen gaji → pastikan `is_attendance_based = true`
- Cek jumlah hari hadir di data kehadiran

### "Tombol Adjust tidak muncul"
- Tombol adjust hanya muncul saat payroll status = **Calculated** (Terhitung)
- Setelah Approve, adjust sudah tidak bisa

### "Komponen BPJS/PPh21 tidak bisa diedit di adjustment"
- Ini by design — BPJS & PPh21 dihitung ulang otomatis berdasarkan gross salary baru
- Ubah gaji pokok atau komponen lain → BPJS & PPh21 ikut berubah otomatis

---

## Checklist Presentasi

Gunakan checklist ini saat demo:

- [ ] PPh 21 Settings sudah aktif & rate ter-initialize
- [ ] BPJS TK Settings sudah aktif
- [ ] BPJS Kes Settings sudah aktif
- [ ] Payroll Settings sudah diisi
- [ ] Minimal 2 komponen gaji sudah dibuat
- [ ] Minimal 2 karyawan sudah punya gaji
- [ ] Data kehadiran sudah diinput untuk periode payroll
- [ ] Buat payroll baru → Proses → Review
- [ ] Demo adjustment (tambah bonus, potong kasbon)
- [ ] Approve → Tandai Dibayar
- [ ] Download slip gaji PDF

---

> **Tips Presentasi**: Mulai dari "big picture" (alur status payroll), lalu zoom-in ke setiap step. Buka 2 tab browser — satu untuk settings, satu untuk payroll — supaya bisa bolak-balik cepat.
