<?php

function get_first_name_current_user() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        return $user->user_firstname;
    }
    return '';
}

function get_last_name_current_user() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        return $user->user_lastname;
    }
    return '';
}

function get_phone_current_user() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        return $user->user_phone;
    }
    return '';
}

function display_user_meta_shortcode($atts) {
    // 1. Define y sanitiza los atributos del shortcode.
    $atts = shortcode_atts(
        array(
            'meta_key' => '',
        ),
        $atts,
        'user_meta_value'
    );

    // 2. Extrae la meta_key y asegúrate de que es una cadena válida.
    $meta_key = isset($atts['meta_key']) ? sanitize_key($atts['meta_key']) : '';

    // 3. Verifica las condiciones necesarias: usuario logueado y meta_key válida.
    if (!is_user_logged_in() || empty($meta_key)) {
        // Opción de depuración: Si no hay clave, podemos devolver un mensaje.
        if (current_user_can('manage_options')) {
            return 'Debe de enviar un meta_key [user_meta_value meta_key="first_name"]';
        }
        return '';
    }

    $user_id = get_current_user_id();

    // 4. Obtiene el valor del metadato del usuario.
    $meta_value = get_user_meta($user_id, $meta_key, true);

    // 5. Sanea y devuelve el valor.
    $sanitized_value = sanitize_text_field($meta_value);
    
    return $sanitized_value;
}
add_shortcode('user_meta_value', 'display_user_meta_shortcode');

