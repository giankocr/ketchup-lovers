<?php
/**
 * WordPress Admin Login Page Customization
 * 
 * This file contains functions to customize the WordPress admin login page
 * for the Ketchup Lovers theme.
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue custom styles for the login page
 */
function ketchuplovers_login_styles() {
    // Only load on login page
    if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false || strpos($_SERVER['REQUEST_URI'], '/login') !== false) {
        wp_enqueue_style(
            'ketchuplovers-login-styles',
            THEME_URI . '/assets/css/login-admin.css',
            array(),
            KERN_LOVERS_VERSION
        );
    }
}
add_action('login_enqueue_scripts', 'ketchuplovers_login_styles');

/**
 * Change the login logo URL to point to the home page
 */
function ketchuplovers_login_logo_url() {
    return home_url();
}
add_filter('login_headerurl', 'ketchuplovers_login_logo_url');

/**
 * Change the login logo title
 */
function ketchuplovers_login_logo_url_title() {
    return get_bloginfo('name') . ' - ' . get_bloginfo('description');
}
add_filter('login_headertext', 'ketchuplovers_login_logo_url_title');

/**
 * Add custom welcome message to login page
 */
function ketchuplovers_login_message($message) {
    // Only show custom message if no other message is present
    if (empty($message)) {
        $welcome_message = sprintf(
            '<div class="login-welcome-message">
                <h2>¡Bienvenido a %s!</h2>
            </div>',
            get_bloginfo('name')
        );
        return $welcome_message;
    }
    return $message;
}
add_filter('login_message', 'ketchuplovers_login_message');

/**
 * Customize login form fields
 */
function ketchuplovers_login_form_fields() {
    // Add custom classes and placeholders to form fields
    add_filter('login_form_top', function() {
        echo '<style>
            .login form .input::placeholder {
                color: #6c757d;
                opacity: 0.7;
            }
        </style>';
    });
}
add_action('login_head', 'ketchuplovers_login_form_fields');

/**
 * Add custom footer text to login page
 */
function ketchuplovers_login_footer_text() {
    echo '<div class="custom-login-footer" style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); text-align: center; color: #FFF; font-size: 12px; z-index: 10;">
        <p>© ' . date('Y') . ' ' . get_bloginfo('name') . ' | Desarrollado con ❤️ por <a href="https://gianko.com" target="_blank" style="color: #FFF;">Gianko.com</a></p>
    </div>';
}
add_action('login_footer', 'ketchuplovers_login_footer_text');

/**
 * Customize error messages
 */
function ketchuplovers_login_errors($errors) {
    // Customize error messages to be more user-friendly
    if (isset($errors->errors['invalid_username'])) {
        $errors->remove('invalid_username');
        $errors->add('invalid_username', 'El nombre de usuario o correo electrónico no es correcto.');
    }
    
    if (isset($errors->errors['incorrect_password'])) {
        $errors->remove('incorrect_password');
        $errors->add('incorrect_password', 'La contraseña ingresada es incorrecta.');
    }
    
    if (isset($errors->errors['empty_username'])) {
        $errors->remove('empty_username');
        $errors->add('empty_username', 'Por favor, ingresa tu nombre de usuario o correo electrónico.');
    }
    
    if (isset($errors->errors['empty_password'])) {
        $errors->remove('empty_password');
        $errors->add('empty_password', 'Por favor, ingresa tu contraseña.');
    }
    
    return $errors;
}
add_filter('wp_login_errors', 'ketchuplovers_login_errors');

/**
 * Add custom JavaScript to login page
 */
function ketchuplovers_login_scripts() {
    if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add custom behavior to login form
            const loginForm = document.getElementById('loginform');
            const submitButton = document.getElementById('wp-submit');
            
            if (loginForm && submitButton) {
                // Change button text
                submitButton.value = 'INICIAR SESIÓN';
                
                // Add loading state to submit button
                loginForm.addEventListener('submit', function() {
                    submitButton.value = 'Iniciando sesión...';
                    submitButton.disabled = true;
                    submitButton.style.opacity = '0.7';
                });
                
                // Add focus effects to input fields
                const inputs = loginForm.querySelectorAll('input[type="text"], input[type="password"]');
                inputs.forEach(function(input) {
                    input.addEventListener('focus', function() {
                        this.parentElement.style.transform = 'scale(1.0)';
                    });
                    
                    input.addEventListener('blur', function() {
                        this.parentElement.style.transform = 'scale(1)';
                    });
                });
            }
        });
        </script>
        <?php
    }
}
add_action('login_footer', 'ketchuplovers_login_scripts');

/**
 * Change login button text
 */
function ketchuplovers_login_button_text($text) {
    return 'INICIAR SESIÓN';
}
add_filter('gettext', function($translated_text, $text, $domain) {
    if ($domain === 'default' && $text === 'Log In') {
        return 'INICIAR SESIÓN';
    }
    return $translated_text;
}, 10, 3);

/**
 * Remove WordPress version from login page
 */
function ketchuplovers_remove_version() {
    return '';
}
add_filter('the_generator', 'ketchuplovers_remove_version');

/**
 * Add custom meta tags to login page
 */
function ketchuplovers_login_head() {
    if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
        ?>
        <meta name="description" content="Panel de administración de <?php echo get_bloginfo('name'); ?>">
        <meta name="author" content="<?php echo get_bloginfo('name'); ?>">
        <link rel="icon" href="<?php echo THEME_URI; ?>/assets/images/Logo_ketchuoLovers.webp" type="image/webp">
        <?php
    }
}
add_action('login_head', 'ketchuplovers_login_head');

/**
 * Hide or reposition problematic elements on login page
 */
function ketchuplovers_fix_login_elements() {
    if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
        ?>
        <style>
        /* Hide language switcher and other problematic elements */
        .login #language-switcher,
        .login .language-switcher,
        .login .language-switcher-locales,
        .login .language-switcher-locales select,
        .login .language-switcher-locales input[type="submit"] {
            display: none !important;
        }
        
        /* Ensure proper positioning of all elements */
        body.login {
            position: relative;
        }
        
        /* Fix any floating elements */
        .login * {
            box-sizing: border-box;
        }
        </style>
        <?php
    }
}
add_action('login_head', 'ketchuplovers_fix_login_elements'); 