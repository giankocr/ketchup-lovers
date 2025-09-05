<?php
declare(strict_types=1);

/**
 * Panel de Administración para Gestión de IPs Bloqueadas
 * 
 * Este archivo proporciona una interfaz de administración para:
 * - Ver IPs bloqueadas
 * - Desbloquear IPs manualmente
 * - Ver estadísticas de respuestas 403
 * - Configurar límites de bloqueo
 * 
 * @package KetchupLovers
 * @version 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    http_response_code(403);
    exit('Acceso directo prohibido');
}

/**
 * Clase para la administración de IPs bloqueadas
 */
class KL_Wallet_IP_Block_Admin {
    
    /**
     * Constructor de la clase
     */
    public function __construct() {
        // Agregar menú de administración
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Manejar acciones de administración
        add_action('admin_post_kl_wallet_unblock_ip', [$this, 'handle_unblock_ip']);
        add_action('admin_post_kl_wallet_clear_403_count', [$this, 'handle_clear_403_count']);
        add_action('admin_post_kl_wallet_block_ip', [$this, 'handle_block_ip']);
        add_action('admin_post_kl_wallet_update_settings', [$this, 'handle_update_settings']);
        add_action('admin_post_kl_wallet_cleanup_logs', [$this, 'cleanup_old_logs']);
        add_action('admin_post_kl_wallet_export_logs', [$this, 'export_logs']);
        add_action('admin_post_kl_wallet_force_create_tables', [$this, 'handle_force_create_tables']);
        add_action('wp_ajax_kl_wallet_show_log_details', [$this, 'show_log_details']);
        add_action('wp_ajax_kl_wallet_load_more_logs', [$this, 'load_more_logs']);
    }
    
    /**
     * Agregar menú de administración
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'tools.php', // Parent slug
            'IPs Bloqueadas - Wallet API', // Page title
            'IPs Bloqueadas', // Menu title
            'manage_options', // Capability
            'kl-wallet-ip-blocks', // Menu slug
            [$this, 'admin_page'] // Callback
        );
    }
    
    /**
     * Página de administración
     */
    public function admin_page(): void {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.'));
        }
        
        // Obtener datos
        $blocked_ips = $this->get_blocked_ips();
        $ip_403_counts = $this->get_ip_403_counts();
        $settings = $this->get_settings();
        
        ?>
        <div class="wrap ">
            <h1>Gestión de IPs Bloqueadas - Wallet API</h1>
            <p class="description">
                <span class="dashicons dashicons-layout" style="color: #0073aa;"></span>
                Panel organizado en dos columnas para mejor visualización y gestión eficiente
            </p>
<span class="kl-wallet-column">
            <!-- Configuración -->
            <div class="card">
                <h2>
                    Configuración
                    <div style="float: right;">
                        <button type="button" class="button button-secondary" onclick="runDiagnostic()">
                            <span class="dashicons dashicons-admin-tools"></span> Diagnóstico
                        </button>
                    </div>
                </h2>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('kl_wallet_update_settings', 'kl_wallet_settings_nonce'); ?>
                    <input type="hidden" name="action" value="kl_wallet_update_settings">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="max_403_responses">Límite de respuestas 403</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="max_403_responses" 
                                       name="max_403_responses" 
                                       value="<?php echo esc_attr($settings['max_403_responses']); ?>"
                                       min="1" 
                                       max="1000" 
                                       class="regular-text">
                                <p class="description">
                                    Número de respuestas 403 antes de bloquear una IP automáticamente.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="block_duration">Duración del bloqueo (horas)</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="block_duration" 
                                       name="block_duration" 
                                       value="<?php echo esc_attr($settings['block_duration']); ?>"
                                       min="1" 
                                       max="720" 
                                       class="regular-text">
                                <p class="description">
                                    Tiempo en horas que una IP permanece bloqueada.
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('Guardar Configuración'); ?>
                </form>
            </div>
            
            <!-- Layout de dos columnas usando tabla -->
            <table style="width: 100%; margin-top: 20px; border-collapse: separate; border-spacing: 20px;">
                <tr>
                    <!-- Columna Izquierda - IPs Bloqueadas -->
                    <td style="width: 50%; vertical-align: top; border-right: 2px solid #e5e5e5; padding-right: 20px;">
                        <h3 style="margin-top: 0; color: #d63638; border-bottom: 2px solid #d63638; padding: 15px 0; font-size: 18px; font-weight: 600; margin-bottom: 20px; background: #f9f9f9; border-radius: 5px; text-align: center;">
                            <span class="dashicons dashicons-shield-alt"></span>
                            Gestión de IPs Bloqueadas
                        </h3>
            
            <!-- IPs Bloqueadas -->
            <div class="card" style="height: fit-content !important; margin-left: 0 !important; margin-right: 0 !important; width: 100% !important; margin-bottom: 15px !important; margin-top: 0 !important;">
                <h2>
                    <span class="dashicons dashicons-shield-alt" style="color: #d63638;"></span>
                    IPs Actualmente Bloqueadas 
                    <span class="badge" style="background: #d63638; color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px;">
                        <?php echo count($blocked_ips); ?>
                    </span>
                </h2>
                
                <?php if (empty($blocked_ips)): ?>
                    <div class="notice notice-info">
                        <p><strong>✅ No hay IPs bloqueadas actualmente.</strong></p>
                        <p>El sistema está funcionando normalmente sin bloqueos activos.</p>
                    </div>
                <?php else: ?>
                    <!-- Estadísticas rápidas -->
                    <div class="stats-container" style="display: flex !important; gap: 20px !important; margin-bottom: 20px !important; flex-wrap: wrap !important; background: #f9f9f9 !important; padding: 15px !important; border-radius: 5px !important;">
                        <?php
                        $total_blocked = count($blocked_ips);
                        $recent_blocked = 0;
                        $old_blocked = 0;
                        $current_time = current_time('timestamp');
                        
                        foreach ($blocked_ips as $ip_data) {
                            $blocked_time = strtotime($ip_data->blocked_at);
                            $hours_ago = ($current_time - $blocked_time) / 3600;
                            
                            if ($hours_ago <= 24) {
                                $recent_blocked++;
                            } else {
                                $old_blocked++;
                            }
                        }
                        ?>
                        
                        <div class="stat-box" style="background: #fff3cd !important; border: 1px solid #ffeaa7 !important; padding: 15px !important; border-radius: 5px !important; min-width: 150px !important; flex: 1 !important;">
                            <h4 style="margin: 0 0 5px 0; color: #856404;">Total Bloqueadas</h4>
                            <div style="font-size: 24px; font-weight: bold; color: #d63638;"><?php echo $total_blocked; ?></div>
                        </div>
                        
                        <div class="stat-box" style="background: #d1ecf1 !important; border: 1px solid #bee5eb !important; padding: 15px !important; border-radius: 5px !important; min-width: 150px !important; flex: 1 !important;">
                            <h4 style="margin: 0 0 5px 0; color: #0c5460;">Últimas 24h</h4>
                            <div style="font-size: 24px; font-weight: bold; color: #0c5460;"><?php echo $recent_blocked; ?></div>
                        </div>
                        
                        <div class="stat-box" style="background: #f8d7da !important; border: 1px solid #f5c6cb !important; padding: 15px !important; border-radius: 5px !important; min-width: 150px !important; flex: 1 !important;">
                            <h4 style="margin: 0 0 5px 0; color: #721c24;">Más de 24h</h4>
                            <div style="font-size: 24px; font-weight: bold; color: #721c24;"><?php echo $old_blocked; ?></div>
                        </div>
                    </div>

