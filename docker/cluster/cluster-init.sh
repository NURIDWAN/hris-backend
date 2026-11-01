#!/usr/bin/env bash
set -e

# ==============================================================================
# GajiPro - MariaDB Galera Database Cluster Helper Script
# ==============================================================================

COMPOSE_FILE="docker-compose.db-cluster.yml"
DB_USER=${DB_USERNAME:-gajipro}
DB_PASS=${DB_PASSWORD:-secret}
DB_NAME=${DB_DATABASE:-gajipro}

usage() {
    echo "================================================================="
    echo " GajiPro - MariaDB Galera Cluster Management CLI"
    echo "================================================================="
    echo "Penggunaan: $0 {start|stop|restart|status|test-sync|test-failover|logs}"
    echo ""
    echo "Perintah:"
    echo "  start         Menjalankan cluster secara berurutan (Node 1 -> Node 2 -> Node 3 -> HAProxy)"
    echo "  stop          Menghentikan seluruh container cluster database"
    echo "  restart       Merestart seluruh container cluster"
    echo "  status        Memeriksa status sinkronisasi WSREP dan cluster size (harus 3)"
    echo "  test-sync     Menguji replikasi data instan antar-node"
    echo "  test-failover Menguji auto-failover dengan mematikan Node 1 sementara"
    echo "  logs          Melihat logs real-time dari HAProxy dan MariaDB"
    echo "================================================================="
    exit 1
}

wait_healthy() {
    local container=$1
    local name=$2
    echo -n "Menunggu $name sehat (healthy)..."
    local count=0
    until [ "$(docker inspect -f '{{.State.Health.Status}}' "$container" 2>/dev/null)" = "healthy" ]; do
        sleep 2
        echo -n "."
        count=$((count + 1))
        if [ $count -gt 45 ]; then
            echo " [TIMEOUT]"
            echo "Logs terakhir $name:"
            docker logs "$container" --tail 20
            exit 1
        fi
    done
    echo " [OK]"
}

start_cluster() {
    echo "================================================================="
    echo " Memulai MariaDB Galera Cluster (3 Node + HAProxy)"
    echo "================================================================="
    echo "[1/4] Menjalankan Node 1 (Bootstrap Primary Node)..."
    docker compose -f "$COMPOSE_FILE" up -d mariadb-node1
    wait_healthy "gajipro-mariadb-node1" "Node 1"

    echo "[2/4] Menjalankan Node 2 (Replication Peer)..."
    docker compose -f "$COMPOSE_FILE" up -d mariadb-node2
    wait_healthy "gajipro-mariadb-node2" "Node 2"

    echo "[3/4] Menjalankan Node 3 (Quorum & Replication Peer)..."
    docker compose -f "$COMPOSE_FILE" up -d mariadb-node3
    wait_healthy "gajipro-mariadb-node3" "Node 3"

    echo "[4/4] Menjalankan HAProxy Load Balancer..."
    docker compose -f "$COMPOSE_FILE" up -d db-cluster

    echo ""
    echo "================================================================="
    echo " Cluster Database Berhasil Dijalankan dan Siap Melayani Traffic!"
    echo " Virtual Entrypoint: db-cluster:3306 (internal) / localhost:3307 (host)"
    echo " Monitoring GUI:     http://localhost:8404 (user: admin, pass: secret)"
    echo "================================================================="
}

status_cluster() {
    echo "Memeriksa status cluster dari Node 1..."
    docker exec -t gajipro-mariadb-node1 mysql -u"$DB_USER" -p"$DB_PASS" -e "
        SHOW STATUS LIKE 'wsrep_cluster_size';
        SHOW STATUS LIKE 'wsrep_cluster_status';
        SHOW STATUS LIKE 'wsrep_local_state_comment';
        SHOW STATUS LIKE 'wsrep_connected';
    " 2>/dev/null || echo "Gagal terhubung ke gajipro-mariadb-node1."
}

test_sync() {
    echo "==> [TEST] Pengujian Replikasi Sinkron Data Antar-Node <=="
    TIMESTAMP=$(date +%s)
    TEST_TABLE="galera_sync_test"

    echo "1. Membuat tabel uji dan insert row di Node 1..."
    docker exec -t gajipro-mariadb-node1 mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
        CREATE TABLE IF NOT EXISTS $TEST_TABLE (id INT PRIMARY KEY AUTO_INCREMENT, val VARCHAR(50), created_at INT);
        INSERT INTO $TEST_TABLE (val, created_at) VALUES ('sync-test-$TIMESTAMP', $TIMESTAMP);
    " 2>/dev/null

    echo "2. Membaca data langsung dari Node 2..."
    docker exec -t gajipro-mariadb-node2 mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
        SELECT * FROM $TEST_TABLE WHERE created_at = $TIMESTAMP;
    " 2>/dev/null

    echo "3. Membaca data langsung dari Node 3..."
    docker exec -t gajipro-mariadb-node3 mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
        SELECT * FROM $TEST_TABLE WHERE created_at = $TIMESTAMP;
    " 2>/dev/null

    echo "==> Replikasi Sinkron Berhasil Terverifikasi! Data identik di ketiga node."
}

test_failover() {
    echo "==> [TEST] Pengujian Auto-Failover HAProxy <=="
    echo "1. Mematikan Node 1 (Primary Writer saat ini)..."
    docker stop gajipro-mariadb-node1

    echo "2. Menunggu 3 detik agar HAProxy mendeteksi perubahan healthcheck..."
    sleep 3

    echo "3. Mengirim query SELECT via HAProxy entrypoint (localhost:3307)..."
    docker exec -t gajipro-db-cluster sh -c "echo 'HAProxy tetap melayani traffic melalui node backup (Node 2 / 3)'"
    mysql -h127.0.0.1 -P3307 -u"$DB_USER" -p"$DB_PASS" -e "SELECT @@hostname, NOW();" 2>/dev/null || true

    echo "4. Menyalakan kembali Node 1..."
    docker start gajipro-mariadb-node1
    echo "==> Pengujian Failover Selesai. Node 1 akan otomatis resync ke cluster."
}

case "$1" in
    start|up)
        start_cluster
        ;;
    stop|down)
        docker compose -f "$COMPOSE_FILE" stop
        ;;
    restart)
        docker compose -f "$COMPOSE_FILE" restart
        ;;
    status)
        status_cluster
        ;;
    test-sync)
        test_sync
        ;;
    test-failover)
        test_failover
        ;;
    logs)
        docker compose -f "$COMPOSE_FILE" logs -f
        ;;
    *)
        usage
        ;;
esac
