# Sesi 3: Setup AI IDE & Cara Prompt Agent untuk Memahami Codebase

> **Durasi**: 2-3 jam
> **Prasyarat**: Sudah paham struktur project (Sesi 2)
> **Tujuan**: Setup AI-powered IDE dan kuasai teknik prompting agar AI agent bisa bantu development secara efektif

---

## Daftar Isi

1. [Kenapa AI IDE?](#1-kenapa-ai-ide)
2. [Landscape AI IDE 2025-2026](#2-landscape-ai-ide-2025-2026)
3. [Setup Claude Code (CLI)](#3-setup-claude-code-cli)
4. [Setup Antigravity (Google)](#4-setup-antigravity-google)
5. [Setup VS Code + Extensions AI](#5-setup-vs-code--extensions-ai)
6. [Memahami Konsep AI Agent vs Autocomplete](#6-memahami-konsep-ai-agent-vs-autocomplete)
7. [CLAUDE.md - Instruksi Permanen untuk AI](#7-claudemd---instruksi-permanen-untuk-ai)
8. [Rules Files - Konteks Spesifik Project](#8-rules-files---konteks-spesifik-project)
9. [Teknik Prompting: Baca & Pahami Codebase](#9-teknik-prompting-baca--pahami-codebase)
10. [Teknik Prompting: Navigasi & Eksplorasi](#10-teknik-prompting-navigasi--eksplorasi)
11. [Teknik Prompting: Analisis & Debugging](#11-teknik-prompting-analisis--debugging)
12. [Teknik Prompting: Menulis Kode Baru](#12-teknik-prompting-menulis-kode-baru)
13. [Teknik Prompting: Testing dengan TDD](#13-teknik-prompting-testing-dengan-tdd)
14. [Anti-Pattern: Kesalahan Umum Prompting](#14-anti-pattern-kesalahan-umum-prompting)
15. [Workflow Harian dengan AI IDE](#15-workflow-harian-dengan-ai-ide)
16. [Latihan Praktik](#16-latihan-praktik)

---

## 1. Kenapa AI IDE?

### Masalah Tradisional

```
Developer baru join project GajiPro:
- 247 PHP files, 59 models, 105 controllers
- 72 migrations, 63+ tabel database
- Multi-tenant architecture yang unik
- Regulasi Indonesia (PPh 21, BPJS, THR)

Waktu onboarding tradisional: 2-4 minggu
Waktu onboarding dengan AI IDE: 2-4 hari
```

### Apa yang AI IDE Bisa Lakukan

| Kemampuan | Contoh |
|-----------|--------|
| **Baca & pahami** codebase | "Jelaskan bagaimana payroll calculation bekerja" |
| **Cari** file/fungsi/pattern | "Di mana tenant isolation diterapkan?" |
| **Tulis** kode sesuai konvensi | "Buat CRUD controller untuk modul baru" |
| **Debug** error | "Kenapa test ini gagal?" |
| **Refactor** | "Pindahkan logic ini ke service" |
| **Testing** | "Buatkan test untuk fitur ini" |
| **Explain** | "Jelaskan alur approval workflow step by step" |

### Apa yang AI IDE TIDAK Bisa (dan Perlu Kamu)

| Keterbatasan | Kenapa |
|-------------|--------|
| Keputusan arsitektur besar | Butuh pemahaman bisnis & trade-off |
| Desain UI/UX | Butuh taste & empati pengguna |
| Requirement gathering | Butuh komunikasi dengan stakeholder |
| Final review | AI bisa salah, kamu yang bertanggung jawab |
| Domain knowledge Indonesia | PPh 21 berubah tiap tahun, perlu verifikasi |

---

## 2. Landscape AI IDE 2025-2026

### Perbandingan Tools

| Tool | Tipe | Kelebihan | Kekurangan | Harga |
|------|------|-----------|------------|-------|
| **Claude Code** | CLI Agent | Paling powerful untuk codebase besar, agentic, bisa jalankan command | Terminal-based, learning curve | $20/bulan (Pro) |
| **Antigravity (Google)** | Full IDE | AI-powered IDE dari Google, integrated Gemini, cloud-based | Masih baru, fitur berkembang | Free / berbayar |
| **GitHub Copilot** | Extension | Autocomplete cepat, integrasi GitHub | Kurang agentic, konteks terbatas | $10/bulan |
| **Windsurf** | Full IDE | Agentic, Cascade flow | Masih baru, ekosistem kecil | $15/bulan |
| **Cline** | VS Code Extension | Open source, multi-model | Setup manual, perlu API key sendiri | Bayar per API |

### Rekomendasi untuk GajiPro

```
Pilihan 1 (Recommended): Claude Code CLI + VS Code
- Claude Code untuk task besar (fitur baru, debug, refactor)
- VS Code untuk editing biasa & review

Pilihan 2: Antigravity (Google)
- AI-powered IDE dari Google dengan Gemini integration
- Cloud-based, bisa diakses dari browser
- Cocok yang mau ecosystem Google

Pilihan 3: VS Code + Cline/Copilot
- Lebih murah, fleksibel
- Perlu setup lebih banyak
```

---

## 3. Setup Claude Code (CLI)

### Instalasi

```bash
# macOS / Linux
npm install -g @anthropic-ai/claude-code

# Verifikasi
claude --version

# Login (akan buka browser)
claude

# Pilih plan:
# - Claude Pro ($20/bulan) - recommended
# - Claude Max ($100/bulan) - untuk heavy usage
# - API Key - bayar per token
```

### Cara Menjalankan

```bash
# Buka terminal di root project
cd /path/to/ultimate-jagogaji-system

# Jalankan Claude Code
claude

# Claude akan otomatis:
# 1. Baca CLAUDE.md di root project
# 2. Baca .claude/rules/*.md
# 3. Memahami konteks project
# 4. Siap menerima perintah
```

### Perintah Dasar Claude Code

```bash
# Di dalam sesi Claude Code:

# Tanya tentang codebase
> Jelaskan struktur project ini

# Baca file
> Baca file app/Http/Middleware/SetTenant.php

# Cari sesuatu
> Cari semua controller yang handle payroll

# Buat fitur baru
> Buat CRUD untuk modul training karyawan

# Jalankan command
> Jalankan php artisan test --compact

# Commit changes
> /commit

# Slash commands
> /help          # Bantuan
> /clear         # Bersihkan history
> /compact       # Compress context
```

### Konfigurasi Claude Code

```bash
# File konfigurasi global
~/.claude/CLAUDE.md          # Instruksi global (semua project)

# File konfigurasi project
project/CLAUDE.md            # Instruksi project-specific
project/.claude/rules/*.md   # Rules detail per topik
project/.claude/settings.json # Settings
```

### MCP Server (Model Context Protocol)

Claude Code bisa terhubung ke MCP server untuk akses tools tambahan:

```
GajiPro sudah setup: Laravel Boost MCP
├── database-schema  → Lihat struktur tabel
├── database-query   → Query database langsung
├── tinker           → Jalankan PHP code
├── search-docs      → Cari dokumentasi Laravel
├── list-routes      → Lihat semua routes
├── read-log-entries → Baca log aplikasi
├── browser-logs     → Baca log browser
└── last-error       → Lihat error terakhir
```

---

## 4. Setup Antigravity (Google)

### Apa itu Antigravity?

Antigravity adalah AI-powered IDE dari Google yang terintegrasi dengan Gemini AI. IDE ini berbasis cloud dan bisa diakses langsung dari browser, membuatnya mudah digunakan tanpa instalasi berat.

### Cara Akses

```
1. Buka https://idx.google.com (Firebase Studio / Antigravity)
2. Login dengan akun Google
3. Buat workspace baru atau import dari GitHub
4. Pilih template Laravel atau blank workspace
5. IDE akan terbuka di browser dengan environment lengkap
```

### Fitur Utama Antigravity

```
Gemini Chat     → Tanya-jawab tentang kode (sidebar AI chat)
Inline Assist   → AI suggestions langsung di editor
Multi-file Edit → Edit beberapa file sekaligus dengan AI
Terminal        → Integrated terminal untuk jalankan command
Preview         → Live preview aplikasi langsung di browser
```

### Konfigurasi Antigravity untuk GajiPro

```
- Antigravity bisa membaca konteks project secara otomatis
- Import project dari GitHub repository
- Setup environment (PHP, Node.js, MySQL) via Nix configuration
- Gemini AI sudah bisa memahami struktur Laravel project
```

### Tips Antigravity

```
1. Gunakan Gemini Chat untuk tanya tentang codebase:
   "Jelaskan bagaimana EmployeeController bekerja"

2. Inline suggestions:
   Ketik kode dan Gemini akan suggest completion

3. Multi-file editing:
   "Buat CRUD lengkap: model, migration, controller, views, test"

4. Review changes sebelum apply:
   Selalu baca diff sebelum accept

5. Cloud advantage:
   - Tidak perlu setup lokal
   - Bisa diakses dari mana saja
   - Environment konsisten untuk semua developer
```

---

## 5. Setup VS Code + Extensions AI

### Extensions yang Direkomendasikan

```
AI Extensions:
├── GitHub Copilot          # Autocomplete AI
├── Cline                   # Agentic AI (alternative to Claude Code)
├── Continue                # Open source AI assistant
└── Claude Code (official)  # VS Code integration for Claude Code

Laravel Extensions:
├── Laravel Extra Intellisense
├── Laravel Blade Snippets
├── PHP Intelephense
├── Laravel Pint
└── Pest Snippets

Flutter Extensions:
├── Dart
├── Flutter
└── Bloc

General:
├── GitLens
├── Error Lens
├── Tailwind CSS IntelliSense
└── Alpine.js IntelliSense
```

### Settings VS Code untuk GajiPro

```json
// .vscode/settings.json
{
  "editor.formatOnSave": true,
  "php.validate.executablePath": "/usr/local/bin/php",
  "[php]": {
    "editor.defaultFormatter": "open-tools.pint"
  },
  "files.associations": {
    "*.blade.php": "blade"
  },
  "tailwindCSS.includeLanguages": {
    "blade": "html"
  }
}
```

---

## 6. Memahami Konsep AI Agent vs Autocomplete

### Autocomplete (GitHub Copilot, Tab completion)

```
Kamu ketik:     function calculateNet
AI suggest:     function calculateNetSalary($gross, $tax) {
                    return $gross - $tax;
                }

Karakteristik:
- Reaktif (merespons apa yang kamu ketik)
- Konteks terbatas (file yang sedang dibuka)
- Cepat, inline
- Cocok untuk: boilerplate, pattern repetitif
```

### AI Agent (Claude Code, Antigravity Gemini, Cline)

```
Kamu perintah:  "Buat fitur overtime request lengkap dengan
                 model, migration, controller, form request,
                 views, dan test. Ikuti pattern yang ada
                 di modul leave request."

AI melakukan:
1. Baca modul leave request sebagai referensi
2. Baca konvensi di CLAUDE.md
3. Buat migration → Model → Controller → Form Request
4. Buat views (index, create, edit, show)
5. Buat test
6. Jalankan pint untuk formatting
7. Jalankan test untuk verifikasi

Karakteristik:
- Proaktif (bisa eksekusi multi-step)
- Konteks luas (seluruh codebase)
- Bisa jalankan command (test, migration, dll)
- Cocok untuk: fitur baru, refactoring, debugging
```

### Kapan Pakai Mana?

```
┌──────────────────┬────────────────────────────────────┐
│   Autocomplete   │          AI Agent                  │
├──────────────────┼────────────────────────────────────┤
│ Nulis 1-5 baris  │ Buat fitur baru multi-file         │
│ Boilerplate      │ Debug error kompleks               │
│ Variable naming  │ Refactor arsitektur                 │
│ Import statement │ Pahami codebase                     │
│ Simple function  │ Migrasi / upgrade                   │
│ Comment          │ Tulis test suite                    │
└──────────────────┴────────────────────────────────────┘
```

---

## 7. CLAUDE.md - Instruksi Permanen untuk AI

### Apa itu CLAUDE.md?

CLAUDE.md adalah file instruksi yang **otomatis dibaca** oleh Claude Code setiap kali sesi dimulai. Ini seperti "onboarding document" untuk AI.

### Struktur CLAUDE.md GajiPro

```
CLAUDE.md (root project)
│
├── Project Overview
│   "GajiPro adalah HRIS/Payroll SaaS multi-tenant"
│
├── Tech Stack
│   Laravel 12, Blade, Alpine.js, Tailwind CSS 4, Pest
│
├── Development Approach - TDD
│   Red → Green → Refactor
│
├── Form Components Standard
│   Semua input pakai class .input
│   Prefix (Rp) pakai inline style
│
├── UI Components
│   Buttons: .btn .btn-primary
│   Badges: <x-badge type="success">
│   Cards: .card .card-header .card-body
│
├── Multi-Tenant Architecture
│   $tenant = app('tenant')
│   Always filter by company_id
│
├── Confirmation Dialog
│   Pakai <x-confirm-dialog />
│   Trigger: $dispatch('confirm-dialog', {...})
│
├── Naming Conventions
│   Routes: dot.notation
│   Views: kebab-case folders
│   Models: PascalCase
│
└── Language
    UI: Bahasa Indonesia
    Code: English
```

### Kenapa CLAUDE.md Penting?

```
Tanpa CLAUDE.md:
> "Buat form input untuk nama karyawan"
AI buat: <input class="form-control"> (Bootstrap style!)

Dengan CLAUDE.md:
> "Buat form input untuk nama karyawan"
AI buat: <input class="input w-full @error('full_name') border-danger-500 @enderror">
         (Sesuai konvensi GajiPro!)
```

### Tips Menulis CLAUDE.md yang Efektif

```
✅ DO:
- Tulis konvensi yang SPESIFIK dan KONKRET
- Sertakan contoh kode yang bisa di-copy
- Update ketika konvensi berubah
- Pisahkan ke .claude/rules/ jika terlalu panjang

❌ DON'T:
- Jangan terlalu panjang (AI punya context limit)
- Jangan tulis hal yang obvious
- Jangan duplikasi dengan framework docs
- Jangan tulis requirement yang berubah-ubah
```

---

## 8. Rules Files - Konteks Spesifik Project

### Struktur .claude/rules/ di GajiPro

```
.claude/rules/
├── 01-overview.md      # Project overview, tech stack, modules
├── 02-design.md        # Color palette, typography, layouts
├── 03-components.md    # Blade components & form patterns
├── 04-formatting.md    # Currency, date, number formatting
├── 05-controllers.md   # Controller patterns & service layer
├── 06-models.md        # Model conventions & relationships
├── 07-database.md      # Schema, migrations, naming
├── 08-views.md         # Blade view architecture
├── 09-security.md      # Multi-tenant, RBAC, attack detection
├── 10-testing.md       # Pest PHP testing standards
├── 11-code-style.md    # PSR-12, naming, anti-patterns
├── 12-api.md           # API design, resources, responses
└── 13-system-features.md # Roadmap & system features
```

### Bagaimana AI Menggunakan Rules

```
Kamu: "Buat migration untuk tabel trainings"

AI membaca rules:
- 07-database.md → Konvensi kolom (company_id, is_*, *_at)
- 07-database.md → Tipe data (DECIMAL(15,2) untuk money)
- 07-database.md → Foreign key conventions
- 09-security.md → HARUS ada company_id untuk tenant isolation

AI menghasilkan migration yang sesuai semua rules.
```

### Cara Kerja Konteks AI

```
┌────────────────────────────────────────────┐
│           AI Context Window                 │
│                                             │
│  [CLAUDE.md]          ← Always loaded       │
│  [.claude/rules/*]    ← Always loaded       │
│  [Conversation]       ← Your prompts        │
│  [Files read]         ← Files AI opened     │
│  [Tool results]       ← Command output      │
│                                             │
│  Total: ~200K tokens (Claude Opus)          │
│         ~128K tokens (Claude Sonnet)        │
└────────────────────────────────────────────┘

Semakin banyak konteks relevan → semakin akurat output AI
```

---

## 9. Teknik Prompting: Baca & Pahami Codebase

### Level 1: Pertanyaan Umum

```
Prompt: "Jelaskan arsitektur project ini secara high-level"

AI akan:
- Baca CLAUDE.md dan rules
- Scan struktur direktori
- Merangkum tech stack, patterns, modules
```

```
Prompt: "Apa saja modul utama di project ini dan bagaimana
         mereka saling terhubung?"

AI akan:
- List semua module
- Jelaskan relasi antar module
- Gambar dependency diagram
```

### Level 2: Pemahaman Modul Spesifik

```
Prompt: "Jelaskan bagaimana modul payroll bekerja dari awal
         sampai akhir. Mulai dari setup salary component
         sampai karyawan menerima gaji."

AI akan:
- Baca PayrollController, PayrollCalculationService
- Baca model Payroll, PayrollItem, PayrollItemDetail
- Baca SalaryComponent, EmployeeSalary
- Trace flow: Draft → Process → Approve → Pay
- Jelaskan setiap step dengan detail
```

```
Prompt: "Bagaimana alur approval leave request?
         Trace dari employee submit sampai approved/rejected.
         Tunjukkan file-file yang terlibat."

AI akan:
- Baca LeaveRequestController (web + API + portal)
- Baca ApprovalWorkflow, ApprovalWorkflowStep
- Baca event LeaveRequestApproved/Rejected
- Baca listener SendLeaveApprovalNotification
- Trace complete flow
```

### Level 3: Pemahaman Detail Teknis

```
Prompt: "Jelaskan bagaimana PPh 21 dihitung menggunakan
         metode TER di PayrollCalculationService.
         Sertakan contoh kalkulasi angka."

AI akan:
- Baca PayrollCalculationService
- Baca Pph21Setting, Pph21TerRate, PtkpSetting
- Jelaskan formula TER
- Berikan contoh: gaji 10jt → PTKP TK/0 → tarif TER → pajak
```

```
Prompt: "Bagaimana face recognition bekerja di attendance?
         Trace dari Flutter app sampai backend verification."

AI akan:
- Flutter: FaceRecognitionService, TFLite model
- Flutter: AttendanceBloc → clockIn with face data
- API: /api/v1/attendance/clock-in
- Backend: FaceRecognitionService
- Database: employee_face_embeddings
```

### Template Prompt untuk Memahami Codebase

```
Template 1 - Pahami Modul:
"Jelaskan modul [NAMA_MODUL] secara detail:
1. File-file utama yang terlibat (model, controller, service, view)
2. Database tables dan relasinya
3. Alur data dari request sampai response
4. Business rules yang diterapkan
5. Bagaimana tenant isolation diterapkan"

Template 2 - Pahami Relasi:
"Bagaimana [MODUL A] dan [MODUL B] saling terhubung?
Tunjukkan file dan method yang menjadi penghubung."

Template 3 - Pahami Pattern:
"Cari semua tempat di codebase yang menggunakan pattern [X].
Jelaskan kenapa pattern ini dipakai dan bagaimana cara kerjanya."
```

---

## 10. Teknik Prompting: Navigasi & Eksplorasi

### Cari File & Kode

```
"Cari semua controller yang berhubungan dengan payroll"

"Di file mana tenant middleware didefinisikan dan diregistrasikan?"

"Cari semua tempat yang memanggil PayrollCalculationService"

"List semua API endpoint yang bisa diakses employee role"

"Cari semua model yang menggunakan SoftDeletes trait"
```

### Cari Pattern & Konvensi

```
"Bagaimana pattern form validation di project ini?
 Tunjukkan 3 contoh Form Request yang berbeda."

"Bagaimana pattern error handling di API controllers?
 Apakah konsisten di semua controller?"

"Cari semua tempat yang melakukan currency formatting.
 Apakah ada helper function atau dilakukan manual?"
```

### Cari Inkonsistensi

```
"Cek apakah semua controller yang handle tenant data
 sudah menggunakan app('tenant') dan filter company_id.
 Laporkan jika ada yang belum."

"Cek apakah semua form request sudah scope
 unique/exists rules dengan company_id"

"Cari controller yang TIDAK punya test file"
```

### Mapping Codebase

```
"Buatkan diagram dependency antar service.
 Service mana yang memanggil service lain?"

"Buatkan list semua event dan listener beserta triggernya"

"Buatkan mapping route → controller → view untuk modul employee"
```

---

## 11. Teknik Prompting: Analisis & Debugging

### Debug Error

```
"Saya dapat error ini saat menjalankan payroll process:
[paste error message]
Analisis penyebabnya dan berikan solusi."

"Test ini gagal:
[paste test output]
Jelaskan kenapa gagal dan cara fix-nya."

"User melaporkan bahwa leave balance tidak berkurang
setelah leave request di-approve. Investigasi masalahnya."
```

### Analisis Performance

```
"Analisis query di PayrollController@index.
 Apakah ada potensi N+1 query problem?
 Suggest eager loading yang diperlukan."

"Cek DashboardController. Apakah ada query
 yang bisa di-cache untuk improve performance?"
```

### Analisis Security

```
"Review EmployeeController untuk potential security issues:
1. Apakah tenant isolation sudah benar?
2. Apakah ada mass assignment vulnerability?
3. Apakah authorization sudah diterapkan?"

"Cek apakah semua file upload sudah divalidasi
 MIME type dan size limit"
```

### Template Debug

```
Template - Debug Error:
"Error: [PASTE ERROR]
File: [FILE YANG ERROR]
Langkah reproduce: [LANGKAH-LANGKAH]

Tolong:
1. Analisis root cause
2. Berikan solusi
3. Pastikan solusi mengikuti konvensi project"

Template - Investigasi Bug:
"Bug: [DESKRIPSI BUG]
Expected: [YANG SEHARUSNYA]
Actual: [YANG TERJADI]

Investigasi:
1. Trace alur kode yang terlibat
2. Identifikasi di mana logic salah
3. Propose fix dengan test"
```

---

## 12. Teknik Prompting: Menulis Kode Baru

### Fitur Baru - Berikan Konteks Cukup

```
❌ Prompt BURUK:
"Buat fitur training karyawan"

✅ Prompt BAIK:
"Buat modul Employee Training Management:

Requirement:
- Admin bisa CRUD training programs
- Admin bisa assign karyawan ke training
- Karyawan bisa lihat training yang diassign di portal
- Training punya: nama, deskripsi, tanggal mulai/selesai,
  trainer, lokasi, max peserta, status (draft/open/ongoing/completed)

Ikuti pattern yang ada:
- Referensi modul: leave-requests (untuk CRUD + approval pattern)
- Multi-tenant: company_id di semua tabel
- TDD: tulis test dulu

Buatkan:
1. Migration & Model (dengan factory)
2. Controller & Form Request
3. Views (index, create, edit, show)
4. Test file
5. Route registration"
```

### Referensikan File Existing

```
"Buat controller TrainingController dengan pattern yang SAMA
 seperti di app/Http/Controllers/LeaveRequestController.php.
 Sesuaikan untuk modul training."

"Buat view training/index.blade.php dengan layout yang SAMA
 seperti leave-requests/index.blade.php. Ganti konten sesuai
 field training."
```

### Incremental Development

```
Step 1: "Buat migration dan model Training dengan factory"
        → Review → Approve

Step 2: "Buat TrainingController dengan CRUD lengkap.
         Referensi: EmployeeController"
        → Review → Approve

Step 3: "Buat form request StoreTrainingRequest dan
         UpdateTrainingRequest"
        → Review → Approve

Step 4: "Buat view files untuk training CRUD"
        → Review → Approve

Step 5: "Buat test untuk TrainingController"
        → Review → Run test → Fix jika gagal
```

### Tips Menulis Kode dengan AI

```
1. SELALU review output AI sebelum accept
   - Cek tenant isolation (company_id)
   - Cek security (authorization, validation)
   - Cek konvensi (naming, formatting)

2. Berikan CONTOH yang jelas
   - "Seperti di file X"
   - "Ikuti pattern Y"
   - "Formatnya seperti ini: [contoh]"

3. JANGAN accept blindly
   - AI bisa hallucinate method/class yang tidak ada
   - AI bisa salah paham requirement
   - AI bisa miss edge case

4. Iterasi
   - Prompt pertama: buat kerangka
   - Prompt kedua: perbaiki detail
   - Prompt ketiga: tambah edge case
```

---

## 13. Teknik Prompting: Testing dengan TDD

### Red Phase - Tulis Test Dulu

```
"Buatkan test file untuk fitur Training Management.
 Skenario yang harus di-test:

 1. Admin bisa melihat daftar training (index)
 2. Admin bisa membuat training baru (store)
 3. Validasi: nama training wajib diisi
 4. Validasi: tanggal selesai harus setelah tanggal mulai
 5. Tenant isolation: tidak bisa akses training company lain
 6. Employee tidak bisa akses halaman training admin

 Gunakan Pest PHP syntax. Referensi pattern dari
 tests/Feature/Employee/EmployeeControllerTest.php"
```

### Green Phase - Tulis Kode Minimum

```
"Test training sudah dibuat tapi masih gagal semua (RED).
 Sekarang buatkan kode minimum agar semua test PASS:
 - Model Training
 - TrainingController
 - Routes
 - Views (minimal)

 Jangan over-engineer. Cukup buat test pass."
```

### Refactor Phase

```
"Semua test sudah pass (GREEN). Sekarang refactor:
 1. Pindahkan business logic dari controller ke service jika perlu
 2. Pastikan code style sesuai (jalankan pint)
 3. Pastikan test masih pass setelah refactor"
```

### Template TDD Prompt

```
"Menggunakan pendekatan TDD, buat fitur [NAMA FITUR]:

RED (Test dulu):
- Buat test file: tests/Feature/[Module]/[Name]Test.php
- Test scenarios: [list skenario]

GREEN (Implementasi):
- Buat kode minimum untuk pass semua test
- Ikuti konvensi project

REFACTOR:
- Clean up kode
- Jalankan pint
- Pastikan test tetap pass

Mulai dari RED phase dulu."
```

---

## 14. Anti-Pattern: Kesalahan Umum Prompting

### 1. Prompt Terlalu Vague

```
❌ "Buat fitur baru"
❌ "Fix bug ini"
❌ "Improve code"

✅ "Buat fitur overtime request dengan model, controller,
    views, dan test. Ikuti pattern leave-requests."
✅ "Fix error 'Undefined property $department' di
    EmployeeController@show line 45"
✅ "Refactor PayrollController@process - pindahkan
    kalkulasi ke PayrollCalculationService"
```

### 2. Tidak Memberikan Konteks

```
❌ "Buat controller"
   (Controller untuk apa? Pattern mana? Module apa?)

✅ "Buat TrainingController di app/Http/Controllers/
    dengan CRUD lengkap. Ikuti pattern yang ada di
    EmployeeController. Jangan lupa tenant isolation."
```

### 3. Accept Tanpa Review

```
❌ AI generate 200 baris kode → langsung accept semua

✅ AI generate 200 baris kode →
   → Baca setiap file
   → Cek tenant isolation
   → Cek naming convention
   → Cek security
   → Jalankan test
   → Baru accept
```

### 4. Satu Prompt Terlalu Besar

```
❌ "Buat fitur lengkap: model, migration, seeder, factory,
    controller, form request, policy, 5 views, API resource,
    API controller, 20 test cases, notification, event,
    listener, export PDF, import Excel"

✅ Pecah jadi beberapa prompt:
   1. "Buat migration, model, factory untuk Training"
   2. "Buat controller dan form request"
   3. "Buat views CRUD"
   4. "Buat test"
   5. "Buat API endpoint"
```

### 5. Tidak Verifikasi Output

```
❌ AI bilang "selesai" → percaya begitu saja

✅ AI bilang "selesai" →
   → "Jalankan test untuk verifikasi"
   → "Jalankan pint untuk cek formatting"
   → "Cek apakah migration bisa dijalankan"
   → Manual review di browser
```

### 6. Melawan Konvensi Project

```
❌ "Buat controller pakai repository pattern"
   (Project ini tidak pakai repository pattern!)

✅ "Buat controller dengan pattern yang sama
    seperti controller lain di project ini"
```

### 7. Lupa Tenant Isolation

```
❌ "Buat query: Training::all()"

✅ "Buat query: Training::where('company_id', $tenant->id)->get()"
```

---

## 15. Workflow Harian dengan AI IDE

### Morning Routine

```
1. Buka terminal di root project
2. Jalankan Claude Code: claude
3. Tanya: "Apa status git sekarang? Ada perubahan pending?"
4. Review task hari ini
5. Mulai coding
```

### Development Workflow

```
┌──────────────────────────────────────────────────┐
│                DEVELOPMENT LOOP                    │
│                                                    │
│  1. UNDERSTAND                                     │
│     "Jelaskan modul X, baca file Y"               │
│         ↓                                          │
│  2. PLAN                                           │
│     "Apa approach terbaik untuk fitur ini?"        │
│     AI masuk plan mode → review → approve          │
│         ↓                                          │
│  3. TEST (Red)                                     │
│     "Buat test untuk fitur ini"                    │
│         ↓                                          │
│  4. CODE (Green)                                   │
│     "Implementasi agar test pass"                  │
│         ↓                                          │
│  5. VERIFY                                         │
│     "Jalankan test" → "Jalankan pint"              │
│         ↓                                          │
│  6. REFACTOR                                       │
│     "Review & improve kode tadi"                   │
│         ↓                                          │
│  7. COMMIT                                         │
│     "/commit" atau manual git commit               │
│         ↓                                          │
│  Repeat ↩                                          │
└──────────────────────────────────────────────────┘
```

### Contoh Sesi Kerja Nyata

```
> "Hari ini saya perlu membuat fitur employee training.
   Requirement: admin bisa CRUD training, assign employee.
   Mulai dengan pahami modul yang mirip dulu."

AI: [Baca leave-requests sebagai referensi, jelaskan pattern]

> "OK, sekarang buat plan untuk implementasi"

AI: [Masuk plan mode, buat step-by-step plan]
    → Review → Approve

> "Mulai dari migration dan model"

AI: [Buat migration + model + factory]
    → Review → Approve

> "Buat test dulu sebelum controller"

AI: [Buat test file dengan skenario lengkap]
    → Jalankan test → Semua FAIL (RED) ✓

> "Sekarang buat controller dan views agar test pass"

AI: [Buat controller, form request, views, routes]
    → Jalankan test → Semua PASS (GREEN) ✓

> "Jalankan pint dan pastikan semua clean"

AI: [Jalankan vendor/bin/pint --dirty]
    → Fix formatting issues

> "/commit"

AI: [Commit dengan message deskriptif]
```

### Tips Produktivitas

```
1. Gunakan /compact ketika context terlalu panjang
   - Claude Code otomatis compress conversation lama
   - Tapi rules & CLAUDE.md tetap tersedia

2. Mulai sesi baru untuk task yang tidak related
   - Jangan campur task payroll dengan task attendance
   - Konteks yang bersih = output yang lebih akurat

3. Simpan prompt yang sering dipakai
   - Buat snippet untuk prompt berulang
   - Contoh: prompt untuk buat CRUD, prompt untuk debug

4. Review selalu, trust but verify
   - AI sangat capable tapi bisa salah
   - Especially untuk business logic Indonesia
   - PPh 21 rates, BPJS percentages - selalu verifikasi

5. Gunakan plan mode untuk fitur besar
   - AI akan explore codebase dulu
   - Buat rencana → kamu review → baru eksekusi
   - Mencegah AI jalan ke arah yang salah
```

---

## 16. Latihan Praktik

### Latihan 1: Setup & First Interaction (15 menit)

```bash
# 1. Buka terminal di root project
cd /path/to/ultimate-jagogaji-system

# 2. Jalankan Claude Code
claude

# 3. Coba prompt-prompt ini:
> "Jelaskan project ini dalam 5 kalimat"
> "Apa saja modul utama dan berapa jumlah file di masing-masing?"
> "Tunjukkan 5 model terpenting dan relasi mereka"
```

### Latihan 2: Explore Codebase dengan AI (15 menit)

```
Coba prompt ini secara berurutan:

1. "Jelaskan bagaimana attendance clock-in bekerja
    dari Flutter app sampai tersimpan di database.
    Trace setiap file yang terlibat."

2. "Cari semua tempat di codebase yang melakukan
    kalkulasi terkait uang/currency. List file dan line-nya."

3. "Apakah ada inkonsistensi di cara controller handle
    tenant isolation? Cek 5 controller secara random."
```

### Latihan 3: Debugging dengan AI (15 menit)

```
Coba prompt ini:

1. "Jelaskan apa yang terjadi jika user dengan role
    'employee' mencoba akses route /employees.
    Trace middleware yang terlibat."

2. "Jika saya menambah kolom baru 'phone' di tabel employees,
    file apa saja yang perlu diubah? List lengkap."

3. "Review PayrollCalculationService. Apakah ada potential
    bug atau edge case yang belum di-handle?"
```

### Latihan 4: Kode Kecil dengan AI (20 menit)

```
Latihan TDD mini:

1. "Buat test: employee yang sudah terminate
    tidak boleh bisa clock-in.
    Tulis test-nya saja dulu (RED phase)."

2. [Review test yang dihasilkan]

3. "Sekarang implementasi logic-nya agar test pass (GREEN)."

4. [Jalankan test, pastikan pass]

5. "Jalankan pint untuk formatting."
```

### Latihan 5: Prompt Comparison (10 menit)

```
Bandingkan hasil dari prompt yang berbeda:

Prompt A (buruk):
"Buat controller department"

Prompt B (baik):
"Buat DepartmentController dengan CRUD lengkap.
 Referensi: EmployeeController.
 Pastikan tenant isolation, form request validation,
 dan flash message dalam Bahasa Indonesia."

Bandingkan output keduanya. Mana yang lebih sesuai
dengan konvensi project?
```

---

## Rangkuman

### Key Takeaways

1. **AI IDE bukan pengganti developer** - tapi force multiplier yang luar biasa
2. **CLAUDE.md + Rules = konteks** - semakin baik konteks, semakin akurat output
3. **Prompt spesifik > prompt vague** - berikan konteks, referensi, dan contoh
4. **Selalu review** - AI bisa salah, terutama business logic
5. **Incremental** - pecah task besar jadi prompt kecil
6. **TDD with AI** - Red → Green → Refactor tetap berlaku
7. **Referensi existing code** - "buat seperti file X" jauh lebih efektif
8. **Plan mode** - gunakan untuk fitur besar agar AI explore dulu

### Cheat Sheet Prompt

| Tujuan | Prompt Starter |
|--------|---------------|
| Pahami modul | "Jelaskan modul X secara detail, trace alur datanya" |
| Cari file | "Cari semua file yang berhubungan dengan X" |
| Buat fitur | "Buat fitur X. Requirement: ... Referensi: file Y" |
| Debug | "Error: [paste]. Analisis root cause dan fix." |
| Test | "Buat test untuk X. Skenario: [list]" |
| Refactor | "Refactor X: pindahkan logic ke service" |
| Review | "Review file X untuk security/performance issues" |
| Commit | "/commit" |

### Checklist Pemahaman

- [ ] Saya bisa menjalankan Claude Code di project GajiPro
- [ ] Saya memahami perbedaan AI agent vs autocomplete
- [ ] Saya memahami fungsi CLAUDE.md dan .claude/rules/
- [ ] Saya bisa menulis prompt yang spesifik dan efektif
- [ ] Saya bisa menggunakan AI untuk explore codebase
- [ ] Saya bisa menggunakan AI untuk debugging
- [ ] Saya bisa menggunakan AI untuk menulis kode baru
- [ ] Saya bisa menggunakan AI untuk TDD workflow
- [ ] Saya tahu anti-pattern prompting yang harus dihindari
- [ ] Saya paham workflow harian dengan AI IDE

---

> **Sesi Selanjutnya**: Sesi 4 - Hands-on Development: Membuat Fitur Baru dengan TDD
