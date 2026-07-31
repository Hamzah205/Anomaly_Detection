<?php
/**
 * PDAM Anomaly Detection System
 * db_sync.php — Sinkronisasi JSON hasil analisis ke Database
 *
 * Support: MySQL & PostgreSQL
 * Dipanggil dari api.php setelah Python selesai.
 * Menyimpan: uploads, raw_data, analysis_results
 */

if (!defined('PDAM_BOOTSTRAP_LOADED')) {
    require_once __DIR__ . '/bootstrap.php';
}

/**
 * Simpan data Excel ke tabel raw_data dan hasilnya ke analysis_results.
 * Mengembalikan upload_id atau null jika DB tidak tersedia.
 */
function db_sync_results(array $json_result, string $filename, ?int $user_id = null): ?int {
    $pdo = db_connect();
    if (!$pdo) return null;   // DB tidak wajib — skip

    try {
        $pdo->beginTransaction();

        // Validasi struktur JSON
        if (empty($json_result['meta']) || empty($json_result['data'])) {
            error_log('db_sync error: Invalid json_result structure. Keys: ' . implode(', ', array_keys($json_result)));
            return null;
        }

        $meta = $json_result['meta'];
        $data = $json_result['data'];
        
        // Log untuk debug
        error_log('db_sync: Processing ' . count($data) . ' rows, meta keys: ' . implode(', ', array_keys($meta)));

        $is_pgsql = defined('DB_DRIVER') && DB_DRIVER === 'pgsql';

        // ── 1. Insert ke tabel uploads ──
        if ($is_pgsql) {
            // PostgreSQL: pakai RETURNING id
            $stmt = $pdo->prepare("
                INSERT INTO uploads
                    (uuid, user_id, original_name, stored_name, total_rows,
                     tahun_min, tahun_max, golongan_list)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                RETURNING id
            ");
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO uploads
                    (uuid, user_id, original_name, stored_name, total_rows,
                     tahun_min, tahun_max, golongan_list)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
        }
        $stmt->execute([
            bin2hex(random_bytes(16)),
            $user_id,
            $filename,
            basename($_SESSION['user_upload_file'] ?? $filename),
            $meta['total'],
            $meta['tahun_range'][0] ?? null,
            $meta['tahun_range'][1] ?? null,
            json_encode($meta['golongan_list'] ?? []),
        ]);
        if ($is_pgsql) {
            $upload_id = (int)$stmt->fetchColumn();
        } else {
            $upload_id = (int)$pdo->lastInsertId();
        }

        // ── 2. Hapus raw_data lama untuk upload sebelumnya (opsional — biarkan history) ──
        // Kita TIDAK hapus — setiap upload disimpan terpisah

        // ── 3. Insert raw_data ──
        if ($is_pgsql) {
            $rawStmt = $pdo->prepare("
                INSERT INTO raw_data
                    (upload_id, tahun, bulan, bulan_num,
                     golongan, nama_golongan, rp, m3, rp_per_m3)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT (upload_id, tahun, bulan_num, golongan)
                DO UPDATE SET
                    nama_golongan = EXCLUDED.nama_golongan,
                    rp = EXCLUDED.rp,
                    m3 = EXCLUDED.m3,
                    rp_per_m3 = EXCLUDED.rp_per_m3,
                    updated_at = CURRENT_TIMESTAMP
                RETURNING id
            ");
        } else {
            $rawStmt = $pdo->prepare("
                INSERT INTO raw_data
                    (upload_id, tahun, bulan, bulan_num,
                     golongan, nama_golongan, rp, m3, rp_per_m3)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    nama_golongan = VALUES(nama_golongan),
                    rp = VALUES(rp),
                    m3 = VALUES(m3),
                    rp_per_m3 = VALUES(rp_per_m3),
                    updated_at = CURRENT_TIMESTAMP
            ");
        }

        // Statement untuk ambil raw_data_id (fallback kalau tidak RETURNING)
        $getIdStmt = $pdo->prepare("
            SELECT id FROM raw_data
            WHERE upload_id = ? AND tahun = ? AND bulan_num = ? AND golongan = ?
        ");

        // ── 4. Insert analysis_results ──
        if ($is_pgsql) {
            $resStmt = $pdo->prepare("
                INSERT INTO analysis_results
                    (raw_data_id, upload_id, is_anomaly,
                     anomaly_score, anomaly_level, causes_json)
                VALUES (?, ?, ?, ?, ?, ?)
                ON CONFLICT (raw_data_id, upload_id)
                DO UPDATE SET
                    is_anomaly = EXCLUDED.is_anomaly,
                    anomaly_score = EXCLUDED.anomaly_score,
                    anomaly_level = EXCLUDED.anomaly_level,
                    causes_json = EXCLUDED.causes_json
            ");
        } else {
            $resStmt = $pdo->prepare("
                INSERT INTO analysis_results
                    (raw_data_id, upload_id, is_anomaly,
                     anomaly_score, anomaly_level, causes_json)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    is_anomaly = VALUES(is_anomaly),
                    anomaly_score = VALUES(anomaly_score),
                    anomaly_level = VALUES(anomaly_level),
                    causes_json = VALUES(causes_json)
            ");
        }

        foreach ($data as $row) {
            // Data dengan nilai 0 tetap disimpan (valid untuk data PDAM)
            $tahun = (int)$row['tahun'];
            $bulan_num = (int)$row['bulan_num'];
            $golongan = $row['golongan'];
            $rp = (int)round($row['rp']);
            $m3 = (int)round($row['m3']);
            
            // Insert raw_data
            $rawStmt->execute([
                $upload_id,
                $tahun,
                $row['bulan'],
                $bulan_num,
                $golongan,
                $row['nama_golongan'] ?? $golongan,
                $rp,
                $m3,
                round((float)$row['rp_per_m3'], 4),
            ]);

            // Ambil raw_data_id (PostgreSQL pakai RETURNING, MySQL pakai SELECT)
            if ($is_pgsql) {
                $raw_data_id = (int)$rawStmt->fetchColumn();
            } else {
                $getIdStmt->execute([$upload_id, $tahun, $bulan_num, $golongan]);
                $raw_data_id = (int)$getIdStmt->fetchColumn();
            }

            // Insert/update analysis_results
            $causes = $row['causes'] ?? [];
            $resStmt->execute([
                $raw_data_id,
                $upload_id,
                (int)($row['is_anomaly'] ?? 0),
                round((float)($row['anomaly_score'] ?? 0), 6),
                $row['anomaly_level'] ?? 'normal',
                !empty($causes) ? json_encode($causes, JSON_UNESCAPED_UNICODE) : null,
            ]);
        }

        $pdo->commit();
        return $upload_id;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('db_sync PDO error: ' . $e->getMessage());
        return null;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('db_sync general error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Update history_id di analysis_results setelah history disimpan
 */
function db_link_history(int $upload_id, int $history_id): void {
    $pdo = db_connect();
    if (!$pdo) return;
    try {
        $pdo->prepare('UPDATE analysis_results SET history_id = ? WHERE upload_id = ?')
            ->execute([$history_id, $upload_id]);
    } catch (PDOException $e) {
        error_log('db_link_history error: ' . $e->getMessage());
    }
}

/**
 * Statistik dari DB
 */
function db_get_upload_stats(int $upload_id): array {
    $pdo = db_connect();
    if (!$pdo) return [];
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(is_anomaly::int) AS anomali,
            SUM(CASE WHEN anomaly_level = 'high'   THEN 1 ELSE 0 END) AS high_cnt,
            SUM(CASE WHEN anomaly_level = 'medium' THEN 1 ELSE 0 END) AS medium_cnt,
            SUM(CASE WHEN anomaly_level = 'low'    THEN 1 ELSE 0 END) AS low_cnt
        FROM analysis_results
        WHERE upload_id = ?
    ");
    $stmt->execute([$upload_id]);
    return $stmt->fetch() ?: [];
}

/**
 * Ambil data lengkap dari DB dalam format yang sama dengan guest JSON
 * untuk kompatibilitas dengan dashboard 2-4
 */
function db_get_analysis_full(int $upload_id): ?array {
    $pdo = db_connect();
    if (!$pdo) return null;

    // Get upload info untuk meta
    $stmt = $pdo->prepare('SELECT * FROM uploads WHERE id = ?');
    $stmt->execute([$upload_id]);
    $upload = $stmt->fetch();
    if (!$upload) return null;

    // Get stats untuk meta
    $stats = db_get_upload_stats($upload_id);
    $total = (int)($stats['total'] ?? 0);
    $anomali = (int)($stats['anomali'] ?? 0);
    $normal = $total - $anomali;

    // Get analysis params dari history untuk contamination & mode
    $stmt = $pdo->prepare('SELECT filter_mode, contamination FROM analysis_history WHERE upload_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$upload_id]);
    $hist = $stmt->fetch();
    $contamination = 'auto';
    $mode = 'near_tahun_per_golongan';
    if ($hist) {
        // BUG FIX: use filter_mode and contamination columns directly (no 'params' column in schema)
        if (!empty($hist['contamination'])) $contamination = (string)$hist['contamination'];
        if (!empty($hist['filter_mode']))   $mode          = $hist['filter_mode'];
    }

    // Get summary per golongan
    $stmt = $pdo->prepare("
        SELECT 
            rd.golongan,
            MAX(rd.nama_golongan) as nama_golongan,
            COUNT(*) as total,
            SUM(ar.is_anomaly::int) as anomali,
            COUNT(*) - SUM(ar.is_anomaly::int) as normal,
            ROUND(SUM(ar.is_anomaly::int) * 100.0 / COUNT(*), 2) as pct
        FROM raw_data rd
        JOIN analysis_results ar ON ar.raw_data_id = rd.id
        WHERE ar.upload_id = ?
        GROUP BY rd.golongan
        ORDER BY rd.golongan
    ");
    $stmt->execute([$upload_id]);
    $summary_golongan = $stmt->fetchAll();

    // Get summary per tahun
    $stmt = $pdo->prepare("
        SELECT 
            rd.tahun,
            COUNT(*) as total,
            SUM(ar.is_anomaly::int) as anomali,
            COUNT(*) - SUM(ar.is_anomaly::int) as normal,
            ROUND(SUM(ar.is_anomaly::int) * 100.0 / COUNT(*), 2) as pct
        FROM raw_data rd
        JOIN analysis_results ar ON ar.raw_data_id = rd.id
        WHERE ar.upload_id = ?
        GROUP BY rd.tahun
        ORDER BY rd.tahun
    ");
    $stmt->execute([$upload_id]);
    $summary_tahun_raw = $stmt->fetchAll();
    $summary_tahun = array_map(function($t) {
        return [
            'tahun'  => (int)$t['tahun'],
            'total'  => (int)$t['total'],
            'anomali'=> (int)$t['anomali'],
            'normal' => (int)$t['normal'],
            'pct'    => (float)$t['pct'],
        ];
    }, $summary_tahun_raw);

    // Get detail data
    $stmt = $pdo->prepare("
        SELECT
            rd.tahun, rd.bulan, rd.bulan_num, rd.golongan, rd.nama_golongan,
            rd.rp, rd.m3, rd.rp_per_m3,
            ar.is_anomaly, ar.anomaly_score, ar.anomaly_level, ar.causes_json
        FROM raw_data rd
        JOIN analysis_results ar ON ar.raw_data_id = rd.id
        WHERE ar.upload_id = ?
        ORDER BY rd.tahun, rd.bulan_num, rd.golongan
    ");
    $stmt->execute([$upload_id]);
    $rows = $stmt->fetchAll();

    // Parse causes_json untuk setiap row
    $data = [];
    foreach ($rows as $row) {
        $data[] = [
            'tahun' => (int)$row['tahun'],
            'bulan' => $row['bulan'],
            'bulan_num' => (int)$row['bulan_num'],
            'golongan' => $row['golongan'],
            'nama_golongan' => $row['nama_golongan'],
            'rp' => (int)$row['rp'],
            'm3' => (int)$row['m3'],
            'rp_per_m3' => (float)$row['rp_per_m3'],
            'is_anomaly' => (int)$row['is_anomaly'],
            'anomaly_score' => (float)$row['anomaly_score'],
            'anomaly_level' => $row['anomaly_level'],
            'causes' => $row['causes_json'] ? json_decode($row['causes_json'], true) : []
        ];
    }

    // Build response format sama seperti guest
    return [
        'meta' => [
            'total' => $total,
            'anomali' => $anomali,
            'normal' => $normal,
            'pct_anomali' => $total > 0 ? round(($anomali / $total) * 100, 2) : 0,
            'contamination' => $contamination,
            'mode' => $mode,
            'tahun_range' => [$upload['tahun_min'], $upload['tahun_max']],
            'golongan_list' => json_decode($upload['golongan_list'], true) ?: [],
            'severity' => [
                'high' => (int)($stats['high_cnt'] ?? 0),
                'medium' => (int)($stats['medium_cnt'] ?? 0),
                'low' => (int)($stats['low_cnt'] ?? 0)
            ]
        ],
        'summary_golongan' => array_map(function($g) {
            return [
                'golongan'      => $g['golongan'],
                'nama_golongan' => $g['nama_golongan'] ?? $g['golongan'],
                'total'         => (int)$g['total'],
                'anomali'       => (int)$g['anomali'],
                'normal'        => (int)$g['normal'],
                'pct'           => (float)($g['pct'] ?? 0),
            ];
        }, $summary_golongan),
        'summary_tahun'   => $summary_tahun,
        'data' => $data
    ];
}
