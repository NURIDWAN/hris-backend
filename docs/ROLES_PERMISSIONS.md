# JagoGaji - Roles & Permissions Specification

## 1. ROLE HIERARCHY

```
PLATFORM LEVEL
├── Super Admin (Platform Owner)
│
COMPANY LEVEL
├── Company Owner
├── Admin HR
├── Manager/Supervisor
└── Employee
```

---

## 2. DETAILED ROLE DEFINITIONS

### 2.1 Super Admin (Platform Level)
**Scope:** Seluruh platform JagoGaji
**Use Case:** Tim JagoGaji internal untuk manage platform SaaS

| Permission | Access |
|------------|--------|
| Companies | View, Create, Edit, Delete, Suspend |
| Subscriptions | Manage billing, packages |
| Platform Settings | Configure global settings |
| System Reports | Usage analytics, revenue |
| Support | Access all company data for support |

---

### 2.2 Company Owner
**Scope:** Satu perusahaan
**Use Case:** Pemilik atau direktur perusahaan yang subscribe JagoGaji

| Module | Permissions |
|--------|-------------|
| **Dashboard** | Full access, all statistics |
| **Employees** | View All, Create, Edit, Delete, Import, Export |
| **Attendance** | View All, Manage Settings |
| **Leave/Permit** | View All, Approve/Reject, Settings |
| **Payroll** | Full Access, Generate, Approve, Pay, Settings |
| **Reports** | All Reports, Export |
| **Settings** | Company Profile, Organization, Approval Flow, Billing |
| **Users** | Manage all users & roles |

---

### 2.3 Admin HR
**Scope:** Satu perusahaan
**Use Case:** Staff HR yang mengelola data karyawan dan payroll (optional: bisa handle payroll jika tidak ada Finance)

---

### 2.4 Finance (Optional Role)
**Scope:** Satu perusahaan
**Use Case:** Staff Finance yang fokus ke payroll tanpa akses data pribadi karyawan

| Module | Permissions |
|--------|-------------|
| **Dashboard** | View (financial metrics only) |
| **Employees** | View List Only (nama, departemen, jabatan) |
| **Attendance** | View Summary untuk perhitungan gaji |
| **Payroll** | Full Access: Generate, Edit, Process Payment |
| **Reports** | Payroll Reports, Tax Reports, BPJS Reports |
| **Settings** | Payroll Components, Tax Settings, Bank Account |

**Restrictions:**
- Cannot access personal employee data (alamat, kontak, keluarga)
- Cannot approve leave/permit
- Cannot manage employee records
- Cannot access non-financial settings

> **Note:** Role Finance bersifat optional. Jika perusahaan kecil, Admin HR bisa handle payroll. Jika perusahaan besar, bisa assign Finance khusus untuk segregation of duties.

---

### 2.5 Manager/Supervisor

| Module | Permissions |
|--------|-------------|
| **Dashboard** | View (limited to HR metrics) |
| **Employees** | View All, Create, Edit, Import, Export |
| **Attendance** | View All, Manage, Correct |
| **Leave/Permit** | View All, Approve/Reject (HR level) |
| **Overtime** | View All, Approve/Reject |
| **Payroll** | View All, Generate, Edit Components |
| **Reports** | All Reports, Export |
| **Settings** | Organization, Schedules, Payroll Components |
| **Users** | Create Employee accounts |

**Restrictions:**
- Cannot delete employees permanently
- Cannot access billing
- Cannot change company profile
- Cannot manage Admin HR role

---

### 2.6 Manager/Supervisor
**Scope:** Departemen/Tim sendiri
**Use Case:** Kepala departemen atau supervisor yang approve request bawahan

| Module | Permissions |
|--------|-------------|
| **Dashboard** | View (team metrics only) |
| **Employees** | View Team Only |
| **Attendance** | View Team, Approve corrections |
| **Leave/Permit** | View Team, Approve/Reject |
| **Overtime** | View Team, Approve/Reject |
| **Payroll** | View Team Slip (no amounts for others) |
| **Reports** | Team Reports Only |

**Restrictions:**
- Cannot access employees outside team
- Cannot access payroll details of others
- Cannot access settings
- Cannot create/edit employees

---

### 2.7 Employee
**Scope:** Data diri sendiri
**Use Case:** Karyawan biasa yang akses via Mobile App (ESS)

| Module | Permissions |
|--------|-------------|
| **Dashboard** | Personal summary only |
| **Profile** | View Own, Edit Limited (contact, photo) |
| **Attendance** | Clock In/Out, View Own History |
| **Leave/Permit** | Request, View Own Status |
| **Overtime** | Request, View Own Status |
| **Payroll** | View Own Slip Only, Download PDF |
| **Schedule** | View Own Schedule |

**Restrictions:**
- Cannot view other employees
- Cannot access admin panel
- Mobile app access only (ESS)

---

## 3. PERMISSION MATRIX

