<?php
declare(strict_types=1);

/**
 * API de Servicio a Servicio - Wallet
 * 
 * Esta API implementa las mejores prácticas de seguridad para WordPress:
 * - Autenticación por JWT (JSON Web Tokens)
 * - Rate limiting
 * - Logging de auditoría
 * - Sanitización de datos
 * - Endpoint para generar tokens JWT
 * 
 * @package KetchupLovers
 * @version 2.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    http_response_code(403);
    exit('Acceso directo prohibido');
}

// Incluir el manejador de JWT
require_once __DIR__ . '/jwt-handler.php';

/**
 * Clase principal para la API de Wallet
 */
class KL_Wallet_API {
    
    /**
     * Obtener API Key válida para autenticación
     * 
     * @return string
     */
    private function get_valid_api_key(): string {
        // Intentar usar la configuración centralizada
        if (class_exists('KL_Wallet_API_Config')) {
            return KL_Wallet_API_Config::get_api_key();
        }
        
        // Fallback a constantes
        if (defined('KL_WALLET_API_KEY')) {
            return KL_WALLET_API_KEY;
        }
        
        if (defined('JWT_AUTH_SECRET_KEY')) {
            return JWT_AUTH_SECRET_KEY;
        }
        
        // Clave por defecto solo para desarrollo
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return 'kl_wallet_secure_key_2024';
        }
        
