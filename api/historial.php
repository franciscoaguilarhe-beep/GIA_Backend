<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : 'get_all';

if ($action === 'get_by_activo') {
    $cat = isset($_GET['cat']) ? trim($_GET['cat']) : '';
    $id_activo = isset($_GET['id_activo']) ? intval($_GET['id_activo']) : 0;

    if (empty($cat) || $id_activo <= 0) {
        echo json_encode([]);
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM historial WHERE categoria = :cat AND id_activo = :id ORDER BY fecha DESC, id DESC");
        $stmt->execute([
            ':cat' => $cat,
            ':id' => $id_activo
        ]);
        $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($historial);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_all') {
    $cat = isset($_GET['cat']) ? trim($_GET['cat']) : '';
    $accion = isset($_GET['accion']) ? trim($_GET['accion']) : '';
    $matricula = isset($_GET['matricula']) ? trim($_GET['matricula']) : '';
    $fecha_desde = isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '';
    $fecha_hasta = isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : '';

    $where = [];
    $params = [];

    if (!empty($cat)) {
        $where[] = "categoria = :cat";
        $params[':cat'] = $cat;
    }
    if (!empty($accion)) {
        $where[] = "accion = :accion";
        $params[':accion'] = $accion;
    }
    if (!empty($matricula)) {
        $where[] = "matricula_responsable = :mat";
        $params[':mat'] = $matricula;
    }
    if (!empty($fecha_desde)) {
        $where[] = "fecha >= :f_desde";
        $params[':f_desde'] = $fecha_desde . " 00:00:00";
    }
    if (!empty($fecha_hasta)) {
        $where[] = "fecha <= :f_hasta";
        $params[':f_hasta'] = $fecha_hasta . " 23:59:59";
    }

    $sql = "SELECT * FROM historial";
    if (count($where) > 0) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY fecha DESC, id DESC LIMIT 500";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($records);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'export') {
    require_once '../vendor/autoload.php';

    $fecha_desde = isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '';
    $fecha_hasta = isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : '';
    $accion = isset($_GET['accion']) ? trim($_GET['accion']) : '';
    $cat = isset($_GET['cat']) ? trim($_GET['cat']) : '';

    $where = [];
    $params = [];

    if (!empty($cat)) {
        $where[] = "categoria = :cat";
        $params[':cat'] = $cat;
    }
    if (!empty($accion)) {
        $where[] = "accion = :accion";
        $params[':accion'] = $accion;
    }
    if (!empty($fecha_desde)) {
        $where[] = "fecha >= :f_desde";
        $params[':f_desde'] = $fecha_desde . " 00:00:00";
    }
    if (!empty($fecha_hasta)) {
        $where[] = "fecha <= :f_hasta";
        $params[':f_hasta'] = $fecha_hasta . " 23:59:59";
    }

    $sql = "SELECT * FROM historial";
    if (count($where) > 0) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY fecha DESC, id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Historial_Auditoria');

    $headers = ['ID', 'FECHA Y HORA', 'ACCIÓN', 'CATEGORÍA', 'IDENTIFICADOR / SERIE', 'DETALLES DEL MOVIMIENTO', 'MATRÍCULA RESPONSABLE', 'NOMBRE RESPONSABLE'];
    
    $col = 'A';
    foreach ($headers as $h) {
        $sheet->setCellValue($col . '1', $h);
        $sheet->getColumnDimension($col)->setAutoSize(true);
        $col++;
    }

    // Header styling
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF13322B']]
    ];
    $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

    $rowNum = 2;
    foreach ($records as $r) {
        $sheet->setCellValue('A' . $rowNum, $r['id']);
        $sheet->setCellValue('B' . $rowNum, $r['fecha']);
        $sheet->setCellValue('C' . $rowNum, $r['accion']);
        $sheet->setCellValue('D' . $rowNum, strtoupper($r['categoria']));
        $sheet->setCellValue('E' . $rowNum, $r['identificador']);
        $sheet->setCellValue('F' . $rowNum, $r['detalles']);
        $sheet->setCellValue('G' . $rowNum, $r['matricula_responsable']);
        $sheet->setCellValue('H' . $rowNum, $r['nombre_responsable']);
        $rowNum++;
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="auditoria_historial_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');

    if (ob_get_length()) ob_end_clean();
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Acción inválida']);
?>
