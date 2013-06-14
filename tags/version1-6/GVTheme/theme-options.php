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
	add_settings_field('gv_credits',   'Website Credits',   'gv_options_input_credits',   'gvadmin_slug', 'gv_options_section_main');
	
	/* By Role Settings */
	add_settings_section('gv_options_section_roles', 'Role Settings', 'gv_options_section_role_text', 'gvadmin_slug');
	foreach( $wp_roles->role_objects as $role => $roleclass ) {
  		add_settings_field('gv_rolelink_' . $role, $role . " Link Name:", "gv_options_input_rolelink", 'gvadmin_slug', 'gv_options_section_roles', $role);
  		add_settings_field('gv_roleurl_' . $role, $role . " Page URL:", "gv_options_input_roleurl", 'gvadmin_slug', 'gv_options_section_roles', $role);
	}
	
} 
add_action( 'admin_init', 'register_gv_settings' );

function gv_options_section_main_text() {
	echo '<p>General settings for theme.</p>';
}
function gv_options_section_role_text() {
	global $wp_roles;
	
	echo '<p>Role settings for theme.</p><table>';
	
	/* $methods = get_class_methods($wp_roles);
	foreach ( $methods as $method ) {
		echo "<p>Method: $method</p>";
	} 
	$classvars = get_class_vars(get_class($wp_roles));
	foreach ( $classvars as $name => $value ) {
		echo "<p>Var: $name - $value";
		echo "</p>";
	}
	
	foreach( $wp_roles->role_names as $test ) {
		echo "<p>Names: $test</p>";
	}
	foreach( $wp_roles->role_objects as $myarray => $test ) {
		echo "<p>$myarray | " . get_class($test) . "</p>";
	} */



}
function gv_options_input_copyright() {
	$options = get_option('gv_options');
	echo "<input id='gv_copyright' name='gv_options[copyright]' size='40' type='text' value='{$options['copyright']}' />";
}
function gv_options_input_credits() {
	$options = get_option('gv_options');
	echo "<input id='gv_credits' name='gv_options[credits]' size='40' type='text' value='{$options['credits']}' />";
}
function gv_options_input_rolelink($role) {
	$options = get_option('gv_options');
	echo "<input id='gv_rolelink_" . $role , "' name='gv_options[rolelink_" . $role . "]' size='20' type='text' value='{$options['rolelink_' . $role]}' />";
}
function gv_options_input_roleurl($role) {
	$options = get_option('gv_options');
	echo "<input id='gv_roleurl_" . $role , "' name='gv_options[roleurl_" . $role . "]' size='50' type='text' value='{$options['roleurl_' . $role]}' />";
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
	$options['credits'] = trim($input['credits']);
	
	foreach( $wp_roles->role_objects as $role => $roleclass ) {
  		$options['rolelink_' . $role] = trim($input['rolelink_' . $role]);
  		$options['roleurl_' . $role] = trim($input['roleurl_' . $role]);
	}
	
	return $options;
}

?>