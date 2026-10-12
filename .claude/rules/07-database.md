# Database Standards

## Engine

- **Database**: MySQL
- **Driver**: `mysql` (Laravel default)

## Schema Overview (63+ migrations)

### Core Tables
| Table | Purpose |
|-------|---------|
| `companies` | Tenant/company data |
| `users` | User accounts (soft deletes) |
| `cache` | Cache storage |
| `jobs` | Queue jobs |
| `personal_access_tokens` | Sanctum API tokens |

### Organization
| Table | Purpose |
|-------|---------|
| `departments` | Company departments |
| `positions` | Job positions |
| `employees` | Employee master data |
| `office_locations` | Office/branch locations |
| `employee_office_locations` | Pivot: employee-office |

### Attendance
| Table | Purpose |
|-------|---------|
| `work_schedules` | Shift/schedule definitions |
| `attendances` | Daily attendance records |
| `face_verification_logs` | Face recognition logs |
| `employee_face_embeddings` | Stored face data |
| `holidays` | Company holidays |

### Leave
| Table | Purpose |
|-------|---------|
| `leave_types` | Leave type definitions |
| `leave_balances` | Employee leave balances |
| `leave_requests` | Leave request records |

### Payroll & Compensation
| Table | Purpose |
|-------|---------|
| `salary_components` | Salary component types |
| `employee_salaries` | Employee salary config |
| `employee_salary_components` | Pivot: employee-salary component |
| `payrolls` | Payroll batch records |
| `payroll_items` | Individual payslips |
| `payroll_item_details` | Payslip line items |
| `payroll_settings` | Payroll configuration |

### Tax & BPJS (Indonesia)
| Table | Purpose |
|-------|---------|
| `pph21_settings` | Income tax settings |
| `pph21_rates` | Tax bracket rates |
| `pph21_ter_rates` | TER method rates |
| `ptkp_settings` | Tax exemption settings |
| `bpjs_tk_settings` | BPJS Ketenagakerjaan config |
| `bpjs_kes_settings` | BPJS Kesehatan config |
| `jkk_risk_rates` | Work accident insurance rates |
| `thr_settings` | THR bonus settings |
| `thr_payments` | THR payment records |

### Overtime & Reimbursement
| Table | Purpose |
|-------|---------|
| `overtime_settings` | Overtime rate configuration |
| `overtime_requests` | Overtime request records |
| `reimbursement_categories` | Reimbursement categories |
| `reimbursements` | Reimbursement records |

### Approval Workflow
| Table | Purpose |
|-------|---------|
| `approval_workflows` | Workflow definitions |
| `approval_workflow_steps` | Workflow step configuration |

### Tax Forms
| Table | Purpose |
|-------|---------|
| `tax_form_1721a1` | Bukti Potong PPh 21 |
| `spt_1721` | Annual tax return header |
| `spt_1721_employees` | SPT employee details |
| `spt_1721_monthly` | SPT monthly summary |

### Documents
| Table | Purpose |
|-------|---------|
| `employee_documents` | Employee document files |
| `employee_exits` | Employee exit/termination |

### Communication
| Table | Purpose |
|-------|---------|
| `announcements` | Company announcements |
| `notifications` | System notifications |
| `device_tokens` | Push notification tokens |

### Billing & Subscription
| Table | Purpose |
|-------|---------|
| `subscription_plans` | Available plans |
| `subscriptions` | Company subscriptions |
| `payments` | Payment history |
| `payment_gateway_settings` | Gateway configuration |
| `invoices` | Billing invoices |

### Security
| Table | Purpose |
|-------|---------|
| `security_logs` | Attack detection logs |
| `blocked_ips` | Blocked IP addresses |
| `activity_log` | Spatie ActivityLog |

### Permissions (Spatie)
| Table | Purpose |
|-------|---------|
| `permissions` | Permission definitions (team_id) |
| `roles` | Role definitions (team_id) |
| `role_has_permissions` | Pivot |
| `model_has_roles` | Pivot |
| `model_has_permissions` | Pivot |

## Column Naming Conventions

| Pattern | Usage | Example |
|---------|-------|---------|
| `{table}_id` | Foreign key | `company_id`, `department_id` |
| `is_*` | Boolean flag | `is_active`, `is_annual` |
| `*_at` | Timestamp | `approved_at`, `paid_at` |
| `*_date` | Date field | `join_date`, `birth_date` |
| `*_amount` | Money value | `base_amount`, `net_amount` |
| `*_rate` | Percentage/rate | `tax_rate`, `overtime_rate` |
| `*_path` | File path | `avatar_path`, `document_path` |
| `*_reason` | Explanation text | `rejection_reason`, `deletion_reason` |
| `status` | Status enum | `'draft'`, `'approved'`, `'paid'` |

## Data Types

| Type | Usage |
|------|-------|
| `DECIMAL(15,2)` | Currency/money amounts |
| `DECIMAL(5,2)` | Percentages and rates |
| `VARCHAR` | Status fields, short text |
| `TEXT` | Long descriptions, notes |
| `DATE` | Dates without time |
| `TIMESTAMP` | Date with time |
| `BOOLEAN` | True/false flags |
| `JSON` | Flexible structured data |

## Foreign Key Conventions

- **CASCADE on delete**: Child records that must be deleted with parent
- **SET NULL on delete**: Optional relationships
- **RESTRICT on delete**: Prevent deletion if children exist

## Multi-Tenant

- Every tenant-scoped table MUST have `company_id` column
- Always index `company_id` for query performance
- Composite unique constraints include `company_id`

```php
$table->foreignId('company_id')->constrained()->cascadeOnDelete();
$table->unique(['company_id', 'employee_id']); // Unique per tenant
```

## Factory Standards

Every model must have a factory with relevant states:

```php
class EmployeeFactory extends Factory
{
    public function definition(): array { /* ... */ }

    public function active(): static { /* ... */ }
    public function inactive(): static { /* ... */ }
    public function withSalary(): static { /* ... */ }
}
```
