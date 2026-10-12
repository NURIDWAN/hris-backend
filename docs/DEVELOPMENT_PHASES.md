# JagoGaji - Development Phases & Checklist

## Overview

| Phase | Nama | Status | Progress |
|-------|------|--------|----------|
| 0 | Project Setup & Foundation | **CURRENT** | 95% |
| 1 | Core Authentication & Multi-tenant | Pending | 0% |
| 2 | Employee Management | Pending | 0% |
| 3 | Attendance & Scheduling | Pending | 0% |
| 4 | Leave & Permit Management | Pending | 0% |
| 5 | Payroll System | Pending | 0% |
| 6 | Reports & Dashboard | Pending | 0% |
| 7 | Mobile App (Flutter) | Pending | 0% |
| 8 | Testing & QA | Pending | 0% |
| 9 | Deployment & Launch | Pending | 0% |

---

## PHASE 0: Project Setup & Foundation
**Status:** IN PROGRESS (40%)

### 0.1 Environment Setup
- [x] Laravel 12 project initialization
- [x] Database configuration (MySQL)
- [x] Tailwind CSS 4 setup
- [x] Alpine.js integration
- [x] Laravel Boost installation
- [x] Redis setup (cache & queue)
- [ ] S3/MinIO configuration (file storage)

### 0.2 Design System
- [x] Ocean Blue color palette defined
- [x] Typography (Plus Jakarta Sans)
- [x] Button styles (pill shape, gradients)
- [x] Form input styles
- [x] Card components
- [x] Alert/Toast components
- [x] Modal components
- [x] Table components
- [x] Pagination components
- [x] Badge components
- [x] Dropdown components
- [x] Empty state components
- [x] Loading spinner

### 0.3 Public Pages
- [x] Landing page
- [x] Pricing page
- [x] Login page
- [x] Register page
- [x] Forgot password page
- [x] Terms of Service page
- [x] Privacy Policy page
- [ ] Contact page

### 0.4 Layout & Components
- [x] Guest layout (public pages)
- [x] Navbar component
- [x] Footer component
- [x] Admin layout (dashboard)
- [x] Sidebar component (collapsible)
- [x] Breadcrumb component
- [x] Mobile responsive navigation
- [x] Dashboard page (sample)

### 0.5 Documentation
- [x] System analysis document
- [x] Roles & permissions specification
- [x] Design differentiation document
- [x] Landing/Auth/Pricing specification
- [x] Development phases checklist (this document)
- [ ] API documentation structure
- [ ] Database schema documentation

---

## PHASE 1: Core Authentication & Multi-tenant
**Status:** PENDING

### 1.1 Database Schema
- [ ] Companies table (tenants)
- [ ] Users table (with company_id)
- [ ] Roles table (Spatie Permission)
- [ ] Permissions table
- [ ] Sessions table
- [ ] Password reset tokens table

### 1.2 Multi-tenant Setup
- [ ] Tenant middleware
- [ ] Global scope for tenant isolation
- [ ] Company registration flow
- [ ] Company profile management
- [ ] Subdomain/path-based routing (optional)

### 1.3 Authentication
- [ ] User registration (company owner)
- [ ] Email verification
- [ ] Login with remember me
- [ ] Logout
- [ ] Password reset flow
- [ ] Password change
- [ ] Profile update

### 1.4 Authorization (Spatie Permission)
- [ ] Install & configure Spatie Permission
- [ ] Define roles:
  - [ ] Super Admin
  - [ ] Company Owner
  - [ ] Admin HR
  - [ ] Finance (optional)
  - [ ] Manager
  - [ ] Employee
- [ ] Define permissions per module
- [ ] Role assignment on registration
- [ ] Permission middleware

### 1.5 User Management
- [ ] User CRUD for admins
- [ ] Invite user via email
- [ ] Role assignment
- [ ] Deactivate/reactivate user
- [ ] User activity log

---

## PHASE 2: Employee Management
**Status:** PENDING

### 2.1 Database Schema
- [ ] Departments table
- [ ] Positions (jabatan) table
- [ ] Employee levels/grades table
- [ ] Employees table
- [ ] Employee documents table
- [ ] Employee families table
- [ ] Employee emergency contacts table

### 2.2 Organization Structure
- [ ] Department CRUD
- [ ] Position CRUD
- [ ] Level/Grade CRUD
- [ ] Org chart visualization
- [ ] Department hierarchy

### 2.3 Employee CRUD
- [ ] Employee list with filters
- [ ] Employee detail view
- [ ] Create employee form
  - [ ] Personal information
  - [ ] Contact information
  - [ ] Employment information
  - [ ] Family information
  - [ ] Emergency contacts
- [ ] Edit employee
- [ ] Delete/archive employee
- [ ] Employee status management (active, inactive, resigned)

### 2.4 Employee Features
- [ ] Photo upload
- [ ] Document upload (KTP, NPWP, etc.)
- [ ] Employee ID auto-generation
- [ ] Work tenure calculation
- [ ] Contract management
- [ ] Probation tracking

### 2.5 Import/Export
- [ ] Import employees from Excel/CSV
- [ ] Export employees to Excel
- [ ] Export employees to PDF
- [ ] Import template download

