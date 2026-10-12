# Code Style & Conventions

## Standards

- **PSR-12** + Laravel Pint
- **Type hints** required on all parameters and return types
- **Max line length**: 120 characters
- **Formatter**: Run `vendor/bin/pint --dirty --format agent` before committing

## Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Class | PascalCase | `EmployeeController`, `PayrollService` |
| Method | camelCase | `calculateNetSalary()`, `getActiveDepartments()` |
| Variable | camelCase | `$totalAmount`, `$employeeCount` |
| Constant | UPPER_SNAKE | `MAX_LEAVE_DAYS`, `DEFAULT_TAX_RATE` |
| Config key | snake_case | `payroll.default_currency` |
| DB column | snake_case | `full_name`, `company_id`, `is_active` |
| Route name | dot.notation | `employees.index`, `portal.leave.store` |
| View file | kebab-case | `leave-requests/index.blade.php` |
| Enum key | TitleCase | `Active`, `Pending`, `InProgress` |

## PHP 8 Features (USE THESE)

### Constructor Property Promotion
```php
public function __construct(
    public PayrollService $payrollService,
    public TaxService $taxService,
) {}
```

### Match Expressions
```php
$label = match ($status) {
    'draft' => 'Draft',
    'processing' => 'Diproses',
    'approved' => 'Disetujui',
    'paid' => 'Dibayar',
    default => ucfirst($status),
};
```

### Null Safe Operator
```php
$departmentName = $employee->department?->name;
```

### Named Arguments
```php
Employee::factory()->create(
    company_id: $company->id,
    department_id: $department->id,
);
```

### Enums
```php
enum PayrollStatus: string
{
    case Draft = 'draft';
    case Processing = 'processing';
    case Approved = 'approved';
    case Paid = 'paid';
}
```

## Eloquent Style

### Fluent Query Building
```php
$employees = Employee::query()
    ->with(['department', 'position'])
    ->where('company_id', $tenant->id)
    ->when($request->search, fn ($q, $search) =>
        $q->where('full_name', 'like', "%{$search}%")
    )
    ->when($request->status, fn ($q, $status) =>
        $q->where('status', $status)
    )
    ->latest()
    ->paginate(15);
```

### Eager Loading (Prevent N+1)
```php
// Always eager load relationships used in views
$payrolls = Payroll::with([
    'items.employee',
    'items.details',
])->where('company_id', $tenant->id)->get();
```

## Blade Style

```blade
{{-- Use Blade directives --}}
@if($condition)
@foreach($items as $item)
@can('permission')
@error('field')

{{-- Component usage --}}
<x-badge type="success">{{ $label }}</x-badge>
<x-alert type="warning">{{ $message }}</x-alert>

{{-- Named routes --}}
{{ route('employees.show', $employee) }}
```

## Comments

- Prefer PHPDoc blocks over inline comments
- Only comment complex business logic
- No obvious comments like `// Get employee`

```php
/**
 * Calculate PPh 21 using TER (Tarif Efektif Rata-rata) method.
 *
 * @param array{gross_income: float, ptkp_status: string, months_worked: int} $params
 */
public function calculatePph21Ter(array $params): float
{
    // ...
}
```

## Anti-Patterns (NEVER DO)

1. **God controllers** — Keep controllers thin, use services for business logic
2. **Magic strings** — Use enums or constants for repeated values
3. **Direct `DB::`** — Use `Model::query()` instead
4. **N+1 queries** — Always eager load relationships
5. **`env()` outside config** — Use `config('key')` instead
6. **Inline validation** — Use Form Request classes
7. **Hardcoded Indonesian text** — Use translation keys where translation files exist
8. **Business logic in controllers** — Extract to services
9. **Missing `company_id`** — Every tenant query must be scoped
10. **Raw SQL** — Use Eloquent query builder
11. **Unprotected file storage** — Always validate and isolate uploads
12. **Empty constructors** — Remove if no parameters
