# Panel de Administración - API de Wallet

## Descripción

El panel de administración del API de Wallet te permite gestionar fácilmente las IPs permitidas para acceder al API desde el dashboard de WordPress.

## Acceso al Panel

### Ubicación en el Dashboard
1. Inicia sesión en WordPress como administrador
2. Ve a **Herramientas** → **API Wallet IPs**
3. O accede directamente a: `/wp-admin/tools.php?page=kl-wallet-ip-manager`

### Permisos Requeridos
- **Rol mínimo:** Administrador (`manage_options`)
- **Capacidad:** `manage_options`

## Funcionalidades del Panel

### 1. Estado del API
Muestra información en tiempo real sobre:
- **Estado del API:** Disponible o no disponible
- **Tu IP actual:** La IP desde donde accedes
- **Restricción de IPs:** Habilitada o deshabilitada
- **Tu IP está permitida:** Si tu IP actual puede acceder al API

### 2. Control de Restricción
- **Habilitar/Deshabilitar:** Controla si la restricción de IPs está activa
- **Estado visual:** Muestra claramente si está habilitada o deshabilitada
- **Descripción:** Explica qué sucede cuando está deshabilitada

### 3. Gestión de IPs
#### Agregar IPs
- **Campo de entrada:** Ingresa IP individual o rango CIDR
- **Validación automática:** Verifica el formato de la IP
- **Ejemplos incluidos:** Muestra formatos válidos

#### Lista de IPs Permitidas
- **Tabla interactiva:** Muestra todas las IPs configuradas
- **Acciones en lote:** Selecciona múltiples IPs para eliminar
- **Acciones individuales:** Elimina IPs una por una
- **Búsqueda:** Filtra IPs por texto

### 4. Información y Ayuda
- **Formatos soportados:** Explica IPv4, IPv6 y CIDR
- **Ejemplos de uso:** Casos prácticos comunes
- **Endpoints del API:** Lista los endpoints disponibles

## Formatos de IP Soportados

### IP Individual
```
192.168.1.100
10.0.0.1
172.16.0.1
```

### Rango CIDR
```
192.168.1.0/24    # Permite 192.168.1.0 a 192.168.1.255
10.0.0.0/8        # Permite toda la red 10.x.x.x
172.16.0.0/12     # Permite toda la red 172.16-31.x.x
```

### IPv6
```
2001:db8::1
2001:db8::/32
```

## Casos de Uso Comunes

### Desarrollo Local
```bash
# Solo localhost
127.0.0.1
::1

# Red local completa
192.168.1.0/24
```

### Servidores de Producción
```bash
# IP específica del servidor
203.0.113.100

# Red privada completa
10.0.0.0/8
172.16.0.0/12
```

### Múltiples Servidores
```bash
# Varios servidores específicos
203.0.113.100
203.0.113.101
203.0.113.102

# O usar rango
203.0.113.0/24
```

## Funcionalidades Avanzadas

### Acciones en Lote
1. **Seleccionar IPs:** Usa checkboxes para seleccionar múltiples IPs
2. **Acción en lote:** Selecciona "Eliminar" del dropdown
3. **Aplicar:** Confirma la acción para todas las IPs seleccionadas

### Búsqueda
- **Campo de búsqueda:** Filtra IPs en tiempo real
- **Búsqueda parcial:** Encuentra IPs que contengan el texto
- **No distingue mayúsculas:** Búsqueda insensible a mayúsculas

### Validación Automática
- **Formato de IP:** Valida automáticamente el formato
- **Mensajes de error:** Muestra errores claros
- **Validación en tiempo real:** Mientras escribes

### Auto-refresh
- **Actualización automática:** Cada 30 segundos
- **Estado en tiempo real:** Muestra cambios automáticamente
- **Sin recargar página:** Experiencia fluida

## Notificaciones

### Tipos de Notificación
- **✅ Éxito:** Operaciones completadas correctamente
- **❌ Error:** Errores en la operación
- **⚠️ Advertencia:** Estados de advertencia
- **ℹ️ Información:** Información general

### Duración
- **Auto-desaparición:** 5 segundos
- **Manual:** Hacer clic para cerrar
- **Persistente:** Algunas notificaciones importantes

## Seguridad

### Verificación de Permisos
- **Solo administradores:** Acceso restringido
- **Nonces:** Protección CSRF
- **Sanitización:** Limpieza de datos de entrada
- **Validación:** Verificación de formatos

### Logging
- **Acciones registradas:** Todas las operaciones se registran
- **Auditoría:** Historial de cambios
- **Debug:** Información para desarrollo

## Solución de Problemas

### IP No Permitida
1. **Verificar IP actual:** Revisa tu IP en el panel
2. **Agregar IP:** Incluye tu IP en la lista de permitidas
3. **Verificar formato:** Asegúrate de usar el formato correcto

### API No Disponible
1. **Verificar archivos:** Asegúrate de que los archivos del API estén presentes
2. **Permisos:** Verifica permisos de archivos
3. **Logs:** Revisa logs de WordPress para errores

### Errores de Validación
1. **Formato de IP:** Verifica que la IP tenga el formato correcto
2. **Rango CIDR:** Asegúrate de que el rango sea válido
3. **Caracteres especiales:** Evita caracteres no permitidos

## Personalización

### Estilos CSS
Los estilos se pueden personalizar editando:
```
assets/css/wallet-admin.css
```

### JavaScript
Funcionalidades adicionales en:
```
assets/js/wallet-admin.js
```

### Hooks de WordPress
El panel usa hooks estándar de WordPress para integración.

## Soporte

### Documentación
- **README-JWT.md:** Documentación del API
- **Este archivo:** Guía del panel de administración
- **Código comentado:** Explicaciones en el código

### Logs
- **WordPress Debug:** Habilita debug para ver errores
- **API Logs:** Logs específicos del API
- **Admin Logs:** Logs del panel de administración

### Contacto
Para soporte técnico, revisa los logs y la documentación antes de contactar. 