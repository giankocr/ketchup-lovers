# Guía de Accesibilidad del Menú de Navegación

## Descripción General

Este documento describe las mejoras de accesibilidad implementadas en el menú de navegación principal del tema Ketchup Lovers.

## Mejoras Implementadas

### 1. Semántica HTML Mejorada

#### Roles ARIA
- `role="navigation"`: Identifica el elemento como navegación
- `role="menuitem"`: Identifica cada enlace como un elemento de menú
- `role="presentation"`: Para iconos decorativos
- `aria-hidden="true"`: Para elementos que no deben ser anunciados

#### Atributos ARIA
- `aria-label`: Descripciones específicas para cada elemento
- `aria-current="page"`: Indica la página actual
- `aria-live="polite"`: Para anuncios dinámicos
- `tabindex="0"`: Hace los elementos navegables por teclado

### 2. Navegación por Teclado

#### Teclas Soportadas
- **Flechas izquierda/derecha**: Navegar entre elementos del menú
- **Home**: Ir al primer elemento
- **End**: Ir al último elemento
- **Enter/Espacio**: Activar el elemento seleccionado
- **Tab**: Navegación estándar

#### Indicadores Visuales
- Outline visible al hacer focus
- Cambio de color de fondo
- Transformación visual (translateY)

### 3. Soporte para Lectores de Pantalla

#### Anuncios Automáticos
- Descripción del elemento al hacer focus
- Indicación del estado activo
- Anuncios de navegación

#### Textos Alternativos
- Iconos marcados como `role="presentation"`
- Etiquetas descriptivas en `aria-label`
- Textos ocultos para elementos decorativos

### 4. Mejoras Visuales

#### Contraste y Legibilidad
- Sombra de texto para mejor contraste
- Peso de fuente aumentado
- Sombras en iconos

#### Estados Interactivos
- Hover con transformación
- Focus con outline visible
- Estado activo diferenciado

### 5. Responsive y Táctil

#### Áreas Táctiles
- Mínimo 44px de altura/ancho
- Feedback visual inmediato
- Gestos táctiles mejorados

#### Adaptación Móvil
- Detección automática de dispositivo
- Ajustes específicos para móviles
- Navegación optimizada para touch

### 6. Preferencias del Usuario

#### Modo Alto Contraste
- Detección automática de `prefers-contrast: high`
- Bordes más gruesos
- Sombras más pronunciadas

#### Movimiento Reducido
- Detección de `prefers-reduced-motion`
- Eliminación de animaciones
- Transiciones simplificadas

## Estructura de Archivos

```
assets/
├── css/
│   └── tomato-menu.css          # Estilos principales + accesibilidad
├── js/
│   └── tomato-menu-accessibility.js  # Funcionalidad de accesibilidad
└── templates/
    └── tomato-menu.php          # HTML con atributos ARIA
```

## Implementación

### 1. HTML (tomato-menu.php)
```html
<nav class='nav-ketchup' role="navigation" aria-label="Menú principal de navegación">
    <a href="..." role="menuitem" aria-label="Descripción" tabindex="0">
        <div aria-hidden="true">
            <img role="presentation" alt="">
        </div>
        <span>Texto visible</span>
    </a>
</nav>
```

### 2. CSS (tomato-menu.css)
```css
/* Focus visible */
.nav-ketchup a:focus {
    outline: 3px solid #ffffff;
    outline-offset: 2px;
}

/* Alto contraste */
@media (prefers-contrast: high) {
    .nav-ketchup a:focus {
        outline: 4px solid #ffffff;
    }
}

/* Movimiento reducido */
@media (prefers-reduced-motion: reduce) {
    .nav-ketchup a {
        transition: none;
    }
}
```

### 3. JavaScript (tomato-menu-accessibility.js)
```javascript
// Navegación por teclado
item.addEventListener('keydown', function(e) {
    switch(e.key) {
        case 'ArrowRight':
            navigateToNextItem(index, 1);
            break;
    }
});

// Anuncios para lectores de pantalla
function announceToScreenReader(message) {
    // Implementación...
}
```

## Pruebas de Accesibilidad

### Herramientas Recomendadas
1. **NVDA** (Windows) - Lector de pantalla gratuito
2. **VoiceOver** (macOS) - Lector de pantalla integrado
3. **axe DevTools** - Extensión de navegador
4. **WAVE** - Evaluador web de accesibilidad

### Checklist de Verificación
- [ ] Navegación completa por teclado
- [ ] Anuncios correctos en lectores de pantalla
- [ ] Contraste de color adecuado
- [ ] Indicadores de focus visibles
- [ ] Funcionamiento en modo alto contraste
- [ ] Compatibilidad con movimiento reducido
- [ ] Áreas táctiles de tamaño adecuado

## Mantenimiento

### Actualizaciones Regulares
1. Revisar compatibilidad con nuevas versiones de lectores de pantalla
2. Actualizar según cambios en estándares WCAG
3. Probar con nuevos dispositivos y navegadores

### Monitoreo
- Revisar logs de errores de JavaScript
- Recopilar feedback de usuarios con discapacidades
- Realizar auditorías de accesibilidad periódicas

## Recursos Adicionales

- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [ARIA Authoring Practices](https://www.w3.org/TR/wai-aria-practices/)
- [Web Accessibility Initiative](https://www.w3.org/WAI/)

## Contacto

Para preguntas sobre accesibilidad, contactar al equipo de desarrollo del tema Ketchup Lovers.
