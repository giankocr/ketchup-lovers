<?php
/**
 * Custom Wallet Access Control
 * 
 * This file handles access control for wallet features
 * Denies access to wallet transfer and withdrawal URLs
 * Hides wallet transfer and withdrawal options from the interface
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Deny access to wallet transfer and withdrawal URLs
 * Redirects users to my-account page if they try to access restricted URLs
 */
function deny_wallet_access() {
    // Check if user is on a restricted wallet page
    if (is_page() || is_single()) {
        $current_url = $_SERVER['REQUEST_URI'];
        
        // Define restricted wallet URLs
        $restricted_urls = array(
            '/mi-cuenta/wps-wallet/wallet-transfer/',
            '/mi-cuenta/wps-wallet/wallet-withdrawal/'
        );
        
        // Check if current URL contains any restricted paths
        foreach ($restricted_urls as $restricted_url) {
            if (strpos($current_url, $restricted_url) !== false) {
                // Redirect to my-account page with error message
                wp_redirect(home_url('/mi-cuenta/?wallet_access_denied=1'));
                exit;
            }
        }
    }
}
add_action('template_redirect', 'deny_wallet_access');

/**
 * Display error message when wallet access is denied
 */
function display_wallet_access_denied_message() {
    if (isset($_GET['wallet_access_denied']) && $_GET['wallet_access_denied'] == '1') {
        echo '<div class="woocommerce-error" role="alert">';
        echo '<p>Acceso denegado: Las funciones de transferencia y retiro del monedero no están disponibles.</p>';
        echo '</div>';
    }
}
add_action('woocommerce_account_navigation', 'display_wallet_access_denied_message', 5);

/**
 * Disable wallet links using JavaScript
 */
function disable_wallet_links_js() {
    if (is_account_page()) {
        ?>
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            // Disable wallet transfer links
            var transferLinks = document.querySelectorAll('a[href*="wallet-transfer"]');
            transferLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    alert('Acceso denegado: La transferencia del monedero no está disponible.');
                    return false;
                });
            });
            
            // Disable wallet withdrawal links
            var withdrawalLinks = document.querySelectorAll('a[href*="wallet-withdrawal"]');
            withdrawalLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    alert('Acceso denegado: La retirada del monedero no está disponible.');
                    return false;
                });
            });
            
            // Disable onclick functions for wallet tabs
            var walletTabs = document.querySelectorAll('.wallet-tabs .tabs li');
            walletTabs.forEach(function(tab) {
                if (tab.onclick && tab.onclick.toString().includes('enable_wallet_link')) {
                    tab.onclick = function(e) {
                        e.preventDefault();
                        return false;
                    };
                }
            });
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'disable_wallet_links_js');

/**
 * Filter wallet menu items to remove restricted options
 */
function filter_wallet_menu_items($items) {
    // Remove wallet transfer and withdrawal from menu items
    if (is_array($items)) {
        foreach ($items as $key => $item) {
            if (isset($item['url']) && (
                strpos($item['url'], 'wallet-transfer') !== false ||
                strpos($item['url'], 'wallet-withdrawal') !== false
            )) {
                unset($items[$key]);
            }
        }
    }
    return $items;
}
add_filter('wps_wallet_menu_items', 'filter_wallet_menu_items');

/**
 * Remove wallet transfer and withdrawal endpoints
 */
function remove_wallet_endpoints() {
    // Remove wallet transfer endpoint
    if (function_exists('wps_wallet_transfer_endpoint')) {
        remove_action('init', 'wps_wallet_transfer_endpoint');
    }
    
    // Remove wallet withdrawal endpoint
    if (function_exists('wps_wallet_withdrawal_endpoint')) {
        remove_action('init', 'wps_wallet_withdrawal_endpoint');
    }
}
add_action('init', 'remove_wallet_endpoints', 1);

/**
 * Redirect wallet main page to transactions page
 * When users click on "Cartera" in My Account, redirect them directly to transactions
 */
function redirect_wallet_to_transactions() {
    // Check if we're on the main wallet page
    if (is_page() || is_single()) {
        $current_url = $_SERVER['REQUEST_URI'];
        
        // Check if current URL is the main wallet page (not transactions, transfer, or withdrawal)
        if (strpos($current_url, '/mi-cuenta/wps-wallet/') !== false &&
            strpos($current_url, '/mi-cuenta/wps-wallet/wallet-transactions/') === false &&
            strpos($current_url, '/mi-cuenta/wps-wallet/wallet-transfer/') === false &&
            strpos($current_url, '/mi-cuenta/wps-wallet/wallet-withdrawal/') === false) {
            
            // Redirect to transactions page
            wp_redirect(home_url('/mi-cuenta/wps-wallet/wallet-transactions/'));
            exit;
        }
    }
}
add_action('template_redirect', 'redirect_wallet_to_transactions');

