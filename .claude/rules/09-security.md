# Security

## Multi-Tenant Isolation (CRITICAL)

Every data query MUST be scoped by `company_id`. Failure to do so is a **critical security vulnerability**.

### Middleware Stack
1. **DetectAttack** — Detects SQL injection, XSS, path traversal, command injection, LDAP injection, XXE
2. **CheckBlockedIp** — Blocks requests from blocked IPs
3. **SetTenant** — Sets company context, checks subscription status

### Tenant Query Pattern
```php
$tenant = app('tenant');
$data = Model::where('company_id', $tenant->id)->get();
```

### Ownership Verification
```php
if ($model->company_id !== $tenant->id) {
    abort(404);
}
```

## Role-Based Access Control

| Role | Access Scope |
|------|-------------|
| Superadmin | All companies, system settings, security logs |
| Admin | Full company management |
| HR Manager | Employees, attendance, leave, organization |
| Payroll Manager | Salary, payroll, tax, BPJS |
| Employee | Portal self-service only |

### Spatie Permission + Team Context
```php
// Team context = company_id
setPermissionsTeamId($tenant->id);

// Check permission
$user->hasPermissionTo('manage employees');

// In routes
Route::middleware('permission:manage payroll')->group(function () {
    // ...
});
```

## Attack Detection

The `DetectAttack` middleware provides:

| Attack Type | Patterns Detected |
|-------------|-------------------|
| SQL Injection | 14 regex patterns (UNION, DROP, SELECT, etc.) |
| XSS | 11 patterns (script tags, event handlers, etc.) |
| Path Traversal | 12 patterns (../, etc/passwd, etc.) |
| Command Injection | 7 patterns (shell commands) |
| LDAP Injection | 2 patterns |
| XML/XXE Injection | 4 patterns |

### Auto-Blocking
- 5+ critical attacks in 1 hour → automatic 24-hour IP block
- Manual blocking available via Superadmin panel

## Input Validation

Always use Form Request with tenant-scoped rules:

```php
'department_id' => ['required', Rule::exists('departments', 'id')
    ->where('company_id', app('tenant')->id)],
```

## Audit Trail

### Spatie ActivityLog + LogsActivityTrait
- Tracks create, update, delete events
- Logs changed attributes (dirty fields only)
- Captures company_id context
- Stored in `activity_log` table

### Security Logs
- Attack detection events stored in `security_logs`
- Severity levels: critical, warning, info
- IP address, user agent, payload captured
- Sensitive data sanitized before storage

## Session Security

- Database session driver
- CSRF protection on all forms
- Rate limiting on login (5/min) and password reset (3/min)

## File Upload Security

- Validate MIME types
- Use random filenames
- Store in tenant-isolated directories
- Never serve uploads directly

## Environment Security

- Use `config()` not `env()` outside config files
- Never commit credentials
- Demo accounts protected from deletion

## Superadmin Protection

- Separate login route (`/superadmin/login`)
- `is_superadmin` flag on User model
- `EnsureSuperadmin` middleware
- No company_id association
