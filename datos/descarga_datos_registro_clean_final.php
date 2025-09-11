<?php
/**
 * Shortcode para descargar datos de usuarios en Excel
 * Incluye información básica y metadatos de Xeerpa
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generar contenido CSV consistente con la tabla HTML
 * Usa exactamente las mismas reglas de formateo y metadatos
 * Soporta separadores personalizados y UTF-8
 */
function generar_contenido_csv_consistente($usuarios, $separador = ',') {
    // Headers del CSV (mismos que la tabla HTML del backup)
    $headers = array(
        'FirstName',
        'LastName', 
        'EmailAddress',
        'BirthDate',
        'Gender',
        'IDNumber',
        'MobileNumber',
        'Province',
        'Country',
        'XeerpaPlataform',
        'AceptaComunicaciones',
        'PoliticasPrivacidad',
        'Source',
        'Marca',
        'XeerpaIDToken',
        'ModifiedDate',
        'CreatedDate'
    );
    
    $csv_content = implode($separador, $headers) . "\n";
    
    foreach ($usuarios as $usuario) {
        // Usar exactamente las mismas reglas que la tabla HTML del backup
        $fila = array(
            escapar_csv($usuario->first_name, $separador), // FirstName
            escapar_csv($usuario->last_name, $separador), // LastName
            escapar_csv($usuario->user_email, $separador), // EmailAddress
            escapar_csv(formatear_fecha(get_user_meta($usuario->ID, 'meta_xeerpa_birthday', true)), $separador), // BirthDate
            escapar_csv(formatear_genero(get_user_meta($usuario->ID, 'meta_xeerpa_gender', true)), $separador), // Gender
            escapar_csv(formatear_cedula(get_user_meta($usuario->ID, 'meta_xeerpa_IDCedula', true)), $separador, true), // IDNumber (forzar como texto)
            escapar_csv(formatear_telefono(get_user_meta($usuario->ID, 'meta_xeerpa_phone', true)), $separador, true), // MobileNumber (forzar como texto)
            escapar_csv(get_user_meta($usuario->ID, 'meta_xeerpa_province', true), $separador), // Province
            escapar_csv('Guatemala', $separador), // Country (fijo como en la tabla)
            escapar_csv(formatear_plataforma(get_user_meta($usuario->ID, 'meta_xeerpa_sn', true)), $separador), // XeerpaPlataform
            escapar_csv('Si', $separador), // AceptaComunicaciones (fijo como en la tabla)
            escapar_csv('Si', $separador), // PoliticasPrivacidad (fijo como en la tabla)
            escapar_csv('KETCHUP_LOVERS2025', $separador), // Source (fijo como en la tabla)
            escapar_csv('Kerns', $separador), // Marca (fijo como en la tabla)
            escapar_csv(get_user_meta($usuario->ID, 'meta_xeerpa_idtoken', true), $separador), // XeerpaIDToken
            escapar_csv(formatear_fecha($usuario->user_registered), $separador), // ModifiedDate
            escapar_csv(formatear_fecha($usuario->user_registered), $separador) // CreatedDate
        );
        
        $csv_content .= implode($separador, $fila) . "\n";
    }
    
    return $csv_content;
}

/**
 * Registrar el shortcode principal
 */
add_shortcode('descargar_usuarios_excel', 'shortcode_descargar_usuarios_excel_simple');

/**
 * Shortcode principal que muestra tabla y botón de descarga
 */
