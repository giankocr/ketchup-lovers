jQuery(function($) {
    
    // Mostrar botón después de que la página cargue
    setTimeout(function() {
        $('.canjear-prenda-button').show();
    }, 1000);
    
    // Manejar botón "Canjear prenda ahora"
    $(document).on('click', '.canjear-prenda-button', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const checkoutUrl = $button.data('checkout-url');
        
        // Deshabilitar botón y mostrar estado de carga
        $button.text('Procesando...').prop('disabled', true);
        
        // Agregar al carrito y redirigir directamente al checkout
        const $form = $('.cart, .variations_form, form.cart');
        
        if ($form.length > 0) {
            
            // Agregar parámetro para ir directo al checkout
            const $checkoutInput = $('<input>').attr({
                type: 'hidden',
                name: 'add-to-cart',
                value: $form.find('[name="add-to-cart"]').val() || $form.data('product-id')
            });
            
            // Agregar parámetro para redirección directa
            const $redirectInput = $('<input>').attr({
                type: 'hidden',
                name: 'redirect_to_checkout',
                value: '1'
            });
            
            // Agregar los campos al formulario
            $form.append($checkoutInput).append($redirectInput);
            
            // Enviar formulario
            $form.submit();
        } else {
            window.location.href = checkoutUrl;
        }
    });
    
    // Manejar botón "Agregar al carrito"
    $(document).on('click', '.agregar-carrito-button', function(e) {
        e.preventDefault();
        
        $(this).text('Agregando...').prop('disabled', true);
        
        // Simular click en el botón nativo de WooCommerce
        $('.single_add_to_cart_button').click();
        
        // Restaurar texto después de un delay
        setTimeout(function() {
            $(this).text('AGREGAR AL CARRITO').prop('disabled', false);
        }.bind(this), 2000);
    });
    
    // Controles de cantidad para productos simples
    $(document).on('click', '.cantidad-menos', function(e) {
        e.preventDefault();
        const input = $('input.qty');
        let value = parseInt(input.val()) || 1;
        if (value > 1) {
            input.val(value - 1).trigger('change');
            $('.cantidad-numero').text(value - 1);
        }
    });
    
    $(document).on('click', '.cantidad-mas', function(e) {
        e.preventDefault();
        const input = $('input.qty');
        let value = parseInt(input.val()) || 1;
        input.val(value + 1).trigger('change');
        $('.cantidad-numero').text(value + 1);
    });
    
});
