<?php

/**
 * @internal never define functions inside callbacks.
 * these functions could be run multiple times; this would result in a fatal error.
 */

/**
 * custom option and settings
 */
function magicloginapi_settings_init()
{
    // Register a new setting for "magicloginapi" page.
    register_setting('magicloginapi', 'magicloginapi_options', 'magicloginapi_validation_sanitization');

    // Register a new section in the "magicloginapi" page.
    add_settings_section(
        'magicloginapi_section',
        __('', 'magicloginapi'),
        'magicloginapi_section_callback',
        'magicloginapi'
    );

    // Register a new field in the "magicloginapi_section" section, inside the "magicloginapi" page.

    add_settings_field(
        'request_type',
        'Select your request type',
        'magicloginapi_options_select_callback',
        'magicloginapi',
        'magicloginapi_section',
        array(
            'request_type'
        )
    );

    add_settings_field(
        'wp_token',
        'Unique WP-Token for webhook Authentication',
        'magicloginapi_options_wp_token_callback',
        'magicloginapi',
        'magicloginapi_section',
        array(
            'wp_token' // $args for callback
        )
    );

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

    add_settings_field(
        'notes',
        'Notes',
        'magicloginapi_options_textarea_callback',
        'magicloginapi',
        'magicloginapi_section',
        array(
            'notes'  // $args for callback
        )
    );

    // Register a new setting for "magicloginapi_token_settings" page.
    register_setting('magicloginapi_token_settings', 'magicloginapi_token_settings_options', 'magicloginapi_token_settings_validation_sanitization');

    // Register a new section in the "magicloginapi_token_settings" page.
    add_settings_section(
        'magicloginapi_token_settings_section',
        __('', 'magicloginapi_token_settings'),
        'magicloginapi_token_settings_section_callback',
        'magicloginapi_token_settings'
    );

    add_settings_field(
        'single_use',
        'Is Single use?',
        'magicloginapi_options_select_boolean_callback',
        'magicloginapi_token_settings',
        'magicloginapi_token_settings_section',
        array(
            'single_use'
        )
    );

    add_settings_field(
        'life_span',
        'Expire in (Value in Minute)',
        'magicloginapi_options_number_callback',
        'magicloginapi_token_settings',
        'magicloginapi_token_settings_section',
        array(
            'life_span'  // $args for callback
        )
    );

    add_settings_field(
        'invalidates_on_creation',
        'Invalidates On Creation',
        'magicloginapi_options_select_boolean_callback',
        'magicloginapi_token_settings',
        'magicloginapi_token_settings_section',
        array(
            'invalidates_on_creation'
        )
    );

    add_settings_field(
        'invalidates_others_on_use',
        'Logout From Other Devices',
        'magicloginapi_options_select_boolean_callback',
        'magicloginapi_token_settings',
        'magicloginapi_token_settings_section',
        array(
            'invalidates_others_on_use'
        )
    );
}

/**
 * Register our magicloginapi_settings_init to the admin_init action hook.
 */
add_action('admin_init', 'magicloginapi_settings_init');


/**
 * Custom option and settings:
 *  - callback functions
 */
function magicloginapi_options_select_callback($args)
{
    $options = get_option('magicloginapi_options'); ?>
    <select id="<?php echo $args[0]; ?>" name="<?php echo "magicloginapi_options[$args[0]]"; ?>" required="required" style="width:300px">
        <option value="">Select Type</option>
        <option value="POST" <?php selected($options[''  . $args[0] . ''], "POST"); ?>>POST</option>
        <option value="PUT" <?php selected($options[''  . $args[0] . ''], "PUT"); ?>>PUT</option>
    </select>
    <?php
}

