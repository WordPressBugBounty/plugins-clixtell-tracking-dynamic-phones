<?php
/**
 * Plugin Name: Clixtell Tracking & Dynamic Phones
 * Plugin URI: https://www.clixtell.com
 * Description: Clixtell Integration Plugin
 * Version: 2.3
 * Author: Clixtell
 * License: GPL2
 */

defined('ABSPATH') || exit;

define('CLIXTELL_PLUGIN_VERSION', '2.3');
define('CLIXTELL_OPTION_NAME', 'clixtell_options');

// Add admin menu
add_action('admin_menu', 'clixtell_admin_add_page');
function clixtell_admin_add_page() {
    add_options_page(
        __('Clixtell Settings', 'clixtell-tracking'),
        __('Clixtell', 'clixtell-tracking'),
        'manage_options',
        'clixtell',
        'clixtell_options_page'
    );
}

// Display options page
function clixtell_options_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <p>
            <?php echo esc_html__('This plugin activates advanced Click Fraud Protection and requires an active Clixtell account.', 'clixtell-tracking'); ?>
            <br />
            <?php
            printf(
                wp_kses(
                    __('Login to your <a href="%1$s">Clixtell dashboard</a> or read more at <a href="%2$s">clixtell.com</a>.', 'clixtell-tracking'),
                    array('a' => array('href' => array()))
                ),
                esc_url('https://app.clixtell.com'),
                esc_url('https://clixtell.com')
            );
            ?>
        </p>

        <form action="options.php" method="post">
            <?php
            settings_fields('clixtell_settings_group');
            do_settings_sections('clixtell_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
add_action('admin_init', 'clixtell_admin_init');
function clixtell_admin_init() {
    register_setting(
        'clixtell_settings_group',
        CLIXTELL_OPTION_NAME,
        'clixtell_sanitize_options'
    );

    add_settings_section(
        'clixtell_main_section',
        '',
        '__return_false',
        'clixtell_settings'
    );

    add_settings_field(
        'clixtell_dynamic_tracking',
        __('Activate Dynamic Call Tracking', 'clixtell-tracking'),
        'clixtell_dynamic_tracking_field',
        'clixtell_settings',
        'clixtell_main_section'
    );
}

// Sanitize options
function clixtell_sanitize_options($input) {
    $sanitized = array();
    $sanitized['dynamic_tracking'] = (!empty($input['dynamic_tracking']) && $input['dynamic_tracking'] === '1') ? '1' : '0';
    return $sanitized;
}

// Display checkbox field
function clixtell_dynamic_tracking_field() {
    $options = get_option(CLIXTELL_OPTION_NAME, array('dynamic_tracking' => '0'));
    $current = isset($options['dynamic_tracking']) ? (string) $options['dynamic_tracking'] : '0';
    ?>
    <input
        type="checkbox"
        id="clixtell_dynamic_tracking"
        name="<?php echo esc_attr(CLIXTELL_OPTION_NAME); ?>[dynamic_tracking]"
        value="1"
        <?php checked('1', $current); ?>
    />
    <label for="clixtell_dynamic_tracking">
        <?php echo esc_html__('This will activate Dynamic Phone Insertion.', 'clixtell-tracking'); ?>
        <a href="<?php echo esc_url('https://support.clixtell.com'); ?>">
            <?php echo esc_html__('Read More', 'clixtell-tracking'); ?>
        </a>
    </label>
    <?php
}

// Inject scripts on frontend only
add_action('wp_enqueue_scripts', 'clixtell_inject_scripts');
function clixtell_inject_scripts() {
    $options = get_option(CLIXTELL_OPTION_NAME, array('dynamic_tracking' => '0'));
    $dynamic_tracking_enabled = !empty($options['dynamic_tracking']) && $options['dynamic_tracking'] === '1';

    if ($dynamic_tracking_enabled) {
        wp_enqueue_script(
            'clixtell-dynamic-phones',
            'https://app.clixtell.com/scripts/dynamicphones.js',
            array(),
            CLIXTELL_PLUGIN_VERSION,
            false
        );
    }

    wp_enqueue_script(
        'clixtell-tracking',
        'https://scripts.clixtell.com/track.js',
        array(),
        CLIXTELL_PLUGIN_VERSION,
        true
    );
}

// Activation hook
register_activation_hook(__FILE__, 'clixtell_plugin_activate');
function clixtell_plugin_activate() {
    $defaults = array(
        'dynamic_tracking' => '1', // change to '0' if you want OFF by default
    );

    $existing = get_option(CLIXTELL_OPTION_NAME, null);
    if ($existing === null) {
        add_option(CLIXTELL_OPTION_NAME, $defaults);
        return;
    }

    if (!is_array($existing)) {
        // Defensive: if an old version stored a scalar, replace with defaults.
        update_option(CLIXTELL_OPTION_NAME, $defaults);
        return;
    }

    // Merge defaults without overwriting existing keys
    update_option(CLIXTELL_OPTION_NAME, array_merge($defaults, $existing));
}