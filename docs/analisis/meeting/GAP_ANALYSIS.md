# Gap Analysis - JagoGaji vs Kebutuhan HR Expert

## Status Legend
- ✅ **SUDAH ADA** - Fitur sudah tersedia dan berfungsi
- ⚠️ **PERLU REVIEW** - Fitur ada tapi perlu dicek/diperbaiki
- ❌ **BELUM ADA** - Fitur belum tersedia
- 🔧 **PERLU ENHANCEMENT** - Fitur ada tapi perlu ditingkatkan

---

## 1. MASTER DATABASE KARYAWAN

| Fitur | Status | Keterangan |
|-------|--------|------------|
| Data Pribadi (Nama, NIK, Alamat, dll) | ✅ SUDAH ADA | employees table |
| Data Keluarga (Tanggungan) | ⚠️ PERLU REVIEW | Cek field tanggungan untuk BPJS |
| Data Bank (Nama, No Rek) | ✅ SUDAH ADA | Untuk transfer gaji |
| Data BPJS (Kesehatan & TK) | ✅ SUDAH ADA | bpjs_kesehatan_number, bpjs_ketenagakerjaan_number |
| Status Kepegawaian | ✅ SUDAH ADA | employment_status (permanent/contract/probation) |
| Departemen & Jabatan | ✅ SUDAH ADA | departments, positions |
| Lokasi Kerja | ✅ SUDAH ADA | office_locations dengan radius GPS |
| Dokumen Karyawan | ✅ SUDAH ADA | employee_documents |
| Kontrak Kerja | ⚠️ PERLU REVIEW | Cek PKWTdanpenyimpanan kontrak |
| Multi-cabang/Brand | ✅ SUDAH ADA | Multi-tenant architecture |

### Action Items Database:
1. Cek field tanggungan keluarga untuk BPJS Kesehatan (pasangan + anak)
2. Review field NPWP dan status PTKP karyawan
3. Pastikan ada field untuk track kontrak PKWT (tanggal mulai, berakhir)

---

## 2. KEHADIRAN (ATTENDANCE)

| Fitur | Status | Keterangan |
|-------|--------|------------|
| Clock In/Out | ✅ SUDAH ADA | attendances table |
| GPS Tracking | ✅ SUDAH ADA | latitude, longitude di attendance |
| Face Recognition | ✅ SUDAH ADA | face_enrollments + API verification |
| Jadwal Kerja | ✅ SUDAH ADA | work_schedules |
| Laporan Kehadiran | ✅ SUDAH ADA | reports/attendance |
| Status Terlambat | ✅ SUDAH ADA | is_late field |
| Status Pulang Cepat | ❌ BELUM ADA | Perlu ditambahkan field |
| Export Excel | ✅ SUDAH ADA | reports/attendance/export |
| Mobile App Absensi | ✅ SUDAH ADA | API v1 attendance |
| Integrasi Mesin Fingerprint | ❌ BELUM ADA | Nice to have, bukan prioritas |

### Action Items Kehadiran:
1. **PENTING:** Tambahkan tracking "Pulang Cepat" (early_leave)
2. Dashboard: Visualisasi tepat waktu vs terlambat vs tidak hadir
3. Alert untuk karyawan mangkir 3+ hari (untuk proses disipliner)

---

## 3. CUTI (LEAVE)

| Fitur | Status | Keterangan |
|-------|--------|------------|
| Jenis Cuti | ✅ SUDAH ADA | leave_types |
| Saldo Cuti | ✅ SUDAH ADA | leave_balances |
| Pengajuan Cuti | ✅ SUDAH ADA | leave_requests |
| Approval Workflow | ✅ SUDAH ADA | approval_workflows + steps |
| Cuti Tahunan | ✅ SUDAH ADA | Configurable di leave_types |
| Cuti Khusus (Sakit, Melahirkan, dll) | ✅ SUDAH ADA | Multiple leave_types |
| Portal Karyawan | ✅ SUDAH ADA | portal/leave |
| Laporan Cuti | ✅ SUDAH ADA | reports/leave |

### Action Items Cuti:
- Fitur cuti sudah lengkap dan sesuai standar HR

---

## 4. LEMBUR (OVERTIME)

| Fitur | Status | Keterangan |
|-------|--------|------------|
| Pengajuan Lembur | ✅ SUDAH ADA | overtime_requests |
| Approval Lembur | ✅ SUDAH ADA | Via approval workflow |
| Setting Tarif Lembur | ✅ SUDAH ADA | overtime_settings |
| Perhitungan Hari Kerja vs Libur | ⚠️ PERLU REVIEW | Cek formula sesuai UU |

### Action Items Lembur:
1. Review formula perhitungan lembur sesuai PP 35/2021:
   - Hari kerja: Jam 1 = 1.5x, Jam 2+ = 2x
   - Hari libur: Jam 1-7 = 2x, Jam 8 = 3x, Jam 9+ = 4x

---

## 5. PAYROLL / PENGGAJIAN

