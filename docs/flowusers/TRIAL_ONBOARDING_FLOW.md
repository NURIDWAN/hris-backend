# GajiPro Trial & Onboarding Flow

## Overview

Dokumen ini menjelaskan flow onboarding untuk user baru yang mendaftar trial 14 hari di GajiPro. Tujuannya adalah memberikan pengalaman "hands-on" yang lengkap sehingga user dari JagoFlutter Academy bisa langsung testing aplikasi mobile tanpa setup manual.

---

## Mobile App Download Links

> **Note:** Update link di bawah ini sesuai dengan distribusi app yang tersedia.

| Platform | Link | Status |
|----------|------|--------|
| Android (Google Play) | `https://play.google.com/store/apps/details?id=net.gajipro.app` | 🔜 Coming Soon |
| Android (Direct APK) | `https://drive.google.com/file/d/xxx/view` | 🔜 Coming Soon |
| iOS (App Store) | `https://apps.apple.com/app/gajipro/idxxx` | 🔜 Coming Soon |
| iOS (TestFlight) | `https://testflight.apple.com/join/xxx` | 🔜 Coming Soon |

### Configuration

Link download disimpan di config untuk kemudahan update:

```php
// config/gajipro.php
return [
    'mobile_app' => [
        'android' => [
            'play_store' => env('MOBILE_ANDROID_PLAYSTORE', ''),
            'direct_apk' => env('MOBILE_ANDROID_APK', ''),
        ],
        'ios' => [
            'app_store' => env('MOBILE_IOS_APPSTORE', ''),
            'testflight' => env('MOBILE_IOS_TESTFLIGHT', ''),
        ],
    ],
];
```

```env
# .env
MOBILE_ANDROID_PLAYSTORE=https://play.google.com/store/apps/details?id=net.gajipro.app
MOBILE_ANDROID_APK=https://drive.google.com/file/d/xxx/view
MOBILE_IOS_APPSTORE=
MOBILE_IOS_TESTFLIGHT=
```

---

## Auto-Created Data saat Registrasi

Ketika user baru mendaftar (register), sistem akan **otomatis membuat** data-data berikut untuk perusahaan tersebut. Ini memungkinkan user langsung testing semua fitur tanpa perlu setup manual.

### 1. Company Data

| Data | Nilai | Keterangan |
|------|-------|------------|
| Subscription Plan | `trial` | Trial gratis 14 hari |
| Max Employees | `10` | Batas karyawan untuk trial |
| Demo Mode | `true` | Flag demo mode aktif |

### 2. Master Data yang Auto-Create

#### Departments (5 departemen)
| Code | Nama | Deskripsi |
|------|------|-----------|
| HR | Human Resources | Pengelolaan SDM dan rekrutmen |
| FIN | Finance | Keuangan dan akuntansi |
| IT | Information Technology | Pengembangan sistem dan infrastruktur |
| MKT | Marketing | Pemasaran dan branding |
| OPS | Operations | Operasional perusahaan |

#### Positions (12 jabatan)
| Department | Jabatan | Level | Gaji Pokok |
|------------|---------|-------|------------|
| HR | HR Manager | 3 | Rp 15.000.000 |
| HR | HR Staff | 1 | Rp 6.000.000 |
| FIN | Finance Manager | 3 | Rp 18.000.000 |
| FIN | Accountant | 2 | Rp 8.000.000 |
| FIN | Finance Staff | 1 | Rp 5.500.000 |
| IT | IT Manager | 3 | Rp 20.000.000 |
| IT | Senior Developer | 2 | Rp 15.000.000 |
| IT | Junior Developer | 1 | Rp 7.000.000 |
| MKT | Marketing Manager | 3 | Rp 16.000.000 |
| MKT | Digital Marketing | 2 | Rp 9.000.000 |
| MKT | Marketing Staff | 1 | Rp 5.500.000 |
| OPS | Operations Manager | 3 | Rp 14.000.000 |
| OPS | Staff | 1 | Rp 5.000.000 |

#### Work Schedules (2 jadwal kerja)
| Code | Nama | Jam Kerja | Hari Kerja |
|------|------|-----------|------------|
| WS-NORMAL | Jam Kantor Normal | 08:00 - 17:00 | Senin - Jumat |
| WS-PAGI | Shift Pagi | 06:00 - 14:00 | Senin - Sabtu |

