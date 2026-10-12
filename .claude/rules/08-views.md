# Blade View Architecture

## Approach

Blade + Tailwind CSS 4 + Alpine.js (NO Livewire, NO Filament).

## View File Naming

- Directories: kebab-case (`leave-requests/`, `salary-components/`)
- Files: standard names (`index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`)

## Layouts

### 1. Landing Layout
Public-facing pages (home, pricing, terms, privacy).

### 2. Admin Layout (`layouts/admin.blade.php`)
- Sidebar with navigation groups
- Top bar (user menu, notifications, company selector)
- Main content with breadcrumb
- `<x-confirm-dialog />` before `</body>`

### 3. Portal Layout
Employee self-service interface with simplified navigation.

### 4. Guest Layout
Authentication pages (login, register, forgot password).

### 5. Superadmin Layout
System administration interface.

## View Directory Structure

```
resources/views/
├── layouts/
│   ├── admin.blade.php
│   ├── portal.blade.php
│   ├── guest.blade.php
│   └── superadmin.blade.php
├── components/
│   ├── alert.blade.php
│   ├── badge.blade.php
│   ├── confirm-dialog.blade.php
│   └── table.blade.php
├── auth/
├── dashboard.blade.php
├── employees/
├── departments/
├── positions/
├── work-schedules/
├── attendances/
├── leave-types/
├── leave-balances/
├── leave-requests/
├── salary-components/
├── employee-salaries/
├── payrolls/
├── payroll-items/
├── overtime-requests/
├── reimbursements/
├── reimbursement-categories/
├── announcements/
├── reports/
├── settings/
├── portal/
├── superadmin/
├── imports/
└── landing/
```

## CRUD View Pattern

### Index Page
```blade
@extends('layouts.admin')

@section('title', 'Daftar Karyawan')

@section('content')
<div class="card">
    <div class="card-header flex items-center justify-between">
        <h3 class="card-title">Daftar Karyawan</h3>
        @can('manage employees')
            <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
                Tambah Karyawan
            </a>
        @endcan
    </div>

    {{-- Filters --}}
    <div class="card-body border-b">
        <form method="GET" class="flex gap-3">
            {{-- Search & filter inputs --}}
        </form>
    </div>

    {{-- Table --}}
    <x-table>
        <x-slot name="header">...</x-slot>
        @forelse($items as $item)
            <tr>...</tr>
        @empty
            <tr><td colspan="..." class="text-center py-8 text-secondary-500">Tidak ada data.</td></tr>
        @endforelse
    </x-table>

    {{-- Pagination --}}
    <div class="card-footer">
        {{ $items->links() }}
    </div>
</div>
@endsection
```

### Create/Edit Page
```blade
@extends('layouts.admin')

@section('title', 'Tambah Karyawan')

@section('content')
<div class="card">
    <form method="POST" action="{{ route('employees.store') }}">
        @csrf
        <div class="card-body space-y-4">
            {{-- Form fields using .input class --}}
        </div>
        <div class="card-footer flex justify-end gap-3">
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection
```

## Important Rules

1. **UI text in Bahasa Indonesia**
2. **Empty states**: Always show "Tidak ada data" message
3. **Pagination**: Always paginate lists
4. **Authorization**: Use `@can` directives for action buttons
5. **Old values**: Always use `old('field', $model->field ?? '')` pattern
6. **Error display**: Show validation errors below each field
7. **Flash messages**: Display success/error alerts at top of content