function shortcode_descargar_usuarios_excel_simple() {
    if (!current_user_can('manage_options')) {
        return '<p>No tienes permisos para acceder a esta función.</p>';
    }
    
    $output = '<div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #dee2e6;">';
    $output .= '<h3>📊 Datos de Usuarios - KetchupLovers</h3>';
    
    // Mostrar vista previa de la tabla
    $output .= generar_vista_previa_usuarios();
    
    // Opciones de descarga
    $nonce = wp_create_nonce('descargar_usuarios_csv');
    $output .= '<div style="text-align: center; margin: 20px 0; padding: 20px; background: #e9ecef; border-radius: 5px;">';
    $output .= '<h4>📥 Descargar Datos</h4>';
    $output .= '<p>Elige el formato de separador y descarga todos los datos en formato CSV</p>';
    
    // Botones de descarga con diferentes separadores
    $output .= '<div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin: 20px 0;">';
    
    // Botón para separador de comas
    $descarga_url_comas = admin_url('admin-ajax.php?action=descargar_usuarios_csv_directo&separador=,&nonce=' . $nonce);
    $output .= '<a href="' . $descarga_url_comas . '" class="button button-primary button-large" style="font-size: 16px; padding: 12px 24px;">📊 CSV con Comas (,)</a>';
    
    // Botón para separador de punto y coma
    $descarga_url_punto_coma = admin_url('admin-ajax.php?action=descargar_usuarios_csv_directo&separador=;&nonce=' . $nonce);
    $output .= '<a href="' . $descarga_url_punto_coma . '" class="button button-secondary button-large" style="font-size: 16px; padding: 12px 24px;">📊 CSV con Punto y Coma (;)</a>';
    
    $output .= '</div>';
    
    $output .= '<p><small>El archivo se descargará automáticamente con codificación UTF-8 (soporta tildes, ñ y caracteres especiales)</small></p>';
    $output .= '</div>';
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Handler AJAX para descarga directa
 */
add_action('wp_ajax_descargar_usuarios_csv_directo', 'manejar_descarga_csv_directo');

function manejar_descarga_csv_directo() {
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para acceder a esta función.');
    }
    
    // Verificar nonce
    if (!wp_verify_nonce($_GET['nonce'], 'descargar_usuarios_csv')) {
        wp_die('Token de seguridad inválido.');
    }
    
    try {
        // Obtener separador (por defecto coma)
        $separador = isset($_GET['separador']) ? sanitize_text_field($_GET['separador']) : ',';
        
        // Validar separador permitido
        if (!in_array($separador, [',', ';'])) {
            $separador = ',';
        }
        
        // Obtener usuarios
        $usuarios = get_users(array(
            'fields' => 'all',
            'number' => -1
        ));
        
        if (empty($usuarios)) {
            wp_die('No se encontraron usuarios.');
        }
        
        // Generar contenido CSV usando las mismas reglas que la tabla HTML
        $contenido_csv = generar_contenido_csv_consistente($usuarios, $separador);
        $nombre_archivo = 'usuarios_xeerpa_' . date('Y-m-d_H-i-s') . '.csv';
        
        // Limpiar cualquier output previo
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Configurar headers para descarga con UTF-8
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
        header('Content-Length: ' . strlen($contenido_csv));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // BOM para UTF-8 (importante para Excel y otros programas)
        echo "\xEF\xBB\xBF";
        echo $contenido_csv;
        exit;
        
    } catch (Exception $e) {
        wp_die('Error al generar el archivo CSV.');
    }
}

/**
 * Generar vista previa de usuarios en tabla HTML
 * Usa exactamente los mismos datos y metadatos que el archivo CSV
 */
