<?php
/**
 * Gestor de IPs para el API de Wallet
 * 
 * Este archivo proporciona una interfaz para gestionar las IPs permitidas
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    http_response_code(403);
    exit('Acceso directo prohibido');
}

/**
 * Clase para gestionar IPs del API
 */
class KL_Wallet_IP_Manager {
    
    /**
     * Agregar IP a la whitelist
     * 
     * @param string $ip IP a agregar
     * @return array Resultado de la operación
     */
    public static function add_ip(string $ip): array {
        if (empty($ip)) {
            return [
                'success' => false,
                'message' => 'IP no puede estar vacía'
            ];
        }
        
        // Validar formato de IP
        if (!self::is_valid_ip_or_cidr($ip)) {
            return [
                'success' => false,
                'message' => 'Formato de IP inválido. Use formato IPv4, IPv6 o CIDR (ej: 192.168.1.0/24)'
            ];
        }
        
        // Agregar IP
        if (kl_wallet_add_allowed_ip($ip)) {
            return [
                'success' => true,
                'message' => "IP $ip agregada exitosamente",
                'ip' => $ip
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Error al agregar IP'
        ];
    }
    
    /**
     * Remover IP de la whitelist
     * 
     * @param string $ip IP a remover
     * @return array Resultado de la operación
     */
    public static function remove_ip(string $ip): array {
        if (empty($ip)) {
            return [
                'success' => false,
                'message' => 'IP no puede estar vacía'
            ];
        }
        
        // Verificar si la IP existe
        $allowed_ips = kl_wallet_get_allowed_ips();
        if (!in_array($ip, $allowed_ips)) {
            return [
                'success' => false,
                'message' => "IP $ip no está en la lista de permitidas"
            ];
        }
        
        // Remover IP
        if (kl_wallet_remove_allowed_ip($ip)) {
            return [
                'success' => true,
                'message' => "IP $ip removida exitosamente",
                'ip' => $ip
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Error al remover IP'
        ];
    }
    
    /**
     * Obtener lista de IPs permitidas
     * 
     * @return array
     */
    public static function get_allowed_ips(): array {
        return [
            'success' => true,
            'ips' => kl_wallet_get_allowed_ips(),
            'count' => count(kl_wallet_get_allowed_ips()),
            'restriction_enabled' => kl_wallet_is_ip_restriction_enabled()
        ];
    }
    
    /**
     * Verificar si una IP está permitida
     * 
     * @param string $ip IP a verificar
     * @return array
     */
    public static function check_ip(string $ip): array {
        if (empty($ip)) {
            return [
                'success' => false,
                'message' => 'IP no puede estar vacía'
            ];
        }
        
        $is_allowed = kl_wallet_is_ip_allowed($ip);
        $restriction_enabled = kl_wallet_is_ip_restriction_enabled();
        
        return [
            'success' => true,
            'ip' => $ip,
            'is_allowed' => $is_allowed,
            'restriction_enabled' => $restriction_enabled,
            'message' => $restriction_enabled 
                ? ($is_allowed ? 'IP permitida' : 'IP no permitida')
                : 'Restricción de IPs deshabilitada'
        ];
    }
    
    /**
     * Habilitar restricción de IPs
     * 
     * @return array
     */
    public static function enable_restriction(): array {
        if (kl_wallet_enable_ip_restriction()) {
            return [
                'success' => true,
                'message' => 'Restricción de IPs habilitada'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Error al habilitar restricción de IPs'
        ];
    }
    
    /**
     * Deshabilitar restricción de IPs
     * 
     * @return array
     */
    public static function disable_restriction(): array {
        if (kl_wallet_disable_ip_restriction()) {
            return [
                'success' => true,
                'message' => 'Restricción de IPs deshabilitada'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Error al deshabilitar restricción de IPs'
        ];
    }
    
    /**
     * Obtener IP actual del cliente
     * 
     * @return array
     */
    public static function get_current_ip(): array {
        $ip = self::get_client_ip();
        
        return [
            'success' => true,
            'ip' => $ip,
            'is_allowed' => kl_wallet_is_ip_allowed($ip),
            'restriction_enabled' => kl_wallet_is_ip_restriction_enabled()
        ];
    }
    
    /**
     * Validar formato de IP o CIDR
     * 
     * @param string $ip
     * @return bool
     */
    private static function is_valid_ip_or_cidr(string $ip): bool {
        // Verificar si es una IP válida
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }
        
        // Verificar si es un CIDR válido
        if (strpos($ip, '/') !== false) {
            list($subnet, $mask) = explode('/', $ip);
            
            if (!filter_var($subnet, FILTER_VALIDATE_IP)) {
                return false;
            }
            
            $mask = intval($mask);
            return $mask >= 0 && $mask <= 32;
        }
        
        return false;
    }
    
    /**
     * Obtener IP del cliente
     * 
     * @return string
     */
    private static function get_client_ip(): string {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

/**
 * Funciones helper para gestión de IPs
 */

/**
 * Agregar IP a la whitelist
 * 
 * @param string $ip
 * @return array
 */
function kl_wallet_ip_add(string $ip): array {
    return KL_Wallet_IP_Manager::add_ip($ip);
}

/**
 * Remover IP de la whitelist
 * 
 * @param string $ip
 * @return array
 */
function kl_wallet_ip_remove(string $ip): array {
    return KL_Wallet_IP_Manager::remove_ip($ip);
}

/**
 * Obtener IPs permitidas
 * 
 * @return array
 */
function kl_wallet_ip_list(): array {
    return KL_Wallet_IP_Manager::get_allowed_ips();
}

/**
 * Verificar IP
 * 
 * @param string $ip
 * @return array
 */
function kl_wallet_ip_check(string $ip): array {
    return KL_Wallet_IP_Manager::check_ip($ip);
}

/**
 * Obtener IP actual
 * 
 * @return array
 */
function kl_wallet_ip_current(): array {
    return KL_Wallet_IP_Manager::get_current_ip();
} 