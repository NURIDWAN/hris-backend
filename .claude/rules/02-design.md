# Design System

## Color Palette

### Primary (Ocean Blue)
```
--color-primary-50:  #eff6ff
--color-primary-100: #dbeafe
--color-primary-200: #bfdbfe
--color-primary-300: #93c5fd
--color-primary-400: #60a5fa
--color-primary-500: #3b82f6  ← Base
--color-primary-600: #2563eb
--color-primary-700: #1d4ed8
--color-primary-800: #1e40af
--color-primary-900: #1e3a8a
--color-primary-950: #172554
```

### Semantic Colors

| Purpose   | Base Color | Variable Prefix   |
|-----------|------------|-------------------|
| Secondary | #64748b    | `secondary-*`     |
| Accent    | #f97316    | `accent-*`        |
| Success   | #14b8a6    | `success-*`       |
| Warning   | #f59e0b    | `warning-*`       |
| Danger    | #f43f5e    | `danger-*`        |
| Info      | #0ea5e9    | `info-*`          |

### Status Colors (HR Context)

| Status          | Color           | Usage                    |
|-----------------|-----------------|--------------------------|
| Active          | `success-*`     | Active employees         |
| Inactive        | `secondary-*`   | Inactive employees       |
| Pending         | `warning-*`     | Pending approvals        |
| Approved        | `success-*`     | Approved requests        |
| Rejected        | `danger-*`      | Rejected requests        |
| Cancelled       | `secondary-*`   | Cancelled requests       |
| Draft           | `secondary-*`   | Draft payroll            |
| Processing      | `info-*`        | Processing payroll       |
| Completed       | `success-*`     | Completed payroll        |
| Paid            | `primary-*`     | Paid payroll             |
| Late            | `danger-*`      | Late attendance          |
| Early Leave     | `warning-*`     | Early leave attendance   |
| On Time         | `success-*`     | On time attendance       |

## Typography

- **Primary Font**: Plus Jakarta Sans
- **Fallback**: Inter, system-ui, sans-serif
- **Monospace**: For employee IDs, payroll numbers

### Text Styles

| Element        | Size / Weight                |
|----------------|------------------------------|
| Page Title     | text-2xl font-bold           |
| Card Title     | text-lg font-semibold        |
| Section Title  | text-base font-semibold      |
| Body Text      | text-sm                      |
| Small/Label    | text-xs                      |
| Currency       | font-mono tabular-nums       |
| Employee Code  | font-mono text-sm            |

## Layouts

### 1. Landing Page Layout
- Public pages: home, pricing, terms, privacy
- No sidebar, top navigation bar

### 2. App Dashboard Layout (`layouts/admin.blade.php`)
- Sidebar navigation (light & dark themes)
- Top bar with user menu & notifications
- Main content area with breadcrumb

### 3. Employee Portal Layout
- Simplified sidebar for employee self-service
- Portal-specific navigation items

### 4. Guest Layout
- Authentication pages: login, register, forgot password
- Minimal layout, centered card

### 5. Superadmin Layout
- Superadmin-specific sidebar
- System-wide management interface

## Spacing & Components

### Cards
- Border-radius: 16px (`rounded-2xl`)
- Background: white
- Shadow: subtle box-shadow
- Padding: card-body uses standard padding

### Buttons
- Border-radius: 8px
- Padding: 10px 20px (default)
- Sizes: btn-sm, btn (default), btn-lg

### Inputs
- Border-radius: 8px
- Border: 1px solid #cbd5e1
- Padding: 8px 12px
- Focus: blue ring shadow

### Stat Cards
- Icon with colored background
- Value (large, bold)
- Label (small, muted)
- Optional change indicator (up/down)

## Alpine.js Patterns

### Tab Navigation
```javascript
x-data="{ activeTab: 'general' }"
```

### Modals & Dialogs
```javascript
x-data="{ open: false }"
@click="open = true"
x-show="open"
```

### Confirmation Dialog
Use `$dispatch('confirm-dialog', {...})` pattern (see CLAUDE.md for details).

## Important Rules

1. **Modal/Dialog positioning**: ALWAYS use inline `style` for `position: fixed`, NOT Tailwind classes
2. **Input prefix positioning**: ALWAYS use inline `style`, NOT Tailwind classes
3. **Form inputs**: ALWAYS use `.input` class
4. **Buttons**: ALWAYS use `.btn` + variant class
