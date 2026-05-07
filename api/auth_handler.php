<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'login':
        handleLogin($db);
        break;
    case 'register':
        handleRegister($db);
        break;
    case 'logout':
        handleLogout();
        break;
    default:
        header("Location: ../index.php");
        break;
}

function handleLogin($db) {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($usuario) || empty($password)) {
        header("Location: ../login.php?error=Por favor complete todos los campos.");
        exit;
    }

    try {
        $query = "SELECT id, nombre, password, id_unidad FROM empleados WHERE usuario = :usuario LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['id_unidad'] = $user['id_unidad'];
                header("Location: ../index.php");
            } else {
                header("Location: ../login.php?error=ContraseÃ±a incorrecta.");
            }
        } else {
            header("Location: ../login.php?error=Usuario no encontrado.");
        }
    } catch (PDOException $e) {
        header("Location: ../login.php?error=Error de base de datos.");
    }
}

function handleRegister($db) {
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $matricula = isset($_POST['matricula']) ? trim($_POST['matricula']) : '';
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $id_unidad = isset($_POST['id_unidad']) ? intval($_POST['id_unidad']) : 0;
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($nombre) || empty($matricula) || empty($usuario) || empty($id_unidad) || empty($password)) {
        header("Location: ../register.php?error=Por favor complete todos los campos.");
        exit;
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // 1. Verificar si el empleado ya existe por MATRÃCULA
        $check_m_q = "SELECT id, usuario FROM empleados WHERE matricula = :matricula";
        $check_m_stmt = $db->prepare($check_m_q);
        $check_m_stmt->bindParam(':matricula', $matricula);
        $check_m_stmt->execute();
        $existing_employee = $check_m_stmt->fetch();

        // 2. Verificar si el NOMBRE DE USUARIO ya lo tiene OTRA persona
        $check_u_q = "SELECT id FROM empleados WHERE usuario = :usuario AND matricula != :matricula";
        $check_u_stmt = $db->prepare($check_u_q);
        $check_u_stmt->bindParam(':usuario', $usuario);
        $check_u_stmt->bindParam(':matricula', $matricula);
        $check_u_stmt->execute();

        if ($check_u_stmt->rowCount() > 0) {
            header("Location: ../register.php?error=El nombre de usuario ya estÃ¡ registrado por otra persona.");
            exit;
        }

        if ($existing_employee) {
            // ACTUALIZAR registro existente (UPSERT)
            $query = "UPDATE empleados SET nombre = :nombre, usuario = :usuario, id_unidad = :id_unidad, password = :password WHERE matricula = :matricula";
        } else {
            // INSERTAR nuevo registro
            $query = "INSERT INTO empleados (nombre, matricula, usuario, id_unidad, password) 
                      VALUES (:nombre, :matricula, :usuario, :id_unidad, :password)";
        }

        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':matricula', $matricula);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':id_unidad', $id_unidad);
        $stmt->bindParam(':password', $hashed_password);

        if ($stmt->execute()) {
            header("Location: ../login.php?success=Acceso restaurado/creado con Ã©xito. Inicie sesiÃ³n ahora.");
        } else {
            header("Location: ../register.php?error=No se pudo completar la operaciÃ³n.");
        }
    } catch (PDOException $e) {
        header("Location: ../register.php?error=Error: " . $e->getMessage());
    }
}

function handleLogout() {
    session_unset();
    session_destroy();
    header("Location: ../index.php");
}

function handleChangeUnit() {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $_SESSION['id_unidad'] = ($id > 0) ? $id : null;
    header("Location: " . $_SERVER['HTTP_REFERER']);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
switch($action) {
    case 'login': handleLogin($db); break;
    case 'register': handleRegister($db); break;
    case 'logout': handleLogout(); break;
    case 'change_unit': handleChangeUnit(); break;
}
?>
