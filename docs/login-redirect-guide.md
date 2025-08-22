# Guía de Redirección de Login - Ketchup Lovers Theme

## Descripción General

Esta implementación redirige automáticamente las visitas a `wp-admin` y `wp-login.php` hacia la página de login existente en Elementor (`/login`), mientras que las funcionalidades nativas de WordPress como recuperación de contraseña se manejan en una página separada (`/wp-login`).

## Funcionalidades Implementadas

### 1. Redirección Automática
- **wp-admin**: Redirige a `/login` (Elementor) si el usuario no está logueado
- **wp-login.php**: Redirige a `/login` (Elementor) excepto para funcionalidades nativas de WordPress
- **/login**: Página de Elementor existente para login principal

### 2. Funcionalidades Nativas de WordPress
Las siguientes funcionalidades se manejan en `/wp-login`:
- `wp-login.php?action=lostpassword` → `/wp-login?action=lostpassword`
- `wp-login.php?action=resetpass` → `/wp-login?action=resetpass`
- `wp-login.php?action=rp` → `/wp-login?action=rp`
- `wp-login.php?action=register` → `/wp-login?action=register`
- URLs con parámetros `key` y `login` (para reset de contraseña)

### 3. Páginas de Login
- **Login Principal**: `/login` (Elementor existente)
- **Login Nativo**: `/wp-login` (para funcionalidades de WordPress)
- **Estilos**: `assets/css/login-admin.css` para página nativa
- **Diseño**: Consistente con la identidad visual de Ketchup Lovers

## Archivos Modificados/Creados

### Archivos Principales
1. **`inc/login-customization.php`**
   - Función `ketchuplovers_redirect_to_custom_login()`
   - Función `ketchuplovers_create_wp_login_page()`
   - Manejo de redirecciones y excepciones

2. **`page-wp-login.php`** (NUEVO)
   - Template para funcionalidades nativas de WordPress
   - Manejo de recuperación de contraseña
   - Formularios nativos de WordPress

3. **`assets/css/login-admin.css`** (EXISTENTE)
   - Estilos para la página de login nativa
   - Diseño responsivo
   - Consistente con la identidad visual

4. **`functions.php`**
   - Comentario sobre página de Elementor existente
   - No crea página de login (ya existe en Elementor)

## Flujo de Funcionamiento

### Para Usuarios No Logueados
1. Usuario accede a `wp-admin` o `wp-login.php`
2. Sistema verifica si es una acción nativa de WordPress
3. Si no es nativa, redirige a `/login` (Elementor)
4. Usuario ve la página de login de Elementor
5. Al hacer login exitoso, redirige a la página original o al home

### Para Funcionalidades Nativas de WordPress
1. Usuario accede a `wp-login.php?action=lostpassword`
2. Sistema detecta que es una acción nativa
3. Permite el acceso a `/wp-login` con la funcionalidad correspondiente
4. Usuario puede recuperar contraseña o usar otras funciones nativas

### Para Usuarios Logueados
1. Usuario accede a `/login` o `/wp-login`
2. Sistema detecta que ya está logueado
3. Redirige automáticamente al home

## Características de Seguridad

### Nonces
- Uso de `wp_nonce_field()` para el formulario de login
- Verificación con `wp_verify_nonce()` en el procesamiento

### Sanitización
- `sanitize_text_field()` para campos de texto
- `esc_html()` para mensajes de error
- `esc_attr()` para valores de campos

### Validación
- Verificación de campos requeridos
- Autenticación con `wp_authenticate()`
- Manejo de errores de autenticación

## Personalización

### Cambiar URL de Redirección
Modificar en `inc/login-customization.php`:
```php
$redirect_url = home_url('/tu-nueva-url');
```

### Modificar Estilos
Editar `assets/css/login-admin.css` para cambiar la apariencia de la página nativa.

### Agregar Excepciones
En `inc/login-customization.php`, agregar a `$allowed_actions`:
```php
$allowed_actions = array(
    'lostpassword',
    'retrievepassword', 
    'resetpass',
    'rp',
    'register',
    'tu-nueva-accion'
);
```

## Compatibilidad

### Plugins Compatibles
- WooCommerce
- Elementor
- Cualquier plugin que use el sistema de autenticación de WordPress

### Temas Compatibles
- Cualquier tema de WordPress
- Funciona independientemente del tema padre

## Troubleshooting

### Problema: Bucle de Redirección
**Solución**: Verificar que la página `/login` existe en Elementor y que `/wp-login` tiene el template correcto.

### Problema: No Funciona la Recuperación de Contraseña
**Solución**: Verificar que las acciones de recuperación están en `$allowed_actions` y que la página `/wp-login` existe.

### Problema: Estilos No Se Cargan
**Solución**: Verificar que `THEME_URI` y `KERN_LOVERS_VERSION` están definidos.

## Mantenimiento

### Actualizaciones
- Revisar compatibilidad con nuevas versiones de WordPress
- Verificar que las funciones de WordPress no han cambiado
- Probar funcionalidad de recuperación de contraseña

### Logs
- Monitorear errores de autenticación
- Verificar redirecciones exitosas
- Revisar logs de WordPress para problemas

## Notas Importantes

1. **Permalinks**: Asegurar que los permalinks están configurados correctamente
2. **Caché**: Limpiar caché después de implementar cambios
3. **Testing**: Probar en diferentes navegadores y dispositivos
4. **Backup**: Hacer backup antes de implementar cambios en producción 