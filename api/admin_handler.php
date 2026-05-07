<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_POST['action']) ? $_POST['action'] : '';

if($action == 'save_unidad') {
    $clave = $_POST['clave'] ?? '';
    $unidad = $_POST['unidad'] ?? '';
    $zona = $_POST['zona'] ?? '';

    if(empty($clave) || empty($unidad) || empty($zona)) {
        echo json_encode(['success' => false, 'error' => 'Todos los campos son requeridos']);
        exit;
    }

    $query = "INSERT INTO unidades (clave, unidad, zona) VALUES (:clave, :unidad, :zona)";
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':clave', $clave);
        $stmt->bindParam(':unidad', $unidad);
        $stmt->bindParam(':zona', $zona);
        $stmt->execute();
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} elseif($action == 'save_empleado') {
    $matricula = $_POST['matricula'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $id_unidad = $_POST['id_unidad'] ?? '';

    if(empty($matricula) || empty($nombre) || empty($usuario) || empty($password) || empty($id_unidad) || empty($categoria)) {
        echo json_encode(['success' => false, 'error' => 'Todos los campos son requeridos']);
        exit;
    }

    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    $query = "INSERT INTO empleados (matricula, nombre, usuario, password, categoria, id_unidad) VALUES (:matricula, :nombre, :usuario, :password, :categoria, :id_unidad)";
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':matricula', $matricula);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':password', $password_hashed);
        $stmt->bindParam(':categoria', $categoria);
        $stmt->bindParam(':id_unidad', $id_unidad);
        $stmt->execute();
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'AcciÃ³n invÃ¡lida']);
}
?>
