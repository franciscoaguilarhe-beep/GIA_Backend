<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;

$db = new Database();
$conn = $db->getConnection();

$where = [];
$params = [];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$nombre_usuario = $_SESSION['nombre'] ?? '';

if (!empty($_GET['start']) && !empty($_GET['end'])) {
    $where[] = "fecha BETWEEN :start AND :end";
    $params[':start'] = $_GET['start'];
    $params[':end'] = $_GET['end'];
}

if (!empty($_GET['area'])) {
    $where[] = "(area LIKE :area OR acciones LIKE :area)";
    $params[':area'] = '%' . $_GET['area'] . '%';
}

if (!empty($nombre_usuario)) {
    $where[] = "nombre_empleado = :nombre_emp";
    $params[':nombre_emp'] = $nombre_usuario;
}

$sql = "SELECT fecha, area, acciones, hora_inicio, duracion FROM informes";
if (count($where) > 0) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY fecha DESC, hora_inicio DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$informes = $stmt->fetchAll();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header
$sheet->setCellValue('A1', 'FECHA');
$sheet->setCellValue('B1', 'ÁREA');
$sheet->setCellValue('C1', 'ACCIONES');
$sheet->setCellValue('D1', 'HORA INICIO');
$sheet->setCellValue('E1', 'DURACIÓN');

$row = 2;
foreach ($informes as $item) {
    // Format date for Excel
    $dateTime = new DateTime($item['fecha']);
    $excelDate = Date::PHPToExcel($dateTime);
    $sheet->setCellValue('A' . $row, $excelDate);
    $sheet->getStyle('A' . $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);

    $sheet->setCellValue('B' . $row, $item['area']);
    $sheet->setCellValue('C' . $row, $item['acciones']);

    // Format time for Excel
    $timeParts = explode(':', $item['hora_inicio']);
    if (count($timeParts) >= 2) {
        $hours = (int)$timeParts[0];
        $minutes = (int)$timeParts[1];
        $fraction = ($hours / 24) + ($minutes / (24 * 60));
        $sheet->setCellValue('D' . $row, $fraction);
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_TIME3);
    } else {
        $sheet->setCellValue('D' . $row, $item['hora_inicio']);
    }

    $sheet->setCellValue('E' . $row, $item['duracion']);

    $row++;
}

// Autofit columns
foreach (range('A', 'E') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$dateStr = date('Y-m-d');
$filename = "info_sem_con_folio_francisco.aguilarh_{$dateStr}.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