#### Leave Types (5 jenis cuti)
| Code | Nama | Max Hari/Tahun | Berbayar |
|------|------|----------------|----------|
| CT | Cuti Tahunan | 12 | Ya |
| CS | Cuti Sakit | 14 | Ya |
| CM | Cuti Melahirkan | 90 | Ya |
| ITD | Izin Tidak Dibayar | 30 | Tidak |
| CMK | Cuti Menikah | 3 | Ya |

#### Salary Components (5 komponen gaji)
| Code | Nama | Tipe | Nominal Default |
|------|------|------|-----------------|
| TJ-MAKAN | Tunjangan Makan | Earning | Rp 500.000 |
| TJ-TRANS | Tunjangan Transport | Earning | Rp 500.000 |
| TJ-KES | Tunjangan Kesehatan | Earning | Rp 300.000 |
| BONUS | Bonus Kinerja | Earning | Rp 0 |
| POT-PINJAM | Potongan Pinjaman | Deduction | Rp 0 |

### 3. Sample Employees (10 karyawan demo)

Sistem akan membuat 10 karyawan demo dengan data lengkap:
- Data pribadi (nama, email, telepon, alamat)
- Data kepegawaian (nomor karyawan, jabatan, departemen)
- Data gaji (gaji pokok sesuai jabatan)
- Data pajak (NPWP, status PTKP)
- Data BPJS (nomor BPJS Kesehatan & Ketenagakerjaan)
- Data bank (untuk transfer gaji)

### 4. Transactional Data

| Data | Jumlah | Keterangan |
|------|--------|------------|
| Attendance | 30 hari terakhir | Data kehadiran untuk semua karyawan |
| Leave Balances | Per karyawan | Saldo cuti untuk setiap jenis cuti |
| Leave Requests | 3 sample | Pengajuan cuti (pending & approved) |
| Payroll | 1 bulan | Payroll bulan sebelumnya (status: paid) |
| Payroll Items | Per karyawan | Slip gaji lengkap dengan perhitungan PPh21 & BPJS |

### 5. Tax & BPJS Settings

#### PPh21 Settings
- Metode: TER (Tarif Efektif Rata-rata)
- PTKP Settings sesuai aturan 2024
- Tarif PPh21 progresif sesuai UU HPP

#### BPJS Ketenagakerjaan
| Program | Perusahaan | Karyawan |
|---------|------------|----------|
| JHT | 3.70% | 2.00% |
| JKK | 0.24% | - |
| JKM | 0.30% | - |
| JP | 2.00% | 1.00% |

#### BPJS Kesehatan
| Item | Rate |
|------|------|
| Perusahaan | 4.00% |
| Karyawan | 1.00% |
| Max UMK | Rp 12.000.000 |

---

## Data yang BELUM Auto-Create (Perlu Enhancement)

Berikut data yang saat ini **belum** otomatis dibuat saat registrasi dan perlu ditambahkan:

| Data | Priority | Alasan |
|------|----------|--------|
| Office Location (Kantor Pusat) | HIGH | Diperlukan untuk attendance GPS validation |
| Role untuk User (hr-manager, employee) | HIGH | User hanya dapat role admin, perlu multi-role |
| Employee record untuk User | HIGH | User belum terhubung ke Employee, tidak bisa login mobile |
| Face Recognition enrollment | MEDIUM | Untuk demo face recognition di mobile |
| Reimbursement samples | LOW | Data sample reimbursement |
| Overtime samples | LOW | Data sample lembur |

---

## Current State Analysis

### Yang Sudah Ada

| Component | Status | Location |
|-----------|--------|----------|
| Registration dengan Trial 14 hari | ✅ Ada | `RegisteredUserController.php` |
| DemoDataService untuk seed data | ✅ Ada | `app/Services/DemoDataService.php` |
| Demo Mode flag di Company | ✅ Ada | `companies.is_demo_mode` |
| Demo Settings Controller | ✅ Ada | `DemoSettingController.php` |
| Demo Mode Banner Component | ✅ Ada | `components/demo-mode-banner.blade.php` |
| Reset to Production | ✅ Ada | `DemoSettingController@switchToProduction` |

