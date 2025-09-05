<?php
/**
 * Edit account form template
 * Template personalizado para edición de cuenta con campos adicionales
 * 
 * @package WooCommerce/Templates
 * @version 7.0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

do_action('woocommerce_before_edit_account_form'); ?>


<p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
    <label><?php esc_html_e('First name', 'woocommerce'); ?></label>
    <span class="user-info-display"><?php echo esc_html($user->first_name); ?></span>
</p>
<p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
    <label><?php esc_html_e('Last name', 'woocommerce'); ?></label>
    <span class="user-info-display"><?php echo esc_html($user->last_name); ?></span>
</p>
<div class="clear"></div>

<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
    <label><?php esc_html_e('Email address', 'woocommerce'); ?></label>
    <span class="user-info-display"><?php echo esc_html($user->user_email); ?></span>
</p>
<div class="clear"></div>

<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
    <label><?php esc_html_e('Phone number', 'woocommerce'); ?></label>
    <span class="user-info-display"><?php echo esc_html(get_user_meta($user->ID, 'billing_phone', true)); ?></span>
</p>



<?php do_action('woocommerce_after_edit_account_form'); ?>

