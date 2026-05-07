<?php
include_once 'config/database.php';
$db = (new Database())->getConnection();
$db->exec("ALTER TABLE equipos_computo MODIFY cuenta_dominio VARCHAR(30) NULL");
echo "Column cuenta_dominio updated to allow NULL.\n";
