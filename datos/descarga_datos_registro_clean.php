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
 * Usa exactamente las mismas reglas de formateo
 */
function generar_contenido_csv_consistente($usuarios) {
    // Headers del CSV (mismos que la tabla HTML)
    $headers = array(
        'XeerpaID',
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
        'ModifiedDate',
        'Source',
        'Marca',
        'robinson',
        'XeerpaCrmID'
    );
    
    $csv_content = implode(',', $headers) . "\n";
    
    foreach ($usuarios as $usuario) {
        // Usar exactamente las mismas reglas que la tabla HTML
        $fila = array(
            escapar_csv($usuario->ID), // XeerpaID
            escapar_csv($usuario->first_name), // FirstName
            escapar_csv($usuario->last_name), // LastName
            escapar_csv($usuario->user_email), // EmailAddress
            escapar_csv(formatear_fecha($usuario->user_registered)), // BirthDate
            escapar_csv(formatear_genero(get_user_meta($usuario->ID, 'gender', true))), // Gender
            escapar_csv(formatear_cedula(get_user_meta($usuario->ID, 'cedula', true))), // IDNumber
            escapar_csv(formatear_telefono(get_user_meta($usuario->ID, 'phone', true))), // MobileNumber
            escapar_csv(get_user_meta($usuario->ID, 'province', true)), // Province
            escapar_csv(get_user_meta($usuario->ID, 'country', true)), // Country
            escapar_csv(formatear_plataforma(get_user_meta($usuario->ID, 'platform', true))), // XeerpaPlataform
            escapar_csv(formatear_si_no(get_user_meta($usuario->ID, 'accept_communications', true))), // AceptaComunicaciones
            escapar_csv(formatear_si_no(get_user_meta($usuario->ID, 'privacy_policy', true))), // PoliticasPrivacidad
            escapar_csv(formatear_fecha($usuario->user_registered)), // ModifiedDate
            escapar_csv('KetchupLovers2024'), // Source
            escapar_csv('KetchupLovers'), // Marca
            escapar_csv(formatear_si_no(get_user_meta($usuario->ID, 'robinson', true))), // robinson
            escapar_csv($usuario->ID) // XeerpaCrmID
        );
        
        $csv_content .= implode(',', $fila) . "\n";
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
    
    // Botón de descarga
    $descarga_url = admin_url('admin-ajax.php?action=descargar_usuarios_csv_directo&nonce=' . wp_create_nonce('descargar_usuarios_csv'));
    $output .= '<div style="text-align: center; margin: 20px 0; padding: 20px; background: #e9ecef; border-radius: 5px;">';
    $output .= '<h4>📥 Descargar Datos</h4>';
    $output .= '<p>Haz clic en el botón para descargar todos los datos en formato CSV</p>';
    $output .= '<a href="' . $descarga_url . '" class="button button-primary button-large" style="font-size: 16px; padding: 12px 24px;">📊 Descargar CSV</a>';
    $output .= '<p><small>El archivo se descargará automáticamente</small></p>';
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
        // Obtener usuarios
        $usuarios = get_users(array(
            'fields' => 'all',
            'number' => -1
        ));
        
        if (empty($usuarios)) {
            wp_die('No se encontraron usuarios.');
        }
        
        // Generar contenido CSV usando las mismas reglas que la tabla HTML
        $contenido_csv = generar_contenido_csv_consistente($usuarios);
        $nombre_archivo = 'usuarios_xeerpa_' . date('Y-m-d_H-i-s') . '.csv';
        
        // Limpiar cualquier output previo
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Configurar headers para descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
        header('Content-Length: ' . strlen($contenido_csv));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // BOM para UTF-8
        echo "\xEF\xBB\xBF";
        echo $contenido_csv;
        exit;
        
    } catch (Exception $e) {
        wp_die('Error al generar el archivo CSV.');
    }
}

/**
 * Shortcode con descarga JavaScript (más robusta)
 */
add_shortcode('descargar_usuarios_excel_js', 'shortcode_descargar_usuarios_excel_js');

