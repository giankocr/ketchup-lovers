<?php
/**
 * Helper de depuración para el API de Wallet
 * 
 * Este archivo proporciona funciones de depuración para verificar
 * que todas las funciones necesarias estén disponibles
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    http_response_code(403);
    exit('Acceso directo prohibido');
}

/**
 * Verificar estado de las funciones del API
 */
function kl_wallet_debug_check_functions(): array {
    $results = [];
    
    // Funciones básicas del API
    $basic_functions = [
        'kl_wallet_get_api_key',
        'kl_wallet_is_api_configured',
        'kl_wallet_regenerate_api_key'
    ];
    
    foreach ($basic_functions as $function) {
        $results[$function] = function_exists($function);
    }
    
    // Funciones de gestión de IPs
    $ip_functions = [
        'kl_wallet_add_allowed_ip',
        'kl_wallet_remove_allowed_ip',
        'kl_wallet_get_allowed_ips',
        'kl_wallet_is_ip_allowed',
        'kl_wallet_enable_ip_restriction',
        'kl_wallet_disable_ip_restriction',
        'kl_wallet_is_ip_restriction_enabled'
    ];
    
    foreach ($ip_functions as $function) {
        $results[$function] = function_exists($function);
    }
    
    // Funciones del gestor de IPs
    $manager_functions = [
        'kl_wallet_ip_add',
        'kl_wallet_ip_remove',
        'kl_wallet_ip_list',
        'kl_wallet_ip_check',
        'kl_wallet_ip_current'
    ];
    
    foreach ($manager_functions as $function) {
        $results[$function] = function_exists($function);
    }
    
    return $results;
}

/**
 * Verificar estado de las clases del API
 */
function kl_wallet_debug_check_classes(): array {
    $results = [];
    
    $classes = [
        'KL_Wallet_API_Config',
        'KL_Wallet_IP_Manager'
    ];
    
    foreach ($classes as $class) {
        $results[$class] = class_exists($class);
    }
    
    return $results;
}

/**
 * Obtener información del sistema
 */
function kl_wallet_debug_system_info(): array {
    return [
        'wordpress_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION,
        'theme_directory' => defined('THEME_DIR') ? THEME_DIR : 'No definido',
        'abspath' => defined('ABSPATH') ? ABSPATH : 'No definido',
        'wp_debug' => defined('WP_DEBUG') ? WP_DEBUG : false,
        'wp_debug_log' => defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : false
    ];
}

/**
 * Mostrar reporte completo de depuración
 */
function kl_wallet_debug_show_report(): void {
    $functions = kl_wallet_debug_check_functions();
    $classes = kl_wallet_debug_check_classes();
    $system = kl_wallet_debug_system_info();
    
    echo '<div class="wrap">';
    echo '<h1>Reporte de Depuración - API Wallet</h1>';
    
    // Estado de las funciones
    echo '<h2>Estado de las Funciones</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Función</th><th>Estado</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($functions as $function => $available) {
        echo '<tr>';
        echo '<td><code>' . esc_html($function) . '</code></td>';
        echo '<td>' . ($available ? '✅ Disponible' : '❌ No disponible') . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    // Estado de las clases
    echo '<h2>Estado de las Clases</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Clase</th><th>Estado</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($classes as $class => $available) {
        echo '<tr>';
        echo '<td><code>' . esc_html($class) . '</code></td>';
        echo '<td>' . ($available ? '✅ Disponible' : '❌ No disponible') . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    // Información del sistema
    echo '<h2>Información del Sistema</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<tbody>';
    
    foreach ($system as $key => $value) {
        echo '<tr>';
        echo '<td><strong>' . esc_html(ucfirst(str_replace('_', ' ', $key))) . ':</strong></td>';
        echo '<td>' . esc_html($value) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    echo '</div>';
}
