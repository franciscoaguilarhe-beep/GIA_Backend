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
    case 'monitores': $table = 'monitores'; break;
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
        $query = "SELECT t.* ";
        
        if ($cat !== 'monitores') {
            $query .= ", u.unidad as unidad ";
        }
        
        if ($cat === 'computo') {
            $query .= ", e.matricula as matricula, e.nombre as nombre, e.categoria as categoria_usuario, 
                      COALESCE(NULLIF(e.usuario, ''), t.cuenta_dominio) as cuenta_dominio ";
        } elseif ($cat === 'monitores') {
            $query .= ", ec.area as cpu_area, ec.departamento as cpu_departamento, u_ec.unidad as cpu_unidad, ec.nombre_equipo as equipo_asociado ";
        }
        
        $query .= " FROM $table t ";
        
        if ($cat !== 'monitores') {
            $query .= " LEFT JOIN unidades u ON t.id_unidad = u.id ";
        }
        
        if ($cat === 'computo') {
            $query .= " LEFT JOIN empleados e ON t.id_usuario = e.id ";
        } elseif ($cat === 'monitores') {
            $query .= " LEFT JOIN equipos_computo ec ON ec.monitor = t.serie LEFT JOIN unidades u_ec ON ec.id_unidad = u_ec.id ";
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
        
        if ($cat === 'monitores') {
            if (!empty($row['equipo_asociado'])) {
                $row['area'] = $row['cpu_area'] ?: 'N/A';
                $row['departamento'] = $row['cpu_departamento'] ?: 'N/A';
                $row['unidad'] = $row['cpu_unidad'] ?: 'N/A';
            } else {
                $row['area'] = 'Sin asignar';
                $row['departamento'] = 'Sin asignar';
                $row['unidad'] = 'Sin asignar';
            }
            // Remove the DB columns so they don't show up in "Otros Datos"
            unset($row['cpu_area'], $row['cpu_departamento'], $row['cpu_unidad']);
            // Also unset internal timestamps to avoid them showing in "Otros Datos"
            unset($row['created_at'], $row['updated_at']);
        }

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