function shortcode_descargar_usuarios_excel_js() {
    if (!current_user_can('manage_options')) {
        return '<p>No tienes permisos para acceder a esta función.</p>';
    }
    
    $output = '<div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #dee2e6;">';
    $output .= '<h3>📊 Datos de Usuarios - KetchupLovers</h3>';
    
    // Mostrar vista previa de la tabla
    $output .= generar_vista_previa_usuarios();
    
    // Botón de descarga con JavaScript
    $descarga_url = admin_url('admin-ajax.php?action=descargar_usuarios_csv_directo&nonce=' . wp_create_nonce('descargar_usuarios_csv'));
    $output .= '<div style="text-align: center; margin: 20px 0; padding: 20px; background: #e9ecef; border-radius: 5px;">';
    $output .= '<h4>📥 Descargar Datos</h4>';
    $output .= '<p>Haz clic en el botón para descargar todos los datos en formato CSV</p>';
    $output .= '<button onclick="descargarUsuariosCSV()" class="button button-primary button-large" style="font-size: 16px; padding: 12px 24px;">📊 Descargar CSV</button>';
    $output .= '<p><small>El archivo se descargará automáticamente</small></p>';
    $output .= '</div>';
    
    $output .= '</div>';
    
    // JavaScript para la descarga
    $output .= '<script>
    function descargarUsuariosCSV() {
        const descargaUrl = "' . $descarga_url . '";
        const button = event.target;
        const originalText = button.innerHTML;
        
        // Mostrar loading
        button.innerHTML = "⏳ Generando...";
        button.disabled = true;
        
        // Crear enlace temporal y hacer clic
        const link = document.createElement("a");
        link.href = descargaUrl;
        link.download = "usuarios_xeerpa_' . date('Y-m-d_H-i-s') . '.csv";
        link.style.display = "none";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Restaurar botón después de un momento
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 2000);
    }
    </script>';
    
    return $output;
}

/**
 * Generar vista previa de usuarios en tabla HTML
 */
function generar_vista_previa_usuarios() {
    $usuarios = get_users(array('fields' => 'all', 'number' => 10)); // Mostrar solo 10 para la vista previa
    
    $output = '<div style="overflow-x: auto; margin: 20px 0;">';
    $output .= '<table style="width: 100%; border-collapse: collapse; font-size: 12px;">';
    $output .= '<thead>';
    $output .= '<tr style="background: #f8f9fa;">';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">XeerpaID</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">FirstName</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">LastName</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">EmailAddress</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">BirthDate</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Gender</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">IDNumber</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">MobileNumber</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Province</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Country</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">XeerpaPlataform</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">AceptaComunicaciones</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">PoliticasPrivacidad</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">ModifiedDate</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Source</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Marca</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">robinson</th>';
    $output .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">XeerpaCrmID</th>';
    $output .= '</tr>';
    $output .= '</thead>';
    $output .= '<tbody>';
    
    foreach ($usuarios as $usuario) {
        $output .= '<tr>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . esc_html($usuario->ID) . '</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . esc_html($usuario->first_name) . '</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . esc_html($usuario->last_name) . '</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . esc_html($usuario->user_email) . '</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . esc_html(formatear_fecha($usuario->user_registered)) . '</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . esc_html(formatear_genero(get_user_meta($usuario->ID, 'gender', true))) . '</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . esc_html(formatear_cedula(get_user_meta($usuario->ID, 'cedula', true))) . '</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . esc_html(formatear_telefono(get_user_meta($usuario->ID, 'phone', true))) . '</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . esc_html(get_user_meta($usuario->ID, 'province', true)) . '</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . esc_html(get_user_meta($usuario->ID, 'country', true)) . '</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . esc_html(formatear_plataforma(get_user_meta($usuario->ID, 'platform', true))) . '</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . esc_html(formatear_si_no(get_user_meta($usuario->ID, 'accept_communications', true))) . '</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . esc_html(formatear_si_no(get_user_meta($usuario->ID, 'privacy_policy', true))) . '</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . esc_html(formatear_fecha($usuario->user_registered)) . '</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">KetchupLovers2024</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">KetchupLovers</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . esc_html(formatear_si_no(get_user_meta($usuario->ID, 'robinson', true))) . '</td>';
        $output .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . esc_html($usuario->ID) . '</td>';
        $output .= '</tr>';
    }
    
    $output .= '</tbody>';
    $output .= '</table>';
    $output .= '<p><small>Mostrando los primeros 10 usuarios. El archivo CSV incluirá todos los usuarios.</small></p>';
    $output .= '<p><small>Total de usuarios: ' . count_users()['total_users'] . '</small></p>';
    $output .= '</div>';
    
    return $output;
}

/**
 * Escapar valores para CSV
 */
function escapar_csv($valor) {
    if (is_array($valor)) {
        $valor = implode('; ', $valor);
    }
    
    $valor = str_replace('"', '""', $valor);
    
    if (strpos($valor, ',') !== false || strpos($valor, '"') !== false || strpos($valor, "\n") !== false) {
        $valor = '"' . $valor . '"';
    }
    
    return $valor;
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
 * Formatear cédula (solo números)
 */
function formatear_cedula($cedula) {
    return preg_replace('/[^0-9]/', '', $cedula);
}

/**
 * Formatear teléfono
 */
function formatear_telefono($telefono) {
    $telefono = preg_replace('/[^0-9]/', '', $telefono);
    if (strlen($telefono) == 8) {
        return '506' . $telefono;
    }
    return $telefono;
}

/**
 * Formatear plataforma
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
