<?php
/**
 * Plugin Name: Clixtell Tracking & Dynamic Phones
 * Plugin URI: https://www.clixtell.com
 * Description: Clixtell Integration Plugin
 * Version: 2.4
 * Author: Clixtell
 * License: GPL2
 */

defined('ABSPATH') || exit;

define('CLIXTELL_PLUGIN_VERSION', '2.4');
define('CLIXTELL_OPTION_NAME', 'clixtell_options');

/**
 * 1. MIGRATION LOGIC (Admin only)
 * Runs only when an admin loads WordPress.
 * Handles migration from v2.2 (Scalar) to v2.4 (Array)
 */
add_action('admin_init', 'clixtell_migrate_legacy_data');
function clixtell_migrate_legacy_data() {
    // Get current options
    $new = get_option(CLIXTELL_OPTION_NAME, array());
    if (!is_array($new)) {
        $new = array();
    }

    // STOP if we have already migrated
    if (!empty($new['_migrated_from_22'])) {
        return;
    }

    // Check if OLD v2.2 settings exist
    $old = get_option('plugin_options', null);

    // If old settings are gone/null, assume fresh install or already clean.
    if ($old === null) {
        $new['_migrated_from_22'] = '1';
        update_option(CLIXTELL_OPTION_NAME, $new);
        return;
    }

    // Normalize old v2.2 value
    $was_enabled = ($old === '1' || $old === 1 || $old === true || $old === 'true');

    // Apply migration (2.2 wins)
    $new['dynamic_tracking']  = $was_enabled ? '1' : '0';
    $new['_migrated_from_22'] = '1';

    update_option(CLIXTELL_OPTION_NAME, $new);
}

/**
 * 2. ADMIN MENU
 */
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

/**
 * 3. SETTINGS PAGE UI
 */
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

/**
 * 4. REGISTER SETTINGS
 */
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

/**
 * 5. SANITIZATION
 */
function clixtell_sanitize_options($input) {
    $existing = get_option(CLIXTELL_OPTION_NAME, array());
    $sanitized = is_array($existing) ? $existing : array();

    $sanitized['dynamic_tracking'] =
        (!empty($input['dynamic_tracking']) && (string)$input['dynamic_tracking'] === '1') ? '1' : '0';

    return $sanitized;
}

/**
 * 6. FIELD CALLBACK
 */
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
        <a href="<?php echo esc_url('https://support.clixtell.com'); ?>" target="_blank" rel="noopener noreferrer">
            <?php echo esc_html__('Read More', 'clixtell-tracking'); ?>
        </a>
    </label>
    <?php
}

/**
 * 7. FRONTEND SCRIPTS
 */
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

/**
 * 8. ACTIVATION HOOK (Fresh installs only)
 */
register_activation_hook(__FILE__, 'clixtell_plugin_activate');
function clixtell_plugin_activate() {
    $defaults = array(
        'dynamic_tracking' => '1',
        '_migrated_from_22' => '1'
    );

    $existing = get_option(CLIXTELL_OPTION_NAME, null);

    if ($existing === null) {
        add_option(CLIXTELL_OPTION_NAME, $defaults);
        return;
    }

    if (!is_array($existing)) {
        update_option(CLIXTELL_OPTION_NAME, $defaults);
        return;
    }
}
?>
