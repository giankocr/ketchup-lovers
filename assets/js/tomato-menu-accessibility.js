/**
 * Mejoras de accesibilidad para el menú de navegación
 * Ketchup Lovers Theme
 */

document.addEventListener('DOMContentLoaded', function() {
    // Obtener elementos del menú
    const navMenu = document.querySelector('.nav-ketchup');
    const menuItems = navMenu.querySelectorAll('a[role="menuitem"]');
    
    if (!navMenu || !menuItems.length) return;
    
    // Configurar navegación por teclado
    setupKeyboardNavigation();
    
    // Configurar indicadores de estado activo
    setupActiveStateIndicators();
    
    // Configurar anuncios para lectores de pantalla
    setupScreenReaderAnnouncements();
    
    // Configurar gestos táctiles mejorados
    setupTouchAccessibility();
});

/**
 * Configura la navegación por teclado en el menú
 */
function setupKeyboardNavigation() {
    const menuItems = document.querySelectorAll('.nav-ketchup a[role="menuitem"]');
    
    menuItems.forEach((item, index) => {
        // Manejar teclas de navegación
        item.addEventListener('keydown', function(e) {
            switch(e.key) {
                case 'ArrowRight':
                    e.preventDefault();
                    navigateToNextItem(index, 1);
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    navigateToNextItem(index, -1);
                    break;
                case 'Home':
                    e.preventDefault();
                    menuItems[0].focus();
                    break;
                case 'End':
                    e.preventDefault();
                    menuItems[menuItems.length - 1].focus();
                    break;
                case 'Enter':
                case ' ':
                    e.preventDefault();
                    item.click();
                    break;
            }
        });
        
        // Mejorar feedback visual al hacer focus
        item.addEventListener('focus', function() {
            announceToScreenReader(`Enfocado en: ${item.querySelector('span').textContent}`);
        });
    });
}

/**
 * Navega al siguiente elemento del menú
 */
function navigateToNextItem(currentIndex, direction) {
    const menuItems = document.querySelectorAll('.nav-ketchup a[role="menuitem"]');
    const nextIndex = (currentIndex + direction + menuItems.length) % menuItems.length;
    menuItems[nextIndex].focus();
}

/**
 * Configura indicadores de estado activo
 */
function setupActiveStateIndicators() {
    const menuItems = document.querySelectorAll('.nav-ketchup a[role="menuitem"]');
    
    menuItems.forEach(item => {
        // Verificar si el enlace corresponde a la página actual
        if (item.href === window.location.href) {
            item.setAttribute('aria-current', 'page');
            item.classList.add('active-menu-item');
        }
        
        // Actualizar estado al hacer clic
        item.addEventListener('click', function() {
            // Remover estado activo de todos los elementos
            menuItems.forEach(menuItem => {
                menuItem.removeAttribute('aria-current');
                menuItem.classList.remove('active-menu-item');
            });
            
            // Agregar estado activo al elemento clickeado
            this.setAttribute('aria-current', 'page');
            this.classList.add('active-menu-item');
        });
    });
}

/**
 * Configura anuncios para lectores de pantalla
 */
function setupScreenReaderAnnouncements() {
    // Crear elemento para anuncios
    let announcementElement = document.getElementById('screen-reader-announcement');
    
    if (!announcementElement) {
        announcementElement = document.createElement('div');
        announcementElement.id = 'screen-reader-announcement';
        announcementElement.className = 'sr-only';
        announcementElement.setAttribute('aria-live', 'polite');
        announcementElement.setAttribute('aria-atomic', 'true');
        document.body.appendChild(announcementElement);
    }
}

/**
 * Anuncia mensajes a lectores de pantalla
 */
function announceToScreenReader(message) {
    const announcementElement = document.getElementById('screen-reader-announcement');
    if (announcementElement) {
        announcementElement.textContent = message;
        
        // Limpiar el mensaje después de un tiempo
        setTimeout(() => {
            announcementElement.textContent = '';
        }, 1000);
    }
}

/**
 * Configura accesibilidad táctil mejorada
 */
function setupTouchAccessibility() {
    const menuItems = document.querySelectorAll('.nav-ketchup a[role="menuitem"]');
    
    menuItems.forEach(item => {
        let touchStartTime = 0;
        let touchEndTime = 0;
        
        item.addEventListener('touchstart', function(e) {
            touchStartTime = new Date().getTime();
            
            // Agregar feedback visual inmediato
            this.style.transform = 'scale(0.95)';
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
        });
        
        item.addEventListener('touchend', function(e) {
            touchEndTime = new Date().getTime();
            
            // Restaurar estilo
            this.style.transform = '';
            this.style.backgroundColor = '';
            
            // Verificar si fue un toque largo (más de 500ms)
            const touchDuration = touchEndTime - touchStartTime;
            if (touchDuration > 500) {
                e.preventDefault();
                announceToScreenReader(`Menú: ${this.querySelector('span').textContent}. Mantén presionado para activar.`);
            }
        });
    });
}

/**
 * Mejora la accesibilidad del menú en dispositivos móviles
 */
function setupMobileAccessibility() {
    // Detectar si es un dispositivo móvil
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isMobile) {
        const navMenu = document.querySelector('.nav-ketchup');
        
        // Agregar indicador de menú móvil
        navMenu.setAttribute('aria-label', 'Menú principal de navegación móvil');
        
        // Mejorar tamaño de área táctil
        const menuItems = navMenu.querySelectorAll('a[role="menuitem"]');
        menuItems.forEach(item => {
            item.style.minHeight = '44px';
            item.style.minWidth = '44px';
        });
    }
}

/**
 * Configura accesibilidad para modo de alto contraste
 */
function setupHighContrastMode() {
    // Detectar preferencias de contraste
    const prefersHighContrast = window.matchMedia('(prefers-contrast: high)');
    
    if (prefersHighContrast.matches) {
        document.body.classList.add('high-contrast-mode');
    }
    
    // Escuchar cambios en las preferencias
    prefersHighContrast.addEventListener('change', function(e) {
        if (e.matches) {
            document.body.classList.add('high-contrast-mode');
        } else {
            document.body.classList.remove('high-contrast-mode');
        }
    });
}

/**
 * Configura accesibilidad para movimiento reducido
 */
function setupReducedMotion() {
    // Detectar preferencias de movimiento reducido
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    
    if (prefersReducedMotion.matches) {
        document.body.classList.add('reduced-motion');
    }
    
    // Escuchar cambios en las preferencias
    prefersReducedMotion.addEventListener('change', function(e) {
        if (e.matches) {
            document.body.classList.add('reduced-motion');
        } else {
            document.body.classList.remove('reduced-motion');
        }
    });
}

// Inicializar configuraciones adicionales
document.addEventListener('DOMContentLoaded', function() {
    setupMobileAccessibility();
    setupHighContrastMode();
    setupReducedMotion();
});
