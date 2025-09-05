<?php

/**
 * Adding custom json locations from child theme
 * WC Provincia-Canton-Distrito
 */
function woo_prov_cant_dist_json($json_file) {
    $json_file = get_stylesheet_directory_uri() . '/assets/js/prov-cant-dist.json';
    
    return $json_file;
}
add_filter('wcpcd_prov_cant_dist_json', 'woo_prov_cant_dist_json');

function woo_prov_cant_dist_placeholder($placeholder) {
    $placeholder = 'Seleccionar un municipio';
    return $placeholder;
}
add_filter('wcpcd_city_field_placeholder', 'woo_prov_cant_dist_placeholder');

/**
 * Mapea los Departamentos de Guatemala para WooCommerce
 * 
 * @param array $states Array de estados por país
 * @return array Estados modificados
 */
function woo_wc_states($states) {
    $states['GT'] = array(
        'AV' => 'ALTA VERAPAZ',
        'BV' => 'BAJA VERAPAZ',
        'CM' => 'CHIMALTENANGO',
        'CQ' => 'CHIQUIMULA',
        'EP' => 'EL PROGRESO',
        'ES' => 'ESCUINTLA',
        'GT' => 'GUATEMALA',
        'HU' => 'HUEHUETENANGO',
        'IZ' => 'IZABAL',
        'JU' => 'JUTIAPA',
        'PE' => 'PETÉN',
        'QT' => 'QUETZALTENANGO',
        'QU' => 'QUICHÉ',
        'RT' => 'RETALHULEU',
        'SA' => 'SACATEPÉQUEZ',
        'SM' => 'SAN MARCOS',
        'JA' => 'JALAPA',
        'SR' => 'SANTA ROSA',
        'SO' => 'SOLOLÁ',
        'SU' => 'SUCHITEPÉQUEZ',
        'TO' => 'TOTONICAPÁN',
        'ZA' => 'ZACAPA'
    );
    
    return $states;
}
add_filter('woocommerce_states', 'woo_wc_states');

/**
 * Modifica el label del campo de ciudad en los campos de dirección por defecto
 * 
 * @param array $fields Campos de dirección
 * @return array Campos modificados
 */
function modify_city_field_label($fields) {
    if (isset($fields['city'])) {
        $fields['city']['label'] = 'Municipio';
    }
    return $fields;
}
add_filter('woocommerce_default_address_fields', 'modify_city_field_label');