<?php
/**
 * Obtiene la cantidad total de productos publicados en WooCommerce
 * @return int
 */
function bfg_get_total_published_products() {
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    );
    
    $products = get_posts($args);
    return count($products);
}

/**
 * Obtiene la cantidad de productos únicos que ha comprado el usuario actual
 * @param int $user_id ID del usuario (opcional, si no se proporciona usa el usuario actual)
 * @return int
 */
function bfg_get_user_purchased_products_count($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return 0;
    }
    
    // Obtener todas las órdenes completadas del usuario
    $customer_orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status'      => array('completed', 'processing'),
        'limit'       => -1,
    ));
    
    $purchased_products = array();
    
    foreach ($customer_orders as $order) {
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if ($product_id) {
                $purchased_products[$product_id] = true;
            }
        }
    }
    
    return count($purchased_products);
}

/**
 * Shortcode para crear una gráfica de progreso de colección.
 * Ejemplo de uso: [grafica_coleccion valor_actual="3" valor_total="10" texto_titulo="Mi Progreso"]
 * O simplemente: [grafica_coleccion] para usar los valores automáticos.
 */
function bfg_crear_grafica_coleccion_shortcode($atts) {
    // --- 1. Definición de atributos y valores por defecto ---
    $atts = shortcode_atts(
        array(
            'valor_actual' => 'auto',
            'valor_total'  => 'auto',
            'texto_titulo' => 'Tu colección',
        ),
        $atts,
        'grafica_coleccion'
    );

    // --- 2. Obtención de valores (Automáticos o Manuales) ---
    if ($atts['valor_actual'] === 'auto') {
        // Llama a una función que cuenta los productos únicos comprados por el usuario.
        $valor_actual = function_exists('bfg_get_user_purchased_products_count') ? bfg_get_user_purchased_products_count() : 0;
    } else {
        $valor_actual = intval($atts['valor_actual']);
    }
    
    if ($atts['valor_total'] === 'auto') {
        // Llama a una función que cuenta el total de productos publicados.
        $valor_total = function_exists('bfg_get_total_published_products') ? bfg_get_total_published_products() : 1; // Evita dividir por cero por defecto
    } else {
        $valor_total = intval($atts['valor_total']);
    }
    
    // Limpia el texto del título para seguridad.
    $texto_titulo = esc_html($atts['texto_titulo']);
    
    // --- 3. Cálculos Correctos para el SVG ---
    $radio = 100;          // Radio del arco en el SVG.
    $stroke_width = 25;    // Grosor de la línea en el SVG.
    $color_fondo = '#AE5454';
    $color_progreso = '#FF0000';

    // La longitud REAL del camino es un SEMI-CÍRCULO (π * r).
    $longitud_del_arco = pi() * $radio;

    // Calcula el progreso como una fracción (ej: 0.5 para 50%).
    $progreso_fraccion = ($valor_total > 0) ? ($valor_actual / $valor_total) : 0;

    // La longitud del trazo de progreso a dibujar.
    $stroke_dash = $longitud_del_arco * $progreso_fraccion;

    // --- 4. Generación del HTML y SVG dinámico ---
    // Se usa HEREDOC (<<<HTML) para mayor claridad.
    $html_output = <<<HTML
    <div class="grafica-coleccion">
        <style>
            /* Estilos para la Gráfica de Colección */
            .grafica-coleccion {
            }
            .grafica-coleccion-texto .texto-titulo {
                font-family: "TangoSans", sans-serif;
                font-size: 16px;
                font-weight: bold;
                fill: #ffffff;
                text-anchor: middle;
            }

            .grafica-coleccion-texto .texto-valor {
                font-family: "TangoSans", sans-serif;
                font-size: 40px;
                font-weight: bold;
                fill: #FF0000;
                text-anchor: middle;
            }

            .grafica-progreso {
                transition: stroke-dasharray 0.5s ease-in-out;
            }
        </style>
        <svg width="250" height="150" viewBox="0 0 250 150" xmlns="http://www.w3.org/2000/svg" role="img" aria-valuenow="{$valor_actual}" aria-valuemin="0" aria-valuemax="{$valor_total}">
            <g class="grafica-coleccion-grupo" transform="translate(125, 125)">
                <path
                    class="grafica-fondo"
                    d="M -100 0 A 100 100 0 0 1 100 0"
                    fill="none"
                    stroke="{$color_fondo}"
                    stroke-width="{$stroke_width}"
                    stroke-linecap="round"
                />
                <path
                    class="grafica-progreso"
                    d="M -100 0 A 100 100 0 0 1 100 0"
                    fill="none"
                    stroke="{$color_progreso}"
                    stroke-width="{$stroke_width}"
                    stroke-linecap="round"
                    stroke-dasharray="{$stroke_dash} {$longitud_del_arco}"
                />
            </g>
            <g class="grafica-coleccion-texto">
                <text x="125" y="75" class="texto-titulo">{$texto_titulo}</text>
                <text x="125" y="120" class="texto-valor">{$valor_actual}/{$valor_total}</text>
            </g>
        </svg>
    </div>
HTML;

    // --- 5. Devolver el HTML generado ---
    return $html_output;
}


// --- FUNCIONES DE EJEMPLO ---
// Si no tienes estas funciones, puedes usar estas como base.
if (!function_exists('bfg_get_user_purchased_products_count')) {
    function bfg_get_user_purchased_products_count() {
        // Lógica para contar productos únicos del usuario logueado.
        // Este es un valor de ejemplo.
        return 5; 
    }
}

if (!function_exists('bfg_get_total_published_products')) {
    function bfg_get_total_published_products() {
        // Lógica para contar todos los productos publicados.
        // Este es un valor de ejemplo.
        return 20; 
    }
}
// Registrar el shortcode en WordPress para poder usarlo
add_shortcode('grafica_coleccion', 'bfg_crear_grafica_coleccion_shortcode');

/**
 * Shortcode adicional para mostrar solo la cantidad de productos comprados
 */
function bfg_productos_comprados_shortcode($atts) {
    // Usa automáticamente el usuario logueado
    $count = bfg_get_user_purchased_products_count();
    return $count;
}
add_shortcode('productos_comprados', 'bfg_productos_comprados_shortcode');

/**
 * Shortcode adicional para mostrar solo la cantidad total de productos
 */
function bfg_total_productos_shortcode($atts) {
    $count = bfg_get_total_published_products();
    return $count;
}
add_shortcode('total_productos', 'bfg_total_productos_shortcode');

/**[grafica_coleccion] [wps-wallet] */