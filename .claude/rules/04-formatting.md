# Data Formatting

## Currency (Indonesian Rupiah)

### PHP Helper
```php
// Format: Rp 10.000.000
number_format($amount, 0, ',', '.')

// With prefix
'Rp ' . number_format($amount, 0, ',', '.')
```

### JavaScript
```javascript
new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
}).format(amount)
```

### Blade Display
```blade
Rp {{ number_format($salary->base_salary, 0, ',', '.') }}
```

## Date & Time

### PHP Formatting
```php
// Date: 05 Maret 2026
Carbon::parse($date)->translatedFormat('d F Y')

// Short date: 05 Mar 2026
Carbon::parse($date)->translatedFormat('d M Y')

// DateTime: 05 Maret 2026 14:30
Carbon::parse($date)->translatedFormat('d F Y H:i')

// Time only: 14:30
Carbon::parse($time)->format('H:i')

// Month-Year: Maret 2026
Carbon::parse($date)->translatedFormat('F Y')
```

### Blade Display
```blade
{{ \Carbon\Carbon::parse($item->created_at)->translatedFormat('d M Y') }}
```

## Number Formatting

### Percentage
```php
number_format($percentage, 1) . '%'
// Output: 12.5%
```

### Employee ID
```blade
{{-- Monospace display --}}
<span class="font-mono text-sm">{{ $employee->employee_id }}</span>
```

### Payroll Number
```blade
<span class="font-mono">{{ $payroll->payroll_number }}</span>
```

## HR-Specific Formatting

### Attendance Duration
```php
// Hours & minutes: 8j 30m
$hours = floor($minutes / 60);
$mins = $minutes % 60;
echo "{$hours}j {$mins}m";
```

### Leave Balance
```blade
{{ number_format($balance->remaining_balance, 1) }} hari
```

### Salary Components
```blade
{{-- Earnings (positive, green) --}}
<span class="text-success-600">+ Rp {{ number_format($amount, 0, ',', '.') }}</span>

{{-- Deductions (negative, red) --}}
<span class="text-danger-600">- Rp {{ number_format($amount, 0, ',', '.') }}</span>

{{-- Net salary (bold) --}}
<span class="font-bold">Rp {{ number_format($netSalary, 0, ',', '.') }}</span>
```

### Tax Rate
```php
number_format($rate * 100, 1) . '%'
// e.g., "5.0%" for 0.05
```

### BPJS Contribution
```blade
{{ number_format($contribution->percentage, 2) }}% = Rp {{ number_format($contribution->amount, 0, ',', '.') }}
```

## Where to Apply

1. **Blade Views** — All user-facing currency, dates, numbers
2. **PDF/Reports** — Payslips, tax forms, attendance reports
3. **API Responses** — Format in API Resources when needed
4. **Exports** — Excel/CSV exports with proper formatting
