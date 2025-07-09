class ketchupMenu {
  constructor() {
    if (typeof gsap === "undefined") {
      console.error("GSAP is not loaded!");
      return;
    }
    // Get menu elements
    const homeMenuItem = document.getElementById("home-menu-item");
    const registerInvoiceMenuItem = document.getElementById(
      "register-invoice-menu-item"
    );
    const redeemPointsMenuItem = document.getElementById(
      "redeem-points-menu-item"
    );
    const tomate = document.getElementById("tomate-svg");
    const leftbottom = document.getElementById("leftbottom");
    const rigthtop = document.getElementById("rigthtop");
    const rigthbottom = document.getElementById("rigthbottom");
    const phone = document.getElementById("phone");
    const timeline = gsap.timeline();
  }
  /** ========================================
  ANIMACIÓN 1: Texto que se desliza horizontalmente
 ======================================== */
  textBannerAnimation() {
    // Seleccionar el elemento del texto horizontal
    const bannerText = document.querySelector(".horizontal-text");

    if (bannerText) {
      // Calcular el ancho del texto y del contenedor
      const container = bannerText.parentElement;
      const textWidth = bannerText.offsetWidth;
      const containerWidth = container.offsetWidth;
      // Posicionar el texto inicialmente a la izquierda
      gsap.set(bannerText, { x: 0 });
      // Crear la animación infinita de izquierda a derecha
      gsap.to(bannerText, {
        x: -textWidth, // Mover hacia la izquierda
        duration: 20, // Duración en segundos
        ease: "linear", // Movimiento constante
        repeat: -1, // Repetir infinitamente
        onRepeat: function () {
          // Reiniciar la posición para un bucle perfecto
          gsap.set(bannerText, { x: 0 });
        },
      });
    }
  }
  /** ========================================
  ANIMACIÓN 2: Tomate que rebota suavemente
  ======================================== */
  tomateCayendoAnimation() {
    const tomato = document.getElementById('tomatecayendo');
    if (tomato) {
     // Get the viewport height to make the tomato fall off-screen
     const viewportHeight = window.innerHeight;
 
     // GSAP animation
     gsap.to(tomato, {
         y: viewportHeight + 200, // Move it down past the bottom of the screen
         duration: 20,             // Animation duration in seconds
         ease: "power1.in",       // Easing function for a more natural fall (starts slow, speeds up)
         repeat: -1,              // Repeat infinitely
         delay: 1.5,              // Delay before the first fall
         onRepeat: function() {
             // Reset position to above the viewport on each repeat
             gsap.set(tomato, { y: -200 });
         }
     });
    }
  }



}
