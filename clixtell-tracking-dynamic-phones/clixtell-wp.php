<?php
/**
 * Plugin Name: Clixtell Tracking & Dynamic Phones
 * Plugin URI: https://www.clixtell.com
 * Description: Clixtell Integration Plugin
 * Version: 2.2
 * Author: Clixtell
 * License: GPL2
 */

add_action('admin_menu', 'plugin_admin_add_page');
function plugin_admin_add_page() {
	add_options_page('Clixtell', 'Clixtell', 'manage_options', 'clixtell', 'clixtell_options_page');
}

function clixtell_options_page() {
?>
	<div>
		<h2>Clixtell Options</h2>
		This plugin activates advanceed click fraud detection and requires an active Clixtell account. </br> 
		Login to your <a href="https://app.clixtell.com">Clixtell dashboard</a> or Read more at <a href="https://clixtell.com">clixtell.com</a>.
		<form action="options.php" method="post">
			<?php settings_fields('plugin_options'); ?>
			<?php do_settings_sections('plugin'); ?>
			<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
		</form>
	</div>
<?php
}

add_action('admin_init', 'plugin_admin_init');
function plugin_admin_init(){
	register_setting( 'plugin_options', 'plugin_options' );
	add_settings_section('plugin_main', '', '', 'plugin');
	add_settings_field('plugin_text_string', 'Activate Dynamic Call Tracking', 'plugin_setting_string', 'plugin', 'plugin_main'); 
}

function plugin_setting_string() {
	$options = get_option('plugin_options', true);
    $html = '<input type="checkbox" id="checkbox_example" name="plugin_options" value="1"' .checked( 1, $options, false) . '/>';
    $html .= '<label for="checkbox_example">This will activate Dynamic Phone Insertion. <a href="https://support.clixtell.com">Read More</a></label>';

    echo $html;

}

add_action( 'wp_head', 'inject_clixtell_scripts' );

function inject_clixtell_scripts() {
	if (get_option('plugin_options') == "1") {
		wp_enqueue_script( 'clixtell-dynamic-phones', '//app.clixtell.com/scripts/dynamicphones.js', '', '', false );
		wp_enqueue_script( 'clixtell-tracking', '//scripts.clixtell.com/track.js', '', '', true );
	}
	else {
		wp_enqueue_script( 'clixtell-tracking', '//scripts.clixtell.com/track.js', '', '', true );
	}
}

register_activation_hook( __FILE__, 'am_plugin_activate' );
function am_plugin_activate() {
    update_option('plugin_options', true );
}

function my_plugin_update() {
  update_option('plugin_options', true);
}

add_action('upgrade_' . plugin_basename(__FILE__), 'my_plugin_update');

?>
