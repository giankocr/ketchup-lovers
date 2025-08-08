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
        duration: 120, // Duración en segundos
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
    // Selecciona el tomate y su "path"
    const tomato = document.getElementById("tomatecayendo");
    const tomatoPath = document.getElementsByClassName("tomate-icon-path");
    const menukernstomate = document.getElementById("menukernstomate");
    
    if (tomato) {
      // Altura de la ventana para calcular hasta dónde debe caer el tomate
      const viewportHeight = window.innerHeight;
      const viewportWidth = window.innerWidth;

      // Crea una nueva línea de tiempo de GSAP
      const tl = gsap.timeline(
        {
          delay: -1,
          duration: 0.1,
        }
      );
      tl.set(menukernstomate,{x:0,y:0,zIndex:100})
      tl.set(tomatoPath,{visibility:"hidden", opacity:0,delay:-1})
      tl.from(tomato,{y:-viewportHeight, x:0, scale:25, rotation:0,duration:2,opacity:1, ease:"expo.out"})
      tl.to(tomato,{y:viewportHeight/10,x:viewportWidth/2, scale:0,opacity:0,duration:0.1, ease:"expo.in"})
      tl.set(tomato,{display:"none"})
      tl.set(tomatoPath,{visibility:"visible", opacity:1,ease:"linear.out",delay:-1.8,duration:1,y:0,x:0})


    } else {
      // Si no encuentra los elementos, muestra un error en consola
      console.error(
        "No se encontró el elemento tomatecayendo o tomate-icon-path"
      );
    }
  }
}
