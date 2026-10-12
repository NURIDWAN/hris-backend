# Master Plan: GajiPro Single-Tenant untuk PT Gemilang Sari Husada

> **Client**: PT Gemilang Sari Husada
> **Product**: GajiPro Single-Tenant Edition (rebrand opsional)
> **Base**: GajiPro HRIS/Payroll SaaS Multi-Tenant (existing)
> **Deployment Model**: On-premise / Dedicated Server untuk 1 client
> **Tanggal Dibuat**: 16 April 2026

---

## 1. Konteks Proyek

GajiPro saat ini adalah SaaS multi-tenant. Untuk PT Gemilang Sari Husada, kita perlu:

1. **Konversi** multi-tenant → single-tenant (lebih sederhana, lebih ringan, lebih aman untuk on-premise)
2. **Rebrand** (opsional) sesuai identitas PT Gemilang Sari Husada
3. **Harden** aplikasi agar production-grade
4. **Setup installer** sehingga bisa di-deploy dengan mudah di server client
5. **Training** & dokumentasi untuk admin + pengguna

## 2. Strategi Utama

### Pilihan Pendekatan

| Opsi | Pros | Cons | Rekomendasi |
|------|------|------|-------------|
| **A. Full Strip Multi-Tenant** | Kode lebih sederhana, query lebih cepat, tidak ada risiko tenant leak | Harus refactor 100+ file, banyak test harus diupdate | Risiko tinggi, effort besar |
| **B. Hide Multi-Tenant (Locked Single Company)** | Effort minimal, bisa reuse codebase existing, upgrade future mudah | Masih ada `company_id` di database (minor overhead) | **DIREKOMENDASIKAN** |
| **C. Fork Terpisah** | Full control, custom heavy | Maintenance nightmare saat ada security patch | Tidak direkomendasikan |

### Pendekatan Terpilih: **Opsi B (Locked Single Company)**

**Alasan**:
- Hemat waktu (80% codebase reusable)
- Security patches dari GajiPro upstream tetap bisa di-merge
- Jika PT Gemilang suatu saat butuh multi-subsidiary, tinggal unlock
- Tenant isolation yang sudah teruji jadi bonus defensive layer

**Strategi**:
1. Buat 1 company tetap di database (seeder)
2. Disable registration publik
3. Hard-code/lock tenant context ke single company ID
4. Remove UI Superadmin (atau jadikan hidden dev-only)
5. Remove Billing/Subscription UI & workflow
6. Keep `company_id` di schema (tidak perlu refactor)

## 3. Ruang Lingkup (In Scope)

### Yang TERMASUK

- [x] Konversi aplikasi ke single-tenant mode
- [x] Rebrand visual (logo, warna, nama) — opsional sesuai preferensi client
- [x] Hapus fitur billing/subscription/payment gateway
- [x] Hapus halaman register publik
- [x] Hapus superadmin panel (atau sembunyikan)
- [x] Setup installer/deployment script untuk server client
- [x] Setup otomatisasi backup database & file
- [x] Security hardening untuk production
- [x] Dokumentasi admin & end-user (Bahasa Indonesia)
- [x] Training 1-2 sesi untuk admin
- [x] Bug fixes & stability improvements
- [x] Performance tuning untuk ~100-500 karyawan
- [x] Seeding data awal (departemen, posisi, jadwal default)

### Yang TIDAK TERMASUK (Out of Scope)

- [ ] Integrasi dengan sistem eksternal client (harus dibahas terpisah)
- [ ] Custom report kompleks (bisa jadi change request)
- [ ] Pembangunan fitur baru yang tidak ada di GajiPro
- [ ] Migrasi data dari sistem lama (jika ada, perlu assessment terpisah)
- [ ] SLA 24/7 support (bahas di kontrak maintenance)
- [ ] Mobile app Flutter customization (standalone project)

## 4. Asumsi

