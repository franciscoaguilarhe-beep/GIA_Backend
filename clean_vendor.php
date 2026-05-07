<?php
function cleanBOM($dir) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $path = $file->getRealPath();
            $content = file_get_contents($path);
            
            // Remove UTF-8 BOM
            $bom = pack('H*','EFBBBF');
            $newContent = preg_replace("/^$bom/", '', $content);
            
            // Ensure no whitespace before <?php
            $newContent = ltrim($newContent);
            
            if ($newContent !== $content) {
                file_put_contents($path, $newContent);
                echo "Cleaned: $path\n";
            }
        }
    }
}

cleanBOM('c:/xampp/htdocs/gia/vendor');
cleanBOM('c:/xampp/htdocs/gia/api');
?>
