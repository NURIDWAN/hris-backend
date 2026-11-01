# Panduan Lengkap: MariaDB Galera Database Cluster (High Availability)

Dokumen ini menjelaskan arsitektur, cara instalasi, pengoperasian, monitoring, dan pengujian **MariaDB Galera Cluster (3-Node Multi-Master)** dengan **HAProxy Load Balancer** untuk GajiPro HRIS & Payroll SaaS.

---

## 1. Arsitektur Cluster

```
┌─────────────────────────────────────────────────────────────┐
│                    Klien / Aplikasi                         │
│   - Laravel Web & API (gajipro-app)                         │
│   - Queue Worker (gajipro-queue)                            │
│   - Host / DBeaver / Navicat                                │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│              HAProxy Load Balancer (db-cluster)              │
│   - Virtual Primary Port: 3306 (internal) / 3307 (host)     │
│   - Read-Only Pool Port: 3308 (opsional)                    │
│   - Web Dashboard GUI: http://localhost:8404                │
│   - Healthcheck interval: 2s (TCP keepalive)                │
└──────────────┬──────────────────────┬───────────────────────┘
               │                      │                       │
      (Primary Writer)            (Backup 1)              (Backup 2)
               ▼                      ▼                       ▼
      ┌────────────────┐     ┌────────────────┐      ┌────────────────┐
      │ mariadb-node1  │<===>│ mariadb-node2  │<====>│ mariadb-node3  │
      │ (Bootstrap)    │     │ (Galera Peer)  │      │ (Galera Peer)  │
      └────────────────┘     └────────────────┘      └────────────────┘
               ▲                                              ▲
               └════════════ Galera Sync WSREP ═══════════════┘
```

### Mengapa 3 Node?
Galera Cluster membutuhkan jumlah node **ganjil** (minimal 3) untuk mempertahankan *quorum* dan mencegah kondisi **split-brain** saat terjadi partisi jaringan. Jika 1 node mati, 2 node yang tersisa (66.7% > 50%) tetap membentuk *Primary Component* dan terus melayani transaksi tanpa henti.

### Mengapa HAProxy Writer Active-Passive?
Meskipun Galera adalah *multi-master* di mana semua node bisa menerima write, transaksi intensif seperti penggajian (*payroll batch*) atau absensi massal berisiko mengalami *certification conflict / optimistic locking rollback* jika ditulis bersamaan ke node berbeda. HAProxy mengarahkan seluruh write ke **Node 1**; jika Node 1 gagal, HAProxy secara instan dan otomatis mengalihkan traffic ke **Node 2** atau **Node 3** tanpa interupsi.

---

## 2. File-File Konfigurasi

| File | Deskripsi |
|---|---|
| [`docker-compose.db-cluster.yml`](../docker-compose.db-cluster.yml) | Definisi 3 container MariaDB Galera, 1 container HAProxy, volumes persisten, dan network bersama. |
| [`docker-compose.cluster.yml`](../docker-compose.cluster.yml) | File override untuk menghubungkan `docker-compose.yml` utama ke cluster database. |
| [`docker/cluster/galera.cnf`](../docker/cluster/galera.cnf) | Konfigurasi replikasi WSREP Galera, mariabackup SST, dan optimasi InnoDB. |
| [`docker/cluster/haproxy.cfg`](../docker/cluster/haproxy.cfg) | Konfigurasi load balancing, auto-failover, dan web stats dashboard. |
| [`docker/cluster/cluster-init.sh`](../docker/cluster/cluster-init.sh) | Script CLI helper untuk bootstrap, status check, dan failover test. |
| [`.env.cluster.example`](../.env.cluster.example) | Contoh variabel environment Laravel untuk koneksi ke cluster. |

---

## 3. Cara Menjalankan Cluster Database

### Cara 1: Menggunakan Script Helper (Direkomendasikan)
Script helper otomatis menangani urutan bootstrap:
```bash
./docker/cluster/cluster-init.sh start
```

### Cara 2: Menggunakan Docker Compose Manual
```bash
# 1. Pastikan network bersama sudah ada
docker network create gajipro-network 2>/dev/null || true

# 2. Jalankan Node 1 (Bootstrap)
docker compose -f docker-compose.db-cluster.yml up -d mariadb-node1

# 3. Tunggu Node 1 berstatus healthy (sekitar 15-30 detik)
docker inspect -f '{{.State.Health.Status}}' gajipro-mariadb-node1

# 4. Jalankan Node 2, Node 3, dan HAProxy
docker compose -f docker-compose.db-cluster.yml up -d mariadb-node2 mariadb-node3 db-cluster
```

