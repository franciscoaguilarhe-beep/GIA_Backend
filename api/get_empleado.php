<?php
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$matricula = isset($_GET['matricula']) ? trim($_GET['matricula']) : '';

if (empty($matricula)) {
    echo json_encode(['error' => 'Matrícula no proporcionada']);
    exit;
}

try {
    $query = "SELECT matricula, nombre, usuario as cuenta, categoria FROM empleados WHERE matricula = :matricula LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':matricula', $matricula);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($row);
    } else {
        echo json_encode(['not_found' => true]);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