function magicloginapi_options_wp_token_callback($args)
{
    $options = get_option('magicloginapi_options');
    echo '<input type="text" class="magicloginapi_input" id="'  . $args[0] . '" name="magicloginapi_options['  . $args[0] . ']" value="' . $options[''  . $args[0] . ''] . '" readonly required="required" style="width:300px;margin-bottom:5px;"></br>';
    echo "  <input type='button' class='button button-primary' id='magiclogina_copy_token' value='Copy Token'>";
    echo "  <input type='button' class='button button-primary' id='magiclogina_generate_token' value='Generate Token'>";
}

function magicloginapi_options_textbox_callback($args)
{
    $options = get_option('magicloginapi_options');
    echo '<input type="text" class="magicloginapi_input" id="'  . $args[0] . '" name="magicloginapi_options['  . $args[0] . ']" value="' . $options[''  . $args[0] . ''] . '" required="required" style="width:300px">';
}

function magicloginapi_options_texturl_callback($args)
{
    $options = get_option('magicloginapi_options');
    echo '<input type="url" class="magicloginapi_input" id="'  . $args[0] . '" name="magicloginapi_options['  . $args[0] . ']" value="' . $options[''  . $args[0] . ''] . '" required="required" style="width:300px">';
}

function magicloginapi_options_textarea_callback($args)
{
    $requried = '';
    if ($args[0] != "notes") {
        $requried = 'required="required"';
    }
    $options = get_option('magicloginapi_options');
    echo '<textarea class="magicloginapi_input" id="'  . $args[0] . '" name="magicloginapi_options['  . $args[0] . ']" rows=10 style="width:300px" ' . $requried . '>' . $options[''  . $args[0] . ''] . '</textarea>';
}

function magicloginapi_options_select_boolean_callback($args)
{
    $options = get_option('magicloginapi_token_settings_options'); ?>
    <select id="<?php echo $args[0]; ?>" name="<?php echo "magicloginapi_token_settings_options[$args[0]]"; ?>" required="required" style="width:300px">
        <option value="">Select Type</option>
        <option value="true" <?php selected($options[''  . $args[0] . ''], "true"); ?>>True</option>
        <option value="false" <?php selected($options[''  . $args[0] . ''], "false"); ?>>False</option>
    </select>
    <?php
}

function magicloginapi_options_number_callback($args)
{
    $options = get_option('magicloginapi_token_settings_options');
    echo '<input type="number" class="magicloginapi_input" id="'  . $args[0] . '" name="magicloginapi_token_settings_options['  . $args[0] . ']" min=1 value="' . $options[''  . $args[0] . '']  . '" required="required" style="width:300px">';
}

/**
 * Developers section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function magicloginapi_section_callback($args)
{ ?>
    <p id="<?php echo esc_attr($args['id']); ?>"><?php esc_html_e('To use the api call config below user will have to merge some information in and overall customize the JSON Post Request. Merge tage available :  [token] [uid] [email] [user_firstname] [user_lastname] [passback] OR [custom_id] In active campaign we are going to trigger a webhook which is going to hit wordpress. And then Wordpress is going to fire a post at active campaign setting a password. Then... then Active Campaign is going to use that token in an email. Then... Active campaign is going to delete that token from its database. ', 'magicloginapi'); ?></p>
    <?php
}

function magicloginapi_token_settings_section_callback($args)
{
    // 
}

/**
 * Add the top level menu page.
 */
function magicloginapi_options_page()
{
    add_menu_page(
        'Magic Login API',
        'Magic Login API Options',
        'manage_options',
        'magic-login-api',
        'magicloginapi_options_page_html',
        'dashicons-rest-api',
        77
    );

    add_submenu_page(
        'magic-login-api',
        'Logs',
        'Logs',
        'manage_options',
        'magic-login-logs',
        'magicloginapi_logs_page_html'
    );

    add_submenu_page(
        'magic-login-api',
        'Token Settings',
        'Token Settings',
        'manage_options',
        'magic-login-token-settings',
        'magicloginapi_token_settings_page_html',
        1
    );
}