                    <!-- Filtros y búsqueda -->
                    <div class="tablenav top" style="margin-bottom: 15px;">
                        <div class="alignleft actions">
                            <input type="text" id="ip-search" placeholder="Buscar IP..." style="margin-right: 10px;">
                            <select id="reason-filter">
                                <option value="">Todas las razones</option>
                                <option value="IP no permitida">IP no permitida</option>
                                <option value="API Key inválida">API Key inválida</option>
                                <option value="Excedió límite">Excedió límite</option>
                            </select>
                        </div>
                        <div class="alignright">
                            <button type="button" class="button" onclick="refreshBlockedIPs()">
                                <span class="dashicons dashicons-update"></span> Actualizar
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de IPs bloqueadas -->
                    <table class="wp-list-table widefat fixed striped" id="blocked-ips-table" style="font-size: 13px !important; width: 100% !important; table-layout: fixed !important;">
                        <thead>
                            <tr>
                                <th style="width: 15%;">
                                    <span class="dashicons dashicons-admin-network"></span> IP
                                </th>
                                <th style="width: 20%;">
                                    <span class="dashicons dashicons-clock"></span> Bloqueada desde
                                </th>
                                <th style="width: 15%;">
                                    <span class="dashicons dashicons-hourglass"></span> Tiempo restante
                                </th>
                                <th style="width: 30%;">
                                    <span class="dashicons dashicons-info"></span> Razón del bloqueo
                                </th>
                                <th style="width: 20%;">
                                    <span class="dashicons dashicons-admin-tools"></span> Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($blocked_ips as $ip_data): ?>
                                <?php
                                $blocked_time = strtotime($ip_data->blocked_at);
                                $current_time = current_time('timestamp');
                                $block_duration = intval(get_option('kl_wallet_block_duration', 24)) * 3600; // Convertir a segundos
                                $time_elapsed = $current_time - $blocked_time;
                                $time_remaining = $block_duration - $time_elapsed;
                                
                                // Determinar el estado del bloqueo
                                if ($time_remaining <= 0) {
                                    $status = 'expired';
                                    $status_text = 'Expirado';
                                    $status_color = '#28a745';
                                } elseif ($time_remaining <= 3600) { // Menos de 1 hora
                                    $status = 'expiring-soon';
                                    $status_text = 'Expira pronto';
                                    $status_color = '#ffc107';
                                } else {
                                    $status = 'active';
                                    $status_text = 'Activo';
                                    $status_color = '#d63638';
                                }
                                
                                // Formatear tiempo restante
                                if ($time_remaining <= 0) {
                                    $time_remaining_text = 'Ya expiró';
                                } else {
                                    $hours = floor($time_remaining / 3600);
                                    $minutes = floor(($time_remaining % 3600) / 60);
                                    $time_remaining_text = $hours . 'h ' . $minutes . 'm';
                                }
                                
                                // Formatear fecha de bloqueo
                                $blocked_date = new DateTime($ip_data->blocked_at);
                                $blocked_date->setTimezone(new DateTimeZone(wp_timezone_string()));
                                $formatted_date = $blocked_date->format('d/m/Y H:i:s');
                                ?>
                                
                                <tr class="ip-row" data-ip="<?php echo esc_attr($ip_data->ip_address); ?>" data-reason="<?php echo esc_attr($ip_data->reason); ?>">
                                    <td>
                                        <strong><code><?php echo esc_html($ip_data->ip_address); ?></code></strong>
                                        <br>
                                        <small style="color: #666;">
                                            <?php echo $this->get_ip_info($ip_data->ip_address); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo esc_html($formatted_date); ?>
                                        <br>
                                        <small style="color: #666;">
                                            <?php echo $this->get_time_ago($ip_data->blocked_at); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="status-badge" style="background: <?php echo $status_color; ?>; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px;">
                                            <?php echo $status_text; ?>
                                        </span>
                                        <br>
                                        <small style="color: #666;">
                                            <?php echo $time_remaining_text; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="reason-text">
                                            <?php echo esc_html($ip_data->reason); ?>
                                        </div>
                                        <?php if (strpos($ip_data->reason, 'Excedió límite') !== false): ?>
                                            <small style="color: #d63638;">
                                                <span class="dashicons dashicons-warning"></span>
                                                Bloqueo automático por múltiples intentos
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                                <?php wp_nonce_field('kl_wallet_unblock_ip', 'kl_wallet_unblock_nonce'); ?>
                                                <input type="hidden" name="action" value="kl_wallet_unblock_ip">
                                                <input type="hidden" name="ip_address" value="<?php echo esc_attr($ip_data->ip_address); ?>">
                                                <button type="submit" class="button button-small button-primary" onclick="return confirm('¿Estás seguro de que quieres desbloquear la IP <?php echo esc_js($ip_data->ip_address); ?>?')">
                                                    <span class="dashicons dashicons-unlock"></span>
                                                    Desbloquear
                                                </button>
                                            </form>
                                            
                                            <button type="button" class="button button-small" onclick="showIPDetails('<?php echo esc_js($ip_data->ip_address); ?>')" style="margin-top: 5px;">
                                                <span class="dashicons dashicons-visibility"></span>
                                                Ver detalles
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Paginación -->
                    <?php if (count($blocked_ips) > 20): ?>
                        <div class="tablenav bottom">
                            <div class="tablenav-pages">
                                <span class="displaying-num"><?php echo count($blocked_ips); ?> IPs bloqueadas</span>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                    </td>
                    
                    <!-- Columna Derecha - Contadores y Logs -->
                    <td style="width: 50%; vertical-align: top; padding-left: 20px;">
                        <h3 style="margin-top: 0; color: #0073aa; border-bottom: 2px solid #0073aa; padding: 15px 0; font-size: 18px; font-weight: 600; margin-bottom: 20px; background: #f9f9f9; border-radius: 5px; text-align: center;">
                            <span class="dashicons dashicons-chart-bar"></span>
                            Contadores y Logs de Actividad
                        </h3>
            
            <!-- Contadores de Respuestas 403 -->
            <div class="card" style="height: fit-content !important; margin-left: 0 !important; margin-right: 0 !important; width: 100% !important; margin-bottom: 15px !important; margin-top: 0 !important;">
                <h2>
                    <span class="dashicons dashicons-chart-bar" style="color: #0073aa;"></span>
                    Contadores de Respuestas 403
                    <span class="badge" style="background: #0073aa; color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px;">
                        <?php echo count($ip_403_counts); ?>
                    </span>
                </h2>
                
                <?php if (empty($ip_403_counts)): ?>
                    <div class="notice notice-info">
                        <p><strong>✅ No hay contadores de respuestas 403 registrados.</strong></p>
                        <p>El sistema no ha detectado intentos fallidos recientes.</p>
                    </div>
                <?php else: ?>
                    <!-- Estadísticas de contadores -->
                    <?php
                    $total_attempts = 0;
                    $high_risk_ips = 0;
                    $recent_activity = 0;
                    $current_time = current_time('timestamp');
                    
                    foreach ($ip_403_counts as $ip_data) {
                        $total_attempts += $ip_data->count;
                        if ($ip_data->count >= $settings['max_403_responses']) {
                            $high_risk_ips++;
                        }
                        
                        $last_attempt_time = strtotime($ip_data->last_attempt);
                        if (($current_time - $last_attempt_time) <= 3600) { // Última hora
                            $recent_activity++;
                        }
                    }
                    ?>
                    
                    <div class="stats-container" style="display: flex !important; gap: 20px !important; margin-bottom: 20px !important; flex-wrap: wrap !important; background: #f9f9f9 !important; padding: 15px !important; border-radius: 5px !important;">
                        <div class="stat-box" style="background: #e3f2fd !important; border: 1px solid #bbdefb !important; padding: 15px !important; border-radius: 5px !important; min-width: 150px !important; flex: 1 !important;">
                            <h4 style="margin: 0 0 5px 0; color: #1565c0;">Total Intentos</h4>
                            <div style="font-size: 24px; font-weight: bold; color: #1565c0;"><?php echo $total_attempts; ?></div>
                        </div>
                        