1. Client sudah siapkan server VPS/Dedicated (Linux, min 4 CPU / 8GB RAM)
2. Client punya domain/subdomain + SSL certificate (atau pakai Let's Encrypt)
3. Database MySQL 8.0+ tersedia
4. Jumlah karyawan target: ~100-500 orang
5. Konten/data awal (org chart, leave types, dll) akan disiapkan di sesi onboarding
6. Client sudah punya email provider (SMTP) untuk notifikasi
7. Akses penuh (SSH, database) ke server production

## 5. Timeline Rekomendasi (6 Minggu)

```
Minggu 1-2: Konversi & Rebrand
  ├─ Week 1: Strip multi-tenant, disable billing, hide superadmin
  └─ Week 2: Rebrand, seeder single company, environment config

Minggu 3: Security Hardening & Maturity
  ├─ Fix known bugs
  ├─ Performance tuning
  └─ Penetration test internal

Minggu 4: Installation & Infrastructure
  ├─ Script installer
  ├─ Backup automation
  ├─ Monitoring
  └─ Staging deployment

Minggu 5: User Acceptance Testing (UAT)
  ├─ Deploy ke staging client
  ├─ UAT dengan admin client
  └─ Bug fixing round

Minggu 6: Go-Live & Training
  ├─ Production deployment
  ├─ Training admin (2 sesi)
  ├─ Onboarding data
  └─ Handover & warranty period
```

## 6. Tim & Peran

| Peran | Tanggung Jawab | Tersedia |
|-------|----------------|----------|
| **Tech Lead** | Arsitektur, conversion strategy, code review | Saya |
| **Backend Dev** | Refactor Laravel, testing | Saya |
| **DevOps** | Server setup, deployment, backup | Saya |
| **QA** | Testing, UAT facilitation | Saya |
| **Trainer** | Training admin client | Saya |
| **Client PIC** | Koordinator dari PT Gemilang | TBD |
| **Client Admin** | Admin sistem pasca go-live | TBD |

## 7. Dokumen Pendamping

Pilah pekerjaan ke dokumen detail:

1. **[CLIENT_GEMILANG_CONVERSION_TASKS.md](./CLIENT_GEMILANG_CONVERSION_TASKS.md)**
   → Daftar task detail untuk konversi aplikasi (development work)

2. **[CLIENT_GEMILANG_INSTALLATION_CHECKLIST.md](./CLIENT_GEMILANG_INSTALLATION_CHECKLIST.md)**
   → Checklist instalasi di server client + operations + go-live

3. *(Akan ditambahkan saat onboarding)* `CLIENT_GEMILANG_HANDOVER.md`
   → Dokumen handover pasca go-live (credentials, kontak, prosedur)

## 8. Deliverables Final

### Kode
- [x] Source code GajiPro single-tenant (private repo client)
- [x] Build artifacts siap deploy
- [x] Script installer (`deploy.sh` atau docker-compose)

### Dokumentasi
- [x] README.md untuk instalasi
- [x] Admin Manual (PDF + online) Bahasa Indonesia
- [x] Employee Manual (portal self-service)
- [x] Maintenance & Backup Guide
- [x] API Documentation (jika dibutuhkan)

### Konfigurasi
- [x] `.env.production` template
- [x] Nginx/Apache config
- [x] Systemd service files (queue, scheduler)
- [x] Backup cron config
- [x] SSL certificate setup

### Training
- [x] Video tutorial basic (5-10 menit per modul)
- [x] Live training 2 sesi (admin + payroll)
- [x] Knowledge base di `/docs/user-guide`

## 9. Risiko & Mitigasi

| Risiko | Probabilitas | Impact | Mitigasi |
|--------|--------------|--------|----------|
| Konversi introduces regression bugs | Sedang | Tinggi | Lengkapi test coverage, UAT rigid |
| Server client tidak memenuhi spec | Rendah | Tinggi | Verifikasi spec sebelum start, provide minimal spec doc |
| Data awal (org chart, gaji) terlambat | Sedang | Sedang | Template Excel untuk bulk import siap dari Week 3 |
| Perubahan requirements mid-project | Tinggi | Sedang | Change request formal + biaya tambahan di kontrak |
| Downtime saat go-live | Rendah | Tinggi | Go-live di akhir pekan/libur, rollback plan siap |
| Security breach pasca go-live | Rendah | Kritis | Security hardening (Fase 3), monitoring aktif, warranty 30 hari |

## 10. Success Criteria

Proyek dianggap SUKSES jika:

- [ ] Aplikasi ter-install di server production client
- [ ] Semua modul inti (Employee, Attendance, Leave, Payroll, Tax BPJS) berfungsi
- [ ] Admin client bisa operate tanpa bantuan harian
- [ ] Tidak ada data breach/kehilangan data dalam 30 hari pertama
- [ ] Uptime > 99% dalam 30 hari pertama
- [ ] Response time dashboard < 2 detik
- [ ] Payroll bulan pertama berhasil diproses dan dibayarkan tepat waktu
- [ ] Feedback score admin ≥ 8/10

---

## Next Step

1. **Review Master Plan ini dengan client** → konfirmasi scope, timeline, tim
2. **Tanda-tangani kontrak** → lock scope & harga
3. **Setup kickoff meeting** → introduce tim, schedule, komunikasi channel
4. **Mulai eksekusi** sesuai [CLIENT_GEMILANG_CONVERSION_TASKS.md](./CLIENT_GEMILANG_CONVERSION_TASKS.md)

---

> **Catatan**: Dokumen ini adalah living document. Update setiap ada perubahan scope, timeline, atau asumsi. Simpan versi di git untuk trail.
