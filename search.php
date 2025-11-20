<?php

require __DIR__ . '/dbconnect.php';

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');

    $term = isset($_GET['term']) ? trim($_GET['term']) : '';
    $payload = ['ok' => true, 'results' => []];

    if ($term === '') {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT DISTINCT Country AS name
           FROM countrycapitals
          WHERE Country LIKE CONCAT('%', ?, '%')
          ORDER BY Country ASC
          LIMIT 10"
    );

    if ($stmt === false) {
        $payload = [
            'ok' => false,
            'message' => 'Database error: ' . $conn->error
        ];
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $stmt->bind_param('s', $term);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $payload['results'][] = [
                'name' => $row['name']
            ];
        }
        $result->free();
    }

    $stmt->close();

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $query = trim($_POST['query']);
    $payload = ['ok' => true, 'results' => []];

    if (!empty($query)) {
        
        $stmt = $conn->prepare("SELECT CapitalCity FROM countrycapitals WHERE Country LIKE ?");
        $searchTerm = "%" . $query . "%";
        $stmt->bind_param("s", $searchTerm);

        $stmt->execute();
        $result = $stmt->get_result();

       while ($row = $result->fetch_assoc()) {
        $payload['results'][] = [
            'name' => $row['CapitalCity'],
        ];
    }

        $stmt->close();
    } else {
        $payload = ['ok' => true, 'results' => []];
    }
}

$conn->close();

header('Content-Type: text/html; charset=utf-8');


$targetOrigin = "*";

?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body>
<script>
  (function () {
   
    var data = <?php echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    
    window.parent.postMessage(data, "<?php echo $targetOrigin; ?>");
  })();
</script>
</body>
</html>