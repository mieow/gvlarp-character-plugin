<?php

/* OPTIONS MENU
--------------------------------- */
add_action('admin_menu', 'my_plugin_menu');

function my_plugin_menu() {
	add_theme_page('GVLarp Theme', 'GVTheme', 'manage_options', 'gvadmin_slug', 'gvadmin_function');
}

function register_gv_settings() {
	global $wp_roles;
	
	register_setting( 'gv_theme_options', 'gv_options', 'gv_options_validate' );
	
	/* Main Settings */
	add_settings_section('gv_options_section_main', 'Main Settings', 'gv_options_section_main_text', 'gvadmin_slug');
	add_settings_field('gv_copyright', 'Copyright Notice', 'gv_options_input_copyright', 'gvadmin_slug', 'gv_options_section_main');
	add_settings_field('gv_credits',   'Website Credits',  'gv_options_input_credits',   'gvadmin_slug', 'gv_options_section_main');
	add_settings_field('gv_colours',   'Theme Colour',     'gv_options_input_colour',    'gvadmin_slug', 'gv_options_section_main');
	add_settings_field('gv_gradient',  'Button/Nav Gradient', 'gv_options_input_gradient', 'gvadmin_slug', 'gv_options_section_main');
		
} 
add_action( 'admin_init', 'register_gv_settings' );

function gv_options_section_main_text() {
	echo '<p>General settings for theme.</p>';
}
function gv_options_input_copyright() {
	$options = get_option('gv_options');
	echo "<input id='gv_copyright' name='gv_options[copyright]' size='40' type='text' value='{$options['copyright']}' />";
}
function gv_options_input_credits() {
	$options = get_option('gv_options');
	echo "<input id='gv_credits' name='gv_options[credits]' size='40' type='text' value='{$options['credits']}' />";
}
function gv_options_input_colour() {
	$options = get_option('gv_options');
	echo "<select id='gv_colours' name='gv_options[colours]'>\n";
	echo "<option value='red' " . selected($options['colours'], 'red', false) . ">Red</option>";
	echo "<option value='blue' " . selected($options['colours'], 'blue', false) . ">Blue</option>";
	echo "<option value='green' " . selected($options['colours'], 'green', false) . ">Green</option>";
	echo "</select>";
}
function gv_options_input_gradient() {
	$options = get_option('gv_options');
	echo "<select id='gv_gradient' name='gv_options[gradient]'>\n";
	echo "<option value='flat' " . selected($options['gradient'], 'flat', false) . ">Flat</option>";
	echo "<option value='gradient' " . selected($options['gradient'], 'gradient', false) . ">Gradient</option>";
	echo "</select>";
}


function gvadmin_function() {

	?>
	<div class="wrap">
	<?php screen_icon(); ?>
	<h2>GVTheme Options</h2>
	
	<form method="post" action="options.php">
	
	<?php settings_fields( 'gv_theme_options' ); ?>
	<?php do_settings_sections('gvadmin_slug'); ?>
	
	<?php submit_button(); ?>
	</form>
	</div>
	<?php	
}

function gv_options_validate($input) {

	global $wp_roles;

	$options = get_option('gv_theme_options');
	
	$options['copyright'] = trim($input['copyright']);
	$options['credits']   = trim($input['credits']);
	$options['colours']   = trim($input['colours']);
	$options['gradient']  = trim($input['gradient']);
	
	return $options;
}

?>