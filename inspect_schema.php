<?php
include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();
$tables = ['equipos_computo', 'impresoras', 'televisiones', 'telefonos', 'redes'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $stmt = $db->query("DESCRIBE $table");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . "\n";
    }
}
?>
