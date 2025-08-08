# API de Wallet - Autenticación JWT

## Descripción

Este API utiliza **JWT (JSON Web Tokens)** como método único de autenticación para comunicación entre servicios.

## Endpoints Disponibles

### 1. Generar Token JWT
```
POST /wp-json/kl-wallet/v1/generate-token
```

**Headers requeridos:**
```
X-API-Key: tu_api_key_aqui
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "service_name": "nombre-del-servicio",
  "expiration": 3600
}
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_in": 3600,
  "token_type": "Bearer",
  "service": "nombre-del-servicio"
}
```

### 2. Obtener Teléfono de Usuario
```
GET /wp-json/kl-wallet/v1/user-phone
```

**Headers requeridos:**
```
Authorization: Bearer TU_TOKEN_JWT_AQUI
Content-Type: application/json
```

**Parámetros:**
- `user_id` (requerido): ID del usuario

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Hola",
  "phone_number": "+1234567890",
  "user_id": 1
}
```

## Flujo de Uso

### Paso 1: Generar Token
```bash
curl -X POST \
  "http://tu-dominio.com/wp-json/kl-wallet/v1/generate-token" \
  -H "X-API-Key: tu_api_key_aqui" \
  -H "Content-Type: application/json" \
  -d '{
    "service_name": "mi-servicio",
    "expiration": 3600
  }'
```

### Paso 2: Usar Token
```bash
curl -X GET \
  "http://tu-dominio.com/wp-json/kl-wallet/v1/user-phone?user_id=1" \
  -H "Authorization: Bearer TU_TOKEN_JWT_AQUI" \
  -H "Content-Type: application/json"
```

## Ejemplos por Lenguaje

### PHP
```php
<?php
// Paso 1: Generar token
$api_key = 'tu_api_key_aqui';
$response = wp_remote_post('http://tu-dominio.com/wp-json/kl-wallet/v1/generate-token', [
    'headers' => [
        'X-API-Key: ' . $api_key,
        'Content-Type: application/json'
    ],
    'body' => json_encode([
        'service_name' => 'mi-servicio',
        'expiration' => 3600
    ])
]);

$data = json_decode(wp_remote_retrieve_body($response), true);
$jwt_token = $data['token'];

// Paso 2: Usar token
$response = wp_remote_get('http://tu-dominio.com/wp-json/kl-wallet/v1/user-phone?user_id=1', [
    'headers' => [
        'Authorization: Bearer ' . $jwt_token,
        'Content-Type: application/json'
    ]
]);

$result = json_decode(wp_remote_retrieve_body($response), true);
?>
```

### JavaScript
```javascript
// Paso 1: Generar token
async function generateToken() {
    const response = await fetch('/wp-json/kl-wallet/v1/generate-token', {
        method: 'POST',
        headers: {
            'X-API-Key': 'tu_api_key_aqui',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            service_name: 'mi-servicio',
            expiration: 3600
        })
    });
    
    const data = await response.json();
    return data.token;
}

// Paso 2: Usar token
async function getUserPhone(userId, token) {
    const response = await fetch(`/wp-json/kl-wallet/v1/user-phone?user_id=${userId}`, {
        method: 'GET',
        headers: {
            'Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
        }
    });
    
    return await response.json();
}

// Uso
generateToken().then(token => {
    getUserPhone(1, token).then(result => {
        console.log(result);
    });
});
```

### Python
```python
import requests
import json

# Paso 1: Generar token
def generate_token():
    url = "http://tu-dominio.com/wp-json/kl-wallet/v1/generate-token"
    headers = {
        'X-API-Key': 'tu_api_key_aqui',
        'Content-Type': 'application/json'
    }
    data = {
        'service_name': 'mi-servicio',
        'expiration': 3600
    }
    
    response = requests.post(url, headers=headers, json=data)
    return response.json()['token']

# Paso 2: Usar token
def get_user_phone(user_id, token):
    url = f"http://tu-dominio.com/wp-json/kl-wallet/v1/user-phone?user_id={user_id}"
    headers = {
        'Authorization': f'Bearer {token}',
        'Content-Type': 'application/json'
    }
    
    response = requests.get(url, headers=headers)
    return response.json()

# Uso
token = generate_token()
result = get_user_phone(1, token)
print(result)
```

## Configuración en Postman

### 1. Generar Token
```
Method: POST
URL: http://tu-dominio.com/wp-json/kl-wallet/v1/generate-token
Headers:
  X-API-Key: tu_api_key_aqui
  Content-Type: application/json
Body (raw JSON):
{
  "service_name": "mi-servicio",
  "expiration": 3600
}
```

### 2. Usar Token
```
Method: GET
URL: http://tu-dominio.com/wp-json/kl-wallet/v1/user-phone?user_id=1
Headers:
  Authorization: Bearer {{jwt_token}}
  Content-Type: application/json
```

### Variables de Entorno en Postman
```
api_key: tu_api_key_aqui
base_url: http://tu-dominio.com
jwt_token: [token_generado_del_paso_1]
```

## Códigos de Error

| Código | Error | Descripción |
|--------|-------|-------------|
| 401 | `missing_jwt` | Token JWT requerido en header Authorization: Bearer |
| 401 | `invalid_jwt` | Token JWT inválido o expirado |
| 401 | `invalid_api_key` | API Key inválida para generar tokens JWT |
| 429 | `rate_limit_exceeded` | Límite de solicitudes excedido |
| 400 | `invalid_service_name` | Nombre de servicio requerido |
| 400 | `invalid_expiration` | Tiempo de expiración inválido (1-86400 segundos) |

## Seguridad

- **Tokens con expiración:** Los tokens JWT tienen un tiempo de vida limitado
- **Rate limiting:** Máximo 60 solicitudes por minuto por IP
- **Logging:** Todas las solicitudes se registran para auditoría
- **Sanitización:** Todos los datos de entrada se limpian y validan

## Notas Importantes

1. **Solo JWT:** El API ahora solo acepta autenticación por JWT
2. **API Key solo para generar tokens:** La API Key solo se usa para el endpoint de generación de tokens
3. **Tokens expiran:** Los tokens tienen un tiempo de vida máximo de 24 horas
4. **Servicios identificados:** Cada token incluye el nombre del servicio para auditoría 