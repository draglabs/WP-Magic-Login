<?php
/**
 * @internal never define functions inside callbacks.
 * these functions could be run multiple times; this would result in a fatal error.
 */
 
/**
 * custom option and settings
 */
function magicloginapi_settings_init() {
    // Register a new setting for "magicloginapi" page.
    register_setting( 'magicloginapi', 'magicloginapi_options' );
 
    // Register a new section in the "magicloginapi" page.
    add_settings_section(
        'magicloginapi_section',
        __( 'The Matrix has you.', 'magicloginapi' ), 'magicloginapi_section_callback',
        'magicloginapi'
    );
 
    // Register a new field in the "magicloginapi_section" section, inside the "magicloginapi" page.

    add_settings_field(
        'api_url',
        'Enter your API endpoint where we will send the token',
        'magicloginapi_options_texturl_callback',
        'magicloginapi',
        'magicloginapi_section',
        array(
            'api_url' // $args for callback
        )
    );

    add_settings_field(
        'api_name',
        'There the Token Name your API will recognize',
        'magicloginapi_options_textbox_callback',
        'magicloginapi',
        'magicloginapi_section',
        array(
            'api_name'  // $args for callback
        )
    );

    add_settings_field(
        'api_token',
        'Enter the destination api token',
        'magicloginapi_options_textbox_callback',
        'magicloginapi',
        'magicloginapi_section',
        array(
            'api_token'  // $args for callback
        )
    );

    add_settings_field(
        'request_data',
        'Configure your post request',
        'magicloginapi_options_textarea_callback',
        'magicloginapi',
        'magicloginapi_section',
        array(
            'request_data'  // $args for callback
        )
    );
}

 
/**
 * Register our magicloginapi_settings_init to the admin_init action hook.
 */
add_action( 'admin_init', 'magicloginapi_settings_init' );

 
/**
 * Custom option and settings:
 *  - callback functions
 */
function magicloginapi_options_textbox_callback($args) { 
 
    $options = get_option('magicloginapi_options'); 
    
    echo '<input type="text" class="magicloginapi_input" id="'  . $args[0] . '" name="magicloginapi_options['  . $args[0] . ']" value="' . $options[''  . $args[0] . ''] . '" required="required" style="width:300px">';
     
}

function magicloginapi_options_texturl_callback($args) { 
 
    $options = get_option('magicloginapi_options'); 
    
    echo '<input type="url" class="magicloginapi_input" id="'  . $args[0] . '" name="magicloginapi_options['  . $args[0] . ']" value="' . $options[''  . $args[0] . ''] . '" required="required" style="width:300px">';
     
}

function magicloginapi_options_textarea_callback($args) { 
 
    $options = get_option('magicloginapi_options'); 
    echo '<textarea class="magicloginapi_input" id="'  . $args[0] . '" name="magicloginapi_options['  . $args[0] . ']" rows=5 style="width:300px" required="required">' . $options[''  . $args[0] . ''] . '</textarea>';
}
 
/**
 * Developers section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function magicloginapi_section_callback( $args ) {
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Follow the white rabbit.', 'magicloginapi' ); ?></p>
    <?php
}



/**
 * Add the top level menu page.
 */
function magicloginapi_options_page() {
    add_menu_page(
        'Magic Login API',
        'Magic Login API Options',
        'manage_options',
        'magic-login-api',
        'magicloginapi_options_page_html',
        'dashicons-rest-api',
        77
    );
}
 
 
/**
 * Register our magicloginapi_options_page to the admin_menu action hook.
 */
add_action( 'admin_menu', 'magicloginapi_options_page' );
 
 
/**
 * Top level menu callback function
 */
function magicloginapi_options_page_html() {
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
 
    // add error/update messages
 
    // check if the user have submitted the settings
    // WordPress will add the "settings-updated" $_GET parameter to the url
    if ( isset( $_GET['settings-updated'] ) ) {
        // add settings saved message with the class of "updated"
        add_settings_error( 'magicloginapi_messages', 'magicloginapi_message', __( 'Settings Saved', 'magicloginapi' ), 'updated' );
    }
 
    // show error/update messages
    settings_errors( 'magicloginapi_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            // output security fields for the registered setting "magicloginapi"
            settings_fields( 'magicloginapi' );
            // output setting sections and their fields
            // (sections are registered for "magicloginapi", each field is registered to a specific section)
            do_settings_sections( 'magicloginapi' );
            // output save settings button
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}