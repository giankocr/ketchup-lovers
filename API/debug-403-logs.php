<?php
/**
 * Script de diagnóstico para el sistema de logs de respuestas 403
 * 
 * Este script verifica por qué no se están guardando los logs de respuestas 403
 */

// Incluir WordPress
require_once('../../../wp-load.php');

// Verificar que estamos en el contexto correcto
if (!defined('ABSPATH')) {
    die('Este script debe ejecutarse desde WordPress');
}

echo "<h1>Diagnóstico del Sistema de Logs de Respuestas 403</h1>\n";

// Función para mostrar información de diagnóstico
function show_diagnostic($title, $callback) {
    echo "<h3>$title</h3>\n";
    try {
        $result = $callback();
        if (is_bool($result)) {
            echo "<p>" . ($result ? "✅ Sí" : "❌ No") . "</p>\n";
        } else {
            echo "<p>$result</p>\n";
        }
    } catch (Exception $e) {
        echo "<p>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>\n";
    }
}

// Inicializar la API
$api = new KL_Wallet_API();

echo "<h2>1. Verificación de Tablas de Base de Datos</h2>\n";

// Verificar si las tablas existen
show_diagnostic('¿Existe la tabla de logs 403?', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kl_wallet_ip_403_logs';
    $result = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    return $result === $table_name ? "✅ Sí" : "❌ No";
});

show_diagnostic('¿Existe la tabla de contadores 403?', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kl_wallet_ip_403_counts';
    $result = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    return $result === $table_name ? "✅ Sí" : "❌ No";
});

show_diagnostic('¿Existe la tabla de IPs bloqueadas?', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kl_wallet_blocked_ips';
    $result = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    return $result === $table_name ? "✅ Sí" : "❌ No";
});

echo "<h2>2. Verificación de Estructura de Tablas</h2>\n";

// Verificar estructura de la tabla de logs
show_diagnostic('Estructura de la tabla de logs 403:', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kl_wallet_ip_403_logs';
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    $output = "<table border='1' style='border-collapse: collapse;'>";
    $output .= "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Llave</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        $output .= "<tr>";
        $output .= "<td>" . $column->Field . "</td>";
        $output .= "<td>" . $column->Type . "</td>";
        $output .= "<td>" . $column->Null . "</td>";
        $output .= "<td>" . $column->Key . "</td>";
        $output .= "<td>" . $column->Default . "</td>";
        $output .= "</tr>";
    }
    $output .= "</table>";
    return $output;
});

echo "<h2>3. Verificación de Métodos de la API</h2>\n";

// Verificar métodos públicos
show_diagnostic('¿Método get_403_logs_public() disponible?', function() use ($api) {
    return method_exists($api, 'get_403_logs_public') ? "✅ Sí" : "❌ No";
});

show_diagnostic('¿Método get_403_logs_stats_public() disponible?', function() use ($api) {
    return method_exists($api, 'get_403_logs_stats_public') ? "✅ Sí" : "❌ No";
});

echo "<h2>4. Prueba de Inserción Manual</h2>\n";

// Probar inserción manual en la tabla de logs
show_diagnostic('Prueba de inserción manual en tabla de logs:', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kl_wallet_ip_403_logs';
    
    $test_data = [
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test User Agent',
        'request_uri' => '/test-uri',
        'request_method' => 'GET',
        'reason' => 'Test reason',
        'endpoint' => 'test-endpoint',
        'headers' => json_encode(['test' => 'header']),
        'timestamp' => current_time('mysql')
    ];
    
    $result = $wpdb->insert(
        $table_name,
        $test_data,
        ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
    );
    
    if ($result !== false) {
        $inserted_id = $wpdb->insert_id;
        $wpdb->delete($table_name, ['id' => $inserted_id], ['%d']);
        return "✅ Inserción exitosa (ID: $inserted_id)";
    } else {
        return "❌ Error en inserción: " . $wpdb->last_error;
    }
});

echo "<h2>5. Verificación de Hooks</h2>\n";

// Verificar si los hooks están registrados
show_diagnostic('¿Hook rest_pre_dispatch registrado?', function() {
    global $wp_filter;
    return isset($wp_filter['rest_pre_dispatch']) ? "✅ Sí" : "❌ No";
});

show_diagnostic('¿Hook rest_authentication_errors registrado?', function() {
    global $wp_filter;
    return isset($wp_filter['rest_authentication_errors']) ? "✅ Sí" : "❌ No";
});

echo "<h2>6. Verificación de Configuración</h2>\n";

