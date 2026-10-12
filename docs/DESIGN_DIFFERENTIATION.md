# JagoGaji - Design Differentiation Strategy

## 1. COMPETITIVE ANALYSIS

### 1.1 GajiHub Visual Identity
| Aspect | GajiHub |
|--------|---------|
| **Primary Color** | Pink/Magenta (#E91E63) |
| **Secondary Color** | Light Pink (#FCE4EC) |
| **Sidebar** | White/Light with pink accent |
| **Header** | Pink gradient |
| **Accent** | Pink buttons, pink highlights |
| **Typography** | Standard sans-serif |
| **Layout** | Traditional sidebar left |
| **Cards** | White with subtle shadow |
| **Icons** | Filled icons |
| **Style** | Soft, feminine, rounded |

### 1.2 Ultimate HRD System (JagoHRIS) Visual Identity
| Aspect | Ultimate HRD |
|--------|--------------|
| **Primary Color** | Emerald Green (#10b981) |
| **Secondary Color** | Dark Green (#065f46) |
| **Sidebar** | Dark green background |
| **Header** | Green gradient |
| **Accent** | Amber/Orange (#f59e0b) |
| **Typography** | Inter font |
| **Layout** | Tailwind + Livewire |
| **Cards** | White with hover shadow |
| **Icons** | Heroicons (outline) |
| **Style** | Modern, professional, clean |

---

## 2. JAGOGAJI UNIQUE DESIGN SYSTEM

### 2.1 Brand Personality
**JagoGaji** = Profesional, Modern, Terpercaya, Indonesia-focused

**Tagline Ideas:**
- "Kelola Gaji, Jadi Mudah"
- "Payroll & HR Made Simple"
- "Solusi Penggajian Cerdas"

### 2.2 Color Palette - OCEAN BLUE THEME

Berbeda dari GajiHub (Pink) dan Ultimate HRD (Green), JagoGaji menggunakan **Deep Ocean Blue** yang melambangkan kepercayaan, profesionalisme, dan stabilitas.

```css
/* Primary - Deep Ocean Blue */
--primary-50: #eff6ff;
--primary-100: #dbeafe;
--primary-200: #bfdbfe;
--primary-300: #93c5fd;
--primary-400: #60a5fa;
--primary-500: #3b82f6;   /* Main */
--primary-600: #2563eb;   /* Hover */
--primary-700: #1d4ed8;   /* Active */
--primary-800: #1e40af;
--primary-900: #1e3a8a;

/* Secondary - Slate (Neutral Professional) */
--secondary-50: #f8fafc;
--secondary-100: #f1f5f9;
--secondary-200: #e2e8f0;
--secondary-300: #cbd5e1;
--secondary-400: #94a3b8;
--secondary-500: #64748b;
--secondary-600: #475569;
--secondary-700: #334155;
--secondary-800: #1e293b;
--secondary-900: #0f172a;

/* Accent - Coral/Orange (Warmth & Energy) */
--accent-50: #fff7ed;
--accent-100: #ffedd5;
--accent-200: #fed7aa;
--accent-300: #fdba74;
--accent-400: #fb923c;
--accent-500: #f97316;   /* Main */
--accent-600: #ea580c;
--accent-700: #c2410c;

/* Success - Teal */
--success-500: #14b8a6;

/* Warning - Amber */
--warning-500: #f59e0b;

/* Danger - Rose */
--danger-500: #f43f5e;

/* Info - Sky */
--info-500: #0ea5e9;
```

### 2.3 Color Comparison

| Element | GajiHub | Ultimate HRD | JagoGaji |
|---------|---------|--------------|----------|
| Primary | Pink #E91E63 | Green #10b981 | Blue #3b82f6 |
| Sidebar BG | White | Dark Green #065f46 | Dark Slate #1e293b |
| Header | Pink Gradient | Green Gradient | Blue Gradient |
| Accent | Pink | Amber #f59e0b | Coral #f97316 |
| Text Primary | Gray | Slate | Slate |
| Success | Green | Green | Teal #14b8a6 |
| Card BG | White | White | White/Subtle Blue tint |

### 2.4 Typography

```css
/* Font Family */
font-family: 'Plus Jakarta Sans', 'Inter', system-ui, sans-serif;

/* Font Sizes */
--text-xs: 0.75rem;    /* 12px */
--text-sm: 0.875rem;   /* 14px */
--text-base: 1rem;     /* 16px */
--text-lg: 1.125rem;   /* 18px */
--text-xl: 1.25rem;    /* 20px */
--text-2xl: 1.5rem;    /* 24px */
--text-3xl: 1.875rem;  /* 30px */
--text-4xl: 2.25rem;   /* 36px */

/* Font Weights */
--font-normal: 400;
--font-medium: 500;
--font-semibold: 600;
--font-bold: 700;
```

### 2.5 Layout Differences

| Aspect | GajiHub | Ultimate HRD | JagoGaji (Recommended) |
|--------|---------|--------------|------------------------|
| Sidebar Position | Left, light | Left, dark | Left, dark slate (collapsible) |
| Sidebar Width | ~250px | ~250px | 260px expanded, 72px collapsed |
| Header Height | ~60px | ~64px | 64px with user dropdown |
| Content Padding | 20px | 24px | 24px |
| Card Style | Rounded 8px | Rounded 12px | Rounded 16px with subtle border |
| Table Style | Bordered | Borderless | Striped alternate |
| Button Style | Rounded | Rounded | Pill shape (rounded-full) |

---

## 3. COMPONENT DESIGN SYSTEM

### 3.1 Sidebar Design
```
┌────────────────────────────────────────┐
│ ┌────┐                                 │
│ │LOGO│  JagoGaji                       │  <- Dark slate bg (#1e293b)
│ └────┘                                 │
│────────────────────────────────────────│
│                                        │
│ ▶ Dashboard                            │  <- Blue highlight on active
│   Karyawan                             │
│   Kehadiran                            │  <- White text, hover: blue bg
│   Cuti & Izin                          │
│   Payroll                              │
│   Laporan                              │
│   Pengaturan                           │
│                                        │
│────────────────────────────────────────│
│                                        │
│  👤 Admin Name                         │
│     admin@company.com                  │
│                                        │
└────────────────────────────────────────┘
```

### 3.2 Dashboard Cards
```
┌─────────────────────────────────────────────────────────────┐
│  Blue gradient banner with company stats                    │
│  "Selamat Datang, [Nama]"                                   │
│  Total: 150 Karyawan | Hadir Hari Ini: 142 | Pending: 5     │
└─────────────────────────────────────────────────────────────┘

┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐
│   📊       │ │   👥       │ │   📅       │ │   💰       │
│   145      │ │   5        │ │   3        │ │   Rp 250jt │
│ Hadir      │ │ Tidak      │ │ Cuti       │ │ Total Gaji │
│            │ │ Hadir      │ │ Pending    │ │ Bulan Ini  │
└────────────┘ └────────────┘ └────────────┘ └────────────┘
     ↑              ↑              ↑              ↑
  Blue bg       Red bg         Amber bg       Teal bg
  #dbeafe       #fee2e2        #fef3c7        #ccfbf1
```

### 3.3 Button Styles
```css
/* Primary Button - Blue pill */
.btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border-radius: 9999px; /* Pill shape */
    padding: 10px 24px;
    font-weight: 600;
    box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
}

/* Secondary Button - Outline */
.btn-secondary {
    background: transparent;
    color: #3b82f6;
    border: 2px solid #3b82f6;
    border-radius: 9999px;
}

/* Accent Button - Coral for important actions */
.btn-accent {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    color: white;
    border-radius: 9999px;
}
```

### 3.4 Form Inputs
```css
/* Input Style */
.form-input {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 16px;
    transition: all 0.2s;
}

.form-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}
```

### 3.5 Table Design
```css
/* Table dengan zebra stripe dan hover */
.table-jagogaji {
    border-collapse: separate;
    border-spacing: 0;
}

.table-jagogaji th {
    background: #f8fafc;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.05em;
}

.table-jagogaji tr:nth-child(even) {
    background: #f8fafc;
}

.table-jagogaji tr:hover {
    background: #eff6ff; /* Blue tint on hover */
}
```

---

## 4. LAYOUT ARCHITECTURE (Berbeda dari Ultimate HRD)

### 4.1 Ultimate HRD menggunakan:
- Stisla Template (Bootstrap based)
- Livewire components
- Traditional blade views
- jQuery for interactions

### 4.2 JagoGaji akan menggunakan:
- **Laravel 12** (Backend)
- **Blade** (Server-side templating)
- **Tailwind CSS v4** (Utility-first styling)
- **Alpine.js v3** (Lightweight JS interactions)
- **Chart.js** (Dashboard charts)
- **MySQL** (Database)

### 4.3 Database Design Enhancements
Berbeda dari Ultimate HRD, JagoGaji akan:
- Multi-tenant dari awal (company_id di setiap tabel)
- Soft deletes di semua tabel penting
- Activity log untuk audit trail
- Full-text search dengan Scout/Meilisearch

---

## 5. FEATURE DIFFERENTIATION

### 5.1 Fitur yang sama (Core HR)
| Feature | GajiHub | Ultimate HRD | JagoGaji |
|---------|:-------:|:------------:|:--------:|
| Employee Management | ✅ | ✅ | ✅ |
| Attendance (GPS) | ✅ | ✅ | ✅ |
| Leave Management | ✅ | ✅ | ✅ |
| Payroll Basic | ✅ | ❌ | ✅ |

### 5.2 Fitur Unik JagoGaji (Differentiator)
| Feature | Deskripsi |
|---------|-----------|
| **Multi-tenant SaaS** | Platform untuk banyak perusahaan |
| **PPh 21 Auto Calculate** | Perhitungan pajak otomatis sesuai PMK terbaru |
| **BPJS Integration** | Hitung iuran BPJS Kesehatan & TK otomatis |
| **THR Pro-rata** | Perhitungan THR sesuai UU Cipta Kerja |
| **Bulk Payroll** | Proses gaji batch dengan 1 klik |
| **Employee Self-Service** | Mobile app Flutter untuk karyawan |
| **Approval Workflow** | Configurable approval chains |
| **Real-time Dashboard** | Live update dengan WebSocket |
| **API First** | REST API lengkap untuk integrasi |
| **White Label Ready** | Bisa di-rebrand untuk klien |

---

## 6. UI/UX IMPROVEMENTS OVER COMPETITORS

### 6.1 GajiHub Pain Points → JagoGaji Solutions
| GajiHub Issue | JagoGaji Solution |
|---------------|-------------------|
| UI terlalu "girly" (pink) | Warna profesional (blue) |
| Menu terlalu banyak | Grouped navigation |
| Loading lambat | SPA dengan Inertia |
| Mobile app terpisah | PWA + Flutter integrated |

### 6.2 Ultimate HRD Pain Points → JagoGaji Solutions
| Ultimate HRD Issue | JagoGaji Solution |
|--------------------|-------------------|
| Tidak ada payroll | Full payroll module |
| Single company only | Multi-tenant |
| Bootstrap (outdated feel) | Tailwind (modern) |
| jQuery dependent | Vue.js reactive |
| No PPh 21 calculation | Auto tax calculation |

---

## 7. IMPLEMENTATION PRIORITY

### Phase 1: Foundation (Use from Ultimate HRD)
- Database schema untuk Employee, Department, Position, Shift
- Authentication & Authorization system
- Base layout dengan Tailwind
- API structure

### Phase 2: Core Features
- Employee Management (enhanced)
- Attendance with GPS & Face (new Flutter app)
- Leave Management with approval

### Phase 3: Payroll (New Development)
- Salary Components
- PPh 21 Calculation
- BPJS Calculation
- Slip Gaji Generation
- THR Calculation

### Phase 4: Advanced
- Multi-tenant implementation
- Billing & Subscription
- Advanced Reports
- White Label

---

## 8. TECHNOLOGY STACK (FINAL)

```
┌─────────────────────────────────────────────────────────┐
│                    JAGOGAJI TECH STACK                  │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  WEB APPLICATION                                        │
│  ├── Laravel 12          (Backend Framework)           │
│  ├── Blade               (Templating)                  │
│  ├── Tailwind CSS v4     (Styling)                     │
│  ├── Alpine.js v3        (JavaScript Interactions)     │
│  └── Chart.js            (Dashboard Charts)            │
│                                                         │
│  MOBILE APP (Employee Self Service)                     │
│  ├── Flutter 3.x         (Cross-platform)              │
│  ├── Dio                 (HTTP Client)                 │
│  ├── Provider/Riverpod   (State Management)            │
│  ├── Geolocator          (GPS Location)                │
│  └── Camera              (Selfie for attendance)       │
│                                                         │
│  BACKEND PACKAGES                                       │
│  ├── Laravel Sanctum     (API Auth for Mobile)         │
│  ├── Spatie Permission   (RBAC)                        │
│  ├── Laravel Excel       (Export)                      │
│  ├── DomPDF              (Slip Gaji PDF)               │
│  └── Laravel Media       (File Upload)                 │
│                                                         │
│  DATABASE                                               │
│  ├── MySQL 8.x           (Primary Database)            │
│  ├── Redis               (Cache & Queue)               │
│  └── S3/MinIO            (File Storage)                │
│                                                         │
│  DEVOPS                                                 │
│  ├── Docker              (Development)                 │
│  └── GitHub Actions      (CI/CD)                       │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## 9. BRAND ASSETS TO CREATE

### 9.1 Logo Concepts
- Primary logo: "JagoGaji" wordmark with abstract J+G icon
- Icon only: Abstract checkmark/chart representing payroll success
- Color: Blue gradient (#3b82f6 → #2563eb)

### 9.2 Favicon
- 32x32 and 16x16 versions
- Simple "J" or abstract icon
- Blue on white or white on blue

### 9.3 Mobile App Icon
- 1024x1024 master
- Blue gradient background
- White icon/wordmark

---

## 10. SUMMARY

| Aspect | JagoGaji Differentiation |
|--------|--------------------------|
| **Color** | Ocean Blue (bukan Pink atau Green) |
| **Layout** | Modern SPA dengan Inertia + Vue |
| **Buttons** | Pill shape dengan gradient |
| **Cards** | Rounded 16px dengan subtle border |
| **Sidebar** | Dark slate, collapsible |
| **Icons** | Heroicons outline style |
| **Font** | Plus Jakarta Sans |
| **Unique Feature** | Full Payroll + Tax + BPJS |
| **Architecture** | Multi-tenant SaaS ready |

---

*Document Version: 1.0*
*Created: 2026-02-11*
