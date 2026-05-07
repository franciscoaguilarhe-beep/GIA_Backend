<?php
include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Update all computers to sync cuenta_dominio with their assigned employee's usuario
$query = "UPDATE equipos_computo ec 
          JOIN empleados e ON ec.id_usuario = e.id 
          SET ec.cuenta_dominio = e.usuario 
          WHERE e.usuario IS NOT NULL AND e.usuario != ''";

try {
    $stmt = $db->prepare($query);
    $stmt->execute();
    echo "Updated " . $stmt->rowCount() . " computers with domain accounts from employees.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
