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

// Incluir el panel de administración de IPs bloqueadas
require_once __DIR__ . '/ip-block-admin.php';

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
     * Obtener límite de respuestas 403 antes de bloquear IP
     * 
     * @return int
     */
    private function get_max_403_responses(): int {
        $value = get_option('kl_wallet_max_403_responses', 100);
        return intval($value);
    }
    
    /**
     * Obtener tiempo de bloqueo de IP en segundos
     * 
     * @return int
     */
    private function get_ip_block_duration(): int {
        $hours = get_option('kl_wallet_block_duration', 24);
        return intval($hours) * 3600; // Convertir horas a segundos
    }
    
    /**
     * Tabla para almacenar IPs bloqueadas
     */
    private const BLOCKED_IPS_TABLE = 'kl_wallet_blocked_ips';
    
    /**
     * Tabla para contar respuestas 403 por IP
     */
    private const IP_403_COUNTS_TABLE = 'kl_wallet_ip_403_counts';
    
    /**
     * Tabla para registro completo de todas las respuestas 403
     */
    private const IP_403_LOGS_TABLE = 'kl_wallet_ip_403_logs';
    
    /**
     * Constructor de la clase
     */
    public function __construct() {
        // Inicializar tablas de base de datos para bloqueo de IPs inmediatamente
        $this->init_ip_blocking_tables();
        
        // Registrar el endpoint de la API
        add_action('rest_api_init', [$this, 'register_api_endpoints']);
        
        // Agregar filtros de seguridad
        add_filter('rest_authentication_errors', [$this, 'authenticate_api_request']);
        
        // Hook para capturar respuestas 403 y registrar para bloqueo de IPs
        add_action('rest_api_init', [$this, 'setup_403_response_hook']);
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

        // Endpoint para actualizar monedero del usuario (proxy a API externa)
        register_rest_route('kl-wallet/v1', '/update-wallet', [
            'methods' => 'POST',
            'callback' => [$this, 'update_user_wallet'],
            'permission_callback' => [$this, 'check_api_permissions'],
            'args' => [
                'user_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => [$this, 'validate_user_id']
                ],
                'amount' => [
                    'required' => true,
                    'type' => 'number',
                    'validate_callback' => [$this, 'validate_amount']
                ],
                'action' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['credit', 'debit'],
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'transaction_detail' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ],
                'payment_method' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default' => 'kl_wallet_api'
                ],
                'note' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ],
                'order_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
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
            // Registrar respuesta 403 y verificar si debe bloquearse la IP
            $this->register_403_response($client_ip, 'IP no permitida', 'kl-wallet-api');
            
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
     * Validar cantidad para transacciones de monedero
     * 
     * @param mixed $amount Cantidad a validar (puede ser string o número)
     * @return bool
     */
    public function validate_amount($amount): bool {
        // Convertir a string para manejo consistente
        $amount_str = (string) $amount;
        
        // Verificar que sea un número válido y positivo
        if (!is_numeric($amount_str) || floatval($amount_str) <= 0) {
            return false;
        }
        
        // Verificar que no exceda un límite razonable (ej: 1 millón)
        if (floatval($amount_str) > 1000000) {
            return false;
        }
        
        // Verificar que tenga máximo 2 decimales
        $decimal_part = strrchr($amount_str, ".");
        if ($decimal_part !== false) {
            $decimal_places = strlen(substr($decimal_part, 1));
            if ($decimal_places > 2) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Verificar permisos para generar tokens
     * 
     * @param WP_REST_Request $request Solicitud REST
     * @return bool|WP_Error
     */
    public function check_token_generation_permissions($request) {
        // Verificar restricción de IPs primero
        $client_ip = $this->get_client_ip();
        if (!$this->check_ip_restriction($client_ip)) {
            // Registrar respuesta 403 y verificar si debe bloquearse la IP
            $this->register_403_response($client_ip, 'IP no permitida para generar tokens', 'generate-token');
            
            return new WP_Error(
                'ip_not_allowed',
                'API Wallet no permitida para esta ocación.',
                ['status' => 403]
            );
        }
        
        // Solo permitir API Key para generar tokens (endpoint especial)
        $api_key = $this->get_api_key_from_request();
        if (!$this->validate_api_key($api_key)) {
            // Registrar respuesta 401 como 403 para el sistema de bloqueo
            $this->register_403_response($client_ip, 'API Key inválida para generar tokens', 'generate-token');
            
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
            
            if ($expiration <= 0 || $expiration > 34560000) { // Máximo 24 horas
                return new WP_Error(
                    'invalid_expiration',
                    'Tiempo de expiración inválido (1-34560000 segundos) 400 días',
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
     * Actualizar monedero del usuario (proxy a API externa)
     * 
     * @param WP_REST_Request $request Solicitud REST
     * @return WP_REST_Response|WP_Error
     */
    public function update_user_wallet($request) {
        $start_time = microtime(true);
        
        try {
            // Obtener parámetros de la solicitud
            $user_id = absint($request->get_param('user_id'));
            $amount = floatval($request->get_param('amount'));
            $action = sanitize_text_field($request->get_param('action'));
            $transaction_detail = sanitize_textarea_field($request->get_param('transaction_detail'));
            $payment_method = sanitize_text_field($request->get_param('payment_method'));
            $note = sanitize_textarea_field($request->get_param('note'));
            $order_id = absint($request->get_param('order_id'));
            
            // Obtener credenciales de la API externa
            $consumer_key = $this->get_external_api_consumer_key();
            $consumer_secret = $this->get_external_api_consumer_secret();
            
            if (empty($consumer_key) || empty($consumer_secret)) {
                return new WP_Error(
                    'external_api_not_configured',
                    'API externa no configurada',
                    ['status' => 500]
                );
            }
            
            // Construir URL de la API externa
            $external_api_url = $this->get_external_api_url($user_id);
            
            // Preparar datos para la API externa
            $external_data = [
                'amount' => $amount,
                'action' => $action,
                'consumer_key' => $consumer_key,
                'consumer_secret' => $consumer_secret,
                'transaction_detail' => $transaction_detail,
                'payment_method' => $payment_method,
                'note' => $note,
                'order_id' => $order_id
            ];
            
            // Realizar solicitud a la API externa
            $response = $this->make_external_api_request($external_api_url, $external_data);
            
            if (is_wp_error($response)) {
                // Log del error
                $this->log_api_request_async([
                    'action' => 'update_wallet_external_error',
                    'user_id' => $user_id,
                    'amount' => $amount,
                    'action_type' => $action,
                    'error' => $response->get_error_message(),
                    'service_name' => $this->get_service_name_from_jwt(),
                    'ip' => $this->get_client_ip(),
                    'timestamp' => current_time('mysql')
                ]);
                
                $this->log_performance('update_user_wallet', $start_time, 'error');
                return $response;
            }
            
            // Procesar respuesta exitosa
            $response_body = wp_remote_retrieve_body($response);
            $response_data = json_decode($response_body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new WP_Error(
                    'invalid_response_format',
                    'Formato de respuesta inválido de la API externa',
                    ['status' => 500]
                );
            }
            
            // Log de auditoría exitoso
            $this->log_api_request_async([
                'action' => 'update_wallet_success',
                'user_id' => $user_id,
                'amount' => $amount,
                'action_type' => $action,
                'transaction_id' => $response_data['transaction_id'] ?? null,
                'new_balance' => $response_data['balance'] ?? null,
                'service_name' => $this->get_service_name_from_jwt(),
                'ip' => $this->get_client_ip(),
                'timestamp' => current_time('mysql')
            ]);
            
            $this->log_performance('update_user_wallet', $start_time, 'success');
            
            // Devolver respuesta formateada
            return new WP_REST_Response([
                'success' => true,
                'message' => 'Monedero actualizado exitosamente',
                'data' => $response_data,
                'user_id' => $user_id,
                'amount' => $amount,
                'action' => $action
            ], 200);
            
        } catch (Exception $e) {
            // Log del error
            error_log('KL Wallet API Error: ' . $e->getMessage());
            
            $this->log_performance('update_user_wallet', $start_time, 'error');
            return new WP_Error(
                'api_error',
                'Error interno del servidor',
                ['status' => 500]
            );
        }
    }

    /**
     * Obtener Consumer Key de la API externa
     * 
     * @return string
     */
    private function get_external_api_consumer_key(): string {
        // Prioridad 1: Constante definida
        if (defined('KL_WALLET_EXTERNAL_CONSUMER_KEY')) {
            return KL_WALLET_EXTERNAL_CONSUMER_KEY;
        }
        
        // Prioridad 2: Opción de WordPress
        $consumer_key = get_option('kl_wallet_external_consumer_key');
        if (!empty($consumer_key)) {
            return $consumer_key;
        }
        
        return '';
    }

    /**
     * Obtener Consumer Secret de la API externa
     * 
     * @return string
     */
    private function get_external_api_consumer_secret(): string {
        // Prioridad 1: Constante definida
        if (defined('KL_WALLET_EXTERNAL_CONSUMER_SECRET')) {
            return KL_WALLET_EXTERNAL_CONSUMER_SECRET;
        }
        
        // Prioridad 2: Opción de WordPress
        $consumer_secret = get_option('kl_wallet_external_consumer_secret');
        if (!empty($consumer_secret)) {
            return $consumer_secret;
        }
        
        return '';
    }

    /**
     * Obtener URL base de la API externa
     * 
     * @return string
     */
    private function get_external_api_base_url(): string {
        // Prioridad 1: Constante definida
        if (defined('KL_WALLET_EXTERNAL_API_URL')) {
            return KL_WALLET_EXTERNAL_API_URL;
        }
        
        // Prioridad 2: Opción de WordPress
        $base_url = get_option('kl_wallet_external_api_url');
        if (!empty($base_url)) {
            return $base_url;
        }
        
        // Prioridad 3: URL por defecto
        return 'https://kernslovers.com/wp-json/wsfw-route/v1/wallet';
    }

    /**
     * Construir URL completa para la API externa
     * 
     * @param int $user_id ID del usuario
     * @return string
     */
    private function get_external_api_url(int $user_id): string {
        $base_url = $this->get_external_api_base_url();
        return rtrim($base_url, '/') . '/' . $user_id;
    }

    /**
     * Realizar solicitud a la API externa
     * 
     * @param string $url URL de la API externa
     * @param array $data Datos a enviar
     * @return WP_Error|array
     */
    private function make_external_api_request(string $url, array $data) {
        $args = [
            'method' => 'PUT',
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Wallet-api-whatsapp/2.0.0'
            ],
            'body' => json_encode($data),
            'data_format' => 'body'
        ];
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_response_message($response);
            $response_body = wp_remote_retrieve_body($response);
            
            return new WP_Error(
                'external_api_error',
                sprintf('Error en API externa: %s (Código: %d)', $error_message, $response_code),
                ['status' => $response_code, 'body' => $response_body]
            );
        }
        
        return $response;
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
     * Verificar si una solicitud REST es al endpoint de generar tokens
     * 
     * @param WP_REST_Request $request Solicitud REST
     * @return bool
     */
    private function is_token_generation_rest_request($request): bool {
        if (!$request instanceof WP_REST_Request) {
            return false;
        }
        
        $route = $request->get_route();
        $method = $request->get_method();
        
        return $route === '/kl-wallet/v1/generate-token' && $method === 'POST';
    }

    /**
     * Obtener endpoint desde una solicitud REST
     * 
     * @param WP_REST_Request $request Solicitud REST
     * @return string
     */
    private function get_endpoint_from_request($request): string {
        if (!$request instanceof WP_REST_Request) {
            return 'unknown';
        }
        
        $route = $request->get_route();
        
        if (strpos($route, '/kl-wallet/v1/generate-token') !== false) {
            return 'generate-token';
        } elseif (strpos($route, '/kl-wallet/v1/') !== false) {
            return 'kl-wallet-api';
        } else {
            return 'unknown';
        }
    }

    /**
     * Obtener endpoint desde la URI de la solicitud
     * 
     * @return string
     */
    private function get_endpoint_from_request_uri(): string {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        if (strpos($request_uri, '/wp-json/kl-wallet/v1/generate-token') !== false) {
            return 'generate-token';
        } elseif (strpos($request_uri, '/wp-json/kl-wallet/v1/') !== false) {
            return 'kl-wallet-api';
        } else {
            return 'unknown';
        }
    }
    
    /**
     * Verificar restricción de IPs
     * 
     * @param string $client_ip IP del cliente
     * @return bool
     */
    private function check_ip_restriction(string $client_ip): bool {
        // Verificar si la IP está bloqueada por múltiples respuestas 403
        if ($this->is_ip_blocked($client_ip)) {
            return false;
        }
        
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
        // Solo guardar en base de datos, no en archivo
        $this->save_api_log($data);
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
        
        // Solo guardar en base de datos, no en archivo
        $this->save_api_log($log_entry);
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

        // Solo guardar en base de datos
        $this->save_performance_log($log_entry);
    }

    /**
     * Rotar archivo de log si excede el tamaño máximo
     * 
     * @param string $log_file Ruta del archivo de log
     */


    /**
     * Limpiar logs antiguos automáticamente
     * 
     * Nota: Los logs ahora se manejan únicamente en base de datos
     * Esta función se mantiene por compatibilidad pero no realiza operaciones
     */
    public function cleanup_old_logs(): void {
        // Los logs se manejan únicamente en base de datos
        // No es necesario limpiar archivos de log
        error_log("KL Wallet API: Log cleanup called - logs are managed in database only");
    }

    /**
     * Obtener estadísticas de logs
     * 
     * @return array Estadísticas de los logs en base de datos
     */
    public function get_log_statistics(): array {
        global $wpdb;
        
        $stats = [
            'database_logs' => [],
            'total_records' => 0,
            'total_size_mb' => 0
        ];
        
        // Estadísticas de logs de auditoría
        $audit_table = $wpdb->prefix . 'kl_wallet_api_logs';
        $audit_count = $wpdb->get_var("SELECT COUNT(*) FROM $audit_table");
        $audit_size = $wpdb->get_var("SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '$audit_table'");
        
        // Estadísticas de logs de rendimiento
        $perf_table = $wpdb->prefix . 'kl_wallet_api_performance_logs';
        $perf_count = $wpdb->get_var("SELECT COUNT(*) FROM $perf_table");
        $perf_size = $wpdb->get_var("SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '$perf_table'");
        
        $stats['database_logs'] = [
            'audit_logs' => [
                'table' => $audit_table,
                'records' => intval($audit_count ?: 0),
                'size_mb' => floatval($audit_size ?: 0)
            ],
            'performance_logs' => [
                'table' => $perf_table,
                'records' => intval($perf_count ?: 0),
                'size_mb' => floatval($perf_size ?: 0)
            ]
        ];
        
        $stats['total_records'] = intval($audit_count ?: 0) + intval($perf_count ?: 0);
        $stats['total_size_mb'] = floatval($audit_size ?: 0) + floatval($perf_size ?: 0);
        
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
        global $wpdb;
        
        $log_type = $request->get_param('log_type') ?? 'audit';
        
        if (!in_array($log_type, ['audit', 'performance'])) {
            return new WP_Error(
                'invalid_log_type',
                'Tipo de log no válido. Use "audit" o "performance"',
                ['status' => 400]
            );
        }
        
        $table_name = $log_type === 'audit' 
            ? $wpdb->prefix . 'kl_wallet_api_logs'
            : $wpdb->prefix . 'kl_wallet_api_performance_logs';
        
        // Obtener logs de la base de datos
        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 1000", ARRAY_A);
        
        if (empty($logs)) {
            return new WP_Error(
                'no_logs_found',
                'No se encontraron logs en la base de datos',
                ['status' => 404]
            );
        }
        
        // Formatear logs como CSV
        $csv_content = '';
        
        // Headers CSV
        if (!empty($logs)) {
            $csv_content .= implode(',', array_keys($logs[0])) . "\n";
        }
        
        // Datos CSV
        foreach ($logs as $log) {
            $csv_content .= implode(',', array_map(function($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            }, $log)) . "\n";
        }
        
        // Crear respuesta con headers para descarga
        $filename = "kl-wallet-api-{$log_type}-logs-" . date('Y-m-d') . ".csv";
        $response = new WP_REST_Response($csv_content, 200);
        $response->header('Content-Type', 'text/csv');
        $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->header('Content-Length', strlen($csv_content));
        
        return $response;
    }

    /**
     * Inicializar tablas de base de datos para el sistema de bloqueo de IPs
     */
    public function init_ip_blocking_tables(): void {
        $this->create_blocked_ips_table();
        $this->create_ip_403_counts_table();
        $this->create_ip_403_logs_table();
    }

    /**
     * Configurar hook para capturar respuestas 403
     */
    public function setup_403_response_hook(): void {
        // Hook para capturar respuestas 403 de la API REST
        add_filter('rest_pre_dispatch', [$this, 'capture_403_responses'], 10, 3);
        
        // Hook para capturar errores de autenticación
        add_action('rest_authentication_errors', [$this, 'capture_auth_errors'], 10, 1);
        
        // Hook específico para capturar respuestas 403 del endpoint de generar tokens
        add_action('rest_pre_dispatch', [$this, 'capture_token_generation_errors'], 5, 3);
    }

    /**
     * Capturar respuestas 403 antes de que se envíen
     * 
     * @param mixed $result Resultado de la validación
     * @param WP_REST_Server $server Servidor REST
     * @param WP_REST_Request $request Solicitud REST
     * @return mixed
     */
    public function capture_403_responses($result, $server, $request) {
        // Si ya hay un error y es 403, registrarlo
        if (is_wp_error($result) && $result->get_error_data('status') === 403) {
            $client_ip = $this->get_client_ip();
            $error_message = $result->get_error_message();
            $endpoint = $this->get_endpoint_from_request($request);
            $this->register_403_response($client_ip, $error_message, $endpoint);
        }
        
        return $result;
    }

    /**
     * Capturar errores de autenticación
     * 
     * @param WP_Error|null $error Error de autenticación
     * @return WP_Error|null
     */
    public function capture_auth_errors($error) {
        if (is_wp_error($error) && $error->get_error_data('status') === 403) {
            $client_ip = $this->get_client_ip();
            $error_message = $error->get_error_message();
            $endpoint = $this->get_endpoint_from_request_uri();
            $this->register_403_response($client_ip, $error_message, $endpoint);
        }
        
        return $error;
    }

    /**
     * Capturar errores específicos del endpoint de generar tokens
     * 
     * @param mixed $result Resultado de la validación
     * @param WP_REST_Server $server Servidor REST
     * @param WP_REST_Request $request Solicitud REST
     * @return mixed
     */
    public function capture_token_generation_errors($result, $server, $request) {
        // Solo procesar si es el endpoint de generar tokens
        if ($this->is_token_generation_rest_request($request)) {
            $client_ip = $this->get_client_ip();
            
            // Capturar errores 403 (IP no permitida)
            if (is_wp_error($result) && $result->get_error_data('status') === 403) {
                $error_message = $result->get_error_message();
                $this->register_403_response($client_ip, $error_message, 'generate-token');
            }
            
            // Capturar errores 401 (API Key inválida) y tratarlos como 403 para el sistema de bloqueo
            if (is_wp_error($result) && $result->get_error_data('status') === 401) {
                $error_message = $result->get_error_message();
                $this->register_403_response($client_ip, $error_message, 'generate-token');
            }
        }
        
        return $result;
    }

    /**
     * Crear tabla para almacenar IPs bloqueadas
     */
    private function create_blocked_ips_table(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::BLOCKED_IPS_TABLE;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            ip_address varchar(45) NOT NULL,
            blocked_at datetime NOT NULL,
            reason text,
            PRIMARY KEY (ip_address)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Crear tabla para contar respuestas 403 por IP
     */
    private function create_ip_403_counts_table(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::IP_403_COUNTS_TABLE;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            ip_address varchar(45) NOT NULL,
            count int(11) NOT NULL DEFAULT 0,
            last_attempt datetime NOT NULL,
            PRIMARY KEY (ip_address)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Crear tabla para registro completo de respuestas 403
     */
    private function create_ip_403_logs_table(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::IP_403_LOGS_TABLE;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            request_uri text,
            request_method varchar(10),
            reason text,
            endpoint varchar(255),
            headers text,
            timestamp datetime NOT NULL,
            PRIMARY KEY (id),
            KEY ip_address (ip_address),
            KEY timestamp (timestamp),
            KEY endpoint (endpoint)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Incrementar contador de respuestas 403 para una IP
     * 
     * @param string $ip_address IP del cliente
     * @return int Nuevo contador
     */
    private function increment_403_count(string $ip_address): int {
        global $wpdb;

        $table_name = $wpdb->prefix . self::IP_403_COUNTS_TABLE;
        $current_time = current_time('mysql');

        // Intentar actualizar el contador existente
        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO $table_name (ip_address, count, last_attempt) 
             VALUES (%s, 1, %s) 
             ON DUPLICATE KEY UPDATE 
             count = count + 1, 
             last_attempt = %s",
            $ip_address,
            $current_time,
            $current_time
        ));

        if ($result === false) {
            // Si falla, intentar obtener el contador actual
            $current_count = $wpdb->get_var($wpdb->prepare(
                "SELECT count FROM $table_name WHERE ip_address = %s",
                $ip_address
            ));

            if ($current_count === null) {
                // Insertar nuevo registro
                $wpdb->insert(
                    $table_name,
                    [
                        'ip_address' => $ip_address,
                        'count' => 1,
                        'last_attempt' => $current_time
                    ],
                    ['%s', '%d', '%s']
                );
                return 1;
            } else {
                // Actualizar contador existente
                $new_count = intval($current_count) + 1;
                $wpdb->update(
                    $table_name,
                    [
                        'count' => $new_count,
                        'last_attempt' => $current_time
                    ],
                    ['ip_address' => $ip_address],
                    ['%d', '%s'],
                    ['%s']
                );
                return $new_count;
            }
        }

        // Obtener el contador actualizado
        $new_count = $wpdb->get_var($wpdb->prepare(
            "SELECT count FROM $table_name WHERE ip_address = %s",
            $ip_address
        ));

        return intval($new_count);
    }

    /**
     * Registrar respuesta 403 individual en el log completo
     * 
     * @param string $ip_address IP del cliente
     * @param string $reason Razón de la respuesta 403
     * @param string $endpoint Endpoint específico (opcional)
     * @return bool
     */
    private function log_403_response(string $ip_address, string $reason, string $endpoint = ''): bool {
        global $wpdb;

        $table_name = $wpdb->prefix . self::IP_403_LOGS_TABLE;
        $current_time = current_time('mysql');

        // Obtener información de la solicitud
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $request_method = $_SERVER['REQUEST_METHOD'] ?? '';
        
        // Obtener headers relevantes (sin información sensible)
        $headers = [];
        $relevant_headers = ['Accept', 'Accept-Language', 'Accept-Encoding', 'Connection', 'Upgrade-Insecure-Requests'];
        
        foreach ($relevant_headers as $header) {
            $header_value = $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $header))] ?? '';
            if (!empty($header_value)) {
                $headers[$header] = $header_value;
            }
        }

        // Determinar endpoint si no se proporciona
        if (empty($endpoint)) {
            if (strpos($request_uri, '/wp-json/kl-wallet/v1/generate-token') !== false) {
                $endpoint = 'generate-token';
            } elseif (strpos($request_uri, '/wp-json/kl-wallet/v1/') !== false) {
                $endpoint = 'kl-wallet-api';
            } else {
                $endpoint = 'unknown';
            }
        }

        // Insertar registro
        $result = $wpdb->insert(
            $table_name,
            [
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'request_uri' => $request_uri,
                'request_method' => $request_method,
                'reason' => $reason,
                'endpoint' => $endpoint,
                'headers' => json_encode($headers),
                'timestamp' => $current_time
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return $result !== false;
    }

    /**
     * Bloquear una IP por exceder el límite de respuestas 403
     * 
     * @param string $ip_address IP a bloquear
     * @param string $reason Razón del bloqueo
     * @return bool
     */
    private function block_ip(string $ip_address, string $reason = ''): bool {
        global $wpdb;

        $table_name = $wpdb->prefix . self::BLOCKED_IPS_TABLE;
        $current_time = current_time('mysql');

        // Insertar o actualizar el bloqueo
        $result = $wpdb->replace(
            $table_name,
            [
                'ip_address' => $ip_address,
                'blocked_at' => $current_time,
                'reason' => $reason
            ],
            ['%s', '%s', '%s']
        );

        // Limpiar contador de 403 para esta IP
        $this->clear_403_count($ip_address);

        // Log del bloqueo
        error_log("IP bloqueada: $ip_address - Razón: $reason");

        // Hook para que otros plugins puedan reaccionar al bloqueo
        do_action('kl_wallet_ip_blocked', $ip_address, $reason, $this->get_403_count($ip_address));

        return $result !== false;
    }

    /**
     * Verificar si una IP está bloqueada
     * 
     * @param string $ip_address IP a verificar
     * @return bool
     */
    private function is_ip_blocked(string $ip_address): bool {
        global $wpdb;

        $table_name = $wpdb->prefix . self::BLOCKED_IPS_TABLE;

        $blocked_ip = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE ip_address = %s",
            $ip_address
        ));

        if (!$blocked_ip) {
            return false;
        }

        // Verificar si el bloqueo ha expirado
        $blocked_time = strtotime($blocked_ip->blocked_at);
        $current_time = current_time('timestamp');

        if (($current_time - $blocked_time) > $this->get_ip_block_duration()) {
            // El bloqueo ha expirado, eliminarlo
            $this->unblock_ip($ip_address);
            return false;
        }

        return true;
    }

    /**
     * Desbloquear una IP
     * 
     * @param string $ip_address IP a desbloquear
     * @return bool
     */
    private function unblock_ip(string $ip_address): bool {
        global $wpdb;

        $table_name = $wpdb->prefix . self::BLOCKED_IPS_TABLE;

        $result = $wpdb->delete(
            $table_name,
            ['ip_address' => $ip_address],
            ['%s']
        );

        if ($result !== false) {
            // Hook para que otros plugins puedan reaccionar al desbloqueo
            do_action('kl_wallet_ip_unblocked', $ip_address);
        }

        return $result !== false;
    }

    /**
     * Limpiar contador de respuestas 403 para una IP
     * 
     * @param string $ip_address IP a limpiar
     * @return bool
     */
    private function clear_403_count(string $ip_address): bool {
        global $wpdb;

        $table_name = $wpdb->prefix . self::IP_403_COUNTS_TABLE;

        $result = $wpdb->delete(
            $table_name,
            ['ip_address' => $ip_address],
            ['%s']
        );

        return $result !== false;
    }

    /**
     * Obtener contador de respuestas 403 para una IP
     * 
     * @param string $ip_address IP a verificar
     * @return int
     */
    private function get_403_count(string $ip_address): int {
        global $wpdb;

        $table_name = $wpdb->prefix . self::IP_403_COUNTS_TABLE;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT count FROM $table_name WHERE ip_address = %s",
            $ip_address
        ));

        return intval($count);
    }

    /**
     * Registrar respuesta 403 y verificar si debe bloquearse la IP
     * 
     * @param string $ip_address IP del cliente
     * @param string $reason Razón de la respuesta 403
     * @param string $endpoint Endpoint específico (opcional)
     * @return bool True si la IP fue bloqueada
     */
    private function register_403_response(string $ip_address, string $reason = '', string $endpoint = ''): bool {
        // Registrar en el log completo
        $this->log_403_response($ip_address, $reason, $endpoint);
        
        // Incrementar contador
        $new_count = $this->increment_403_count($ip_address);

        // Verificar si debe bloquearse
        $max_responses = $this->get_max_403_responses();
        if ($new_count >= $max_responses) {
            $block_reason = "Excedió límite de " . $max_responses . " respuestas 403. Última razón: $reason";
            $this->block_ip($ip_address, $block_reason);
            return true;
        }

        return false;
    }

    /**
     * Métodos públicos para acceder a la funcionalidad de bloqueo de IPs
     * Estos métodos permiten que otros componentes del sistema accedan a la funcionalidad
     */

    /**
     * Registrar respuesta 403 (método público)
     * 
     * @param string $ip_address IP del cliente
     * @param string $reason Razón de la respuesta 403
     * @return bool True si la IP fue bloqueada
     */
    public function register_403_response_public(string $ip_address, string $reason = ''): bool {
        return $this->register_403_response($ip_address, $reason);
    }

    /**
     * Verificar si IP está bloqueada (método público)
     * 
     * @param string $ip_address IP a verificar
     * @return bool
     */
    public function is_ip_blocked_public(string $ip_address): bool {
        return $this->is_ip_blocked($ip_address);
    }

    /**
     * Bloquear IP (método público)
     * 
     * @param string $ip_address IP a bloquear
     * @param string $reason Razón del bloqueo
     * @return bool
     */
    public function block_ip_public(string $ip_address, string $reason = ''): bool {
        return $this->block_ip($ip_address, $reason);
    }

    /**
     * Desbloquear IP (método público)
     * 
     * @param string $ip_address IP a desbloquear
     * @return bool
     */
    public function unblock_ip_public(string $ip_address): bool {
        return $this->unblock_ip($ip_address);
    }

    /**
     * Obtener contador de respuestas 403 (método público)
     * 
     * @param string $ip_address IP a verificar
     * @return int
     */
    public function get_403_count_public(string $ip_address): int {
        return $this->get_403_count($ip_address);
    }

    /**
     * Limpiar contador de respuestas 403 (método público)
     * 
     * @param string $ip_address IP a limpiar
     * @return bool
     */
    public function clear_403_count_public(string $ip_address): bool {
        return $this->clear_403_count($ip_address);
    }

    /**
     * Obtener logs completos de respuestas 403 (método público)
     * 
     * @param string $ip_address IP específica (opcional)
     * @param int $limit Límite de registros
     * @param int $offset Offset para paginación
     * @return array
     */
    public function get_403_logs_public(string $ip_address = '', int $limit = 100, int $offset = 0): array {
        return $this->get_403_logs($ip_address, $limit, $offset);
    }

    /**
     * Obtener estadísticas de logs 403 (método público)
     * 
     * @return array
     */
    public function get_403_logs_stats_public(): array {
        return $this->get_403_logs_stats();
    }

    /**
     * Limpiar logs antiguos de respuestas 403 (método público)
     * 
     * @param int $days_old Días de antigüedad para eliminar
     * @return int Número de registros eliminados
     */
    public function cleanup_old_403_logs_public(int $days_old = 30): int {
        return $this->cleanup_old_403_logs($days_old);
    }

    /**
     * Forzar la creación de tablas de base de datos (método público)
     * 
     * @return bool
     */
    public function force_create_tables_public(): bool {
        try {
            $this->init_ip_blocking_tables();
            return true;
        } catch (Exception $e) {
            error_log("Error forzando creación de tablas: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener logs completos de respuestas 403
     * 
     * @param string $ip_address IP específica (opcional)
     * @param int $limit Límite de registros
     * @param int $offset Offset para paginación
     * @return array
     */
    private function get_403_logs(string $ip_address = '', int $limit = 100, int $offset = 0): array {
        global $wpdb;

        $table_name = $wpdb->prefix . self::IP_403_LOGS_TABLE;
        
        $where_clause = '';
        $prepare_values = [];
        
        if (!empty($ip_address)) {
            $where_clause = 'WHERE ip_address = %s';
            $prepare_values[] = $ip_address;
        }
        
        $prepare_values[] = $limit;
        $prepare_values[] = $offset;
        
        $sql = "SELECT * FROM $table_name $where_clause ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        
        if (!empty($prepare_values)) {
            $sql = $wpdb->prepare($sql, ...$prepare_values);
        }
        
        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Obtener estadísticas de logs 403
     * 
     * @return array
     */
    private function get_403_logs_stats(): array {
        global $wpdb;

        $table_name = $wpdb->prefix . self::IP_403_LOGS_TABLE;
        $current_time = current_time('mysql');
        
        $stats = [
            'total_logs' => 0,
            'today_logs' => 0,
            'week_logs' => 0,
            'month_logs' => 0,
            'top_ips' => [],
            'top_endpoints' => [],
            'top_reasons' => []
        ];
        
        // Total de logs
        $stats['total_logs'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Logs de hoy
        $stats['today_logs'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(timestamp) = DATE(%s)",
            $current_time
        ));
        
        // Logs de la semana
        $stats['week_logs'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE timestamp >= DATE_SUB(%s, INTERVAL 7 DAY)",
            $current_time
        ));
        
        // Logs del mes
        $stats['month_logs'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE timestamp >= DATE_SUB(%s, INTERVAL 30 DAY)",
            $current_time
        ));
        
        // Top IPs
        $stats['top_ips'] = $wpdb->get_results(
            "SELECT ip_address, COUNT(*) as count 
             FROM $table_name 
             GROUP BY ip_address 
             ORDER BY count DESC 
             LIMIT 10"
        );
        
        // Top endpoints
        $stats['top_endpoints'] = $wpdb->get_results(
            "SELECT endpoint, COUNT(*) as count 
             FROM $table_name 
             GROUP BY endpoint 
             ORDER BY count DESC 
             LIMIT 10"
        );
        
        // Top razones
        $stats['top_reasons'] = $wpdb->get_results(
            "SELECT reason, COUNT(*) as count 
             FROM $table_name 
             GROUP BY reason 
             ORDER BY count DESC 
             LIMIT 10"
        );
        
        return $stats;
    }

    /**
     * Limpiar logs antiguos de respuestas 403
     * 
     * @param int $days_old Días de antigüedad para eliminar
     * @return int Número de registros eliminados
     */
    private function cleanup_old_403_logs(int $days_old = 30): int {
        global $wpdb;

        $table_name = $wpdb->prefix . self::IP_403_LOGS_TABLE;
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days_old days"));
        
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE timestamp < %s",
            $cutoff_date
        ));
        
        return $result !== false ? $result : 0;
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
// Nota: Los logs ahora se manejan únicamente en base de datos
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
