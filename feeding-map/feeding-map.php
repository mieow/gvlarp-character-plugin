<?php
    /*  Plugin Name: Vampire Feeding Maps
        Plugin URI: http://plugin.gvlarp.com
        Description: Management of Feeding Maps for Vampire-the Masquerade
        Author: Jane Houston
        Version: 1.0
        Author URI: http://plugin.gvlarp.com
    */

    /*  Copyright 2013 Jane Houston

        This program is free software; you can redistribute it and/or modify
        it under the terms of the GNU General Public License, version 2, as
        published by the Free Software Foundation.

        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with this program; if not, write to the Free Software
        Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
    */

define( 'FEEDINGMAP_PATH', plugin_dir_path(__FILE__) );
define( 'FEEDINGMAP_TABLE_PREFIX', $wpdb->prefix . "feedingmap_" );

require_once FEEDINGMAP_PATH . 'inc/install.php';
require_once FEEDINGMAP_PATH . 'inc/shortcodes.php';
require_once FEEDINGMAP_PATH . 'inc/tables.php';

/* STYLESHEETS
------------------------------------------------------ */
function feedingmap_plugin_style()  
{ 
  wp_register_style( 'feedingmap-style', plugins_url( 'feeding-map/css/style-plugin.css' ) );
  wp_enqueue_style( 'feedingmap-style' );
}
add_action('wp_enqueue_scripts', 'feedingmap_plugin_style');

function feedingmap_admin_css() {
	wp_enqueue_style('feedingmap-admin-style', plugins_url('feeding-map/css/style-admin.css',dirname(__FILE__)));
}
add_action('admin_enqueue_scripts', 'feedingmap_admin_css');

/* JAVASCRIPT
----------------------------------------------------------------- */
function feedingmap_scripts() {
	wp_enqueue_script( 'feedingmap-setup-api', plugins_url('feeding-map/js/googleapi.js',dirname(__FILE__)));
}

add_action( 'wp_enqueue_scripts', 'feedingmap_scripts' );
add_action('admin_enqueue_scripts', 'feedingmap_scripts');

/* ADMIN SETUP
------------------------------------------------------ */
if ( is_admin() ){ // admin actions
	add_action( 'admin_menu', 'register_feedingmap_menu' );
	add_action( 'admin_init', 'register_feedingmap_settings' );
} else {
	// non-admin enqueues, actions, and filters
}

function register_feedingmap_menu() {
	add_options_page('Feeding Map Options', 'Feeding Map', 'manage_options', 'feeding-map', 'feedingmap_page');}

function register_feedingmap_settings() {
	global $wp_roles;
	
	register_setting( 'feedingmap_options_group', 'feedingmap_google_api' );  // google api key
	register_setting( 'feedingmap_options_group', 'feedingmap_centre_lat' );  // centre point, latitude
	register_setting( 'feedingmap_options_group', 'feedingmap_centre_long' ); // centre point, latitude
	register_setting( 'feedingmap_options_group', 'feedingmap_zoom' );        // zoom
	register_setting( 'feedingmap_options_group', 'feedingmap_map_type' );    // map type
	// centre point
	// zoom level
	// google map type (default ROADMAP)

}

/* OPTIONS PAGE
------------------------------------------------------ */
function feedingmap_page() {

	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	?>
	<div class="wrap">
		<h2>Feeding Map Options</h2>
		
		<form method="post" action="options.php">
			<?php
			settings_fields( 'feedingmap_options_group' );
			do_settings_sections('feedingmap_options_group');
			?>	
			
			<table>
			<tr>
				<td><label>Google Maps API Key:</label></td>
				<td><input type="text" name="feedingmap_google_api" value="<?php echo get_option('feedingmap_google_api'); ?>" size=60 /></td>
			</tr>
			<tr>
				<td><label>Centre Point, Latitude:</label></td>
				<td><input type="number" name="feedingmap_centre_lat" value="<?php echo get_option('feedingmap_centre_lat'); ?>" /></td>
			</tr>
			<tr>
				<td><label>Centre Point, Longitude:</label></td>
				<td><input type="number" name="feedingmap_centre_long" value="<?php echo get_option('feedingmap_centre_long'); ?>" /></td>
			</tr>
			<tr>
				<td><label>Map Zoom:</label></td>
				<td><input type="number" name="feedingmap_zoom" value="<?php echo get_option('feedingmap_zoom'); ?>" /></td>
			</tr>
			<tr>
				<td><label>Map Type:</label></td>
				<td>
					<select name="feedingmap_map_type">
						<option value="ROADMAP" <?php selected(get_option('feedingmap_map_type'),"ROADMAP"); ?>>Roadmap</option>
						<option value="SATELLITE" <?php selected(get_option('feedingmap_map_type'),"SATELLITE"); ?>>Satellite</option>
						<option value="HYBRID" <?php selected(get_option('feedingmap_map_type'),"HYBRID"); ?>>Hybrid</option>
						<option value="TERRAIN" <?php selected(get_option('feedingmap_map_type'),"TERRAIN"); ?>>Terrain</option>
					</select>
				</td>
			</tr>
			</table>
			<?php submit_button(); ?>
		
		</form>
		
		<?php echo print_map(""); ?>
		
		<hr />
		
		<h3>Map Key</h3>
		<?php render_owner_data(); ?>
		
		<hr />
		
		<h3>Domains</h3>
		<?php render_domain_data(); ?>
		
	</div>
	
	<?php
}

?>