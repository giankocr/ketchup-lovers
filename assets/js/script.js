// ========================================
// ANIMACIÓN SENCILLA CON GSAP
// ========================================

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {

    // Verificar que GSAP esté disponible
    if (typeof gsap === 'undefined') {
        console.log('GSAP no está cargado');
        return;
    }
    this.ketchupMenu = new ketchupMenu();
    this.ketchupMenu.textBannerAnimation();
    this.ketchupMenu.tomateCayendoAnimation();
    
});

