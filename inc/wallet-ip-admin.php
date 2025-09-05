<?php
/**
 * Panel de administración para gestión de IPs del API de Wallet
 * 
 * @package KetchupLovers
 * @version 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    http_response_code(403);
    exit('Acceso directo prohibido');
}

// Incluir el gestor de IPs
require_once THEME_DIR . '/API/ip-manager.php';

/**
 * Clase para el panel de administración de IPs
 */
class KL_Wallet_IP_Admin {
    
    /**
     * Mostrar error de dependencias faltantes
     */
    public function show_dependency_error(): void {
        ?>
        <div class="notice notice-error">
            <p>
                <strong>Error en API Wallet IP Manager:</strong> 
                Las funciones de gestión de IPs no están disponibles. 
                Verifica que el archivo <code>API/ip-manager.php</code> esté presente y se haya incluido correctamente.
            </p>
            <p>
                <a href="<?php echo admin_url('tools.php?page=kl-wallet-ip-manager&action=debug'); ?>" class="button button-secondary">
                    Ver Información de Depuración
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Verificar que las funciones necesarias estén disponibles
     */
    private function check_dependencies(): bool {
        $required_functions = [
            'kl_wallet_ip_current',
            'kl_wallet_ip_list',
            'kl_wallet_ip_add',
            'kl_wallet_ip_remove',
            'kl_wallet_is_ip_restriction_enabled',
            'kl_wallet_enable_ip_restriction',
            'kl_wallet_disable_ip_restriction'
        ];
        
        $missing_functions = [];
        foreach ($required_functions as $function) {
            if (!function_exists($function)) {
                $missing_functions[] = $function;
            }
        }
        
        if (!empty($missing_functions)) {
            error_log('KL Wallet IP Admin: Funciones faltantes: ' . implode(', ', $missing_functions));
            return false;
        }
        
        return true;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        // Verificar dependencias antes de inicializar
        if (!$this->check_dependencies()) {
            add_action('admin_notices', [$this, 'show_dependency_error']);
            return;
        }
        
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_form_submissions']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('admin_footer', [$this, 'add_custom_scripts']);
    }
    
    /**
     * Agregar menú al dashboard
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'tools.php', // Parent slug (Herramientas)
            'API Wallet - Gestión de IPs',
            'API Wallet IPs',
            'manage_options',
            'kl-wallet-ip-manager',
            [$this, 'admin_page']
        );
    }
    
    /**
     * Cargar scripts y estilos del admin
     */
    public function enqueue_admin_scripts($hook): void {
        if ($hook !== 'tools_page_kl-wallet-ip-manager') {
            return;
        }
        
        wp_enqueue_style('kl-wallet-admin', get_template_directory_uri() . '/assets/css/wallet-admin.css', [], '1.0.0');
        wp_enqueue_script('kl-wallet-admin', get_template_directory_uri() . '/assets/js/wallet-admin.js', ['jquery'], '1.0.0', true);
        
        // Localizar script para AJAX
        wp_localize_script('kl-wallet-admin', 'klWalletAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kl_wallet_admin_nonce'),
            'strings' => [
                'confirmDelete' => '¿Estás seguro de que quieres eliminar esta IP?',
                'confirmDisable' => '¿Estás seguro de que quieres deshabilitar la restricción de IPs?',
                'loading' => 'Procesando...'
            ]
        ]);
    }
    
    /**
     * Manejar envíos de formularios
     */
    public function handle_form_submissions(): void {
        if (!isset($_POST['kl_wallet_ip_action']) || !wp_verify_nonce($_POST['kl_wallet_nonce'], 'kl_wallet_ip_action')) {
            return;
        }
        
        $action = sanitize_text_field($_POST['kl_wallet_ip_action']);
        
        switch ($action) {
            case 'add_ip':
                $this->handle_add_ip();
                break;
                
            case 'remove_ip':
                $this->handle_remove_ip();
                break;
                
            case 'toggle_restriction':
                $this->handle_toggle_restriction();
                break;
                
            case 'import_google_cloud':
                $this->handle_import_google_cloud();
                break;
                
            case 'bulk_action':
                $this->handle_bulk_action();
                break;
        }
    }
    
    /**
     * Manejar agregar IP
     */
    private function handle_add_ip(): void {
        $ip = sanitize_text_field($_POST['ip_address'] ?? '');
        
        if (empty($ip)) {
            $this->add_admin_notice('IP no puede estar vacía', 'error');
            return;
        }
        
        $result = function_exists('kl_wallet_ip_add') ? kl_wallet_ip_add($ip) : [
            'success' => false,
            'message' => 'Función de gestión de IPs no disponible'
        ];
        
        if ($result['success']) {
            $this->add_admin_notice($result['message'], 'success');
        } else {
            $this->add_admin_notice($result['message'], 'error');
        }
    }
    
    /**
     * Manejar remover IP
     */
    private function handle_remove_ip(): void {
        $ip = sanitize_text_field($_POST['ip_address'] ?? '');
        
        if (empty($ip)) {
            $this->add_admin_notice('IP no puede estar vacía', 'error');
            return;
        }
        
        $result = function_exists('kl_wallet_ip_remove') ? kl_wallet_ip_remove($ip) : [
            'success' => false,
            'message' => 'Función de gestión de IPs no disponible'
        ];
        
        if ($result['success']) {
            $this->add_admin_notice($result['message'], 'success');
        } else {
            $this->add_admin_notice($result['message'], 'error');
        }
    }
    
    /**
     * Manejar toggle de restricción
     */
    private function handle_toggle_restriction(): void {
        $current_status = function_exists('kl_wallet_is_ip_restriction_enabled') ? kl_wallet_is_ip_restriction_enabled() : false;
        
        if ($current_status) {
            $result = function_exists('kl_wallet_disable_ip_restriction') ? kl_wallet_disable_ip_restriction() : false;
            $message = $result ? 'Restricción de IPs deshabilitada' : 'Error al deshabilitar restricción';
        } else {
            $result = function_exists('kl_wallet_enable_ip_restriction') ? kl_wallet_enable_ip_restriction() : false;
            $message = $result ? 'Restricción de IPs habilitada' : 'Error al habilitar restricción';
        }
        
        $this->add_admin_notice($message, $result ? 'success' : 'error');
    }
    
    /**
     * Manejar importación de IPs de Google Cloud
     */
    private function handle_import_google_cloud(): void {
        $import_type = sanitize_text_field($_POST['import_type'] ?? 'all');
        $filters = [];
        
        // Configurar filtros si se especifican
        if ($import_type === 'filtered') {
            $services = $_POST['google_services'] ?? [];
            $scopes = $_POST['google_scopes'] ?? [];
            
            if (!empty($services)) {
                $filters['services'] = array_map('sanitize_text_field', $services);
            }
            if (!empty($scopes)) {
                $filters['scopes'] = array_map('sanitize_text_field', $scopes);
            }
        }
        
        // Ejecutar la importación
        if (!empty($filters)) {
            $result = function_exists('kl_wallet_add_google_cloud_ips_filtered') 
                ? kl_wallet_add_google_cloud_ips_filtered($filters) 
                : ['success' => false, 'message' => 'Función de importación no disponible'];
        } else {
            $result = function_exists('kl_wallet_add_google_cloud_ips') 
                ? kl_wallet_add_google_cloud_ips() 
                : ['success' => false, 'message' => 'Función de importación no disponible'];
        }
        
        $this->add_admin_notice($result['message'], $result['success'] ? 'success' : 'error');
    }
    
    /**
     * Manejar acciones en lote
     */
    private function handle_bulk_action(): void {
        $bulk_action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $selected_ips = $_POST['selected_ips'] ?? [];
        
        if (empty($selected_ips) || !is_array($selected_ips)) {
            $this->add_admin_notice('No se seleccionaron IPs', 'error');
            return;
        }
        
        $success_count = 0;
        
        foreach ($selected_ips as $ip) {
            $ip = sanitize_text_field($ip);
            
            if ($bulk_action === 'delete') {
                $result = function_exists('kl_wallet_ip_remove') ? kl_wallet_ip_remove($ip) : [
                    'success' => false,
                    'message' => 'Función de gestión de IPs no disponible'
                ];
                if ($result['success']) {
                    $success_count++;
                }
            }
        }
        
        if ($success_count > 0) {
            $this->add_admin_notice("$success_count IP(s) procesada(s) exitosamente", 'success');
        } else {
            $this->add_admin_notice('No se pudo procesar ninguna IP', 'error');
        }
    }
    
    /**
     * Agregar notificación de admin
     */
    private function add_admin_notice(string $message, string $type = 'info'): void {
        $notices = get_option('kl_wallet_admin_notices', []);
        $notices[] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => time()
        ];
        update_option('kl_wallet_admin_notices', $notices);
    }
    
    /**
     * Página de administración
     */
    public function admin_page(): void {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para acceder a esta página');
        }
        
        // Verificar si se solicita información de depuración
        if (isset($_GET['action']) && $_GET['action'] === 'debug') {
            $this->show_debug_info();
            return;
        }
        
        // Obtener datos
        $current_ip_info = function_exists('kl_wallet_ip_current') ? kl_wallet_ip_current() : [
            'ip' => '0.0.0.0',
            'is_allowed' => false,
            'restriction_enabled' => false
        ];
        
        $allowed_ips = function_exists('kl_wallet_ip_list') ? kl_wallet_ip_list() : [
            'ips' => [],
            'count' => 0,
            'restriction_enabled' => false
        ];
        
        $api_status = $this->get_api_status();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-shield-alt" style="color: #007cba;"></span>
                API Wallet - Gestión de IPs
            </h1>
            
            <?php $this->display_notices(); ?>
            
            <div class="kl-wallet-admin-container">
                
                <!-- Estado del API -->
                <div class="kl-wallet-card">
                    <h2>Estado del API</h2>
                    <div class="kl-wallet-status-grid">
                        <div class="status-item">
                            <span class="status-label">API Status:</span>
                            <span class="status-value <?php echo $api_status['available'] ? 'success' : 'error'; ?>">
                                <?php echo $api_status['available'] ? '✅ Disponible' : '❌ No disponible'; ?>
                            </span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Tu IP actual:</span>
                            <span class="status-value"><?php echo $current_ip_info['ip']; ?></span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Restricción de IPs:</span>
                            <span class="status-value <?php echo $current_ip_info['restriction_enabled'] ? 'success' : 'warning'; ?>">
                                <?php echo $current_ip_info['restriction_enabled'] ? '🔒 Habilitada' : '🔓 Deshabilitada'; ?>
                            </span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Tu IP está permitida:</span>
                            <span class="status-value <?php echo $current_ip_info['is_allowed'] ? 'success' : 'error'; ?>">
                                <?php echo $current_ip_info['is_allowed'] ? '✅ Sí' : '❌ No'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Control de restricción -->
                <div class="kl-wallet-card">
                    <h2>Control de Restricción</h2>
                    <form method="post" class="kl-wallet-form">
                        <?php wp_nonce_field('kl_wallet_ip_action', 'kl_wallet_nonce'); ?>
                        <input type="hidden" name="kl_wallet_ip_action" value="toggle_restriction">
                        
                        <p>
                            <strong>Estado actual:</strong> 
                            <?php echo $current_ip_info['restriction_enabled'] ? 'Restricción habilitada' : 'Restricción deshabilitada'; ?>
                        </p>
                        
                        <button type="submit" class="button button-<?php echo $current_ip_info['restriction_enabled'] ? 'secondary' : 'primary'; ?>">
                            <?php echo $current_ip_info['restriction_enabled'] ? 'Deshabilitar Restricción' : 'Habilitar Restricción'; ?>
                        </button>
                        
                        <p class="description">
                            Cuando la restricción está deshabilitada, todas las IPs pueden acceder al API.
                        </p>
                    </form>
                </div>
                
                <!-- Agregar IP -->
                <div class="kl-wallet-card">
                    <h2>Agregar IP Permitida</h2>
                    <form method="post" class="kl-wallet-form">
                        <?php wp_nonce_field('kl_wallet_ip_action', 'kl_wallet_nonce'); ?>
                        <input type="hidden" name="kl_wallet_ip_action" value="add_ip">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="ip_address">IP o Rango CIDR:</label>
                                </th>
                                <td>
                                    <input type="text" id="ip_address" name="ip_address" class="regular-text" 
                                           placeholder="192.168.1.100 o 192.168.1.0/24" required>
                                    <p class="description">
                                        Ejemplos: 192.168.1.100, 10.0.0.0/8, 172.16.0.0/12
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">Agregar IP</button>
                        </p>
                    </form>
                </div>
                
                <!-- Importar IPs de Google Cloud -->
                <div class="kl-wallet-card">
                    <h2>Importar IPs de Google Cloud</h2>
                    <form method="post" class="kl-wallet-form">
                        <?php wp_nonce_field('kl_wallet_ip_action', 'kl_wallet_nonce'); ?>
                        <input type="hidden" name="kl_wallet_ip_action" value="import_google_cloud">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="import_type">Tipo de importación:</label>
                                </th>
                                <td>
                                    <select id="import_type" name="import_type" onchange="toggleGoogleCloudFilters()">
                                        <option value="all">Todas las IPs de Google Cloud</option>
                                        <option value="filtered">Filtrar por servicio/región</option>
                                    </select>
                                    <p class="description">
                                        Importa automáticamente los rangos de IP de Google Cloud desde el archivo oficial.
                                    </p>
                                </td>
                            </tr>
                            
                            <tr id="google-filters" style="display: none;">
                                <th scope="row">
                                    <label>Filtros:</label>
                                </th>
                                <td>
                                    <div style="margin-bottom: 10px;">
                                        <strong>Servicios:</strong><br>
                                        <label><input type="checkbox" name="google_services[]" value="Google Cloud"> Google Cloud</label><br>
                                        <label><input type="checkbox" name="google_services[]" value="Cloud NAT"> Cloud NAT</label><br>
                                        <label><input type="checkbox" name="google_services[]" value="Cloud VPN"> Cloud VPN</label>
                                    </div>
                                    
                                    <div>
                                        <strong>Regiones (ejemplos):</strong><br>
                                        <label><input type="checkbox" name="google_scopes[]" value="us-central1"> us-central1</label><br>
                                        <label><input type="checkbox" name="google_scopes[]" value="us-east1"> us-east1</label><br>
                                        <label><input type="checkbox" name="google_scopes[]" value="europe-west1"> europe-west1</label><br>
                                        <label><input type="checkbox" name="google_scopes[]" value="asia-east1"> asia-east1</label>
                                    </div>
                                    
                                    <p class="description">
                                        Deja vacío para incluir todos los servicios/regiones disponibles.
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-cloud" style="margin-right: 5px;"></span>
                                Importar IPs de Google Cloud
                            </button>
                        </p>
                        
                        <p class="description">
                            <strong>Nota:</strong> Esta operación puede tomar varios segundos y agregará cientos de rangos de IP.
                            Solo se agregarán los rangos que no estén ya en tu lista de IPs permitidas.
                        </p>
                    </form>
                </div>
                
                <!-- Lista de IPs -->
                <div class="kl-wallet-card">
                    <h2>IPs Permitidas (<?php echo $allowed_ips['count']; ?>)</h2>
                    
                    <?php if (empty($allowed_ips['ips'])): ?>
                        <p>No hay IPs configuradas. Todas las IPs están permitidas cuando la restricción está deshabilitada.</p>
                    <?php else: ?>
                        <form method="post" id="bulk-action-form">
                            <?php wp_nonce_field('kl_wallet_ip_action', 'kl_wallet_nonce'); ?>
                            <input type="hidden" name="kl_wallet_ip_action" value="bulk_action">
                            
                            <div class="tablenav top">
                                <div class="alignleft actions bulkactions">
                                    <select name="bulk_action">
                                        <option value="">Acciones en lote</option>
                                        <option value="delete">Eliminar</option>
                                    </select>
                                    <button type="submit" class="button action">Aplicar</button>
                                </div>
                            </div>
                            
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <td class="manage-column column-cb check-column">
                                            <input type="checkbox" id="cb-select-all-1">
                                        </td>
                                        <th scope="col" class="manage-column column-ip">IP/Rango</th>
                                        <th scope="col" class="manage-column column-type">Tipo</th>
                                        <th scope="col" class="manage-column column-actions">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allowed_ips['ips'] as $ip): ?>
                                        <tr>
                                            <th scope="row" class="check-column">
                                                <input type="checkbox" name="selected_ips[]" value="<?php echo esc_attr($ip); ?>">
                                            </th>
                                            <td class="column-ip">
                                                <code><?php echo esc_html($ip); ?></code>
                                            </td>
                                            <td class="column-type">
                                                <?php echo $this->get_ip_type_label($ip); ?>
                                            </td>
                                            <td class="column-actions">
                                                <form method="post" style="display: inline;">
                                                    <?php wp_nonce_field('kl_wallet_ip_action', 'kl_wallet_nonce'); ?>
                                                    <input type="hidden" name="kl_wallet_ip_action" value="remove_ip">
                                                    <input type="hidden" name="ip_address" value="<?php echo esc_attr($ip); ?>">
                                                    <button type="submit" class="button button-small button-link-delete" 
                                                            onclick="return confirm('¿Estás seguro de eliminar esta IP?')">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </form>
                    <?php endif; ?>
                </div>
                
                <!-- Información -->
                <div class="kl-wallet-card">
                    <h2>Información y Ayuda</h2>
                    
                    <h3>Formatos de IP soportados:</h3>
                    <ul>
                        <li><strong>IP individual:</strong> <code>192.168.1.100</code></li>
                        <li><strong>Rango CIDR:</strong> <code>192.168.1.0/24</code> (permite 192.168.1.0 a 192.168.1.255)</li>
                        <li><strong>Red privada:</strong> <code>10.0.0.0/8</code> (permite toda la red 10.x.x.x)</li>
                    </ul>
                    
                    <h3>Ejemplos de uso:</h3>
                    <ul>
                        <li><code>127.0.0.1</code> - Solo localhost</li>
                        <li><code>192.168.1.0/24</code> - Toda la red 192.168.1.x</li>
                        <li><code>10.0.0.0/8</code> - Toda la red privada 10.x.x.x</li>
                        <li><code>172.16.0.0/12</code> - Toda la red privada 172.16-31.x.x</li>
                    </ul>
                    
                    <h3>Endpoints del API:</h3>
                    <ul>
                        <li><strong>Generar token:</strong> <code>POST /wp-json/kl-wallet/v1/generate-token</code></li>
                        <li><strong>Obtener teléfono:</strong> <code>GET /wp-json/kl-wallet/v1/user-phone</code></li>
                    </ul>
                </div>
                
            </div>
        </div>
        <?php
    }
    
    /**
     * Mostrar notificaciones
     */
    private function display_notices(): void {
        $notices = get_option('kl_wallet_admin_notices', []);
        
        if (empty($notices)) {
            return;
        }
        
        foreach ($notices as $notice) {
            $class = 'notice notice-' . $notice['type'];
            $message = esc_html($notice['message']);
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
        }
        
        // Limpiar notificaciones
        delete_option('kl_wallet_admin_notices');
    }
    
    /**
     * Obtener estado del API
     */
    private function get_api_status(): array {
        return [
            'available' => kl_wallet_api_is_available(),
            'configured' => kl_wallet_is_api_configured(),
            'ip_restriction_enabled' => kl_wallet_is_ip_restriction_enabled()
        ];
    }
    
    /**
     * Obtener etiqueta del tipo de IP
     */
    private function get_ip_type_label(string $ip): string {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return 'IPv4';
        }
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return 'IPv6';
        }
        
        if (strpos($ip, '/') !== false) {
            return 'Rango CIDR';
        }
        
        return 'Desconocido';
    }
    
    /**
     * Mostrar información de depuración
     */
    public function show_debug_info(): void {
        ?>
        <div class="wrap">
            <h1>Información de Depuración - API Wallet IP Manager</h1>
            
            <p><a href="<?php echo admin_url('tools.php?page=kl-wallet-ip-manager'); ?>" class="button button-secondary">
                ← Volver al Panel Principal
            </a></p>
            
            <?php
            if (function_exists('kl_wallet_debug_show_report')) {
                kl_wallet_debug_show_report();
            } else {
                echo '<div class="notice notice-error"><p>Las funciones de depuración no están disponibles.</p></div>';
            }
            ?>
            
            <h2>Información del Sistema</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <td><strong>WordPress Version:</strong></td>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>PHP Version:</strong></td>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Theme Directory:</strong></td>
                        <td><?php echo defined('THEME_DIR') ? THEME_DIR : 'No definido'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>ABSPATH:</strong></td>
                        <td><?php echo defined('ABSPATH') ? ABSPATH : 'No definido'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Archivos Incluidos:</strong></td>
                        <td>
                            <?php
                            $included_files = get_included_files();
                            $api_files = array_filter($included_files, function($file) {
                                return strpos($file, 'API/') !== false;
                            });
                            if (!empty($api_files)) {
                                echo '<ul>';
                                foreach ($api_files as $file) {
                                    echo '<li>' . esc_html($file) . '</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo 'No se encontraron archivos de API incluidos';
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Agregar JavaScript personalizado para la funcionalidad de Google Cloud
     */
    public function add_custom_scripts(): void {
        ?>
        <script type="text/javascript">
        function toggleGoogleCloudFilters() {
            var importType = document.getElementById('import_type').value;
            var filtersRow = document.getElementById('google-filters');
            
            if (importType === 'filtered') {
                filtersRow.style.display = 'table-row';
            } else {
                filtersRow.style.display = 'none';
            }
        }
        
        // Ejecutar al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            toggleGoogleCloudFilters();
        });
        </script>
        <?php
    }
}

// Inicializar el panel de administración
new KL_Wallet_IP_Admin(); 