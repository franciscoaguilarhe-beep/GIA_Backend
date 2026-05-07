<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$cat = isset($_GET['cat']) ? $_GET['cat'] : '';

if($id === 0 || empty($cat)){
    http_response_code(400);
    echo json_encode(['error' => 'ID o categoría faltante.']);
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
        echo json_encode(['error' => 'Categoría inválida.']);
        exit;
}

try {
    // Para simplificar, hacemos un SELECT * de la tabla correspondiente
    // e incluimos un JOIN con unidades si existe la relación
    
    if ($cat === 'unidades') {
        $query = "SELECT * FROM $table t";
    } else {
        $query = "SELECT t.*, u.unidad as unidad ";
        if ($cat === 'computo') {
            $query .= ", e.matricula as matricula, e.nombre as nombre, e.categoria as categoria_usuario, 
                      COALESCE(NULLIF(e.usuario, ''), t.cuenta_dominio) as cuenta_dominio ";
        }
        $query .= " FROM $table t LEFT JOIN unidades u ON t.id_unidad = u.id ";
        if ($cat === 'computo') {
            $query .= " LEFT JOIN empleados e ON t.id_usuario = e.id ";
        }
    }
    
    $query .= " WHERE t.id = :id LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        unset($row['id_unidad']);
        unset($row['id_usuario']);

        // Función para limpiar caracteres no UTF-8 que rompen json_encode
        array_walk_recursive($row, function(&$item) {
            if (is_string($item)) {
                $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
            }
        });

        echo json_encode($row, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Activo no encontrado.']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
