jQuery(function($) {
    // Seleccionamos los elementos
    const form = $('.variations_form, form.cart');
    const canjearButton = $('.canjear-prenda-button');
    const agregarCarritoButton = $('.agregar-carrito-button');
    const quantityInput = $('input.qty');
    const numberSpan = $('.cantidad-numero');
    const minusBtn = $('.cantidad-menos');
    const plusBtn = $('.cantidad-mas');

    // Sincroniza el número visual con el input real
    function syncCantidadVisual() {
        let cantidad = parseInt(quantityInput.val());
        if (isNaN(cantidad) || cantidad < 1) cantidad = 1;
        numberSpan.text(cantidad);
    }

    // Cambia la cantidad al hacer click en los botones
    minusBtn.on('click', function(e) {
        e.preventDefault();
        let cantidad = parseInt(quantityInput.val());
        if (cantidad > 1) {
            cantidad--;
            quantityInput.val(cantidad).trigger('change');
            syncCantidadVisual();
        }
    });

    plusBtn.on('click', function(e) {
        e.preventDefault();
        let cantidad = parseInt(quantityInput.val());
        cantidad++;
        quantityInput.val(cantidad).trigger('change');
        syncCantidadVisual();
    });

    // Si el usuario cambia el input manualmente, actualiza el número visual
    quantityInput.on('input change', function() {
        syncCantidadVisual();
    });

    // Función para actualizar el enlace del botón "Canjear prenda ahora"
    function actualizarEnlaceBoton() {
        const variationData = form.data('product_variations');
        const variationId = form.find('input[name="variation_id"]').val();
        if (!variationId || variationId === '0' || !variationData) {
            canjearButton.addClass('disabled').hide();
            return;
        }
        const productId = form.find('input[name="product_id"]').val();
        const quantity = quantityInput.val();
        const checkoutUrl = canjearButton.data('checkout-url');
        const redirectUrl = checkoutUrl + '?add-to-cart=' + productId + '&variation_id=' + variationId + '&quantity=' + quantity;
        canjearButton.attr('href', redirectUrl).removeClass('disabled').show();
    }

    // WooCommerce: actualiza el botón al seleccionar variación o cantidad
    form.on('show_variation', actualizarEnlaceBoton);
    quantityInput.on('change', actualizarEnlaceBoton);
    form.on('hide_variation', function() {
        canjearButton.addClass('disabled').hide();
    });

    // Botón personalizado "Agregar al carrito" envía el formulario WooCommerce
    agregarCarritoButton.on('click', function(e) {
        e.preventDefault();
        // Si hay variaciones, asegúrate de que una esté seleccionada
        if (form.find('input[name="variation_id"]').length && form.find('input[name="variation_id"]').val() === '0') {
            alert('Por favor, selecciona una opción antes de agregar al carrito.');
            return;
        }
        // Envía el formulario WooCommerce
        form.submit();
    });

    // Inicializa el número visual al cargar
    syncCantidadVisual();
});