function generar_vista_previa_usuarios() {
    $usuarios = get_users(array('fields' => 'all', 'number' => 10, 'orderby' => 'user_registered', 'order' => 'DESC')); // Mostrar solo 10 para la vista previa
    
    $html = '<div class="vista-previa-usuarios">';
    $html .= '<h3>Vista Previa - Ultimos 10 Usuarios</h3>';
    $html .= '<div class="tabla-container">';
    $html .= '<table class="tabla-usuarios">';
    
    // Encabezados de la tabla
    $html .= '<thead><tr>';
    $html .= '<th>FirstName</th>';
    $html .= '<th>LastName</th>';
    $html .= '<th>EmailAddress</th>';
    $html .= '<th>BirthDate</th>';
    $html .= '<th>Gender</th>';
    $html .= '<th>IDNumber</th>';
    $html .= '<th>MobileNumber</th>';
    $html .= '<th>Province</th>';
    $html .= '<th>Country</th>';
    $html .= '<th>XeerpaPlataform</th>';
    $html .= '<th>AceptaComunicaciones</th>';
    $html .= '<th>PoliticasPrivacidad</th>';
    $html .= '<th>Source</th>';
    $html .= '<th>Marca</th>';
    $html .= '<th>XeerpaIDToken</th>';
    $html .= '<th>ModifiedDate</th>';
    $html .= '<th>CreatedDate</th>';
    $html .= '</tr></thead>';
    
    // Datos de usuarios
    $html .= '<tbody>';
    foreach ($usuarios as $usuario) {
        $html .= '<tr>';
        $html .= '<td>' . esc_html($usuario->first_name) . '</td>';
        $html .= '<td>' . esc_html($usuario->last_name) . '</td>';
        $html .= '<td>' . esc_html($usuario->user_email) . '</td>';
        $html .= '<td>' . esc_html(formatear_fecha(get_user_meta($usuario->ID, 'meta_xeerpa_birthday', true))) . '</td>';
        $html .= '<td>' . esc_html(formatear_genero(get_user_meta($usuario->ID, 'meta_xeerpa_gender', true))) . '</td>';
        $html .= '<td>' . esc_html(formatear_cedula(get_user_meta($usuario->ID, 'meta_xeerpa_IDCedula', true))) . '</td>';
        $html .= '<td>' . esc_html(formatear_telefono(get_user_meta($usuario->ID, 'meta_xeerpa_phone', true))) . '</td>';
        $html .= '<td>' . esc_html(get_user_meta($usuario->ID, 'meta_xeerpa_province', true)) . '</td>';
        $html .= '<td>' . esc_html('Guatemala') . '</td>';
        $html .= '<td>' . esc_html(formatear_plataforma(get_user_meta($usuario->ID, 'meta_xeerpa_sn', true))) . '</td>';
        $html .= '<td>' . esc_html('Si') . '</td>';
        $html .= '<td>' . esc_html('Si') . '</td>';
        $html .= '<td>' . esc_html('KETCHUP_LOVERS2025') . '</td>';
        $html .= '<td>' . esc_html('Kerns') . '</td>';
        $html .= '<td>' . esc_html(get_user_meta($usuario->ID, 'meta_xeerpa_idtoken', true)) . '</td>';
        $html .= '<td>' . esc_html(formatear_fecha($usuario->user_registered)) . '</td>';
        $html .= '<td>' . esc_html(formatear_fecha($usuario->user_registered)) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    $html .= '<p class="info-tabla">Mostrando los primeros 10 usuarios de un total de ' . count_users()['total_users'] . ' usuarios.</p>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Escapar datos para CSV con soporte UTF-8 y separadores personalizados
 */
function escapar_csv($dato, $separador = ',', $forzar_texto = false) {
    if (is_null($dato) || $dato === '') {
        return '';
    }
    
    // Convertir a string
    $dato = (string) $dato;
    
    // Convertir a UTF-8 si no lo está
    if (!mb_check_encoding($dato, 'UTF-8')) {
        $dato = mb_convert_encoding($dato, 'UTF-8', 'auto');
    }
    
    // Escapar comillas dobles
    $dato = str_replace('"', '""', $dato);
    
    // Si es un campo que debe ser tratado como texto (IDNumber, MobileNumber) o forzar_texto es true
    $es_campo_texto = $forzar_texto || 
                     (is_numeric($dato) && (strlen($dato) > 10 || strpos($dato, '0') === 0));
    
    // Envolver en comillas si:
    // 1. Es un campo de texto que debe ser tratado como tal
    // 2. Contiene el separador, saltos de línea, comillas o caracteres especiales
    if ($es_campo_texto ||
        strpos($dato, $separador) !== false || 
        strpos($dato, "\n") !== false || 
        strpos($dato, "\r") !== false ||
        strpos($dato, '"') !== false ||
        preg_match('/[^\x20-\x7E]/', $dato)) { // Caracteres no ASCII (tildes, ñ, etc.)
        $dato = '"' . $dato . '"';
    }
    
    return $dato;
}

/**
 * Formatear fecha a DD/MM/AAAA
 */
function formatear_fecha($fecha) {
    if (empty($fecha)) return '';
    return date('d/m/Y', strtotime($fecha));
}

/**
 * Formatear género
 */
function formatear_genero($genero) {
    $generos = array(
        'f' => 'Femenino',
        'm' => 'Masculino',
        'nb' => 'No Binario',
        'na' => 'Prefiero no decir'
    );
    return isset($generos[$genero]) ? $generos[$genero] : '';
}

/**
 * Formatear cédula (eliminar espacios y guiones)
 */
function formatear_cedula($cedula) {
    if (empty($cedula)) {
        return '';
    }
    
    // Eliminar espacios, guiones y caracteres especiales
    $cedula = preg_replace('/[^0-9]/', '', $cedula);
    
    // Devolver como texto (con comillas si es necesario para evitar que Excel lo trate como número)
    return $cedula;
}

/**
 * Formatear teléfono a formato 50288888888
 */
function formatear_telefono($telefono) {
    if (empty($telefono)) {
        return '';
    }
    
    // Eliminar todos los caracteres que no sean números
    $telefono = preg_replace('/[^0-9]/', '', $telefono);
    
    // Si no tiene código de país, agregar 502 (Guatemala)
    if (strlen($telefono) == 8) {
        $telefono = '502' . $telefono;
    }
    
    // Devolver como texto (con comillas si es necesario para evitar que Excel lo trate como número)
    return $telefono;
}

/**
 * Formatear plataforma según especificación
 */
function formatear_plataforma($plataforma) {
    $plataformas = array(
        'google' => 'GO',
        'facebook' => 'FB',
        'instagram' => 'IG',
        'tiktok' => 'TT'
    );
    return isset($plataformas[strtolower($plataforma)]) ? $plataformas[strtolower($plataforma)] : '';
}

/**
 * Formatear Sí/No
 */
function formatear_si_no($valor) {
    if (empty($valor) || $valor === '0' || $valor === 'no' || $valor === 'false') {
        return 'No';
    }
    return 'Sí';
}

/**
 * Agregar estilos CSS para el botón y la tabla
 */
add_action('wp_head', 'agregar_estilos_descarga_usuarios');

function agregar_estilos_descarga_usuarios() {
    if (has_shortcode(get_post()->post_content ?? '', 'descargar_usuarios_excel')) {
        ?>
        <style>
        .vista-previa-usuarios {
            margin: 20px 0;
        }
        
        .tabla-container {
            overflow-x: auto;
            margin: 20px 0;
        }
        
        .tabla-usuarios {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            background: white;
        }
        
        .tabla-usuarios th,
        .tabla-usuarios td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }
        
        .tabla-usuarios th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .tabla-usuarios tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .tabla-usuarios tr:hover {
            background: #e9ecef;
        }
        
        .info-tabla {
            font-size: 12px;
            color: #6c757d;
            margin-top: 10px;
        }
        
        .btn-descargar-excel {
            display: inline-block;
            padding: 10px 20px;
            background: #007cba;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .btn-descargar-excel:hover {
            background: #005a87;
            color: white;
        }
        </style>
        <?php
    }
}
