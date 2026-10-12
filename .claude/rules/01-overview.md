# GajiPro - HRIS/Payroll SaaS

## Brand

- **Name**: GajiPro (Gaji Professional)
- **Type**: HRIS/Payroll SaaS Multi-Tenant untuk pasar Indonesia
- **Tagline**: Solusi HR & Payroll Profesional

## Related Projects

| Project | Path | Framework |
|---------|------|-----------|
| Flutter Mobile App | `/Users/bahri/development/AcademyJF/flutter_gajipro_app-ajf-gajipro` | Flutter |

## Tech Stack

| Layer      | Technology                        |
|------------|-----------------------------------|
| Backend    | Laravel 12 (PHP 8.3)             |
| Frontend   | Blade + Alpine.js 3 + Tailwind CSS 4 |
| Mobile     | Flutter (via API)                 |
| Database   | MySQL                             |
| Auth       | Laravel Sanctum + Spatie Permission |
| Testing    | Pest PHP 3                        |
| Formatter  | Laravel Pint                      |
| Activity   | Spatie ActivityLog                |

## User Roles

| Role             | Scope                                          |
|------------------|-------------------------------------------------|
| Superadmin       | System-wide management, all companies           |
| Admin            | Full company management                         |
| HR Manager       | Employee, attendance, leave, organization       |
| Payroll Manager  | Salary, payroll, tax, BPJS, THR                |
| Employee         | Portal access (self-service)                    |

## Core Modules

### 1. Employee Management
- Employee CRUD, documents, exit/termination
- Organization chart, departments, positions

### 2. Attendance & Scheduling
- Clock in/out with GPS & face recognition
- Work schedules (including overnight shifts)
- Office locations management
- Holiday management

### 3. Leave Management
- Leave types & balances
- Leave requests with approval workflow
- Annual leave, sick leave, etc.

### 4. Payroll & Compensation
- Salary components (earnings & deductions)
- Payroll processing (create, process, approve, pay)
- Payslips generation
- Payroll settings & attendance-based calculations

### 5. Tax & Compliance (Indonesia)
- PPh 21 (income tax) with TER method
- BPJS Ketenagakerjaan (JHT, JP, JKK, JKM)
- BPJS Kesehatan
- THR (Tunjangan Hari Raya) calculation
- Tax Form 1721-A1 (Bukti Potong)
- SPT 1721 (Annual tax return)

### 6. Overtime Management
- Overtime settings & rates
- Overtime requests with approval

### 7. Reimbursement
- Reimbursement categories
- Reimbursement requests with approval workflow

### 8. Communication
- Company announcements (publish/unpublish)
- Push notifications via device tokens

### 9. Employee Portal (Self-Service)
- Personal dashboard & profile
- Attendance (clock in/out)
- Leave requests & balance
- Payslips view
- Overtime & reimbursement requests

### 10. Settings & Administration
- Company profile & settings
- User management & roles/permissions
- Approval workflows (multi-step)
- Attendance settings (GPS, face recognition)
- Activity logs

### 11. Billing & Subscription
- Subscription plans management
- Payment processing & history
- Payment gateway configuration

### 12. Security & Monitoring
- Attack detection (SQL injection, XSS, path traversal, etc.)
- IP blocking (auto & manual)
- Security logs & audit trail

### 13. Data Import
- Bulk import for departments, positions, schedules, leave types, employees, holidays

### 14. Reporting
- Employee reports (by department)
- Attendance reports (daily, lateness)
- Leave reports (balance, by type)
- Payroll reports (by department, tax summary)

## Directory Structure

```
app/
├── Console/Commands/        # Artisan commands
├── Enums/                   # PHP enums
├── Http/
│   ├── Controllers/
│   │   ├── Auth/            # Authentication controllers
│   │   ├── Import/          # Data import controllers
│   │   ├── Portal/          # Employee portal controllers
│   │   ├── Reports/         # Report controllers
│   │   ├── Settings/        # Settings controllers
│   │   └── Superadmin/      # Superadmin controllers
│   ├── Middleware/           # Custom middleware
│   └── Requests/            # Form request validation
├── Models/                  # Eloquent models (54+)
├── Notifications/           # Notification classes
├── Providers/               # Service providers
├── Services/                # Business logic services
└── Traits/                  # Shared traits
```