| Fitur | Status | Keterangan |
|-------|--------|------------|
| Komponen Gaji | ✅ SUDAH ADA | salary_components |
| Gaji Pokok | ✅ SUDAH ADA | employee_salaries |
| Tunjangan Tetap | ✅ SUDAH ADA | Configurable components |
| Tunjangan Tidak Tetap | ✅ SUDAH ADA | Based on attendance |
| Periode Payroll | ✅ SUDAH ADA | payrolls table dengan periode |
| Proses Gaji | ✅ SUDAH ADA | payrolls/process |
| Approval Payroll | ✅ SUDAH ADA | payrolls/approve |
| Slip Gaji (Payslip) | ✅ SUDAH ADA | payroll_items + PDF |
| Setting Cut-off | ⚠️ PERLU REVIEW | Cek flexibility cut-off date |
| Export Bank Transfer | ❌ BELUM ADA | **PENTING:** Perlu export format bank |
| Pro-rata Gaji | ⚠️ PERLU REVIEW | Untuk karyawan baru |
| Hari Kerja Setting | ⚠️ PERLU REVIEW | 5 hari (22 hari/bln) atau 6 hari (25 hari/bln) |

### Action Items Payroll:
1. **CRITICAL:** Tambahkan fitur Export Payroll untuk Bank:
   - Format: Nama Rekening, No Rek, Nominal, Bank
   - Support multi-bank
2. Review setting cut-off period (flexible tanggal)
3. Tambahkan setting hari kerja per perusahaan (5 atau 6 hari)
4. Review perhitungan pro-rata gaji untuk karyawan baru

---

## 6. BPJS KETENAGAKERJAAN

| Fitur | Status | Keterangan |
|-------|--------|------------|
| Setting JHT | ✅ SUDAH ADA | bpjs_tk_settings |
| Setting JKK | ✅ SUDAH ADA | Rate berdasarkan risiko |
| Setting JKM | ✅ SUDAH ADA | Fixed rate |
| Setting JP | ✅ SUDAH ADA | Dengan plafon |
| Setting JKP | ⚠️ PERLU REVIEW | Cek rate 2026 |
| Kalkulator BPJS | ✅ SUDAH ADA | bpjs-tk-settings/calculate |
| Laporan BPJS TK | ❌ BELUM ADA | Untuk rekonsiliasi tagihan |

### Action Items BPJS TK:
1. Update rate JKP 2026 (jika ada perubahan)
2. Tambahkan laporan BPJS TK untuk rekonsiliasi dengan tagihan BPJS
3. Review plafon JP terbaru

---

## 7. BPJS KESEHATAN

| Fitur | Status | Keterangan |
|-------|--------|------------|
| Setting Rate | ✅ SUDAH ADA | bpjs_kes_settings |
| Plafon Maksimum | ✅ SUDAH ADA | Rp 12.000.000 |
| Upah Minimum Setting | ✅ SUDAH ADA | UMR per daerah |
| Tanggungan Keluarga | ⚠️ PERLU REVIEW | 1% per orang tambahan |
| Kalkulator BPJS Kes | ✅ SUDAH ADA | bpjs-kes-settings/calculate |
| Laporan BPJS Kes | ❌ BELUM ADA | Untuk rekonsiliasi tagihan |

### Action Items BPJS Kesehatan:
1. Review field tanggungan keluarga tambahan di employee
2. Tambahkan laporan BPJS Kesehatan untuk rekonsiliasi
3. Pastikan UMR per provinsi bisa di-setting

---

## 8. PPh 21 / PAJAK

| Fitur | Status | Keterangan |
|-------|--------|------------|
| Setting PTKP | ✅ SUDAH ADA | pph21_settings (ptkp) |
| Setting Tarif TER | ✅ SUDAH ADA | pph21_rates |
| Metode Gross | ✅ SUDAH ADA | Pajak ditanggung karyawan |
| Metode Gross-up/Net | ✅ SUDAH ADA | Pajak ditanggung perusahaan |
| Inisialisasi PTKP | ✅ SUDAH ADA | pph21-settings/initialize-ptkp |
| Inisialisasi Rate TER | ✅ SUDAH ADA | pph21-settings/initialize-rates |
| Laporan Pajak | ⚠️ PERLU REVIEW | reports/payroll/tax-summary |

### Action Items PPh 21:
1. Review tarif TER 2026 (apakah ada perubahan kategori)
2. Pastikan laporan pajak bisa export untuk e-SPT/Coretax

---

## 9. THR (TUNJANGAN HARI RAYA)

| Fitur | Status | Keterangan |
|-------|--------|------------|
| Setting THR | ✅ SUDAH ADA | thr_settings |
| Kalkulasi THR | ✅ SUDAH ADA | thr/calculate |
| Pro-rata THR | ✅ SUDAH ADA | Untuk masa kerja < 12 bulan |
| Include Tunjangan Tetap | ✅ SUDAH ADA | Configurable |
| Pilihan Hari Raya | ✅ SUDAH ADA | Idul Fitri, Natal, dll |
| Proses THR | ✅ SUDAH ADA | thr/process |
| Pembayaran THR | ✅ SUDAH ADA | thr/pay |

