<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error al subir el archivo']);
    exit;
}

$matricula_post = $_POST['matricula'] ?? '';
if (empty($matricula_post)) {
    echo json_encode(['success' => false, 'message' => 'Falta la matrícula de autorización.']);
    exit;
}

$fileTmpPath = $_FILES['file']['tmp_name'];

try {
    $spreadsheet = IOFactory::load($fileTmpPath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);
    // Rows are indexed by 1-based integers, columns by A, B, C, etc.

    $db = new Database();
    $conn = $db->getConnection();

    // Validar empleado
    $stmtEmp = $conn->prepare("SELECT nombre FROM empleados WHERE matricula = :matricula");
    $stmtEmp->execute([':matricula' => $matricula_post]);
    $empleado = $stmtEmp->fetch();

    if (!$empleado) {
        echo json_encode(['success' => false, 'message' => 'Matrícula no encontrada en el sistema.']);
        exit;
    }
    
    $matricula = $matricula_post;
    $nombre = $empleado['nombre'];

    $inserted = 0;
    $firstRow = true;
    $lastFecha = null;

    $stmt = $conn->prepare("INSERT INTO informes (fecha, area, acciones, hora_inicio, duracion, matricula_empleado, nombre_empleado)
                            VALUES (:fecha, :area, :acciones, :hora_inicio, :duracion, :matricula, :nombre)");

    foreach ($rows as $rowIndex => $row) {
        if ($firstRow) {
            $firstRow = false;
            continue; // Skip header
        }

        $fechaRaw = trim($row['A'] ?? '');
        $area = trim($row['B'] ?? '');
        $acciones = trim($row['C'] ?? '');
        $horaInicioRaw = trim($row['D'] ?? '');
        $duracion = trim($row['E'] ?? '');

        if (empty($fechaRaw) && empty($area) && empty($acciones)) {
            continue; // Skip completely empty rows
        }

        // Process Date (Column A)
        if (!empty($fechaRaw)) {
            if (is_numeric($fechaRaw)) {
                $dateTime = Date::excelToDateTimeObject($fechaRaw);
                $fecha = $dateTime->format('Y-m-d');
            } else {
                // If it's DD/MM/YYYY
                if (strpos($fechaRaw, '/') !== false) {
                    $dt = DateTime::createFromFormat('d/m/Y', $fechaRaw);
                    if ($dt !== false) {
                        $fecha = $dt->format('Y-m-d');
                    } else {
                        $fecha = date('Y-m-d', strtotime(str_replace('/', '-', $fechaRaw)));
                    }
                } else {
                    $fecha = date('Y-m-d', strtotime($fechaRaw));
                }
            }
            $lastFecha = $fecha;
        } else {
            $fecha = $lastFecha; // Carry over previous date
        }

        if (empty($area) && empty($acciones)) {
            continue; // If only date is present but no action, skip
        }

        // Process Time (Column D)
        if (is_numeric($horaInicioRaw)) {
            $timeObj = Date::excelToDateTimeObject($horaInicioRaw);
            $hora_inicio = $timeObj->format('H:i');
        } else {
            $hora_inicio = date('H:i', strtotime($horaInicioRaw));
        }

        // Matricula and nombre are already set from outside the loop

        $stmt->execute([
            ':fecha' => $fecha,
            ':area' => $area,
            ':acciones' => $acciones,
            ':hora_inicio' => $hora_inicio,
            ':duracion' => (int)$duracion,
            ':matricula' => $matricula,
            ':nombre' => $nombre
        ]);
        $inserted++;
    }

    echo json_encode(['success' => true, 'inserted' => $inserted]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
