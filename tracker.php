<?php
// Fungsi untuk mendapatkan informasi pengunjung
function getVisitorInfo() {
    $info = [];
    
    // Informasi dasar
    $info['ip'] = $_SERVER['REMOTE_ADDR'];
    $info['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $info['timestamp'] = date('Y-m-d H:i:s');
    $info['referer'] = $_SERVER['HTTP_REFERER'] ?? '';
    $info['language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    
    // Deteksi perangkat mobile
    $info['mobile'] = preg_match('/(android|iphone|ipod|ipad|blackberry|windows phone)/i', $info['user_agent']);
    
    // Dapatkan lokasi dari IP
    if (filter_var($info['ip'], FILTER_VALIDATE_IP)) {
        $location = @json_decode(file_get_contents("http://ip-api.com/json/{$info['ip']}?fields=status,message,country,regionName,city,zip,lat,lon,isp,org,as,query"), true);
        if ($location && $location['status'] == 'success') {
            $info['location'] = $location;
        }
    }
    
    // Simpan data dari JavaScript (jika ada)
    $jsData = json_decode(file_get_contents('php://input'), true);
    if ($jsData) {
        $info['js_data'] = $jsData; // Berisi baterai, koneksi, dll
    }
    
    return $info;
}

// Fungsi untuk menyimpan log
function saveLog($path, $originalUrl, $visitorInfo) {
    $logDir = __DIR__ . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/tracking_logs.json';
    $logData = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
    
    $logEntry = [
        'path' => $path,
        'original_url' => $originalUrl,
        'visitor_info' => $visitorInfo,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $logData[] = $logEntry;
    file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));
    
    // Juga simpan sebagai file terpisah untuk view real-time
    $viewFile = $logDir . '/last_view_' . $path . '.json';
    file_put_contents($viewFile, json_encode($logEntry, JSON_PRETTY_PRINT));
}

// Baca database link
$links = file_exists(__DIR__ . '/links.json') ? json_decode(file_get_contents(__DIR__ . '/links.json'), true) : [];

// Proses request
$path = $_GET['p'] ?? '';
$originalUrl = '';
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

foreach ($links as $link) {
    if ($link['path'] === $path) {
        $originalUrl = $link['original_url'];
        break;
    }
}

if (empty($originalUrl)) {
    die('Link tidak valid atau tidak ditemukan.');
}

// Dapatkan info pengunjung
$visitorInfo = getVisitorInfo();

// Simpan log
saveLog($path, $originalUrl, $visitorInfo);

// Jika request AJAX (dari JavaScript), kembalikan JSON
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'redirect_url' => $originalUrl]);
    exit;
}

// Jika bukan AJAX, tampilkan halaman info
include 'view.php';
exit;
?>
