/**
 * Login Page Customization JavaScript
 * 
 * This file handles custom behavior for the WordPress login page
 * for the Ketchup Lovers theme.
 */

jQuery(document).ready(function($) {
    'use strict';
    
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