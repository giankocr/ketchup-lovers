<?php
/**
 * Fallback para funciones de IP del API de Wallet
 * 
 * Este archivo proporciona implementaciones de respaldo para las funciones
 * de gestión de IPs en caso de que no estén disponibles
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    http_response_code(403);
    exit('Acceso directo prohibido');
}

/**
 * Verificar si la restricción de IPs está habilitada
 * 
 * @return bool
 */
if (!function_exists('kl_wallet_is_ip_restriction_enabled')) {
    function kl_wallet_is_ip_restriction_enabled(): bool {
        // Verificar si WordPress está completamente cargado
        if (!function_exists('get_option')) {
            return false;
        }
        
        return (bool) get_option('kl_wallet_ip_restriction_enabled', false);
    }
}

/**
 * Verificar si una IP está permitida
 * 
 * @param string $ip
 * @return bool
 */
if (!function_exists('kl_wallet_is_ip_allowed')) {
    function kl_wallet_is_ip_allowed(string $ip): bool {
        // Si la restricción no está habilitada, permitir todas las IPs
        if (!kl_wallet_is_ip_restriction_enabled()) {
            return true;
        }
        
        // Verificar si WordPress está completamente cargado
        if (!function_exists('get_option')) {
            return true; // Permitir por defecto si WordPress no está listo
        }
        
        $allowed_ips = get_option('kl_wallet_allowed_ips', []);
        
        if (empty($allowed_ips)) {
            return true; // Permitir por defecto si no hay IPs configuradas
        }
        
        // Verificar si la IP está en la lista
        foreach ($allowed_ips as $allowed_ip) {
            if (kl_wallet_ip_matches($ip, $allowed_ip)) {
                return true;
            }
        }
        
        return false;
    }
}

/**
 * Agregar IP a la lista de permitidas
 * 
 * @param string $ip
 * @return bool
 */
if (!function_exists('kl_wallet_add_allowed_ip')) {
    function kl_wallet_add_allowed_ip(string $ip): bool {
        // Verificar si WordPress está completamente cargado
        if (!function_exists('get_option') || !function_exists('update_option')) {
            return false;
        }
        
        $allowed_ips = get_option('kl_wallet_allowed_ips', []);
        
        if (!in_array($ip, $allowed_ips)) {
            $allowed_ips[] = $ip;
            return update_option('kl_wallet_allowed_ips', $allowed_ips);
        }
        
        return true; // La IP ya estaba en la lista
    }
}

/**
 * Remover IP de la lista de permitidas
 * 
 * @param string $ip
 * @return bool
 */
if (!function_exists('kl_wallet_remove_allowed_ip')) {
    function kl_wallet_remove_allowed_ip(string $ip): bool {
        // Verificar si WordPress está completamente cargado
        if (!function_exists('get_option') || !function_exists('update_option')) {
            return false;
        }
        
        $allowed_ips = get_option('kl_wallet_allowed_ips', []);
        
        $key = array_search($ip, $allowed_ips);
        if ($key !== false) {
            unset($allowed_ips[$key]);
            $allowed_ips = array_values($allowed_ips); // Reindexar array
            return update_option('kl_wallet_allowed_ips', $allowed_ips);
        }
        
        return true; // La IP no estaba en la lista
    }
}

/**
 * Obtener lista de IPs permitidas
 * 
 * @return array
 */
if (!function_exists('kl_wallet_get_allowed_ips')) {
    function kl_wallet_get_allowed_ips(): array {
        // Verificar si WordPress está completamente cargado
        if (!function_exists('get_option')) {
            return [];
        }
        
        return get_option('kl_wallet_allowed_ips', []);
    }
}

/**
 * Habilitar restricción de IPs
 * 
 * @return bool
 */
if (!function_exists('kl_wallet_enable_ip_restriction')) {
    function kl_wallet_enable_ip_restriction(): bool {
        // Verificar si WordPress está completamente cargado
        if (!function_exists('update_option')) {
            return false;
        }
        
        return update_option('kl_wallet_ip_restriction_enabled', true);
    }
}

/**
 * Deshabilitar restricción de IPs
 * 
 * @return bool
 */
if (!function_exists('kl_wallet_disable_ip_restriction')) {
    function kl_wallet_disable_ip_restriction(): bool {
        // Verificar si WordPress está completamente cargado
        if (!function_exists('update_option')) {
            return false;
        }
        
        return update_option('kl_wallet_ip_restriction_enabled', false);
    }
}

/**
 * Verificar si una IP coincide con un patrón (IP individual o CIDR)
 * 
 * @param string $ip
 * @param string $pattern
 * @return bool
 */
if (!function_exists('kl_wallet_ip_matches')) {
    function kl_wallet_ip_matches(string $ip, string $pattern): bool {
        // Si es una IP individual, comparar directamente
        if (filter_var($pattern, FILTER_VALIDATE_IP)) {
            return $ip === $pattern;
        }
        
        // Si es un patrón CIDR
        if (strpos($pattern, '/') !== false) {
            list($subnet, $mask) = explode('/', $pattern);
            
            if (!filter_var($subnet, FILTER_VALIDATE_IP)) {
                return false;
            }
            
            $mask = intval($mask);
            if ($mask < 0 || $mask > 32) {
                return false;
            }
            
            // Convertir IPs a números enteros
            $ip_long = ip2long($ip);
            $subnet_long = ip2long($subnet);
            
            if ($ip_long === false || $subnet_long === false) {
                return false;
            }
            
            // Calcular máscara de red
            $network_mask = ~((1 << (32 - $mask)) - 1);
            
            // Verificar si la IP está en la misma red
            return ($ip_long & $network_mask) === ($subnet_long & $network_mask);
        }
        
        return false;
    }
}
