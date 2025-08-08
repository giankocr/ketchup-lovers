<?php
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('jquery');
    $script = "
    jQuery(function($){
        // Cuando se haga clic en el botón de agregar al carrito (productos simples)
        $('body').on('added_to_cart', function(){
            $('#add-to-cart-popup').fadeIn();
        });

        // Cerrar el popup
        $('#add-to-cart-popup').on('click', '#close-popup', function(){
            $('#add-to-cart-popup').fadeOut();
        });

        // También puedes cerrar el popup haciendo clic fuera del contenido
        $('#add-to-cart-popup').on('click', function(e){
            if(e.target === this){
                $(this).fadeOut();
            }
        });
    });
    ";
    wp_add_inline_script('jquery', $script);
});

/**
 * Cambia el ícono '×' para eliminar productos en el carrito de WooCommerce.
 * Utiliza el filtro 'woocommerce_cart_item_remove_link'.
 */
add_filter( 'woocommerce_cart_item_remove_link', 'cambiar_icono_eliminar_del_carrito', 10, 2 );

function cambiar_icono_eliminar_del_carrito( $html, $cart_item_key ) {
    // Reemplaza la '×' por el HTML de tu nuevo ícono.
    // En este caso, un ícono de papelera de Font Awesome.
    $nuevo_icono_html = '<img src="'.THEME_URI.'/assets/images/basurero_cart.svg" alt="Eliminar" aria-hidden="true" />';

    $html = str_replace( '&times;', $nuevo_icono_html, $html );
    return $html;
}