<?php
// Fungsi untuk mendapatkan informasi IP dan lokasi
function getVisitorInfo() {
    $info = [];
    
    // Dapatkan IP pengunjung
    $info['ip'] = $_SERVER['REMOTE_ADDR'];
    
    // Dapatkan User Agent
    $info['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    
    // Dapatkan waktu akses
    $info['timestamp'] = date('Y-m-d H:i:s');
    
    // Coba dapatkan lokasi dari IP (menggunakan API gratis)
    if (filter_var($info['ip'], FILTER_VALIDATE_IP)) {
        $location = @json_decode(file_get_contents("http://ip-api.com/json/{$info['ip']}"), true);
        if ($location && $location['status'] == 'success') {
            $info['location'] = [
                'country' => $location['country'],
                'region' => $location['regionName'],
                'city' => $location['city'],
                'zip' => $location['zip'],
                'lat' => $location['lat'],
                'lon' => $location['lon'],
                'isp' => $location['isp']
            ];
        }
    }
    
    // Deteksi apakah menggunakan mobile
    $mobile = false;
    if (preg_match('/(android|iphone|ipod|ipad|blackberry|windows phone)/i', $info['user_agent'])) {
        $mobile = true;
    }
    $info['mobile'] = $mobile;
    
    return $info;
}

// Fungsi untuk menyimpan log ke file
function saveLog($path, $originalUrl, $visitorInfo) {
    $logDir = __DIR__ . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/tracking_logs.json';
    
    $logData = [];
    if (file_exists($logFile)) {
        $logData = json_decode(file_get_contents($logFile), true);
    }
    
    $logEntry = [
        'path' => $path,
        'original_url' => $originalUrl,
        'visitor_info' => $visitorInfo,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $logData[] = $logEntry;
    file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));
}

// Database sederhana untuk link yang dipendekkan (dalam contoh nyata gunakan database sesungguhnya)
$links = [];
if (file_exists(__DIR__ . '/links.json')) {
    $links = json_decode(file_get_contents(__DIR__ . '/links.json'), true);
}

// Proses parameter path
$path = $_GET['p'] ?? '';
$originalUrl = '';

// Cari URL asli berdasarkan path
foreach ($links as $link) {
    if ($link['path'] === $path) {
        $originalUrl = $link['original_url'];
        break;
    }
}

if (empty($originalUrl)) {
    die('Link tidak ditemukan atau tidak valid.');
}

// Dapatkan informasi pengunjung
$visitorInfo = getVisitorInfo();

// Simpan log
saveLog($path, $originalUrl, $visitorInfo);

// Redirect ke URL asli
header("Location: $originalUrl");
exit();
?>