        throw new Exception('API Key no configurada');
    }
    
    /**
     * Límite de solicitudes por minuto
     */
    private const RATE_LIMIT = 60;
    
    /**
     * Constructor de la clase
     */
    public function __construct() {
        // Registrar el endpoint de la API
        add_action('rest_api_init', [$this, 'register_api_endpoints']);
        
        // Agregar filtros de seguridad
        add_filter('rest_authentication_errors', [$this, 'authenticate_api_request']);
    }
    
    /**
     * Registrar los endpoints de la API
     */
    public function register_api_endpoints(): void {
        // Endpoint para obtener teléfono del usuario
        register_rest_route('kl-wallet/v1', '/get-user-by-id', [
            'methods' => 'GET',
            'callback' => [$this, 'get_user_phone'],
            'permission_callback' => [$this, 'check_api_permissions'],
            'args' => [
                'user_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => [$this, 'validate_user_id']
                ]
            ]
        ]);
        
        // Endpoint para obtener usuario por teléfono
        register_rest_route('kl-wallet/v1', '/user-by-phone', [
            'methods' => 'GET',
            'callback' => [$this, 'get_user_by_phone'],
            'permission_callback' => [$this, 'check_api_permissions'],
            'args' => [
                'phone' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => [$this, 'validate_phone_number']
                ]
            ]
        ]);
        
        // Endpoint para generar token JWT
        register_rest_route('kl-wallet/v1', '/generate-token', [
            'methods' => 'POST',
            'callback' => [$this, 'generate_jwt_token'],
            'permission_callback' => [$this, 'check_token_generation_permissions'],
            'args' => [
                'service_name' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'expiration' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 3600,
                    'sanitize_callback' => 'absint'
                ]
            ]
        ]);
        
        // Endpoint para gestionar logs (solo administradores)
        register_rest_route('kl-wallet/v1', '/logs', [
            'methods' => 'GET',
            'callback' => [$this, 'get_logs_info'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'action' => [
                    'required' => false,
                    'type' => 'string',
                    'enum' => ['stats', 'cleanup', 'download'],
                    'default' => 'stats'
                ]
            ]
        ]);
    }
    
    /**
     * Autenticar la solicitud de la API
     * 
     * @param WP_Error|null $result Resultado de autenticación
     * @return WP_Error|null
     */
    public function authenticate_api_request($result) {
        // Si ya hay un error, devolverlo
        if ($result !== null) {
            return $result;
        }
        
        // Verificar que sea una solicitud a nuestra API
        if (!$this->is_kl_wallet_request()) {
            return $result;
        }
        
        // Excepción: El endpoint de generar token usa API Key, no JWT
        if ($this->is_token_generation_request()) {
            return $result; // Permitir que el endpoint maneje su propia autenticación
        }
        
        // Verificar restricción de IPs
        $client_ip = $this->get_client_ip();
        if (!$this->check_ip_restriction($client_ip)) {
            return new WP_Error(
                'ip_not_allowed',
                'API Wallet no permitida para esta ocación.',
                ['status' => 403]
            );
        }
        
        // Verificar JWT (método único de autenticación)
        $jwt_token = $this->get_jwt_from_request();
        if (empty($jwt_token)) {
            return new WP_Error(
                'missing_jwt',
                'Token JWT requerido en header Authorization: Bearer',
                ['status' => 401]
            );
        }
        
        $jwt_payload = KL_Wallet_JWT_Handler::verify_token($jwt_token);
        if ($jwt_payload === false) {
            return new WP_Error(
                'invalid_jwt',
                'Token JWT inválido o expirado',
                ['status' => 401]
            );
        }
        
        // Verificar rate limiting
        if (!$this->check_rate_limit()) {
            return new WP_Error(
                'rate_limit_exceeded',
                'Límite de solicitudes excedido',
                ['status' => 429]
            );
        }
        
        return $result;
    }
    
    /**
     * Verificar permisos de la API
     * 
     * @param WP_REST_Request $request Solicitud REST
     * @return bool|WP_Error
     */
    public function check_api_permissions($request) {
        // Para APIs de servicio a servicio, solo verificamos la API Key
        // El nonce no es necesario en este contexto
        return true;
    }
    
    /**
     * Validar ID de usuario
     * 
     * @param int $user_id ID del usuario
     * @return bool
     */
    public function validate_user_id($user_id): bool {
        return $user_id > 0 && get_user_by('ID', $user_id) !== false;
    }
    
    /**
     * Validar número de teléfono (OPTIMIZADO)
     * 
     * @param string $phone Número de teléfono
     * @return bool
     */
    public function validate_phone_number($phone): bool {
        // Validación rápida sin regex para mejor rendimiento
        if (empty($phone) || strlen($phone) < 7 || strlen($phone) > 15) {
            return false;
        }
        
        // Verificar que solo contenga dígitos (más rápido que regex)
        return ctype_digit(preg_replace('/[\s\-\(\)]/', '', $phone));
    }
    
    /**
     * Verificar permisos para generar tokens
     * 
     * @param WP_REST_Request $request Solicitud REST
     * @return bool|WP_Error
     */
    public function check_token_generation_permissions($request) {
        // Solo permitir API Key para generar tokens (endpoint especial)
        $api_key = $this->get_api_key_from_request();
        if (!$this->validate_api_key($api_key)) {
            return new WP_Error(
                'invalid_api_key',
                'API Key inválida para generar tokens JWT',
                ['status' => 401]
            );
        }
        
        return true;
    }
    
    /**
     * Generar token JWT
     * 
     * @param WP_REST_Request $request Solicitud REST
     * @return WP_REST_Response|WP_Error
     */
    public function generate_jwt_token($request) {
        try {
            $service_name = sanitize_text_field($request->get_param('service_name'));
            $expiration = absint($request->get_param('expiration'));
            
            // Validar parámetros
            if (empty($service_name)) {
                return new WP_Error(
                    'invalid_service_name',
                    'Nombre de servicio requerido',
                    ['status' => 400]
                );
            }
            
            if ($expiration <= 0 || $expiration > 86400) { // Máximo 24 horas
                return new WP_Error(
                    'invalid_expiration',
                    'Tiempo de expiración inválido (1-86400 segundos)',
                    ['status' => 400]
                );
            }
            
            // Generar token
            $token = KL_Wallet_JWT_Handler::generate_token([
                'service' => $service_name,
                'type' => 'service-to-service'
            ], $expiration);
            
            // Log de auditoría
            $this->log_api_request([
                'action' => 'generate_jwt_token',
                'service_name' => $service_name,
                'expiration' => $expiration,
                'ip' => $this->get_client_ip(),
                'timestamp' => current_time('mysql')
            ]);
            
            return new WP_REST_Response([
                'success' => true,
                'token' => $token,
                'expires_in' => $expiration,
                'token_type' => 'Bearer',
                'service' => $service_name
            ], 200);
            
        } catch (Exception $e) {
            error_log('KL Wallet API Error: ' . $e->getMessage());
            
            return new WP_Error(
                'token_generation_error',
                'Error generando token',
                ['status' => 500]
            );
        }
    }
    
    /**
     * Obtener service_name del JWT token (OPTIMIZADO)
     * 
     * @return string
     */
    private function get_service_name_from_jwt(): string {
        // Cachear el resultado para evitar múltiples verificaciones
        static $cached_service_name = null;
        
        if ($cached_service_name !== null) {
            return $cached_service_name;
        }
        
        $jwt_token = $this->get_jwt_from_request();
        
        if (!empty($jwt_token)) {
            $jwt_payload = KL_Wallet_JWT_Handler::verify_token($jwt_token);
            if ($jwt_payload && isset($jwt_payload['service'])) {
                $cached_service_name = sanitize_text_field($jwt_payload['service']);
                return $cached_service_name;
            }
        }
        
        $cached_service_name = '';
        return $cached_service_name;
    }

    /**
     * Obtener número de teléfono del usuario
     * 
     * @param WP_REST_Request $request Solicitud REST
     * @return WP_REST_Response|WP_Error
     */
    public function get_user_phone($request) {
        try {
            // Obtener y validar parámetros
            $user_id = absint($request->get_param('user_id'));
            
            // Obtener el service_name del JWT token
            $service_name = $this->get_service_name_from_jwt();
            
            // Obtener el metadato del usuario
            $phone_number = get_user_meta($user_id, 'billing_phone', true);
            
            // Log de auditoría
            $this->log_api_request([
                'action' => 'get_user_phone',
                'user_id' => $user_id,
                'service_name' => $service_name,
                'ip' => $this->get_client_ip(),
                'timestamp' => current_time('mysql')
            ]);
            
            // Si no hay número de teléfono, devolver mensaje por defecto
            if (empty($phone_number)) {
                return new WP_REST_Response([
                    'success' => true,
                    'message' => 'Hola',
                    'phone_number' => null,
                    'user_id' => $user_id
                ], 200);
            }
            
            // Devolver respuesta exitosa
            return new WP_REST_Response([
                'success' => true,
                'message' => 'Hola',
                'phone_number' => $phone_number,
                'user_id' => $user_id
            ], 200);
            
        } catch (Exception $e) {
            // Log del error
            error_log('KL Wallet API Error: ' . $e->getMessage());
            
            return new WP_Error(
                'api_error',
                'Error interno del servidor',
                ['status' => 500]
            );
        }
    }

    /**
     * Obtener usuario por número de teléfono (OPTIMIZADO)
     * 
     * @param WP_REST_Request $request Solicitud REST
     * @return WP_REST_Response|WP_Error
     */
    public function get_user_by_phone($request) {
        $start_time = microtime(true);
        
        try {
            $phone_number = sanitize_text_field($request->get_param('phone'));

            // Validar el número de teléfono
            if (!$this->validate_phone_number($phone_number)) {
                return new WP_Error(
                    'invalid_phone_number',
                    'Número de teléfono inválido',
                    ['status' => 400]
                );
            }

            // OPTIMIZACIÓN 1: Usar caché de transients
            $cache_key = 'kl_wallet_user_phone_' . md5($phone_number);
            $cached_result = get_transient($cache_key);
            
            if ($cached_result !== false) {
                $this->log_performance('get_user_by_phone', $start_time, 'cache_hit');
                return new WP_REST_Response($cached_result, 200);
            }

            // OPTIMIZACIÓN 2: Consulta directa a la base de datos (más rápida que get_users)
            global $wpdb;
            $user_id = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} 
                 WHERE meta_key = 'billing_phone' 
                 AND meta_value = %s 
                 LIMIT 1",
                $phone_number
            ));

            if (!$user_id) {
                $response_data = [
                    'success' => false,
                    'message' => 'Usuario no encontrado con este número de teléfono.',
                    'phone_number' => $phone_number
                ];
                
                // Cachear resultado negativo por menos tiempo
                set_transient($cache_key, $response_data, 300); // 5 minutos
                
                $this->log_performance('get_user_by_phone', $start_time, 'not_found');
                return new WP_REST_Response($response_data, 404);
            }

            // OPTIMIZACIÓN 3: Obtener datos básicos en una sola consulta
            $user_data = $wpdb->get_row($wpdb->prepare(
                "SELECT ID, display_name, user_email 
                 FROM {$wpdb->users} 
                 WHERE ID = %d",
                $user_id
            ));

            if (!$user_data) {
                $response_data = [
                    'success' => false,
                    'message' => 'Usuario no encontrado con este número de teléfono.',
                    'phone_number' => $phone_number
                ];
                
                set_transient($cache_key, $response_data, 300);
                $this->log_performance('get_user_by_phone', $start_time, 'user_not_found');
                return new WP_REST_Response($response_data, 404);
            }

            // OPTIMIZACIÓN 4: Obtener metadatos específicos en una sola consulta
            $metadata = $wpdb->get_results($wpdb->prepare(
                "SELECT meta_key, meta_value 
                 FROM {$wpdb->usermeta} 
                 WHERE user_id = %d 
                 AND meta_key IN ('billing_phone', 'wps_wallet')",
                $user_id
            ));

            // Procesar metadatos
            $billing_phone = '';
            $wps_wallet = '';
            
            foreach ($metadata as $meta) {
                if ($meta->meta_key === 'billing_phone') {
                    $billing_phone = $meta->meta_value;
                } elseif ($meta->meta_key === 'wps_wallet') {
                    $wps_wallet = $meta->meta_value;
                }
            }

            // Obtener el service_name del JWT token
            $service_name = $this->get_service_name_from_jwt();

            // Preparar respuesta
            $response_data = [
                'success' => true,
                'message' => 'Usuario encontrado',
                'user' => [
                    'id' => $user_data->ID,
                    'display_name' => $user_data->display_name,
                    'email' => $user_data->user_email
                ],
                'phone_number' => $phone_number,
                'wps_wallet' => $wps_wallet
            ];

            // OPTIMIZACIÓN 5: Cachear resultado exitoso
            set_transient($cache_key, $response_data, 1800); // 30 minutos

            // OPTIMIZACIÓN 6: Log de auditoría asíncrono (no bloquea la respuesta)
            $this->log_api_request_async([
                'action' => 'get_user_by_phone',
                'user_id' => $user_data->ID,
                'service_name' => $service_name,
                'ip' => $this->get_client_ip(),
                'timestamp' => current_time('mysql')
            ]);

            $this->log_performance('get_user_by_phone', $start_time, 'success');
            return new WP_REST_Response($response_data, 200);

        } catch (Exception $e) {
            // Log del error
            error_log('KL Wallet API Error: ' . $e->getMessage());
            
            $this->log_performance('get_user_by_phone', $start_time, 'error');
            return new WP_Error(
                'api_error',
                'Error interno del servidor',
                ['status' => 500]
            );
        }
    }
    
    /**
     * Verificar si es una solicitud a nuestra API
     * 
     * @return bool
     */
    private function is_kl_wallet_request(): bool {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($request_uri, '/wp-json/kl-wallet/') !== false;
    }
    
    /**
     * Verificar si es una solicitud para generar token
     * 
     * @return bool
     */
    private function is_token_generation_request(): bool {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $request_method = $_SERVER['REQUEST_METHOD'] ?? '';
        
        return strpos($request_uri, '/wp-json/kl-wallet/v1/generate-token') !== false 
            && $request_method === 'POST';
    }
    
    /**
     * Verificar restricción de IPs
     * 
     * @param string $client_ip IP del cliente
     * @return bool
     */
    private function check_ip_restriction(string $client_ip): bool {
        // Si la restricción de IPs está deshabilitada, permitir
        if (!kl_wallet_is_ip_restriction_enabled()) {
            return true;
        }
        
        // Verificar si la IP está permitida
        return kl_wallet_is_ip_allowed($client_ip);
    }
    
    /**
     * Obtener JWT de la solicitud
     * 
     * @return string
     */
    private function get_jwt_from_request(): string {
        // Buscar en Authorization header
        $headers = getallheaders();
        $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return trim($matches[1]);
        }
        
        // Buscar en parámetros GET
        return sanitize_text_field($_GET['jwt'] ?? '');
    }
    
    /**
     * Obtener API Key de la solicitud (solo para generar tokens)
     * 
     * @return string
     */
    private function get_api_key_from_request(): string {
        // Buscar en headers
        $headers = getallheaders();
        $api_key = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? '';
        
        // Si no está en headers, buscar en parámetros GET
        if (empty($api_key)) {
            $api_key = sanitize_text_field($_GET['api_key'] ?? '');
        }
        
        return $api_key;
    }
    
    /**
     * Validar API Key (solo para generar tokens)
     * 
     * @param string $api_key API Key proporcionada
     * @return bool
     */
    private function validate_api_key(string $api_key): bool {
        try {
            $valid_key = $this->get_valid_api_key();
            return hash_equals($valid_key, $api_key);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Verificar rate limiting
     * 
     * @return bool
     */
    private function check_rate_limit(): bool {
        $client_ip = $this->get_client_ip();
        $transient_key = 'kl_wallet_rate_limit_' . md5($client_ip);
        
        // Obtener solicitudes actuales
        $requests = get_transient($transient_key);
        if ($requests === false) {
            $requests = 0;
        }
        
        // Verificar límite
        if ($requests >= self::RATE_LIMIT) {
            return false;
        }
        
        // Incrementar contador
        set_transient($transient_key, $requests + 1, 60); // 1 minuto
        
        return true;
    }
    
    /**
     * Obtener IP del cliente
     * 
     * @return string
     */
    private function get_client_ip(): string {
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
    
    /**
     * Registrar solicitud de la API para auditoría (asíncrono)
     * 
     * @param array $data Datos a registrar
     */
    private function log_api_request_async(array $data): void {
        // Usar wp_schedule_single_event para logging asíncrono
        wp_schedule_single_event(time(), 'kl_wallet_async_log', [$data]);
    }

    /**
     * Registrar solicitud de la API para auditoría
     * 
     * @param array $data Datos a registrar
     */
    private function log_api_request(array $data): void {
        // Crear entrada de log
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'ip' => $data['ip'],
            'action' => $data['action'],
            'user_id' => $data['user_id'] ?? 0,
            'service_name' => $data['service_name'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        
        // Guardar en base de datos (opcional)
        $this->save_api_log($log_entry);
        
        // Escribir en archivo de log con control de tamaño
        $this->write_log_file('kl-wallet-api.log', $log_entry);
    }

    /**
     * Registrar solicitud de la API para auditoría de rendimiento (asíncrono)
     * 
     * @param array $data Datos a registrar
     */
    private function log_performance(string $action, float $start_time, string $status): void {
        $end_time = microtime(true);
        $duration = $end_time - $start_time;

        $log_entry = [
            'timestamp' => current_time('mysql'),
            'ip' => $this->get_client_ip(),
            'action' => $action,
            'status' => $status,
            'duration_ms' => round($duration * 1000, 2)
        ];

        // Guardar en base de datos (opcional)
        $this->save_performance_log($log_entry);

        // Escribir en archivo de log con control de tamaño
        $this->write_log_file('kl-wallet-api-performance.log', $log_entry);
    }

    /**
     * Escribir en archivo de log con control de tamaño y rotación
     * 
     * @param string $filename Nombre del archivo de log
     * @param array $log_entry Entrada del log
     */
    private function write_log_file(string $filename, array $log_entry): void {
        $log_dir = WP_CONTENT_DIR . '/logs';
        $log_file = $log_dir . '/' . $filename;
        
        // Crear directorio si no existe
        if (!is_dir($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        // Verificar tamaño del archivo y rotar si es necesario
        $this->rotate_log_file_if_needed($log_file);
        
        // Formatear mensaje de log
        $log_message = $this->format_log_message($log_entry, $filename);
        
        // Escribir en archivo
        file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
    }

    /**
     * Rotar archivo de log si excede el tamaño máximo
     * 
     * @param string $log_file Ruta del archivo de log
     */
    private function rotate_log_file_if_needed(string $log_file): void {
        // Configuración de rotación
        $max_size_mb = 100; // 10 MB máximo por archivo
        $max_files = 5; // Mantener máximo 5 archivos de backup
        $max_size_bytes = $max_size_mb * 1024 * 1024;
        
        // Verificar si el archivo existe y su tamaño
        if (!file_exists($log_file)) {
            return;
        }
        
        $file_size = filesize($log_file);
        
        if ($file_size < $max_size_bytes) {
            return; // No necesita rotación
        }
        
        // Rotar archivo
        $this->rotate_log_file($log_file, $max_files);
    }

    /**
     * Rotar archivo de log
     * 
     * @param string $log_file Ruta del archivo de log
     * @param int $max_files Número máximo de archivos de backup
     */
    private function rotate_log_file(string $log_file, int $max_files): void {
        $log_dir = dirname($log_file);
        $filename = basename($log_file);
        $name_without_ext = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Eliminar archivo más antiguo si excede el límite
        for ($i = $max_files; $i >= 1; $i--) {
            $old_file = $log_dir . '/' . $name_without_ext . '.' . $i . '.' . $extension;
            if (file_exists($old_file)) {
                if ($i == $max_files) {
                    // Eliminar el archivo más antiguo
                    unlink($old_file);
                } else {
                    // Renombrar archivo
                    $new_file = $log_dir . '/' . $name_without_ext . '.' . ($i + 1) . '.' . $extension;
                    rename($old_file, $new_file);
                }
            }
        }
        
        // Renombrar archivo actual
        $backup_file = $log_dir . '/' . $name_without_ext . '.1.' . $extension;
        rename($log_file, $backup_file);
        
        // Crear nuevo archivo vacío
        touch($log_file);
        
        // Log de rotación
        error_log("KL Wallet API: Log file rotated - $filename");
    }

    /**
     * Formatear mensaje de log según el tipo de archivo
     * 
     * @param array $log_entry Entrada del log
     * @param string $filename Nombre del archivo
     * @return string Mensaje formateado
     */
    private function format_log_message(array $log_entry, string $filename): string {
        switch ($filename) {
            case 'kl-wallet-api.log':
                return sprintf(
                    "[%s] %s - IP: %s - Action: %s - User ID: %d - Service: %s\n",
                    $log_entry['timestamp'],
                    $log_entry['user_agent'] ?? 'Unknown',
                    $log_entry['ip'],
                    $log_entry['action'],
                    $log_entry['user_id'],
                    $log_entry['service_name']
                );
                
            case 'kl-wallet-api-performance.log':
                return sprintf(
                    "[%s] %s - IP: %s - Action: %s - Status: %s - Duration: %fms\n",
                    $log_entry['timestamp'],
                    $log_entry['user_agent'] ?? 'Unknown',
                    $log_entry['ip'],
                    $log_entry['action'],
                    $log_entry['status'],
                    $log_entry['duration_ms']
                );
                
            default:
                return json_encode($log_entry) . "\n";
        }
    }

    /**
     * Limpiar logs antiguos automáticamente
     */
    public function cleanup_old_logs(): void {
        $log_dir = WP_CONTENT_DIR . '/logs';
        
        if (!is_dir($log_dir)) {
            return;
        }
        
        $max_age_days = 30; // Mantener logs por 30 días
        $cutoff_time = time() - ($max_age_days * 24 * 60 * 60);
        
        $files = glob($log_dir . '/kl-wallet-*.log.*');
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
                error_log("KL Wallet API: Deleted old log file - " . basename($file));
            }
        }
    }

    /**
     * Obtener estadísticas de logs
     * 
     * @return array Estadísticas de los archivos de log
     */
    public function get_log_statistics(): array {
        $log_dir = WP_CONTENT_DIR . '/logs';
        $stats = [
            'api_log' => [],
            'performance_log' => [],
            'total_size' => 0,
            'total_files' => 0
        ];
        
        if (!is_dir($log_dir)) {
            return $stats;
        }
        
        // Estadísticas del log principal de API
        $api_log_files = glob($log_dir . '/kl-wallet-api.log*');
        foreach ($api_log_files as $file) {
            $size = filesize($file);
            $stats['api_log'][] = [
                'file' => basename($file),
                'size' => $size,
                'size_mb' => round($size / 1024 / 1024, 2),
                'modified' => date('Y-m-d H:i:s', filemtime($file))
            ];
            $stats['total_size'] += $size;
            $stats['total_files']++;
        }
        
        // Estadísticas del log de rendimiento
        $perf_log_files = glob($log_dir . '/kl-wallet-api-performance.log*');
        foreach ($perf_log_files as $file) {
            $size = filesize($file);
            $stats['performance_log'][] = [
                'file' => basename($file),
                'size' => $size,
                'size_mb' => round($size / 1024 / 1024, 2),
                'modified' => date('Y-m-d H:i:s', filemtime($file))
            ];
            $stats['total_size'] += $size;
            $stats['total_files']++;
        }
        
        $stats['total_size_mb'] = round($stats['total_size'] / 1024 / 1024, 2);
        
        return $stats;
    }
    
    /**
     * Guardar log en base de datos
     * 
     * @param array $log_entry Entrada del log
     */
    private function save_api_log(array $log_entry): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'kl_wallet_api_logs';
        
        // Crear tabla si no existe
        $this->create_log_table();
        
        // Insertar log
        $wpdb->insert(
            $table_name,
            [
                'timestamp' => $log_entry['timestamp'],
                'ip_address' => $log_entry['ip'],
                'action' => $log_entry['action'],
                'user_id' => $log_entry['user_id'],
                'service_name' => $log_entry['service_name'],
                'user_agent' => $log_entry['user_agent']
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );
    }

    /**
     * Guardar log de rendimiento en base de datos (asíncrono)
     * 
     * @param array $log_entry Entrada del log de rendimiento
     */
    private function save_performance_log(array $log_entry): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'kl_wallet_api_performance_logs';

        // Crear tabla si no existe
        $this->create_performance_log_table();

        // Insertar log
        $wpdb->insert(
            $table_name,
            [
                'timestamp' => $log_entry['timestamp'],
                'ip_address' => $log_entry['ip'],
                'action' => $log_entry['action'],
                'status' => $log_entry['status'],
                'duration_ms' => $log_entry['duration_ms']
            ],
            ['%s', '%s', '%s', '%s', '%f']
        );
    }
    
    /**
     * Crear tabla de logs si no existe
     */
    private function create_log_table(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'kl_wallet_api_logs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45) NOT NULL,
            action varchar(100) NOT NULL,
            user_id bigint(20) NOT NULL,
            service_name varchar(100) DEFAULT '',
            user_agent text,
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY ip_address (ip_address),
            KEY action (action),
            KEY service_name (service_name)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Verificar y agregar columna service_name si no existe
        $this->ensure_service_name_column_exists($table_name);
    }

    /**
     * Crear tabla de logs de rendimiento si no existe
     */
    private function create_performance_log_table(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'kl_wallet_api_performance_logs';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45) NOT NULL,
            action varchar(100) NOT NULL,
            status varchar(50) NOT NULL,
            duration_ms float NOT NULL,
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY ip_address (ip_address),
            KEY action (action),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Asegurar que la columna service_name existe
     * 
     * @param string $table_name Nombre de la tabla
     */
    private function ensure_service_name_column_exists(string $table_name): void {
        global $wpdb;
        
        // Verificar si la columna existe
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'service_name'",
                DB_NAME,
                $table_name
            )
        );
        
        // Si la columna no existe, agregarla
        if (empty($column_exists)) {
            $wpdb->query(
                "ALTER TABLE $table_name 
                 ADD COLUMN service_name varchar(100) DEFAULT '' AFTER user_id,
                 ADD KEY service_name (service_name)"
            );
            
            // Log de la actualización
            error_log("KL Wallet API: Columna service_name agregada a la tabla $table_name");
        }
    }

    /**
     * Verificar permisos de administrador
     * 
     * @param WP_REST_Request $request Solicitud REST
     * @return bool
     */
    public function check_admin_permissions($request): bool {
        return current_user_can('manage_options');
    }

    /**
     * Obtener información de logs
     * 
     * @param WP_REST_Request $request Solicitud REST
     * @return WP_REST_Response|WP_Error
     */
    public function get_logs_info($request) {
        try {
            $action = $request->get_param('action');
            
            switch ($action) {
                case 'stats':
                    $stats = $this->get_log_statistics();
                    return new WP_REST_Response([
                        'success' => true,
                        'data' => $stats
                    ], 200);
                    
                case 'cleanup':
                    $this->cleanup_old_logs();
                    return new WP_REST_Response([
                        'success' => true,
                        'message' => 'Logs antiguos eliminados correctamente'
                    ], 200);
                    
                case 'download':
                    return $this->download_log_file($request);
                    
                default:
                    return new WP_Error(
                        'invalid_action',
                        'Acción no válida',
                        ['status' => 400]
                    );
            }
            
        } catch (Exception $e) {
            error_log('KL Wallet API Error: ' . $e->getMessage());
            
            return new WP_Error(
                'api_error',
                'Error interno del servidor',
                ['status' => 500]
            );
        }
    }

    /**
     * Descargar archivo de log
     * 
     * @param WP_REST_Request $request Solicitud REST
     * @return WP_REST_Response|WP_Error
     */
    private function download_log_file($request) {
        $log_type = $request->get_param('log_type') ?? 'api';
        
        $log_files = [
            'api' => 'kl-wallet-api.log',
            'performance' => 'kl-wallet-api-performance.log'
        ];
        
        if (!isset($log_files[$log_type])) {
            return new WP_Error(
                'invalid_log_type',
                'Tipo de log no válido',
                ['status' => 400]
            );
        }
        
        $log_file = WP_CONTENT_DIR . '/logs/' . $log_files[$log_type];
        
        if (!file_exists($log_file)) {
            return new WP_Error(
                'log_not_found',
                'Archivo de log no encontrado',
                ['status' => 404]
            );
        }
        
        // Leer contenido del archivo
        $content = file_get_contents($log_file);
        
        if ($content === false) {
            return new WP_Error(
                'read_error',
                'Error al leer el archivo de log',
                ['status' => 500]
            );
        }
        
        // Crear respuesta con headers para descarga
        $response = new WP_REST_Response($content, 200);
        $response->header('Content-Type', 'text/plain');
        $response->header('Content-Disposition', 'attachment; filename="' . $log_files[$log_type] . '"');
        $response->header('Content-Length', strlen($content));
        
        return $response;
    }
}

