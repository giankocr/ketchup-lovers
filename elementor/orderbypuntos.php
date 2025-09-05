<?php

add_action('elementor/query/order_by_puntos', 'register_order_by_puntos_widget');

function register_order_by_puntos_widget($query) {
  // Verificar que $query es un objeto WP_Query válido
  if (!$query instanceof WP_Query) {
    return;
  }
  
   
  
  // Configurar el ordenamiento por puntos
  $query->set('posts_per_page', 16);
  $query->set('orderby', 'meta_value');
  $query->set('meta_key', 'puntos');
  $query->set('order', 'ASC');
  
  
}

