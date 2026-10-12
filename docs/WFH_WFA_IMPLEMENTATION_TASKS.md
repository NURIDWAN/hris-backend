# WFH & WFA Implementation Tasks

> **Date**: 2026-04-15
> **Status**: PLANNING
> **Branch**: `ajf-gajipro-academy`
> **Guide Reference**: `docs/WFH_WFA_GUIDE.md`

---

## Overview

Implementasi fitur Work From Home (WFH) dan Work From Anywhere (WFA) pada GajiPro HRIS. Fitur ini menambahkan 3 mode kerja:

| Mode | Validasi GPS | Keterangan |
|------|-------------|------------|
| **WFO** | Wajib dalam radius kantor | Mode default (sudah ada) |
| **WFH** | Wajib dalam radius rumah | Karyawan daftar lokasi rumah, radius dari setting perusahaan |
| **WFA** | Hanya rekam koordinat | Untuk dinas luar / tugas lapangan, tanpa validasi radius |

---

## Existing Infrastructure (Sudah Ada)

- [x] GPS coordinate storage di `attendances` table (clock_in_latitude/longitude)
- [x] Haversine distance calculation di `OfficeLocation::distanceTo()` dan `GpsValidationService`
- [x] Office location radius validation (`OfficeLocation::isWithinRadius()`)
- [x] Employee-Office assignment (`employee_office_locations` pivot)
- [x] `GpsValidationService::validateEmployeeLocation()` untuk WFO
- [x] `Employee::resolveScheduleForDate()` untuk multi-shift schedule
- [x] Company timezone support (`Company::now()`, `Company::today()`)
- [x] Attendance settings page (`settings/attendance`)
- [x] API & Web attendance clock in/out

---

## Phase 1: Database & Models

### Task 1.1: Migration — Add `wfh_radius` to `companies` table
- [ ] Buat migration `add_wfh_radius_to_companies_table`
- [ ] Kolom: `wfh_radius` integer unsigned, default 100 (meter)
- [ ] Tambahkan ke `Company` model `$fillable` dan `casts()`

### Task 1.2: Migration — Create `employee_home_locations` table
- [ ] Buat migration `create_employee_home_locations_table`
- [ ] Kolom:
  - `id` (PK)
  - `company_id` (FK → companies, cascade delete)
  - `employee_id` (FK → employees, cascade delete)
  - `latitude` decimal(10,8)
  - `longitude` decimal(11,8)
  - `address` text nullable (alamat deskriptif)
  - `registered_at` timestamp
  - `reset_by` FK → users nullable (admin yang reset)
  - `reset_at` timestamp nullable
  - `timestamps`
- [ ] Unique constraint: `(employee_id)` — satu lokasi per karyawan
- [ ] Index: `company_id`

### Task 1.3: Migration — Create `employee_schedules` table (WFH/WFA daily schedule)
- [ ] Buat migration `create_employee_work_mode_schedules_table`
- [ ] Kolom:
  - `id` (PK)
  - `company_id` (FK → companies, cascade delete)
  - `employee_id` (FK → employees, cascade delete)
  - `date` date
  - `work_mode` enum('wfh', 'wfa')
  - `notes` text nullable
  - `status` enum('pending', 'approved', 'rejected'), default 'pending'
  - `requested_by` FK → users nullable
  - `approved_by` FK → users nullable
  - `approved_at` timestamp nullable
  - `rejection_reason` text nullable
  - `timestamps`
  - `softDeletes`
- [ ] Unique constraint: `(employee_id, date)` — satu jadwal per hari per karyawan
- [ ] Indexes: `company_id`, `(employee_id, date, status)`, `(company_id, date)`

### Task 1.4: Migration — Add `work_mode` to `attendances` table
- [ ] Buat migration `add_work_mode_to_attendances_table`
- [ ] Kolom: `work_mode` enum('wfo', 'wfh', 'wfa'), default 'wfo'
- [ ] Tambahkan setelah kolom `date`

### Task 1.5: Model — `EmployeeHomeLocation`
- [ ] Buat model dengan factory
- [ ] Relationships: `company()`, `employee()`, `resetBy()`
- [ ] Method: `distanceTo(lat, lng)` (Haversine), `isWithinRadius(lat, lng, radius)`
- [ ] Scope: `forCompany()`, `forEmployee()`
- [ ] `LogsActivityTrait` untuk audit trail
- [ ] Factory dengan states

