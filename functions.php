<?php
/**
 * KetchupLovers Theme Functions
 * 
 * This file contains all the custom functions for the KetchupLovers theme
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

define('KERN_LOVERS_VERSION', '1.0.0');
define('THEME_URI', get_stylesheet_directory_uri());
define('THEME_DIR', get_stylesheet_directory());
define('THEME_NAME', 'Ketchup Lovers');

include_once THEME_DIR . '/inc/currency_symbol.php';
include_once THEME_DIR . '/inc/wc_woo_states.php';
include_once THEME_DIR . '/inc/custom_wallet_misc.php';
include_once THEME_DIR . '/inc/login-customization.php';
include_once THEME_DIR . '/templates/tomato-menu.php';
include_once THEME_DIR . '/templates/cart-filters.php';
include_once THEME_DIR . '/inc/welcome_menu.php';
include_once THEME_DIR . '/inc/store_welcome.php';
include_once THEME_DIR . '/inc/cart_hooks.php';
include_once THEME_DIR . '/inc/shortcode-coleccion.php';
include_once THEME_DIR . '/inc/titulo_productos.php';
include_once THEME_DIR . '/inc/pto_to_buy.php';
include_once THEME_DIR . '/inc/misc.php';

// Include API files
include_once THEME_DIR . '/API/config.php';
include_once THEME_DIR . '/API/api-wallet.php';
include_once THEME_DIR . '/API/generar-token.php';
include_once THEME_DIR . '/API/ip-manager.php';

// Include Wallet IP Admin Panel
include_once THEME_DIR . '/inc/wallet-ip-admin.php';

// Enqueue custom styles
function add_styles_css() {
    // Get the file modification time for versioning
    $css_file_path = THEME_DIR . '/assets/css/style.css';
    $css_file_url = THEME_URI . '/assets/css/style.css';
    $version = file_exists($css_file_path) ? filemtime($css_file_path) : '1.0.0';
    
    wp_enqueue_style( 'parent', get_template_directory_uri().'/style.css' );
    
    // Enqueue the main stylesheet
    wp_enqueue_style(
        'ketchuplovers-styles', 
        $css_file_url,
        array(), // No dependencies
        $version // Version for cache busting
    );
    if (!wp_script_is('gsap', 'enqueued')) {
        wp_enqueue_script(
            'gsap',
            'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js',
            array(),
            '3.12.5',
            false // Load in header to ensure it's available before shortcodes
        );
    }
    
    // Load ScrollTrigger plugin
    if (!wp_script_is('gsap-scrolltrigger', 'enqueued')) {
        wp_enqueue_script(
            'gsap-scrolltrigger',
            'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js',
            array('gsap'),
            '3.12.5',
            false // Load in header to ensure it's available before shortcodes
        ); 
    }
}
add_action('wp_enqueue_scripts', 'add_styles_css');

function add_scripts_js_footer() {
    // Prevent duplicate loading by checking if scripts are already enqueued
    if (wp_script_is('ketchuplovers-scripts', 'enqueued')) {
        return;
    }
    
    // Get the file modification time for versioning
    $js_file_path = THEME_DIR . '/assets/js/script.js';
    $js_file_url = THEME_URI . '/assets/js/script.js';
    $version = file_exists($js_file_path) ? filemtime($js_file_path) : '1.0.0';
    
    // Load custom scripts with GSAP dependency
    wp_enqueue_script(
        'ketchuplovers-scripts',
        $js_file_url,
        array('gsap', 'gsap-scrolltrigger'), // Depend on GSAP and ScrollTrigger
        $version,
        true // Load in footer for better performance
    );

    wp_enqueue_script(
        'ketchup-menu',
        THEME_URI . '/assets/js/ketchup-menu.js',
        array('gsap', 'gsap-scrolltrigger'),
        $version,
        true
    );
}
add_action('wp_enqueue_scripts', 'add_scripts_js_footer',99999);

/**
 * Add theme information to admin footer
 */
