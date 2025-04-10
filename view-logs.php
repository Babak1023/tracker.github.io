<?php
// Database sederhana untuk link yang dipendekkan
$links = [];
if (file_exists(__DIR__ . '/links.json')) {
    $links = json_decode(file_get_contents(__DIR__ . '/links.json'), true);
}

// Baca log
$logFile = __DIR__ . '/logs/tracking_logs.json';
$logs = [];
if (file_exists($logFile)) {
    $logs = json_decode(file_get_contents($logFile), true);
    $logs = array_reverse($logs); // Tampilkan yang terbaru pertama
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Pelacakan Link</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        h1 {
            color: #2c3e50;
        }
        .log-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .log-table th, .log-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .log-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .log-table th {
            padding-top: 12px;
            padding-bottom: 12px;
            background-color: #3498db;
            color: white;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
        }
        .info-details {
            display: none;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <a href="index.html" class="back-link">← Kembali ke pembuat link</a>
    <h1>Log Pelacakan Link</h1>
    
    <?php if (empty($logs)): ?>
        <p>Belum ada data pelacakan.</p>
    <?php else: ?>
        <table class="log-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Path</th>
                    <th>URL Tujuan</th>
                    <th>IP</th>
                    <th>Lokasi</th>
                    <th>Perangkat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['timestamp']) ?></td>
                        <td><?= htmlspecialchars($log['path']) ?></td>
                        <td><?= htmlspecialchars($log['original_url']) ?></td>
                        <td><?= htmlspecialchars($log['visitor_info']['ip']) ?></td>
                        <td>
                            <?php if (isset($log['visitor_info']['location'])): ?>
                                <?= htmlspecialchars($log['visitor_info']['location']['city'] ?? '') ?>, 
                                <?= htmlspecialchars($log['visitor_info']['location']['region'] ?? '') ?>
                            <?php else: ?>
                                Tidak diketahui
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $log['visitor_info']['mobile'] ? 'Mobile' : 'Desktop' ?>
                        </td>
                        <td>
                            <button onclick="toggleDetails(this)">Detail</button>
                            <div class="info-details">
                                <h3>Detail Lengkap:</h3>
                                <pre><?= json_encode($log['visitor_info'], JSON_PRETTY_PRINT) ?></pre>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <script>
        function toggleDetails(button) {
            const detailsDiv = button.nextElementSibling;
            if (detailsDiv.style.display === 'block') {
                detailsDiv.style.display = 'none';
                button.textContent = 'Detail';
            } else {
                detailsDiv.style.display = 'block';
                button.textContent = 'Sembunyikan';
            }
        }
    </script>
</body>
</html>