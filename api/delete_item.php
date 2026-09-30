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

// 1. Validar matrícula del personal que autoriza la baja
$matricula_responsable = trim($_POST['matricula_responsable'] ?? ($_POST['matricula'] ?? ($_POST['matricula_autoriza'] ?? '')));
if (empty($matricula_responsable)) {
    http_response_code(400);
    echo json_encode(['error' => 'Se requiere la matrícula del personal para autorizar la acción.']);
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

// Parámetros de la baja/retiro
$fecha_baja_input = trim($_POST['fecha_retiro'] ?? ($_POST['fecha_baja'] ?? date('Y-m-d')));
$estatus_baja = trim($_POST['estatus'] ?? 'RETIRADO');
if (empty($estatus_baja)) $estatus_baja = 'RETIRADO';

$observaciones_baja = trim($_POST['observaciones'] ?? ($_POST['motivo'] ?? ($_POST['nota'] ?? '')));

// Formatear fecha a Y-m-d
if (!empty($fecha_baja_input)) {
    $fecha_baja_input = str_replace('/', '-', $fecha_baja_input);
    $time = strtotime($fecha_baja_input);
    $fecha_baja = $time ? date('Y-m-d', $time) : date('Y-m-d');
} else {
    $fecha_baja = date('Y-m-d');
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

try {
    // Obtener datos del registro antes del cambio
    $info_stmt = $db->prepare("SELECT * FROM $table WHERE id = :id LIMIT 1");
    $info_stmt->execute([':id' => $id]);
    $row = $info_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'El registro a procesar no fue encontrado.']);
        exit;
    }

    $identificador = $row['serie'] ?? ($row['clave'] ?? ($row['matricula'] ?? ($row['unidad'] ?? ($row['nombre'] ?? "ID $id"))));

    // Obtener las columnas reales de la tabla
    $valid_columns = [];
    try {
        $q_cols = $db->query("DESCRIBE $table");
        while ($col = $q_cols->fetch(PDO::FETCH_ASSOC)) {
            $valid_columns[] = $col['Field'];
        }
    } catch (Exception $e) {}

    // Si la tabla no soporta el campo de estatus ni fecha_retiro (ej. unidades o empleados), se desvincula o elimina según corresponda
    if (!in_array('estatus', $valid_columns) && !in_array('fecha_retiro', $valid_columns)) {
        if ($table === 'unidades') {
            $db->prepare("UPDATE equipos_computo SET id_unidad = NULL WHERE id_unidad = :id")->execute([':id' => $id]);
            $db->prepare("UPDATE impresoras SET id_unidad = NULL WHERE id_unidad = :id")->execute([':id' => $id]);
            $db->prepare("UPDATE televisiones SET id_unidad = NULL WHERE id_unidad = :id")->execute([':id' => $id]);
            $db->prepare("UPDATE telefonos SET id_unidad = NULL WHERE id_unidad = :id")->execute([':id' => $id]);
            $db->prepare("UPDATE redes SET id_unidad = NULL WHERE id_unidad = :id")->execute([':id' => $id]);
            $db->prepare("UPDATE consumibles SET id_unidad = NULL WHERE id_unidad = :id")->execute([':id' => $id]);
            $db->prepare("UPDATE empleados SET id_unidad = NULL WHERE id_unidad = :id")->execute([':id' => $id]);
        }
        if ($table === 'empleados') {
            $db->prepare("UPDATE equipos_computo SET id_usuario = NULL WHERE id_usuario = :id")->execute([':id' => $id]);
        }

        try {
            $db->prepare("DELETE FROM notas WHERE categoria = :cat AND id_activo = :id")->execute([':cat' => $cat, ':id' => $id]);
        } catch (Exception $e) {}

        $stmt = $db->prepare("DELETE FROM $table WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $hist_q = "INSERT INTO historial (categoria, id_activo, identificador, accion, detalles, matricula_responsable, nombre_responsable) 
                   VALUES (:cat, :id_a, :ident, 'ELIMINACION', :det, :mat, :nom)";
        $hist_stmt = $db->prepare($hist_q);
        $hist_stmt->execute([
            ':cat' => $cat,
            ':id_a' => $id,
            ':ident' => $identificador,
            ':det' => "Eliminación permanente de $cat ($identificador)",
            ':mat' => $matricula_responsable,
            ':nom' => $nombre_responsable
        ]);

        echo json_encode(['success' => true, 'message' => 'Eliminado correctamente']);
        exit;
    }

    // Proceso de BAJA / RETIRO (Soft Delete)
    $updates = [];
    $params = [':id' => $id];

    if (in_array('estatus', $valid_columns)) {
        $updates[] = "`estatus` = :estatus";
        $params[':estatus'] = $estatus_baja;
    }

    if (in_array('fecha_retiro', $valid_columns)) {
        $updates[] = "`fecha_retiro` = :fecha_retiro";
        $params[':fecha_retiro'] = $fecha_baja;
    }

    if (in_array('modificado_por', $valid_columns)) {
        $updates[] = "`modificado_por` = :modificado_por";
        $params[':modificado_por'] = $matricula_responsable;
    }

    // Si se retira un equipo de cómputo, desvincular el usuario asignado para liberar asignación
    if ($table === 'equipos_computo' && in_array('id_usuario', $valid_columns)) {
        $updates[] = "`id_usuario` = NULL";
    }

    if (in_array('observaciones', $valid_columns)) {
        $old_obs = trim($row['observaciones'] ?? '');
        $new_note_str = "[BAJA $fecha_baja]" . (!empty($observaciones_baja) ? ": $observaciones_baja" : "");
        $updated_obs = !empty($old_obs) ? ($old_obs . "\n" . $new_note_str) : $new_note_str;
        $updates[] = "`observaciones` = :observaciones";
        $params[':observaciones'] = $updated_obs;
    }

    $upd_sql = "UPDATE $table SET " . implode(", ", $updates) . " WHERE id = :id";
    $upd_stmt = $db->prepare($upd_sql);
    $upd_stmt->execute($params);

    // Agregar registro en Bitácora de Notas
    try {
        $nota_texto = "Baja/Retiro de activo ($identificador). Estatus: $estatus_baja, Fecha de retiro: $fecha_baja.";
        if (!empty($observaciones_baja)) {
            $nota_texto .= " Motivo: " . $observaciones_baja;
        }
        $ins_n = $db->prepare("INSERT INTO notas (categoria, id_activo, titulo, nota, personal, matricula, fecha) 
                              VALUES (:cat, :id_a, :tit, :nota, :pers, :mat, :fec)");
        $ins_n->execute([
            ':cat' => $cat,
            ':id_a' => $id,
            ':tit' => 'BAJA DE EQUIPO',
            ':nota' => $nota_texto,
            ':pers' => $nombre_responsable,
            ':mat' => $matricula_responsable,
            ':fec' => $fecha_baja . ' ' . date('H:i:s')
        ]);
    } catch (Exception $e) {}

    // Registrar en Historial de Movimientos
    $detalles_hist = "Baja de activo ($identificador) | Estatus: '$estatus_baja' | Fecha Retiro: '$fecha_baja'";
    if (!empty($observaciones_baja)) {
        $detalles_hist .= " | Motivo: '$observaciones_baja'";
    }

    $hist_q = "INSERT INTO historial (categoria, id_activo, identificador, accion, detalles, matricula_responsable, nombre_responsable) 
               VALUES (:cat, :id_a, :ident, 'BAJA', :det, :mat, :nom)";
    $hist_stmt = $db->prepare($hist_q);
    $hist_stmt->execute([
        ':cat' => $cat,
        ':id_a' => $id,
        ':ident' => $identificador,
        ':det' => $detalles_hist,
        ':mat' => $matricula_responsable,
        ':nom' => $nombre_responsable
    ]);

    echo json_encode([
        'success' => true, 
        'message' => "El registro '$identificador' ha sido cambiado a estatus $estatus_baja con fecha $fecha_baja."
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
