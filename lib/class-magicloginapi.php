<?php

new MagicLoginAPI;

class MagicLoginAPI extends WP_REST_Controller
{
    public function __construct()
    {
        add_action('init', [$this, '_autologin_via_url']);
        add_action('init', [$this, 'register_routes']);
    }

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes()
    {
        register_rest_route('magicloginapi/v1', '/get-token', array(
            'methods' => 'GET',
            'callback' => [$this, 'magicloginapi_callback'],
        ));
    }

    /**
     * API Callback function
     */
    public function magicloginapi_callback($request)
    {
        try {
            $params = $request->get_params();
            $email = sanitize_email(str_replace(' ', '+',$params['email']));
            $wp_token = sanitize_text_field($params['wp_token']);
            $custom_id = sanitize_text_field($params['custom_id']);
            $passback =  sanitize_text_field($params['passback']);

            $token_settings = get_option('magicloginapi_token_settings_options');

            $single_use = $token_settings['single_use'] ?? true;
            $life_span = $token_settings['life_span'] ?? 5;
            $invalidates_on_creation = $token_settings['invalidates_on_creation'] ?? true;
            $invalidates_others_on_use = $token_settings['invalidates_others_on_use'] ?? true;

            // Get options
            $options = get_option('magicloginapi_options');

            if (empty($options)) {
                throw new Exception("Magic login API settings not configured.");
            }

            if (!$wp_token) {
                throw new Exception("Token is required.");
            }

            if (!$email) {
                throw new Exception("Email is required.");
            }

            if ($wp_token != $options['wp_token']) {
                throw new Exception("Token mismatch authentication failed.");
            }
            $last = get_option('magic_login_api_trail', true);
            
            if (magic_login_api_core()->can_use_premium_code()==false && $last > 150) {
                return new WP_REST_Response([
                    "message" => "Free plan is over please upgrade your plan. ".magic_login_api_core()->get_upgrade_url()
                ], 404);
            }

            $data = $this->getMagicToken($email, $single_use, $life_span, $invalidates_on_creation, $invalidates_others_on_use);

            $response = str_replace(
                [
                    '[token]',
                    '[uid]',
                    '[email]',
                    '[custom_id]',
                    '[passback]',
                    '[user_firstname]',
                    '[user_lastname]',
                ],
                [
                    $data->token,
                    $data->uid,
                    $email,
                    $custom_id,
                    $passback,
                    $data->first_name,
                    $data->last_name
                ],
                $options['request_data']
            );

            $url = str_replace(
                [
                    '[token]',
                    '[uid]',
                    '[email]',
                    '[custom_id]',
                    '[passback]',
                    '[user_firstname]',
                    '[user_lastname]',
                ],
                [
                    $data->token,
                    $data->uid,
                    $email,
                    $custom_id,
                    $passback,
                    $data->first_name,
                    $data->last_name
                ],
                $options['api_url']
            );

            $this->magicloginapi_hit_url($url,$response, $options);

            $response = json_decode($response);
            $data = $this->prepare_response_for_collection($response);
            if (!empty($data)) {
                if(!magic_login_api_core()->can_use_premium_code()){
                    $last = get_option('magic_login_api_trail', true);
                    update_option('magic_login_api_trail', $last + 1);
                }
                return new WP_REST_Response([
                    "code" => 200,
                    "message" => "user data found",
                    "data" => $data
                ], 200);
            } else {
                throw new Exception("something went wrong");
            }
        } catch (Exception $e) {
            magiclogin_log( __("Line: " .$e->getLine()." | ".$e->getMessage()) );
            return new WP_Error('401', __("Line: " .$e->getLine()." | ".$e->getMessage(), 'text-domain'));
        }
    }