### Gap Analysis - Yang Perlu Ditambahkan

| Feature | Status | Priority | Description |
|---------|--------|----------|-------------|
| Office Location (Kantor Pusat) | ❌ Missing | HIGH | Lokasi kantor default untuk GPS validation attendance |
| User sebagai Admin + HR + Employee | ❌ Missing | HIGH | User pendaftar harus punya 3 role sekaligus |
| Employee record untuk user pendaftar | ❌ Missing | HIGH | User harus punya data Employee agar bisa login mobile |
| Employee-Office assignment | ❌ Missing | HIGH | Karyawan perlu di-assign ke Office Location |
| Face Recognition sample data | ❌ Missing | MEDIUM | Data wajah untuk demo face recognition |
| Sample Reimbursement | ❌ Missing | LOW | Data reimbursement sample |
| Sample Overtime Request | ❌ Missing | LOW | Data lembur sample |
| Dashboard Demo Info Banner | ⚠️ Partial | MEDIUM | Banner yang jelas di dashboard |
| Mobile API compatibility | ⚠️ Check | HIGH | Pastikan user bisa login via mobile API |

---

## Target Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         USER ONBOARDING FLOW                                 │
└─────────────────────────────────────────────────────────────────────────────┘

┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│   1. LANDING     │     │   2. REGISTER    │     │   3. AUTO SETUP  │
│      PAGE        │────▶│    (Trial 14d)   │────▶│    (Background)  │
└──────────────────┘     └──────────────────┘     └──────────────────┘
                                │                         │
                                │                         ▼
                                │                 ┌──────────────────┐
                                │                 │ Create Company   │
                                │                 │ - Trial plan     │
                                │                 │ - 14 days        │
                                │                 │ - is_demo_mode   │
                                │                 └──────────────────┘
                                │                         │
                                │                         ▼
                                │                 ┌──────────────────┐
                                │                 │ Create User      │
                                │                 │ - admin role     │
                                │                 │ - hr-manager     │
                                │                 │ - employee role  │
                                │                 └──────────────────┘
                                │                         │
                                │                         ▼
                                │                 ┌──────────────────┐
                                │                 │ Create Employee  │
                                │                 │ Record for User  │
                                │                 │ (agar bisa login │
                                │                 │  mobile app)     │
                                │                 └──────────────────┘
                                │                         │
                                │                         ▼
                                │                 ┌──────────────────┐
                                │                 │ Seed Demo Data   │
                                │                 │ - Departments    │
                                │                 │ - Positions      │
                                │                 │ - 10 Employees   │
                                │                 │ - Attendance 30d │
                                │                 │ - Payroll 1 bulan│
                                │                 │ - Leave samples  │
                                │                 └──────────────────┘
                                │                         │
                                ▼                         ▼
                        ┌──────────────────────────────────────────┐
                        │              4. DASHBOARD                 │
                        │  ┌────────────────────────────────────┐  │
                        │  │     🎉 DEMO MODE BANNER             │  │
                        │  │  "Anda menggunakan data demo..."   │  │
                        │  │  [Mulai dari Nol] [Lanjutkan Demo] │  │
                        │  └────────────────────────────────────┘  │
                        │                                          │
                        │  - Lihat statistik karyawan              │
                        │  - Kelola data HR                        │
                        │  - Proses payroll                        │
                        │  - Test semua fitur admin                │
                        └──────────────────────────────────────────┘
                                          │
                    ┌─────────────────────┼─────────────────────┐
                    │                     │                     │
                    ▼                     ▼                     ▼
        ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
        │ 5A. TEST WEB     │  │ 5B. TEST MOBILE  │  │ 5C. RESET DATA   │
        │    ADMIN         │  │      APP         │  │                  │
        │                  │  │                  │  │ - Hapus semua    │
        │ - Kelola HR      │  │ - Login dengan   │  │   data demo      │
        │ - Run payroll    │  │   email yg sama  │  │ - Mulai fresh    │
        │ - Approve cuti   │  │ - Clock in/out   │  │ - Input data     │
        │ - Reports        │  │ - Face recog     │  │   real           │
        └──────────────────┘  │ - Lihat slip gaji│  └──────────────────┘
                              │ - Ajukan cuti    │
                              │ - Lihat history  │
                              └──────────────────┘