---

## PHASE 3: Attendance & Scheduling
**Status:** PENDING

### 3.1 Database Schema
- [ ] Schedules table
- [ ] Schedule details table
- [ ] Work shifts table
- [ ] Holidays table
- [ ] Attendances table
- [ ] Attendance locations table
- [ ] Office locations table

### 3.2 Schedule Management
- [ ] Work shift CRUD (pagi, siang, malam)
- [ ] Schedule template CRUD
- [ ] Assign schedule to employee
- [ ] Bulk schedule assignment
- [ ] Holiday calendar management
- [ ] Working days configuration

### 3.3 Office Location
- [ ] Office location CRUD
- [ ] GPS coordinates setup
- [ ] Radius configuration
- [ ] Multiple location support

### 3.4 Attendance (Web Admin)
- [ ] Attendance dashboard
- [ ] Daily attendance view
- [ ] Monthly attendance recap
- [ ] Attendance correction
- [ ] Manual attendance entry
- [ ] Late/early detection
- [ ] Absence tracking

### 3.5 Attendance Reports
- [ ] Daily attendance report
- [ ] Monthly attendance summary
- [ ] Employee attendance history
- [ ] Department attendance report
- [ ] Export to Excel/PDF

---

## PHASE 4: Leave & Permit Management
**Status:** PENDING

### 4.1 Database Schema
- [ ] Leave types table
- [ ] Leave balances table
- [ ] Leave requests table
- [ ] Leave approvals table

### 4.2 Leave Type Configuration
- [ ] Leave type CRUD
- [ ] Default quota setting
- [ ] Carry forward rules
- [ ] Gender-specific leaves
- [ ] Document requirement setting

### 4.3 Leave Balance
- [ ] Annual leave quota assignment
- [ ] Balance calculation
- [ ] Balance adjustment
- [ ] Carry forward processing
- [ ] Balance history

### 4.4 Leave Request
- [ ] Request leave form
- [ ] Date range picker
- [ ] Document upload
- [ ] Leave request list
- [ ] Request status tracking
- [ ] Cancel request

### 4.5 Leave Approval
- [ ] Pending approval list
- [ ] Approve/reject with notes
- [ ] Approval history
- [ ] Email notifications
- [ ] Auto-sync with attendance

### 4.6 Overtime Management
- [ ] Overtime request form
- [ ] Overtime approval
- [ ] Overtime calculation
- [ ] Overtime reports

---

## PHASE 5: Payroll System
**Status:** PENDING

### 5.1 Database Schema
- [ ] Salary components table
- [ ] Employee salaries table
- [ ] Payroll periods table
- [ ] Payroll items table
- [ ] Payroll details table
- [ ] Tax configurations table
- [ ] BPJS configurations table

### 5.2 Salary Configuration
- [ ] Salary component CRUD (allowances, deductions)
- [ ] Default components setup
- [ ] Component formulas
- [ ] Tax bracket configuration
- [ ] BPJS rate configuration

### 5.3 Employee Salary
- [ ] Assign salary to employee
- [ ] Salary components per employee
- [ ] Salary history
- [ ] Salary adjustment

### 5.4 Payroll Processing
- [ ] Create payroll period
- [ ] Generate payroll batch
- [ ] Auto-calculate:
  - [ ] Basic salary
  - [ ] Allowances
  - [ ] Overtime pay
  - [ ] Attendance deductions
  - [ ] PPh 21 calculation
  - [ ] BPJS Kesehatan
  - [ ] BPJS Ketenagakerjaan
  - [ ] Other deductions
- [ ] Manual adjustment
- [ ] Payroll preview
- [ ] Approve payroll
- [ ] Process payment

### 5.5 Payslip
- [ ] Generate payslip
- [ ] Payslip template
- [ ] Download PDF
- [ ] Email payslip
- [ ] Payslip history

### 5.6 Tax & BPJS Reports
- [ ] PPh 21 monthly report
- [ ] PPh 21 annual report (1721-A1)
- [ ] BPJS Kesehatan report
- [ ] BPJS Ketenagakerjaan report

### 5.7 THR (Bonus)
- [ ] THR configuration
- [ ] THR calculation (pro-rata)
- [ ] THR processing
- [ ] THR slip

---

## PHASE 6: Reports & Dashboard
**Status:** PENDING

### 6.1 Dashboard
- [ ] Company owner dashboard
- [ ] HR admin dashboard
- [ ] Manager dashboard
- [ ] Employee count widgets
- [ ] Attendance summary widget
- [ ] Payroll summary widget
- [ ] Birthday reminder widget
- [ ] Contract expiry widget

### 6.2 Employee Reports
- [ ] Employee master list
- [ ] Employee by department
- [ ] Employee by status
- [ ] New hire report
- [ ] Resignation report
- [ ] Headcount trend

### 6.3 Attendance Reports
- [ ] Daily attendance
- [ ] Monthly summary
- [ ] Lateness report
- [ ] Absence report
- [ ] Overtime report

