# Flutter Frontend Tasks: Multi-Shift & Weekly Schedule

> **Date**: 2026-04-15
> **Status**: COMPLETED
> **Backend Branch**: `ajf-gajipro-academy`
> **Flutter Project**: `/Users/bahri/development/AcademyJF/flutter_gajipro_app-ajf-gajipro`
> **Related Commit**: `a31eef2` - Sesi 4: Employee weekly schedule, attendance improvements

---

## Ringkasan Perubahan Backend

### Apa yang berubah?

1. **Tabel baru `employee_weekly_schedules`** — Karyawan bisa punya jadwal kerja berbeda setiap hari dalam seminggu (Senin=Pagi, Selasa=Siang, Rabu=Malam, dst.)
2. **Model `Employee` enhanced** — Method `resolveScheduleForDate()` menentukan jadwal berdasarkan prioritas:
   - **Prioritas 1**: Weekly schedule pattern (per-hari) → jika ada record di `employee_weekly_schedules`
   - **Prioritas 2**: Default `work_schedule_id` + pengecekan `isWorkingDay()`
   - **Prioritas 3**: `null` (hari libur / tidak ada jadwal)
3. **Attendance API sudah menggunakan `resolveScheduleForDate()`** — Clock in/out dan today endpoint sudah resolve jadwal dengan logic baru

### Apa yang BELUM ada di backend?

- **Endpoint `GET /api/v1/schedule` TIDAK ADA** — Flutter app memanggil endpoint ini tapi belum dibuat di backend
- Tidak ada API untuk shift swap, shift rotation, atau schedule assignment

---

## Analisis Impact ke Flutter

### A. CRITICAL — Endpoint Tidak Ada

| Issue | Detail |
|-------|--------|
| `GET /api/v1/schedule` | **Endpoint ini tidak ada di `routes/api.php` backend!** Flutter app memanggil `Variables.schedule` (`/api/v1/schedule`) tapi backend belum menyediakan endpoint ini. Akan selalu return 404. |

### B. Sudah Compatible (Tidak Perlu Ubah)

| Fitur | Alasan |
|-------|--------|
| Attendance Today (`GET /attendance/today`) | Sudah menggunakan `resolveScheduleForDate()`. Response tetap sama: `schedule.name`, `schedule.start_time`, `schedule.end_time` |
| Clock In/Out (`POST /attendance/clock-in/out`) | Sudah menggunakan `resolveScheduleForDate()`. Tidak ada perubahan request/response format |
| Model `AttendanceTodayModel.schedule` | Format response `schedule` tidak berubah (name, start_time, end_time). Compatible. |

### C. Perlu Update/Enhancement

| Area | Impact | Priority |
|------|--------|----------|
| Schedule Screen | Bergantung pada endpoint yang tidak ada | **CRITICAL** |
| Schedule Model | Mungkin perlu field baru (`shift_type`, `is_day_off`) | MEDIUM |
| Dashboard | Bisa tampilkan jadwal hari ini yang berbeda tiap hari | LOW |

---

## Task List untuk Flutter

### Task 1: [BACKEND] Buat Endpoint `GET /api/v1/schedule` ⚠️ PREREQUISITE

**Status**: Belum ada
**Priority**: CRITICAL
**Lokasi**: Backend Laravel

Endpoint ini HARUS dibuat di backend dulu sebelum Flutter bisa menampilkan jadwal bulanan. Endpoint harus menggunakan `resolveScheduleForDate()` untuk setiap hari dalam bulan.

**Expected Response Format** (sesuaikan dengan model Flutter existing):
```json
{
  "data": {
    "month": "2026-04",
    "month_label": "April 2026",
    "prev_month": "2026-03",
    "next_month": "2026-05",
    "days": [
      {
        "date": "2026-04-01",
        "day_name": "Rabu",
        "is_working_day": true,
        "is_holiday": false,
        "holiday_name": null,
        "schedule": {
          "id": 1,
          "name": "Shift Pagi",
          "start_time": "08:00",
          "end_time": "17:00",
          "is_overnight": false
        }
      },
      {
        "date": "2026-04-02",
        "day_name": "Kamis",
        "is_working_day": true,
        "is_holiday": false,
        "holiday_name": null,
        "schedule": {
          "id": 2,
          "name": "Shift Malam",
          "start_time": "22:00",
          "end_time": "06:00",
          "is_overnight": true
        }
      },
      {
        "date": "2026-04-06",
        "day_name": "Minggu",
        "is_working_day": false,
        "is_holiday": false,
        "holiday_name": null,
        "schedule": null
      }
    ]
  }
}
```

