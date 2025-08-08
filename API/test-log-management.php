<?php
/**
 * Prueba de gestión de archivos de log
 */

// Definir la ruta absoluta al directorio raíz de WordPress
$wp_root = dirname(dirname(dirname(dirname(__DIR__)))) . '/';

// Verificar que wp-config.php existe
if (!file_exists($wp_root . 'wp-config.php')) {
    die("❌ Error: No se puede encontrar wp-config.php en: " . $wp_root);
}

// Incluir WordPress de forma segura
require_once($wp_root . 'wp-config.php');

// Verificar que WordPress se cargó correctamente
if (!defined('ABSPATH')) {
    die("❌ Error: WordPress no se cargó correctamente");
}

// Verificar permisos de administrador
if (!current_user_can('manage_options')) {
    die("❌ Acceso denegado. Necesitas permisos de administrador.");
}

echo "<h1>Gestión de Archivos de Log - KL Wallet API</h1>";

// Incluir el API
require_once(__DIR__ . '/api-wallet.php');

$api = new KL_Wallet_API();

echo "<h2>1. Configuración de Rotación de Logs</h2>";

echo "<h3>✅ Configuración Actual:</h3>";
echo "<ul>";
echo "<li><strong>Tamaño máximo por archivo:</strong> 10 MB</li>";
echo "<li><strong>Archivos de backup:</strong> 5 archivos</li>";
echo "<li><strong>Retención de logs:</strong> 30 días</li>";
echo "<li><strong>Limpieza automática:</strong> Diaria (cron job)</li>";
echo "</ul>";

echo "<h3>📁 Estructura de Archivos:</h3>";
echo "<pre><code>wp-content/logs/
├── kl-wallet-api.log          # Archivo actual
├── kl-wallet-api.log.1        # Backup más reciente
├── kl-wallet-api.log.2        # Segundo backup
├── kl-wallet-api.log.3        # Tercer backup
├── kl-wallet-api.log.4        # Cuarto backup
├── kl-wallet-api.log.5        # Quinto backup (más antiguo)
├── kl-wallet-api-performance.log
├── kl-wallet-api-performance.log.1
└── ...</code></pre>";

echo "<h2>2. Estadísticas Actuales de Logs</h2>";

$stats = $api->get_log_statistics();

echo "<h3>📊 Resumen General:</h3>";
echo "<ul>";
echo "<li><strong>Total de archivos:</strong> {$stats['total_files']}</li>";
echo "<li><strong>Tamaño total:</strong> {$stats['total_size_mb']} MB</li>";
echo "<li><strong>Archivos de API:</strong> " . count($stats['api_log']) . "</li>";
echo "<li><strong>Archivos de rendimiento:</strong> " . count($stats['performance_log']) . "</li>";
echo "</ul>";

