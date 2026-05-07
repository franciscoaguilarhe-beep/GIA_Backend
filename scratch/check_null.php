<?php
include_once 'config/database.php';
$db = (new Database())->getConnection();
$s = $db->query("DESCRIBE equipos_computo");
$cols = $s->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $c) {
    echo "{$c['Field']}: Null={$c['Null']}\n";
}
