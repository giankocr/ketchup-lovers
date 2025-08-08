<?php
/**
 * Página de agradecimiento moderna del pedido.
 * Diseño con mejores prácticas de UX/UI y hooks de WooCommerce.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;

// Obtener el ID del pedido de diferentes maneras
$order_id = null;

// Método 1: Verificar si viene como variable global
if ( isset( $order_id ) && ! empty( $order_id ) ) {
    $order_id = absint( $order_id );
}
// Método 2: Extraer de la URL (para URLs como /order-received/1950/)
elseif ( isset( $_GET['order-received'] ) ) {
    $order_id = absint( $_GET['order-received'] );
}
// Método 3: Extraer del path de la URL usando regex
else {
    $current_url = $_SERVER['REQUEST_URI'];
    // Buscar patrones como /order-received/1950/ o /finalizar-compra/order-received/1950/
    if ( preg_match( '/order-received\/(\d+)/', $current_url, $matches ) ) {
        $order_id = absint( $matches[1] );
    }
    // Buscar también en el path completo
    elseif ( preg_match( '/(\d+)\/\?key=/', $current_url, $matches ) ) {
        $order_id = absint( $matches[1] );
    }
}

// Verificar que tenemos un ID válido
if ( ! $order_id ) {
    ?>
    <div style="text-align: center; padding: 50px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <h1 style="color: #e74c3c; margin-bottom: 20px;">Error: ID de pedido no válido</h1>
        <p style="color: #7f8c8d; margin-bottom: 30px;">No se pudo identificar el pedido en la URL.</p>
        <a href="<?php echo esc_url( home_url() ); ?>" style="background: #8c0c0b; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block;">
            Volver al inicio
        </a>
    </div>
    <?php
    return;
}

// Obtener el objeto del pedido
$order = wc_get_order( $order_id );

if ( ! $order ) {
    // Si no hay pedido, mostrar mensaje de error
    ?>
    <div style="text-align: center; padding: 50px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <h1 style="color: #e74c3c; margin-bottom: 20px;">Error: Pedido no encontrado</h1>
        <p style="color: #7f8c8d; margin-bottom: 30px;">No se pudo encontrar el pedido #<?php echo esc_html( $order_id ); ?> en nuestro sistema.</p>
        <a href="<?php echo esc_url( home_url() ); ?>" style="background: #8c0c0b; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block;">
            Volver al inicio
        </a>
    </div>
    <?php
    return;
}

// Verificar que el usuario actual puede ver este pedido (seguridad)
$current_user_id = get_current_user_id();
$order_user_id = $order->get_user_id();

// Si hay un usuario logueado, verificar que es su pedido
if ( $current_user_id && $order_user_id && $current_user_id !== $order_user_id ) {
    ?>
    <div style="text-align: center; padding: 50px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <h1 style="color: #e74c3c; margin-bottom: 20px;">Acceso Denegado</h1>
        <p style="color: #7f8c8d; margin-bottom: 30px;">No tienes permisos para ver este pedido.</p>
        <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" style="background: #8c0c0b; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block;">
            Ver mis pedidos
        </a>
    </div>
    <?php
    return;
}

$order_status = $order->get_status();
$order_items = $order->get_items();
?>

<style>
    /* Reset y variables CSS para consistencia */
    :root {
        --primary-color: #8c0c0b;
        --secondary-color: #2c3e50;
        --accent-color: #8c0c0b;
        --success-color: #000000;
        --text-color: #2c3e50;
        --text-light: #7f8c8d;
        --background-light: #f8f9fa;
        --border-color: #e9ecef;
        --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.1);
        --shadow-medium: 0 4px 20px rgba(0, 0, 0, 0.15);
        --border-radius: 12px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Estilos base modernos - Compatible con Elementor */
    .thankyou-page {
        font-family: "TangoSans", BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        padding: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .thankyou-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        width: 100%;
        overflow: hidden;
        animation: slideInUp 0.6s ease-out;
    }

    /* Animaciones CSS */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    /* Header con icono de éxito - Más compacto */
    .thankyou-header {
        background: linear-gradient(135deg, var(--success-color), #000000);
        color: white;
        padding: 25px 20px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .thankyou-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse 3s infinite;
    }

    .success-icon {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        position: relative;
        z-index: 1;
    }

    .success-icon::before {
        content: '✓';
        font-size: 30px;
        font-weight: bold;
        color: white;
    }

    .thankyou-title {
        font-size: 2em;
        font-weight: 700;
        margin-bottom: 8px;
        position: relative;
        z-index: 1;
    }

    .thankyou-subtitle {
        font-size: 1em;
        opacity: 0.9;
        line-height: 1.4;
        position: relative;
        z-index: 1;
    }

    /* Contenido principal - Más compacto */
    .thankyou-content {
        padding: 25px 20px;
    }

    /* Resumen del pedido - Más compacto */
    .order-summary {
        background: var(--background-light);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid var(--border-color);
    }

    .order-summary h3 {
        color: var(--text-color);
        font-size: 1.1em;
        margin-bottom: 15px;
        text-align: center;
        font-weight: 600;
    }

    .order-details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .order-detail-item {
        background: white;
        padding: 15px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        text-align: center;
        transition: var(--transition);
    }

    .order-detail-item:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-light);
    }

    .order-detail-label {
        font-size: 0.8em;
        color: var(--text-light);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
        font-weight: 500;
    }

    .order-detail-value {
        font-size: 1em;
        color: var(--text-color);
        font-weight: 600;
    }

    /* Sección de productos - Más compacta */
    .products-section {
        margin-bottom: 20px;
    }

    .products-section h3 {
        color: var(--text-color);
        font-size: 1.1em;
        margin-bottom: 15px;
        text-align: center;
        font-weight: 600;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        justify-items: center;
    }

    .product-card {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
        transition: var(--transition);
        animation: fadeIn 0.6s ease-out;
        max-width: 300px !important;
    }

    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-light);
    }

    .product-image {
        width: 100%;
        height: 150px;
        max-width: 300px !important;
        border-radius: 8px 8px 0 0;
        object-fit: cover;
        background: var(--background-light);
    }

    .product-info {
        padding: 15px;
    }

    .product-name {
        font-size: 1em;
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 6px;
        line-height: 1.3;
    }

    .product-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.85em;
        color: var(--text-light);
    }

    .product-quantity {
        background: var(--accent-color);
        color: white;
        padding: 3px 6px;
        border-radius: 10px;
        font-size: 0.75em;
        font-weight: 500;
    }

    /* Tabla de detalles del pedido */
    .order-details-table-section {
        margin-bottom: 20px;
    }

    .order-details-table-section h3 {
        color: var(--text-color);
        font-size: 1.1em;
        margin-bottom: 15px;
        text-align: center;
        font-weight: 600;
    }

    .order-details-table {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
    }

    .order-details-table table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9em;
    }

    .order-details-table th {
        background: var(--background-light);
        color: var(--text-color);
        font-weight: 600;
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.85em;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .order-details-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: top;
    }

    .order-details-table tr:last-child td {
        border-bottom: none;
    }

    .product-name-link {
        color: var(--accent-color);
        font-weight: 500;
        display: block;
        margin-bottom: 4px;
    }

    .product-quantity-badge {
        background: var(--background-light);
        color: var(--text-light);
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 0.75em;
        font-weight: 500;
    }

    .subtotal-row td {
        background: #f8f9fa;
        font-weight: 500;
    }

    .shipping-row td {
        background: #f8f9fa;
        font-weight: 500;
    }

    .total-row td {
        background: var(--accent-color);
        color: white;
        font-weight: 600;
        font-size: 1.1em;
    }

    /* Botones de acción - Más compactos */
    .thankyou-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 20px;
    }

    .action-button {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        border-radius: 6px;
        transition: var(--transition);
        border: none;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .action-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }

    .action-button:hover::before {
        left: 100%;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--accent-color), #8c0c0b);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
    }

    .btn-secondary {
        background: white;
        color: var(--text-color);
        border: 2px solid var(--border-color);
    }

    .btn-secondary:hover {
        background: var(--background-light);
        border-color: var(--accent-color);
        transform: translateY(-2px);
    }

    /* Estado del pedido - Más compacto */
    .order-status {
        background: linear-gradient(135deg, #f39c12, #e67e22);
        color: white;
        padding: 10px 20px;
        border-radius: 20px;
        display: inline-block;
        font-weight: 600;
        font-size: 0.9em;
        margin-bottom: 15px;
        animation: pulse 2s infinite;
    }

    /* Responsive design - Compatible con Elementor */
    @media (max-width: 768px) {
        .thankyou-page {
            padding: 5px;
            min-height: auto;
        }
        
        .thankyou-container {
            margin: 5px;
            max-width: 100%;
        }
        
        .thankyou-header {
            padding: 20px 15px;
        }
        
        .thankyou-title {
            font-size: 1.5em;
        }
        
        .thankyou-content {
            padding: 20px 15px;
        }
        
        .order-details-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }
        
        .order-detail-item {
            padding: 12px;
        }
        
        .products-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }
        
        .product-card {
            margin-bottom: 10px;
        }
        
        .product-image {
            height: 120px;
        }
        
        .product-info {
            padding: 12px;
        }
        
        .order-details-table {
            font-size: 0.8em;
        }
        
        .order-details-table th,
        .order-details-table td {
            padding: 8px 10px;
        }
        
        .thankyou-actions {
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .action-button {
            width: 100%;
            max-width: 250px;
            justify-content: center;
            padding: 10px 15px;
            font-size: 13px;
        }
    }

    @media (max-width: 480px) {
        .thankyou-container {
            border-radius: 6px;
        }
        
        .thankyou-header {
            padding: 15px 10px;
        }
        
        .thankyou-title {
            font-size: 1.3em;
        }
        
        .thankyou-content {
            padding: 15px 10px;
        }
        
        .order-summary {
            padding: 15px;
        }
        
        .order-details-table th,
        .order-details-table td {
            padding: 6px 8px;
            font-size: 0.75em;
        }
    }

    /* Estados de pedido específicos */
    .status-processing .order-status {
        background: linear-gradient(135deg, #f39c12, #e67e22);
    }

    .status-completed .order-status {
        background: linear-gradient(135deg, var(--success-color), #000000);
    }

    .status-on-hold .order-status {
        background: linear-gradient(135deg, #95a5a6, #7f8c8d);
    }
</style>

<div class="thankyou-page">
    <div class="thankyou-container status-<?php echo esc_attr( $order_status ); ?>">
        
        <!-- Header con mensaje de éxito -->
        <div class="thankyou-header">
            <div class="success-icon"></div>
            <h1 class="thankyou-title">¡Compra Exitosa!</h1>
            <p class="thankyou-subtitle">
                Tu pedido ha sido confirmado y está siendo procesado. 
                Recibirás un correo electrónico con todos los detalles.
            </p>
        </div>

        <!-- Contenido principal -->
        <div class="thankyou-content">
            
            <!-- Estado del pedido -->
            <div style="text-align: center;">
                <div class="order-status">
                    Estado: <?php echo wc_get_order_status_name( $order_status ); ?>
                </div>
            </div>

            <!-- Resumen del pedido -->
            <div class="order-summary">
                <h3>📋 Resumen del Pedido</h3>
                <div class="order-details-grid">
                    <div class="order-detail-item">
                        <div class="order-detail-label">Número de Pedido</div>
                        <div class="order-detail-value">#<?php echo esc_html( $order->get_order_number() ); ?></div>
                    </div>
                    <div class="order-detail-item">
                        <div class="order-detail-label">Fecha de Canje</div>
                        <div class="order-detail-value"><?php echo wc_format_datetime( $order->get_date_created() ); ?></div>
                    </div>
                    <div class="order-detail-item">
                        <div class="order-detail-label">Puntos Canjeados</div>
                        <div class="order-detail-value"><?php echo $order->get_formatted_order_total(); ?></div>
                    </div>
                    <div class="order-detail-item">
                        <div class="order-detail-label">Método de Pago</div>
                        <div class="order-detail-value"><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></div>
                    </div>
                </div>
            </div>

            <!-- Productos comprados -->
            <?php if ( ! empty( $order_items ) ) : ?>
            <div class="products-section">
                <h3>Productos Comprados</h3>
                <div class="products-grid">
                    <?php
                    foreach ( $order_items as $item_id => $item ) {
                        $product = $item->get_product();
                        if ( $product ) {
                            $product_image = $product->get_image( 'medium' );
                            $product_name = $item->get_name();
                            $product_quantity = $item->get_quantity();
                            $product_total = $order->get_formatted_line_subtotal( $item );
                            ?>
                            <div class="product-card">
                                <?php if ( $product_image ) : ?>
                                    <img src="<?php echo esc_url( $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'medium' ) : wc_placeholder_img_src() ); ?>" 
                                         alt="<?php echo esc_attr( $product_name ); ?>" 
                                         class="product-image">
                                <?php else : ?>
                                    <div class="product-image" style="display: flex; align-items: center; justify-content: center; background: var(--background-light);">
                                        <span style="color: var(--text-light);">Sin imagen</span>
                                    </div>
                                <?php endif; ?>
                                <div class="product-info">
                                    <div class="product-name"><?php echo esc_html( $product_name ); ?></div>
                                    <div class="product-meta">
                                        <span>Cantidad: <?php echo esc_html( $product_quantity ); ?></span>
                                        <span class="product-quantity"><?php echo $product_total; ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tabla de detalles del pedido -->
            <div class="order-details-table-section">
                <h3>Detalles del Pedido</h3>
                <div class="order-details-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ( $order_items as $item_id => $item ) {
                                $product_name = $item->get_name();
                                $product_quantity = $item->get_quantity();
                                $product_total = $order->get_formatted_line_subtotal( $item );
                                ?>
                                <tr>
                                    <td>
                                        <span class="product-name-link"><?php echo esc_html( $product_name ); ?></span>
                                        <span class="product-quantity-badge">x <?php echo esc_html( $product_quantity ); ?></span>
                                    </td>
                                    <td><?php echo $product_total; ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                            <tr class="subtotal-row">
                                <td><strong>Subtotal:</strong></td>
                                <td><?php echo $order->get_subtotal_to_display(); ?></td>
                            </tr>
                            <?php if ( $order->get_shipping_total() > 0 ) : ?>
                            <tr class="shipping-row">
                                <td><strong>Envío:</strong></td>
                                <td><?php echo $order->get_shipping_to_display(); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="total-row">
                                <td><strong>Total:</strong></td>
                                <td><strong><?php echo $order->get_formatted_order_total(); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="thankyou-actions">
                <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="action-button btn-primary">
                Ver Mis Pedidos
                </a>
                <a href="<?php echo esc_url( home_url() ); ?>" class="action-button btn-secondary">
                 Continuar Comprando
                </a>
            </div>

        </div>

    </div>
</div>

<?php
// Hooks de WooCommerce para funcionalidad adicional
do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
do_action( 'woocommerce_thankyou', $order->get_id() );

// Hook personalizado para funcionalidad adicional
do_action( 'ketchup_lovers_thankyou_page', $order );
?>