/**
 * Modify wallet menu links to point directly to transactions
 * This changes the main wallet link in the navigation menu
 */
function modify_wallet_menu_links($items) {
    if (is_array($items)) {
        foreach ($items as $key => $item) {
            // Check if this is the main wallet menu item (not transfer or withdrawal)
            if (isset($item['url']) && 
                strpos($item['url'], 'wps-wallet') !== false &&
                strpos($item['url'], 'wallet-transfer') === false &&
                strpos($item['url'], 'wallet-withdrawal') === false &&
                strpos($item['url'], 'wallet-transactions') === false) {
                
                // Change the URL to point to transactions
                $items[$key]['url'] = home_url('/mi-cuenta/wps-wallet/wallet-transactions/');
            }
        }
    }
    return $items;
}
add_filter('wps_wallet_menu_items', 'modify_wallet_menu_links');

/**
 * Add JavaScript to redirect wallet links to transactions page
 * This handles any wallet links that might not be caught by the PHP redirect
 */
function redirect_wallet_links_js() {
    if (is_account_page()) {
        ?>
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            // Find all wallet links that are not transactions, transfer, or withdrawal
            var walletLinks = document.querySelectorAll('a[href*="wps-wallet"]');
            walletLinks.forEach(function(link) {
                var href = link.getAttribute('href');
                
                // Check if this is a main wallet link (not specific pages)
                if (href && 
                    href.indexOf('wps-wallet') !== -1 &&
                    href.indexOf('wallet-transfer') === -1 &&
                    href.indexOf('wallet-withdrawal') === -1 &&
                    href.indexOf('wallet-transactions') === -1) {
                    
                    // Change the link to point to transactions
                    link.href = '<?php echo home_url('/mi-cuenta/wps-wallet/wallet-transactions/'); ?>';
                }
            });
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'redirect_wallet_links_js');

function custom_hide_gateway_for_low_total( $available_gateways ) {
    var_dump($available_gateways);
    return $available_gateways;
}
//add_filter( 'woocommerce_available_payment_gateways', 'custom_hide_gateway_for_low_total' );

function custom_change_wallet_gateway_title( $available_gateways ) {
    // Comprobar si el gateway del monedero está disponible.
    if ( isset( $available_gateways['wps_wcb_wallet_payment_gateway'] ) ) {

        // Acceder a la pasarela de pago del monedero.
        $gateway = $available_gateways['wps_wcb_wallet_payment_gateway'];

        // Puedes acceder al saldo del usuario si el plugin lo proporciona.
        // Aquí se usa un ejemplo, la forma exacta de obtener el saldo puede variar.
        $user_id = get_current_user_id();
        $user_balance = get_user_meta( $user_id, 'wps_wallet', true );
        
        // Validar y convertir el saldo a un número válido
        // Si el saldo está vacío o no es numérico, establecerlo en 0
        $user_balance = ( ! empty( $user_balance ) && is_numeric( $user_balance ) ) ? floatval( $user_balance ) : 0.0;
        
        // Define el nuevo texto.
        // Usa `number_format_i18n` para formatear el número según las configuraciones de WordPress.
        $new_title = sprintf( 'Canjear con puntos. Tu saldo actual: %s Puntos', number_format_i18n( $user_balance, 0 ) );

        // Asignar el nuevo título a la pasarela de pago.
        $gateway->title = $new_title;

        // Define el nuevo texto que aparecerá debajo del título.
        $new_description = sprintf( 'Utiliza tu saldo de %s Puntos para pagar este pedido.', number_format_i18n( $user_balance, 0 ) );

        // Asignamos el nuevo texto a la propiedad 'method_description'.
        $gateway->method_description = $new_description;

        // Actualizar el array de pasarelas de pago.
        $available_gateways['wps_wcb_wallet_payment_gateway'] = $gateway;
    }
    
    // Devolver las pasarelas de pago modificadas.
    return $available_gateways;
}
add_filter( 'woocommerce_available_payment_gateways', 'custom_change_wallet_gateway_title' );

function cambiar_mensaje_pago_no_disponible( $message ) {
    // Reemplaza el mensaje predeterminado con tu propio texto.
    // Puedes usar HTML si lo necesitas para dar formato.
    $nuevo_mensaje = 'Registra tus compras y gana puntos para canjear en la tienda. <br>Registra tus compras <a href="https://wa.me/50200000000" target="_blank">aquí</a>.';
    
    return $nuevo_mensaje;
}
add_filter( 'woocommerce_no_available_payment_methods_message', 'cambiar_mensaje_pago_no_disponible' );