**Implementation Notes**:
- Loop setiap hari dalam bulan yang diminta
- Panggil `$employee->resolveScheduleForDate($date)` per hari
- Cek juga `Holiday` table untuk hari libur nasional
- Parameter: `?month=2026-04` (optional, default bulan ini)

---

### Task 2: [FLUTTER] Update Schedule Model untuk Multi-Shift

**Priority**: HIGH
**File**: `lib/data/models/responses/employee_schedule_model.dart`

Model `EmployeeScheduleShift` sudah mendukung multi-shift (setiap hari punya schedule berbeda). **Tidak perlu perubahan besar**, tapi perlu verifikasi:

- [ ] Pastikan field `is_overnight` di-handle dengan benar di UI
- [ ] Pastikan `schedule: null` (hari libur/non-kerja) ditampilkan sebagai "Libur" bukan error
- [ ] Tambahkan fallback handling jika API belum tersedia (graceful error)

---

### Task 3: [FLUTTER] Enhance Schedule Screen untuk Multi-Shift

**Priority**: HIGH
**File**: `lib/presentation/schedule/pages/schedule_screen.dart`

Saat ini schedule screen sudah menampilkan jadwal per hari. Enhancement yang dibutuhkan:

- [ ] **Tampilkan nama shift** yang berbeda per hari dengan jelas (Shift Pagi, Shift Siang, Shift Malam)
- [ ] **Color coding per shift type** — bedakan warna card berdasarkan shift (morning=kuning, afternoon=biru, night=ungu)
- [ ] **Overnight shift indicator** — tampilkan icon/badge untuk shift yang melewati tengah malam
- [ ] **Legend/keterangan** — tambahkan keterangan warna shift di bagian atas screen
- [ ] **Hari libur (schedule=null)** — pastikan ditampilkan dengan benar sebagai "Libur" atau "Tidak Ada Jadwal"

---

### Task 4: [FLUTTER] Update Attendance Screen untuk Dynamic Schedule

**Priority**: MEDIUM
**File**: `lib/presentation/attendance/pages/attendance_screen.dart`

Attendance today sudah kompatibel (API response format tidak berubah). Enhancement optional:

- [ ] **Tampilkan nama shift** di halaman attendance (contoh: "Shift Malam - 22:00 s/d 06:00")
- [ ] **Overnight shift awareness** — jika shift malam, tampilkan info bahwa clock-out akan di hari berikutnya
- [ ] **Perubahan jadwal harian** — info bahwa jadwal bisa berbeda setiap hari

---

### Task 5: [FLUTTER] Dashboard Widget: Jadwal Hari Ini

**Priority**: LOW
**File**: `lib/presentation/dashboard/` atau `lib/presentation/home/`

- [ ] Tambahkan widget "Jadwal Hari Ini" di dashboard yang menampilkan shift hari ini
- [ ] Tampilkan nama shift, waktu masuk/pulang
- [ ] Jika hari libur, tampilkan "Hari Ini Libur"
- [ ] Tap untuk navigate ke schedule screen

---

### Task 6: [FLUTTER] Weekly Schedule Summary Widget (Nice to Have)

**Priority**: LOW
**Files**: Buat widget baru

- [ ] Tampilkan ringkasan jadwal minggu ini dalam format compact
- [ ] Contoh: Sen=Pagi, Sel=Siang, Rab=Malam, Kam=Pagi, Jum=Pagi, Sab=Libur, Min=Libur
- [ ] Bisa ditempatkan di schedule screen atau profile screen

---

## Urutan Pengerjaan (Recommended)

```
1. [BACKEND] Task 1 — Buat endpoint GET /api/v1/schedule  ← HARUS DULUAN
2. [FLUTTER] Task 2 — Verifikasi & update model
3. [FLUTTER] Task 3 — Enhance schedule screen
4. [FLUTTER] Task 4 — Update attendance screen
5. [FLUTTER] Task 5 — Dashboard widget
6. [FLUTTER] Task 6 — Weekly summary widget (optional)
```

---

## Catatan Penting

1. **Backend `GET /api/v1/schedule` harus dibuat dulu** — tanpa ini, Schedule Screen di Flutter akan error 404
2. **Attendance API sudah kompatibel** — tidak ada breaking change, `resolveScheduleForDate()` sudah aktif
3. **Model Flutter sudah support multi-shift** — setiap `EmployeeScheduleDay` sudah punya `schedule` nullable yang berbeda per hari
4. **Tidak ada API untuk shift swap/rotation** — fitur ini belum diimplementasi di backend