### 6.4 Leave Reports
- [ ] Leave balance report
- [ ] Leave usage report
- [ ] Leave by type
- [ ] Pending approvals

### 6.5 Payroll Reports
- [ ] Payroll summary
- [ ] Payroll by department
- [ ] Salary distribution
- [ ] Tax summary
- [ ] BPJS summary

### 6.6 Export Features
- [ ] Export to Excel
- [ ] Export to PDF
- [ ] Scheduled reports (email)
- [ ] Custom date range

---

## PHASE 7: Mobile App (Flutter)
**Status:** PENDING

### 7.1 Project Setup
- [ ] Flutter project initialization
- [ ] State management setup (Riverpod/Provider)
- [ ] API client setup (Dio)
- [ ] Secure storage
- [ ] Push notification setup

### 7.2 Authentication
- [ ] Login screen
- [ ] Forgot password
- [ ] Biometric login (optional)
- [ ] Session management
- [ ] Auto logout

### 7.3 Home & Profile
- [ ] Home screen
- [ ] Profile view
- [ ] Profile edit
- [ ] Change password
- [ ] Notification settings

### 7.4 Attendance
- [ ] Clock in button
- [ ] Clock out button
- [ ] Camera capture (selfie)
- [ ] GPS location capture
- [ ] Location validation
- [ ] Attendance history
- [ ] Monthly calendar view

### 7.5 Leave & Permit
- [ ] Leave request form
- [ ] Leave balance display
- [ ] Request history
- [ ] Request status tracking

### 7.6 Payslip
- [ ] Payslip list
- [ ] Payslip detail
- [ ] Download PDF
- [ ] Share payslip

### 7.7 Schedule
- [ ] Schedule calendar
- [ ] Shift information
- [ ] Holiday calendar

### 7.8 Manager Features (conditional)
- [ ] Team attendance view
- [ ] Pending approvals
- [ ] Approve/reject actions

---

## PHASE 8: Testing & QA
**Status:** PENDING

### 8.1 Backend Testing
- [ ] Unit tests for models
- [ ] Unit tests for services
- [ ] Feature tests for controllers
- [ ] API tests
- [ ] Integration tests

### 8.2 Frontend Testing
- [ ] Component tests (if applicable)
- [ ] E2E tests with Playwright/Cypress

### 8.3 Mobile Testing
- [ ] Widget tests
- [ ] Integration tests
- [ ] Device testing (Android)
- [ ] Device testing (iOS)

### 8.4 Security Testing
- [ ] Authentication tests
- [ ] Authorization tests
- [ ] SQL injection tests
- [ ] XSS tests
- [ ] CSRF tests
- [ ] Multi-tenant isolation tests

### 8.5 Performance Testing
- [ ] Load testing
- [ ] Database query optimization
- [ ] API response time
- [ ] Mobile app performance

### 8.6 User Acceptance Testing
- [ ] UAT scenarios preparation
- [ ] UAT execution
- [ ] Bug fixes from UAT
- [ ] Sign-off

---

## PHASE 9: Deployment & Launch
**Status:** PENDING

### 9.1 Infrastructure
- [ ] Production server setup
- [ ] Database server setup
- [ ] Redis server setup
- [ ] File storage setup (S3)
- [ ] SSL certificate
- [ ] Domain configuration

### 9.2 CI/CD
- [ ] GitHub Actions / GitLab CI setup
- [ ] Automated testing on PR
- [ ] Automated deployment
- [ ] Database migration automation

### 9.3 Monitoring
- [ ] Error tracking (Sentry/Bugsnag)
- [ ] Application monitoring
- [ ] Server monitoring
- [ ] Log management

### 9.4 Documentation
- [ ] User manual
- [ ] Admin guide
- [ ] API documentation
- [ ] Deployment guide

### 9.5 Launch Preparation
- [ ] Data backup strategy
- [ ] Disaster recovery plan
- [ ] Support workflow
- [ ] Feedback collection system

### 9.6 Go Live
- [ ] Final deployment
- [ ] DNS switchover
- [ ] Smoke testing
- [ ] Monitoring active
- [ ] Support standby

---

## Progress Tracking

### Current Phase: 0 (Project Setup & Foundation)
### Current Progress: 95%

### Completed Items:
- Laravel 12 + Tailwind 4 + Alpine.js setup
- Design system (colors, typography, buttons, forms, cards)
- Alert/Toast components
- Modal components
- Table components with actions
- Badge components
- Dropdown components
- Empty state & Loading spinner
- Public pages (landing, pricing, login, register, forgot password, terms, privacy)
- Guest layout, navbar, footer
- Admin layout with collapsible sidebar
- Dashboard page (sample with widgets)
- Redis configuration for cache & queue
- Documentation (analysis, roles, design, phases)

### Remaining Phase 0 Items:
1. S3/MinIO configuration (optional, can be done later)
2. Contact page (optional)
3. API documentation structure
4. Database schema documentation

### Next Priority (Phase 1):
1. Database schema for multi-tenant
2. Spatie Permission setup
3. User registration & authentication
4. Company registration flow

---

*Document Version: 1.0*
*Created: 2026-02-11*
*Last Updated: 2026-02-11*
