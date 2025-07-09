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
            '/my-account/wps-wallet/wallet-transfer/',
            '/my-account/wps-wallet/wallet-withdrawal/'
        );
        
        // Check if current URL contains any restricted paths
        foreach ($restricted_urls as $restricted_url) {
            if (strpos($current_url, $restricted_url) !== false) {
                // Redirect to my-account page with error message
                wp_redirect(home_url('/my-account/?wallet_access_denied=1'));
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
        if (strpos($current_url, '/my-account/wps-wallet/') !== false &&
            strpos($current_url, '/my-account/wps-wallet/wallet-transactions/') === false &&
            strpos($current_url, '/my-account/wps-wallet/wallet-transfer/') === false &&
            strpos($current_url, '/my-account/wps-wallet/wallet-withdrawal/') === false) {
            
            // Redirect to transactions page
            wp_redirect(home_url('/my-account/wps-wallet/wallet-transactions/'));
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
                $items[$key]['url'] = home_url('/my-account/wps-wallet/wallet-transactions/');
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
                    link.href = '<?php echo home_url('/my-account/wps-wallet/wallet-transactions/'); ?>';
                }
            });
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'redirect_wallet_links_js');

/**
 * Change wallet menu item text from "Cartera" to "Puntos"
 * This modifies the display text of the wallet menu item
 */
function change_wallet_menu_text($items) {
    if (is_array($items)) {
        foreach ($items as $key => $item) {
            // Check if this is a wallet menu item
            if (isset($item['url']) && strpos($item['url'], 'wps-wallet') !== false) {
                // Change the text to "Puntos"
                if (isset($item['text'])) {
                    $items[$key]['text'] = 'Puntos';
                }
                if (isset($item['title'])) {
                    $items[$key]['title'] = 'Puntos';
                }
            }
        }
    }
    return $items;
}
add_filter('wps_wallet_menu_items', 'change_wallet_menu_text');

/**
 * Hide downloads menu item from WooCommerce My Account navigation
 * This removes the downloads option from the main account navigation
 */
function hide_downloads_menu_item($items) {
    // Remove downloads from the main WooCommerce account navigation
    if (isset($items['downloads'])) {
        unset($items['downloads']);
    }
    return $items;
}
add_filter('woocommerce_account_menu_items', 'hide_downloads_menu_item');

/**
 * Add JavaScript to change wallet text and hide downloads
 * This provides JavaScript-based modifications for better compatibility
 */
function add_account_menu_js_modifications() {
    if (is_account_page()) {
        ?>
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            // Change wallet text to "Puntos"
            var walletLinks = document.querySelectorAll('a[href*="wps-wallet"]');
            walletLinks.forEach(function(link) {
                // Change the text content
                if (link.textContent && link.textContent.trim() !== '') {
                    link.textContent = 'Puntos';
                }
                
                // Also change any child elements
                var childElements = link.querySelectorAll('span, strong, em');
                childElements.forEach(function(element) {
                    element.textContent = 'Puntos';
                });
            });
            
            // Hide downloads menu items
            var downloadsLinks = document.querySelectorAll('a[href*="downloads"]');
            downloadsLinks.forEach(function(link) {
                var listItem = link.closest('li');
                if (listItem) {
                    listItem.style.display = 'none';
                }
            });
            
            // Alternative method to hide downloads
            var downloadsMenuItems = document.querySelectorAll('.woocommerce-MyAccount-navigation-link--downloads');
            downloadsMenuItems.forEach(function(item) {
                item.style.display = 'none';
            });
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'add_account_menu_js_modifications');
