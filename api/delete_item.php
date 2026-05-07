<?php
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$cat = isset($_POST['entity_cat']) ? $_POST['entity_cat'] : '';

if ($id === 0 || empty($cat)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID o categorÃ­a faltante']);
    exit;
}

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
        echo json_encode(['error' => 'CategorÃ­a invÃ¡lida']);
        exit;
}

try {
    $stmt = $db->prepare("DELETE FROM $table WHERE id = :id");
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo eliminar']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
