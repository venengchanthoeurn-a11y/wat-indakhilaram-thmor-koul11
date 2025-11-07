<?php
include 'config.php';
requireLogin(); // Ensure user is logged in

header('Content-Type: application/json');

$kuti_id = filter_var($_GET['kuti_id'], FILTER_VALIDATE_INT);
if ($kuti_id === false) {
    echo json_encode(['error' => 'Invalid kuti_id']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, khmer_name FROM monks WHERE kuti_id = ?");
    $stmt->execute([$kuti_id]);
    $monks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($monks === false) {
        $monks = [];
    }
    echo json_encode(['monks' => $monks]);
} catch (PDOException $e) {
    error_log("Database error in get_monks.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
exit;
?>