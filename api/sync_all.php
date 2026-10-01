<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Subqueries for all categories with CORRECT field names
$query_equipos = "
    SELECT 'computo' as categoria, t.id, t.serie, t.tipo, t.fabricante, t.modelo, t.ip, t.id_unidad, t.id_usuario, 
           COALESCE(t.area, '') as area, COALESCE(t.departamento, '') as departamento, t.estatus,
           t.nombre_equipo, t.monitor, t.monitor_secundario, t.tipo_alm as tipo_almacenamiento, t.capacidad, t.ram, t.mac_net, t.mac_wifi, t.nodo, t.p_router as puerto_router, t.extension, t.proyecto, t.fecha_instalacion, t.fecha_retiro, t.observaciones,
           NULL as uso, NULL as num_puertos
    FROM equipos_computo t
";

$query_impresoras = "
    SELECT 'impresoras' as categoria, t.id, t.serie, t.tipo, t.fabricante, t.modelo, t.ip, t.id_unidad, NULL as id_usuario, 
           COALESCE(t.area, '') as area, COALESCE(t.departamento, '') as departamento, t.estatus,
           NULL as nombre_equipo, NULL as monitor, NULL as monitor_secundario, NULL as tipo_almacenamiento, NULL as capacidad, NULL as ram, NULL as mac_net, NULL as mac_wifi, NULL as nodo, NULL as puerto_router, NULL as extension, NULL as proyecto, t.fecha_instalacion, t.fecha_retiro, t.observaciones,
           NULL as uso, NULL as num_puertos
    FROM impresoras t
";

$query_televisiones = "
    SELECT 'televisiones' as categoria, t.id, t.serie, NULL as tipo, t.fabricante, t.modelo, NULL as ip, t.id_unidad, NULL as id_usuario, 
           COALESCE(t.area, '') as area, COALESCE(t.departamento, '') as departamento, t.estatus,
           NULL as nombre_equipo, NULL as monitor, NULL as monitor_secundario, NULL as tipo_almacenamiento, NULL as capacidad, NULL as ram, NULL as mac_net, NULL as mac_wifi, NULL as nodo, NULL as puerto_router, NULL as extension, NULL as proyecto, t.fecha_instalacion, t.fecha_retiro, t.observaciones,
           t.uso, NULL as num_puertos
    FROM televisiones t
";

$query_telefonos = "
    SELECT 'telefonia' as categoria, t.id, t.serie, t.tipo, t.fabricante, t.modelo, t.ip, t.id_unidad, NULL as id_usuario, 
           COALESCE(t.area, '') as area, COALESCE(t.departamento, '') as departamento, t.estatus,
           t.nombre as nombre_equipo, NULL as monitor, NULL as monitor_secundario, NULL as tipo_almacenamiento, NULL as capacidad, NULL as ram, NULL as mac_net, NULL as mac_wifi, t.nodo, t.p_router as puerto_router, t.extension, NULL as proyecto, t.fecha_instalacion, t.fecha_retiro, t.observaciones,
           NULL as uso, NULL as num_puertos
    FROM telefonos t
";

$query_redes = "
    SELECT 'redes' as categoria, t.id, t.serie, t.tipo, t.fabricante, t.modelo, t.ip_gestion as ip, t.id_unidad, NULL as id_usuario, t.area, NULL as departamento, t.estatus,
           NULL as nombre_equipo, NULL as monitor, NULL as monitor_secundario, NULL as tipo_almacenamiento, NULL as capacidad, NULL as ram, t.mac as mac_net, NULL as mac_wifi, t.nodo_uplink as nodo, NULL as puerto_router, NULL as extension, NULL as proyecto, NULL as fecha_instalacion, NULL as fecha_retiro, t.observaciones,
           NULL as uso, t.num_puertos
    FROM redes t
";

$query_consumibles = "
    SELECT 'consumibles' as categoria, t.id, NULL as serie, t.tipo, NULL as fabricante, NULL as modelo, NULL as ip, t.id_unidad, NULL as id_usuario, NULL as area, NULL as departamento, t.estatus,
           NULL as nombre_equipo, NULL as monitor, NULL as monitor_secundario, t.categoria as tipo_almacenamiento, t.longitud as capacidad, t.cantidad_stock as ram, NULL as mac_net, NULL as mac_wifi, NULL as nodo, NULL as puerto_router, NULL as extension, NULL as proyecto, NULL as fecha_instalacion, NULL as fecha_retiro, t.observaciones,
           NULL as uso, NULL as num_puertos
    FROM consumibles t
";

$query_full = "
    SELECT t.*, u.unidad, e.nombre as usuario_asignado, e.matricula, e.usuario as cuenta, e.categoria as categoria_usuario
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
        UNION ALL
        ($query_consumibles)
    ) as t
    LEFT JOIN unidades u ON t.id_unidad = u.id
    LEFT JOIN empleados e ON t.id_usuario = e.id
";

try {
    $stmt = $db->prepare($query_full);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($resultados);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
