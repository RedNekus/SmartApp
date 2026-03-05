<?php
namespace CCF;

class API {
    public static function register_routes() {
        add_action('rest_api_init', function () {
            register_rest_route('company/v1', '/contact', [
                'methods' => 'POST',
                'callback' => [__CLASS__, 'handle_submission'],
                'permission_callback' => function () {
                    // Пропускаем все запросы в режиме разработки
                    return true;
                },
            ]);
        });
    }

    /**
     * Проверка антиспама: Honeypot + Time-trap + Rate-limit
     */
    private static function check_spam($request) {
        // 1. Honeypot: если заполнено скрытое поле — это бот
        $honeypot = $request->get_param('website'); // имя поля-ловушки
        if (!empty($honeypot)) {
            return new \WP_Error('spam_detected', 'Honeypot triggered', ['status' => 400]);
        }

        // 2. Time-trap: если форма отправлена быстрее 2 секунд — бот
        $start_time = intval($request->get_param('form_start_time'));
        if ($start_time > 0) {
            $fill_time = time() - $start_time;
            if ($fill_time < 2) { // минимальное время заполнения
                return new \WP_Error('spam_detected', 'Time-trap triggered', ['status' => 400]);
            }
        }

        // 3. Rate-limit: не более 5 отправок в минуту с одного IP
        $ip = self::get_client_ip();
        $rate_key = 'ccf_rate_limit_' . md5($ip);
        $attempts = get_transient($rate_key);
        
        if ($attempts === false) {
            set_transient($rate_key, 1, 60); // первый запрос, ставим таймер на 60 сек
        } elseif ($attempts >= 5) {
            return new \WP_Error('too_many_requests', 'Rate limit exceeded', ['status' => 429]);
        } else {
            set_transient($rate_key, $attempts + 1, 60); // инкремент
        }

        return true; // всё чисто
    }

    /**
     * Получение IP адреса (с учётом прокси)
     */
    private static function get_client_ip() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = '0.0.0.0';
        }
        return sanitize_text_field($ip);
    }

    public static function handle_submission($request) {
        // 1. Nonce check
        /*
        if (!wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')) {
            return new WP_Error('rest_forbidden', 'Invalid nonce', ['status' => 403]);
        }
        */

        // === ANTI-SPAM CHECK ===
        $spam_check = self::check_spam($request);
        if (is_wp_error($spam_check)) {
            // Логируем попытку спама
            if (class_exists('CCF\Logger')) {
                \CCF\Logger::log(
                    sanitize_email($request->get_param('email')), 
                    'spam_blocked', 
                    null, 
                    self::get_client_ip()
                );
            }
            return $spam_check;
        }
        // === END ANTI-SPAM ===

        // 2. Валидация email (RFC)
        $email = sanitize_email($request->get_param('email'));
        if (!is_email($email)) {
            return new \WP_Error('invalid_email', 'Invalid email format', ['status' => 400]);
        }

        // 3. Санитизация остальных полей
        $data = [
            'first_name' => sanitize_text_field($request->get_param('first_name')),
            'last_name'  => sanitize_text_field($request->get_param('last_name')),
            'subject'    => sanitize_text_field($request->get_param('subject')),
            'message'    => sanitize_textarea_field($request->get_param('message')),
            'email'      => $email,
            'ip'         => self::get_client_ip(),
            'timestamp'  => current_time('mysql'),
        ];

        // 4. HubSpot integration (MOCK or PRODUCTION)
        // Передаём пустой массив атрибутов — метод сам определит режим по токенам
        self::send_to_hubspot($request, []);

        // 5. Логирование (заглушка)
        if (class_exists('CCF\Logger')) {
            \CCF\Logger::log($email, 'received', null, $data['ip']);
        }

        return rest_ensure_response([
            'success' => true, 
            'message' => 'Form submitted successfully',
            'data' => ['email' => $email]
        ]);
    }
    /**
     * Отправка данных в HubSpot (с поддержкой Mock-режима)
     */
    private static function send_to_hubspot($request, $attributes) {
        // Проверяем, есть ли реальные токены
        $use_constants = defined('CCF_HUBSPOT_USE_CONSTANTS') && CCF_HUBSPOT_USE_CONSTANTS;
        
        if ($use_constants) {
            $token = defined('CCF_HUBSPOT_TOKEN') ? CCF_HUBSPOT_TOKEN : '';
            $portal_id = defined('CCF_HUBSPOT_PORTAL_ID') ? CCF_HUBSPOT_PORTAL_ID : '';
            $form_id = defined('CCF_HUBSPOT_FORM_ID') ? CCF_HUBSPOT_FORM_ID : '';
        } else {
            $token = $attributes['hubspotAccessToken'] ?? '';
            $portal_id = $attributes['hubspotPortalId'] ?? '';
            $form_id = $attributes['hubspotFormId'] ?? '';
        }
        
        // === MOCK MODE: если нет токенов ===
        if (empty($token) || empty($portal_id) || empty($form_id)) {
            error_log('[CCF] HubSpot MOCK MODE: Integration not configured');
            error_log('[CCF] HubSpot MOCK: first_name=' . sanitize_text_field($request->get_param('first_name')));
            error_log('[CCF] HubSpot MOCK: last_name=' . sanitize_text_field($request->get_param('last_name')));
            error_log('[CCF] HubSpot MOCK: email=' . sanitize_email($request->get_param('email')));
            error_log('[CCF] HubSpot MOCK: message=' . sanitize_textarea_field($request->get_param('message')));
            error_log('[CCF] HubSpot MOCK: To enable real integration, add HubSpot credentials to wp-config.php');
            return true; // Не блокируем форму
        }
        
        // === PRODUCTION MODE: реальная отправка ===
        // Маппинг полей формы → HubSpot property names
        $field_map = [
            'first_name' => 'firstname',
            'last_name'  => 'lastname',
            'email'      => 'email',
            'message'    => 'message',
            'subject'    => 'subject',
        ];
        
        $fields = [];
        foreach ($field_map as $form_field => $hubspot_prop) {
            $value = $request->get_param($form_field);
            if (!empty($value)) {
                $fields[] = [
                    'name'  => $hubspot_prop,
                    'value' => sanitize_text_field($value),
                ];
            }
        }
        
        if (empty($fields)) {
            error_log('[CCF] HubSpot: no fields to send');
            return false;
        }
        
        // Подготовка запроса
        $url = sprintf(
            'https://api.hubapi.com/submissions/v3/integration/submit/%s/%s',
            $portal_id,
            $form_id
        );
        
        $payload = [
            'fields' => $fields,
            'context' => [
                'pageUri'   => home_url($_SERVER['REQUEST_URI'] ?? ''),
                'pageName'  => get_the_title() ?: '',
                'ipAddress' => self::get_client_ip(),
            ],
        ];
        
        // cURL запрос
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Логирование
        if ($http_code >= 200 && $http_code < 300) {
            error_log('[CCF] HubSpot: submission successful');
            return true;
        } else {
            error_log(sprintf(
                '[CCF] HubSpot error: HTTP %d | Response: %s | cURL: %s',
                $http_code,
                $response,
                $error
            ));
            return false;
        }
    }
}