/**
 * Register our magicloginapi_options_page to the admin_menu action hook.
 */
add_action('admin_menu', 'magicloginapi_options_page');

function magicloginapi_logs_page_html()
{
    if ($_REQUEST['log-action'] == 'view') {
        $openFile = $_REQUEST['log-file'];
    } elseif ($_REQUEST['log-action'] == 'delete') {
        unlink(dirname(plugin_dir_path(__FILE__)) . '/logs/' . $_REQUEST['log-file']);
        wp_redirect(home_url('/wp-admin/admin.php?page=magic-login-logs'));
    } ?>
    <style>
        .accordion {
            background-color: #eee;
            color: #444;
            cursor: pointer;
            padding: 18px;
            width: 100%;
            border: none;
            text-align: left;
            outline: none;
            font-size: 15px;
            transition: 0.4s;
        }

        .active,
        .accordion:hover {
            background-color: #ccc;
        }

        .accordion:after {
            content: '\002B';
            color: #777;
            font-weight: bold;
            float: left;
            margin-right: 20px;
        }

        .active:after {
            content: "\2212";
        }

        .panel {
            padding: 0 18px;
            background-color: white;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.2s ease-out;
        }
    </style>
    <div class="container">
        <div class="wrap">
            <h1 class="wp-heading-inline">Logs</h1>
        </div>
        <div class="" style="margin-bottom: 10px;">
            <div class="">
                <form method="get" action="<?php echo home_url('/wp-admin/admin.php?page=magic-login-logs'); ?>">
                    <input type="hidden" name="page" value="magic-login-logs">
                    <select name="log-file">
                        <?php
                            if ($handle = opendir(dirname(plugin_dir_path(__FILE__)) . '/logs')) {
                                $logFiles = [];
                                $ignore = array('cgi-bin', '.', '..', '._');
                                while (false !== ($entry = readdir($handle))) {
                                    if (!in_array($entry, $ignore) and substr($entry, 0, 1) != '.') {
                                        $logFiles[] = $entry;
                                    }
                                }
                                closedir($handle);
                                krsort($logFiles);
                                foreach ($logFiles as $key => $entry) {
                                    $selected = '';
                                    if ($openFile != '') {
                                        if ($entry == $openFile) {
                                            $selected = 'selected';
                                        }
                                    } else {
                                        $selected = (count($logFiles) == $key) ? 'selected' : '';
                                    } ?>
                                    <option value="<?php echo $entry; ?>" <?php echo $selected; ?>>
                                        <?php echo $entry; ?>
                                    </option>
                                    <?php
                                }
                            }
                        ?>
                    </select>
                    <button class="button-primary" type="submit" name="log-action" value="view">View</button>
                    <button class="button-primary" type="submit" id="delete" name="log-action" value="delete">Delete</button>
                    <span style="float: right;margin-right: 22px;font-size: 15px;">Current Time: <?php echo date('d-M-Y h:i:s A (e)'); ?></span>
                </form>
            </div>
        </div>
        <div class='logDiv' style='font-weight: 500; width: 98%;'>
            <?php
                if ($openFile == '') {
                    $openFile = $logFiles[count($logFiles) - 1];
                }
                $logFile = dirname(plugin_dir_path(__FILE__)) . '/logs/' . $openFile;
                if (file_exists($logFile) && !empty($openFile)) {
                    $file = file($logFile);
                    $file = array_reverse($file);
                    foreach ($file as $f) {
                        echo "<button class='accordion'>" . limit_text($f, 8) . "</button>
                                <div class='panel'>
                                    <p>$f</p>
                                </div>";
                    }
                } else {
                    echo "<div class='notice notice-warning is-dismissible'>
                            <p>Log's File Is Not Exist </p>
                        </div>";
                }
            ?>
        </div>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var acc = document.getElementsByClassName("accordion");
            var i;

            for (i = 0; i < acc.length; i++) {
                acc[i].addEventListener("click", function() {
                    this.classList.toggle("active");
                    var panel = this.nextElementSibling;
                    if (panel.style.maxHeight) {
                        panel.style.maxHeight = null;
                    } else {
                        panel.style.maxHeight = panel.scrollHeight + "px";
                    } 
                });
            }
            $('#delete').click(function(e) {
                var val = confirm("Please confirm to delete log file.");
                if (val === false) {
                    return false;
                }
            });
            jQuery(".logDiv").scrollTop(jQuery(".logDiv")[0].scrollHeight);
        });
    </script>
    <?php
}

