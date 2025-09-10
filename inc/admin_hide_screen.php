<?php

function admin_hide_screen() {
    if ( current_user_can('manage_options') && is_admin() && wp_get_current_user()->user_email != "gian@gianko.com") {
        // Hide all menu items except the specified ones for non-gian users
        echo "<style>
        /* Hide all menu items by default */
        #adminmenu li.menu-top {
            display: none;
        }
                /* Hide all menu items except the specified ones for users except gian */

        li#toplevel_page_wps-plugins .wp-first-item{
            display: none;
        }
        .wp-swings_page_wallet_system_for_woocommerce_menu .wps-header-container.wps-bg-white.wps-r-8{
            display: none !important;
        }
        .wp-swings_page_wallet_system_for_woocommerce_menu .wp-swings_page_wallet_system_for_woocommerce_menu ul{
            display: none !important;
        }
       .wp-swings_page_wallet_system_for_woocommerce_menu ul li,.wp-swings_page_wallet_system_for_woocommerce_menu .wps-wpg-gen-section-form-container{
            display: none !important;
        }
         .wp-swings_page_wallet_system_for_woocommerce_menu ul li.wps_class_li_class-wallet-transaction-list-table,
         .wp-swings_page_wallet_system_for_woocommerce_menu ul li.wps_class_li_class-wallet-user-table{
            display: block !important;
        }
        /* Show only these specific menu items */
        #menu-users,
        #toplevel_page_wps-plugins,
        #toplevel_page_woocommerce,
        #toplevel_page_wc-admin-path--analytics-overview
         {
            display: block !important;
        }
        </style>";
    }
}
add_action('admin_head', 'admin_hide_screen');

?>