### Action Items THR:
- Fitur THR sudah lengkap dan sesuai standar

---

## 10. PINJAMAN KARYAWAN (LOAN)

| Fitur | Status | Keterangan |
|-------|--------|------------|
| Pengajuan Pinjaman | ✅ SUDAH ADA | loans |
| Setting Pinjaman | ✅ SUDAH ADA | loan_settings |
| Approval Pinjaman | ✅ SUDAH ADA | loans/approve |
| Cicilan/Angsuran | ✅ SUDAH ADA | loan_installments |
| Pembayaran Cicilan | ✅ SUDAH ADA | loan-installments/pay |
| Portal Karyawan | ✅ SUDAH ADA | portal/loans |

### Action Items Pinjaman:
- **NILAI PLUS!** Fitur ini tidak semua kompetitor punya
- Sudah lengkap sesuai kebutuhan

---

## 11. REIMBURSEMENT

| Fitur | Status | Keterangan |
|-------|--------|------------|
| Kategori Reimbursement | ✅ SUDAH ADA | reimbursement_categories |
| Pengajuan | ✅ SUDAH ADA | reimbursements |
| Upload Bukti | ✅ SUDAH ADA | receipt_path |
| Approval | ✅ SUDAH ADA | approval workflow |
| Pembayaran | ✅ SUDAH ADA | reimbursements/pay |
| Portal Karyawan | ✅ SUDAH ADA | portal/reimbursements |

### Action Items Reimbursement:
- Sudah lengkap

---

## 12. LAPORAN (REPORTS)

| Fitur | Status | Keterangan |
|-------|--------|------------|
| Laporan Karyawan | ✅ SUDAH ADA | reports/employees |
| Laporan per Departemen | ✅ SUDAH ADA | reports/employees/by-department |
| Laporan Kehadiran | ✅ SUDAH ADA | reports/attendance |
| Laporan Keterlambatan | ✅ SUDAH ADA | reports/attendance/lateness |
| Laporan Cuti | ✅ SUDAH ADA | reports/leave |
| Laporan Payroll | ✅ SUDAH ADA | reports/payroll |
| Laporan per Departemen Payroll | ✅ SUDAH ADA | reports/payroll/by-department |
| Laporan Pajak | ✅ SUDAH ADA | reports/payroll/tax-summary |
| Export Excel | ✅ SUDAH ADA | Semua report ada export |
| Laporan BPJS | ❌ BELUM ADA | Untuk rekonsiliasi |

### Action Items Laporan:
1. Tambahkan Laporan BPJS Kesehatan
2. Tambahkan Laporan BPJS Ketenagakerjaan

---

## 13. FITUR TAMBAHAN

| Fitur | Status | Keterangan |
|-------|--------|------------|
| Karir/Career Path | ✅ SUDAH ADA | career_paths, career_transitions |
| KPI/Performance | ✅ SUDAH ADA | performance_kpis, performance_reviews |
| Pengumuman | ✅ SUDAH ADA | announcements |
| Petty Cash | ✅ SUDAH ADA | petty_cash (masuk Finance) |
| Activity Logs | ✅ SUDAH ADA | activity_logs |
| Multi-user & Roles | ✅ SUDAH ADA | Spatie Permission |
| Approval Workflow | ✅ SUDAH ADA | Configurable steps |
| API Mobile | ✅ SUDAH ADA | Full API v1 |

---

## PRIORITAS PENGEMBANGAN

### Critical (Harus Ada)
1. ❌ **Export Payroll untuk Bank Transfer**
2. ❌ **Laporan BPJS (Kesehatan & TK)**
3. ❌ **Tracking Pulang Cepat (Early Leave)**

### High Priority
1. ⚠️ Review cut-off period flexibility
2. ⚠️ Review setting hari kerja (5/6 hari)
3. ⚠️ Review tanggungan keluarga BPJS
4. ⚠️ Update rate TER 2026 jika ada perubahan

### Nice to Have
1. Dashboard alert untuk karyawan mangkir
2. Organisation Chart generator dari database
3. Integrasi e-SPT/Coretax

---

## KESIMPULAN

**JagoGaji sudah memiliki ~90% fitur yang dibutuhkan oleh praktisi HR.**

Fitur yang BELUM ADA dan PERLU PRIORITAS:
1. Export format bank untuk transfer gaji
2. Laporan BPJS untuk rekonsiliasi
3. Field pulang cepat di attendance

Fitur yang sudah ada dan menjadi NILAI PLUS:
- Pinjaman Karyawan (tidak semua kompetitor punya)
- Face Recognition
- GPS Tracking
- UI/UX modern (lebih baik dari Axiopro & Sigma)
- Multi-tenant architecture
- Full mobile API