/**
 * Truncate string as per limit.
 */
function limit_text($text, $limit)
{
    $pos = stripos($text, ':-');
    if ( $pos > -1 ) {
        $text = substr($text, 0, $pos)."...";
    }else{
        if (str_word_count($text, 0) > $limit) {
            $words = str_word_count($text, 2);
            $pos   = array_keys($words);
            $text  = substr($text, 0, $pos[$limit]) . '...';
        }
    }
    return $text;
}

/**
 * Top level menu callback function
 */
function magicloginapi_options_page_html()
{
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // add error/update messages

    // check if the user have submitted the settings
    // WordPress will add the "settings-updated" $_GET parameter to the url
    if (isset($_GET['settings-updated'])) {
        // add settings saved message with the class of "updated"
        // add_settings_error('magicloginapi_messages', 'magicloginapi_message', __('Settings Saved', 'magicloginapi'), 'updated');
    }

    // show error/update messages
    settings_errors('magicloginapi_messages'); ?>

    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            // output security fields for the registered setting "magicloginapi"
            settings_fields('magicloginapi');
            // output setting sections and their fields
            // (sections are registered for "magicloginapi", each field is registered to a specific section)
            do_settings_sections('magicloginapi');
            // output save settings button
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <script>
        (function($) {
            $('#magiclogina_generate_token').click(function() {
                var rand = function() {
                    return Math.random().toString(36).substr(2); // remove `0.`
                };

                var token = function() {
                    return rand() + rand(); // to make it longer
                };

                $('#wp_token').val(token);
            });

            $('#magiclogina_copy_token').click(function() {
                copyToClipboard($('#wp_token').val());
            });
        })(jQuery);

        function copyToClipboard(text) {
            var sampleTextarea = document.createElement("textarea");
            document.body.appendChild(sampleTextarea);
            sampleTextarea.value = text; //save main text in it
            sampleTextarea.select(); //select textarea contenrs
            document.execCommand("copy");
            document.body.removeChild(sampleTextarea);
        }
    </script>
<?php
}

// Token Settings PAGE
function magicloginapi_token_settings_page_html()
{
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // show error/update messages
    settings_errors(); ?>

    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            // output security fields for the registered setting "magicloginapi_token_settings"
            settings_fields('magicloginapi_token_settings');
            // output setting sections and their fields
            // (sections are registered for "magicloginapi_token_settings", each field is registered to a specific section)
            do_settings_sections('magicloginapi_token_settings');
            // output save settings button
            submit_button('Save Token Settings');
            ?>
        </form>
    </div>
<?php
}

//Fields Validation
function magicloginapi_validation_sanitization($option)
{
    $old_data = get_option('magicloginapi_options');

    if (!json_validator($option['request_data'])) {
        add_settings_error('magicloginapi_messages', 'magicloginapi_message', __('Invalid Json', 'magicloginapi'), 'error');
        return $old_data;
    } else {
        add_settings_error('magicloginapi_messages', 'magicloginapi_message', __('Settings Saved', 'magicloginapi'), 'updated');
        return $option;
    }
}

//JSON Validator function
function json_validator($data = NULL)
{
    if (!empty($data)) {
        @json_decode($data);
        return (json_last_error() === JSON_ERROR_NONE);
    }
    return false;
}