```

---

## Implementation Steps (To-Do List)

### Phase 1: Core Registration Flow Enhancement (Priority: HIGH)

#### 1.1 Update DemoDataService - Tambah Office Location
- [ ] Create default Office Location (Kantor Pusat)
- [ ] Assign semua employee ke Office Location

```php
// Create default office location
protected function createOfficeLocation(): void
{
    OfficeLocation::create([
        'company_id' => $this->company->id,
        'name' => 'Kantor Pusat',
        'code' => 'HQ',
        'address' => 'Jl. Demo No. 1, Jakarta Pusat',
        'latitude' => -6.2088,  // Jakarta coordinates
        'longitude' => 106.8456,
        'radius' => 100, // 100 meter radius
        'is_active' => true,
        'is_main' => true,
    ]);
}
```

#### 1.2 Update RegisteredUserController
- [ ] Assign multiple roles: `admin`, `hr-manager`, `employee`
- [ ] Create Employee record for the registering user
- [ ] Link User to Employee via `user_id`
- [ ] Assign Employee ke Office Location

```php
// Target code structure
$user->assignRole(['admin', 'hr-manager', 'employee']);

// Create employee record for user
$employee = Employee::create([
    'company_id' => $company->id,
    'user_id' => $user->id,
    'first_name' => $names[0],
    'last_name' => $names[1] ?? '',
    'email' => $user->email,
    'employee_number' => 'EMP' . date('Y') . '0001',
    // ... other required fields
]);

