<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$database = new Database();
$db = $database->getConnection();

$cat = isset($_GET['cat']) ? $_GET['cat'] : 'computo';
$id_unidad_filtro = isset($_GET['id_unidad']) ? intval($_GET['id_unidad']) : (isset($_SESSION['id_unidad']) ? $_SESSION['id_unidad'] : null);

$headers = [];
$query = "";

switch($cat) {
    case 'computo': 
        $headers = ['serie', 'tipo', 'nombre_equipo', 'fabricante', 'modelo', 'monitor', 'tipo_alm', 'capacidad', 'ram', 'ip', 'mac_net', 'mac_wifi', 'nodo', 'p_router', 'usuario', 'cuenta', 'unidad', 'area', 'departamento', 'extension', 'proyecto', 'fecha_instalacion', 'fecha_retiro', 'estatus', 'observaciones'];
        $query = "SELECT t.serie, t.tipo, t.nombre_equipo, t.fabricante, t.modelo, t.monitor, t.tipo_alm, t.capacidad, t.ram, t.ip, t.mac_net, t.mac_wifi, t.nodo, t.p_router, 
                  e.nombre as usuario,
                  COALESCE(NULLIF(e.usuario, ''), t.cuenta_dominio) as cuenta,
                  u.unidad as unidad, t.area, t.departamento, t.extension, t.proyecto, t.fecha_instalacion, t.fecha_retiro, t.estatus, t.observaciones 
                  FROM equipos_computo t 
                  LEFT JOIN unidades u ON t.id_unidad = u.id 
                  LEFT JOIN empleados e ON t.id_usuario = e.id";
        if ($id_unidad_filtro) $query .= " WHERE t.id_unidad = :id_u";
        break;
        
    case 'impresoras': 
        $headers = ['tipo', 'serie', 'fabricante', 'modelo', 'unidad', 'area', 'departamento', 'ip', 'fecha_instalacion', 'fecha_retiro', 'estatus', 'observaciones'];
        $query = "SELECT t.tipo, t.serie, t.fabricante, t.modelo, u.unidad as unidad, t.area, t.departamento, t.ip, t.fecha_instalacion, t.fecha_retiro, t.estatus, t.observaciones 
                  FROM impresoras t 
                  LEFT JOIN unidades u ON t.id_unidad = u.id";
        if ($id_unidad_filtro) $query .= " WHERE t.id_unidad = :id_u";
        break;
        
    case 'televisiones': 
        $headers = ['serie', 'fabricante', 'modelo', 'unidad', 'area', 'departamento', 'uso', 'fecha_instalacion', 'fecha_retiro', 'estatus', 'observaciones'];
        $query = "SELECT t.serie, t.fabricante, t.modelo, u.unidad as unidad, t.area, t.departamento, t.uso, t.fecha_instalacion, t.fecha_retiro, t.estatus, t.observaciones 
                  FROM televisiones t 
                  LEFT JOIN unidades u ON t.id_unidad = u.id";
        if ($id_unidad_filtro) $query .= " WHERE t.id_unidad = :id_u";
        break;
        
    case 'telefonia': 
        $headers = ['serie', 'fabricante', 'modelo', 'tipo', 'ip', 'nombre', 'extension', 'nodo', 'p_router', 'unidad', 'area', 'departamento', 'fecha_instalacion', 'fecha_retiro', 'estatus', 'observaciones'];
        $query = "SELECT t.serie, t.fabricante, t.modelo, t.tipo, t.ip, t.nombre, t.extension, t.nodo, t.p_router, u.unidad as unidad, t.area, t.departamento, t.fecha_instalacion, t.fecha_retiro, t.estatus, t.observaciones 
                  FROM telefonos t 
                  LEFT JOIN unidades u ON t.id_unidad = u.id";
        if ($id_unidad_filtro) $query .= " WHERE t.id_unidad = :id_u";
        break;
        
    case 'redes': 
        $headers = ['tipo', 'serie', 'fabricante', 'modelo', 'num_puertos', 'ip_gestion', 'mac', 'nodo_uplink', 'unidad', 'area', 'estatus', 'observaciones'];
        $query = "SELECT t.tipo, t.serie, t.fabricante, t.modelo, t.num_puertos, t.ip_gestion, t.mac, t.nodo_uplink, u.unidad as unidad, t.area, t.estatus, t.observaciones 
                  FROM redes t 
                  LEFT JOIN unidades u ON t.id_unidad = u.id";
        if ($id_unidad_filtro) $query .= " WHERE t.id_unidad = :id_u";
        break;
        
    case 'consumibles': 
        $headers = ['tipo', 'categoria', 'longitud', 'cantidad_stock', 'unidad', 'estatus', 'observaciones'];
        $query = "SELECT t.tipo, t.categoria, t.longitud, t.cantidad_stock, u.unidad as unidad, t.estatus, t.observaciones 
                  FROM consumibles t 
                  LEFT JOIN unidades u ON t.id_unidad = u.id";
        if ($id_unidad_filtro) $query .= " WHERE t.id_unidad = :id_u";
        break;
        
    case 'unidades': 
        $headers = ['clave', 'unidad', 'zona'];
        $query = "SELECT clave, unidad, zona FROM unidades";
        break;
        
    case 'empleados': 
        $headers = ['matricula', 'nombre', 'usuario', 'password', 'categoria', 'unidad'];
        $query = "SELECT t.matricula, t.nombre, t.usuario, '' as password, t.categoria, u.unidad as unidad 
                  FROM empleados t 
                  LEFT JOIN unidades u ON t.id_unidad = u.id";
        if ($id_unidad_filtro) $query .= " WHERE t.id_unidad = :id_u";
        break;
        
    default:
        die("Categoría no válida");
}

$stmt = $db->prepare($query);
if ($id_unidad_filtro && $cat !== 'unidades') {
    $stmt->bindParam(':id_u', $id_unidad_filtro);
}
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($results) >= 0) {
    // Clear any previous output
    if (ob_get_length()) ob_end_clean();
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Header row
    $colNum = 1;
    foreach ($headers as $header) {
        $colString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colNum);
        $sheet->setCellValue($colString . '1', $header);
        $sheet->getColumnDimension($colString)->setAutoSize(true);
        $colNum++;
    }
    
    // Style header
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF006847']]
    ];
    $lastColString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
    $sheet->getStyle('A1:' . $lastColString . '1')->applyFromArray($headerStyle);
    
    // Data rows
    $rowNum = 2;
    foreach ($results as $row) {
        $colNum = 1;
        foreach ($headers as $header) {
            $value = isset($row[$header]) ? $row[$header] : '';
            
            // Format dates for Excel compatibility
            if (($header === 'fecha_instalacion' || $header === 'fecha_retiro') && $value) {
                $value = date('d/m/Y', strtotime($value));
            }
            
            $colString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colNum);
            $sheet->setCellValue($colString . $rowNum, $value);
            $colNum++;
        }
        $rowNum++;
    }
    
    $filename = "export_{$cat}_" . date('Y-m-d') . ".xlsx";
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} else {
    echo "No hay datos para exportar.";
}
