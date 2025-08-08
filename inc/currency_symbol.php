<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add custom currency "Puntos" to WooCommerce
 * This creates a new currency option in WooCommerce settings
 */
function kerns_lovers_add_puntos_currency($currencies) {
    // Add our custom currency to the list
    $currencies['PTO'] = __('Puntos', 'kerns-lovers');
    return $currencies;
}

// Hook to add the custom currency
add_filter('woocommerce_currencies', 'kerns_lovers_add_puntos_currency');

/**
 * Add custom currency symbol for "Puntos"
 */
function kerns_lovers_add_puntos_currency_symbol($currency_symbol, $currency) {
    // Define the symbol for our custom currency
    if ($currency === 'PTO') {
        return 'Puntos';
    }
    return $currency_symbol;
}

// Hook to add the currency symbol
add_filter('woocommerce_currency_symbol', 'kerns_lovers_add_puntos_currency_symbol', 10, 2); 