<?php
/**
 * Archivo de diagnóstico para encontrar wp-config.php
 */

echo "<h1>Diagnóstico de Rutas</h1>";

// Mostrar información del directorio actual
echo "<h2>Directorio Actual</h2>";
echo "<p><strong>__DIR__:</strong> " . __DIR__ . "</p>";
echo "<p><strong>__FILE__:</strong> " . __FILE__ . "</p>";

// Probar diferentes niveles
echo "<h2>Búsqueda de wp-config.php</h2>";

$levels = [
    'Nivel 0 (actual)' => __DIR__,
    'Nivel 1' => dirname(__DIR__),
    'Nivel 2' => dirname(dirname(__DIR__)),
    'Nivel 3' => dirname(dirname(dirname(__DIR__))),
    'Nivel 4' => dirname(dirname(dirname(dirname(__DIR__)))),
    'Nivel 5' => dirname(dirname(dirname(dirname(dirname(__DIR__)))))
];

foreach ($levels as $level_name => $path) {
    $wp_config_path = $path . '/wp-config.php';
    $exists = file_exists($wp_config_path);
    
    echo "<p>";
    echo "<strong>$level_name:</strong> ";
    echo "<code>$path</code> ";
    
    if ($exists) {
        echo "<span style='color: green;'>✅ wp-config.php encontrado</span>";
    } else {
        echo "<span style='color: red;'>❌ wp-config.php no encontrado</span>";
    }
    
    echo "</p>";
}

// Mostrar contenido del directorio actual
echo "<h2>Contenido del Directorio Actual</h2>";
$files = scandir(__DIR__);
echo "<ul>";
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "<li>$file</li>";
    }
}
echo "</ul>";

// Mostrar contenido del directorio padre
echo "<h2>Contenido del Directorio Padre</h2>";
$parent_files = scandir(dirname(__DIR__));
echo "<ul>";
foreach ($parent_files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "<li>$file</li>";
    }
}
echo "</ul>";

// Mostrar contenido del directorio padre del padre
echo "<h2>Contenido del Directorio Padre del Padre</h2>";
$grandparent_files = scandir(dirname(dirname(__DIR__)));
echo "<ul>";
foreach ($grandparent_files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "<li>$file</li>";
    }
}
echo "</ul>";

// Mostrar contenido del directorio padre del padre del padre
echo "<h2>Contenido del Directorio Padre del Padre del Padre</h2>";
$great_grandparent_files = scandir(dirname(dirname(dirname(__DIR__))));
echo "<ul>";
foreach ($great_grandparent_files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "<li>$file</li>";
    }
}
echo "</ul>";

echo "<h2>Recomendación</h2>";
echo "<p>Ejecuta este archivo y busca la línea que dice '✅ wp-config.php encontrado'.</p>";
echo "<p>Esa será la ruta correcta que debes usar en tu script.</p>";
?> 