// Verificar configuración
show_diagnostic('Configuración actual:', function() {
    $config = [
        'max_403_responses' => get_option('kl_wallet_max_403_responses', 100),
        'block_duration' => get_option('kl_wallet_block_duration', 24),
        'ip_restriction_enabled' => get_option('kl_wallet_ip_restriction_enabled', true)
    ];
    
    $output = "<ul>";
    foreach ($config as $key => $value) {
        $output .= "<li><strong>$key:</strong> " . var_export($value, true) . "</li>";
    }
    $output .= "</ul>";
    return $output;
});

echo "<h2>7. Prueba de Registro de 403</h2>\n";

// Probar el registro de una respuesta 403
show_diagnostic('Prueba de registro de respuesta 403:', function() use ($api) {
    try {
        // Simular una respuesta 403
        $test_ip = '192.168.1.100';
        $test_reason = 'Test 403 response from debug script';
        $test_endpoint = 'debug-test';
        
        // Usar el método público para registrar
        $result = $api->register_403_response_public($test_ip, $test_reason, $test_endpoint);
        
        return $result ? "✅ Registro exitoso" : "❌ Error en registro";
    } catch (Exception $e) {
        return "❌ Excepción: " . $e->getMessage();
    }
});

echo "<h2>8. Verificación de Logs Existentes</h2>\n";

// Verificar logs existentes
show_diagnostic('Logs existentes en la tabla:', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kl_wallet_ip_403_logs';
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    return "Total de logs: $count";
});

show_diagnostic('Últimos 5 logs:', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kl_wallet_ip_403_logs';
    $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 5");
    
    if (empty($logs)) {
        return "No hay logs registrados";
    }
    
    $output = "<table border='1' style='border-collapse: collapse;'>";
    $output .= "<tr><th>ID</th><th>IP</th><th>Endpoint</th><th>Razón</th><th>Fecha</th></tr>";
    foreach ($logs as $log) {
        $output .= "<tr>";
        $output .= "<td>" . $log->id . "</td>";
        $output .= "<td>" . $log->ip_address . "</td>";
        $output .= "<td>" . $log->endpoint . "</td>";
        $output .= "<td>" . substr($log->reason, 0, 50) . "...</td>";
        $output .= "<td>" . $log->timestamp . "</td>";
        $output .= "</tr>";
    }
    $output .= "</table>";
    return $output;
});

echo "<h2>9. Verificación de Permisos de Base de Datos</h2>\n";

// Verificar permisos
show_diagnostic('¿Puede escribir en la tabla de logs?', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kl_wallet_ip_403_logs';
    
    // Intentar una operación de escritura
    $result = $wpdb->query("INSERT INTO $table_name (ip_address, user_agent, request_uri, request_method, reason, endpoint, headers, timestamp) VALUES ('test', 'test', 'test', 'GET', 'test', 'test', '{}', NOW())");
    
    if ($result !== false) {
        // Limpiar el registro de prueba
        $wpdb->query("DELETE FROM $table_name WHERE ip_address = 'test'");
        return "✅ Sí, puede escribir en la tabla";
    } else {
        return "❌ No, error: " . $wpdb->last_error;
    }
});

echo "<h2>10. Recomendaciones</h2>\n";

echo "<div style='background: #f0f0f0; padding: 15px; border-left: 4px solid #0073aa;'>\n";
echo "<h4>Posibles causas del problema:</h4>\n";
echo "<ul>\n";
echo "<li><strong>Tablas no creadas:</strong> Verificar que las tablas se crearon correctamente</li>\n";
echo "<li><strong>Hooks no registrados:</strong> Verificar que los hooks de WordPress estén funcionando</li>\n";
echo "<li><strong>Permisos de BD:</strong> Verificar permisos de escritura en la base de datos</li>\n";
echo "<li><strong>Errores de PHP:</strong> Verificar logs de error de PHP</li>\n";
echo "<li><strong>Configuración incorrecta:</strong> Verificar configuración de WordPress</li>\n";
echo "</ul>\n";

echo "<h4>Acciones recomendadas:</h4>\n";
echo "<ul>\n";
echo "<li>Revisar los logs de error de WordPress</li>\n";
echo "<li>Verificar que el plugin esté activado correctamente</li>\n";
echo "<li>Probar con una solicitud real a la API</li>\n";
echo "<li>Verificar que los hooks se ejecuten en el momento correcto</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<hr>\n";
echo "<p><em>Diagnóstico completado. Revisa los resultados arriba para identificar el problema.</em></p>\n";
?>
