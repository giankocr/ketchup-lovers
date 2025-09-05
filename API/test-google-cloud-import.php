<?php
/**
 * Script de prueba para importación de IPs de Google Cloud
 * 
 * Este archivo permite probar la funcionalidad de importación de IPs
 * de Google Cloud sin necesidad de usar la interfaz web.
 * 
 * Uso: Ejecutar desde la línea de comandos o acceder directamente
 */

// Cargar WordPress
require_once dirname(dirname(dirname(__DIR__))) . '/wp-load.php';

// Verificar que el usuario tenga permisos
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para ejecutar este script');
}

// Incluir las funciones necesarias
require_once __DIR__ . '/config.php';

echo "<h1>Prueba de Importación de IPs de Google Cloud</h1>\n";

// Probar obtención de estadísticas
echo "<h2>1. Estadísticas de Google Cloud</h2>\n";
$stats = kl_wallet_get_google_cloud_stats();

if ($stats['success']) {
    echo "<p><strong>✅ Estadísticas obtenidas exitosamente:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Total de prefijos: " . $stats['stats']['total_prefixes'] . "</li>\n";
    echo "<li>Prefijos IPv4: " . $stats['stats']['ipv4_prefixes'] . "</li>\n";
    echo "<li>Prefijos IPv6: " . $stats['stats']['ipv6_prefixes'] . "</li>\n";
    echo "<li>Token de sincronización: " . $stats['stats']['sync_token'] . "</li>\n";
    echo "<li>Tiempo de creación: " . $stats['stats']['creation_time'] . "</li>\n";
    echo "</ul>\n";
    
    // Mostrar algunos servicios disponibles
    if (!empty($stats['stats']['services'])) {
        echo "<h3>Servicios disponibles:</h3>\n";
        echo "<ul>\n";
        foreach ($stats['stats']['services'] as $service => $count) {
            echo "<li>$service: $count prefijos</li>\n";
        }
        echo "</ul>\n";
    }
    
    // Mostrar algunas regiones disponibles
    if (!empty($stats['stats']['scopes'])) {
        echo "<h3>Regiones disponibles (primeras 10):</h3>\n";
        echo "<ul>\n";
        $count = 0;
        foreach ($stats['stats']['scopes'] as $scope => $scope_count) {
            if ($count >= 10) break;
            echo "<li>$scope: $scope_count prefijos</li>\n";
            $count++;
        }
        echo "</ul>\n";
    }
} else {
    echo "<p><strong>❌ Error al obtener estadísticas:</strong> " . $stats['message'] . "</p>\n";
}

// Probar importación con filtros específicos
echo "<h2>2. Prueba de importación con filtros</h2>\n";
$filters = [
    'services' => ['Google Cloud'],
    'scopes' => ['us-central1', 'us-east1']
];

$import_result = kl_wallet_add_google_cloud_ips_filtered($filters);

if ($import_result['success']) {
    echo "<p><strong>✅ Importación exitosa:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>IPs agregadas: " . $import_result['added_count'] . "</li>\n";
    echo "<li>IPs omitidas (ya existían): " . $import_result['skipped_count'] . "</li>\n";
    echo "<li>IPs filtradas: " . $import_result['filtered_count'] . "</li>\n";
    echo "<li>Total procesadas: " . $import_result['total_processed'] . "</li>\n";
    echo "</ul>\n";
} else {
    echo "<p><strong>❌ Error en la importación:</strong> " . $import_result['message'] . "</p>\n";
}

// Mostrar IPs actuales permitidas
echo "<h2>3. IPs actualmente permitidas</h2>\n";
$current_ips = kl_wallet_get_allowed_ips();

if (empty($current_ips)) {
    echo "<p>No hay IPs configuradas actualmente.</p>\n";
} else {
    echo "<p><strong>Total de IPs permitidas:</strong> " . count($current_ips) . "</p>\n";
    echo "<h3>Primeras 20 IPs:</h3>\n";
    echo "<ul>\n";
    $count = 0;
    foreach ($current_ips as $ip) {
        if ($count >= 20) break;
        echo "<li>$ip</li>\n";
        $count++;
    }
    if (count($current_ips) > 20) {
        echo "<li>... y " . (count($current_ips) - 20) . " más</li>\n";
    }
    echo "</ul>\n";
}

// Estado de la restricción
echo "<h2>4. Estado de la restricción</h2>\n";
$restriction_enabled = kl_wallet_is_ip_restriction_enabled();
echo "<p><strong>Restricción de IPs:</strong> " . ($restriction_enabled ? '🔒 Habilitada' : '🔓 Deshabilitada') . "</p>\n";

echo "<hr>\n";
echo "<p><em>Script de prueba completado. Revisa los logs de WordPress para más detalles.</em></p>\n";
?>
