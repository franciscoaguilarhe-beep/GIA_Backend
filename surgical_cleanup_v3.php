<?php
$files = [
    'c:/xampp/htdocs/gia/js/app.js',
    'c:/xampp/htdocs/gia/api/get_activo.php',
    'c:/xampp/htdocs/gia/api/save_activo.php',
    'c:/xampp/htdocs/gia/inventario.php',
    'c:/xampp/htdocs/gia/login.php',
    'c:/xampp/htdocs/gia/views/layout/header.php',
    'c:/xampp/htdocs/gia/index.php'
];

$replacements = [
    // Common Mojibake
    "Ã Área" => "Área",
    "Ã ÁREA" => "ÁREA",
    "Ã¡rea" => "área",
    "aÃ¡rea" => "área",
    "Ã©" => "é",
    "Ã³" => "ó",
    "Ã­" => "í",
    "Ã±" => "ñ",
    "Ã¡" => "á",
    "Ãº" => "ú",
    "Ã " => "Í",
    "Â¿" => "¿",
    "Ã‰" => "É",
    "TÃ©cnica" => "Técnica",
    "GestiÃ³n" => "Gestión",
    "CÃ³mputo" => "Cómputo",
    "InformaciÃ³n" => "Información",
    "CategorÃ­a" => "Categoría",
    "CategorÃAa" => "Categoría",
    "invÃ¡lida" => "inválida",
    "ContraseÃ±a" => "Contraseña"
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    // JS specific robustness
    if (strpos($file, 'app.js') !== false) {
        // Fix forEach just in case
        $content = str_replace('.foreach', '.forEach', $content);
        
        // Add error handling to openFichaTecnica
        $target = "fetch(`api/get_activo.php?id=\${id}&cat=\${categoria}`)\r\n        .then(res => res.json())\r\n        .then(data => {\r\n            renderModalContent(data, categoria, modalBody);\r\n        });";
        $replacement = "fetch(`api/get_activo.php?id=\${id}&cat=\${categoria}`)\r\n        .then(res => {\r\n            if(!res.ok) throw new Error('Error en el servidor');\r\n            return res.json();\r\n        })\r\n        .then(data => {\r\n            console.log('Datos recibidos:', data);\r\n            renderModalContent(data, categoria, modalBody);\r\n        })\r\n        .catch(err => {\r\n            console.error('Error cargando ficha:', err);\r\n            modalBody.innerHTML = `<div style=\"color:red; text-align:center; padding:20px;\"><i class=\"ph ph-warning\"></i> Error al cargar datos: \${err.message}</div>`;\r\n        });";
        
        // Normalize line endings for replacement
        $content = str_replace("\r\n", "\n", $content);
        $target = str_replace("\r\n", "\n", $target);
        $replacement = str_replace("\r\n", "\n", $replacement);
        
        $content = str_replace($target, $replacement, $content);
    }
    
    file_put_contents($file, $content);
    echo "Surgically cleaned: $file\n";
}
?>