### Task 1.6: Model — `EmployeeWorkModeSchedule`
- [ ] Buat model dengan factory
- [ ] Relationships: `company()`, `employee()`, `requestedBy()`, `approvedBy()`
- [ ] Scopes: `forCompany()`, `forDate()`, `approved()`, `pending()`, `byMode()`
- [ ] Accessors: `status_label`, `work_mode_label`, `status_color`
- [ ] Method: `isApproved()`, `isPending()`, `isRejected()`
- [ ] `LogsActivityTrait`, `SoftDeletes`
- [ ] Factory dengan states: `approved()`, `rejected()`, `wfh()`, `wfa()`

### Task 1.7: Update `Attendance` model
- [ ] Tambahkan `work_mode` ke `$fillable` dan `casts()`
- [ ] Tambahkan accessor `work_mode_label` (WFO/WFH/WFA dalam Bahasa Indonesia)

### Task 1.8: Update `Company` model
- [ ] Tambahkan `wfh_radius` ke `$fillable` dan `casts()` (integer)

---

## Phase 2: Backend Logic & Services

### Task 2.1: Update `GpsValidationService` — Add WFH/WFA validation
- [ ] Method: `validateWfhLocation(Employee, lat, lng): array`
  - Get employee home location dari `EmployeeHomeLocation`
  - Get company `wfh_radius`
  - Calculate distance, return `{valid, distance, reason}`
- [ ] Method: `validateWfaLocation(lat, lng): array`
  - Always valid, return `{valid: true, distance: null}`
- [ ] Method: `resolveWorkMode(Employee, date): string`
  - Check `EmployeeWorkModeSchedule` untuk hari ini (approved)
  - Return 'wfh', 'wfa', atau 'wfo' (default)
- [ ] Method: `validateByWorkMode(Employee, lat, lng, workMode): array`
  - Route ke validasi yang sesuai berdasarkan work_mode

### Task 2.2: Form Request — `StoreEmployeeWorkModeScheduleRequest`
- [ ] Validasi: employee_id, date, work_mode, notes
- [ ] Rule: date harus future atau hari ini
- [ ] Rule: employee_id harus milik tenant
- [ ] Rule: unique (employee_id, date) — tidak boleh duplikat

### Task 2.3: Form Request — `StoreEmployeeHomeLocationRequest`
- [ ] Validasi: latitude, longitude, address
- [ ] Rule: latitude -90 to 90, longitude -180 to 180

---

## Phase 3: Admin Controllers & Views (Web)

### Task 3.1: Controller — `EmployeeWorkModeScheduleController` (Admin)
- [ ] `index()` — List jadwal WFH/WFA dengan filter (tanggal, karyawan, status, mode)
- [ ] `create()` — Form tambah jadwal
- [ ] `store()` — Simpan jadwal (bisa langsung approved oleh admin)
- [ ] `show()` — Detail jadwal
- [ ] `approve()` — Approve jadwal pending
- [ ] `reject()` — Reject jadwal pending dengan alasan
- [ ] `destroy()` — Hapus jadwal (soft delete)
- [ ] Semua scoped by `company_id`

### Task 3.2: Views — Employee Work Mode Schedule (Admin)
- [ ] `employee-schedules/index.blade.php` — Tabel dengan filter, badge status/mode
- [ ] `employee-schedules/create.blade.php` — Form: pilih karyawan, tanggal, mode, notes
- [ ] `employee-schedules/show.blade.php` — Detail dengan tombol approve/reject

### Task 3.3: Controller — `EmployeeHomeLocationController` (Admin)
- [ ] `index()` — List lokasi rumah karyawan
- [ ] `show()` — Detail lokasi dengan peta
- [ ] `reset()` — Reset lokasi rumah karyawan (agar bisa daftar ulang)

### Task 3.4: Views — Employee Home Location (Admin)
- [ ] `employee-home-locations/index.blade.php` — Tabel lokasi karyawan
- [ ] `employee-home-locations/show.blade.php` — Detail dengan peta (optional)

