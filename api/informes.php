<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $conn->query("SELECT * FROM informes ORDER BY fecha DESC, hora_inicio DESC");
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $fecha = $input['fecha'] ?? '';
    $area = $input['area'] ?? '';
    $acciones = $input['acciones'] ?? '';
    $hora_inicio = $input['hora_inicio'] ?? '';
    $duracion = $input['duracion'] ?? '';
    $matricula = $input['matricula'] ?? '';

    if (!$fecha || !$area || !$acciones || !$hora_inicio || !$duracion || !$matricula) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios.']);
        exit;
    }

    try {
        // Validate matricula
        $stmtEmp = $conn->prepare("SELECT nombre FROM empleados WHERE matricula = :matricula");
        $stmtEmp->execute([':matricula' => $matricula]);
        $empleado = $stmtEmp->fetch();

        if (!$empleado) {
            echo json_encode(['success' => false, 'error_type' => 'matricula', 'message' => 'Matrícula no encontrada en el sistema.']);
            exit;
        }

        $nombre = $empleado['nombre'];

        // Insert
        $sql = "INSERT INTO informes (fecha, area, acciones, hora_inicio, duracion, matricula_empleado, nombre_empleado)
                VALUES (:fecha, :area, :acciones, :hora_inicio, :duracion, :matricula, :nombre)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':fecha' => $fecha,
            ':area' => $area,
            ':acciones' => $acciones,
            ':hora_inicio' => $hora_inicio,
            ':duracion' => $duracion,
            ':matricula' => $matricula,
            ':nombre' => $nombre
        ]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';
    $fecha = $input['fecha'] ?? '';
    $area = $input['area'] ?? '';
    $acciones = $input['acciones'] ?? '';
    $hora_inicio = $input['hora_inicio'] ?? '';
    $duracion = $input['duracion'] ?? '';

    if (!$id || !$fecha || !$area || !$acciones || !$hora_inicio || !$duracion) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios.']);
        exit;
    }

    try {
        $sql = "UPDATE informes SET fecha = :fecha, area = :area, acciones = :acciones, hora_inicio = :hora_inicio, duracion = :duracion WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':fecha' => $fecha,
            ':area' => $area,
            ':acciones' => $acciones,
            ':hora_inicio' => $hora_inicio,
            ':duracion' => $duracion,
            ':id' => $id
        ]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
