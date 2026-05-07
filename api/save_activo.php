<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get POST data
$data = $_POST;
$id = isset($data['id']) ? intval($data['id']) : 0;
$cat = isset($data['entity_cat']) ? $data['entity_cat'] : '';

if (empty($cat)) {
    http_response_code(400);
    echo json_encode(['error' => 'Categoría no especificada']);
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
        echo json_encode(['error' => 'Categoría inválida']);
        exit;
}

// Prepare fields and values
$fields = [];
$placeholders = [];
$params = [];

// Audit fields
if ($table !== 'unidades' && $table !== 'empleados') {
    $data['modificado_por'] = $_SESSION['user_id'];
}

// Map foreign keys and dates
function parseDate($val) {
    if (!$val || $val == 'N/A') return null;
    $val = str_replace('/', '-', $val);
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $val, $matches)) {
        return $matches[3] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
    }
    $time = strtotime($val);
    return $time ? date('Y-m-d', $time) : null;
}

if (isset($data['unidad']) && $table !== 'unidades') {
    $un_val = trim($data['unidad']);
    if ($un_val && $un_val !== 'N/A') {
        $un_stmt = $db->prepare("SELECT id FROM unidades WHERE unidad = :v OR clave = :v LIMIT 1");
        $un_stmt->execute([':v' => $un_val]);
        $un_res = $un_stmt->fetch();
        $data['id_unidad'] = $un_res ? $un_res['id'] : null;
    } else {
        $data['id_unidad'] = null;
    }
    unset($data['unidad']);
}

if (isset($data['matricula']) && $table === 'equipos_computo') {
    $mat = trim($data['matricula']);
    if ($mat && $mat !== 'N/A') {
        $e_stmt = $db->prepare("SELECT id FROM empleados WHERE matricula = :m LIMIT 1");
        $e_stmt->execute([':m' => $mat]);
        $e_res = $e_stmt->fetch();
        
        if ($e_res) {
            $data['id_usuario'] = $e_res['id'];
            // Actualizar información del empleado si ya existe
            $upd_e = $db->prepare("UPDATE empleados SET nombre = :n, usuario = :u, categoria = :c WHERE id = :id");
            $upd_e->execute([
                ':n' => (isset($data['nombre']) && $data['nombre'] !== 'N/A') ? trim($data['nombre']) : 'N/A',
                ':u' => isset($data['cuenta']) ? trim($data['cuenta']) : '',
                ':c' => isset($data['categoria_usuario']) ? trim($data['categoria_usuario']) : '',
                ':id' => $e_res['id']
            ]);
            
            // Sincronizar cuenta_dominio si el empleado tiene usuario
            if (!empty($data['cuenta'])) {
                $data['cuenta_dominio'] = $data['cuenta'];
            }
        } else {
            $ins_e = $db->prepare("INSERT INTO empleados (nombre, matricula, usuario, categoria, id_unidad) VALUES (:n, :m, :u, :c, :un)");
            $ins_e->execute([
                ':n' => (isset($data['nombre']) && $data['nombre'] !== 'N/A') ? trim($data['nombre']) : 'N/A',
                ':m' => $mat,
                ':u' => isset($data['cuenta']) ? trim($data['cuenta']) : '',
                ':c' => isset($data['categoria_usuario']) ? trim($data['categoria_usuario']) : '',
                ':un' => $data['id_unidad'] ?? null
            ]);
            $data['id_usuario'] = $db->lastInsertId();
            
            // Sincronizar cuenta_dominio si el empleado tiene usuario
            if (!empty($data['cuenta'])) {
                $data['cuenta_dominio'] = $data['cuenta'];
            }
        }
    } else {
        $data['id_usuario'] = null;
    }
    unset($data['matricula']);
    unset($data['nombre']);
    unset($data['cuenta']);
    unset($data['categoria_usuario']);
} elseif (isset($data['usuario']) && $table !== 'empleados') {
    $u_val = trim($data['usuario']);
    if ($u_val && $u_val !== 'N/A') {
        $u_stmt = $db->prepare("SELECT id FROM empleados WHERE nombre = :v OR matricula = :v OR usuario = :v LIMIT 1");
        $u_stmt->execute([':v' => $u_val]);
        $u_res = $u_stmt->fetch();
        $data['id_usuario'] = $u_res ? $u_res['id'] : null;
    } else {
        $data['id_usuario'] = null;
    }
    unset($data['usuario']);
}

if (isset($data['fecha_instalacion'])) $data['fecha_instalacion'] = parseDate($data['fecha_instalacion']);
if (isset($data['fecha_retiro'])) $data['fecha_retiro'] = parseDate($data['fecha_retiro']);

if (isset($data['categoria_usuario'])) {
    unset($data['categoria_usuario']);
}

// Remove non-table fields
unset($data['entity_cat']);
unset($data['id']);
if ($table !== 'empleados') {
    unset($data['categoria']);
}

foreach ($data as $key => $value) {
    if ($value === '') $value = null;
    $fields[] = "`$key`";
    $placeholders[] = ":$key";
    $params[":$key"] = $value;
}

try {
    if ($id > 0) {
        $update_parts = [];
        foreach ($fields as $index => $field) {
            $placeholder = $placeholders[$index];
            $update_parts[] = "$field = $placeholder";
        }
        $query = "UPDATE $table SET " . implode(", ", $update_parts) . " WHERE id = :target_id";
        $params[':target_id'] = $id;
    } else {
        $query = "INSERT INTO $table (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
    }

    $stmt = $db->prepare($query);
    if ($stmt->execute($params)) {
        echo json_encode(['success' => true, 'message' => 'Registro guardado correctamente']);
    } else {
        echo json_encode(['error' => 'No se pudo guardar el registro']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
