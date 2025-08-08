<?php
/**
 * Configuración de la API de Wallet
 * 
 * Este archivo centraliza la configuración de la API
 * para facilitar el mantenimiento y la seguridad
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    http_response_code(403);
    exit('Acceso directo prohibido');
}

/**
 * Configuración de la API de Wallet
 */
class KL_Wallet_API_Config {
    
    /**
     * Obtener la API Key para autenticación
     * 
     * @return string
     */
    public static function get_api_key(): string {
        // Prioridad 1: Constante definida en wp-config.php
        if (defined('KL_WALLET_API_KEY')) {
            return KL_WALLET_API_KEY;
        }
        
        // Prioridad 2: Usar JWT_AUTH_SECRET_KEY si está disponible
        if (defined('JWT_AUTH_SECRET_KEY')) {
            return JWT_AUTH_SECRET_KEY;
        }
        
        // Prioridad 3: Obtener de opciones de WordPress
        $stored_key = get_option('kl_wallet_api_key');
        if (!empty($stored_key)) {
            return $stored_key;
        }
        
        // Prioridad 4: Clave por defecto (solo para desarrollo)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return 'kl_wallet_secure_key_2024';
        }
        
        // Prioridad 5: Generar una clave temporal si no hay ninguna configurada
        $temp_key = wp_generate_password(64, false, false);
        update_option('kl_wallet_api_key', $temp_key);
        return $temp_key;
    }
    
    /**
     * Generar firma para servicio a servicio
     * 
     * @param string $timestamp Timestamp de la solicitud
     * @param string $payload Datos de la solicitud
     * @return string
     */
    public static function generate_service_signature(string $timestamp, string $payload = ''): string {
        $api_key = self::get_api_key();
        $data = $timestamp . $payload;
        return hash_hmac('sha256', $data, $api_key);
    }
    
    /**
     * Verificar firma de servicio a servicio
     * 
     * @param string $signature Firma recibida
     * @param string $timestamp Timestamp de la solicitud
     * @param string $payload Datos de la solicitud
     * @param int $max_age Tiempo máximo de validez en segundos
     * @return bool
     */
    public static function verify_service_signature(string $signature, string $timestamp, string $payload = '', int $max_age = 300): bool {
        // Verificar que el timestamp no sea muy antiguo
        if (abs(time() - intval($timestamp)) > $max_age) {
            return false;
        }
        
        $expected_signature = self::generate_service_signature($timestamp, $payload);
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Generar una nueva API Key
     * 
     * @return string
     */
    public static function generate_api_key(): string {
        return wp_generate_password(64, false, false);
    }
    
    /**
     * Guardar API Key en opciones de WordPress
     * 
     * @param string $api_key
     * @return bool
     */
    public static function save_api_key(string $api_key): bool {
        return update_option('kl_wallet_api_key', $api_key);
    }
    
    /**
     * Obtener configuración de rate limiting
     * 
     * @return array
     */
    public static function get_rate_limit_config(): array {
        return [
            'requests_per_minute' => get_option('kl_wallet_rate_limit', 60),
            'window_seconds' => 60,
            'enabled' => get_option('kl_wallet_rate_limit_enabled', true)
        ];
    }
    
    /**
     * Obtener configuración de logging
     * 
     * @return array
     */
    public static function get_logging_config(): array {
        return [
            'enabled' => get_option('kl_wallet_logging_enabled', true),
            'file_logging' => get_option('kl_wallet_file_logging', true),
            'database_logging' => get_option('kl_wallet_db_logging', true),
            'log_level' => get_option('kl_wallet_log_level', 'info')
        ];
    }
    
    /**
     * Verificar si la API está habilitada
     * 
     * @return bool
     */
    public static function is_api_enabled(): bool {
        return get_option('kl_wallet_api_enabled', true);
    }
    
    /**
     * Obtener IPs permitidas (whitelist)
     * 
     * @return array
     */
    public static function get_allowed_ips(): array {
        $ips = get_option('kl_wallet_allowed_ips', '');
        if (empty($ips)) {
            return [];
        }
        
        return array_filter(array_map('trim', explode(',', $ips)));
    }
    
    /**
     * Verificar si una IP está permitida
     * 
     * @param string $ip
     * @return bool
     */
    public static function is_ip_allowed(string $ip): bool {
        // Si la restricción está deshabilitada, permitir todas las IPs
        if (!self::is_ip_restriction_enabled()) {
            return true;
        }
        
        $allowed_ips = self::get_allowed_ips();
        
        // Si la restricción está habilitada pero no hay IPs configuradas, denegar todas
        if (empty($allowed_ips)) {
            return false;
        }
        
        // Verificar IP exacta
        if (in_array($ip, $allowed_ips)) {
            return true;
        }
        
        // Verificar rangos CIDR
        foreach ($allowed_ips as $allowed_ip) {
            if (self::is_ip_in_cidr_range($ip, $allowed_ip)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verificar si una IP está en un rango CIDR
     * 
     * @param string $ip IP a verificar
     * @param string $cidr Rango CIDR (ej: 192.168.1.0/24)
     * @return bool
     */
    private static function is_ip_in_cidr_range(string $ip, string $cidr): bool {
        // Si no es un rango CIDR, verificar IP exacta
        if (strpos($cidr, '/') === false) {
            return $ip === $cidr;
        }
        
        list($subnet, $mask) = explode('/', $cidr);
        
        // Convertir IPs a enteros
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        
        if ($ip_long === false || $subnet_long === false) {
            return false;
        }
        
        // Calcular máscara
        $mask_long = -1 << (32 - intval($mask));
        
        // Verificar si la IP está en el rango
        return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
    }
    
    /**
     * Agregar IP a la whitelist
     * 
     * @param string $ip IP a agregar
     * @return bool
     */
    public static function add_allowed_ip(string $ip): bool {
        $allowed_ips = self::get_allowed_ips();
        
        // Verificar que la IP sea válida
        if (!filter_var($ip, FILTER_VALIDATE_IP) && !self::is_valid_cidr($ip)) {
            return false;
        }
        
        // Si ya está en la lista, no hacer nada
        if (in_array($ip, $allowed_ips)) {
            return true;
        }
        
        $allowed_ips[] = $ip;
        return update_option('kl_wallet_allowed_ips', implode(',', $allowed_ips));
    }
    
    /**
     * Remover IP de la whitelist
     * 
     * @param string $ip IP a remover
     * @return bool
     */
    public static function remove_allowed_ip(string $ip): bool {
        $allowed_ips = self::get_allowed_ips();
        $allowed_ips = array_filter($allowed_ips, function($allowed_ip) use ($ip) {
            return $allowed_ip !== $ip;
        });
        
        return update_option('kl_wallet_allowed_ips', implode(',', $allowed_ips));
    }
    
    /**
     * Verificar si una cadena es un CIDR válido
     * 
     * @param string $cidr
     * @return bool
     */
    private static function is_valid_cidr(string $cidr): bool {
        if (strpos($cidr, '/') === false) {
            return filter_var($cidr, FILTER_VALIDATE_IP) !== false;
        }
        
        list($ip, $mask) = explode('/', $cidr);
        
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        $mask = intval($mask);
        return $mask >= 0 && $mask <= 32;
    }
    
    /**
     * Verificar si la restricción de IPs está habilitada
     * 
     * @return bool
     */
    public static function is_ip_restriction_enabled(): bool {
        return get_option('kl_wallet_ip_restriction_enabled', true);
    }
    
    /**
     * Obtener configuración de restricción de IPs
     * 
     * @return array
     */
    public static function get_ip_restriction_config(): array {
        return [
            'enabled' => self::is_ip_restriction_enabled(),
            'allowed_ips' => self::get_allowed_ips(),
            'strict_mode' => get_option('kl_wallet_ip_strict_mode', false)
        ];
    }
}

/**
 * Funciones helper para la configuración
 */

/**
 * Obtener API Key de forma segura
 * 
 * @return string
 */
function kl_wallet_get_api_key(): string {
    return KL_Wallet_API_Config::get_api_key();
}

/**
 * Verificar si la API está configurada correctamente
 * 
 * @return bool
 */
function kl_wallet_is_api_configured(): bool {
    try {
        KL_Wallet_API_Config::get_api_key();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Generar y guardar una nueva API Key
 * 
 * @return string
 */
function kl_wallet_regenerate_api_key(): string {
    $new_key = KL_Wallet_API_Config::generate_api_key();
    KL_Wallet_API_Config::save_api_key($new_key);
    return $new_key;
}

/**
 * Funciones helper para gestión de IPs
 */

/**
 * Agregar IP a la whitelist
 * 
 * @param string $ip IP a agregar
 * @return bool
 */
function kl_wallet_add_allowed_ip(string $ip): bool {
    return KL_Wallet_API_Config::add_allowed_ip($ip);
}

/**
 * Remover IP de la whitelist
 * 
 * @param string $ip IP a remover
 * @return bool
 */
function kl_wallet_remove_allowed_ip(string $ip): bool {
    return KL_Wallet_API_Config::remove_allowed_ip($ip);
}

/**
 * Obtener IPs permitidas
 * 
 * @return array
 */
function kl_wallet_get_allowed_ips(): array {
    return KL_Wallet_API_Config::get_allowed_ips();
}

/**
 * Verificar si una IP está permitida
 * 
 * @param string $ip
 * @return bool
 */
function kl_wallet_is_ip_allowed(string $ip): bool {
    return KL_Wallet_API_Config::is_ip_allowed($ip);
}

/**
 * Habilitar restricción de IPs
 * 
 * @return bool
 */
function kl_wallet_enable_ip_restriction(): bool {
    $result = update_option('kl_wallet_ip_restriction_enabled', true);
    error_log("KL Wallet IP Restriction: Habilitando restricción - Resultado: " . ($result ? 'true' : 'false'));
    return $result;
}

/**
 * Deshabilitar restricción de IPs
 * 
 * @return bool
 */
function kl_wallet_disable_ip_restriction(): bool {
    $result = update_option('kl_wallet_ip_restriction_enabled', false);
    error_log("KL Wallet IP Restriction: Deshabilitando restricción - Resultado: " . ($result ? 'true' : 'false'));
    return $result;
}

/**
 * Verificar si la restricción de IPs está habilitada
 * 
 * @return bool
 */
function kl_wallet_is_ip_restriction_enabled(): bool {
    return get_option('kl_wallet_ip_restriction_enabled', true);
} 