    /**
     * Webhook hit function 
     */
    public function magicloginapi_hit_url($url,$data, $options)
    {
        magiclogin_log("Magic Login trigger URL " . $url, 'notice');
        $headers = [
            $options['api_name'] => $options['api_token'],
            "Content-Type" => "application/json",
        ];
            
        $args = array(
            'body'        => $data,
            'timeout'     => '60',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => $headers,
            'response'    => array('code'=> 200 , 'message'=>'ok'),
            'cookies'     => array(),
            'method'      => $options['request_type']
        );
        
        $response = wp_remote_request($url, $args );
        $http_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $http_code != 200 ) {
            magiclogin_log("Trigger Error Response:-$body");
            throw new Exception("Someting went wrong while hitting $url");
        }else{
            magiclogin_log("Trigger Success Response:-$body", 'success');
        }
    }
    
    /** ==================================================
     * Check if the account is valid from the email address.
     *
     * @param string $email  email.
     * @return string / bool
     * @since 1.00
     */
    private function valid_account($email)
    {
        $valid_email = sanitize_email($email);
        if (is_email($valid_email) && email_exists($valid_email)) {
            return $valid_email;
        }

        return false;
    }

    /** ==================================================
     * Returns the current page URL
     *
     * @return string
     * @since 1.00
     */
    private function curpageurl()
    {
        $current_url = get_the_permalink();

        if (!$current_url) {
            if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])) {
                $current_url = 'https://';
            } else {
                $current_url = 'http://';
            }
            if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
                $current_url .= sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST']));
                if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
                    $current_url .= esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']));
                }
            }
        }

        return $current_url;
    }

    /** ==================================================
     * Create a nonce like token that you only use once based on transients
     *
     * @param string $action  action.
     * @param int    $user_id  user_id.
     * @param int    $life_Span  life_Span.
     * @return object
     * @since 1.00
     */
    private function _create_onetime_token($user_id, $life_Span)
    {
        try {
            $time = time();

            /* random salt */
            $key = wp_generate_password(20, false);

            require_once(ABSPATH . 'wp-includes/class-phpass.php');
            $wp_hasher = new PasswordHash(8, true);
            $string = $key . "magic_login_token_$user_id" . $time;

            /* we're sending this to the user */
            $token  = wp_hash($string);
            $expiration = $time + (60 * $life_Span);

            /* we're storing a combination of token and expiration */
            $stored_hash = $wp_hasher->HashPassword($token . $expiration);

            return (object)[
                "uid" => $user_id,
                "token" => $token,
                "stored_hash" => $stored_hash,
                "time" => $time,
                "expiration" => $expiration
            ];
        } catch (Exception $e) {
            return __( "Line: " .$e->getLine()." | ".$e->getMessage() );
        }
    }

    /** ==================================================
     * Create a Custom function to get token
     *
     * @param string    $email  Admin user email.
     * @param bool    $single_use  One time use token. If false - the token may be used many times.
     * @param int    $life_Span  After a set period of days this token will expire.
     * @param bool    $invalidates_on_creation  The moment this token is created it invalidates any other tokens the user may have created before.
     * @param bool    $invalidates_others_on_use  The moment this token is used it invalidated all other tokens the user may have created before.
     * @return object
     * @since 1.00
     */
    public function getMagicToken($email, $single_use, $life_span, $invalidates_on_creation, $invalidates_others_on_use)
    {
        try {
            if ($email = $this->valid_account($email)) {
                $user = get_user_by('email', $email);
                if (!empty($user)) {
                    $first_name = get_user_meta($user->ID, 'first_name', true);
                    $last_name = get_user_meta($user->ID, 'last_name', true);
                    // Genrate token
                    $token = $this->_create_onetime_token($user->ID, $life_span);

                    $token->first_name = $first_name;
                    $token->last_name = $last_name;

                    $meta_key = '_magic_login_tokens_' . $user->ID;

                    // Get Existing tokens and Update in DB.
                    // If enbale the invalidate on creation then other token will be deleted for the user and it will be add a single new token.
                    // Dose not effect other users.
                    if ($invalidates_on_creation == "false") {
                        $tokens = empty(get_user_meta($user->ID, $meta_key, true)) ? [] : get_user_meta($user->ID, $meta_key, true);
                    }

                    $tokens[] = [
                        "hash_token" => $token->stored_hash,
                        "token" => $token->token,
                        "single_use" => $single_use,
                        "life_span" => $life_span,
                        "invalidates_on_creation" => $invalidates_on_creation,
                        "invalidates_others_on_use" => $invalidates_others_on_use,
                        "token_use_count" => 0,
                        "user_ip" => $this->get_the_user_ip(),
                        "create" => $token->time,
                        "expire" => $token->expiration
                    ];
                    // Update or Create user meta.
                    update_user_meta($user->ID, $meta_key, $tokens);
                    return $token;
                } else {
                    throw new Exception("User not exists");
                }
            } else {
                throw new Exception("Email not valid");
            }
        } catch (Exception $e) {
            return __( "Line: " .$e->getLine() ." | ".$e->getMessage() );
        }
    }

    /** ==================================================
     * Magic link custom login
     *
     * @since 1.00
     */
    public function _autologin_via_url()
    {
        global $wp_session;
        try {
            if (isset($_GET['magic_token']) && isset($_GET['uid'])) {
                magiclogin_log("Login trigger via magic login token", "notice");
                $uid = intval(sanitize_key($_GET['uid']));
                $token = sanitize_text_field($_GET['magic_token']);
                $tokens = get_user_meta($uid, '_magic_login_tokens_' . $uid, true);
                $key = array_search($token, array_column($tokens, 'token'));
                $db_token = $tokens[$key];
                $arr_params = array('uid', 'magic_token');
                $current_page_url = remove_query_arg($arr_params, $this->curpageurl());
                require_once(ABSPATH . 'wp-includes/class-phpass.php');
                $wp_hasher = new PasswordHash(8, true);
                $time = time();
                if ($wp_hasher->CheckPassword($token . $db_token['expire'], $db_token['hash_token']) && $time < $db_token['expire']) {
                    if ($db_token['single_use'] == "true" && $db_token['token_use_count']) {
                        throw new Exception("This token is single use and it's already used $token");
                    }
                    $tokens[$key]['token_use_count'] = (int)$tokens[$key]['token_use_count'] + 1;
                    magiclogin_log("Login trigger via magic login successful", "notice");
                    $user = get_user_by( 'id', $uid );
                    $uid = (int) $uid;
                    if ($db_token['invalidates_others_on_use'] == "true") {
                        wp_set_current_user($uid);
                        $user_id = get_current_user_id();
                        $session = wp_get_session_token();
                        $sessions = WP_Session_Tokens::get_instance($user_id);
                        $sessions->destroy_others($session);
                    }
                    if( $user ) {
                        wp_set_auth_cookie($uid);
                    }else{
                        throw new Exception("Login trigger via magic login token but user not found with ID: $uid");
                    }
                    wp_redirect(apply_filters('magic_login_mail_after_login_redirect', $current_page_url, $uid));
                    if(!is_user_logged_in()){
                        throw new Exception("Login trigger via magic login token/uid -> Someting went wrong... user id not able to set as auth");
                    }
                    exit;
                }else{
                    throw new Exception("Login Token Not Valid Token is $token");   
                }
            }
        } catch (Exception $e) {
            magiclogin_log( __("Magic Login Error:- Line: " .$e->getLine()." | ".$e->getMessage()) );
            $url = add_query_arg('magic_login_mail_error_token', 'true', $current_page_url);
            wp_redirect($url);
            exit;
        }
        
    }

    /** ==================================================
     * Display User IP in WordPress
     *
     * @since 1.00
     */
    private function get_the_user_ip()
    {   
        $http_client_ip = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        $http_x = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        if (!empty($http_client_ip)) {
            //check ip from share internet
            $ip = $http_client_ip;
        } elseif (!empty($http_x)) {
            //to check ip is pass from proxy
            $ip = $http_x;
        } else {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        return apply_filters('wpb_get_ip', $ip);
    }
}
