<?php

/**
 * Shortcode para mostrar información de puntos a comprar
 * Compatible con Elementor y obtiene el precio del producto actual
 */
function kerns_pto_to_buy($atts = array()) {
    // Default attributes - valores por defecto para el shortcode
    $atts = shortcode_atts(array(
        'show_price' => 'yes',
        'show_points' => 'yes',
        'points_per_unit' => null
    ), $atts, 'pto_to_buy');

    // Get the current product ID from the loop
    $product_id = get_the_ID();
    if( is_user_logged_in() ){
        $user_id = get_current_user_id();
        $user_points = get_user_meta($user_id, 'wps_wallet', true);
    }else{
        $user_points = 0;
    }
    $user_cart_total = WC()->cart->get_total('raw');
    //**
    // Get the cost per point from the CPT 1282 
    // modify the cost per point to be the cost per point of the product
    // https://kernslovers.com/kerns-producto/ketchup-776g/
    //  */
    $cost_pto_cpt = get_field('puntos', 1282);

    // Check if we're in a WooCommerce product loop
    if (!$product_id || get_post_type($product_id) !== 'product') {
        // If not in a product loop, try to get product from global
        global $product;
        if ($product && is_object($product)) {
            $product_id = $product->get_id();
        } else {
            return '<div class="pto-to-buy-error">Error: No se encontró un producto válido</div>';
        }
    }

    // Get the product object
    $product = wc_get_product($product_id);
    
    
    if (!$product) {
        return '<div class="pto-to-buy-error">Error: Producto no encontrado</div>';
    }

   
    // Get product price
    $price = $product->get_price();
    $product_url = $product->get_permalink();
    
    // Convert values to numbers to avoid string operation errors
    $price = floatval($price);
    $user_points = floatval($user_points);
    
    // Calculate points needed (assuming 1 point = $1)
    $points_needed = $price - $user_points;

    if( isset($atts['points_per_unit']) ){
        $buy_needed_cpt =  $points_needed / floatval($atts['points_per_unit']);
    }else{
        $buy_needed_cpt =  $points_needed / floatval($cost_pto_cpt);
    }

    // Always round up to ensure customer can afford the product
    $buy_needed = ceil($buy_needed_cpt);

    if( $buy_needed > 0 ){
    // Build HTML output
    $html = '<div class="pto-to-buy-container" data-product-id="' . esc_attr($product_id) . '" data-points-needed="' . esc_attr($points_needed) . '" data-buy-needed="' . esc_attr($buy_needed) . '">';
        $html .= '<div class="pto-to-buy-row">';
            $html .= '<div class="pto-to-buy-col col-1">Aún te faltan</div>';
            $html .= '<div class="pto-to-buy-col col-2">';
                $html .= '<img src="'. THEME_URI .'/assets/images/ketchup_puntos.webp" alt="Cantidad de ketchup a comprar, para obtener los puntos necesarios para comprar el producto" class="pto-to-buy-image">';
                $html .= '<span class="pto-needed">' . esc_html($buy_needed) . '</span>';
            $html .= '</div>';
            $html .= '<div class="pto-to-buy-col col-3">kétchups <br>de 776g</div>';
        $html .='</div>';   
    $html .= '</div>';
    }else{
        $html = '<div class="pto-to-buy-container_canjear" data-product-id="' . esc_attr($product_id) . '" data-points-needed="' . esc_attr($points_needed) . '" data-buy-needed="' . esc_attr($buy_needed) . '">';
            $html .= '<div class="pto-to-buy-row">';
                $html .= '<div class="pto-to-buy-box-yatienes">Ya tienes suficientes puntos</div>';
                $html .= '<div class="pto-to-buy-box-canjear"><a href="'. esc_url($product_url) .'" class="white-text">Canjear</a></div>';

            $html .='</div>';   
        $html .= '</div>';
    }
    return $html;
}

/**
 * Register the shortcode
 * Registra el shortcode para que WordPress lo reconozca
 */
add_shortcode('pto_to_buy', 'kerns_pto_to_buy');

/**
 * Add shortcode support for Elementor
 * Hace que el shortcode sea compatible con el editor de Elementor
 */
function kerns_add_pto_to_buy_elementor_support() {
    // Check if Elementor is active
    if (class_exists('\Elementor\Plugin')) {
        // Add shortcode to Elementor's shortcode widget
        add_action('elementor/widgets/register', function($widgets_manager) {
            // This makes the shortcode available in Elementor's shortcode widget
            // No additional code needed as Elementor automatically detects registered shortcodes
        });
    }
}
add_action('init', 'kerns_add_pto_to_buy_elementor_support');

/**
 * Add custom CSS for Elementor compatibility
 * Agrega estilos CSS para mejor compatibilidad con Elementor
 */
function kerns_pto_to_buy_elementor_styles() {
    if (class_exists('\Elementor\Plugin')) {
        echo '<style>
        .pto-to-buy {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin: 10px 0;
        }
        .pto-to-buy .pto-price {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .pto-to-buy .pto-points {
            color: #666;
        }
        .pto-to-buy-error {
            color: #d32f2f;
            background: #ffebee;
            padding: 10px;
            border-radius: 4px;
        }
        </style>';
    }
}
add_action('wp_head', 'kerns_pto_to_buy_elementor_styles');