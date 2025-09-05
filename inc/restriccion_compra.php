<?php
/**
 * Restricción de Compra Única por Usuario
 * 
 * Este archivo implementa una restricción que permite a los usuarios
 * comprar solo una vez cada producto en WooCommerce.
 * 
 * Funcionalidades:
 * - Verifica si el usuario ya compró el producto
 * - Bloquea agregar al carrito productos ya comprados
 * - Muestra mensajes informativos al usuario
 * - Funciona tanto para usuarios logueados como invitados
 */

// Prevenir acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal para manejar las restricciones de compra
 */
class KetchupLovers_Purchase_Restriction {
    
    /**
     * Constructor de la clase
     */
    public function __construct() {
        // Agregar hooks para verificar restricciones
        add_action('woocommerce_before_add_to_cart', array($this, 'check_purchase_restriction'), 10, 2);
        add_action('woocommerce_add_to_cart_validation', array($this, 'validate_cart_addition'), 10, 5);
        add_filter('woocommerce_add_to_cart_redirect', array($this, 'redirect_after_restriction'), 10, 1);
        
        // Agregar mensajes de error personalizados
        add_action('woocommerce_before_single_product', array($this, 'display_purchase_restriction_notice'));
    }
    
    /**
     * Verifica si el usuario ya compró el producto
     * 
     * @param int $product_id ID del producto a verificar
     * @param int $user_id ID del usuario (opcional)
     * @return bool True si ya compró, False si no
     */
    public function has_user_purchased_product($product_id, $user_id = null) {
        // Si no hay usuario especificado, usar el usuario actual
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Si no hay usuario logueado, usar la sesión de WooCommerce
        if (!$user_id) {
            return $this->check_guest_purchase_history($product_id);
        }
        
        // Buscar en el historial de pedidos del usuario
        $customer_orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => array('wc-completed', 'wc-processing'),
            'limit' => -1,
            'return' => 'ids'
        ));
        
        // Verificar cada pedido
        foreach ($customer_orders as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                foreach ($order->get_items() as $item) {
                    if ($item->get_product_id() == $product_id) {
                        return true; // Usuario ya compró este producto
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Verifica el historial de compras para usuarios invitados
     * usando la sesión de WooCommerce
     * 
     * @param int $product_id ID del producto
     * @return bool True si ya compró, False si no
     */
    private function check_guest_purchase_history($product_id) {
        // Obtener productos en el carrito actual
        $cart_items = WC()->cart->get_cart();
        
        foreach ($cart_items as $cart_item) {
            if ($cart_item['product_id'] == $product_id) {
                return true; // Ya está en el carrito
            }
        }
        
        return false;
    }
    
    /**
     * Verifica restricciones antes de mostrar el botón de agregar al carrito
     * 
     * @param int $product_id ID del producto
     * @param int $variation_id ID de la variación (si aplica)
     */
    public function check_purchase_restriction($product_id, $variation_id = 0) {
        // Solo verificar el producto principal, no variaciones
        $main_product_id = $variation_id ? $this->get_parent_product_id($variation_id) : $product_id;
        
        if ($this->has_user_purchased_product($main_product_id)) {
            // Ocultar el botón de agregar al carrito
            remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart');
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
            
            // Agregar mensaje de restricción
            add_action('woocommerce_single_product_summary', array($this, 'display_restriction_message'), 30);
        }
    }
    
    /**
     * Obtiene el ID del producto padre de una variación
     * 
     * @param int $variation_id ID de la variación
     * @return int ID del producto padre
     */
    private function get_parent_product_id($variation_id) {
        $variation = wc_get_product($variation_id);
        return $variation ? $variation->get_parent_id() : $variation_id;
    }
    
    /**
     * Valida si se puede agregar el producto al carrito
     * 
     * @param bool $passed Validación previa
     * @param int $product_id ID del producto
     * @param int $quantity Cantidad
     * @param int $variation_id ID de la variación
     * @param array $variations Datos de la variación
     * @return bool True si pasa la validación
     */
    public function validate_cart_addition($passed, $product_id, $quantity, $variation_id = 0, $variations = array()) {
        // Solo verificar el producto principal
        $main_product_id = $variation_id ? $this->get_parent_product_id($variation_id) : $product_id;
        
        if ($this->has_user_purchased_product($main_product_id)) {
            // Agregar mensaje de error
        /*    wc_add_notice(
                sprintf(
                    'Ya has comprado este producto anteriormente. Solo se permite una compra por producto.',
                    get_the_title($main_product_id)
                ),
                'error'
            );*/
            
            return false; // Bloquear la adición al carrito
        }
        
        return $passed;
    }
    
    /**
     * Muestra mensaje de restricción en lugar del botón de agregar al carrito
     */
    public function display_restriction_message() {
        echo '<div class="purchase-restriction-notice">';
        echo '<p class="restriction-message">';
        echo '<strong>⚠️ Producto ya comprado</strong><br>';
        echo 'Ya has comprado este producto anteriormente. Solo se permite una compra por producto.';
        echo '</p>';
        echo '</div>';
    }
    
    /**
     * Muestra aviso general sobre la política de compra única
     */
    public function display_purchase_restriction_notice() {
        if (is_product()) {
            global $product;
            $product_id = $product->get_id();
            
            if ($this->has_user_purchased_product($product_id)) {
                echo '<div class="woocommerce-info purchase-restriction-info">';
                echo '<p><strong>Política de Compra Única:</strong> Este producto ya ha sido comprado anteriormente.</p>';
                echo '</div>';
            }
        }
    }
    
    /**
     * Redirige después de una restricción
     * 
     * @param string $redirect_url URL de redirección
     * @return string URL modificada si es necesario
     */
    public function redirect_after_restriction($redirect_url) {
        // Si hay errores de validación, redirigir de vuelta al producto
        if (wc_notice_count('error') > 0) {
            return wp_get_referer() ? wp_get_referer() : wc_get_page_permalink('shop');
        }
        
        return $redirect_url;
    }
}

/**
 * Inicializar la clase de restricciones
 */
function init_ketchup_lovers_purchase_restrictions() {
    // Solo inicializar si WooCommerce está activo
    if (class_exists('WooCommerce')) {
        new KetchupLovers_Purchase_Restriction();
    }
}

// Hook para inicializar cuando WordPress esté listo
add_action('init', 'init_ketchup_lovers_purchase_restrictions');

/**
 * Función auxiliar para verificar restricciones desde otros archivos
 * 
 * @param int $product_id ID del producto
 * @param int $user_id ID del usuario (opcional)
 * @return bool True si ya compró, False si no
 */
function ketchup_lovers_check_purchase_restriction($product_id, $user_id = null) {
    $restriction = new KetchupLovers_Purchase_Restriction();
    return $restriction->has_user_purchased_product($product_id, $user_id);
}