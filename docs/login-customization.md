# Personalización de la Página de Login de Administración

## Descripción

Este módulo personaliza la página de login de administración de WordPress para el tema Ketchup Lovers, proporcionando una experiencia visual moderna y consistente con la marca.

## Características Implementadas

### 1. Diseño Visual Personalizado
- **Fondo degradado**: Gradiente rojo/naranja que coincide con la temática de ketchup
- **Logo personalizado**: Utiliza el logo KL.svg del tema
- **Tipografía moderna**: Segoe UI para mejor legibilidad
- **Efectos visuales**: Animaciones suaves y efectos hover

### 2. Funcionalidades Mejoradas
- **Mensajes de bienvenida**: Texto personalizado en español
- **Mensajes de error**: Traducidos y más amigables
- **Estados de carga**: Feedback visual durante el login
- **Responsive design**: Adaptable a dispositivos móviles

### 3. Seguridad y UX
- **Ocultar versión de WordPress**: Mejora la seguridad
- **Meta tags personalizados**: SEO y branding
- **Favicon personalizado**: Consistencia visual

## Archivos Creados

### `assets/css/login-admin.css`
Contiene todos los estilos CSS para la página de login:
- Estilos base y reset
- Diseño del contenedor principal
- Estilos de formularios e inputs
- Efectos hover y animaciones
- Media queries para responsive

### `inc/login-customization.php`
Contiene todas las funciones PHP para personalizar el login:
- Carga de estilos CSS
- Personalización del logo y URL
- Mensajes personalizados
- Manejo de errores
- JavaScript personalizado

## Funciones Principales

### `ketchuplovers_login_styles()`
Carga los estilos CSS personalizados solo en la página de login.

### `ketchuplovers_login_logo_url()`
Cambia la URL del logo para que apunte a la página principal.

### `ketchuplovers_login_message()`
Añade un mensaje de bienvenida personalizado.

### `ketchuplovers_login_errors()`
Personaliza los mensajes de error para que sean más amigables.

### `ketchuplovers_login_scripts()`
Añade JavaScript personalizado para mejorar la experiencia de usuario.

## Personalización Adicional

### Cambiar Colores
Para cambiar los colores del tema, modifica las variables CSS en `login-admin.css`:

```css
/* Colores principales */
--primary-color: #ff6b6b;
--secondary-color: #ee5a24;
--accent-color: #ff4757;
```

### Cambiar Logo
Para cambiar el logo, reemplaza la imagen en:
```css
.login h1 a {
    background-image: url('../images/tu-nuevo-logo.svg') !important;
}
```

### Cambiar Mensajes
Para personalizar los mensajes, edita las funciones en `login-customization.php`:
- `ketchuplovers_login_message()` - Mensaje de bienvenida
- `ketchuplovers_login_errors()` - Mensajes de error
- `ketchuplovers_login_footer_text()` - Texto del footer

## Compatibilidad

- ✅ WordPress 5.0+
- ✅ PHP 7.4+
- ✅ Navegadores modernos (Chrome, Firefox, Safari, Edge)
- ✅ Dispositivos móviles y tablets

## Notas de Seguridad

1. **No exponer información sensible**: Los mensajes de error son genéricos
2. **Ocultar versión de WordPress**: Se elimina la información de versión
3. **Validación de entrada**: Se mantienen las validaciones nativas de WordPress
4. **Sanitización**: Se utilizan las funciones de sanitización de WordPress

## Troubleshooting

### Los estilos no se cargan
1. Verifica que el archivo `login-admin.css` existe en `assets/css/`
2. Comprueba que `login-customization.php` está incluido en `functions.php`
3. Limpia la caché del navegador

### El logo no aparece
1. Verifica que el archivo `KL.svg` existe en `assets/images/`
2. Comprueba la ruta en el CSS
3. Verifica los permisos del archivo

### Mensajes en inglés
1. Verifica que las funciones de traducción están funcionando
2. Comprueba que el sitio está configurado en español
3. Limpia la caché de WordPress

## Mantenimiento

### Actualizaciones
- Revisar compatibilidad con nuevas versiones de WordPress
- Actualizar estilos CSS según sea necesario
- Mantener la consistencia con el tema principal

### Backup
- Hacer backup de los archivos antes de modificaciones
- Documentar cambios personalizados
- Mantener versiones de los archivos modificados 