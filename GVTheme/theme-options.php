<?php

/* OPTIONS MENU
--------------------------------- */
add_action('admin_menu', 'my_plugin_menu');

function my_plugin_menu() {
	add_theme_page('GVLarp Theme', 'GVTheme', 'manage_options', 'gvadmin_slug', 'gvadmin_function');
}

function register_gv_settings() {

	register_setting( 'gv_theme_options', 'gv_options', 'gv_options_validate' );
	add_settings_section('gv_options_section_main', 'Main Settings', 'gv_options_section_main_text', 'gvadmin_slug');
	
	add_settings_field('gv_copyright', 'Copyright Notice', 'gv_options_input_copyright', 'gvadmin_slug', 'gv_options_section_main');
	add_settings_field('gv_credits',   'Website Credits',   'gv_options_input_credits',   'gvadmin_slug', 'gv_options_section_main');
	
	add_settings_section('gv_options_section_roles', 'Role Settings', 'gv_options_section_role_text', 'gvadmin_slug');
	
} 
add_action( 'admin_init', 'register_gv_settings' );

function gv_options_section_main_text() {
	echo '<p>General settings for theme.</p>';
}
function gv_options_section_role_text() {
	global $wp_roles;
	
	echo '<p>Role settings for theme.</p>';
	
	foreach( $wp_roles->role_names as $role ) {
  		echo '<p>' .$role . '</p>';
	}
}
function gv_options_input_copyright() {
	$options = get_option('gv_options');
	echo "<input id='gv_copyright' name='gv_options[copyright]' size='40' type='text' value='{$options['copyright']}' />";
}
function gv_options_input_credits() {
	$options = get_option('gv_options');
	echo "<input id='gv_credits' name='gv_options[credits]' size='40' type='text' value='{$options['credits']}' />";
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
	$options = get_option('gv_theme_options');
	
	$options['copyright'] = trim($input['copyright']);
	$options['credits'] = trim($input['credits']);
	
	return $options;
}

?>