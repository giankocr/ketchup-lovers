<?php

// Shortcode para mostrar el mensaje de bienvenida y puntos
function kl_welcome_menu_shortcode() {
    // Verifica si el usuario está logueado
    if ( is_user_logged_in() ) {
        // Obtiene el usuario actual
        $current_user = wp_get_current_user();
        // Obtiene el primer nombre del usuario
        $user_firstname = get_user_meta($current_user->ID, 'first_name', true);
        // Construye el mensaje HTML
        $output  = '<div class="welcome-menu-container">';
        $output .= '<span class="welcome-menu">Bienvenido/a <strong>' . esc_html($user_firstname) . '</strong></span> <br>';
        $output .= '<span class="welcome-menu">Puntos disponibles: <strong>' . do_shortcode('[wps-wallet-amount]') . '</strong></span>';
        $output .= '</div>';
    } else {
        // Mensaje si no está logueado
        $output = '<div style="display:none;"></div>';
    }
    return $output;
}
add_shortcode('kl-welcome-menu', 'kl_welcome_menu_shortcode');
