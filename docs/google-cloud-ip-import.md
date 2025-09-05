# Importación de IPs de Google Cloud

Esta funcionalidad permite importar automáticamente los rangos de IP de Google Cloud a tu sistema de restricción de IPs del API de Wallet.

## ¿Qué hace?

La importación de IPs de Google Cloud:

1. **Descarga automáticamente** el archivo JSON oficial de rangos de IP de Google Cloud desde `https://www.gstatic.com/ipranges/cloud.json`
2. **Procesa todos los rangos** de IP (IPv4 e IPv6) disponibles
3. **Agrega solo los rangos nuevos** que no estén ya en tu lista de IPs permitidas
4. **Proporciona estadísticas** detalladas del proceso de importación

## ¿Por qué es útil?

- **Automatización**: No necesitas agregar manualmente cientos de rangos de IP
- **Actualización**: Los rangos se actualizan automáticamente desde la fuente oficial de Google
- **Seguridad**: Permite acceso desde servicios legítimos de Google Cloud
- **Flexibilidad**: Puedes filtrar por servicios o regiones específicas

## Cómo usar

### Desde la interfaz web

1. Ve a **Herramientas > API Wallet IPs** en el panel de administración
2. Busca la sección **"Importar IPs de Google Cloud"**
3. Selecciona el tipo de importación:
   - **Todas las IPs de Google Cloud**: Importa todos los rangos disponibles
   - **Filtrar por servicio/región**: Permite seleccionar servicios y regiones específicas
4. Haz clic en **"Importar IPs de Google Cloud"**

### Desde código PHP

```php
// Importar todas las IPs de Google Cloud
$result = kl_wallet_add_google_cloud_ips();

if ($result['success']) {
    echo "Se agregaron " . $result['added_count'] . " rangos de IP";
} else {
    echo "Error: " . $result['message'];
}

// Importar con filtros específicos
$filters = [
    'services' => ['Google Cloud', 'Cloud NAT'],
    'scopes' => ['us-central1', 'us-east1']
];

$result = kl_wallet_add_google_cloud_ips_filtered($filters);
```

### Script de prueba

Puedes ejecutar el script de prueba para verificar la funcionalidad:

```
https://tu-sitio.com/wp-content/themes/ketchup-lovers/API/test-google-cloud-import.php
```

## Funciones disponibles

### `kl_wallet_add_google_cloud_ips()`

Importa todos los rangos de IP de Google Cloud.

**Retorna:**
```php
[
    'success' => true/false,
    'message' => 'Mensaje descriptivo',
    'added_count' => 123,
    'skipped_count' => 45,
    'total_processed' => 168
]
```

### `kl_wallet_add_google_cloud_ips_filtered($filters)`

Importa rangos de IP de Google Cloud con filtros específicos.

**Parámetros:**
- `$filters` (array): Filtros a aplicar
  - `services` (array): Lista de servicios a incluir
  - `scopes` (array): Lista de regiones a incluir

**Ejemplo:**
```php
$filters = [
    'services' => ['Google Cloud', 'Cloud NAT'],
    'scopes' => ['us-central1', 'europe-west1']
];
```

### `kl_wallet_get_google_cloud_stats()`

Obtiene estadísticas de los rangos de IP disponibles en Google Cloud.

**Retorna:**
```php
[
    'success' => true/false,
    'stats' => [
        'total_prefixes' => 1234,
        'ipv4_prefixes' => 1000,
        'ipv6_prefixes' => 234,
        'services' => ['Google Cloud' => 500, 'Cloud NAT' => 300],
        'scopes' => ['us-central1' => 100, 'us-east1' => 80],
        'sync_token' => '1756325194905',
        'creation_time' => '2025-08-27T13:06:34.905662'
    ]
]
```

## Servicios disponibles

Los principales servicios de Google Cloud incluyen:

- **Google Cloud**: Servicios generales de Google Cloud Platform
- **Cloud NAT**: Network Address Translation
- **Cloud VPN**: Virtual Private Network
- **Cloud Load Balancing**: Balanceo de carga
- **Cloud Armor**: Protección DDoS

## Regiones disponibles

Algunas de las regiones más comunes:

- **us-central1**: Iowa (EE.UU.)
- **us-east1**: Carolina del Sur (EE.UU.)
- **us-west1**: Oregón (EE.UU.)
- **europe-west1**: Bélgica
- **asia-east1**: Taiwán
- **asia-northeast1**: Tokio (Japón)

## Consideraciones importantes

### Rendimiento
- La importación puede tomar varios segundos debido a la cantidad de rangos
- Se recomienda ejecutar durante períodos de bajo tráfico
- El proceso es idempotente: no duplica rangos existentes

### Seguridad
- Solo se importan rangos oficiales de Google Cloud
- Los rangos se validan antes de ser agregados
- Se mantiene un registro detallado en los logs

### Mantenimiento
- Los rangos de Google Cloud se actualizan regularmente
- Se recomienda ejecutar la importación periódicamente
- Puedes eliminar rangos específicos si es necesario

## Solución de problemas

### Error: "Función wp_remote_get no disponible"
- Verifica que WordPress esté completamente cargado
- Asegúrate de que las funciones de red estén habilitadas

### Error: "Error al obtener rangos de IP de Google Cloud"
- Verifica la conectividad a internet
- Comprueba que el archivo JSON esté accesible
- Revisa los logs de WordPress para más detalles

### Error: "Error al procesar datos de Google Cloud"
- El formato del archivo JSON puede haber cambiado
- Verifica que el archivo sea válido
- Contacta al soporte si el problema persiste

### Importación lenta
- Es normal que tome varios segundos
- Considera usar filtros para reducir la cantidad de rangos
- Ejecuta durante períodos de bajo tráfico

## Logs y depuración

Todas las operaciones se registran en los logs de WordPress:

```php
// Ejemplo de logs
error_log("KL Wallet IP: Google Cloud IPs procesadas - Agregadas: 150, Omitidas: 25");
error_log("KL Wallet IP: Error al agregar rango de Google Cloud: 34.1.208.0/20");
```

Para habilitar logs detallados, agrega esto a tu `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Ejemplos de uso avanzado

### Importar solo rangos IPv4
```php
$filters = [
    'services' => ['Google Cloud'],
    'scopes' => ['us-central1']
];

$result = kl_wallet_add_google_cloud_ips_filtered($filters);
```

### Verificar estadísticas antes de importar
```php
$stats = kl_wallet_get_google_cloud_stats();
if ($stats['success'] && $stats['stats']['total_prefixes'] > 0) {
    $result = kl_wallet_add_google_cloud_ips();
}
```

### Importar con manejo de errores
```php
try {
    $result = kl_wallet_add_google_cloud_ips();
    if ($result['success']) {
        echo "Importación exitosa: " . $result['added_count'] . " IPs agregadas";
    } else {
        echo "Error: " . $result['message'];
    }
} catch (Exception $e) {
    echo "Excepción: " . $e->getMessage();
}
```