---

## 4. Menghubungkan Backend GajiPro (Laravel) ke Cluster

Cukup ubah konfigurasi `.env` pada backend:
```env
# Koneksi Database diarahkan ke service HAProxy 'db-cluster'
DB_CONNECTION=mariadb
DB_HOST=db-cluster
DB_PORT=3306
DB_DATABASE=gajipro
DB_USERNAME=gajipro
DB_PASSWORD=secret
```

Jalankan container aplikasi dengan file override:
```bash
docker compose -f docker-compose.yml -f docker-compose.cluster.yml up -d
```
Container `app` dan `queue` akan otomatis terhubung ke `db-cluster` melalui Docker network `gajipro-network`.

---

## 5. Memeriksa Status Kesehatan Cluster

Jalankan script helper:
```bash
./docker/cluster/cluster-init.sh status
```

Atau query langsung:
```bash
docker exec -it gajipro-mariadb-node1 mysql -ugajipro -psecret -e "
    SHOW STATUS LIKE 'wsrep_cluster_size';
    SHOW STATUS LIKE 'wsrep_cluster_status';
    SHOW STATUS LIKE 'wsrep_local_state_comment';
"
```

**Hasil yang diharapkan:**
- `wsrep_cluster_size`: `3` (ketiga node aktif dan terhubung).
- `wsrep_cluster_status`: `Primary` (cluster dalam keadaan sehat).
- `wsrep_local_state_comment`: `Synced` (node sudah sinkron 100%).

---

## 6. Web Dashboard Monitoring (HAProxy Stats)

Buka browser Anda dan akses:
- **URL:** [http://localhost:8404](http://localhost:8404)
- **Username:** `admin`
- **Password:** `secret`

Dashboard akan menampilkan status visual hijau pada ketiga node MariaDB, jumlah sesi aktif, dan status failover secara real-time.

---

## 7. Pengujian Replikasi & Failover

### Uji Replikasi Sinkron (Data Sync Test)
Jalankan perintah:
```bash
./docker/cluster/cluster-init.sh test-sync
```
Perintah ini akan menulis data ke Node 1, lalu membaca langsung dari Node 2 dan Node 3 untuk memastikan replikasi instan berlangsung dengan sempurna.

### Uji Auto-Failover (Simulasi Bencana)
Jalankan perintah:
```bash
./docker/cluster/cluster-init.sh test-failover
```
Alur pengujian:
1. Node 1 dimatikan (`docker stop gajipro-mariadb-node1`).
2. HAProxy mendeteksi Node 1 down dalam 2-4 detik.
3. Kueri aplikasi otomatis dialihkan ke Node 2/Node 3 tanpa error koneksi.
4. Node 1 dihidupkan kembali dan otomatis bergabung ke cluster serta sinkronisasi data tertinggal via IST (*Incremental State Transfer*).

---

## 8. Panduan Maintenance & Disaster Recovery

### Menghentikan Cluster dengan Aman
Hentikan node non-primary terlebih dahulu, lalu bootstrap node terakhir:
```bash
docker compose -f docker-compose.db-cluster.yml stop mariadb-node3 mariadb-node2
docker compose -f docker-compose.db-cluster.yml stop mariadb-node1 db-cluster
```

### Restart Seluruh Node Setelah Listrik Mati Total (Clean Recovery)
Jika semua container mati bersamaan:
1. Temukan node dengan urutan transaksi tertinggi (`seqno` terbesar) pada file `grastate.dat`:
   ```bash
   cat /var/lib/docker/volumes/gajipro-backend_db_cluster_node1_data/_data/grastate.dat
   ```
2. Pastikan nilai `safe_to_bootstrap: 1` pada node tersebut.
3. Nyalakan node tersebut terlebih dahulu sebagai bootstrap:
   ```bash
   docker compose -f docker-compose.db-cluster.yml up -d mariadb-node1
   ```
4. Setelah itu, nyalakan node lainnya:
   ```bash
   docker compose -f docker-compose.db-cluster.yml up -d
   ```
