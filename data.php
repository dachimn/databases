<?php
$accessLogPath = 'C:/xampp/apache/logs/access.log';
$errorLogPath  = 'C:/xampp/apache/logs/error.log';

// Only include log entries that contain this path
$filterPath = '/Databases';

$visitsPerDay = [];     // 'Y-m-d' => count
$ipsPerDay    = [];     // 'Y-m-d' => [ip => count]

if (is_readable($accessLogPath)) {
    $fh = fopen($accessLogPath, 'r');
    if ($fh !== false) {
        while (($line = fgets($fh)) !== false) {

            if (strpos($line, $filterPath) === false) {
                continue;
            }

            $parts = explode(' ', $line);
            if (count($parts) < 4) {
                continue;
            }
            $ip = $parts[0];

            if (preg_match('/\[(\d{2}\/\w{3}\/\d{4}):/', $line, $m)) {
                $dateStr = $m[1];
                $dt = DateTime::createFromFormat('d/M/Y', $dateStr);
                if (!$dt) continue;

                $dayKey = $dt->format('Y-m-d');

                if (!isset($visitsPerDay[$dayKey])) $visitsPerDay[$dayKey] = 0;
                $visitsPerDay[$dayKey]++;

                if (!isset($ipsPerDay[$dayKey])) $ipsPerDay[$dayKey] = [];
                if (!isset($ipsPerDay[$dayKey][$ip])) $ipsPerDay[$dayKey][$ip] = 0;

                $ipsPerDay[$dayKey][$ip]++;
            }
        }
        fclose($fh);
    }
}

$errorsPerDay = [];

if (is_readable($errorLogPath)) {
    $fh = fopen($errorLogPath, 'r');
    if ($fh !== false) {
        while (($line = fgets($fh)) !== false) {

            if (strpos($line, $filterPath) === false) {
                continue;
            }

            if (!preg_match('/^\[([^\]]+)\]/', $line, $m)) continue;

            $raw = $m[1];

            $dt = DateTime::createFromFormat('D M d H:i:s.u Y', $raw);
            if (!$dt) {
                $dt = DateTime::createFromFormat('D M d H:i:s Y', $raw);
            }
            if (!$dt) continue;

            $dayKey = $dt->format('Y-m-d');

            if (!isset($errorsPerDay[$dayKey])) $errorsPerDay[$dayKey] = 0;
            $errorsPerDay[$dayKey]++;
        }
        fclose($fh);
    }
}

ksort($visitsPerDay);
ksort($errorsPerDay);

$visitorDates  = array_keys($visitsPerDay);
$visitorCounts = array_values($visitsPerDay);

$errorDates    = array_keys($errorsPerDay);
$errorCounts   = array_values($errorsPerDay);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apache Log Statistics (Filtered)</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, serif; margin: 20px; }
        .chart-container { max-width: 900px; margin-bottom: 40px; }
        .details-container { max-width: 900px; }
        details { margin-bottom: 8px; }
        .warning { color: #b00; font-weight: bold; }
    </style>
</head>
<body>

<h1>Apache Log Statistics</h1>

<?php if (!is_readable($accessLogPath)): ?>
    <p class="warning">Cannot read access log: <?php echo htmlspecialchars($accessLogPath); ?></p>
<?php endif; ?>

<?php if (!is_readable($errorLogPath)): ?>
    <p class="warning">Cannot read error log: <?php echo htmlspecialchars($errorLogPath); ?></p>
<?php endif; ?>

<div class="chart-container">
    <h2>Visitors per Day (Filtered Access Log)</h2>
    <canvas id="visitorsChart"></canvas>
</div>

<div class="details-container">
    <h3>Filtered Visitor IPs per Day</h3>
    <?php if (!empty($ipsPerDay)): ?>
        <?php foreach ($ipsPerDay as $day => $ips): ?>
            <details>
                <summary><?php echo $day; ?> (<?php echo array_sum($ips); ?> matching hits)</summary>
                <ul>
                    <?php foreach ($ips as $ip => $count): ?>
                        <li><?php echo $ip; ?> — <?php echo $count; ?> request(s)</li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No entries matched the filter.</p>
    <?php endif; ?>
</div>

<div class="chart-container">
    <h2>Errors per Day</h2>
    <canvas id="errorsChart"></canvas>
</div>

<script>

new Chart(document.getElementById('visitorsChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($visitorDates); ?>,
        datasets: [{
            label: 'Filtered visitors per day',
            data: <?php echo json_encode($visitorCounts); ?>,
            borderWidth: 1
        }]
    }
});


new Chart(document.getElementById('errorsChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($errorDates); ?>,
        datasets: [{
            label: 'Filtered errors per day',
            data: <?php echo json_encode($errorCounts); ?>,
            borderWidth: 1
        }]
    }
});
</script>

</body>
</html>
