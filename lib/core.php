<?php
if ( ! function_exists( 'magic_login_api_core' ) ) {
    // Create a helper function for easy SDK access.
    function magic_login_api_core() {
        global $magic_login_api_core;

        if ( ! isset( $magic_login_api_core ) ) {
            // Include Freemius SDK.
            require_once MAGICLOGINAPI_PATH . 'admin/core/start.php';

            $magic_login_api_core = fs_dynamic_init( array(
                'id'                  => '10561',
                'slug'                => 'magic-login-api',
                'type'                => 'plugin',
                'public_key'          => 'pk_7515e2bd90d80ac23515cfda00cb2',
                'is_premium'          => true,
                'premium_suffix'      => 'Professional',
                // If your plugin is a serviceware, set this option to false.
                'has_premium_version' => true,
                'has_addons'          => false,
                'has_paid_plans'      => true,
                'trial'               => array(
                    'days'               => 14,
                    'is_require_payment' => true,
                ),
                'menu'                => array(
                    'slug'           => 'magic-login-api',
                    'support'        => false,
                ),
                // Set the SDK to work in a sandbox mode (for development & testing).
                // IMPORTANT: MAKE SURE TO REMOVE SECRET KEY BEFORE DEPLOYMENT.
                'secret_key'          => 'sk_l(Pna!OV}=Ca*[=OyrM0E.bBYwlEP',
            ) );
        }

        return $magic_login_api_core;
    }

    // Init Freemius.
    magic_login_api_core();
    // Signal that SDK was initiated.
    do_action( 'magic_login_api_core_loaded' );
}
