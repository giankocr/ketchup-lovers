# KetchupLovers Child Theme

Un child theme personalizado para WordPress basado en Hello Elementor, diseñado específicamente para el sitio KetchupLovers con funcionalidades de monedero personalizadas.

## 🎨 Características

- **Child Theme**: Basado en Hello Elementor
- **Funcionalidad de Monedero**: Control de acceso personalizado para transferencias y retiros
- **WooCommerce**: Integración completa con WooCommerce
- **Responsive**: Diseño adaptable a todos los dispositivos
- **Personalizable**: Fácil de modificar y extender

## 📁 Estructura del Tema

```
kernsLovers/
├── assets/
│   ├── css/
│   │   ├── style.css          # Estilos principales del tema
│   │   └── wallet.css         # Estilos específicos del monedero
│   └── js/
│       └── prov-cant-dist.json
├── inc/
│   ├── currency_symbol.php    # Funciones de símbolos de moneda
│   ├── custom_wallet_misc.php # Control de acceso del monedero
│   └── wc_woo_states.php      # Estados de WooCommerce
├── functions.php              # Funciones principales del tema
├── style.css                  # Estilos del child theme
├── screenshot.png             # Captura de pantalla del tema
└── README.md                  # Este archivo
```

## 🚀 Instalación

1. Sube la carpeta `kernsLovers` a `/wp-content/themes/`
2. Activa el tema desde **Apariencia > Temas**
3. Configura las opciones del tema según tus necesidades

## ⚙️ Configuración

### Control de Acceso del Monedero

El tema incluye funcionalidades para denegar acceso a:
- Transferencias del monedero (`/mi-cuenta/wps-wallet/wallet-transfer/`)
- Retiros del monedero (`/mi-cuenta/wps-wallet/wallet-withdrawal/`)

### Personalización de Estilos

Los estilos se pueden modificar en:
- `style.css` - Estilos principales del child theme
- `assets/css/style.css` - Estilos personalizados adicionales
- `assets/css/wallet.css` - Estilos específicos del monedero

## 🛠️ Desarrollo

### Constantes del Tema

```php
define('KERN_LOVERS_VERSION', '1.0.0');
define('THEME_URI', get_template_directory_uri());
define('THEME_DIR', get_template_directory());
define('THEME_NAME', 'ketchuplovers');
```

### Hooks Principales

- `wp_enqueue_scripts` - Carga de estilos y scripts
- `body_class` - Clases CSS personalizadas
- `after_setup_theme` - Configuración del tema
- `template_redirect` - Control de acceso a URLs

### Funciones Principales

- `kernslovers_enqueue_styles()` - Carga de estilos
- `deny_wallet_access()` - Control de acceso del monedero
- `hide_wallet_tabs_css()` - Ocultar opciones del monedero
- `disable_wallet_links_js()` - Deshabilitar enlaces con JavaScript

## 🎯 Funcionalidades Específicas

### Control de Monedero

El tema implementa múltiples capas de seguridad:

1. **Redirección de URLs**: Previene acceso directo a páginas restringidas
2. **CSS**: Oculta visualmente las opciones del monedero
3. **JavaScript**: Deshabilita enlaces y funciones
4. **PHP**: Filtra elementos del menú y endpoints

### Estilos Personalizados

- Botones con gradientes personalizados
- Contenedores responsivos
- Estilos de header y footer
- Soporte para logos personalizados

## 📱 Responsive Design

El tema incluye media queries para:
- Dispositivos móviles (max-width: 768px)
- Impresión (print styles)
- Diferentes tamaños de pantalla

## 🔧 Mantenimiento

### Actualización de Versión

Para actualizar la versión del tema:
1. Modifica `KERN_LOVERS_VERSION` en `functions.php`
2. Actualiza la versión en `style.css`
3. Prueba todas las funcionalidades

### Limpieza de Caché

Después de modificar estilos:
1. Limpia la caché del navegador
2. Limpia la caché de WordPress si usas plugins de caché
3. Verifica que los cambios se apliquen correctamente

## 🐛 Solución de Problemas

### Error 404 en CSS
- Verifica que los archivos CSS existan en las rutas correctas
- Limpia la caché del navegador
- Verifica los permisos de archivos

### Opciones del Monedero Visibles
- Verifica que el archivo `custom_wallet_misc.php` esté incluido
- Revisa la consola del navegador para errores JavaScript
- Inspecciona los elementos con las herramientas de desarrollador

## 📞 Soporte

- **Desarrollador**: Gian Carlos
- **Sitio Web**: https://giancarlosv31.com
- **Tema**: https://ketchuplovers.com

## 📄 Licencia

MIT License - Ver archivo LICENSE para más detalles.

---

**Versión**: 1.0.0  
**Última actualización**: Enero 2025  
**Compatibilidad**: WordPress 6.0+, Hello Elementor 