                        <div class="stat-box" style="background: #fff3e0 !important; border: 1px solid #ffcc02 !important; padding: 15px !important; border-radius: 5px !important; min-width: 150px !important; flex: 1 !important;">
                            <h4 style="margin: 0 0 5px 0; color: #ef6c00;">Alto Riesgo</h4>
                            <div style="font-size: 24px; font-weight: bold; color: #ef6c00;"><?php echo $high_risk_ips; ?></div>
                        </div>
                        
                        <div class="stat-box" style="background: #f3e5f5 !important; border: 1px solid #ce93d8 !important; padding: 15px !important; border-radius: 5px !important; min-width: 150px !important; flex: 1 !important;">
                            <h4 style="margin: 0 0 5px 0; color: #7b1fa2;">Actividad Reciente</h4>
                            <div style="font-size: 24px; font-weight: bold; color: #7b1fa2;"><?php echo $recent_activity; ?></div>
                        </div>
                    </div>

                    <table class="wp-list-table widefat fixed striped" style="font-size: 13px !important; width: 100% !important; table-layout: fixed !important;">
                        <thead>
                            <tr>
                                <th style="width: 15%;">
                                    <span class="dashicons dashicons-admin-network"></span> IP
                                </th>
                                <th style="width: 15%;">
                                    <span class="dashicons dashicons-chart-line"></span> Intentos
                                </th>
                                <th style="width: 20%;">
                                    <span class="dashicons dashicons-clock"></span> Último intento
                                </th>
                                <th style="width: 15%;">
                                    <span class="dashicons dashicons-shield"></span> Estado
                                </th>
                                <th style="width: 35%;">
                                    <span class="dashicons dashicons-admin-tools"></span> Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ip_403_counts as $ip_data): ?>
                                <?php
                                $last_attempt_time = strtotime($ip_data->last_attempt);
                                $time_ago = $current_time - $last_attempt_time;
                                
                                // Determinar estado de riesgo
                                if ($ip_data->count >= $settings['max_403_responses']) {
                                    $risk_status = 'high';
                                    $risk_text = 'Alto Riesgo';
                                    $risk_color = '#d63638';
                                    $risk_icon = 'dashicons-warning';
                                } elseif ($ip_data->count >= ($settings['max_403_responses'] * 0.7)) {
                                    $risk_status = 'medium';
                                    $risk_text = 'Riesgo Medio';
                                    $risk_color = '#ffc107';
                                    $risk_icon = 'dashicons-admin-generic';
                                } else {
                                    $risk_status = 'low';
                                    $risk_text = 'Bajo Riesgo';
                                    $risk_color = '#28a745';
                                    $risk_icon = 'dashicons-yes-alt';
                                }
                                
                                // Formatear fecha del último intento
                                $last_attempt_date = new DateTime($ip_data->last_attempt);
                                $last_attempt_date->setTimezone(new DateTimeZone(wp_timezone_string()));
                                $formatted_last_attempt = $last_attempt_date->format('d/m/Y H:i:s');
                                ?>
                                
                                <tr>
                                    <td>
                                        <strong><code><?php echo esc_html($ip_data->ip_address); ?></code></strong>
                                        <br>
                                        <small style="color: #666;">
                                            <?php echo $this->get_ip_info($ip_data->ip_address); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong style="font-size: 16px;"><?php echo esc_html($ip_data->count); ?></strong>
                                        <br>
                                        <small style="color: #666;">
                                            de <?php echo $settings['max_403_responses']; ?> máximo
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo esc_html($formatted_last_attempt); ?>
                                        <br>
                                        <small style="color: #666;">
                                            <?php echo $this->get_time_ago($ip_data->last_attempt); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="risk-badge" style="background: <?php echo $risk_color; ?>; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px;">
                                            <span class="dashicons <?php echo $risk_icon; ?>"></span>
                                            <?php echo $risk_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                                <?php wp_nonce_field('kl_wallet_clear_403_count', 'kl_wallet_clear_nonce'); ?>
                                                <input type="hidden" name="action" value="kl_wallet_clear_403_count">
                                                <input type="hidden" name="ip_address" value="<?php echo esc_attr($ip_data->ip_address); ?>">
                                                <button type="submit" class="button button-small" onclick="return confirm('¿Estás seguro de que quieres limpiar el contador para la IP <?php echo esc_js($ip_data->ip_address); ?>?')">
                                                    <span class="dashicons dashicons-trash"></span>
                                                    Limpiar Contador
                                                </button>
                                            </form>
                                            
                                            <?php if ($ip_data->count >= $settings['max_403_responses']): ?>
                                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                                    <?php wp_nonce_field('kl_wallet_block_ip', 'kl_wallet_block_nonce'); ?>
                                                    <input type="hidden" name="action" value="kl_wallet_block_ip">
                                                    <input type="hidden" name="ip_address" value="<?php echo esc_attr($ip_data->ip_address); ?>">
                                                    <input type="hidden" name="reason" value="Bloqueo manual desde panel de administración">
                                                    <button type="submit" class="button button-small button-primary" onclick="return confirm('¿Estás seguro de que quieres bloquear manualmente la IP <?php echo esc_js($ip_data->ip_address); ?>?')">
                                                        <span class="dashicons dashicons-shield-alt"></span>
                                                        Bloquear IP
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Logs Completos de Respuestas 403 -->
            <div class="card" style="height: fit-content !important; margin-left: 0 !important; margin-right: 0 !important; width: 100% !important; margin-bottom: 15px !important; margin-top: 0 !important;">
                <h2>
                    <span class="dashicons dashicons-list-view" style="color: #6c757d;"></span>
                    Logs Completos de Respuestas 403
                    <span class="badge" style="background: #6c757d; color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px;">
                        <?php echo $this->get_logs_count(); ?>
                    </span>
                </h2>
                
                <?php
                $logs_stats = $this->get_logs_stats();
                $recent_logs = $this->get_recent_logs(50);
                ?>
                
                <!-- Estadísticas de logs -->
                <div class="stats-container" style="display: flex !important; gap: 20px !important; margin-bottom: 20px !important; flex-wrap: wrap !important; background: #f9f9f9 !important; padding: 15px !important; border-radius: 5px !important;">
                    <div class="stat-box" style="background: #e8f5e8 !important; border: 1px solid #c3e6c3 !important; padding: 15px !important; border-radius: 5px !important; min-width: 150px !important; flex: 1 !important;">
                        <h4 style="margin: 0 0 5px 0; color: #2d5a2d;">Total Logs</h4>
                        <div style="font-size: 24px; font-weight: bold; color: #2d5a2d;"><?php echo number_format($logs_stats['total_logs']); ?></div>
                    </div>
                    
                    <div class="stat-box" style="background: #fff3cd !important; border: 1px solid #ffeaa7 !important; padding: 15px !important; border-radius: 5px !important; min-width: 150px !important; flex: 1 !important;">
                        <h4 style="margin: 0 0 5px 0; color: #856404;">Hoy</h4>
                        <div style="font-size: 24px; font-weight: bold; color: #856404;"><?php echo number_format($logs_stats['today_logs']); ?></div>
                    </div>
                    
                    <div class="stat-box" style="background: #d1ecf1 !important; border: 1px solid #bee5eb !important; padding: 15px !important; border-radius: 5px !important; min-width: 150px !important; flex: 1 !important;">
                        <h4 style="margin: 0 0 5px 0; color: #0c5460;">Esta Semana</h4>
                        <div style="font-size: 24px; font-weight: bold; color: #0c5460;"><?php echo number_format($logs_stats['week_logs']); ?></div>
                    </div>
                    
                    <div class="stat-box" style="background: #f8d7da !important; border: 1px solid #f5c6cb !important; padding: 15px !important; border-radius: 5px !important; min-width: 150px !important; flex: 1 !important;">
                        <h4 style="margin: 0 0 5px 0; color: #721c24;">Este Mes</h4>
                        <div style="font-size: 24px; font-weight: bold; color: #721c24;"><?php echo number_format($logs_stats['month_logs']); ?></div>
                    </div>
                </div>

                <!-- Filtros para logs -->
                <div class="tablenav top" style="margin-bottom: 15px;">
                    <div class="alignleft actions">
                        <input type="text" id="log-ip-search" placeholder="Buscar por IP..." style="margin-right: 10px;">
                        <select id="log-endpoint-filter">
                            <option value="">Todos los endpoints</option>
                            <option value="generate-token">Generate Token</option>
                            <option value="kl-wallet-api">KL Wallet API</option>
                            <option value="unknown">Unknown</option>
                        </select>
                        <select id="log-reason-filter">
                            <option value="">Todas las razones</option>
                            <option value="IP no permitida">IP no permitida</option>
                            <option value="API Key inválida">API Key inválida</option>
                            <option value="Excedió límite">Excedió límite</option>
                        </select>
                    </div>
                                    <div class="alignright">
                    <button type="button" class="button" onclick="forceCreateTables()">
                        <span class="dashicons dashicons-database-add"></span> Crear Tablas
                    </button>
                    <button type="button" class="button" onclick="exportLogs()">
                        <span class="dashicons dashicons-download"></span> Exportar
                    </button>
                    <button type="button" class="button" onclick="cleanupOldLogs()">
                        <span class="dashicons dashicons-trash"></span> Limpiar Antiguos
                    </button>
                </div>
                </div>

                <!-- Tabla de logs -->
                <table class="wp-list-table widefat fixed striped" id="logs-table" style="font-size: 13px !important; width: 100% !important; table-layout: fixed !important;">
                    <thead>
                        <tr>
                            <th style="width: 12%;">
                                <span class="dashicons dashicons-clock"></span> Fecha/Hora
                            </th>
                            <th style="width: 12%;">
                                <span class="dashicons dashicons-admin-network"></span> IP
                            </th>
                            <th style="width: 15%;">
                                <span class="dashicons dashicons-admin-tools"></span> Endpoint
                            </th>
                            <th style="width: 20%;">
                                <span class="dashicons dashicons-info"></span> Razón
                            </th>
                            <th style="width: 15%;">
                                <span class="dashicons dashicons-admin-links"></span> Método
                            </th>
                            <th style="width: 15%;">
                                <span class="dashicons dashicons-admin-users"></span> User Agent
                            </th>
                            <th style="width: 11%;">
                                <span class="dashicons dashicons-visibility"></span> Detalles
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_logs)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px;">
                                    <p>No hay logs de respuestas 403 registrados.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_logs as $log): ?>
                                <?php
                                $log_date = new DateTime($log['timestamp']);
                                $log_date->setTimezone(new DateTimeZone(wp_timezone_string()));
                                $formatted_date = $log_date->format('d/m/Y H:i:s');
                                $time_ago = $this->get_time_ago($log['timestamp']);
                                
                                // Determinar color del endpoint
                                $endpoint_colors = [
                                    'generate-token' => '#0073aa',
                                    'kl-wallet-api' => '#d63638',
                                    'unknown' => '#6c757d'
                                ];
                                $endpoint_color = $endpoint_colors[$log['endpoint']] ?? '#6c757d';
                                ?>
                                
                                <tr class="log-row" data-ip="<?php echo esc_attr($log['ip_address']); ?>" data-endpoint="<?php echo esc_attr($log['endpoint']); ?>" data-reason="<?php echo esc_attr($log['reason']); ?>">
                                    <td>
                                        <?php echo esc_html($formatted_date); ?>
                                        <br>
                                        <small style="color: #666;">
                                            <?php echo $time_ago; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><code><?php echo esc_html($log['ip_address']); ?></code></strong>
                                        <br>
                                        <small style="color: #666;">
                                            <?php echo $this->get_ip_info($log['ip_address']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="endpoint-badge" style="background: <?php echo $endpoint_color; ?>; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px;">
                                            <?php echo esc_html($log['endpoint']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="reason-text">
                                            <?php echo esc_html($log['reason']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <code><?php echo esc_html($log['request_method']); ?></code>
                                    </td>
                                    <td>
                                        <div class="user-agent-text" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo esc_html($log['user_agent']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small" onclick="showLogDetails(<?php echo $log['id']; ?>)">
                                            <span class="dashicons dashicons-visibility"></span>
                                            Ver
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Paginación -->
                <?php if (count($recent_logs) >= 50): ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <span class="displaying-num">Mostrando los últimos 50 logs</span>
                            <a href="#" onclick="loadMoreLogs()" class="button">Cargar más</a>
                        </div>
                    </div>
                <?php endif; ?>
                </div>
                    </td>
                </tr>
            </table>
            </span>
        </div>
        <?php
    }
    
    /**
     * Obtener IPs bloqueadas
     * 
     * @return array
     */
    private function get_blocked_ips(): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'kl_wallet_blocked_ips';
        
        return $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY blocked_at DESC"
        );
    }
    
    /**
     * Obtener contadores de respuestas 403
     * 
     * @return array
     */
    private function get_ip_403_counts(): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'kl_wallet_ip_403_counts';
        
        return $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY count DESC, last_attempt DESC"
        );
    }
    
    /**
     * Obtener configuración actual
     * 
     * @return array
     */
    private function get_settings(): array {
        return [
            'max_403_responses' => intval(get_option('kl_wallet_max_403_responses', 100)),
            'block_duration' => intval(get_option('kl_wallet_block_duration', 24))
        ];
    }

    /**
     * Obtener información de la IP
     * 
     * @param string $ip_address IP a verificar
     * @return string
     */
    private function get_ip_info(string $ip_address): string {
        // Verificar si es IP privada
        if (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
            return 'IP Pública';
        } else {
            return 'IP Privada';
        }
    }

    /**
     * Obtener tiempo transcurrido desde una fecha
     * 
     * @param string $date_string Fecha en formato MySQL
     * @return string
     */
    private function get_time_ago(string $date_string): string {
        $date = new DateTime($date_string);
        $now = new DateTime();
        $diff = $now->diff($date);
        
        if ($diff->days > 0) {
            return 'Hace ' . $diff->days . ' día' . ($diff->days > 1 ? 's' : '');
        } elseif ($diff->h > 0) {
            return 'Hace ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
        } elseif ($diff->i > 0) {
            return 'Hace ' . $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
        } else {
            return 'Hace unos segundos';
        }
    }
    
    /**
     * Manejar desbloqueo de IP
     */
    public function handle_unblock_ip(): void {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['kl_wallet_unblock_nonce'], 'kl_wallet_unblock_ip')) {
            wp_die('Verificación de seguridad fallida.');
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para realizar esta acción.');
        }
        
        $ip_address = sanitize_text_field($_POST['ip_address']);
        
        if (empty($ip_address)) {
            wp_die('IP no válida.');
        }
        
        // Desbloquear IP
        global $wpdb;
        $table_name = $wpdb->prefix . 'kl_wallet_blocked_ips';
        
        $result = $wpdb->delete(
            $table_name,
            ['ip_address' => $ip_address],
            ['%s']
        );
        
        if ($result !== false) {
            add_action('admin_notices', function() use ($ip_address) {
                echo '<div class="notice notice-success is-dismissible"><p>IP <code>' . esc_html($ip_address) . '</code> desbloqueada exitosamente.</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($ip_address) {
                echo '<div class="notice notice-error is-dismissible"><p>Error al desbloquear IP <code>' . esc_html($ip_address) . '</code>.</p></div>';
            });
        }
        
        wp_redirect(admin_url('tools.php?page=kl-wallet-ip-blocks'));
        exit;
    }
    
    /**
     * Manejar limpieza de contador 403
     */
    public function handle_clear_403_count(): void {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['kl_wallet_clear_nonce'], 'kl_wallet_clear_403_count')) {
            wp_die('Verificación de seguridad fallida.');
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para realizar esta acción.');
        }
        
        $ip_address = sanitize_text_field($_POST['ip_address']);
        
        if (empty($ip_address)) {
            wp_die('IP no válida.');
        }
        
        // Limpiar contador
        global $wpdb;
        $table_name = $wpdb->prefix . 'kl_wallet_ip_403_counts';
        
        $result = $wpdb->delete(
            $table_name,
            ['ip_address' => $ip_address],
            ['%s']
        );
        
        if ($result !== false) {
            add_action('admin_notices', function() use ($ip_address) {
                echo '<div class="notice notice-success is-dismissible"><p>Contador de IP <code>' . esc_html($ip_address) . '</code> limpiado exitosamente.</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($ip_address) {
                echo '<div class="notice notice-error is-dismissible"><p>Error al limpiar contador de IP <code>' . esc_html($ip_address) . '</code>.</p></div>';
            });
        }
        
        wp_redirect(admin_url('tools.php?page=kl-wallet-ip-blocks'));
        exit;
    }

    /**
     * Manejar bloqueo manual de IP
     */
    public function handle_block_ip(): void {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['kl_wallet_block_nonce'], 'kl_wallet_block_ip')) {
            wp_die('Verificación de seguridad fallida.');
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para realizar esta acción.');
        }
        
        $ip_address = sanitize_text_field($_POST['ip_address']);
        $reason = sanitize_textarea_field($_POST['reason']);
        
        if (empty($ip_address)) {
            wp_die('IP no válida.');
        }
        
        // Bloquear IP usando la API
        $api = new KL_Wallet_API();
        $result = $api->block_ip_public($ip_address, $reason);
        
        if ($result) {
            add_action('admin_notices', function() use ($ip_address) {
                echo '<div class="notice notice-success is-dismissible"><p>IP <code>' . esc_html($ip_address) . '</code> bloqueada manualmente exitosamente.</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($ip_address) {
                echo '<div class="notice notice-error is-dismissible"><p>Error al bloquear IP <code>' . esc_html($ip_address) . '</code>.</p></div>';
            });
        }
        
        wp_redirect(admin_url('tools.php?page=kl-wallet-ip-blocks'));
        exit;
    }
    
    /**
     * Manejar actualización de configuración
     */
    public function handle_update_settings(): void {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['kl_wallet_settings_nonce'], 'kl_wallet_update_settings')) {
            wp_die('Verificación de seguridad fallida.');
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para realizar esta acción.');
        }
        
        $max_403_responses = intval($_POST['max_403_responses']);
        $block_duration = intval($_POST['block_duration']);
        
        // Validar valores
        if ($max_403_responses < 1 || $max_403_responses > 1000) {
            wp_die('Límite de respuestas 403 no válido.');
        }
        
        if ($block_duration < 1 || $block_duration > 720) {
            wp_die('Duración de bloqueo no válida.');
        }
        
        // Guardar configuración
        update_option('kl_wallet_max_403_responses', $max_403_responses);
        update_option('kl_wallet_block_duration', $block_duration);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Configuración guardada exitosamente.</p></div>';
        });
        
        wp_redirect(admin_url('tools.php?page=kl-wallet-ip-blocks'));
        exit;
    }

    /**
     * Obtener el número total de logs
     * 
     * @return int
     */
    private function get_logs_count(): int {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kl_wallet_ip_403_logs';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        // Convertir a entero y manejar valores null
        return (int) ($count ?? 0);
    }

    /**
     * Obtener estadísticas de logs (total, hoy, semana, mes)
     * 
     * @return array
     */
    private function get_logs_stats(): array {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kl_wallet_ip_403_logs';

        $current_time = current_time('mysql');
        
        $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $today_logs = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(timestamp) = DATE(%s)",
            $current_time
        ));
        $week_logs = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE timestamp >= DATE_SUB(%s, INTERVAL 7 DAY)",
            $current_time
        ));
        $month_logs = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE timestamp >= DATE_SUB(%s, INTERVAL 30 DAY)",
            $current_time
        ));

        return [
            'total_logs' => (int) ($total_logs ?? 0),
            'today_logs' => (int) ($today_logs ?? 0),
            'week_logs' => (int) ($week_logs ?? 0),
            'month_logs' => (int) ($month_logs ?? 0)
        ];
    }

    /**
     * Obtener los últimos logs (limitado por $limit)
     * 
     * @param int $limit Número de logs a obtener
     * @return array
     */
    private function get_recent_logs(int $limit): array {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kl_wallet_ip_403_logs';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY id DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }

    /**
     * Limpiar logs antiguos (por ejemplo, logs más antiguos que 30 días)
     */
    public function cleanup_old_logs(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kl_wallet_api_logs';
        $thirty_days_ago = strtotime('-30 days');
        
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE timestamp < %d",
                $thirty_days_ago
            )
        );

        if ($result !== false) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Logs antiguos limpiados exitosamente.</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>Error al limpiar logs antiguos.</p></div>';
            });
        }
    }

    /**
     * Forzar la creación de tablas de base de datos
     */
    public function handle_force_create_tables(): void {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['kl_wallet_force_create_nonce'], 'kl_wallet_force_create_tables')) {
            wp_die('Verificación de seguridad fallida.');
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para realizar esta acción.');
        }
        
        // Forzar creación de tablas
        global $wpdb;
        $api = new KL_Wallet_API();
        $result = $api->force_create_tables_public();
        
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Tablas de base de datos creadas/actualizadas exitosamente.</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>Error al crear las tablas de base de datos.</p></div>';
            });
        }
        
        wp_redirect(admin_url('tools.php?page=kl-wallet-ip-blocks'));
        exit;
    }

    /**
     * Exportar logs a un archivo CSV
     */
    public function export_logs(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kl_wallet_api_logs';
        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");

        if (empty($logs)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible"><p>No hay logs para exportar.</p></div>';
            });
            return;
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="kl_wallet_api_logs.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Fecha/Hora', 'IP', 'Endpoint', 'Razón', 'Método', 'User Agent']);

        foreach ($logs as $log) {
            fputcsv($output, [
                $log->id,
                $log->timestamp,
                $log->ip_address,
                $log->endpoint,
                $log->reason,
                $log->request_method,
                $log->user_agent
            ]);
        }
        fclose($output);
        exit;
    }

    /**
     * Mostrar detalles de un log en un modal
     */
    public function show_log_details(): void {
        if (isset($_POST['log_id'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'kl_wallet_api_logs';
            $log_id = intval($_POST['log_id']);

            $log = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE id = %d",
                    $log_id
                )
            );

            if ($log) {
                ?>
                <div class="ip-details-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">
                    <div style="background: white; padding: 20px; border-radius: 5px; max-width: 600px; width: 90%;">
                        <h3>Detalles del Log: <?php echo esc_html($log->id); ?></h3>
                        <p><strong>Fecha/Hora:</strong> <?php echo esc_html($log->timestamp); ?></p>
                        <p><strong>IP:</strong> <code><?php echo esc_html($log->ip_address); ?></code></p>
                        <p><strong>Endpoint:</strong> <?php echo esc_html($log->endpoint); ?></p>
                        <p><strong>Razón:</strong> <?php echo esc_html($log->reason); ?></p>
                        <p><strong>Método:</strong> <?php echo esc_html($log->request_method); ?></p>
                        <p><strong>User Agent:</strong> <?php echo esc_html($log->user_agent); ?></p>
                        <div style="text-align: right; margin-top: 20px;">
                            <button onclick="closeIPDetails()" class="button">Cerrar</button>
                        </div>
                    </div>
                </div>
                <?php
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>Log no encontrado.</p></div>';
                });
            }
        }
    }

    /**
     * Cargar más logs cuando el usuario hace clic en "Cargar más"
     */
    public function load_more_logs(): void {
        $current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $offset = ($current_page - 1) * 50; // Mostrar 50 logs por página

        global $wpdb;
        $table_name = $wpdb->prefix . 'kl_wallet_api_logs';
        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY id DESC LIMIT %d OFFSET %d",
                50, // Limite de logs por carga
                $offset
            ),
            ARRAY_A
        );

        if (!empty($logs)) {
            ?>
            <script type="text/javascript">
                $(document).ready(function($) {
                    $('#logs-table tbody').append('<?php echo implode('', array_map(function($log) {
                        $log_date = new DateTime($log['timestamp']);
                        $log_date->setTimezone(new DateTimeZone(wp_timezone_string()));
                        $formatted_date = $log_date->format('d/m/Y H:i:s');
                        $time_ago = $this->get_time_ago($log['timestamp']);
                        
                        // Determinar color del endpoint
                        $endpoint_colors = [
                            'generate-token' => '#0073aa',
                            'kl-wallet-api' => '#d63638',
                            'unknown' => '#6c757d'
                        ];
                        $endpoint_color = $endpoint_colors[$log['endpoint']] ?? '#6c757d';
                        return '<tr class="log-row" data-ip="' . esc_attr($log['ip_address']) . '" data-endpoint="' . esc_attr($log['endpoint']) . '" data-reason="' . esc_attr($log['reason']) . '">' .
                               '<td>' . esc_html($formatted_date) . '<br><small style="color: #666;">' . esc_html($time_ago) . '</small></td>' .
                               '<td><strong><code>' . esc_html($log['ip_address']) . '</code></strong><br><small style="color: #666;">' . esc_html($this->get_ip_info($log['ip_address'])) . '</small></td>' .
                               '<td><span class="endpoint-badge" style="background: ' . esc_attr($endpoint_color) . '; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px;">' . esc_html($log['endpoint']) . '</span></td>' .
                               '<td><div class="reason-text">' . esc_html($log['reason']) . '</div></td>' .
                               '<td><code>' . esc_html($log['request_method']) . '</code></td>' .
                               '<td><div class="user-agent-text" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' . esc_html($log['user_agent']) . '</div></td>' .
                               '<td><button type="button" class="button button-small" onclick="showLogDetails(' . esc_attr($log['id']) . ')">Ver</button></td>' .
                               '</tr>';
                    }, $logs)); ?>');
                    $('#logs-table').trigger('update'); // Re-render datatables
                });
            </script>
            <?php
        } else {
            echo '<div class="notice notice-info" style="margin-top: 20px;">No hay más logs para cargar.</div>';
        }
    }
}

