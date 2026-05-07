<?php
$files = [
    'c:/xampp/htdocs/gia/index.php',
    'c:/xampp/htdocs/gia/inventario.php',
    'c:/xampp/htdocs/gia/gestion_administrativa.php',
    'c:/xampp/htdocs/gia/js/app.js'
];

$replacements = [
    "Ã Área" => "Área",
    "Ã ÁREA" => "ÁREA",
    "tÃ©rmino" => "término",
    "bÃºsqueda" => "búsqueda",
    "CATEGORÃ A" => "CATEGORÍA",
    "MATRÃ CULA" => "MATRÍCULA",
    "aÁárea" => "area",
    "áÁárea" => "area",
    "Ã­" => "í",
    "Ã©" => "é",
    "Ã³" => "ó",
    "Ã±" => "ñ",
    "Ã¡" => "á"
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    file_put_contents($file, $content);
    echo "Polished: $file\n";
}
?>
