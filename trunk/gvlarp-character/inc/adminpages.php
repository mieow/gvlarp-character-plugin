<?php

require_once GVLARP_CHARACTER_URL . 'inc/adminpages/characters.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/reports.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/reportclasses.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/backgrounds.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/clans.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/data.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/moredata.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/toolbar.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/config.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/experience.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/enlightenment.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/paths.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/nature.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/domains.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/offices.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/combodisciplines.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/feedingmap.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/players.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/masterpath.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/generation.php';
require_once GVLARP_CHARACTER_URL . 'inc/adminpages/tempstats.php';

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
	register_setting( 'gvcharacter_options_group', 'gvcharacter_xp_bgcolour' );
	register_setting( 'gvcharacter_options_group', 'gvcharacter_xp_dotcolour' );
	register_setting( 'gvcharacter_options_group', 'gvcharacter_xp_dotlinewidth' );

	register_setting( 'feedingmap_options_group', 'feedingmap_google_api' );  // google api key
	register_setting( 'feedingmap_options_group', 'feedingmap_centre_lat' );  // centre point, latitude
	register_setting( 'feedingmap_options_group', 'feedingmap_centre_long' ); // centre point, latitude
	register_setting( 'feedingmap_options_group', 'feedingmap_zoom' );        // zoom
	register_setting( 'feedingmap_options_group', 'feedingmap_map_type' );    // map type

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
	add_submenu_page( "gvcharacter-plugin", "Players",            "Players",       "manage_options", "gvcharacter-player", "character_players" );  
	add_submenu_page( "gvcharacter-plugin", "Database Tables",    "Data Tables",   "manage_options", "gvcharacter-data",   "character_datatables" );  
	add_submenu_page( "gvcharacter-plugin", "Backgrounds",        "Backgrounds",   "manage_options", "gvcharacter-bg",     "character_backgrounds" );  
	add_submenu_page( "gvcharacter-plugin", "XP Approval",        "XP Approval",   "manage_options", "gvcharacter-xp",     "character_experience" );  
	add_submenu_page( "gvcharacter-plugin", "Path Changes",       "Path Changes",  "manage_options", "gvcharacter-paths",  "character_master_path" );  
	add_submenu_page( "gvcharacter-plugin", "Stat Changes",       "Stat Changes",  "manage_options", "gvcharacter-stats",  "character_temp_stats" );  
	add_submenu_page( "gvcharacter-plugin", "Assign XP",          "Assign XP",     "manage_options", "gvcharacter-xpassign",  "character_xp_assign" );  
	add_submenu_page( "gvcharacter-plugin", "Reports",            "Reports",       "manage_options", "gvcharacter-report", "character_reports" );  
	add_submenu_page( "gvcharacter-plugin", "Configuration",      "Configuration", "manage_options", "gvcharacter-config", "character_config" );  
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

function get_tabhighlight($tab){
	if ((isset($_REQUEST['tab']) && $_REQUEST['tab'] == $tab))
		return "class='shown'";
	return "";
}

function get_tablink($tab, $text){
	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'tab', $current_url );
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = add_query_arg('tab', $tab, $current_url);
	$markup = '<a id="gvm-@TAB@" href="@HREF@" @SHOWN@>@TEXT@</a>';
	return str_replace(
		Array('@TAB@','@TEXT@','@SHOWN@', '@HREF@'),
			Array($tab, $text, get_tabhighlight($tab, $default),htmlentities($current_url)),
			$markup
		);
}

/* DISPLAY TABS
-------------------------------------------------- */
function character_datatables() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$config = getConfig();
	?>
	<div class="wrap">
		<h2>Database Tables</h2>
		<div class="gvadmin_nav">
			<ul>
				<li><?php echo get_tablink('stat',   'Attributes and Stats'); ?></li>
				<li><?php echo get_tablink('skill',  'Abilities'); ?></li>
				<li><?php echo get_tablink('merit',  'Merits'); ?></li>
				<li><?php echo get_tablink('flaw',   'Flaws'); ?></li>
				<li><?php echo get_tablink('ritual', 'Rituals'); ?></li>
				<li><?php echo get_tablink('book',   'Sourcebooks'); ?></li>
				<li><?php echo get_tablink('clans',  'Clans'); ?></li>
				<li><?php echo get_tablink('disc',   'Disciplines'); ?></li>
				<li><?php echo get_tablink('bgdata', 'Backgrounds'); ?></li>
				<li><?php echo get_tablink('sector', 'Sectors'); ?></li>
				<li><?php echo get_tablink('question', 'Background Questions'); ?></li>
				<li><?php echo get_tablink('costmodel', 'Cost Models'); ?></li>
				<li><?php echo get_tablink('enlighten', 'Paths of Enlightenment'); ?></li>
				<li><?php echo get_tablink('path',    'Paths of Magik'); ?></li>
				<li><?php if ($config->USE_NATURE_DEMEANOUR == 'Y') echo get_tablink('nature',  'Nature/Demeanour'); ?></li>
				<li><?php echo get_tablink('domain',  'Domains'); ?></li>
				<li><?php echo get_tablink('office',  'Offices'); ?></li>
				<li><?php echo get_tablink('combo',   'Combination Disciplines'); ?></li>
				<li><?php echo get_tablink('generation', 'Generation'); ?></li>
				<li><?php echo get_tablink('mapowner', 'Map Domain Owners'); ?></li>
				<li><?php echo get_tablink('mapdomain','Map Domains'); ?></li>
			</ul>
		</div>
		<div class="gvadmin_content">
		<?php
		
		switch ($_REQUEST['tab']) {
			case 'stat':
				render_stat_page("stat");
				break;
			case 'skill':
				render_skill_page("skill");
				break;
			case 'merit':
				render_meritflaw_page("merit");
				break;
			case 'flaw':
				render_meritflaw_page("flaw");
				break;
			case 'ritual':
				render_rituals_page(); 
				break;
			case 'book':
				render_sourcebook_page();
				break;
			case 'clans':
				render_clan_page(); 
				break;
			case 'disc':
				render_discipline_page();
				break;
			case 'bgdata':
				render_background_data();
				break;
			case 'sector':
				render_sector_data();
				break;
			case 'question':
				render_question_data();
				break;
			case 'costmodel':
				render_costmodel_page("costmodel");
				break;
			case 'enlighten':
				render_enlightenment_page();
				break;
			case 'path':
				render_paths_page();
				break;
			case 'nature':
				render_nature_page();
				break;
			case 'domain':
				render_domain_page();
				break;
			case 'office':
				render_office_page();
				break;
			case 'combo':
				render_combo_page();
				break;
			case 'mapowner':
				render_owner_data();
				break;
			case 'mapdomain':
				render_domain_data();
				break;
			case 'generation':
				render_generation_data();
				break;
			default:
				render_stat_page("stat");
		}
		
		?>
		</div>
	</div>
	
	<?php
}
?>