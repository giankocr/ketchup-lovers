<?php
/**
 * Cart Filters
 * 
 * This file contains all the custom functions for the Ketchup Lovers theme
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Paso 2.1: Añadir el botón personalizado y prepararlo para JavaScript.
 * Se muestra antes del botón de "Añadir al carrito".
 */
//add_action( 'woocommerce_after_add_to_cart_button', 'emply_function' );
add_action( 'woocommerce_before_add_to_cart_button', 'agregar_boton_canjear_prenda_checkout' );

function emply_function() {
    return;
}

function agregar_boton_canjear_prenda_checkout() {
    // Asegurarnos de que solo se muestre en páginas de producto
    if ( ! is_product() ) {
        return;
    }
    $url_checkout = wc_get_checkout_url();
    $url_cart = wc_get_cart_url();
        // El botón se crea aquí, pero se activará con JavaScript.
    // data-checkout-url nos permite pasar la URL del checkout a nuestro script.
    if ( ! is_user_logged_in() ) {
        // Si el usuario NO está logueado, muestra el botón de login
        echo '<div class="canjear-prenda-container">
            <a href="/ingresar" class="boton-estandar login-button">INICIAR SESIÓN PARA CANJEAR</a>
        </div>';
    } else {
        // Si el usuario está logueado, muestra los botones normales
        echo '<div class="cantidad-container">
  <span class="cantidad-label">Cantidad</span>
  <div class="cantidad-box">
    <button class="cantidad-menos" type="button">−</button>
    <span class="cantidad-numero">1</span>
    <button class="cantidad-mas" type="button">+</button>
  </div>
</div>
<div class="canjear-prenda-container">
<a href="#" class="boton-estandar canjear-prenda-button" style="display:none;" data-checkout-url="' . esc_url( $url_checkout ) . '" data-product-id="' . get_the_ID() . '">CANJEAR PRENDA AHORA</a>
<a href="#" class="boton-estandar agregar-carrito-button" data-cart-url="' . esc_url( $url_cart ) . '">AGREGAR AL CARRITO</a>
</div>';
    }
}

/**
 * Paso 2.2: Cargar el archivo JavaScript que activará el botón.
 * Este script solo se cargará en las páginas de producto.
 */
add_action( 'wp_enqueue_scripts', 'cargar_script_canjear_prenda' );

function cargar_script_canjear_prenda() {
    // Solo cargar el script en las páginas de producto para optimizar
    if ( is_product() ) {
        wp_enqueue_script(
            'canjear-prenda-script', // Nombre del script
            get_stylesheet_directory_uri() . '/assets/js/canjear-prenda.js', // Ruta al archivo JS
            array( 'jquery', 'wc-add-to-cart-variation' ), // Dependencias: jQuery y WooCommerce variations
            '2.0.2', // Versión restaurada
            true // Cargar el script en el pie de página
        );
        
        // Pasar variables al JavaScript
        wp_localize_script( 'canjear-prenda-script', 'kerns_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'kerns_nonce' )
        ));
    }
}

