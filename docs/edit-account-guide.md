# Guía de Campos Personalizados de Edición de Cuenta

## Descripción

Esta funcionalidad agrega campos personalizados para teléfonos en la página de edición de cuenta de WooCommerce. Los usuarios pueden editar sus números de teléfono para diferentes propósitos.

## Campos Disponibles

### 1. Teléfono Xeerpa (`meta_xeerpa_phone`)
- **Propósito**: Número de teléfono para integración con Xeerpa
- **Campo**: Campo personalizado de metadatos
- **Validación**: Formato de número de teléfono internacional

### 2. Teléfono de Facturación (`billing_phone`)
- **Propósito**: Número de teléfono para facturación
- **Campo**: Campo estándar de WooCommerce
- **Validación**: Formato de número de teléfono internacional

### 3. Teléfono de Envío (`shipping_phone`)
- **Propósito**: Número de teléfono para envíos
- **Campo**: Campo estándar de WooCommerce
- **Validación**: Formato de número de teléfono internacional

## Cómo Acceder

1. **Desde el menú de cuenta**: Los usuarios pueden acceder a "Editar cuenta" desde el menú de su cuenta
2. **URL directa**: `/mi-cuenta/edit-account/`

## Funcionalidades

### Formulario de Edición
- Campos de entrada para los tres tipos de teléfono
- Validación en tiempo real
- Mensajes de éxito y error
- Diseño responsivo

### Validación
- Formato de número de teléfono internacional
- Campos opcionales (pueden estar vacíos)
- Sanitización de datos de entrada

### Guardado de Datos
- Los datos se guardan como metadatos del usuario
- Compatible con WooCommerce estándar
- Seguridad con nonces de WordPress

## Shortcodes Disponibles

### Mostrar Información de Teléfono
```php
[user_phone_info]
```

#### Parámetros:
- `field`: Campo específico a mostrar
  - `meta_xeerpa_phone` - Solo teléfono Xeerpa
  - `billing_phone` - Solo teléfono de facturación
  - `shipping_phone` - Solo teléfono de envío
  - `all` - Todos los teléfonos (por defecto)

- `show_label`: Mostrar etiquetas
  - `true` - Mostrar etiquetas (por defecto)
  - `false` - Solo mostrar números

- `separator`: Separador entre múltiples teléfonos
  - Por defecto: `<br>`

#### Ejemplos de Uso:

```php
<!-- Mostrar todos los teléfonos -->
[user_phone_info]

<!-- Solo teléfono Xeerpa -->
[user_phone_info field="meta_xeerpa_phone"]

<!-- Solo números sin etiquetas -->
[user_phone_info show_label="false"]

<!-- Separados por comas -->
[user_phone_info separator=", "]
```

## Archivos Modificados

### Templates
- `woocommerce/myaccount/form-edit-account.php` - Formulario personalizado

### Funciones
- `functions.php` - Lógica de guardado y validación

### Estilos
- `assets/css/edit-account.css` - Estilos personalizados

## Hooks y Filtros

### Actions
- `woocommerce_save_account_details` - Guardar campos personalizados
- `woocommerce_save_account_details_errors` - Validar campos
- `wp_enqueue_scripts` - Cargar estilos CSS

### Filters
- `woocommerce_account_menu_items` - Modificar menú de cuenta

## Seguridad

- **Nonces**: Verificación de seguridad en formularios
- **Sanitización**: Limpieza de datos de entrada
- **Validación**: Verificación de formato de teléfonos
- **Escape**: Escape de datos de salida

## Compatibilidad

- **WooCommerce**: Compatible con versiones 3.0+
- **WordPress**: Compatible con versiones 5.0+
- **Elementor**: Compatible con el editor de Elementor
- **Responsive**: Diseño adaptativo para móviles

## Personalización

### Modificar Estilos
Edita el archivo `assets/css/edit-account.css` para personalizar la apariencia.

### Agregar Campos
Para agregar nuevos campos:

1. Agregar el campo al template `form-edit-account.php`
2. Agregar lógica de guardado en `functions.php`
3. Agregar validación si es necesario
4. Actualizar estilos CSS

### Ejemplo de Campo Adicional
```php
// En el template
<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
    <label for="custom_field">Campo Personalizado</label>
    <input type="text" name="custom_field" id="custom_field" value="<?php echo esc_attr(get_user_meta($user->ID, 'custom_field', true)); ?>" />
</p>

// En functions.php
if (isset($_POST['custom_field'])) {
    $custom_field = sanitize_text_field($_POST['custom_field']);
    update_user_meta($user_id, 'custom_field', $custom_field);
}
```

## Troubleshooting

### Problemas Comunes

1. **Los campos no se guardan**
   - Verificar que el nonce esté presente
   - Revisar permisos de usuario
   - Verificar logs de errores

2. **Validación falla**
   - Verificar formato de teléfono
   - Revisar expresión regular de validación

3. **Estilos no se cargan**
   - Verificar que el archivo CSS existe
   - Revisar ruta del archivo
   - Verificar permisos de archivo

### Debug
Habilita el modo debug de WordPress para ver errores detallados:

```php
// En wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Soporte

Para soporte técnico o preguntas sobre esta funcionalidad, contacta al equipo de desarrollo.

