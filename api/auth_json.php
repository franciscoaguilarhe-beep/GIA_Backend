<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->usuario) && !empty($data->password)) {
    try {
        $query = "SELECT id, nombre, password, id_unidad FROM empleados WHERE usuario = :usuario LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':usuario', $data->usuario);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($data->password, $user['password'])) {
                // In a real app, you'd generate a JWT here. 
                // For this implementation, we return success and user info.
                echo json_encode([
                    "success" => true,
                    "message" => "Login exitoso",
                    "user" => [
                        "id" => $user['id'],
                        "nombre" => $user['nombre'],
                        "id_unidad" => $user['id_unidad']
                    ],
                    "token" => bin2hex(random_bytes(16)) // Dummy token
                ]);
            } else {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Contraseña incorrecta"]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Usuario no encontrado"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error de base de datos: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
}
?>
