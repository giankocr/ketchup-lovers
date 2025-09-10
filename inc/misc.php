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


 function add_puntos_after_puntos_field($atts = array()){
    // Obtener el valor del campo ACF 'puntos'
    $puntos = get_field('puntos');
    
    // Validar que el campo existe y es un número
    if (empty($puntos) || !is_numeric($puntos)) {
        return ''; // Retorna vacío si no hay valor válido
    }
    
    // Convertir a entero para comparación
    $puntos = intval($puntos);
    
    // Determinar si usar singular o plural
    if ($puntos == 1) {
        $text = __('Punto', 'kerns-lovers'); // Sin coma al final
    } else {
        $text = __('Puntos', 'kerns-lovers');
    }
    
    // Retornar el número seguido del texto
    return ' ' . $text;
}
add_shortcode('after_puntos_field', 'add_puntos_after_puntos_field');


function shortcode_change_phone() {
    // Verificar que el usuario actual tenga permisos de administrador
    if (!current_user_can('manage_options')) {
        return '<p style="color: red;">Error: No tienes permisos para acceder a esta función.</p>';
    }
    
    $output = '';
    $message = '';
    $user_data = null;
    
    // Procesar el formulario si se envió
    if (isset($_POST['search_user_email']) && !empty($_POST['search_user_email'])) {
        $email = sanitize_email($_POST['search_user_email']);
        
        if (is_email($email)) {
            // Buscar usuario por email
            $user = get_user_by('email', $email);
            
            if ($user) {
                $user_data = $user;
                $message = '<p style="color: green;">Usuario encontrado: ' . esc_html($user->display_name) . '</p>';
            } else {
                $message = '<p style="color: red;">No se encontró ningún usuario con ese email.</p>';
            }
        } else {
            $message = '<p style="color: red;">Por favor, ingresa un email válido.</p>';
        }
    }
    
    // Procesar actualización de teléfono si se envió
    if (isset($_POST['update_phone']) && isset($_POST['user_id']) && isset($_POST['new_phone'])) {
        $user_id = intval($_POST['user_id']);
        $new_phone = sanitize_text_field($_POST['new_phone']);
        
        // Verificar que el usuario existe
        $user = get_user_by('id', $user_id);
        
        if ($user) {
            // Actualizar los metadatos del teléfono
            update_user_meta($user_id, 'meta_xeerpa_phone', $new_phone);
            update_user_meta($user_id, 'billing_phone', $new_phone);
            update_user_meta($user_id, 'shipping_phone', $new_phone);
            
            $message = '<p style="color: green;">Teléfono actualizado correctamente para ' . esc_html($user->display_name) . '</p>';
            
            // Recargar datos del usuario
            $user_data = $user;
        } else {
            $message = '<p style="color: red;">Error: Usuario no encontrado.</p>';
        }
    }
    
    // Mostrar mensajes
    if (!empty($message)) {
        $output .= $message;
    }
    
    // Formulario de búsqueda
    $output .= '<div style="margin: 20px 0; padding: 20px; border: 1px solid #ddd; background: #f9f9f9;">';
    $output .= '<h3>Buscar Usuario por Email</h3>';
    $output .= '<form method="post" action="">';
    $output .= '<p>';
    $output .= '<label for="search_user_email">Email del usuario:</label><br>';
    $output .= '<input type="email" id="search_user_email" name="search_user_email" value="' . (isset($_POST['search_user_email']) ? esc_attr($_POST['search_user_email']) : '') . '" required style="width: 300px; padding: 8px;">';
    $output .= '</p>';
    $output .= '<p>';
    $output .= '<input type="submit" value="Buscar Usuario" style="background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer;">';
    $output .= '</p>';
    $output .= '</form>';
    $output .= '</div>';
    
    // Mostrar datos del usuario si se encontró
    if ($user_data) {
        $user_phone = get_user_meta($user_data->ID, 'meta_xeerpa_phone', true);
        $user_phone_billing = get_user_meta($user_data->ID, 'billing_phone', true);
        $user_phone_shipping = get_user_meta($user_data->ID, 'shipping_phone', true);
        
        $output .= '<div style="margin: 20px 0; padding: 20px; border: 1px solid #ddd; background: #fff;">';
        $output .= '<h3>Información del Usuario</h3>';
        $output .= '<p><strong>Nombre:</strong> ' . esc_html($user_data->display_name) . '</p>';
        $output .= '<p><strong>Email:</strong> ' . esc_html($user_data->user_email) . '</p>';
        $output .= '<p><strong>ID:</strong> ' . esc_html($user_data->ID) . '</p>';
        
        $output .= '<h4>Teléfonos Actuales</h4>';
        $output .= '<p><strong>Teléfono Xeerpa:</strong> ' . esc_html($user_phone) . '</p>';
        $output .= '<p><strong>Teléfono Facturación:</strong> ' . esc_html($user_phone_billing) . '</p>';
        $output .= '<p><strong>Teléfono Envío:</strong> ' . esc_html($user_phone_shipping) . '</p>';
        
        // Formulario para actualizar teléfono
        $output .= '<h4>Actualizar Teléfono</h4>';
        $output .= '<form method="post" action="">';
        $output .= '<input type="hidden" name="user_id" value="' . esc_attr($user_data->ID) . '">';
        $output .= '<input type="hidden" name="search_user_email" value="' . esc_attr($user_data->user_email) . '">';
        $output .= '<p>';
        $output .= '<label for="new_phone">Nuevo teléfono:</label><br>';
        $output .= '<input type="tel" id="new_phone" name="new_phone" value="' . esc_attr($user_phone) . '" required style="width: 300px; padding: 8px;">';
        $output .= '</p>';
        $output .= '<p>';
        $output .= '<input type="submit" name="update_phone" value="Actualizar Teléfono" style="background: #28a745; color: white; padding: 10px 20px; border: none; cursor: pointer;">';
        $output .= '</p>';
        $output .= '</form>';
        $output .= '</div>';
    }
    
    return $output;
}
add_shortcode('change_phone', 'shortcode_change_phone');