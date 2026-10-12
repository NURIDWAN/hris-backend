# Client Edition Info

> **Client**: PT Gemilang Sari Husada
> **Edition**: GajiPro Single-Tenant
> **Base**: GajiPro HRIS/Payroll SaaS Multi-Tenant
> **Version**: 1.0.0-gemilang
> **Start Date**: 16 April 2026

---

## Overview

This repository is a client-specific single-tenant edition of GajiPro HRIS/Payroll system, dedicated for **PT Gemilang Sari Husada**.

The multi-tenant architecture is preserved in the database schema (defense in depth), but locked to a single company via:

- `config/tenant.php` — `single_mode = true`
- Environment variables: `SINGLE_TENANT_MODE`, `SINGLE_TENANT_COMPANY_ID`
- `CompanyObserver` prevents creating more than 1 company

## Key Differences from Upstream

| Feature | Upstream (SaaS) | Gemilang Edition |
|---------|-----------------|------------------|
| Registration | Public `/register` | Disabled (admin creates users) |
| Billing | Subscription & payment flow | Removed (licensed install) |
| Superadmin | `/superadmin` panel | Disabled |
| Payment Gateway | Xendit/Midtrans | Removed |
| Multiple Companies | Yes | 1 company only (locked) |
| Subscription Check | Enforced in middleware | Skipped in single_mode |

## Configuration

Required `.env` settings:

```ini
SINGLE_TENANT_MODE=true
SINGLE_TENANT_COMPANY_ID=1
```

## Deployment

Refer to `docs/CLIENT_GEMILANG_INSTALLATION_CHECKLIST.md` for full installation steps.

## Contact

- **Vendor**: AcademyJF
- **Support**: (TBD)
- **Emergency**: (TBD)

---

**⚠️ DO NOT merge this branch back to upstream `main`** — it contains client-specific removals that break the SaaS flow.