// Inicializar la clase de administración
new KL_Wallet_IP_Block_Admin();

// Agregar estilos críticos en el head
add_action('admin_head', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'kl-wallet-ip-blocks') {
        ?>
        <style type="text/css">
        /* Estilos críticos para layout de tabla en el head */
        .wrap table[style*="border-collapse: separate"] {
            display: table !important;
            width: 100% !important;
            margin-top: 20px !important;
            border-collapse: separate !important;
            border-spacing: 20px !important;
        }
        
        .wrap table[style*="border-collapse: separate"] td {
            display: table-cell !important;
            vertical-align: top !important;
        }
        
        .wrap table[style*="border-collapse: separate"] td:first-child {
            width: 50% !important;
            border-right: 2px solid #e5e5e5 !important;
            padding-right: 20px !important;
        }
        
        .wrap table[style*="border-collapse: separate"] td:last-child {
            width: 50% !important;
            padding-left: 20px !important;
        }
        
        /* Estilos críticos que se aplican inmediatamente en el head */
        .kl-wallet-two-columns {
            display: flex !important;
            gap: 20px !important;
            margin-top: 20px !important;
            width: 100% !important;
            max-width: 100% !important;
            overflow: hidden !important;
        }
        
        .kl-wallet-column {
            flex: 1 !important;
            min-width: 0 !important;
            width: 50% !important;
            padding: 0 10px !important;
            box-sizing: border-box !important;
        }
        
        .kl-wallet-column:first-child {
            border-right: 2px solid #e5e5e5 !important;
            padding-right: 20px !important;
        }
        
        .kl-wallet-column:last-child {
            padding-left: 20px !important;
        }
        
        .kl-wallet-column .card {
            height: fit-content !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 100% !important;
            margin-bottom: 15px !important;
            margin-top: 0 !important;
        }
        
        .kl-wallet-column .wp-list-table {
            font-size: 13px !important;
            width: 100% !important;
            table-layout: fixed !important;
        }
        
        .kl-wallet-column .stats-container {
            display: flex !important;
            gap: 20px !important;
            margin-bottom: 20px !important;
            flex-wrap: wrap !important;
            background: #f9f9f9 !important;
            padding: 15px !important;
            border-radius: 5px !important;
        }
        
        .kl-wallet-column .stat-box {
            flex: 1 !important;
            min-width: 120px !important;
        }
        
        @media (max-width: 1200px) {
            .kl-wallet-two-columns {
                flex-direction: column !important;
            }
            
            .kl-wallet-column {
                width: 100% !important;
                padding: 0 !important;
            }
            
            .kl-wallet-column:first-child {
                border-right: none !important;
                border-bottom: 2px solid #e5e5e5 !important;
                padding-right: 0 !important;
                padding-bottom: 20px !important;
                margin-bottom: 20px !important;
            }
            
            .kl-wallet-column:last-child {
                padding-left: 0 !important;
            }
        }
        </style>
        <?php
    }
});

