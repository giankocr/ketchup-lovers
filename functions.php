<?php
/**
 * KetchupLovers Theme Functions
 * 
 * This file contains all the custom functions for the KetchupLovers theme
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

define('KERN_LOVERS_VERSION', '1.0.0');
define('THEME_URI', get_stylesheet_directory_uri());
define('THEME_DIR', get_stylesheet_directory());
define('THEME_NAME', 'Ketchup Lovers');

include_once 'inc/currency_symbol.php';
include_once 'inc/wc_woo_states.php';
include_once 'inc/custom_wallet_misc.php';
include_once 'templates/tomato-menu.php';

// Enqueue custom styles
function add_styles_css() {
    // Get the file modification time for versioning
    $css_file_path = THEME_DIR . '/assets/css/style.css';
    $css_file_url = THEME_URI . '/assets/css/style.css';
    $version = file_exists($css_file_path) ? filemtime($css_file_path) : '1.0.0';
    
    wp_enqueue_style( 'parent', get_template_directory_uri().'/style.css' );
    
    // Enqueue the main stylesheet
    wp_enqueue_style(
        'ketchuplovers-styles', 
        $css_file_url,
        array(), // No dependencies
        $version // Version for cache busting
    );
    if (!wp_script_is('gsap', 'enqueued')) {
        wp_enqueue_script(
            'gsap',
            'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js',
            array(),
            '3.12.5',
            false // Load in header to ensure it's available before shortcodes
        );
    }
    
    // Load ScrollTrigger plugin
    if (!wp_script_is('gsap-scrolltrigger', 'enqueued')) {
        wp_enqueue_script(
            'gsap-scrolltrigger',
            'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js',
            array('gsap'),
            '3.12.5',
            false // Load in header to ensure it's available before shortcodes
        ); 
    }
}
add_action('wp_enqueue_scripts', 'add_styles_css');

function add_scripts_js_footer() {
    // Prevent duplicate loading by checking if scripts are already enqueued
    if (wp_script_is('ketchuplovers-scripts', 'enqueued')) {
        return;
    }
    
    // Get the file modification time for versioning
    $js_file_path = THEME_DIR . '/assets/js/script.js';
    $js_file_url = THEME_URI . '/assets/js/script.js';
    $version = file_exists($js_file_path) ? filemtime($js_file_path) : '1.0.0';
    
    // Load custom scripts with GSAP dependency
    wp_enqueue_script(
        'ketchuplovers-scripts',
        $js_file_url,
        array('gsap', 'gsap-scrolltrigger'), // Depend on GSAP and ScrollTrigger
        $version,
        true // Load in footer for better performance
    );

    wp_enqueue_script(
        'ketchup-menu',
        THEME_URI . '/assets/js/ketchup-menu.js',
        array('gsap', 'gsap-scrolltrigger'),
        $version,
        true
    );
}
add_action('wp_enqueue_scripts', 'add_scripts_js_footer',99999);

/**
 * Add theme information to admin footer
 */
function kernslovers_admin_footer_text($text) {
    if (is_admin()) {
        return sprintf(
            'Desarrollado con ❤️ por <a href="https://gianko.com" target="_blank">Gianko.com</a> | Tema: %s v%s',
            THEME_NAME,
            KERN_LOVERS_VERSION
        );
    }
    return $text;
}
add_filter('admin_footer_text', 'kernslovers_admin_footer_text');