### Task 3.5: Update Attendance Settings
- [ ] Tambahkan field "Radius Toleransi WFH" di halaman settings attendance
- [ ] Input number dalam meter, default 100
- [ ] Simpan ke `companies.wfh_radius`

### Task 3.6: Update Attendance Views
- [ ] Tampilkan badge `work_mode` (WFO/WFH/WFA) di attendance index/show
- [ ] Filter attendance by work_mode
- [ ] Tampilkan work_mode di attendance report

### Task 3.7: Route Registration (Web)
- [ ] Resource route `employee-schedules` dengan middleware permission
- [ ] Routes untuk approve/reject
- [ ] Resource route `employee-home-locations` (index, show, reset)
- [ ] Sidebar navigation items

---

## Phase 4: Employee Portal (Web)

### Task 4.1: Controller — `Portal/HomeLocationController`
- [ ] `index()` — Tampilkan lokasi rumah terdaftar atau form pendaftaran
- [ ] `store()` — Simpan lokasi rumah (satu kali, cek belum ada)
- [ ] Validasi: hanya bisa register 1x, selanjutnya harus minta admin reset

### Task 4.2: Views — Portal Home Location
- [ ] `portal/home-location/index.blade.php`
  - Jika belum daftar: form dengan button "Simpan Lokasi WFH" + current GPS
  - Jika sudah daftar: tampilkan koordinat, alamat, tanggal daftar

### Task 4.3: Update Portal Attendance Controller
- [ ] Detect work mode dari `EmployeeWorkModeSchedule` hari ini
- [ ] Tampilkan mode kerja di halaman absensi portal
- [ ] Validasi clock-in berdasarkan work_mode

### Task 4.4: Portal Routes
- [ ] `GET /portal/home-location` — index
- [ ] `POST /portal/home-location` — store
- [ ] Sidebar navigation item "Lokasi WFH"

---

## Phase 5: API (Mobile Flutter)

### Task 5.1: API Controller — `Api/V1/ScheduleController` enhancement
- [ ] Tambahkan `work_mode` di response `GET /api/v1/schedule`
  - Setiap hari: check `EmployeeWorkModeSchedule` → return `work_mode: 'wfo'|'wfh'|'wfa'`
- [ ] Endpoint baru: `GET /api/v1/work-mode-schedules`
  - List jadwal WFH/WFA karyawan (upcoming + history)
- [ ] Endpoint baru: `POST /api/v1/work-mode-schedules`
  - Request jadwal WFH/WFA (status pending)

### Task 5.2: API Controller — `Api/V1/HomeLocationController`
- [ ] `GET /api/v1/home-location` — Get registered home location
- [ ] `POST /api/v1/home-location` — Register home location (one-time)

### Task 5.3: Update `Api/V1/AttendanceController` — clockIn
- [ ] Resolve work_mode dari schedule
- [ ] Validasi GPS berdasarkan work_mode:
  - WFO: existing logic (office radius)
  - WFH: validate against home location + company wfh_radius
  - WFA: skip radius validation, hanya record coordinates
- [ ] Simpan `work_mode` di attendance record
- [ ] Return `work_mode` di response

### Task 5.4: Update `Api/V1/AttendanceController` — today
- [ ] Return `work_mode` di response today endpoint
- [ ] Return info "Hari ini Anda bekerja dari: Rumah/Kantor/Anywhere"

### Task 5.5: API Route Registration
- [ ] `GET /api/v1/home-location`
- [ ] `POST /api/v1/home-location`
- [ ] `GET /api/v1/work-mode-schedules`
- [ ] `POST /api/v1/work-mode-schedules`

---

## Phase 6: Flutter Frontend

### Task 6.1: Model — WorkModeSchedule & HomeLocation
- [ ] `EmployeeWorkModeScheduleModel` (id, date, work_mode, status, notes)
- [ ] `HomeLocationModel` (latitude, longitude, address, registered_at)

### Task 6.2: Datasource — Home Location & Work Mode
- [ ] `HomeLocationRemoteDatasource` (get, register)
- [ ] `WorkModeScheduleRemoteDatasource` (list, create)

### Task 6.3: BLoC — Home Location
- [ ] `HomeLocationBloc` (load, register)
- [ ] States: initial, loading, loaded, not_registered, error