// JavaScript para funcionalidad interactiva
add_action('admin_footer', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'kl-wallet-ip-blocks') {
        ?>
        <script type="text/javascript">
        // Script que se ejecuta inmediatamente para forzar el layout de tabla
        (function() {
            function forceTableLayout() {
                var layoutTable = document.querySelector('table[style*="border-collapse: separate"]');
                
                if (layoutTable) {
                    layoutTable.style.display = 'table';
                    layoutTable.style.width = '100%';
                    layoutTable.style.marginTop = '20px';
                    layoutTable.style.borderCollapse = 'separate';
                    layoutTable.style.borderSpacing = '20px';
                    
                    var cells = layoutTable.querySelectorAll('td');
                    if (cells.length >= 2) {
                        cells[0].style.display = 'table-cell';
                        cells[0].style.width = '50%';
                        cells[0].style.verticalAlign = 'top';
                        cells[0].style.borderRight = '2px solid #e5e5e5';
                        cells[0].style.paddingRight = '20px';
                        
                        cells[1].style.display = 'table-cell';
                        cells[1].style.width = '50%';
                        cells[1].style.verticalAlign = 'top';
                        cells[1].style.paddingLeft = '20px';
                    }
                }
            }
            
            // Ejecutar inmediatamente
            forceTableLayout();
            
            // Ejecutar cuando el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', forceTableLayout);
            }
            
            // Ejecutar después de un delay
            setTimeout(forceTableLayout, 50);
            setTimeout(forceTableLayout, 200);
            setTimeout(forceTableLayout, 500);
            setTimeout(forceTableLayout, 1000);
        })();
        
        jQuery(document).ready(function($) {
            // Forzar layout de dos columnas con JavaScript
            function forceTwoColumnLayout() {
                $('.kl-wallet-two-columns').css({
                    'display': 'flex',
                    'gap': '20px',
                    'margin-top': '20px',
                    'width': '100%',
                    'max-width': '100%',
                    'overflow': 'hidden'
                });
                
                $('.kl-wallet-column').css({
                    'flex': '1',
                    'min-width': '0',
                    'width': '50%',
                    'padding': '0 10px',
                    'box-sizing': 'border-box'
                });
                
                $('.kl-wallet-column:first-child').css({
                    'border-right': '2px solid #e5e5e5',
                    'padding-right': '20px'
                });
                
                $('.kl-wallet-column:last-child').css({
                    'padding-left': '20px'
                });
                
                // Aplicar estilos a las cards
                $('.kl-wallet-column .card').css({
                    'height': 'fit-content',
                    'margin-left': '0',
                    'margin-right': '0',
                    'width': '100%',
                    'margin-bottom': '15px',
                    'margin-top': '0'
                });
                
                // Aplicar estilos a las tablas
                $('.kl-wallet-column .wp-list-table').css({
                    'font-size': '13px',
                    'width': '100%',
                    'table-layout': 'fixed'
                });
                
                // Aplicar estilos a las estadísticas
                $('.kl-wallet-column .stats-container').css({
                    'display': 'flex',
                    'gap': '20px',
                    'margin-bottom': '20px',
                    'flex-wrap': 'wrap',
                    'background': '#f9f9f9',
                    'padding': '15px',
                    'border-radius': '5px'
                });
                
                $('.kl-wallet-column .stat-box').css({
                    'flex': '1',
                    'min-width': '120px'
                });
            }
            
            // Ejecutar inmediatamente
            forceTwoColumnLayout();
            
            // Ejecutar después de un pequeño delay para asegurar que se aplique
            setTimeout(forceTwoColumnLayout, 100);
            setTimeout(forceTwoColumnLayout, 500);
            setTimeout(forceTwoColumnLayout, 1000);
            
            // Ejecutar cuando se redimensiona la ventana
            $(window).resize(function() {
                if ($(window).width() <= 1200) {
                    $('.kl-wallet-two-columns').css('flex-direction', 'column');
                    $('.kl-wallet-column').css({
                        'width': '100%',
                        'padding': '0'
                    });
                    $('.kl-wallet-column:first-child').css({
                        'border-right': 'none',
                        'border-bottom': '2px solid #e5e5e5',
                        'padding-right': '0',
                        'padding-bottom': '20px',
                        'margin-bottom': '20px'
                    });
                    $('.kl-wallet-column:last-child').css('padding-left', '0');
                } else {
                    $('.kl-wallet-two-columns').css('flex-direction', 'row');
                    forceTwoColumnLayout();
                }
            });
            
            // Búsqueda de IPs
            $('#ip-search').on('keyup', function() {
                var searchTerm = $(this).val().toLowerCase();
                $('.ip-row').each(function() {
                    var ip = $(this).data('ip').toLowerCase();
                    if (ip.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Filtro por razón
            $('#reason-filter').on('change', function() {
                var filterValue = $(this).val();
                if (filterValue === '') {
                    $('.ip-row').show();
                } else {
                    $('.ip-row').each(function() {
                        var reason = $(this).data('reason');
                        if (reason.includes(filterValue)) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                }
            });

            // Actualizar tiempo restante cada minuto
            setInterval(function() {
                updateRemainingTimes();
            }, 60000);

            // Función para actualizar tiempos restantes
            function updateRemainingTimes() {
                $('.status-badge').each(function() {
                    var row = $(this).closest('tr');
                    var blockedTime = new Date(row.find('td:nth-child(2)').text());
                    var now = new Date();
                    var blockDuration = <?php echo intval(get_option('kl_wallet_block_duration', 24)) * 3600; ?>;
                    var timeElapsed = Math.floor((now - blockedTime) / 1000);
                    var timeRemaining = blockDuration - timeElapsed;

                    if (timeRemaining <= 0) {
                        $(this).text('Expirado').css('background', '#28a745');
                        row.find('td:nth-child(3) small').text('Ya expiró');
                    } else {
                        var hours = Math.floor(timeRemaining / 3600);
                        var minutes = Math.floor((timeRemaining % 3600) / 60);
                        var timeText = hours + 'h ' + minutes + 'm';
                        row.find('td:nth-child(3) small').text(timeText);

                        if (timeRemaining <= 3600) {
                            $(this).text('Expira pronto').css('background', '#ffc107');
                        }
                    }
                });
            }
        });

        // Función global para actualizar la página
        function refreshBlockedIPs() {
            location.reload();
        }

        // Función para mostrar detalles de IP
        function showIPDetails(ip) {
            // Crear modal con detalles de la IP
            var modal = $('<div class="ip-details-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">' +
                '<div style="background: white; padding: 20px; border-radius: 5px; max-width: 500px; width: 90%;">' +
                '<h3>Detalles de IP: ' + ip + '</h3>' +
                '<p><strong>Estado:</strong> <span id="ip-status">Verificando...</span></p>' +
                '<p><strong>Última actividad:</strong> <span id="ip-last-activity">Verificando...</span></p>' +
                '<p><strong>Intentos fallidos:</strong> <span id="ip-attempts">Verificando...</span></p>' +
                '<div style="text-align: right; margin-top: 20px;">' +
                '<button onclick="closeIPDetails()" class="button">Cerrar</button>' +
                '</div>' +
                '</div>' +
                '</div>');
            
            $('body').append(modal);

            // Aquí podrías hacer una llamada AJAX para obtener más detalles
            // Por ahora, mostraremos información básica
            $('#ip-status').text('Bloqueada');
            $('#ip-last-activity').text('Hoy');
            $('#ip-attempts').text('Múltiples');
        }

        function closeIPDetails() {
            $('.ip-details-modal').remove();
        }

        // Funciones para logs
        function showLogDetails(logId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kl_wallet_show_log_details',
                    log_id: logId,
                    nonce: '<?php echo wp_create_nonce('kl_wallet_log_details'); ?>'
                },
                success: function(response) {
                    $('body').append(response);
                },
                error: function() {
                    alert('Error al cargar los detalles del log');
                }
            });
        }

        function exportLogs() {
            window.location.href = '<?php echo admin_url('admin-post.php'); ?>?action=kl_wallet_export_logs&nonce=<?php echo wp_create_nonce('kl_wallet_export_logs'); ?>';
        }

        function forceCreateTables() {
            if (confirm('¿Estás seguro de que quieres forzar la creación de las tablas de base de datos? Esto puede tomar unos segundos.')) {
                // Crear un formulario temporal para enviar la solicitud
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo admin_url('admin-post.php'); ?>';
                
                var actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'kl_wallet_force_create_tables';
                form.appendChild(actionInput);
                
                var nonceInput = document.createElement('input');
                nonceInput.type = 'hidden';
                nonceInput.name = 'kl_wallet_force_create_nonce';
                nonceInput.value = '<?php echo wp_create_nonce('kl_wallet_force_create_tables'); ?>';
                form.appendChild(nonceInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        function cleanupOldLogs() {
            if (confirm('¿Estás seguro de que quieres limpiar los logs antiguos? Esta acción no se puede deshacer.')) {
                window.location.href = '<?php echo admin_url('admin-post.php'); ?>?action=kl_wallet_cleanup_logs&nonce=<?php echo wp_create_nonce('kl_wallet_cleanup_logs'); ?>';
            }
        }

        function runDiagnostic() {
            // Abrir el script de diagnóstico en una nueva ventana
            var diagnosticUrl = '<?php echo get_template_directory_uri(); ?>/API/debug-403-logs.php';
            window.open(diagnosticUrl, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
        }

        function loadMoreLogs() {
            // Implementar carga de más logs
            alert('Funcionalidad de carga de más logs en desarrollo');
        }

        // Filtros para logs
        $('#log-ip-search').on('keyup', function() {
            var searchTerm = $(this).val().toLowerCase();
            $('.log-row').each(function() {
                var ip = $(this).data('ip').toLowerCase();
                if (ip.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        $('#log-endpoint-filter').on('change', function() {
            var filterValue = $(this).val();
            if (filterValue === '') {
                $('.log-row').show();
            } else {
                $('.log-row').each(function() {
                    var endpoint = $(this).data('endpoint');
                    if (endpoint === filterValue) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });

        $('#log-reason-filter').on('change', function() {
            var filterValue = $(this).val();
            if (filterValue === '') {
                $('.log-row').show();
            } else {
                $('.log-row').each(function() {
                    var reason = $(this).data('reason');
                    if (reason.includes(filterValue)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });
        </script>

        <style>
        /* Estilos críticos para layout de tabla */
        .wrap table[style*="border-collapse: separate"] {
            display: table !important;
            width: 100% !important;
            margin-top: 20px !important;
            border-collapse: separate !important;
            border-spacing: 20px !important;
        }
        
        .wrap table[style*="border-collapse: separate"] td {
            display: table-cell !important;
            vertical-align: top !important;
        }
        
        .wrap table[style*="border-collapse: separate"] td:first-child {
            width: 50% !important;
            border-right: 2px solid #e5e5e5 !important;
            padding-right: 20px !important;
        }
        
        .wrap table[style*="border-collapse: separate"] td:last-child {
            width: 50% !important;
            padding-left: 20px !important;
        }
        
        /* Estilos críticos que se aplican inmediatamente */
        .kl-wallet-two-columns {
            display: flex !important;
            gap: 20px !important;
            margin-top: 20px !important;
            width: 100% !important;
            max-width: 100% !important;
            overflow: hidden !important;
        }
        
        .kl-wallet-column {
            flex: 1 !important;
            min-width: 0 !important;
            width: 50% !important;
            padding: 0 10px !important;
            box-sizing: border-box !important;
        }
        
        .kl-wallet-column:first-child {
            border-right: 2px solid #e5e5e5 !important;
            padding-right: 20px !important;
        }
        
        .kl-wallet-column:last-child {
            padding-left: 20px !important;
        }
        
        .kl-wallet-column .card {
            height: fit-content !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            margin-bottom: 15px !important;
            margin-top: 0 !important;
        }
        
        .kl-wallet-column .wp-list-table {
            font-size: 13px !important;
            width: 100% !important;
            table-layout: fixed !important;
        }
        
        .kl-wallet-column .stats-container {
            display: flex !important;
            gap: 20px !important;
            margin-bottom: 20px !important;
            flex-wrap: wrap !important;
            background: #f9f9f9 !important;
            padding: 15px !important;
            border-radius: 5px !important;
        }
        
        .kl-wallet-column .stat-box {
            flex: 1 !important;
            min-width: 120px !important;
        }
        
        /* Media query para responsive design */
        @media (max-width: 1200px) {
            .kl-wallet-two-columns {
                flex-direction: column !important;
            }
            
            .kl-wallet-column {
                width: 100% !important;
                padding: 0 !important;
            }
            
            .kl-wallet-column:first-child {
                border-right: none !important;
                border-bottom: 2px solid #e5e5e5 !important;
                padding-right: 0 !important;
                padding-bottom: 20px !important;
                margin-bottom: 20px !important;
            }
            
            .kl-wallet-column:last-child {
                padding-left: 0 !important;
            }
        }
        /* Layout de dos columnas */
        .kl-wallet-two-columns {
            display: flex !important;
            gap: 20px !important;
            margin-top: 20px !important;
            width: 100% !important;
            max-width: 100% !important;
            overflow: hidden !important;
        }
        
        .kl-wallet-column {
            flex: 1 !important;
            min-width: 0 !important;
            width: 50% !important;
            padding: 0 10px !important;
            box-sizing: border-box !important;
        }
        
        /* Borde visual para separar columnas */
        .kl-wallet-column:first-child {
            border-right: 2px solid #e5e5e5 !important;
            padding-right: 20px !important;
        }
        
        .kl-wallet-column:last-child {
            padding-left: 20px !important;
        }
        
        /* Responsive design */
        @media (max-width: 1200px) {
            .kl-wallet-two-columns {
                flex-direction: column !important;
            }
            
            .kl-wallet-column {
                width: 100% !important;
                padding: 0 !important;
            }
            
            .kl-wallet-column:first-child {
                border-right: none !important;
                border-bottom: 2px solid #e5e5e5 !important;
                padding-right: 0 !important;
                padding-bottom: 20px !important;
                margin-bottom: 20px !important;
            }
            
            .kl-wallet-column:last-child {
                padding-left: 0 !important;
            }
        }
        
        /* Mejoras visuales para las columnas */
        .kl-wallet-column .card {
            height: fit-content !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100% !important;
        }
        
        /* Ajustes para tablas en columnas */
        .kl-wallet-column .wp-list-table {
            font-size: 13px !important;
            width: 100% !important;
            table-layout: fixed !important;
        }
        
        .kl-wallet-column .wp-list-table th,
        .kl-wallet-column .wp-list-table td {
            padding: 8px 10px !important;
            word-wrap: break-word !important;
        }
        
        /* Estilos para títulos de columnas */
        .kl-wallet-column h3 {
            font-size: 18px !important;
            font-weight: 600 !important;
            margin-bottom: 20px !important;
            margin-top: 0 !important;
            padding: 15px 0 !important;
            background: #f9f9f9 !important;
            border-radius: 5px !important;
            text-align: center !important;
        }
        
        .kl-wallet-column h3 .dashicons {
            margin-right: 8px;
            font-size: 20px;
        }
        
        /* Mejoras para las cards en columnas */
        .kl-wallet-column .card {
            margin-bottom: 15px !important;
            margin-top: 0 !important;
        }
        
        .kl-wallet-column .card h2 {
            font-size: 16px !important;
            margin-top: 0 !important;
            margin-bottom: 15px !important;
        }
        
        /* Estilo para la descripción del panel */
        .wrap .description {
            font-size: 14px !important;
            color: #666 !important;
            margin-bottom: 20px !important;
            padding: 10px 15px !important;
            background: #f9f9f9 !important;
            border-left: 4px solid #0073aa !important;
            border-radius: 3px !important;
        }
        
        .wrap .description .dashicons {
            vertical-align: middle !important;
            margin-right: 5px !important;
        }
        
        .stats-container {
            background: #f9f9f9 !important;
            padding: 15px !important;
            border-radius: 5px !important;
            margin-bottom: 20px !important;
        }
        
        .kl-wallet-column .stats-container {
            flex-wrap: wrap !important;
            gap: 10px !important;
        }
        
        .kl-wallet-column .stat-box {
            min-width: 120px !important;
            flex: 1 !important;
        }
        
        .stat-box {
            transition: transform 0.2s;
        }
        
        .stat-box:hover {
            transform: translateY(-2px) !important;
        }
        
        /* Estilos para botones en columnas */
        .kl-wallet-column .button {
            margin: 2px !important;
            font-size: 12px !important;
            padding: 5px 10px !important;
        }
        
        .kl-wallet-column .tablenav {
            margin: 10px 0 !important;
        }
        
        .status-badge {
            transition: all 0.3s ease !important;
        }
        
        .badge {
            font-weight: bold !important;
            display: inline-block !important;
        }
        
        .ip-row:hover {
            background-color: #f0f0f1 !important;
        }
        
        .log-row:hover {
            background-color: #f0f0f1 !important;
        }
        
        .reason-text {
            max-width: 300px !important;
            word-wrap: break-word !important;
        }
        
        .kl-wallet-column .reason-text {
            max-width: 200px !important;
        }
        
        .row-actions {
            display: flex !important;
            flex-direction: column !important;
            gap: 5px !important;
        }
        
        .kl-wallet-column .row-actions {
            gap: 3px !important;
        }
        
        .kl-wallet-column .row-actions .button {
            font-size: 11px !important;
            padding: 3px 8px !important;
        }
        
        .badge {
            font-weight: bold;
        }
        
        #ip-search, #reason-filter {
            padding: 5px 10px !important;
            border: 1px solid #ddd !important;
            border-radius: 3px !important;
        }
        
        .kl-wallet-column #ip-search, 
        .kl-wallet-column #reason-filter,
        .kl-wallet-column #log-ip-search,
        .kl-wallet-column #log-endpoint-filter,
        .kl-wallet-column #log-reason-filter {
            width: 100% !important;
            margin-bottom: 10px !important;
        }
        
        #ip-search:focus, #reason-filter:focus {
            border-color: #0073aa !important;
            outline: none !important;
            box-shadow: 0 0 0 1px #0073aa !important;
        }
        
        .kl-wallet-column #ip-search:focus, 
        .kl-wallet-column #reason-filter:focus,
        .kl-wallet-column #log-ip-search:focus,
        .kl-wallet-column #log-endpoint-filter:focus,
        .kl-wallet-column #log-reason-filter:focus {
            border-color: #0073aa !important;
            outline: none !important;
            box-shadow: 0 0 0 1px #0073aa !important;
        }
        </style>
        <?php
    }
});
