<?php
$path = $_GET['p'] ?? '';
$viewFile = __DIR__ . '/logs/last_view_' . $path . '.json';

if (!file_exists($viewFile)) {
    die('Data tidak ditemukan atau link tidak valid.');
}

$data = json_decode(file_get_contents($viewFile), true);
$visitorInfo = $data['visitor_info'];
$hasLocation = isset($visitorInfo['location']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Pengunjung</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        .info-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .info-box {
            flex: 1;
            min-width: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            background-color: #f9f9f9;
        }
        .info-box h2 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        #map {
            height: 400px;
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 20px;
        }
        .redirect-message {
            background-color: #e74c3c;
            color: white;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin-top: 20px;
        }
        .battery-info, .connection-info {
            margin-top: 15px;
            padding: 10px;
            background-color: #eaf2f8;
            border-radius: 5px;
        }
    </style>
    <?php if ($hasLocation): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <?php endif; ?>
</head>
<body>
    <h1>Informasi Pengunjung</h1>
    
    <div class="info-container">
        <div class="info-box">
            <h2>Informasi Perangkat</h2>
            <div class="info-item">
                <span class="info-label">IP Address:</span>
                <?= htmlspecialchars($visitorInfo['ip']) ?>
            </div>
            <div class="info-item">
                <span class="info-label">Waktu Akses:</span>
                <?= htmlspecialchars($visitorInfo['timestamp']) ?>
            </div>
            <div class="info-item">
                <span class="info-label">Perangkat:</span>
                <?= $visitorInfo['mobile'] ? 'Mobile' : 'Desktop' ?>
            </div>
            <div class="info-item">
                <span class="info-label">Browser:</span>
                <?= htmlspecialchars($visitorInfo['user_agent']) ?>
            </div>
            <div class="info-item">
                <span class="info-label">Bahasa:</span>
                <?= htmlspecialchars($visitorInfo['language']) ?>
            </div>
            
            <div id="js-battery" class="battery-info">
                <h3>Informasi Baterai</h3>
                <p>Mengumpulkan data...</p>
            </div>
            
            <div id="js-connection" class="connection-info">
                <h3>Informasi Koneksi</h3>
                <p>Mengumpulkan data...</p>
            </div>
        </div>
        
        <div class="info-box">
            <h2>Informasi Lokasi</h2>
            <?php if ($hasLocation): ?>
                <div class="info-item">
                    <span class="info-label">Negara:</span>
                    <?= htmlspecialchars($visitorInfo['location']['country']) ?>
                </div>
                <div class="info-item">
                    <span class="info-label">Wilayah:</span>
                    <?= htmlspecialchars($visitorInfo['location']['regionName']) ?>
                </div>
                <div class="info-item">
                    <span class="info-label">Kota:</span>
                    <?= htmlspecialchars($visitorInfo['location']['city']) ?>
                </div>
                <div class="info-item">
                    <span class="info-label">Kode Pos:</span>
                    <?= htmlspecialchars($visitorInfo['location']['zip']) ?>
                </div>
                <div class="info-item">
                    <span class="info-label">ISP:</span>
                    <?= htmlspecialchars($visitorInfo['location']['isp']) ?>
                </div>
                <div class="info-item">
                    <span class="info-label">Koordinat:</span>
                    <?= htmlspecialchars($visitorInfo['location']['lat']) ?>, <?= htmlspecialchars($visitorInfo['location']['lon']) ?>
                </div>
            <?php else: ?>
                <p>Lokasi tidak dapat ditentukan.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($hasLocation): ?>
    <div id="map"></div>
    <script>
        // Inisialisasi peta
        const map = L.map('map').setView([
            <?= $visitorInfo['location']['lat'] ?>, 
            <?= $visitorInfo['location']['lon'] ?>
        ], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        L.marker([
            <?= $visitorInfo['location']['lat'] ?>, 
            <?= $visitorInfo['location']['lon'] ?>
        ]).addTo(map)
          .bindPopup('Lokasi perkiraan pengunjung')
          .openPopup();
    </script>
    <?php endif; ?>
    
    <div class="redirect-message">
        <p>Anda akan diarahkan ke: <?= htmlspecialchars($data['original_url']) ?></p>
        <p id="countdown">Redirect dalam 10 detik...</p>
    </div>
    
    <script>
        // Kirim data tambahan dari browser ke server
        const sendDataToServer = async () => {
            const data = {};
            
            // Dapatkan info baterai jika tersedia
            if ('getBattery' in navigator) {
                try {
                    const battery = await navigator.getBattery();
                    data.battery = {
                        level: Math.round(battery.level * 100) + '%',
                        charging: battery.charging,
                        chargingTime: battery.chargingTime,
                        dischargingTime: battery.dischargingTime
                    };
                } catch (e) {
                    console.error('Gagal mendapatkan info baterai:', e);
                }
            }
            
            // Dapatkan info koneksi jika tersedia
            if ('connection' in navigator) {
                data.connection = {
                    effectiveType: navigator.connection.effectiveType,
                    type: navigator.connection.type,
                    downlink: navigator.connection.downlink + ' Mbps',
                    rtt: navigator.connection.rtt + ' ms',
                    saveData: navigator.connection.saveData
                };
            }
            
            // Kirim data ke server
            if (Object.keys(data).length > 0) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(data)
                });
            }
            
            // Update tampilan dengan data yang dikumpulkan
            if (data.battery) {
                document.getElementById('js-battery').innerHTML = `
                    <h3>Informasi Baterai</h3>
                    <p>Level: ${data.battery.level}</p>
                    <p>Status: ${data.battery.charging ? 'Sedang diisi' : 'Tidak diisi'}</p>
                    ${data.battery.charging ? `<p>Waktu pengisian: ${formatTime(data.battery.chargingTime)}</p>` : ''}
                    ${!data.battery.charging ? `<p>Waktu habis: ${formatTime(data.battery.dischargingTime)}</p>` : ''}
                `;
            }
            
            if (data.connection) {
                document.getElementById('js-connection').innerHTML = `
                    <h3>Informasi Koneksi</h3>
                    <p>Tipe: ${data.connection.type} (${data.connection.effectiveType})</p>
                    <p>Kecepatan: ${data.connection.downlink}</p>
                    <p>Latensi: ${data.connection.rtt}</p>
                    <p>Mode hemat data: ${data.connection.saveData ? 'Aktif' : 'Tidak aktif'}</p>
                `;
            }
        };
        
        function formatTime(seconds) {
            if (seconds === Infinity) return 'Tidak diketahui';
            if (seconds < 60) return `${seconds} detik`;
            const minutes = Math.floor(seconds / 60);
            return `${minutes} menit ${seconds % 60} detik`;
        }
        
        // Hitung mundur redirect
        let seconds = 10;
        const countdownElement = document.getElementById('countdown');
        const countdownInterval = setInterval(() => {
            seconds--;
            countdownElement.textContent = `Redirect dalam ${seconds} detik...`;
            
            if (seconds <= 0) {
                clearInterval(countdownInterval);
                window.location.href = '<?= htmlspecialchars($data['original_url']) ?>';
            }
        }, 1000);
        
        // Jalankan pengumpulan data
        sendDataToServer();
    </script>
</body>
</html>
