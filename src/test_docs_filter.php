<?php
// Simular la lógica de docs.php para verificar el filtrado
$hidden_docs = [];
if (file_exists('.hidden_docs.json')) {
    $hidden_docs = json_decode(file_get_contents('.hidden_docs.json'), true) ?? [];
}

$md_files = glob('docs/md/*.md');
$visible_files = array_filter($md_files, function($file) use ($hidden_docs) {
    $filename = basename($file);
    return !in_array($filename, $hidden_docs);
});

echo "Archivos MD totales: " . count($md_files) . PHP_EOL;
echo "Archivos ocultos: " . count($hidden_docs) . PHP_EOL;
echo "Archivos visibles: " . count($visible_files) . PHP_EOL;
echo "Archivos visibles:" . PHP_EOL;
foreach ($visible_files as $file) {
    echo "  - " . basename($file) . PHP_EOL;
}
?>