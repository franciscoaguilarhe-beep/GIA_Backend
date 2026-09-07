<?php
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$db = $database->getConnection();

$id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
$cat = isset($_POST['entity_cat']) ? $_POST['entity_cat'] : (isset($_POST['categoria']) ? $_POST['categoria'] : (isset($_POST['cat']) ? $_POST['cat'] : (isset($_GET['cat']) ? $_GET['cat'] : '')));

if ($id === 0 || empty($cat)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID o categoría faltante']);
    exit;
}

// 1. Validar matrícula del personal que autoriza la eliminación
$matricula_responsable = trim($_POST['matricula_responsable'] ?? ($_POST['matricula'] ?? ($_POST['matricula_autoriza'] ?? '')));
if (empty($matricula_responsable)) {
    http_response_code(400);
    echo json_encode(['error' => 'Se requiere la matrícula del personal para autorizar la eliminación.']);
    exit;
}

$emp_stmt = $db->prepare("SELECT id, matricula, nombre FROM empleados WHERE matricula = :m LIMIT 1");
$emp_stmt->execute([':m' => $matricula_responsable]);
$responsable = $emp_stmt->fetch(PDO::FETCH_ASSOC);

if (!$responsable) {
    http_response_code(400);
    echo json_encode(['error' => "La matrícula '$matricula_responsable' no se encuentra registrada en el sistema."]);
    exit;
}

$nombre_responsable = $responsable['nombre'];

$table = '';
switch($cat) {
    case 'computo': $table = 'equipos_computo'; break;
    case 'impresoras': $table = 'impresoras'; break;
    case 'televisiones': $table = 'televisiones'; break;
    case 'telefonia': $table = 'telefonos'; break;
    case 'redes': $table = 'redes'; break;
    case 'consumibles': $table = 'consumibles'; break;
    case 'unidades': $table = 'unidades'; break;
    case 'empleados': $table = 'empleados'; break;
    default: 
        http_response_code(400);
        echo json_encode(['error' => 'Categoría inválida']);
        exit;
}

try {
    // Obtener datos del registro antes de eliminar
    $info_stmt = $db->prepare("SELECT * FROM $table WHERE id = :id LIMIT 1");
    $info_stmt->execute([':id' => $id]);
    $row = $info_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'El registro a eliminar no fue encontrado.']);
        exit;
    }

    $identificador = $row['serie'] ?? ($row['clave'] ?? ($row['matricula'] ?? ($row['unidad'] ?? ($row['nombre'] ?? "ID $id"))));

    // Si se elimina una unidad, desvincular primero los activos y empleados asociados para no violar claves foráneas
    if ($table === 'unidades') {
        $db->prepare("UPDATE equipos_computo SET id_unidad = NULL WHERE id_unidad = :id")->execute([':id' => $id]);
        $db->prepare("UPDATE impresoras SET id_unidad = NULL WHERE id_unidad = :id")->execute([':id' => $id]);
        $db->prepare("UPDATE televisiones SET id_unidad = NULL WHERE id_unidad = :id")->execute([':id' => $id]);
        $db->prepare("UPDATE telefonos SET id_unidad = NULL WHERE id_unidad = :id")->execute([':id' => $id]);
        $db->prepare("UPDATE redes SET id_unidad = NULL WHERE id_unidad = :id")->execute([':id' => $id]);
        $db->prepare("UPDATE consumibles SET id_unidad = NULL WHERE id_unidad = :id")->execute([':id' => $id]);
        $db->prepare("UPDATE empleados SET id_unidad = NULL WHERE id_unidad = :id")->execute([':id' => $id]);
    }
    
    // Si se elimina un empleado, desvincular los equipos de cómputo asociados
    if ($table === 'empleados') {
        $db->prepare("UPDATE equipos_computo SET id_usuario = NULL WHERE id_usuario = :id")->execute([':id' => $id]);
    }

    // Eliminar notas asociadas
    try {
        $db->prepare("DELETE FROM notas WHERE categoria = :cat AND id_activo = :id")->execute([':cat' => $cat, ':id' => $id]);
    } catch (Exception $e) {}

    // Eliminar el registro
    $stmt = $db->prepare("DELETE FROM $table WHERE id = :id");
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        // Registrar en historial
        $hist_q = "INSERT INTO historial (categoria, id_activo, identificador, accion, detalles, matricula_responsable, nombre_responsable) 
                   VALUES (:cat, :id_a, :ident, 'ELIMINACION', :det, :mat, :nom)";
        $hist_stmt = $db->prepare($hist_q);
        $hist_stmt->execute([
            ':cat' => $cat,
            ':id_a' => $id,
            ':ident' => $identificador,
            ':det' => "Eliminación de $cat ($identificador)",
            ':mat' => $matricula_responsable,
            ':nom' => $nombre_responsable
        ]);

        echo json_encode(['success' => true, 'message' => 'Eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo eliminar el registro']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
