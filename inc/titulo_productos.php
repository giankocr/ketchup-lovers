<?php
/**
 * Shortcode para mostrar el campo 'html_titulo' del post actual en un loop de Elementor.
 * 
 * Este shortcode se puede usar dentro de un loop de Elementor sin necesidad de pasar el ID del post.
 * Obtiene el valor del campo personalizado 'html_titulo' del post actual y lo muestra.
 * 
 * Uso: [kl_html_titulo]
 */
function kl_html_titulo_shortcode() {
    // Obtiene el ID del post actual en el loop
    $post_id = get_the_ID();

    // Verifica que haya un ID de post válido
    if ( ! $post_id ) {
        // Si no hay post, retorna vacío
        return '';
    }

    // Obtiene el valor del campo personalizado 'html_titulo'
    $html_titulo = get_field('html_titulo', $post_id);

    // Si el campo tiene contenido, lo retorna
    if ( $html_titulo ) {
        // Escapa el HTML para seguridad si es necesario, o simplemente retorna el HTML si confías en el contenido
        // return esc_html($html_titulo); // Si quieres mostrar como texto plano
        return $html_titulo; // Si quieres permitir HTML
    }

    // Si no hay contenido, retorna vacío
    return '';
}
// Registra el shortcode [kl_html_titulo]
add_shortcode('kl_producto_titulo', 'kl_html_titulo_shortcode');