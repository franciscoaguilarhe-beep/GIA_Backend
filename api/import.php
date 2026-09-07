<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$database = new Database();
$db = $database->getConnection();

$cat = isset($_POST['entity_cat']) ? $_POST['entity_cat'] : null;

if (!$cat || !isset($_FILES['fileExcel']) || $_FILES['fileExcel']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Datos de importación o archivo inválidos']);
    exit;
}

$table = '';
switch($cat) {
    case 'computo': $table = 'equipos_computo'; break;
    case 'impresoras': $table = 'impresoras'; break;
    case 'televisiones': $table = 'televisiones'; break;
    case 'telefonia': $table = 'telefonos'; break;
    case 'redes': $table = 'redes'; break;
    case 'consumibles': $table = 'consumibles'; break;
    case 'unidades': $table = 'unidades'; break;
    case 'empleados': $table = 'empleados'; break;
    default: 
        echo json_encode(['error' => 'Categoría inválida']);
        exit;
}

$file = $_FILES['fileExcel']['tmp_name'];

try {
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    
    if (count($rows) <= 1) {
        echo json_encode(['error' => 'El archivo está vacío o solo contiene encabezados.']);
        exit;
    }

    $headers = array_shift($rows);
    $uploadedHeaders = array_map(function($h) { return strtolower(trim((string)$h)); }, $headers);

    $success_count = 0;
    $error_count = 0;

    function parseExcelDate($val) {
        if (!$val) return null;
        if (is_numeric($val)) {
            $unixDate = ($val - 25569) * 86400;
            return gmdate("Y-m-d", $unixDate);
        }
        $val = str_replace('/', '-', $val);
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $val, $matches)) {
            return $matches[3] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        }
        $time = strtotime($val);
        if ($time) {
            return date('Y-m-d', $time);
        }
        return null;
    }

    foreach ($rows as $row) {
        $data = array_combine($uploadedHeaders, $row);
        if (!$data || empty(array_filter($data))) continue;

        // UPSERT logic: check if 'serie', 'clave', or 'matricula' exists
        $serie = isset($data['serie']) ? trim((string)$data['serie']) : null;
        $clave = isset($data['clave']) ? trim((string)$data['clave']) : null;
        $matricula = isset($data['matricula']) ? trim((string)$data['matricula']) : null;
        $extension = isset($data['extension']) ? trim((string)$data['extension']) : null;
        $exists = false;
        
        if ($serie && $table !== 'unidades' && $table !== 'empleados') {
            $check_q = "SELECT id FROM $table WHERE serie = :serie";
            $check_stmt = $db->prepare($check_q);
            $check_stmt->bindParam(':serie', $serie);
            $check_stmt->execute();
            $exists = $check_stmt->fetch();
        } 
        
        if (!$exists && $extension && $table === 'telefonos') {
            $check_q = "SELECT id FROM $table WHERE extension = :extension";
            $check_stmt = $db->prepare($check_q);
            $check_stmt->bindParam(':extension', $extension);
            $check_stmt->execute();
            $exists = $check_stmt->fetch();
        }
        
        if (!$exists && $clave && $table === 'unidades') {
            $check_q = "SELECT id FROM $table WHERE clave = :clave";
            $check_stmt = $db->prepare($check_q);
            $check_stmt->bindParam(':clave', $clave);
            $check_stmt->execute();
            $exists = $check_stmt->fetch();
        } elseif (!$exists && $matricula && $table === 'empleados') {
            $check_q = "SELECT id FROM $table WHERE matricula = :matricula";
            $check_stmt = $db->prepare($check_q);
            $check_stmt->bindParam(':matricula', $matricula);
            $check_stmt->execute();
            $exists = $check_stmt->fetch();
        }

        // Build query
        $fields = [];
        $params = [];
        
        // Map foreign keys
        if (array_key_exists('unidad', $data) && $table !== 'unidades') {
            $u_val = trim((string)$data['unidad']);
            if ($u_val) {
                $u_stmt = $db->prepare("SELECT id FROM unidades WHERE unidad = :u OR clave = :u LIMIT 1");
                $u_stmt->execute([':u' => $u_val]);
                $u_res = $u_stmt->fetch();
                $data['id_unidad'] = $u_res ? $u_res['id'] : null;
            } else {
                $data['id_unidad'] = null;
            }
            unset($data['unidad']);
        }
        
        if (array_key_exists('usuario', $data) && $table !== 'empleados') {
            $e_val = trim((string)$data['usuario']);
            if ($e_val && $e_val !== 'N/A') {
                // First try to match by matricula (as requested)
                $e_stmt = $db->prepare("SELECT id, usuario FROM empleados WHERE matricula = :n LIMIT 1");
                $e_stmt->execute([':n' => $e_val]);
                $e_res = $e_stmt->fetch();
                
                // If not found by matricula, try name or account
                if (!$e_res) {
                    $e_stmt = $db->prepare("SELECT id, usuario FROM empleados WHERE nombre = :n OR usuario = :n LIMIT 1");
                    $e_stmt->execute([':n' => $e_val]);
                    $e_res = $e_stmt->fetch();
                }
                
                if ($e_res) {
                    $data['id_usuario'] = $e_res['id'];
                    // Si el empleado tiene usuario, este llena el campo cuenta_dominio
                    if (!empty($e_res['usuario'])) {
                        $data['cuenta_dominio'] = $e_res['usuario'];
                    }
                } else {
                    $data['id_usuario'] = null;
                }
            } else {
                $data['id_usuario'] = null;
            }
            unset($data['usuario']);
        }

        // Parse dates
        if (array_key_exists('fecha_instalacion', $data)) {
            $data['fecha_instalacion'] = parseExcelDate($data['fecha_instalacion']);
        }
        if (array_key_exists('fecha_retiro', $data)) {
            $data['fecha_retiro'] = parseExcelDate($data['fecha_retiro']);
        }

        // Get valid columns for this table
        static $valid_columns = null;
        if ($valid_columns === null) {
            $s_cols = $db->query("DESCRIBE $table");
            $valid_columns = $s_cols->fetchAll(PDO::FETCH_COLUMN);
        }

        $final_data = [];
        foreach ($data as $key => $value) {
            $key = trim((string)$key);
            if ($key === '' || !in_array($key, $valid_columns) || $key === 'id') continue;
            
            if ($value === '' || $value === null) $value = null;
            $final_data[$key] = $value;
        }

        // Audit fields
        if ($table !== 'unidades' && $table !== 'empleados') {
            $final_data['modificado_por'] = $_SESSION['user_id'] ?? 1;
        }

        $fields = [];
        $params = [];
        foreach ($final_data as $key => $value) {
            $fields[] = "`$key` = :$key";
            $params[":$key"] = $value;
        }

        try {
            if ($exists) {
                $query = "UPDATE $table SET " . implode(", ", $fields) . " WHERE id = :id";
                $params[':id'] = $exists['id'];
            } else {
                // Re-map for INSERT
                $insert_fields = array_keys($final_data);
                $insert_placeholders = array_map(function($k) { return ":$k"; }, $insert_fields);
                $query = "INSERT INTO $table (`" . implode("`, `", $insert_fields) . "`) VALUES (" . implode(", ", $insert_placeholders) . ")";
            }

            $stmt = $db->prepare($query);
            if ($stmt->execute($params)) {
                $success_count++;
            } else {
                $error_count++;
            }
        } catch (PDOException $e) {
            $error_count++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Importación completada. Éxitos: $success_count, Errores: $error_count"
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Error al procesar archivo: ' . $e->getMessage()]);
}
?>
