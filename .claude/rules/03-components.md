# Blade Components

## UI Components

### Buttons
```blade
<button class="btn btn-primary">Primary</button>
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-ghost">Ghost</button>
<button class="btn btn-white">White</button>
<button class="btn btn-accent">Accent</button>
<button class="btn btn-danger">Danger</button>
<button class="btn btn-success">Success</button>

{{-- Sizes --}}
<button class="btn btn-primary btn-sm">Small</button>
<button class="btn btn-primary btn-lg">Large</button>

{{-- Link Button --}}
<a href="#" class="btn btn-primary">Link Button</a>
```

### Cards
```blade
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Title</h3>
    </div>
    <div class="card-body">
        Content
    </div>
    <div class="card-footer">
        Footer actions
    </div>
</div>
```

### Badges
```blade
<x-badge type="success">Active</x-badge>
<x-badge type="warning">Pending</x-badge>
<x-badge type="danger">Rejected</x-badge>
<x-badge type="info">Processing</x-badge>
<x-badge type="secondary">Draft</x-badge>
<x-badge type="primary">Approved</x-badge>
```

### Stat Cards
```blade
<div class="stat-card">
    <div class="stat-card-icon bg-primary-100">
        <svg class="w-6 h-6 text-primary-600">...</svg>
    </div>
    <div>
        <div class="stat-card-value">{{ $value }}</div>
        <div class="stat-card-label">{{ $label }}</div>
    </div>
</div>
```

### Alerts
```blade
<x-alert type="success" dismissible>Success message</x-alert>
<x-alert type="danger">Error message</x-alert>
<x-alert type="warning">Warning message</x-alert>
<x-alert type="info">Info message</x-alert>
```

### Tables
```blade
<x-table>
    <x-slot name="header">
        <th>Column 1</th>
        <th>Column 2</th>
        <th>Actions</th>
    </x-slot>
    @foreach($items as $item)
        <tr>
            <td>{{ $item->field1 }}</td>
            <td>{{ $item->field2 }}</td>
            <td>
                {{-- Action buttons --}}
            </td>
        </tr>
    @endforeach
</x-table>
```

### Confirmation Dialog
```blade
{{-- Place in layout before </body> --}}
<x-confirm-dialog />

{{-- Trigger with Alpine.js $dispatch --}}
<button
    type="button"
    @click="$dispatch('confirm-dialog', {
        title: 'Hapus Data',
        message: 'Apakah Anda yakin?',
        confirmText: 'Ya, Hapus',
        type: 'danger',
        formAction: '{{ route('resource.destroy', $model) }}'
    })"
    class="btn btn-danger btn-sm"
>
    Hapus
</button>
```

## Form Components

### Text Input
```blade
<label for="field" class="block text-sm font-medium text-secondary-700 mb-1">
    Label <span class="text-danger-500">*</span>
</label>
<input type="text" name="field" id="field"
       value="{{ old('field', $model->field ?? '') }}"
       class="input w-full @error('field') border-danger-500 @enderror"
       required>
@error('field')
    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
@enderror
```

### Select
```blade
<select name="field" id="field"
        class="input w-full @error('field') border-danger-500 @enderror">
    <option value="">Pilih opsi...</option>
    @foreach($options as $option)
        <option value="{{ $option->id }}"
            {{ old('field', $model->field ?? '') == $option->id ? 'selected' : '' }}>
            {{ $option->name }}
        </option>
    @endforeach
</select>
```

### Textarea
```blade
<textarea name="field" id="field" rows="3"
          class="input w-full @error('field') border-danger-500 @enderror"
>{{ old('field', $model->field ?? '') }}</textarea>
```

### Input with Prefix (Currency)
```blade
{{-- IMPORTANT: Use inline style, NOT Tailwind classes --}}
<div style="position: relative;">
    <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
                 color: #64748b; font-size: 14px; pointer-events: none;">Rp</span>
    <input type="text" name="amount" id="amount"
           value="{{ old('amount') }}"
           class="input w-full"
           style="padding-left: 36px;"
           placeholder="10.000.000">
</div>
```

## Domain-Specific Patterns

### Employee Card Pattern
Used in employee list/grid views with avatar, name, position, department, status badge.

### Approval Action Buttons
Approve/Reject buttons with confirmation dialog for leave requests, overtime, reimbursements.

### Payroll Status Flow
Draft → Processing → Approved → Paid (with appropriate badge colors at each step).

### Attendance Status Indicators
On Time (green), Late (red), Early Leave (yellow), Absent (gray).
