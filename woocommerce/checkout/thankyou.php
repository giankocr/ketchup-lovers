<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.1.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order thankyou-page">

	<?php
	if ( $order ) :

		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<div class="thankyou-container status-failed">
				<div class="thankyou-header">
					<div class="error-icon"></div>
					<h1 class="thankyou-title">Error en el Pedido</h1>
					<p class="thankyou-subtitle">
						<?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?>
					</p>
				</div>

				<div class="thankyou-actions">
					<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="action-button btn-primary">
						<?php esc_html_e( 'Pay', 'woocommerce' ); ?>
					</a>
					<?php if ( is_user_logged_in() ) : ?>
						<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="action-button btn-secondary">
							<?php esc_html_e( 'My account', 'woocommerce' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

		<?php else : ?>

			<?php wc_get_template( 'checkout/order-received.php', array( 'order' => $order ) ); ?>

			<div class="thankyou-container status-<?php echo esc_attr( $order->get_status() ); ?>">
				
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
							Estado: <?php echo wc_get_order_status_name( $order->get_status() ); ?>
						</div>
					</div>

					<!-- Resumen del pedido usando la estructura oficial de WooCommerce -->
					<div class="order-summary">
						<h3>📋 Resumen del Pedido</h3>
						<div class="order-details-grid">
							<div class="order-detail-item">
								<div class="order-detail-label"><?php esc_html_e( 'Order number:', 'woocommerce' ); ?></div>
								<div class="order-detail-value">#<?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
							</div>
							<div class="order-detail-item">
								<div class="order-detail-label"><?php esc_html_e( 'Date:', 'woocommerce' ); ?></div>
								<div class="order-detail-value"><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
							</div>
							<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
								<div class="order-detail-item">
									<div class="order-detail-label"><?php esc_html_e( 'Email:', 'woocommerce' ); ?></div>
									<div class="order-detail-value"><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
								</div>
							<?php endif; ?>
							<div class="order-detail-item">
								<div class="order-detail-label"><?php esc_html_e( 'Total:', 'woocommerce' ); ?></div>
								<div class="order-detail-value"><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
							</div>
							<?php if ( $order->get_payment_method_title() ) : ?>
								<div class="order-detail-item">
									<div class="order-detail-label"><?php esc_html_e( 'Payment method:', 'woocommerce' ); ?></div>
									<div class="order-detail-value"><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></div>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<!-- Productos comprados -->
					<?php 
					$order_items = $order->get_items();
					if ( ! empty( $order_items ) ) : 
					?>
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

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

		<?php
		// Hook personalizado para funcionalidad adicional
		do_action( 'ketchup_lovers_thankyou_page', $order );
		?>

	<?php else : ?>

		<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>

	<?php endif; ?>

</div>