### 3.1 Employee Module

| Action | Super Admin | Owner | Admin HR | Manager | Employee |
|--------|:-----------:|:-----:|:--------:|:-------:|:--------:|
| View All | - | Y | Y | Team | Self |
| View Detail | - | Y | Y | Team | Self |
| Create | - | Y | Y | - | - |
| Edit | - | Y | Y | - | Limited |
| Delete | - | Y | - | - | - |
| Import | - | Y | Y | - | - |
| Export | - | Y | Y | - | - |

### 3.2 Attendance Module

| Action | Super Admin | Owner | Admin HR | Manager | Employee |
|--------|:-----------:|:-----:|:--------:|:-------:|:--------:|
| Clock In/Out | - | - | - | Y | Y |
| View All | - | Y | Y | Team | Self |
| Manage Schedule | - | Y | Y | - | - |
| Correct Attendance | - | Y | Y | - | - |
| View Reports | - | Y | Y | Team | - |

### 3.3 Leave/Permit Module

| Action | Super Admin | Owner | Admin HR | Manager | Employee |
|--------|:-----------:|:-----:|:--------:|:-------:|:--------:|
| Request | - | - | - | Y | Y |
| View All | - | Y | Y | Team | Self |
| Approve/Reject | - | Y | Y | Team | - |
| Manage Types | - | Y | Y | - | - |
| Manage Quota | - | Y | Y | - | - |

### 3.4 Payroll Module

| Action | Super Admin | Owner | Admin HR | Manager | Employee |
|--------|:-----------:|:-----:|:--------:|:-------:|:--------:|
| View All Slips | - | Y | Y | - | Self |
| Generate Payroll | - | Y | Y | - | - |
| Edit Components | - | Y | Y | - | - |
| Approve Payroll | - | Y | - | - | - |
| Process Payment | - | Y | - | - | - |
| Download Slip | - | Y | Y | Self | Self |
| Settings | - | Y | Y | - | - |

### 3.5 Settings Module

| Action | Super Admin | Owner | Admin HR | Manager | Employee |
|--------|:-----------:|:-----:|:--------:|:-------:|:--------:|
| Company Profile | Y | Y | - | - | - |
| Organization | - | Y | Y | - | - |
| Positions | - | Y | Y | - | - |
| Schedules | - | Y | Y | - | - |
| Payroll Config | - | Y | Y | - | - |
| Approval Flow | - | Y | - | - | - |
| Billing | Y | Y | - | - | - |

---

## 4. APPROVAL WORKFLOW (Single Level)

### 4.1 Leave/Permit Request
```
Employee Submit → Manager Approve/Reject → Done
                         ↓
                 (Auto-notify Employee)
```

### 4.2 Overtime Request
```
Employee Submit → Manager Approve/Reject → Done
                         ↓
                 (Auto-sync to Payroll)
```

### 4.3 Attendance Correction
```
Employee Request → Admin HR Review → Approve/Reject → Done
```

### 4.4 Payroll Approval
```
Admin HR Generate → Owner Review & Approve → Process Payment → Done
```

---

## 5. IMPLEMENTATION NOTES

### 5.1 Multi-Tenant Consideration
- Semua query harus di-scope by `company_id`
- Super Admin bypass company scope
- Middleware untuk validate tenant access

### 5.2 Laravel Implementation
Menggunakan **Spatie Laravel Permission**:

```php
// Roles
- super-admin (platform level, guard: web)
- company-owner (company level, guard: web)
- admin-hr (company level, guard: web)
- manager (company level, guard: web)
- employee (company level, guard: api)

// Sample Permissions
- employees.view
- employees.create
- employees.edit
- employees.delete
- attendance.manage
- leave.approve
- payroll.generate
- payroll.approve
- settings.manage
```

### 5.3 Team/Department Scope
Manager hanya bisa akses:
- Employees dengan `department_id` sama
- Atau employees dengan `supervisor_id` = manager's user_id

### 5.4 API Guard untuk Mobile
- Employee role pakai `guard: api` (Sanctum token)
- Web roles pakai `guard: web` (session)

---

## 6. FINALIZED DECISIONS

| Question | Decision |
|----------|----------|
| Role Finance terpisah? | **Optional** - Bisa diaktifkan per company. Admin HR tetap bisa handle payroll jika tidak ada Finance. |
| Employee lihat directory? | **Tidak** - Employee hanya bisa lihat data diri sendiri, lebih private. |
| Approval workflow? | **Single Level** - Langsung ke atasan/manager. |

---

## 7. ROLE ASSIGNMENT FLEXIBILITY

Company Owner bisa mengatur:
- Apakah Admin HR boleh akses Payroll atau tidak
- Apakah perlu role Finance terpisah atau tidak
- Custom permission per user jika diperlukan

Ini dilakukan via **Permission Settings** di admin panel.

---

*Document Version: 1.0*
*Created: 2026-02-11*
