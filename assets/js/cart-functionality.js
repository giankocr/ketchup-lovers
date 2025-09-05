/**
 * Cart Functionality JavaScript
 * 
 * This file handles cart-related functionality for the Ketchup Lovers theme
 */

jQuery(function($){
    'use strict';
    
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