<?php
/**
 * PDAM Anomaly Detection System - Backend API
 * Kompatibel dengan XAMPP Windows
 */
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/../bootstrap/bootstrap.php';
require_once __DIR__ . '/../app/Models/db_sync.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function run_python_cmd($python_path, $script, $args) {
    $pyCmd = '"' . $python_path . '" "' . $script . '"';
    foreach ($args as $k => $v) {
        if ($v === true) {
            $pyCmd .= ' --' . $k;
        } else {
            $pyCmd .= ' --' . $k . ' "' . addslashes($v) . '"';
        }
    }
    // Windows: set env var via cmd.exe wrapper to ensure UTF-8 encoding
    $cmd = 'cmd /c "set PYTHONIOENCODING=utf-8 && ' . $pyCmd . ' 2>&1"';

    $descriptors = [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];
    $proc = proc_open($cmd, $descriptors, $pipes);
    if (!is_resource($proc)) return ['ok'=>false,'out'=>'proc_open gagal'];
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    proc_close($proc);
    return ['ok'=>true,'out'=>trim($stdout ?: $stderr)];
}

switch ($action) {

    case 'check':
        $python = detect_python();
        $deps = $python ? check_python_deps($python) : false;
        $is_login = auth_check();
        $session_id = get_session_id();
        
        // Check session-specific files
        if ($is_login) {
            $has_file = !empty($_SESSION['user_upload_file']) && file_exists($_SESSION['user_upload_file']);
            $has_result = false; // User: result di DB, bukan file
        } else {
            $guest_file = DATA_DIR . 'guest_' . $_SESSION['guest_id'] . '.xlsx';
            $guest_json = get_guest_json_path();
            $has_file = file_exists($guest_file);
            $has_result = file_exists($guest_json);
        }
        
        respond([
            'status'      => 'success',
            'python'      => $python,
            'python_ver'  => $python ? trim(shell_exec('"'.$python.'" --version 2>&1')) : null,
            'deps_ok'     => $deps,
            'is_login'    => $is_login,
            'session_id'  => $session_id,
            'has_file'    => $has_file,
            'has_result'  => $has_result,
            'php_ver'     => PHP_VERSION,
            'base_dir'    => BASE_DIR,
        ]);
        break;

    case 'install_deps':
        $python = detect_python();
        if (!$python) respond(['status'=>'error','message'=>'Python tidak ditemukan'], 500);
        $cmd = '"'.$python.'" -m pip install scikit-learn scipy pandas openpyxl --quiet 2>&1';
        $out = shell_exec($cmd);
        respond(['status'=>'success','output'=>$out,'message'=>'Instalasi selesai']);
        break;

    case 'upload':
        if (empty($_FILES['excel'])) respond(['status'=>'error','message'=>'File tidak ditemukan'], 400);
        $f = $_FILES['excel'];
        if ($f['error'] !== UPLOAD_ERR_OK) respond(['status'=>'error','message'=>'Upload error code: '.$f['error']], 400);
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx','xls'])) respond(['status'=>'error','message'=>'Harus file .xlsx/.xls'], 400);
        
        // Mode detection
        $is_login = auth_check();
        
        if ($is_login) {
            // USER MODE: File unik per user+timestamp
            $upload_path = get_user_upload_path((int)$_SESSION['user_id']);
            $_SESSION['user_upload_file'] = $upload_path;
        } else {
            // GUEST MODE: File per session
            $upload_path = DATA_DIR . 'guest_' . $_SESSION['guest_id'] . '.xlsx';
            // Cleanup file lama guest jika ada
            if (file_exists($upload_path)) @unlink($upload_path);
        }
        
        if (!move_uploaded_file($f['tmp_name'], $upload_path))
            respond(['status'=>'error','message'=>'Gagal simpan file. Cek permission folder: '.DATA_DIR], 500);
        
        // Simpan nama file ke session untuk referensi analyze
        $_SESSION['last_upload_name'] = $f['name'];
        $_SESSION['current_upload_path'] = $upload_path;
        
        respond(['status'=>'success','filename'=>$f['name'],'size'=>$f['size'],'mode'=> $is_login ? 'user' : 'guest']);
        break;

    case 'analyze':
        // Trigger cleanup: 10% chance per request (hapus file guest > 30 menit)
        if (mt_rand(1, 10) === 1) {
            cleanup_guest_files(30); // 30 menit
        }
        
        // Mode detection: guest vs user
        $is_login = auth_check();
        
        // Get upload path based on mode
        if ($is_login) {
            $upload_file = $_SESSION['user_upload_file'] ?? null;
        } else {
            $upload_file = DATA_DIR . 'guest_' . $_SESSION['guest_id'] . '.xlsx';
        }
        
        if (!$upload_file || !file_exists($upload_file)) {
            respond(['status'=>'error','message'=>'Upload file Excel dulu di Dashboard 1'], 400);
        }
        
        $python = detect_python();
        if (!$python) respond(['status'=>'error','message'=>'Python tidak ditemukan. Periksa config.php','paths'=>PYTHON_PATHS], 500);

        $cont = $_POST['contamination'] ?? '0.05';
        if (!in_array($cont, ['auto','0.05','0.1','0.2','0.3'])) $cont = '0.05';
        $mode = $_POST['mode'] ?? 'near_tahun_per_golongan';
        $valid = ['near_tahun_per_golongan','near_tahun_near_golongan','multi_tahun_per_golongan','multi_tahun_semua_golongan'];
        if (!in_array($mode, $valid)) $mode = 'near_tahun_per_golongan';

        // Build args
        $args = ['contamination'=>$cont,'mode'=>$mode];
        $t1 = intval($_POST['tahun_min'] ?? 0);
        $t2 = intval($_POST['tahun_max'] ?? 0);
        if ($t1 > 2000 && $t2 >= $t1) { $args['tahun_min']=$t1; $args['tahun_max']=$t2; }
        
        // GUEST MODE: baca dari Excel, output ke JSON file
        // USER MODE: export DB ke temp JSON hanya jika TIDAK ada upload baru
        $temp_json_file = null;
        
        // Cek apakah ada file upload BARU (current_upload_path) atau harus pakai data history
        $has_new_upload = !empty($_SESSION['current_upload_path']) && file_exists($_SESSION['current_upload_path']);
        
        if ($is_login && !$has_new_upload && !empty($_SESSION['user_last_upload_id'])) {
            // Export data dari database ke temporary JSON (mode re-analisis dari history)
            $temp_json_file = DATA_DIR . 'temp_user_' . $_SESSION['user_id'] . '_' . time() . '.json';
            $db_data = db_get_analysis_full($_SESSION['user_last_upload_id']);
            if ($db_data) {
                file_put_contents($temp_json_file, json_encode($db_data['data'], JSON_UNESCAPED_UNICODE));
                $args['file'] = $temp_json_file;
                $args['json-input'] = true;  // Flag untuk Python baca sebagai JSON
            } else {
                // Fallback ke Excel file
                $args['file'] = $upload_file;
            }
        } else {
            // Mode upload baru atau guest: pakai file Excel
            $args['file'] = $upload_file;
        }
        
        // Set output mode
        if ($is_login) {
            $args['stdout'] = true;
        } else {
            $guest_json = get_guest_json_path();
            $args['output'] = $guest_json;
        }

        $r = run_python_cmd($python, PYTHON_SCRIPT, $args);
        
        // Cleanup temp file jika ada
        if ($temp_json_file && file_exists($temp_json_file)) {
            @unlink($temp_json_file);
        }
        $parsed = json_decode($r['out'], true);
        if (!$parsed || ($parsed['status']??'') !== 'success') {
            respond(['status'=>'error','message'=>'Python error: '.substr($r['out'],0,600),
                     'hint'=>'Jalankan: pip install scikit-learn scipy pandas openpyxl'], 500);
        }

        // ===== MODE GUEST: Simpan hasil ke session =====
        if (!$is_login) {
            $_SESSION['guest_result'] = $parsed;
            respond([
                'status'    => 'success',
                'result'    => $parsed,
                'mode'      => 'guest',
                'session'   => $_SESSION['guest_id'],
            ]);
            break; // Guest selesai di sini
        }
        
        // ===== MODE USER: Sinkronisasi ke DB =====
        $uid = (int)$_SESSION['user_id'];
        $orig_name = $_SESSION['last_upload_name'] ?? basename($upload_file);
        $upload_id = db_sync_results($parsed, $orig_name, $uid);
        
        // Debug: log jika upload_id null
        if (!$upload_id) {
            error_log('db_sync_results returned null for user ' . $uid . ', filename: ' . $orig_name);
        } else {
            error_log('db_sync_results success: upload_id=' . $upload_id);
        }
        
        // Simpan hasil ke session untuk akses cepat
        $_SESSION['user_last_result'] = $parsed;
        $_SESSION['user_last_upload_id'] = $upload_id;

        // ===== SIMPAN HISTORY =====
        // Fix: akses total/anomali dari meta sub-array
        $meta = $parsed['meta'] ?? [];
        $total = $meta['total'] ?? 0;
        $anomali = $meta['anomali'] ?? 0;
        
        $hist_params = [
            'mode'          => $mode,
            'contamination' => $cont,
            'tahun_min'     => $args['tahun_min'] ?? null,
            'tahun_max'     => $args['tahun_max'] ?? null,
            'golongan'      => null,
            'total'         => $total,
            'anomali'       => $anomali,
            'pct'           => $meta['pct_anomali'] ?? 0,
            'filename'      => $orig_name,
            'upload_id'     => $upload_id,
        ];
        $hist_id = history_save($hist_params);

        // Link history ke results
        if ($upload_id && $hist_id) {
            db_link_history($upload_id, (int)$hist_id);
        }
        
        // Clear current_upload_path agar request berikutnya (replay) tidak tercampur
        unset($_SESSION['current_upload_path']);

        respond([
            'status'    => 'success',
            'result'    => $parsed,
            'mode'      => 'user',
            'upload_id' => $upload_id,
            'db_synced' => $upload_id !== null,
        ]);
        break;

    case 'get_result':
        $is_login = auth_check();
        
        if ($is_login) {
            // USER MODE: Ambil dari session atau DB
            if (!empty($_SESSION['user_last_result'])) {
                respond(['status'=>'success','data'=>$_SESSION['user_last_result'],'source'=>'session']);
            }
            // Atau ambil dari DB jika ada upload_id
            if (!empty($_SESSION['user_last_upload_id'])) {
                $data = db_get_analysis_full($_SESSION['user_last_upload_id']);
                if ($data) {
                    respond(['status'=>'success','data'=>$data,'source'=>'database']);
                }
            }
            respond(['status'=>'error','message'=>'Belum ada hasil. Jalankan analisis dulu.'], 404);
        } else {
            // GUEST MODE: Ambil dari JSON file
            $guest_json = get_guest_json_path();
            if (!file_exists($guest_json)) {
                respond(['status'=>'error','message'=>'Belum ada hasil. Jalankan analisis dulu.'], 404);
            }
            $data = json_decode(file_get_contents($guest_json), true);
            if (!$data) respond(['status'=>'error','message'=>'Data hasil rusak'], 500);
            respond(['status'=>'success','data'=>$data,'source'=>'guest_json']);
        }
        break;
        
    case 'cleanup_guest':
        if (!is_guest()) {
            respond(['status'=>'success','message'=>'Not a guest session']);
        }
        $guest_file = DATA_DIR . 'guest_' . $_SESSION['guest_id'] . '.xlsx';
        $guest_json = get_guest_json_path();
        $cleaned = [];
        if (file_exists($guest_file)) {
            @unlink($guest_file);
            $cleaned[] = 'upload';
        }
        if (file_exists($guest_json)) {
            @unlink($guest_json);
            $cleaned[] = 'result';
        }
        unset($_SESSION['guest_result']);
        respond(['status'=>'success','cleaned'=>$cleaned]);
        break;

    case 'get_history':
        $is_login = auth_check();
        if (!$is_login) {
            respond(['status'=>'error','message'=>'Login required'], 401);
        }
        $history = history_get($_SESSION['user_id'], 50);
        respond(['status'=>'success','data'=>$history]);
        break;

        case 'load_history':
        $is_login = auth_check();
        if (!$is_login) {
            respond(['status'=>'error','message'=>'Login required'], 401);
        }
        
        $history_id = intval($_POST['history_id'] ?? 0);
        if (!$history_id) {
            respond(['status'=>'error','message'=>'History ID required'], 400);
        }
        
        $pdo = db_connect();
        if (!$pdo) {
            respond(['status'=>'error','message'=>'Database error'], 500);
        }
        
        $stmt = $pdo->prepare('
            SELECT h.*, h.upload_id 
            FROM analysis_history h 
            WHERE h.id = ? AND h.user_id = ?
        ');
        $stmt->execute([$history_id, $_SESSION['user_id']]);
        $history = $stmt->fetch();
        
        if (!$history) {
            respond(['status'=>'error','message'=>'History record not found (ID: ' . $history_id . ')'], 404);
        }
        
        if (empty($history['upload_id'])) {
            respond([
                'status'=>'error',
                'message'=>'History record exists but has no associated data (ID: ' . $history_id . '). ' .
                         'This may be old data from before the fix. ' .
                         'Please run a new analysis to create a proper history record.',
                'history_date' => $history['created_at'] ?? 'unknown',
                'history_mode' => $history['filter_mode'] ?? 'unknown'
            ], 404);
        }
        
        $upload_id = (int)$history['upload_id'];
        
        $full_data = db_get_analysis_full($upload_id);
        
        if (!$full_data) {
            respond(['status'=>'error','message'=>'Data tidak ditemukan di database'], 404);
        }
        
        $_SESSION['user_last_upload_id'] = $upload_id;
        $_SESSION['user_last_result'] = $full_data;
        $_SESSION['last_upload_name'] = $history['filename'] ?? 'history_data.xlsx';
        
        respond([
            'status'=>'success',
            'upload_id'=>$upload_id,
            'history_id'=>$history_id,
            'message'=>'Data loaded from history',
            'source'=>'database',
            'record_count'=>count($full_data['data'] ?? []),
            'data'=>$full_data
        ]);
        break;

    default:
        respond(['status'=>'error','message'=>'Unknown action: '.$action], 400);
}