if (!empty($stats['api_log'])) {
    echo "<h3>📋 Archivos de Log de API:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Archivo</th><th>Tamaño</th><th>Última Modificación</th></tr>";
    
    foreach ($stats['api_log'] as $file) {
        $size_color = ($file['size_mb'] > 5) ? 'red' : 'green';
        echo "<tr>";
        echo "<td><code>{$file['file']}</code></td>";
        echo "<td style='color: $size_color;'><strong>{$file['size_mb']} MB</strong></td>";
        echo "<td>{$file['modified']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

if (!empty($stats['performance_log'])) {
    echo "<h3>📈 Archivos de Log de Rendimiento:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Archivo</th><th>Tamaño</th><th>Última Modificación</th></tr>";
    
    foreach ($stats['performance_log'] as $file) {
        $size_color = ($file['size_mb'] > 5) ? 'red' : 'green';
        echo "<tr>";
        echo "<td><code>{$file['file']}</code></td>";
        echo "<td style='color: $size_color;'><strong>{$file['size_mb']} MB</strong></td>";
        echo "<td>{$file['modified']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<h2>3. Prueba de Rotación de Logs</h2>";

// Crear un archivo de log de prueba grande
$log_dir = WP_CONTENT_DIR . '/logs';
$test_log_file = $log_dir . '/test-rotation.log';

if (!is_dir($log_dir)) {
    wp_mkdir_p($log_dir);
}

// Generar contenido de prueba (aproximadamente 1MB)
$test_content = '';
for ($i = 0; $i < 10000; $i++) {
    $test_content .= "[2024-01-01 12:00:00] Test User Agent - IP: 192.168.1.1 - Action: test_action - User ID: 123 - Service: test_service\n";
}

// Crear archivo de prueba
file_put_contents($test_log_file, $test_content);

echo "<p><strong>Archivo de prueba creado:</strong> <code>test-rotation.log</code> (" . round(filesize($test_log_file) / 1024 / 1024, 2) . " MB)</p>";

// Simular rotación
echo "<h3>🔄 Simulación de Rotación:</h3>";

// Crear archivos de backup simulados
for ($i = 1; $i <= 3; $i++) {
    $backup_file = $log_dir . '/test-rotation.log.' . $i;
    file_put_contents($backup_file, "Backup file $i content\n");
    echo "<p>✅ Backup creado: <code>test-rotation.log.$i</code></p>";
}

echo "<h2>4. Endpoints de Gestión de Logs</h2>";

echo "<h3>📡 Endpoints Disponibles:</h3>";
echo "<ul>";
echo "<li><strong>GET /wp-json/kl-wallet/v1/logs?action=stats</strong> - Obtener estadísticas</li>";
echo "<li><strong>GET /wp-json/kl-wallet/v1/logs?action=cleanup</strong> - Limpiar logs antiguos</li>";
echo "<li><strong>GET /wp-json/kl-wallet/v1/logs?action=download&log_type=api</strong> - Descargar log de API</li>";
echo "<li><strong>GET /wp-json/kl-wallet/v1/logs?action=download&log_type=performance</strong> - Descargar log de rendimiento</li>";
echo "</ul>";

echo "<h3>🔧 Ejemplos de Uso:</h3>";
echo "<pre><code># Obtener estadísticas
curl -X GET 'https://tu-sitio.com/wp-json/kl-wallet/v1/logs?action=stats' \\
  -H 'Authorization: Bearer TU_TOKEN_JWT'

# Limpiar logs antiguos
curl -X GET 'https://tu-sitio.com/wp-json/kl-wallet/v1/logs?action=cleanup' \\
  -H 'Authorization: Bearer TU_TOKEN_JWT'

# Descargar log de API
curl -X GET 'https://tu-sitio.com/wp-json/kl-wallet/v1/logs?action=download&log_type=api' \\
  -H 'Authorization: Bearer TU_TOKEN_JWT' \\
  -o kl-wallet-api.log</code></pre>";

echo "<h2>5. Configuración Personalizable</h2>";

echo "<h3>⚙️ Parámetros de Configuración:</h3>";
echo "<pre><code>// En la función rotate_log_file_if_needed()
\$max_size_mb = 100;        // Tamaño máximo por archivo (MB)
\$max_files = 5;           // Número máximo de archivos de backup

// En la función cleanup_old_logs()
\$max_age_days = 30;       // Retención de logs (días)</code></pre>";

echo "<h3>🔧 Personalización:</h3>";
echo "<p>Para cambiar la configuración, modifica estos valores en el archivo <code>api-wallet.php</code>:</p>";
echo "<ul>";
echo "<li><strong>Línea ~580:</strong> <code>\$max_size_mb = 10;</code> - Cambiar tamaño máximo</li>";
echo "<li><strong>Línea ~581:</strong> <code>\$max_files = 5;</code> - Cambiar número de backups</li>";
echo "<li><strong>Línea ~650:</strong> <code>\$max_age_days = 30;</code> - Cambiar retención</li>";
echo "</ul>";

echo "<h2>6. Monitoreo y Mantenimiento</h2>";

echo "<h3>📊 Monitoreo Automático:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Rotación automática:</strong> Cuando el archivo excede 10MB</li>";
echo "<li>✅ <strong>Limpieza automática:</strong> Diaria via cron job</li>";
echo "<li>✅ <strong>Logs de rotación:</strong> Registrados en error_log</li>";
echo "</ul>";

echo "<h3>🔍 Comandos de Monitoreo:</h3>";
echo "<pre><code># Verificar tamaño de logs
du -sh wp-content/logs/

# Verificar archivos de log
ls -lah wp-content/logs/kl-wallet-*.log*

# Verificar cron jobs
wp cron event list | grep kl_wallet

# Verificar logs de rotación
tail -f wp-content/debug.log | grep 'KL Wallet API'</code></pre>";

echo "<h2>7. Recomendaciones</h2>";

echo "<h3>💡 Mejores Prácticas:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Monitoreo regular:</strong> Revisar estadísticas semanalmente</li>";
echo "<li>✅ <strong>Backup de logs importantes:</strong> Antes de limpieza automática</li>";
echo "<li>✅ <strong>Ajuste de configuración:</strong> Según el volumen de tráfico</li>";
echo "<li>✅ <strong>Alertas:</strong> Configurar alertas para archivos muy grandes</li>";
echo "<li>✅ <strong>Análisis de logs:</strong> Usar herramientas como logrotate</li>";
echo "</ul>";

echo "<h3>⚠️ Consideraciones:</h3>";
echo "<ul>";
echo "<li>⚠️ <strong>Espacio en disco:</strong> Monitorear uso de espacio</li>";
echo "<li>⚠️ <strong>Rendimiento:</strong> Logs muy grandes pueden afectar el rendimiento</li>";
echo "<li>⚠️ <strong>Privacidad:</strong> Los logs pueden contener información sensible</li>";
echo "<li>⚠️ <strong>Backup:</strong> Considerar backup de logs importantes</li>";
echo "</ul>";

echo "<h2>8. Limpieza de Archivos de Prueba</h2>";

// Limpiar archivos de prueba
$test_files = glob($log_dir . '/test-rotation.log*');
foreach ($test_files as $file) {
    if (file_exists($file)) {
        unlink($file);
        echo "<p>🗑️ Eliminado: <code>" . basename($file) . "</code></p>";
    }
}

echo "<p><strong>Nota:</strong> Después de verificar, elimina este archivo temporal.</p>";
?> 