### Task 6.4: Screen — Home Location Registration
- [ ] Halaman register lokasi rumah
- [ ] Tampilkan GPS current location
- [ ] Button "Simpan Lokasi WFH"
- [ ] Jika sudah terdaftar, tampilkan info lokasi

### Task 6.5: Update Schedule Screen
- [ ] Tampilkan badge work_mode (WFO/WFH/WFA) per hari di jadwal
- [ ] Color coding: WFO=biru, WFH=hijau, WFA=ungu

### Task 6.6: Update Attendance Screen
- [ ] Tampilkan mode kerja hari ini (WFO/WFH/WFA)
- [ ] Jika WFH: info "Absensi dari rumah"
- [ ] Jika WFA: info "Absensi dari mana saja"
- [ ] Skip office location validation untuk WFH/WFA mode

### Task 6.7: Request WFH/WFA Screen
- [ ] Form request jadwal WFH/WFA
- [ ] Pilih tanggal, mode (WFH/WFA), catatan
- [ ] List riwayat request dengan status

---

## Phase 7: Testing

### Task 7.1: Unit Tests — Models
- [ ] `EmployeeHomeLocation` — distanceTo, isWithinRadius
- [ ] `EmployeeWorkModeSchedule` — scopes, accessors, states

### Task 7.2: Feature Tests — WFH/WFA Schedule Management
- [ ] Admin CRUD employee work mode schedules
- [ ] Approve/reject workflow
- [ ] Tenant isolation
- [ ] Validation rules

### Task 7.3: Feature Tests — Home Location
- [ ] Admin view/reset employee locations
- [ ] Portal register location (one-time)
- [ ] Tenant isolation

### Task 7.4: Feature Tests — Attendance with Work Mode
- [ ] Clock in WFO — existing validation (must be in office radius)
- [ ] Clock in WFH — validate against home location + wfh_radius
- [ ] Clock in WFA — accept any location
- [ ] Clock in WFH tanpa home location terdaftar → error
- [ ] Clock in WFH di luar radius rumah → error
- [ ] Work mode recorded in attendance

### Task 7.5: API Tests — Schedule & Home Location
- [ ] `GET /api/v1/home-location` — authenticated, not registered, registered
- [ ] `POST /api/v1/home-location` — register, duplicate prevention
- [ ] `GET /api/v1/work-mode-schedules` — list, filter
- [ ] `POST /api/v1/work-mode-schedules` — create request
- [ ] Attendance clockIn with work_mode validation

---

## Urutan Pengerjaan (Recommended)

```
Phase 1 → Phase 2 → Phase 7 (partial) → Phase 3 → Phase 4 → Phase 5 → Phase 6 → Phase 7 (complete)
```

| Phase | Priority | Effort | Dependency |
|-------|----------|--------|------------|
| 1. Database & Models | CRITICAL | Medium | None |
| 2. Backend Logic & Services | CRITICAL | Medium | Phase 1 |
| 7. Testing (model & service) | HIGH | Low | Phase 1-2 |
| 3. Admin Web | HIGH | High | Phase 1-2 |
| 4. Employee Portal Web | HIGH | Medium | Phase 1-2 |
| 5. API Mobile | HIGH | Medium | Phase 1-2 |
| 6. Flutter Frontend | MEDIUM | High | Phase 5 |
| 7. Testing (complete) | HIGH | Medium | All |

---

## Catatan Penting

1. **Naming**: Gunakan `EmployeeWorkModeSchedule` (bukan `EmployeeSchedule`) untuk menghindari konflik dengan `EmployeeWeeklySchedule` yang sudah ada
2. **Satu lokasi per karyawan**: `employee_home_locations` unique per employee_id. Reset oleh admin untuk ganti lokasi
3. **WFO tetap default**: Jika tidak ada `EmployeeWorkModeSchedule` approved untuk hari itu, mode = WFO
4. **Approval required**: Jadwal WFH/WFA harus di-approve oleh admin/HR sebelum bisa dipakai absensi
5. **Backward compatible**: Semua attendance yang sudah ada otomatis `work_mode = 'wfo'`
6. **Company-level radius**: `wfh_radius` di level company (global), bukan per karyawan
