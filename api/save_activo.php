<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get POST data
$data = $_POST;
$id = isset($data['id']) ? intval($data['id']) : 0;
$cat = isset($data['entity_cat']) ? $data['entity_cat'] : '';

// 1. Validar matrícula del responsable que autoriza la acción
$matricula_responsable = trim($data['matricula_responsable'] ?? ($data['matricula_autoriza'] ?? ''));
if (empty($matricula_responsable)) {
    http_response_code(400);
    echo json_encode(['error' => 'Se requiere la matrícula del personal para autorizar esta acción.']);
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

// Mapeo de campos para compatibilidad con la App Móvil
if (isset($data['usuario_asignado']) && (!isset($data['nombre']) || $data['nombre'] == 'N/A' || empty($data['nombre']))) {
    $data['nombre'] = $data['usuario_asignado'];
}

// Traducción de nombres de campos Móvil -> Base de Datos
if (isset($data['tipo_almacenamiento'])) {
    $data['tipo_alm'] = $data['tipo_almacenamiento'];
    unset($data['tipo_almacenamiento']);
}
if (isset($data['puerto_router'])) {
    $data['p_router'] = $data['puerto_router'];
    unset($data['puerto_router']);
}
if (isset($data['mac_net']) && $cat == 'redes') {
    $data['mac'] = $data['mac_net'];
    unset($data['mac_net']);
}

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
    case 'monitores': $table = 'monitores'; break;
    case 'unidades': $table = 'unidades'; break;
    case 'empleados': $table = 'empleados'; break;
    default: 
        http_response_code(400);
        echo json_encode(['error' => 'Categoría inválida']);
        exit;
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
            $upd_e = $db->prepare("UPDATE empleados SET nombre = :n, usuario = :u, categoria = :c WHERE id = :id");
            $upd_e->execute([
                ':n' => (isset($data['nombre']) && $data['nombre'] !== 'N/A') ? trim($data['nombre']) : 'N/A',
                ':u' => isset($data['cuenta']) ? trim($data['cuenta']) : '',
                ':c' => isset($data['categoria_usuario']) ? trim($data['categoria_usuario']) : '',
                ':id' => $e_res['id']
            ]);
            
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

// Remove non-table fields and internal mobile fields
unset($data['entity_cat']);
unset($data['id']);
unset($data['remote_id']);
unset($data['is_dirty']);
unset($data['json_data']);
unset($data['usuario_asignado']);
unset($data['matricula_responsable']);
unset($data['matricula_autoriza']);

if ($table !== 'empleados') {
    unset($data['matricula']);
    unset($data['cuenta']);
    unset($data['categoria']);
}
if ($table !== 'telefonos' && $table !== 'empleados') {
    unset($data['nombre']);
}

// Asignar modificado_por SOLO en tablas que no sean unidades ni empleados
if ($table !== 'unidades' && $table !== 'empleados') {
    $data['modificado_por'] = $matricula_responsable;
}

// Obtener columnas reales de la tabla para filtrar campos inválidos
$valid_columns = [];
try {
    $q_cols = $db->query("DESCRIBE $table");
    while ($col = $q_cols->fetch()) {
        $valid_columns[] = $col['Field'];
    }
} catch (Exception $e) {
}

$fields = [];
$placeholders = [];
$params = [];

foreach ($data as $key => $value) {
    if ($value === '' || $value === 'N/A') $value = null;
    
    if (in_array($key, $valid_columns)) {
        $fields[] = "`$key`";
        $placeholders[] = ":$key";
        $params[":$key"] = $value;
    }
}

try {
    if ($id > 0) {
        // Obtener estado anterior para registrar los cambios en historial
        $old_stmt = $db->prepare("SELECT * FROM $table WHERE id = :id LIMIT 1");
        $old_stmt->execute([':id' => $id]);
        $old_row = $old_stmt->fetch(PDO::FETCH_ASSOC);

        $changes = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $valid_columns) && $k !== 'modificado_por') {
                $old_val = $old_row[$k] ?? null;
                $new_val = $v;
                if ((string)$old_val !== (string)$new_val) {
                    $old_display = ($old_val === null || $old_val === '') ? 'vacío' : $old_val;
                    $new_display = ($new_val === null || $new_val === '') ? 'vacío' : $new_val;
                    $changes[] = "$k: '$old_display' → '$new_display'";
                }
            }
        }

        $detalles = count($changes) > 0 ? implode(" | ", $changes) : "Modificación de datos generales";

        $update_parts = [];
        foreach ($fields as $index => $field) {
            $placeholder = $placeholders[$index];
            $update_parts[] = "$field = $placeholder";
        }
        $query = "UPDATE $table SET " . implode(", ", $update_parts) . " WHERE id = :target_id";
        $params[':target_id'] = $id;

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        // Identificador para el historial
        $identificador = $old_row['serie'] ?? ($old_row['clave'] ?? ($old_row['matricula'] ?? ($old_row['unidad'] ?? "ID $id")));

        // Registrar en historial
        $hist_q = "INSERT INTO historial (categoria, id_activo, identificador, accion, detalles, matricula_responsable, nombre_responsable) 
                   VALUES (:cat, :id_a, :ident, 'MODIFICACION', :det, :mat, :nom)";
        $hist_stmt = $db->prepare($hist_q);
        $hist_stmt->execute([
            ':cat' => $cat,
            ':id_a' => $id,
            ':ident' => $identificador,
            ':det' => $detalles,
            ':mat' => $matricula_responsable,
            ':nom' => $nombre_responsable
        ]);

        echo json_encode(['success' => true, 'message' => 'Registro actualizado correctamente']);
    } else {
        $query = "INSERT INTO $table (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $new_id = $db->lastInsertId();

        $identificador = $data['serie'] ?? ($data['clave'] ?? ($data['matricula'] ?? ($data['unidad'] ?? "ID $new_id")));

        // Registrar en historial
        $hist_q = "INSERT INTO historial (categoria, id_activo, identificador, accion, detalles, matricula_responsable, nombre_responsable) 
                   VALUES (:cat, :id_a, :ident, 'ALTA', 'Alta de nuevo registro en el inventario', :mat, :nom)";
        $hist_stmt = $db->prepare($hist_q);
        $hist_stmt->execute([
            ':cat' => $cat,
            ':id_a' => $new_id,
            ':ident' => $identificador,
            ':mat' => $matricula_responsable,
            ':nom' => $nombre_responsable
        ]);

        echo json_encode(['success' => true, 'message' => 'Registro agregado correctamente']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
