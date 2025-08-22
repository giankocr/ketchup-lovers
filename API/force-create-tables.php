<?php
/**
 * Script para forzar la creación de las tablas de base de datos del sistema de bloqueo de IPs
 * 
 * Este script crea manualmente las tablas necesarias para el sistema de logs de respuestas 403
 */

// Incluir WordPress
require_once('../../../wp-load.php');

// Verificar que estamos en el contexto correcto
if (!defined('ABSPATH')) {
    die('Este script debe ejecutarse desde WordPress');
}

echo "<h1>Forzar Creación de Tablas del Sistema de Bloqueo de IPs</h1>\n";

// Función para crear tabla de IPs bloqueadas
function create_blocked_ips_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'kl_wallet_blocked_ips';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        ip_address varchar(45) NOT NULL,
        blocked_at datetime NOT NULL,
        reason text,
        PRIMARY KEY (ip_address)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $result = dbDelta($sql);
    
    echo "<h3>Creando tabla de IPs bloqueadas: $table_name</h3>\n";
    echo "<p>Resultado: " . (empty($result) ? "✅ Tabla creada/actualizada correctamente" : "❌ Error en creación") . "</p>\n";
    
    if (!empty($result)) {
        echo "<pre>" . print_r($result, true) . "</pre>\n";
    }
    
    return empty($result);
}

// Función para crear tabla de contadores 403
function create_ip_403_counts_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'kl_wallet_ip_403_counts';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        ip_address varchar(45) NOT NULL,
        count int(11) NOT NULL DEFAULT 0,
        last_attempt datetime NOT NULL,
        PRIMARY KEY (ip_address)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $result = dbDelta($sql);
    
    echo "<h3>Creando tabla de contadores 403: $table_name</h3>\n";
    echo "<p>Resultado: " . (empty($result) ? "✅ Tabla creada/actualizada correctamente" : "❌ Error en creación") . "</p>\n";
    
    if (!empty($result)) {
        echo "<pre>" . print_r($result, true) . "</pre>\n";
    }
    
    return empty($result);
}

// Función para crear tabla de logs 403
function create_ip_403_logs_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'kl_wallet_ip_403_logs';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        ip_address varchar(45) NOT NULL,
        user_agent text,
        request_uri text,
        request_method varchar(10),
        reason text,
        endpoint varchar(255),
        headers text,
        timestamp datetime NOT NULL,
        PRIMARY KEY (id),
        KEY ip_address (ip_address),
        KEY timestamp (timestamp),
        KEY endpoint (endpoint)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $result = dbDelta($sql);
    
    echo "<h3>Creando tabla de logs 403: $table_name</h3>\n";
    echo "<p>Resultado: " . (empty($result) ? "✅ Tabla creada/actualizada correctamente" : "❌ Error en creación") . "</p>\n";
    
    if (!empty($result)) {
        echo "<pre>" . print_r($result, true) . "</pre>\n";
    }
    
    return empty($result);
}

// Función para verificar si una tabla existe
function table_exists($table_name) {
    global $wpdb;
    $result = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    return $result === $table_name;
}