// Inicializar la API cuando WordPress esté listo
add_action('init', function() {
    new KL_Wallet_API();
});

// Hook para logging asíncrono
add_action('kl_wallet_async_log', function($data) {
    $api = new KL_Wallet_API();
    $api->log_api_request($data);
});

// Cron job para limpieza automática de logs (diario)
add_action('kl_wallet_cleanup_logs_cron', function() {
    $api = new KL_Wallet_API();
    $api->cleanup_old_logs();
});

// Programar limpieza automática si no está programada
if (!wp_next_scheduled('kl_wallet_cleanup_logs_cron')) {
    wp_schedule_event(time(), 'daily', 'kl_wallet_cleanup_logs_cron');
}

// Limpiar logs al desactivar el plugin/tema
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('kl_wallet_cleanup_logs_cron');
});

/**
 * Función helper para generar nonce para la API
 * 
 * @return string
 */
function kl_wallet_generate_api_nonce(): string {
    return wp_create_nonce('kl_wallet_api_nonce');
}

/**
 * Función helper para verificar si la API está disponible
 * 
 * @return bool
 */
function kl_wallet_api_is_available(): bool {
    return class_exists('KL_Wallet_API');
}

/**
 * Función helper para generar token JWT desde PHP
 * 
 * @param string $service_name Nombre del servicio
 * @param int $expiration Tiempo de expiración en segundos
 * @return string
 */
