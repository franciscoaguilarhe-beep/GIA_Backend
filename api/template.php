<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    die("No autorizado");
}

require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$cat = isset($_GET['cat']) ? $_GET['cat'] : 'computo';

$headers = [];
switch($cat) {
    case 'computo': $headers = ['serie', 'tipo', 'nombre_equipo', 'fabricante', 'modelo', 'monitor', 'tipo_alm', 'capacidad', 'ram', 'ip', 'mac_net', 'mac_wifi', 'nodo', 'p_router', 'usuario', 'cuenta_dominio', 'unidad', 'area', 'departamento', 'extension', 'proyecto', 'fecha_instalacion', 'fecha_retiro', 'estatus', 'observaciones']; break;
    case 'impresoras': $headers = ['tipo', 'serie', 'fabricante', 'modelo', 'unidad', 'area', 'departamento', 'ip', 'fecha_instalacion', 'fecha_retiro', 'estatus', 'observaciones']; break;
    case 'televisiones': $headers = ['serie', 'fabricante', 'modelo', 'unidad', 'area', 'departamento', 'uso', 'fecha_instalacion', 'fecha_retiro', 'estatus', 'observaciones']; break;
    case 'telefonia': $headers = ['serie', 'fabricante', 'modelo', 'tipo', 'ip', 'nombre', 'extension', 'nodo', 'p_router', 'unidad', 'area', 'departamento', 'fecha_instalacion', 'fecha_retiro', 'estatus', 'observaciones']; break;
    case 'redes': $headers = ['tipo', 'serie', 'fabricante', 'modelo', 'num_puertos', 'ip_gestion', 'mac', 'nodo_uplink', 'unidad', 'area', 'estatus', 'observaciones']; break;
    case 'consumibles': $headers = ['tipo', 'categoria', 'longitud', 'cantidad_stock', 'unidad', 'estatus', 'observaciones']; break;
    case 'unidades': $headers = ['clave', 'unidad', 'zona']; break;
    case 'empleados': $headers = ['matricula', 'nombre', 'usuario', 'password', 'categoria', 'unidad']; break;
    default: $headers = ['serie', 'tipo', 'fabricante', 'modelo'];
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $col++;
}

// Style header
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF006847']]
];
$lastCol = chr(ord('A') + count($headers) - 1);
$sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($headerStyle);

$filename = "plantilla_" . $cat . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

    // Clear any previous output
    if (ob_get_length()) ob_end_clean();

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