// Función para mostrar información de una tabla
function show_table_info($table_name) {
    global $wpdb;
    
    if (!table_exists($table_name)) {
        echo "<p>❌ La tabla $table_name no existe</p>\n";
        return;
    }
    
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "<p>✅ Tabla $table_name existe con $count registros</p>\n";
    
    // Mostrar estructura
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Llave</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column->Field . "</td>";
        echo "<td>" . $column->Type . "</td>";
        echo "<td>" . $column->Null . "</td>";
        echo "<td>" . $column->Key . "</td>";
        echo "<td>" . $column->Default . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>1. Verificación de Tablas Existentes</h2>\n";

$blocked_ips_table = $wpdb->prefix . 'kl_wallet_blocked_ips';
$counts_table = $wpdb->prefix . 'kl_wallet_ip_403_counts';
$logs_table = $wpdb->prefix . 'kl_wallet_ip_403_logs';

echo "<h3>Estado actual de las tablas:</h3>\n";
show_table_info($blocked_ips_table);
show_table_info($counts_table);
show_table_info($logs_table);

echo "<h2>2. Creación Forzada de Tablas</h2>\n";

$success = true;

// Crear tabla de IPs bloqueadas
if (!table_exists($blocked_ips_table)) {
    $success &= create_blocked_ips_table();
} else {
    echo "<h3>Tabla de IPs bloqueadas ya existe</h3>\n";
}

// Crear tabla de contadores
if (!table_exists($counts_table)) {
    $success &= create_ip_403_counts_table();
} else {
    echo "<h3>Tabla de contadores 403 ya existe</h3>\n";
}

// Crear tabla de logs
if (!table_exists($logs_table)) {
    $success &= create_ip_403_logs_table();
} else {
    echo "<h3>Tabla de logs 403 ya existe</h3>\n";
}

echo "<h2>3. Verificación Post-Creación</h2>\n";

echo "<h3>Estado final de las tablas:</h3>\n";
show_table_info($blocked_ips_table);
show_table_info($counts_table);
show_table_info($logs_table);

echo "<h2>4. Prueba de Funcionalidad</h2>\n";

// Probar inserción en cada tabla
echo "<h3>Prueba de inserción en tabla de logs:</h3>\n";
$test_data = [
    'ip_address' => '127.0.0.1',
    'user_agent' => 'Test User Agent',
    'request_uri' => '/test-uri',
    'request_method' => 'GET',
    'reason' => 'Test reason from force-create script',
    'endpoint' => 'test-endpoint',
    'headers' => json_encode(['test' => 'header']),
    'timestamp' => current_time('mysql')
];

$result = $wpdb->insert(
    $logs_table,
    $test_data,
    ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
);

if ($result !== false) {
    $inserted_id = $wpdb->insert_id;
    echo "<p>✅ Inserción exitosa en tabla de logs (ID: $inserted_id)</p>\n";
    
    // Limpiar el registro de prueba
    $wpdb->delete($logs_table, ['id' => $inserted_id], ['%d']);
    echo "<p>✅ Registro de prueba eliminado</p>\n";
} else {
    echo "<p>❌ Error en inserción: " . $wpdb->last_error . "</p>\n";
}

echo "<h3>Prueba de inserción en tabla de contadores:</h3>\n";
$result = $wpdb->insert(
    $counts_table,
    [
        'ip_address' => '127.0.0.1',
        'count' => 1,
        'last_attempt' => current_time('mysql')
    ],
    ['%s', '%d', '%s']
);

if ($result !== false) {
    echo "<p>✅ Inserción exitosa en tabla de contadores</p>\n";
    
    // Limpiar el registro de prueba
    $wpdb->delete($counts_table, ['ip_address' => '127.0.0.1'], ['%s']);
    echo "<p>✅ Registro de prueba eliminado</p>\n";
} else {
    echo "<p>❌ Error en inserción: " . $wpdb->last_error . "</p>\n";
}

echo "<h3>Prueba de inserción en tabla de IPs bloqueadas:</h3>\n";
$result = $wpdb->insert(
    $blocked_ips_table,
    [
        'ip_address' => '127.0.0.1',
        'blocked_at' => current_time('mysql'),
        'reason' => 'Test block from force-create script'
    ],
    ['%s', '%s', '%s']
);

if ($result !== false) {
    echo "<p>✅ Inserción exitosa en tabla de IPs bloqueadas</p>\n";
    
    // Limpiar el registro de prueba
    $wpdb->delete($blocked_ips_table, ['ip_address' => '127.0.0.1'], ['%s']);
    echo "<p>✅ Registro de prueba eliminado</p>\n";
} else {
    echo "<p>❌ Error en inserción: " . $wpdb->last_error . "</p>\n";
}

echo "<h2>5. Configuración de Opciones</h2>\n";

// Configurar opciones por defecto si no existen
$default_options = [
    'kl_wallet_max_403_responses' => 100,
    'kl_wallet_block_duration' => 24,
    'kl_wallet_ip_restriction_enabled' => true
];

foreach ($default_options as $option_name => $default_value) {
    $current_value = get_option($option_name);
    if ($current_value === false) {
        add_option($option_name, $default_value);
        echo "<p>✅ Opción '$option_name' configurada con valor: $default_value</p>\n";
    } else {
        echo "<p>ℹ️ Opción '$option_name' ya existe con valor: " . var_export($current_value, true) . "</p>\n";
    }
}

echo "<h2>6. Resumen</h2>\n";

if ($success) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px;'>\n";
    echo "<h4>✅ Éxito</h4>\n";
    echo "<p>Todas las tablas han sido creadas correctamente y el sistema está listo para funcionar.</p>\n";
    echo "</div>\n";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>\n";
    echo "<h4>❌ Error</h4>\n";
    echo "<p>Hubo problemas en la creación de algunas tablas. Revisa los errores arriba.</p>\n";
    echo "</div>\n";
}

echo "<hr>\n";
echo "<p><em>Script de creación de tablas completado.</em></p>\n";
echo "<p><strong>Próximo paso:</strong> Ejecutar el script de diagnóstico para verificar que todo funciona correctamente.</p>\n";
?>
