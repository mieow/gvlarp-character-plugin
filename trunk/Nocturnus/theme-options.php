<?php

/* OPTIONS MENU
--------------------------------- */
add_action('admin_menu', 'nocturnus_menu');

function nocturnus_menu() {
	add_theme_page('Nocturnus Theme', 'Nocturnus', 'manage_options', 'nocturnus_admin_slug', 'nocturnus_admin_function');
}

function register_nocturnus_settings() {
	global $wp_roles;
	
	register_setting( 'nocturnus_options', 'nocturnus_options', 'nocturnus_options_validate' );
	
	/* Main Settings */
	add_settings_section('nocturnus_options_section_main', __('Main Settings', 'nocturnus'), 'nocturnus_options_section_main_text', 'nocturnus_admin_slug');
	add_settings_field('nocturnus_copyright', __('Copyright Notice', 'nocturnus'), 'nocturnus_options_input_copyright', 'nocturnus_admin_slug', 'nocturnus_options_section_main');
	add_settings_field('nocturnus_credits',   __('Website Credits', 'nocturnus'),  'nocturnus_options_input_credits',   'nocturnus_admin_slug', 'nocturnus_options_section_main');
	add_settings_field('nocturnus_colours',   __('Theme Colour', 'nocturnus'),     'nocturnus_options_input_colour',    'nocturnus_admin_slug', 'nocturnus_options_section_main');
	add_settings_field('nocturnus_gradient',  __('Button/Nav Gradient, 'nocturnus')', 'nocturnus_options_input_gradient', 'nocturnus_admin_slug', 'nocturnus_options_section_main');
	add_settings_field('nocturnus_corners',   __('Corners', 'nocturnus'),          'nocturnus_options_input_corners',   'nocturnus_admin_slug', 'nocturnus_options_section_main');
	add_settings_field('nocturnus_sidebar',   __( 'Sidebar Location', 'nocturnus'),'nocturnus_options_input_sidebar',   'nocturnus_admin_slug', 'nocturnus_options_section_main');
		
} 
add_action( 'admin_init', 'register_nocturnus_settings' );

function nocturnus_options_section_main_text() {
	_e('<p>General settings for theme.</p>', 'nocturnus');
}
function nocturnus_options_input_copyright() {
	$options = get_option('nocturnus_options');
	echo "<input id='nocturnus_copyright' name='nocturnus_options[copyright]' size='40' type='text' value='{$options['copyright']}' />";
}
function nocturnus_options_input_credits() {
	$options = get_option('nocturnus_options');
	echo "<input id='nocturnus_credits' name='nocturnus_options[credits]' size='40' type='text' value='{$options['credits']}' />";
}
function nocturnus_options_input_colour() {
	$options = get_option('nocturnus_options');
	echo "<select id='nocturnus_colours' name='nocturnus_options[colours]'>\n";
	echo "<option value='red' "   . selected($options['colours'], 'red', false) . ">"   . __("Red", 'nocturnus')   . "</option>";
	echo "<option value='blue' "  . selected($options['colours'], 'blue', false) . ">"  . __("Blue", 'nocturnus')  . "</option>";
	echo "<option value='green' " . selected($options['colours'], 'green', false) . ">" . __("Green", 'nocturnus') . "</option>";
	echo "</select>";
}
function nocturnus_options_input_gradient() {
	$options = get_option('nocturnus_options');
	echo "<select id='nocturnus_gradient' name='nocturnus_options[gradient]'>\n";
	echo "<option value='flat' " . selected($options['gradient'], 'flat', false) . ">" . __("Flat", 'nocturnus') . "</option>";
	echo "<option value='gradient' " . selected($options['gradient'], 'gradient', false) . ">" . __("Gradient", 'nocturnus') . "</option>";
	echo "</select>";
}
function nocturnus_options_input_corners() {
	$options = get_option('nocturnus_options');
	echo "<select id='nocturnus_corners' name='nocturnus_options[corners]'>\n";
	echo "<option value='round' " . selected($options['corners'], 'round', false) . ">" . __("Round", 'nocturnus') . "</option>";
	echo "<option value='square' " . selected($options['corners'], 'square', false) . ">" . __("Square", 'nocturnus') . "</option>";
	echo "</select>";
}
function nocturnus_options_input_sidebar() {
	$options = get_option('nocturnus_options');
	echo "<select id='nocturnus_corners' name='nocturnus_options[sidebar]'>\n";
	echo "<option value='left' " . selected($options['sidebar'], 'left', false) . ">" . __("Left", 'nocturnus') . "</option>";
	echo "<option value='right' " . selected($options['sidebar'], 'right', false) . ">" . __("Right", 'nocturnus') . "</option>";
	echo "</select>";
}


function nocturnus_admin_function() {

	?>
	<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php _e('Nocturnus Options', 'nocturnus'); ?></h2>
	
	<form method="post" action="options.php">
	
	<?php settings_fields( 'nocturnus_options' ); ?>
	<?php do_settings_sections('nocturnus_admin_slug'); ?>
	
	<?php submit_button(); ?>
	</form>
	</div>
	<?php	
}

function nocturnus_options_validate($input) {

	global $wp_roles;

	$options = get_option('nocturnus_options');
	
	$options['copyright'] = trim($input['copyright']);
	$options['credits']   = trim($input['credits']);
	$options['colours']   = trim($input['colours']);
	$options['gradient']  = trim($input['gradient']);
	$options['corners']   = trim($input['corners']);
	$options['sidebar']   = trim($input['sidebar']);

	return $options;
}

/* THEME CUSTOMISER
--------------------------------- */

add_action( 'customize_register', 'nocturnus_customize_register' );
function nocturnus_customize_register($wp_customize) {

	$wp_customize->add_section( 'nocturnus_custom_theme_options', array(
		'title'          => __('Nocturnus Options', 'nocturnus'),
		'priority'       => 35,
	) );
	$wp_customize->add_setting( 'nocturnus_options[colours]', array(
    'default'        => 'red',
    'type'           => 'option',
    'capability'     => 'edit_theme_options',
	) );
	$wp_customize->add_setting( 'nocturnus_options[gradient]', array(
    'default'        => 'sidebar',
    'type'           => 'option',
    'capability'     => 'edit_theme_options',
	) );
	$wp_customize->add_setting( 'nocturnus_options[corners]', array(
    'default'        => 'round',
    'type'           => 'option',
    'capability'     => 'edit_theme_options',
	) );
	$wp_customize->add_setting( 'nocturnus_options[sidebar]', array(
    'default'        => 'right',
    'type'           => 'option',
    'capability'     => 'edit_theme_options',
	) );

	$wp_customize->add_control( 'nocturnus_options[colours]', array(
		'label'   => __('Theme Colour', 'nocturnus'),
		'section' => 'nocturnus_custom_theme_options',
		'type'    => 'select',
		'choices'    => array(
			'red'   => __('Red', 'nocturnus'),
			'green' => __('Green', 'nocturnus'),
			'blue'  => __('Blue', 'nocturnus'),
			),
	) );
	$wp_customize->add_control( 'nocturnus_options[gradient]', array(
		'label'      => __('Button/Nav Gradient', 'nocturnus'),
		'section'    => 'nocturnus_custom_theme_options',
		'settings'   => 'nocturnus_options[gradient]',
		'type'       => 'radio',
		'choices'    => array(
			'flat'     => __('Flat', 'nocturnus'),
			'gradient' => __('Gradient', 'nocturnus')
			),
	) );
	$wp_customize->add_control( 'nocturnus_options[corners]', array(
		'label'      => __('Corners', 'nocturnus'),
		'section'    => 'nocturnus_custom_theme_options',
		'settings'   => 'nocturnus_options[corners]',
		'type'       => 'radio',
		'choices'    => array(
			 'round'  => __('Round', 'nocturnus'),
			 'square' => __('Square', 'nocturnus')
			),
	) );
	$wp_customize->add_control( 'nocturnus_options[sidebar]', array(
		'label'      => __('Sidebar Location', 'nocturnus'),
		'section'    => 'nocturnus_custom_theme_options',
		'settings'   => 'nocturnus_options[sidebar]',
		'type'       => 'radio',
		'choices'    => array(
			'left'  => __('Left', 'nocturnus'),
			'right' => __('Right', 'nocturnus')
			),
	) );
	
}

?>