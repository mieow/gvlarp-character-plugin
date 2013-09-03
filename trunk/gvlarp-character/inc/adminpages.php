<?php

require_once GVLARP_CHARACTER_URL . 'inc/adminpages/reports.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/reportclasses.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/backgrounds.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/clans.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/data.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/toolbar.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/config.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/experience.php';

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
if ( is_admin() ){ // admin actions
	add_action( 'admin_menu', 'register_character_menu' );
	add_action( 'admin_init', 'register_gvlarp_character_settings' );
} else {
	// non-admin enqueues, actions, and filters
}


function admin_css() { 
	wp_enqueue_style('my-admin-style', plugins_url('css/style-admin.css',dirname(__FILE__)));
}
add_action('admin_enqueue_scripts', 'admin_css');


/* OPTIONS SETTINGS 
----------------------------------------------------------------- */
function register_gvlarp_character_settings() {
	global $wp_roles;
	
	register_setting( 'gvcharacter_options_group', 'gvcharacter_pdf_title' );
	register_setting( 'gvcharacter_options_group', 'gvcharacter_pdf_footer' );
	register_setting( 'gvcharacter_options_group', 'gvcharacter_pdf_titlefont' );
	register_setting( 'gvcharacter_options_group', 'gvcharacter_pdf_titlecolour' );
	register_setting( 'gvcharacter_options_group', 'gvcharacter_pdf_divcolour' );
	register_setting( 'gvcharacter_options_group', 'gvcharacter_pdf_divtextcolour' );
	register_setting( 'gvcharacter_options_group', 'gvcharacter_pdf_divlinewidth' );
	register_setting( 'gvcharacter_options_group', 'gvcharacter_pdf_dotcolour' );
	register_setting( 'gvcharacter_options_group', 'gvcharacter_pdf_dotlinewidth' );

	register_setting( 'gvcharacter_options_group', 'gvcharacter_view_bgcolour' );
	register_setting( 'gvcharacter_options_group', 'gvcharacter_view_dotcolour' );
	register_setting( 'gvcharacter_options_group', 'gvcharacter_view_dotlinewidth' );
	register_setting( 'gvcharacter_options_group', 'gvcharacter_pend_bgcolour' );
	register_setting( 'gvcharacter_options_group', 'gvcharacter_pend_dotcolour' );
	register_setting( 'gvcharacter_options_group', 'gvcharacter_pend_dotlinewidth' );

	/* add_settings_section('gv_options_section_pdf', 'PDF Character Sheet', 'gv_options_section_pdf_text', 'gvcharacter-config');
	add_settings_field('gv_pdf_title',     'Character Sheet Title',      'gvcharacter_pdf_input_title',  'gvcharacter-config',    'gv_options_section_pdf');
	add_settings_field('gv_pdf_titlefont', 'Character Sheet Title Font', 'gvcharacter_pdf_input_titlefont', 'gvcharacter-config', 'gv_options_section_pdf');
	*/
}
add_action( 'admin_menu', 'register_gvlarp_character_settings' );

function gv_options_section_pdf_text() {
	echo '<p>General settings for PDF character sheet generation.</p>';
}
function gvcharacter_pdf_input_title() {
	$options = get_option('gvcharacter_options');
	echo "<input id='gv_pdf_title' name='gvcharacter_options[title]' size='40' type='text' value='{$options['title']}' />";
}

function gvcharacter_options_validate($input) {

	global $wp_roles;

	$options = get_option('gvcharacter_plugin_options');
	
	$options['title'] = trim($input['title']);
	
	
	return $options;
}


/* ADMIN MENUS
----------------------------------------------------------------- */

function register_character_menu() {
	add_menu_page( "Character Plugin Options", "Characters", "manage_options", "gvcharacter-plugin", "character_options");
	add_submenu_page( "gvcharacter-plugin", "Database Tables",     "Data",          "manage_options", "gvcharacter-data",   "character_datatables" );  
	add_submenu_page( "gvcharacter-plugin", "Clans & Disciplines", "Clans",         "manage_options", "gvcharacter-clans",  "character_clans" );  
	add_submenu_page( "gvcharacter-plugin", "Backgrounds",         "Backgrounds",   "manage_options", "gvcharacter-bg",     "character_backgrounds" );  
	add_submenu_page( "gvcharacter-plugin", "Reports",             "Reports",       "manage_options", "gvcharacter-report", "character_reports" );  
	add_submenu_page( "gvcharacter-plugin", "Experience",          "Experience",    "manage_options", "gvcharacter-xp",     "character_experience" );  
	add_submenu_page( "gvcharacter-plugin", "Configuration",       "Configuration", "manage_options", "gvcharacter-config", "character_config" );  
}

function character_options() {


}



function tabdisplay($tab, $default="merit") {

	$display = "style='display:none'";

	if (isset($_REQUEST['tab'])) {
		if ($_REQUEST['tab'] == $tab)
			$display = "";
	} else if ($tab == $default) {
		$display = "class=default";
	}
		
	print $display;
		
}


/* DISPLAY TABLES 
-------------------------------------------------- */


function gvmake_filter($sqlresult) {
	
	$keys = array('all');
	$vals = array('All');

	foreach ($sqlresult as $item) {
		if (isset($item->ID) && isset($item->NAME) ) {
			array_push($keys, $item->ID);
			array_push($vals, $item->NAME);
		} 
		else {
			$keylist = array_keys(get_object_vars($item));
			if (count($keylist) == 1) {
				array_push($keys, sanitize_key($item->$keylist[0]));
				array_push($vals, $item->$keylist[0]);
			}
		}
	}
	$outarray = array_combine($keys,$vals);

	return $outarray;
}


?>