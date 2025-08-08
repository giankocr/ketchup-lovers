<?php

// Shortcode para mostrar el mensaje de bienvenida y puntos
function kl_welcome_store_shortcode() {
    // Verifica si el usuario está logueado
    if ( is_user_logged_in() ) {
        // Obtiene el usuario actual
        $current_user = wp_get_current_user();
        // Obtiene el primer nombre del usuario
        $user_firstname = get_user_meta($current_user->ID, 'first_name', true);
        // Construye el mensaje HTML
        $output  = '<div class="welcome-menu-container text-center-menu">';
        $output .= '<span class="welcome-menu welcome-text"> Bienvenido/a <strong>' . esc_html($user_firstname) . '</strong> </span>';
        $output .= '<span class="welcome-menu points-available-text">Puntos disponibles:</span>';
        $output .= '<span class="welcome-menu points-available"> <strong>' . do_shortcode('[wps-wallet-amount]') . ' </strong></span>';
        $output .= '</div>';
    } else {
        // Mensaje si no está logueado
        $output = '<h1 style="font-family: Dorgan, Sans-serif !important;font-size: clamp(2rem, 4.5vw, 3rem) !important;">Tienda</h1>';
    }
    return $output;
}
add_shortcode('kl-welcome-store', 'kl_welcome_store_shortcode');