function kernslovers_admin_footer_text($text) {
    if (is_admin()) {
        return sprintf(
            'Desarrollado con ❤️ por <a href="https://gianko.com" target="_blank">Gianko.com</a> | Tema: %s v%s',
            THEME_NAME,
            KERN_LOVERS_VERSION
        );
    }
    return $text;
}
add_filter('admin_footer_text', 'kernslovers_admin_footer_text');

/**
* @snippet       Hide Edit Address Tab @ My Account
* @author        Gianko.com
*/
 
add_filter( 'woocommerce_account_menu_items', 'kernslovers_remove_address_my_account', 999 );
 
function kernslovers_remove_address_my_account( $items ) {
unset($items['edit-address']);
unset($items['edit-account']);
unset($items['dashboard']);
unset($items['downloads']);
unset($items['address']);
unset($items['customer-logout']);
return $items;
}
 
/**
* @snippet       Rename Edit Address Tab @ My Account
* @author        Gianko.com
*/
 
add_filter( 'woocommerce_account_menu_items', 'kernslovers_rename_tabs_my_account', 999 );
 
function kernslovers_rename_tabs_my_account( $items ) {
    $items['orders'] = 'Productos comprados';
    $items['wps-wallet'] = 'Mi Wallet';

    // Es crucial devolver el array modificado.
    return $items;
}

add_filter( 'woocommerce_account_dashboard', 'kernslovers_wallet_default', 999 );
function kernslovers_wallet_default() {
    echo do_shortcode('[wps-wallet]');
}

/**
 * CÓDIGO PARA MOSTRAR 'MI WALLET' POR DEFECTO EN 'MI CUENTA'
 * Compatible con Elementor - No afecta el modo editor
 */

add_action( 'template_redirect', 'redirigir_mi_cuenta_a_wallet_transactions' );
function redirigir_mi_cuenta_a_wallet_transactions() {
    
    // Verificar si Elementor está en modo editor
    if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
        return; // Salir sin hacer redirección en modo editor
    }
    
    // Verificar si estamos en el modo preview de Elementor
    if ( isset( $_GET['elementor-preview'] ) || isset( $_GET['preview'] ) ) {
        return; // Salir sin hacer redirección en modo preview
    }
    
    // Verificar si estamos en el modo editor de Elementor (parámetros adicionales)
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'elementor' ) {
        return; // Salir sin hacer redirección
    }
    
    // Obtenemos las variables de la URL actual.
    $query_vars = $GLOBALS['wp']->query_vars;

    // Condición 1: ¿Estamos en la página "Mi Cuenta"?
    $is_my_account = is_account_page();
    
    // Condición 2: ¿Estamos en la URL principal, sin ningún endpoint de wallet ya presente?
    // Esta es la clave para evitar el bucle. Solo redirigimos si 'wps-wallet' NO está en la URL.
    $is_base_url = ! isset( $query_vars['wps-wallet'] );
    $is_base_url_2 = ! isset( $query_vars['orders'] );
    $is_base_url_3 = ! isset( $query_vars['view-order'] );

    // Si AMBAS condiciones son verdaderas, procedemos a redirigir.
    if ( $is_my_account && $is_base_url && $is_base_url_2 && $is_base_url_3 ) {

        // Construimos la URL de destino final.
        // Primero, obtenemos la URL del endpoint principal 'wps-wallet'.
        $wallet_url = wc_get_account_endpoint_url( 'wps-wallet' );
        
        // Luego, le añadimos la sub-página 'wallet-transactions'.
        $destination_url = $wallet_url . 'wallet-transactions/';

        // Redirigimos de forma segura al usuario a la URL de destino.
        wp_safe_redirect( $destination_url );
        exit(); // Detenemos la ejecución para completar la redirección.
    }
}

// ===== FIN DEL CÓDIGO =====

/**
 * Redirige automáticamente a la página del carrito
 * después de añadir un producto.
 */
add_filter( 'woocommerce_add_to_cart_redirect', 'gc_redirigir_al_anadir_al_carrito' );

function gc_redirigir_al_anadir_al_carrito() {
    return wc_get_cart_url();
}
/**
 * Desactiva el mensaje de "Producto añadido al carrito" de WooCommerce.
 */
add_filter( 'wc_add_to_cart_message_html', '__return_false' );