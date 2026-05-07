<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$q = isset($_GET['q']) ? $_GET['q'] : '';

if(empty($q) || strlen($q) < 2){
    echo json_encode([]);
    exit;
}

$term = "%{$q}%";

// Subqueries with expanded search fields
$query_equipos = "
    SELECT 'computo' as categoria, t.id, t.serie, t.tipo, t.fabricante, t.modelo, t.ip, t.id_unidad, t.id_usuario, t.area, t.departamento
    FROM equipos_computo t
    LEFT JOIN empleados e ON t.id_usuario = e.id
    WHERE t.serie LIKE :term OR t.ip LIKE :term OR t.modelo LIKE :term OR t.tipo LIKE :term OR t.area LIKE :term OR t.departamento LIKE :term OR e.nombre LIKE :term OR e.matricula LIKE :term
";

$query_impresoras = "
    SELECT 'impresoras' as categoria, t.id, t.serie, t.tipo, t.fabricante, t.modelo, t.ip, t.id_unidad, NULL as id_usuario, t.area, t.departamento
    FROM impresoras t
    WHERE t.serie LIKE :term OR t.ip LIKE :term OR t.modelo LIKE :term OR t.tipo LIKE :term OR t.area LIKE :term OR t.departamento LIKE :term
";

$query_televisiones = "
    SELECT 'televisiones' as categoria, t.id, t.serie, NULL as tipo, t.fabricante, t.modelo, NULL as ip, t.id_unidad, NULL as id_usuario, t.area, t.departamento
    FROM televisiones t
    WHERE t.serie LIKE :term OR t.modelo LIKE :term OR t.area LIKE :term OR t.departamento LIKE :term
";

$query_telefonos = "
    SELECT 'telefonia' as categoria, t.id, t.serie, t.tipo, t.fabricante, t.modelo, t.ip, t.id_unidad, NULL as id_usuario, t.area, t.departamento
    FROM telefonos t
    WHERE t.serie LIKE :term OR t.ip LIKE :term OR t.modelo LIKE :term OR t.tipo LIKE :term OR t.area LIKE :term OR t.departamento LIKE :term OR t.nombre LIKE :term OR t.extension LIKE :term
";

$query_redes = "
    SELECT 'redes' as categoria, t.id, t.serie, t.tipo, t.fabricante, t.modelo, t.ip_gestion as ip, t.id_unidad, NULL as id_usuario, t.area, NULL as departamento
    FROM redes t
    WHERE t.serie LIKE :term OR t.ip_gestion LIKE :term OR t.modelo LIKE :term OR t.tipo LIKE :term OR t.area LIKE :term
";

$query_unida = "
    SELECT t.categoria, t.id, t.serie, t.tipo, t.fabricante, t.modelo, t.ip, u.unidad, e.nombre as usuario
    FROM (
        ($query_equipos)
        UNION ALL
        ($query_impresoras)
        UNION ALL
        ($query_televisiones)
        UNION ALL
        ($query_telefonos)
        UNION ALL
        ($query_redes)
    ) as t
    LEFT JOIN unidades u ON t.id_unidad = u.id
    LEFT JOIN empleados e ON t.id_usuario = e.id
    LIMIT 20
";

try {
    $stmt = $db->prepare($query_unida);
    $stmt->bindParam(':term', $term);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($resultados);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
