# Summary Meeting HR Expert - 13 Februari 2026

## Informasi Meeting
- **Tanggal:** 13 Februari 2026
- **Peserta:** Code Bahri (Developer JagoGaji), Benny Astria (HR Expert, ex-Holland Bakery, sekarang di perusahaan Jepang)
- **Topik:** Review fitur HRIS/Payroll dan masukan dari praktisi HR

---

## Ringkasan Diskusi

### 1. Background Benny Astria
- Pengalaman di Holland Bakery menggunakan **Axiopro** (fitur lengkap, harga ~250 juta)
- Saat ini di perusahaan Jepang (800 karyawan lokal + 200 expat)
- Menggunakan **SAP** untuk database karyawan (dari HQ Jepang)
- Menggunakan **Sigma HRIS** untuk absensi dan payroll
- Menggunakan **Axiopro** hanya untuk Performance Evaluation

### 2. Benchmark Kompetitor
| Aplikasi | Kelebihan | Kekurangan | Harga |
|----------|-----------|------------|-------|
| Axiopro | Fitur lengkap, Authority Rights detail | UI/UX kurang menarik | ~250 juta (sekali beli) + maintenance |
| Sigma HRIS | Fokus payroll & absensi | UI/UX sangat jadul | ~250 juta |
| Gajihub | SaaS, subscription | Biaya per bulan | Per karyawan/bulan |
| Talenta | SaaS, modern | Biaya per bulan | Per karyawan/bulan |

### 3. Struktur HR yang Ideal (Menurut Benny)
HR dibagi menjadi beberapa spesialisasi:
1. **Master Database** - Data karyawan (FUNDAMENTAL)
2. **Payroll/Compensation & Benefit** - Penggajian
3. **Recruitment** - Penerimaan karyawan
4. **Training & Development** - Pelatihan
5. **Performance Evaluation/KPI** - Penilaian kinerja
6. **Industrial Relationship** - SP, kontrak, hubungan industrial

### 4. Flow Payroll yang Benar
```
Data Karyawan → Data Kehadiran → Data Cuti/Lembur → Proses Payroll
```

**Cut-off Period:**
- Benny recommend: Gajian tanggal 1, absensi dari tanggal 1 s/d akhir bulan sebelumnya
- Contoh: Gaji Februari = Absensi Januari (1-31 Jan)

**Proses Approval:**
```
Staff Payroll → HR Manager → GM HR → GM Finance → Bank → Transfer
```

---

## Fitur-Fitur yang Dibahas Detail

### A. BPJS Ketenagakerjaan
| Jenis | Karyawan | Perusahaan | Keterangan |
|-------|----------|------------|------------|
| JHT (Jaminan Hari Tua) | 2% | 3.7% | Dari gaji |
| JKK (Jaminan Kecelakaan Kerja) | 0% | Bervariasi | Sesuai risiko pekerjaan |
| JKM (Jaminan Kematian) | 0% | 0.3% | Murni perusahaan |
| JP (Jaminan Pensiun) | 1% | 2% | Ada plafon maksimum |
| JKP (Jaminan Kehilangan Pekerjaan) | 0% | 0.46% | Baru 2026 |

**Catatan:** JP ada batas maksimum yang diupdate tiap tahun oleh BPJS.

### B. BPJS Kesehatan
- **Karyawan:** 1% dari gaji
- **Perusahaan:** 4% dari gaji
- **Plafon:** Rp 12.000.000 (maksimum gaji yang dihitung)
- **Cover:** Karyawan + Pasangan + 3 Anak (maks 21 tahun, atau 25 tahun jika kuliah)
- **Tanggungan Tambahan:** 1% per orang (opsional)

**Mekanisme:**
- Kesehatan: Bayar dulu ke BPJS, baru potong gaji (H+1 bulan)
- Ketenagakerjaan: Potong gaji, baru bayar ke BPJS (bulan yang sama)

### C. PPh 21 / TER (Tarif Efektif Rata-rata)
- **Sistem Gross:** Pajak ditanggung karyawan (dipotong dari gaji)
- **Sistem Net/Gross-up:** Pajak ditanggung perusahaan (karyawan terima bersih)
- **PTKP:** Beda kategori (TK/0, K/0, K/1, K/2, K/3, dll)
- Tahun 2026 ada kategori baru di TER

### D. THR (Tunjangan Hari Raya)
- **Masa kerja ≥ 12 bulan:** 1x gaji (Gaji Pokok + Tunjangan Tetap)
- **Masa kerja < 12 bulan:** Pro-rata (Gaji/12 × bulan kerja)
- **Deadline:** Max 7 hari sebelum hari raya
- **Opsi:** Idul Fitri, Natal, atau sesuai agama karyawan

### E. Sistem Absensi yang Ideal
**Fitur yang dibutuhkan:**
- Face Recognition (anti titip absen)
- GPS Lock (radius kantor)
- Mobile apps
- Integrasi mesin fingerprint/RFID
- Record: Tepat waktu, Terlambat, Pulang cepat, Tidak hadir

**Laporan yang dibutuhkan:**
- Dashboard kehadiran (tepat waktu, terlambat, tidak hadir)
- Tracking mangkir (untuk proses disipliner)
- Export Excel untuk analisis

### F. Sistem Cuti
- Approval berjenjang (Atasan → HR)
- Saldo cuti tahunan
- Jenis cuti: Tahunan, Sakit, Melahirkan, dll
- Cuti tidak digunakan bisa dibayarkan (opsional per perusahaan)

### G. Ekspor Payroll ke Bank
**Format yang dibutuhkan:**
- Nama sesuai rekening
- Nomor rekening
- Gaji bersih (sudah potong BPJS & Pajak)
- Support multi-bank

---

## Rekomendasi untuk Pricing Course

| Paket | Fitur | Harga Suggested |
|-------|-------|-----------------|
| Basic | Database + Payroll | ~600-700rb |
| Intermediate | + Kehadiran + Cuti + Lembur | TBD |
| Advanced | + KPI + Rekrutmen | TBD (batch terpisah) |

**Catatan Benny:**
- KPI/Performance sebaiknya jadi fitur/batch terpisah (scope besar sendiri)
- Database karyawan adalah FUNDAMENTAL
- Payroll adalah yang paling dibutuhkan
- Untuk course, sebaiknya gajian tanggal 1 (lebih mudah kalkulasi)

---

## Kesimpulan Meeting

1. **JagoGaji sudah di jalur yang benar** - fitur-fitur fundamental sudah ada
2. **UI/UX lebih bagus** dari kompetitor (Axiopro, Sigma)
3. **Perlu fokus** ke flow yang benar (Database → Kehadiran → Payroll)
4. **Perhitungan pajak TER** perlu di-update ke regulasi 2026
5. **Fitur pinjaman** adalah nilai tambah (tidak semua kompetitor punya)
6. **Export ke bank** perlu ditambahkan