function kl_wallet_generate_jwt_token(string $service_name, int $expiration = 3600): string {
    if (!class_exists('KL_Wallet_JWT_Handler')) {
        require_once __DIR__ . '/jwt-handler.php';
    }
    
    return KL_Wallet_JWT_Handler::generate_token([
        'service' => $service_name,
        'type' => 'service-to-service'
    ], $expiration);
}

/**
 * Función helper para verificar token JWT
 * 
 * @param string $token Token JWT
 * @return array|false
 */
function kl_wallet_verify_jwt_token(string $token) {
    if (!class_exists('KL_Wallet_JWT_Handler')) {
        require_once __DIR__ . '/jwt-handler.php';
    }
    
    return KL_Wallet_JWT_Handler::verify_token($token);
}

/**
 * Función para actualizar la tabla de logs (ejecutar una vez)
 * 
 * @return bool
 */
function kl_wallet_update_logs_table(): bool {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'kl_wallet_api_logs';
    
    // Verificar si la tabla existe
    $table_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )
    );
    
    if (!$table_exists) {
        // Si la tabla no existe, crearla
        $api = new KL_Wallet_API();
        $api->create_log_table();
        return true;
    }
    
    // Verificar si la columna service_name existe
    $column_exists = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'service_name'",
            DB_NAME,
            $table_name
        )
    );
    
    if (empty($column_exists)) {
        // Agregar la columna service_name
        $result = $wpdb->query(
            "ALTER TABLE $table_name 
             ADD COLUMN service_name varchar(100) DEFAULT '' AFTER user_id,
             ADD KEY service_name (service_name)"
        );
        
        if ($result !== false) {
            error_log("KL Wallet API: Columna service_name agregada exitosamente a $table_name");
            return true;
        } else {
            error_log("KL Wallet API: Error al agregar columna service_name a $table_name");
            return false;
        }
    }
    
    return true; // La columna ya existe
}