// Assign to office location
$officeLocation = OfficeLocation::where('company_id', $company->id)->first();
$employee->officeLocations()->attach($officeLocation->id);
```

#### 1.3 Update DemoDataService
- [ ] Create Office Location sebelum Employee
- [ ] Skip creating employee for user (sudah dibuat di registration)
- [ ] Create attendance records for the main user's employee
- [ ] Create payroll item for the main user's employee
- [ ] Add work schedule assignment
- [ ] Assign semua demo employees ke Office Location

### Phase 2: Demo Data Enhancement (Priority: MEDIUM)

#### 2.1 Enhance Attendance Data
- [ ] Pastikan user utama punya attendance 30 hari terakhir
- [ ] Variasi status: present, late, early leave
- [ ] Include clock in/out photos (placeholder)

#### 2.2 Add Payroll for Main User
- [ ] Slip gaji untuk 1-3 bulan terakhir
- [ ] Detail lengkap: gaji pokok, tunjangan, potongan
- [ ] PPh 21 calculation sample
- [ ] BPJS calculation sample

#### 2.3 Add Leave Data
- [ ] Leave balance untuk user utama
- [ ] Sample leave request (approved & pending)
- [ ] Leave history

#### 2.4 Add Additional Sample Data
- [ ] Reimbursement requests (pending, approved, rejected)
- [ ] Overtime requests
- [ ] Announcement samples

### Phase 3: Dashboard Demo Banner (Priority: MEDIUM)

#### 3.1 Update Demo Banner Component
- [ ] Tampilan yang lebih prominent di dashboard
- [ ] Info "Trial berakhir dalam X hari"
- [ ] Button "Mulai dari Nol" (reset data)
- [ ] Button "Upgrade ke Premium"
- [ ] Link ke dokumentasi/tutorial

#### 3.2 Dashboard Stats for Demo
- [ ] Show sample statistics
- [ ] Highlight demo features

### Phase 4: Mobile App Compatibility (Priority: HIGH)

#### 4.1 Verify API Login
- [ ] Pastikan user bisa login via `/api/v1/auth/login`
- [ ] Return employee data dalam response
- [ ] Token generation

#### 4.2 Verify Employee Endpoints
- [ ] `/api/v1/attendance/today` - return today's status
- [ ] `/api/v1/attendance/history` - return 30 day history
- [ ] `/api/v1/payslips` - return payslip list
- [ ] `/api/v1/leaves/balance` - return leave balance

#### 4.3 Face Recognition Setup
- [ ] Option untuk skip face enrollment di demo mode
- [ ] Atau provide sample enrollment flow

### Phase 5: Reset & Production Mode (Priority: LOW)

#### 5.1 Reset Data Flow
- [ ] Confirmation dialog yang jelas
- [ ] Preserve user & company data
- [ ] Delete all transactional data
- [ ] Option untuk re-seed demo data

#### 5.2 Switch to Production
- [ ] Clear all demo data
- [ ] Remove demo mode flag
- [ ] Reset employee counter
- [ ] Keep user as admin only (remove hr-manager, employee roles)

---

## Database Changes Required

### 1. Migrations (If not exists)

```php
// Already exists: companies.is_demo_mode, companies.demo_started_at
```

### 2. New Fields (Optional)

```php
// employees table - pastikan ada:
// - user_id (nullable, untuk link ke users)
// - Sudah ada ✅
```

---

## File Changes Summary

| File | Action | Description |
|------|--------|-------------|
| `app/Services/DemoDataService.php` | MODIFY | Add Office Location, employee-office assignment |
| `app/Http/Controllers/Auth/RegisteredUserController.php` | MODIFY | Add multi-role & create employee with office assignment |
| `resources/views/components/demo-mode-banner.blade.php` | MODIFY | Enhance banner UI |
| `resources/views/dashboard.blade.php` | MODIFY | Add demo info section |
| `app/Http/Controllers/Api/V1/AuthController.php` | VERIFY | Ensure employee data returned |
| `tests/Feature/Auth/RegistrationTest.php` | ADD | Test for multi-role registration |

---

## Testing Checklist

### Registration Flow
- [ ] User dapat register dengan company name & email
- [ ] User otomatis mendapat role: admin, hr-manager, employee
- [ ] Employee record dibuat untuk user
- [ ] Office Location (Kantor Pusat) dibuat otomatis
- [ ] Employee di-assign ke Office Location
- [ ] Demo data di-seed otomatis
- [ ] User redirect ke dashboard

### Web Dashboard
- [ ] Demo banner tampil dengan jelas
- [ ] Statistik karyawan tampil (termasuk user)
- [ ] Bisa akses semua menu admin
- [ ] Bisa akses employee portal
- [ ] Bisa lihat slip gaji sendiri

### Mobile App
- [ ] Login berhasil dengan email yg didaftarkan
- [ ] Employee profile tampil
- [ ] Attendance history 30 hari tampil
- [ ] Payslip list tampil
- [ ] Leave balance tampil
- [ ] Clock in/out functional

### Reset Data
- [ ] Button reset muncul di demo mode
- [ ] Confirmation dialog tampil
- [ ] Data terhapus setelah confirm
- [ ] User tetap login
- [ ] Company tetap ada
- [ ] User bisa mulai input data baru

---

## Timeline Estimate

| Phase | Tasks | Estimated |
|-------|-------|-----------|
| Phase 1 | Core Registration Enhancement | 2-3 hours |
| Phase 2 | Demo Data Enhancement | 3-4 hours |
| Phase 3 | Dashboard Banner | 1-2 hours |
| Phase 4 | Mobile API Verification | 2-3 hours |
| Phase 5 | Reset Flow | 1-2 hours |
| Testing | End-to-end testing | 2-3 hours |
| **Total** | | **11-17 hours** |

---

## Notes

1. **JagoFlutter Academy Context**: User adalah peserta kursus Flutter yang ingin test aplikasi. Mereka perlu experience yang seamless tanpa harus setup data manual.

2. **Face Recognition**: Untuk testing, bisa provide mode "skip verification" saat demo mode, atau guide untuk enrollment.

3. **Subscription**: Trial 14 hari sudah di-set di registration. Setelah expired, user harus upgrade atau data akan read-only.

4. **Multi-tenant**: Semua data sudah ter-isolasi per company via `company_id`.

---

## Related Documentation

- [README.md](../README.md) - Project overview
- [ROLES_PERMISSIONS.md](../ROLES_PERMISSIONS.md) - Role definitions
- [Mobile API Docs](../README.md#mobile-app--api) - API endpoints

---

*Last updated: 2026-02-18*
*Updated: Added auto-create data documentation and Office Location requirement*
