# Model Patterns

## Model Structure

```php
class Employee extends Model
{
    use HasFactory, SoftDeletes, LogsActivityTrait;

    protected $fillable = [
        'company_id',
        'employee_id',
        'full_name',
        'email',
        // ...
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'is_active' => 'boolean',
            'birth_date' => 'date',
        ];
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
```

## Key Conventions

1. **`casts()` method** over `$casts` property (Laravel 12 convention)
2. **`$fillable`** — always define explicitly
3. **Return type hints** on all relationships
4. **`company_id`** on all tenant-scoped models
5. **`LogsActivityTrait`** on models that need audit trail
6. **`SoftDeletes`** on models where data recovery is needed

## LogsActivityTrait

Custom trait extending Spatie ActivityLog:

```php
use App\Traits\LogsActivityTrait;

class Employee extends Model
{
    use LogsActivityTrait;

    // Auto-logs create, update, delete events
    // Captures changed attributes only
    // Sets company_id context automatically
}
```

## Enums

Use PHP 8 backed enums:

```php
enum EmployeeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Terminated = 'terminated';
    case Resigned = 'resigned';
}
```

Enum keys: TitleCase. Values: snake_case or lowercase.

## Common Scopes

```php
// Tenant scope (always apply)
public function scopeForCompany(Builder $query, int $companyId): Builder
{
    return $query->where('company_id', $companyId);
}

// Status scope
public function scopeActive(Builder $query): Builder
{
    return $query->where('is_active', true);
}

// Date range scope
public function scopeBetweenDates(Builder $query, string $start, string $end): Builder
{
    return $query->whereBetween('date', [$start, $end]);
}
```

## Accessors

```php
// Status label for display
protected function statusLabel(): Attribute
{
    return Attribute::get(fn () => match ($this->status) {
        'active' => 'Aktif',
        'inactive' => 'Tidak Aktif',
        default => ucfirst($this->status),
    });
}
```

## Auto-Generated Fields

```php
protected static function booted(): void
{
    static::creating(function (self $model) {
        if (empty($model->employee_id)) {
            $model->employee_id = self::generateEmployeeId($model->company_id);
        }
    });
}

// Pattern: {PREFIX}{YEAR}{SEQUENCE}
// Example: EMP20260001
```

## Model List (54+ models)

### Core
`User`, `Company`

### Organization
`Department`, `Position`, `Employee`, `OfficeLocation`, `EmployeeOfficeLocation`

### Attendance
`WorkSchedule`, `Attendance`, `FaceVerificationLog`, `EmployeeFaceEmbedding`, `Holiday`

### Leave
`LeaveType`, `LeaveBalance`, `LeaveRequest`

### Payroll
`SalaryComponent`, `EmployeeSalary`, `EmployeeSalaryComponent`, `Payroll`, `PayrollItem`, `PayrollItemDetail`, `PayrollSetting`

### Tax & BPJS
`Pph21Setting`, `Pph21Rate`, `Pph21TerRate`, `PtkpSetting`, `BpjsTkSetting`, `BpjsKesSetting`, `JkkRiskRate`, `ThrSetting`, `ThrPayment`

### Overtime & Reimbursement
`OvertimeSetting`, `OvertimeRequest`, `ReimbursementCategory`, `Reimbursement`

### Approval
`ApprovalWorkflow`, `ApprovalWorkflowStep`

### Tax Forms
`TaxForm1721A1`, `Spt1721`, `Spt1721Employee`, `Spt1721Monthly`

### Documents
`EmployeeDocument`, `EmployeeExit`

### Communication
`Announcement`, `Notification`, `DeviceToken`

### Billing
`SubscriptionPlan`, `Subscription`, `Payment`, `PaymentGatewaySetting`, `Invoice`

### Security
`SecurityLog`, `BlockedIp`

### Audit
`Activity` (Spatie ActivityLog)
