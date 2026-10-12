# Controller Architecture

## Resource Controllers

Standard CRUD pattern for most modules:

```php
class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = app('tenant');

        $employees = Employee::with(['department', 'position'])
            ->where('company_id', $tenant->id)
            ->when($request->search, fn ($q, $search) =>
                $q->where('full_name', 'like', "%{$search}%")
            )
            ->when($request->department_id, fn ($q, $id) =>
                $q->where('department_id', $id)
            )
            ->latest()
            ->paginate(15);

        return view('employees.index', compact('employees'));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $tenant = app('tenant');

        $employee = Employee::create([
            'company_id' => $tenant->id,
            ...$request->validated(),
        ]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Karyawan berhasil ditambahkan.');
    }
}
```

## Tenant Isolation

Every controller MUST scope queries by `company_id`:

```php
// Get tenant context
$tenant = app('tenant');

// Query with tenant isolation
$items = Model::where('company_id', $tenant->id)->get();

// Verify ownership for show/edit/delete
if ($model->company_id !== $tenant->id) {
    abort(404);
}
```

## Form Requests

Always use Form Request classes for validation (never inline):

```php
class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = app('tenant');

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('employees')->where('company_id', $tenant->id)],
            'department_id' => ['required', Rule::exists('departments', 'id')->where('company_id', $tenant->id)],
            'position_id' => ['required', Rule::exists('positions', 'id')->where('company_id', $tenant->id)],
            'join_date' => ['required', 'date'],
        ];
    }
}
```

## Authorization

Use Spatie Permission for role-based access:

```php
// In routes
Route::resource('employees', EmployeeController::class)
    ->middleware('permission:manage employees');

// In controller
public function __construct()
{
    $this->middleware('permission:manage employees');
}

// In blade
@can('manage employees')
    <a href="{{ route('employees.create') }}" class="btn btn-primary">Tambah Karyawan</a>
@endcan
```

## Flash Messages

```php
return redirect()
    ->route('resource.index')
    ->with('success', 'Data berhasil disimpan.');

return redirect()
    ->back()
    ->with('error', 'Terjadi kesalahan.');
```

## Controller Types

### Admin Controllers (`app/Http/Controllers/`)
Main dashboard controllers for HR/Payroll management.

### Portal Controllers (`app/Http/Controllers/Portal/`)
Employee self-service controllers.

### Superadmin Controllers (`app/Http/Controllers/Superadmin/`)
System-wide management (companies, subscriptions, security).

### Report Controllers (`app/Http/Controllers/Reports/`)
Report generation controllers.

### Import Controllers (`app/Http/Controllers/Import/`)
Bulk data import controllers.

### Settings Controllers (`app/Http/Controllers/Settings/`)
Company settings controllers.

## Service Layer

For complex business logic (payroll calculation, tax computation), use Service classes:

```php
// app/Services/PayrollService.php
class PayrollService
{
    public function calculatePayroll(Payroll $payroll): void
    {
        DB::transaction(function () use ($payroll) {
            // Complex payroll calculation logic
        });
    }
}
```

## Route Registration

```php
// Standard resource
Route::resource('employees', EmployeeController::class);

// With middleware
Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('employees', EmployeeController::class);
});

// Custom actions
Route::post('/payrolls/{payroll}/process', [PayrollController::class, 'process'])->name('payrolls.process');
Route::post('/payrolls/{payroll}/approve', [PayrollController::class, 'approve'])->name('payrolls.approve');
```
