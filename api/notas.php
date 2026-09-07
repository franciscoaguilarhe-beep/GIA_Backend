<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get');

if ($action === 'get') {
    $cat = $_GET['cat'] ?? ($_GET['categoria'] ?? '');
    $id_activo = isset($_GET['id_activo']) ? intval($_GET['id_activo']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

    if (empty($cat) || $id_activo === 0) {
        echo json_encode([]);
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM notas WHERE categoria = :cat AND id_activo = :id ORDER BY fecha DESC, id_nota DESC");
        $stmt->execute([':cat' => $cat, ':id' => $id_activo]);
        $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($notas, JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al consultar notas: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'add') {
    $cat = trim($_POST['categoria'] ?? ($_POST['entity_cat'] ?? ''));
    $id_activo = intval($_POST['id_activo'] ?? ($_POST['id'] ?? 0));
    $titulo = trim($_POST['titulo'] ?? '');
    $nota = trim($_POST['nota'] ?? '');
    $fecha = trim($_POST['fecha'] ?? '');
    $matricula = trim($_POST['matricula'] ?? '');

    if (empty($cat) || $id_activo === 0 || empty($titulo) || empty($nota) || empty($matricula)) {
        http_response_code(400);
        echo json_encode(['error' => 'Todos los campos son obligatorios (Categoría, ID, Título, Nota y Matrícula).']);
        exit;
    }

    // 1. Validar matrícula en empleados
    $emp_stmt = $db->prepare("SELECT id, matricula, nombre FROM empleados WHERE matricula = :m LIMIT 1");
    $emp_stmt->execute([':m' => $matricula]);
    $emp = $emp_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emp) {
        http_response_code(400);
        echo json_encode(['error' => "La matrícula '$matricula' no se encuentra registrada en el sistema."]);
        exit;
    }

    $personal = $emp['nombre'];

    // 2. Procesar fecha personalizada
    if (!empty($fecha)) {
        if (strlen($fecha) === 10) {
            $fecha_insert = $fecha . ' ' . date('H:i:s');
        } else {
            $fecha_insert = date('Y-m-d H:i:s', strtotime($fecha));
        }
    } else {
        $fecha_insert = date('Y-m-d H:i:s');
    }

    try {
        // Insertar nota
        $ins_q = "INSERT INTO notas (categoria, id_activo, titulo, nota, personal, matricula, fecha) 
                  VALUES (:cat, :id_a, :tit, :nota, :pers, :mat, :fec)";
        $ins_stmt = $db->prepare($ins_q);
        $ins_stmt->execute([
            ':cat' => $cat,
            ':id_a' => $id_activo,
            ':tit' => $titulo,
            ':nota' => $nota,
            ':pers' => $personal,
            ':mat' => $matricula,
            ':fec' => $fecha_insert
        ]);

        // Registrar en historial
        $hist_q = "INSERT INTO historial (categoria, id_activo, identificador, accion, detalles, matricula_responsable, nombre_responsable) 
                   VALUES (:cat, :id_a, :ident, 'MODIFICACION', :det, :mat, :nom)";
        $hist_stmt = $db->prepare($hist_q);
        $hist_stmt->execute([
            ':cat' => $cat,
            ':id_a' => $id_activo,
            ':ident' => "ID $id_activo",
            ':det' => "Nota agregada: '$titulo' - " . (strlen($nota) > 50 ? substr($nota, 0, 47) . '...' : $nota),
            ':mat' => $matricula,
            ':nom' => $personal
        ]);

        echo json_encode(['success' => true, 'message' => 'Nota agregada correctamente.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de base de datos al guardar nota: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'update') {
    $id_nota = intval($_POST['id_nota'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $nota = trim($_POST['nota'] ?? '');
    $fecha = trim($_POST['fecha'] ?? '');
    $matricula = trim($_POST['matricula'] ?? '');

    if ($id_nota === 0 || empty($titulo) || empty($nota) || empty($matricula)) {
        http_response_code(400);
        echo json_encode(['error' => 'Título, nota y matrícula son obligatorios.']);
        exit;
    }

    // 1. Validar matrícula en empleados
    $emp_stmt = $db->prepare("SELECT id, matricula, nombre FROM empleados WHERE matricula = :m LIMIT 1");
    $emp_stmt->execute([':m' => $matricula]);
    $emp = $emp_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emp) {
        http_response_code(400);
        echo json_encode(['error' => "La matrícula '$matricula' no se encuentra registrada en el sistema."]);
        exit;
    }

    $personal = $emp['nombre'];

    // 2. Procesar fecha
    if (!empty($fecha)) {
        if (strlen($fecha) === 10) {
            $fecha_update = $fecha . ' ' . date('H:i:s');
        } else {
            $fecha_update = date('Y-m-d H:i:s', strtotime($fecha));
        }
    } else {
        $fecha_update = date('Y-m-d H:i:s');
    }

    try {
        $upd_q = "UPDATE notas SET titulo = :tit, nota = :nota, personal = :pers, matricula = :mat, fecha = :fec WHERE id_nota = :id";
        $upd_stmt = $db->prepare($upd_q);
        $upd_stmt->execute([
            ':tit' => $titulo,
            ':nota' => $nota,
            ':pers' => $personal,
            ':mat' => $matricula,
            ':fec' => $fecha_update,
            ':id' => $id_nota
        ]);

        // Registrar en historial
        $n_stmt = $db->prepare("SELECT categoria, id_activo FROM notas WHERE id_nota = :id LIMIT 1");
        $n_stmt->execute([':id' => $id_nota]);
        $nota_row = $n_stmt->fetch(PDO::FETCH_ASSOC);

        if ($nota_row) {
            $hist_q = "INSERT INTO historial (categoria, id_activo, identificador, accion, detalles, matricula_responsable, nombre_responsable) 
                       VALUES (:cat, :id_a, :ident, 'MODIFICACION', :det, :mat, :nom)";
            $hist_stmt = $db->prepare($hist_q);
            $hist_stmt->execute([
                ':cat' => $nota_row['categoria'],
                ':id_a' => $nota_row['id_activo'],
                ':ident' => "ID " . $nota_row['id_activo'],
                ':det' => "Nota actualizada: '$titulo'",
                ':mat' => $matricula,
                ':nom' => $personal
            ]);
        }

        echo json_encode(['success' => true, 'message' => 'Nota actualizada correctamente.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de base de datos al actualizar nota: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id_nota = intval($_POST['id_nota'] ?? 0);
    $matricula = trim($_POST['matricula'] ?? '');

    if ($id_nota === 0 || empty($matricula)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de nota y matrícula son requeridos.']);
        exit;
    }

    // 1. Validar matrícula en empleados
    $emp_stmt = $db->prepare("SELECT id, matricula, nombre FROM empleados WHERE matricula = :m LIMIT 1");
    $emp_stmt->execute([':m' => $matricula]);
    $emp = $emp_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emp) {
        http_response_code(400);
        echo json_encode(['error' => "La matrícula '$matricula' no se encuentra registrada en el sistema."]);
        exit;
    }

    try {
        // Obtener datos de la nota antes de borrar
        $n_stmt = $db->prepare("SELECT * FROM notas WHERE id_nota = :id LIMIT 1");
        $n_stmt->execute([':id' => $id_nota]);
        $nota_row = $n_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$nota_row) {
            http_response_code(404);
            echo json_encode(['error' => 'Nota no encontrada.']);
            exit;
        }

        // Borrar nota
        $del_stmt = $db->prepare("DELETE FROM notas WHERE id_nota = :id");
        $del_stmt->execute([':id' => $id_nota]);

        // Registrar en historial
        $hist_q = "INSERT INTO historial (categoria, id_activo, identificador, accion, detalles, matricula_responsable, nombre_responsable) 
                   VALUES (:cat, :id_a, :ident, 'MODIFICACION', :det, :mat, :nom)";
        $hist_stmt = $db->prepare($hist_q);
        $hist_stmt->execute([
            ':cat' => $nota_row['categoria'],
            ':id_a' => $nota_row['id_activo'],
            ':ident' => "ID " . $nota_row['id_activo'],
            ':det' => "Nota eliminada: '{$nota_row['titulo']}'",
            ':mat' => $matricula,
            ':nom' => $emp['nombre']
        ]);

        echo json_encode(['success' => true, 'message' => 'Nota eliminada correctamente.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de base de datos al eliminar nota: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['error' => 'Acción inválida.']);
