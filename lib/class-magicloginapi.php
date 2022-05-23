<?php

new MagicLoginAPI;

class MagicLoginAPI extends WP_REST_Controller
{
    public function __construct()
    {
        add_action('init', [$this, 'magic_login_token_on_use_auth']);
        add_action('init', [$this, '_autologin_via_url']);
        add_action('init', [$this, 'register_routes']);
        // add_shortcode('magic_login_custom', [$this, 'front_end_login_custom']);
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
     * API Callback function - 
     *   -to do- 
     *   Take the default settings described below and add them as options in the admin panel. 
     */
    public function magicloginapi_callback($request)
    {
        try {
            $params = $request->get_params();
            $email = $params['email'];
            $single_use = $params['single_use'] ?? true;
            $life_span = $params['life_span'] ?? 5;
            $invalidates_on_creation = $params['invalidates_on_creation'] ?? true;
            $invalidates_others_on_use = $params['invalidates_others_on_use'] ?? true;
            $wp_token = $params['wp_token'];
            $custom_id = $params['custom_id'];
            $passback = $params['passback'];

            // Get options
            $options = get_option('magicloginapi_options');

            if (empty($options)) {
                throw new Exception("Magic login API settings not configured.");
            }
            if (!$wp_token) {
                throw new Exception("token is required.");
            }

            if (!$email) {
                throw new Exception("email is required.");
            }

            if ($wp_token != $options['wp_token']) {
                throw new Exception("Token mismatch authentication failed.");
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
                return new WP_REST_Response([
                    "code" => 200,
                    "message" => "user data found",
                    "data" => $data
                ], 200);
            } else {
                throw new Exception("something went wrong");
            }
        } catch (Exception $e) {
            magiclogin_log($e->getMessage());
            return new WP_Error('401', __($e->getMessage(), 'text-domain'));
        }
    }

    /**
     * Webhook hit function - (post / put response)
     */
    public function magicloginapi_hit_url($url,$data, $options)
    {
        magiclogin_log("Magic Login trigger URL " . $url, 'notice');

        $headers = [
            $options['api_name'] . ': ' . $options['api_token'],
            'Content-Type: application/json',
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $options['request_type'],
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            throw new Exception($error_msg);
        }

        curl_close($curl);

        if ($httpcode != 200) {
            throw new Exception('Someting went wrong while hitting ' . $url . '.');
        } else {
            $response = json_encode(json_decode($response), JSON_PRETTY_PRINT);
            magiclogin_log("Trigger Success Response:</br>$response", 'success');
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
            return $e->getMessage();
        }
    }

    /** ==================================================
     * FrontEnd Shortcode HTML code
     *
     * @since 1.00
     */
    public function front_end_login_custom()
    {
        if (is_user_logged_in()) {
            return;
        }
        try {
            if (isset($_POST['magic-submit-custom'])) {
                if (!empty($_POST['nonce'])) {
                    $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
                    if (wp_verify_nonce($nonce, 'magic_login_request')) {
                        if (!empty($_POST['magic_user_email_custom'])) {
                            $email = sanitize_text_field(wp_unslash($_POST['magic_user_email_custom']));
                            $single_use = sanitize_text_field($_POST['single_use']);
                            $life_span = sanitize_text_field($_POST['life_span']);
                            $invalidates_on_creation = sanitize_text_field($_POST['invalidates_on_creation']);
                            $invalidates_others_on_use = sanitize_text_field($_POST['invalidates_others_on_use']);
                            $final_string = $this->getMagicToken($email, $single_use, $life_span, $invalidates_on_creation, $invalidates_others_on_use);
                            echo "<p style='font-weight: bold;'>$final_string<p>";
                        }
                    } else {
                        throw new Exception("Refresh and try again token expire");
                    }
                } else {
                    throw new Exception("Submission not verified. Refersh and try again.");
                }
            }
        } catch (Exception $e) {
            echo "<p style='font-weight: bold;'>$e->getMessage()</p>";
        }

        ob_start(); ?>
        <h3>Test Custom Magic Link</h3>
        <form action="<?php echo get_the_permalink(); ?>" method="post">
            <label for="magic_user_email_custom">Login with email</label>
            <input type="text" inputmode="url" pattern="[a-zA-Z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" name="magic_user_email_custom" id="magic_user_email_custom" value="" />
            <label for="single_use">Is Single use?</label>
            <select name="single_use">
                <option value=true>True</option>
                <option value=false>False</option>
            </select>
            <label for="life_span">Expire in (Value in Minute)</label>
            <input type="number" min="5" name="life_span" value="5">
            <label for="invalidates_on_creation">Invalidates On Creation</label>
            <select name="invalidates_on_creation">
                <option value=true>True</option>
                <option value=false>False</option>
            </select>
            <label for="invalidates_others_on_use">Invalidates Others On Use</label>
            <select name="invalidates_others_on_use">
                <option value=true>True</option>
                <option value=false>False</option>
            </select>
            <?php wp_nonce_field('magic_login_request', 'nonce'); ?>
            <input type="submit" name="magic-submit-custom" value="Get Token">
        </form>
<?php
        return ob_get_clean();
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
                if (user_can($user->ID, 'manage_options')) {

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
                    // return "uid=$user->ID&magic_token=$token->token";
                } else {
                    throw new Exception("User not Admin");
                }
            } else {
                throw new Exception("Email not valid");
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /** ==================================================
     * Magic link custom login
     *
     * @since 1.00
     */
    public function _autologin_via_url()
    {
        if (isset($_GET['magic_token']) && isset($_GET['uid'])) {
            $uid = intval(sanitize_key($_GET['uid']));
            $token = $_GET['magic_token'];
            $tokens = get_user_meta($uid, '_magic_login_tokens_' . $uid, true);
            $key = array_search($token, array_column($tokens, 'token'));
            $db_token = $tokens[$key];
            $arr_params = array('uid', 'magic_token');
            $current_page_url = remove_query_arg($arr_params, $this->curpageurl());

            require_once(ABSPATH . 'wp-includes/class-phpass.php');
            $wp_hasher = new PasswordHash(8, true);
            $time = time();
            if ($wp_hasher->CheckPassword($token . $db_token['expire'], $db_token['hash_token']) && $time < $db_token['expire']) {
                var_dump($db_token['token_use_count']);
                if ($db_token['single_use'] == "true" && $db_token['token_use_count']) {
                    $url = add_query_arg('magic_login_mail_error_token', 'true', $current_page_url);
                    wp_redirect($url);
                    exit;
                }
                if ($db_token['invalidates_others_on_use'] == "true") {
                    $tokens[$key]['user_ip'] = $this->get_the_user_ip();
                    update_user_meta($uid, '_magic_login_token_on_use_' . $uid, $db_token['token']);
                } else {
                    delete_user_meta($uid, '_magic_login_token_on_use_' . $uid, $db_token['token']);
                }
                $tokens[$key]['token_use_count'] = (int)$tokens[$key]['token_use_count'] + 1;
                update_user_meta($uid, '_magic_login_tokens_' . $uid, $tokens);
                wp_set_auth_cookie($uid);
                setcookie('magic_login_token', true, 0);
                wp_redirect(apply_filters('magic_login_mail_after_login_redirect', $current_page_url, $uid));
                exit;
            }
        }
    }

    /** ==================================================
     * Display User IP in WordPress
     *
     * @since 1.00
     */
    public function magic_login_token_on_use_auth()
    {
        if ($_COOKIE['magic_login_token']) {
            $user = wp_get_current_user();
            if (!empty($user->ID)) {
                $token = get_user_meta($user->ID, "_magic_login_token_on_use_$user->ID", true);
                $tokens = get_user_meta($user->ID, '_magic_login_tokens_' . $user->ID, true);
                $key = array_search($token, array_column($tokens, 'token'));
                $db_token = $tokens[$key];
                if ($db_token['user_ip'] != $this->get_the_user_ip()) {
                    wp_logout();
                }
            }
        }
    }

    /** ==================================================
     * Display User IP in WordPress
     *
     * @since 1.00
     */
    private function get_the_user_ip()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return apply_filters('wpb_get_ip', $ip);
    }
}
