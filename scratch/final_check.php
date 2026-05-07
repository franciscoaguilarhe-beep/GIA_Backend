<?php
include_once 'config/database.php';
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$database = new Database();
$db = $database->getConnection();

$cat = 'computo';
$table = 'equipos_computo';
$file = 'SUBIR ACTIVOS.xlsx';

$s = $db->query("DESCRIBE $table");
$valid_columns = $s->fetchAll(PDO::FETCH_COLUMN);

try {
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    $headers = array_shift($rows);
    $uploadedHeaders = array_map(function($h) { return trim((string)$h); }, $headers);

    $success = 0; $errors = 0;
    foreach ($rows as $row) {
        if (empty(array_filter($row))) continue;
        $data = array_combine($uploadedHeaders, $row);
        $serie = trim($data['serie']);
        
        // Use the actual import logic
        $final_data = [];
        // Map unit
        if (!empty($data['unidad'])) {
            $u_stmt = $db->prepare("SELECT id FROM unidades WHERE unidad = :u OR clave = :u LIMIT 1");
            $u_stmt->execute([':u' => $data['unidad']]);
            $u_res = $u_stmt->fetch();
            $final_data['id_unidad'] = $u_res ? $u_res['id'] : null;
        }
        
        // Map user and sync cuenta_dominio
        if (!empty($data['usuario'])) {
            $e_stmt = $db->prepare("SELECT id, usuario FROM empleados WHERE matricula = :n OR nombre = :n OR usuario = :n LIMIT 1");
            $e_stmt->execute([':n' => $data['usuario']]);
            $e_res = $e_stmt->fetch();
            if ($e_res) {
                $final_data['id_usuario'] = $e_res['id'];
                if (!empty($e_res['usuario'])) $final_data['cuenta_dominio'] = $e_res['usuario'];
            }
        }
        
        foreach ($data as $k => $v) {
            if (in_array($k, $valid_columns) && $k !== 'id') {
                if (!isset($final_data[$k]) || $final_data[$k] === null) $final_data[$k] = $v;
            }
        }
        
        $check = $db->prepare("SELECT id FROM $table WHERE serie = :s");
        $check->execute([':s' => $serie]);
        $exists = $check->fetch();
        
        $fields = []; $params = [];
        foreach ($final_data as $k => $v) {
            if ($v === '') $v = null;
            $fields[] = "`$k` = :$k";
            $params[":$k"] = $v;
        }
        
        if ($exists) {
            $q = "UPDATE $table SET " . implode(", ", $fields) . " WHERE id = :id";
            $params[':id'] = $exists['id'];
        } else {
            $cols = array_keys($final_data);
            $phs = array_map(function($k) { return ":$k"; }, $cols);
            $q = "INSERT INTO $table (`" . implode("`, `", $cols) . "`) VALUES (" . implode(", ", $phs) . ")";
        }
        
        if ($db->prepare($q)->execute($params)) $success++; else $errors++;
    }
    echo "Import: $success success, $errors errors\n";
} catch (Exception $e) { echo $e->getMessage(); }
