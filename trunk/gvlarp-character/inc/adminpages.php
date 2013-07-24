<?php

require_once GVLARP_CHARACTER_URL . 'inc/adminclasses.php';

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

/* Admin pages still to add
-----------------------------------------------------------------
Generation (for costs)
Characters
Players
Cost Models
Skills
*/

/* WORDPRESS TOOLBAR 
----------------------------------------------------------------- */
function toolbar_link_gvadmin( $wp_admin_bar ) {

	if ( current_user_can( 'manage_options' ) )  {
		$args = array(
			'id'    => 'gvcharacters',
			'title' => 'Characters',
			/* 'href'  => admin_url('admin.php?page=gvcharacter-plugin'), */
			'meta'  => array( 'class' => 'my-toolbar-page' )
		);
		$wp_admin_bar->add_node( $args );
		
		/* 		$args = array(
			'id'    => 'gvdata',
			'title' => 'Data Tables',
			'href'  => admin_url('admin.php?page=gvcharacter-data'),
			'parent' => 'gvcharacters',
			'meta'  => array( 'class' => 'my-toolbar-page' )
		);*/

		$args = array(
			'id'    => 'gvdata',
			'title' => 'Background',
			'href'  => admin_url('admin.php?page=gvcharacter-bg'),
			'parent' => 'gvcharacters',
			'meta'  => array( 'class' => 'my-toolbar-page' )
		); 
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'id'    => 'gvconfig',
			'title' => 'Configuration',
			'href'  => admin_url('admin.php?page=gvcharacter-config'),
			'parent' => 'gvcharacters',
			'meta'  => array( 'class' => 'my-toolbar-page' )
		);
		$wp_admin_bar->add_node( $args );
	}
}
add_action( 'admin_bar_menu', 'toolbar_link_gvadmin', 999 );


/* ADMIN MENUS
----------------------------------------------------------------- */

function register_character_menu() {
	add_menu_page( "Character Plugin Options", "Characters", "manage_options", "gvcharacter-plugin", "character_options");
	add_submenu_page( "gvcharacter-plugin", "Database Tables", "Data", "manage_options", "gvcharacter-data", "character_datatables" );  
	add_submenu_page( "gvcharacter-plugin", "Backgrounds", "Backgrounds", "manage_options", "gvcharacter-bg", "character_backgrounds" );  
	add_submenu_page( "gvcharacter-plugin", "Configuration", "Configuration", "manage_options", "gvcharacter-config", "character_config" );  
}

function character_options() {


}

function character_config() {
	global $wpdb;

	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
			
	?>
	<div class="wrap">
		<h2>Configuration</h2>
		<h3>Options</h3>
		<?php 
			$inputsfail = 0;
			if (isset($_REQUEST['save_options']) ) {
				/* Validate entries */
				if (!empty($_REQUEST['discount'])) {
					if ( ($_REQUEST['discount']+0) == 0) {
						$inputsfail = "Invalid value of discount";
					}
				}
			}
			if (!$inputsfail) {
				if (isset($_REQUEST['save_options'])) {
					$dataarray = array (
						'PROFILE_LINK' => $_REQUEST['profile'],
						'PLACEHOLDER_IMAGE' => $_REQUEST['placeholder'],
						'CLAN_DISCIPLINE_DISCOUNT' => $_REQUEST['discount']
					);
					
					$result = $wpdb->update(GVLARP_TABLE_PREFIX . "CONFIG",
						$dataarray,
						array (
							'ID' => 1
						)
					);		
					
					if ($result) 
						echo "<p style='color:green'>Updated configuration options</p>";
					else if ($result === 0) 
						echo "<p style='color:orange'>No updates made to options</p>";
					else {
						$wpdb->print_error();
						echo "<p style='color:red'>Could not update options</p>";
					}
					
				}
				$sql = "select * from " . GVLARP_TABLE_PREFIX . "CONFIG;";
				$options = $wpdb->get_results($wpdb->prepare($sql));
			} else {
				echo "<p style='color:red'>Could not save options: $inputsfail</p>";
				$options[0]->PROFILE_LINK = $_REQUEST['profile'];
				$options[0]->PLACEHOLDER_IMAGE = $_REQUEST['placeholder'];
				$options[0]->CLAN_DISCIPLINE_DISCOUNT = $_REQUEST['discount'];
			}
		?>

		<form id='options_form' method='post'>
			<table>
			<tr>
				<td>URL to Character Profile</td>
				<td><input type="text" name="profile" value="<?php print $options[0]->PROFILE_LINK; ?>" size=60 /></td>
				<td>Location of profile page with the character summary.</td>
			</tr><tr>
				<td>URL to Profile Placeholder image</td>
				<td><input type="text" name="placeholder" value="<?php print $options[0]->PLACEHOLDER_IMAGE; ?>" size=60 /></td>
				<td>This image is used in place of a character portait on the profile page.</td>
			</tr><tr>
				<td>Discount for Clan Disciplines</td>
				<td><input type="text" name="discount" value="<?php print $options[0]->CLAN_DISCIPLINE_DISCOUNT; ?>" size=5 /></td>
				<td>Integer values will be applied to Discipline costs as a flat discount.  A decimal value will
					be applied to the cost as a ratio.</td>
			</tr>
			</table>
			<input type="submit" name="save_options" class="button-primary" value="Save Options" />
		</form>
		
		<h3>Page Links</h3>
		<?php 
			if (isset($_REQUEST['save_st_links'])) {
				for ($i=0; $i<$_REQUEST['linecount']; $i++) {
					if ($_REQUEST['selectpage' . $i] == "0")
						$link = $_REQUEST['link' . $i];
					else if ($_REQUEST['selectpage' . $i] == "gvnewpage") {
					
						/* check if page with name $_REQUEST['value' . $i] exists */
					
						$my_page = array(
							  'post_status'           => 'publish', 
							  'post_type'             => 'page',
							  'comment_status'		  => 'closed',
							  'post_name'			  => $_REQUEST['value' . $i],
							  'post_title'			  => $_REQUEST['link' . $i]
						);

						// Insert the post into the database
						$pageid = wp_insert_post( $my_page );
						
						$link = "/" . get_page_uri($pageid);
					}
					else
						$link = $_REQUEST['selectpage' . $i];
								
					$dataarray = array (
						'ORDERING' => $_REQUEST['order' . $i],
						'LINK' => $link
					);
					
					$result = $wpdb->update(GVLARP_TABLE_PREFIX . "ST_LINK",
						$dataarray,
						array (
							'ID' => $_REQUEST['id' . $i]
						)
					);
					
					if ($result) 
						echo "<p style='color:green'>Updated {$_REQUEST['value' . $i]}</p>";
					else if ($result === 0) 
						echo "<p style='color:orange'>No updates made to {$_REQUEST['value' . $i]}</p>";
					else {
						$wpdb->print_error();
						echo "<p style='color:red'>Could not update {$_REQUEST['value' . $i]} ({$_REQUEST['id' . $i]})</p>";
					}
			
				}
			}
			$sql = "select * from " . GVLARP_TABLE_PREFIX . "ST_LINK;";
			$stlinks = $wpdb->get_results($wpdb->prepare($sql));
			
			$args = array(
				'sort_order' => 'ASC',
				'sort_column' => 'post_title',
				'hierarchical' => 0,
				'exclude' => '',
				'include' => '',
				'meta_key' => '',
				'meta_value' => '',
				'authors' => '',
				'child_of' => 0,
				'parent' => -1,
				'exclude_tree' => '',
				'number' => '',
				'offset' => 0,
				'post_type' => 'page',
				'post_status' => 'publish'
			); 
			$pages = get_pages($args);
		?>
		
		<form id='ST_Links_form' method='post'>
			<input type="hidden" name="linecount" value="<?php print count($stlinks); ?>" />
			<table>
				<tr><th>List Order</th><th>Name</th><th>Description</th><th>Select Page</th><th>Specify Link or New Page name</th></tr>
			<?php
				$i = 0;
				foreach ($stlinks as $stlink) {
					
					?>
					<tr>
						<td><input type="hidden" name="id<?php print $i ?>" value="<?php print $stlink->ID; ?>" />
							<input type="text" name="order<?php print $i; ?>" value="<?php print $stlink->ORDERING; ?>" size=5 /></td>
						<td><input type="hidden" name="value<?php print $i ?>" value="<?php print $stlink->VALUE; ?>" />
							<?php print $stlink->VALUE; ?></td>
						<td><input type="hidden" name="desc<?php print $i ?>" value="<?php print $stlink->DESCRIPTION; ?>" />
							<?php print $stlink->DESCRIPTION; ?></td>
						<td>
							<select name="selectpage<?php print $i; ?>">
							<?php
								$match = 0;
								foreach ( $pages as $page ) {
									if ('/' . get_page_uri( $page->ID ) == $stlink->LINK)
										$match = 1;
									echo "<option value='/" . get_page_uri( $page->ID ) . "' ";
									selected('/' . get_page_uri( $page->ID ), $stlink->LINK);
									echo ">{$page->post_title}</option>";
								}								
								echo "<option value='0' ";
								if (!$match)
									echo "selected";
								echo ">[Specify Link]</option>";
								echo "<option value='gvnewpage'>[New Page]</option>";
							?>
							</select>
						</td>
						<td><input type="text" name="link<?php print $i; ?>" value="<?php print $stlink->LINK; ?>" size=60 /></td>
					</tr>
					<?php
					$i++;
				}
			?>
			</table>
			<input type="submit" name="save_st_links" class="button-primary" value="Save Links" />
		</form>
		
		<h3>PDF Character Sheet Options</h3>
		<form method="post" action="options.php">
		<?php
		
		settings_fields( 'gvcharacter_options_group' );
		do_settings_sections('gvcharacter_options_group');
		?>
		<table>
			<tr>
				<td>Character Sheet Title</td><td><input type="text" name="gvcharacter_pdf_title" value="<?php echo get_option('gvcharacter_pdf_title'); ?>" size=30 /></td>
				<td>Title Font</td><td><select name="gvcharacter_pdf_titlefont">
					<option value="Arial"     <?php if ('Arial' == get_option('gvcharacter_pdf_titlefont')) echo "selected='selected'"; ?>>Arial</option>
					<option value="Courier"   <?php if ('Courier' == get_option('gvcharacter_pdf_titlefont')) echo "selected='selected'"; ?>>Courier</option>
					<option value="Helvetica" <?php if ('Helvetica' == get_option('gvcharacter_pdf_titlefont')) echo "selected='selected'"; ?>>Helvetica</option>
					<option value="Times"     <?php if ('Times' == get_option('gvcharacter_pdf_titlefont')) echo "selected='selected'"; ?>>Times New Roman</option>
					</select>
				</td>
				<td>Title Text Colour (#RRGGBB)</td><td><input type="color" name="gvcharacter_pdf_titlecolour" value="<?php echo get_option('gvcharacter_pdf_titlecolour'); ?>" /></td>
			</tr>
			<tr>
				<td>Divider Line Colour (#RRGGBB)</td><td><input type="color" name="gvcharacter_pdf_divcolour" value="<?php echo get_option('gvcharacter_pdf_divcolour'); ?>" /></td>
				<td>Divider Text Colour (#RRGGBB)</td><td><input type="color" name="gvcharacter_pdf_divtextcolour" value="<?php echo get_option('gvcharacter_pdf_divtextcolour'); ?>" /></td>
				<td>Divider Line Width (mm)</td><td><input type="text" name="gvcharacter_pdf_divlinewidth" value="<?php echo get_option('gvcharacter_pdf_divlinewidth'); ?>" size=4 /></td>
			</tr>
			<tr>
				<td>Character Sheet Footer</td><td><input type="text" name="gvcharacter_pdf_footer" value="<?php echo get_option('gvcharacter_pdf_footer'); ?>" size=30 /></td>
				<?php if (class_exists('Imagick')) { ?>
				<td>Dot/Box Colour (#RRGGBB)</td><td><input type="color" name="gvcharacter_pdf_dotcolour" value="<?php echo get_option('gvcharacter_pdf_dotcolour'); ?>" /></td>
				<td>Dot/Box Line Width (mm)</td><td><input type="text" name="gvcharacter_pdf_dotlinewidth" value="<?php echo get_option('gvcharacter_pdf_dotlinewidth'); ?>" size=4 /></td>
			</tr>
				<td colspan = 6>
					<table><tr>
					<td><img width=16 src='<?php echo plugins_url( 'gvlarp-character/images/emptydot.jpg' ); ?>'></td>
					<td><img width=16 src='<?php echo plugins_url( 'gvlarp-character/images/fulldot.jpg' ); ?>'></td>
					<td><img width=16 src='<?php echo plugins_url( 'gvlarp-character/images/box.jpg' ); ?>'></td>
					<td><img width=16 src='<?php echo plugins_url( 'gvlarp-character/images/boxcross1.jpg' ); ?>'></td>
					<td><img width=16 src='<?php echo plugins_url( 'gvlarp-character/images/boxcross2.jpg' ); ?>'></td>
					<td><img width=16 src='<?php echo plugins_url( 'gvlarp-character/images/boxcross3.jpg' ); ?>'></td>
					</tr></table>
				</td>
			<tr>
				
				<?php } else { ?>
				<td colspan=4>&nbsp;</td>
				<?php } ?>
			</tr>
		</table>
		
		<?php submit_button(); ?>
		</form>
		
		<?php
					
		if (class_exists('Imagick')) {
		
			$drawwidth    = 32;
			$drawheight   = 32;
			$drawbgcolour = '#FFFFFF';
			$drawcolour   = get_option('gvcharacter_pdf_dotcolour');
			$drawborder   = get_option('gvcharacter_pdf_dotlinewidth');
			$imagetype    = 'jpg';
			
			if ($drawcolour == '')
				$drawcolour = '#000000';
			if ($drawborder == '')
				$drawcolour = 3;
		
			$image = new Imagick();
			
			$image->newImage($drawwidth, $drawheight, new ImagickPixel($drawbgcolour), $imagetype);
			$draw = new ImagickDraw();
			$draw->setStrokeColor($drawcolour);
			$draw->setStrokeWidth($drawborder);
			$draw->setFillColor($drawbgcolour);
			$draw->circle( ($drawwidth / 2) - 1, $drawheight / 2, $drawwidth / 2, $drawborder);
			$image->drawImage($draw);
			$image->writeImage(GVLARP_CHARACTER_URL . 'images/emptydot.' . $imagetype);
			
			$image->newImage($drawwidth, $drawheight, new ImagickPixel($drawbgcolour), $imagetype);
			$draw = new ImagickDraw();
			$draw->setStrokeColor($drawcolour);
			$draw->setStrokeWidth($drawborder);
			$draw->setFillColor($drawcolour);
			$draw->circle( ($drawwidth / 2) - 1, $drawheight / 2, $drawwidth / 2, $drawborder);
			$image->drawImage($draw);
			$image->writeImage(GVLARP_CHARACTER_URL . 'images/fulldot.' . $imagetype);
			
			$image->newImage($drawwidth, $drawheight, new ImagickPixel($drawbgcolour), $imagetype);
			$draw = new ImagickDraw();
			$draw->setStrokeColor($drawcolour);
			$draw->setStrokeWidth($drawborder);
			$draw->setFillColor($drawbgcolour);
			$draw->rectangle( $drawborder, $drawborder, $drawwidth - $drawborder - 1, $drawheight - $drawborder - 1);
			$image->drawImage($draw);
			$image->writeImage(GVLARP_CHARACTER_URL . 'images/box.' . $imagetype);
			
			/* Add a line */
			$draw = new ImagickDraw();
			$draw->setStrokeColor($drawcolour);
			$draw->setStrokeWidth($drawborder);
			$draw->setFillColor($drawbgcolour);
			$draw->line( $drawborder, $drawborder, $drawwidth - $drawborder - 1, $drawheight - $drawborder - 1);
			$image->drawImage($draw);
			$image->writeImage(GVLARP_CHARACTER_URL . 'images/boxcross1.' . $imagetype);
			/* Add another line */
			$draw = new ImagickDraw();
			$draw->setStrokeColor($drawcolour);
			$draw->setStrokeWidth($drawborder);
			$draw->setFillColor($drawbgcolour);
			$draw->line( $drawborder, $drawheight - $drawborder - 1, $drawwidth - $drawborder - 1, $drawborder);
			$image->drawImage($draw);
			$image->writeImage(GVLARP_CHARACTER_URL . 'images/boxcross2.' . $imagetype);
			/* Add last line */
			$draw = new ImagickDraw();
			$draw->setStrokeColor($drawcolour);
			$draw->setStrokeWidth($drawborder);
			$draw->setFillColor($drawbgcolour);
			$draw->line( $drawborder, $drawheight/2, $drawwidth - $drawborder - 1, $drawheight / 2);
			$image->drawImage($draw);
			$image->writeImage(GVLARP_CHARACTER_URL . 'images/boxcross3.' . $imagetype);
			

		}
	?>
		
	</div>
	<?php
}

function character_datatables() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	?>
	<div class="wrap">
		<h2>Database Tables</h2>
		<script type="text/javascript">
			function tabSwitch(tab) {
				document.getElementById('gv-merits').style.display = 'none';
				document.getElementById('gv-flaws').style.display = 'none';
				document.getElementById('gv-rituals').style.display = 'none';
				document.getElementById('gv-books').style.display = 'none';
				document.getElementById(tab).style.display = '';
				return false;
			}
		</script>
		<div class="gvadmin_nav">
			<ul>
				<li><a href="javascript:void(0);" onclick="tabSwitch('gv-merits');">Merits</a></li>
				<li><a href="javascript:void(0);" onclick="tabSwitch('gv-flaws');">Flaws</a></li>
				<li><a href="javascript:void(0);" onclick="tabSwitch('gv-rituals');">Rituals</a></li>
				<li><a href="javascript:void(0);" onclick="tabSwitch('gv-books');">Sourcebooks</a></li>
			</ul>
		</div>
		<!-- <p>tab: <?php echo $_REQUEST['tab'] ?>, m: <?php tabdisplay("merits"); ?>, f: <?php tabdisplay("flaws"); ?></p> -->
		<div class="gvadmin_content">
			<div id="gv-merits" <?php tabdisplay("merit"); ?>>
				<h1>Merits</h1>
				<?php render_meritflaw_page("merit"); ?>
			</div>
			<div id="gv-flaws" <?php tabdisplay("flaw"); ?>>
				<h1>Flaws</h1>
				<?php render_meritflaw_page("flaw"); ?>
			</div>
			<div id="gv-rituals" <?php tabdisplay("ritual"); ?>>
				<h1>Rituals</h1>
				<?php render_rituals_page(); ?>
			</div>
			<div id="gv-books" <?php tabdisplay("book"); ?>>
				<h1>Sourcebooks</h1>
				<?php render_sourcebook_page(); ?>
			</div>
		</div>

	</div>
	
	<?php
}

function character_backgrounds() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	?>
	<div class="wrap">
		<h2>Backgrounds</h2>
		<script type="text/javascript">
			function tabSwitch(tab) {
				document.getElementById('gv-approve').style.display = 'none';
				document.getElementById('gv-bgdata').style.display = 'none';
				document.getElementById('gv-sectors').style.display = 'none';
				document.getElementById('gv-questions').style.display = 'none';
				document.getElementById(tab).style.display = '';
				return false;
			}
		</script>
		<div class="gvadmin_nav">
			<ul>
				<li><a href="javascript:void(0);" onclick="tabSwitch('gv-approve');">Approvals</a></li>
				<li><a href="javascript:void(0);" onclick="tabSwitch('gv-bgdata');">Background Data</a></li>
				<li><a href="javascript:void(0);" onclick="tabSwitch('gv-questions');">Background Questions</a></li>
				<li><a href="javascript:void(0);" onclick="tabSwitch('gv-sectors');">Sector Data</a></li>
			</ul>
		</div>
		<div class="gvadmin_content">
			<div id="gv-approve" <?php tabdisplay("gvapprove", "gvapprove"); ?>>
				<h1>Extended Background Approvals</h1>
				<?php render_approvals_data(); ?>
			</div>
			<div id="gv-bgdata" <?php tabdisplay("bgdata", "gvapprove"); ?>>
				<h1>Background Data</h1>
				<?php render_background_data(); ?>
			</div>
			<div id="gv-sectors" <?php tabdisplay("sector", "gvapprove"); ?>>
				<h1>Background Sectors</h1>
				<?php render_sector_data(); ?>
			</div>
			<div id="gv-questions" <?php tabdisplay("question", "gvapprove"); ?>>
				<h1>Extended Background Questions</h1>
				<?php render_question_data(); ?>
			</div>
		</div>

	</div>
	
	<?php
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
function render_approvals_data(){
	global $wpdb;

    $testListTable['gvapprove'] = new gvadmin_extbgapproval_table();

	$showform = 0;
	if (!empty($_REQUEST['do_deny'])) {
		/* save denial */
		
		$data = array(
			'DENIED_DETAIL'  => $_REQUEST['gvapprove_denied']
		);
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . $_REQUEST['table'],
			$data,
			array (
				'ID' => $_REQUEST['table_id']
			)
		);
		
		if ($result)
			echo "<p style='color:green'>Denied message saved</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not deny background</p>";
		}
		
		/* switch ($_REQUEST['table']) {
			case 'CHARACTER_BACKGROUND':
				$data = array(
					'DENIED_DETAIL'  => $_REQUEST['gvapprove_denied']
				);
				$result = $wpdb->update(GVLARP_TABLE_PREFIX . "CHARACTER_BACKGROUND",
					$data,
					array (
						'ID' => $_REQUEST['table_id']
					)
				);
				
				if ($result)
					echo "<p style='color:green'>Denied message saved</p>";
				else {
					$wpdb->print_error();
					echo "<p style='color:red'>Could not deny background</p>";
				}
				break;
			case 'CHARACTER_MERIT':
				$data = array(
					'DENIED_DETAIL'  => $_REQUEST['gvapprove_denied']
				);
				$result = $wpdb->update(GVLARP_TABLE_PREFIX . "CHARACTER_MERIT",
					$data,
					array (
						'ID' => $_REQUEST['table_id']
					)
				);
				
				if ($result)
					echo "<p style='color:green'>Denied message saved</p>";
				else {
					$wpdb->print_error();
					echo "<p style='color:red'>Could not deny merit/flaw</p>";
				}
				break;
		} */
		
		$id   = -1;
		$data = array();
	}
	else if (!empty($_REQUEST['action']) && 'string' == gettype($_REQUEST['extbackground']) && $_REQUEST['action'] == 'denyit') {
		
		/* load from database */
		$data = $testListTable['gvapprove']->read_data();
		$id   = $_REQUEST['extbackground'];

		$showform = 1;
	}

	render_approve_form($showform, $id, $data);
	
	$testListTable['gvapprove']->prepare_items();

	

   ?>	

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="approve-filter" method="get">
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="gvapprove" />
 		<?php $testListTable['gvapprove']->display() ?>
	</form>

    <?php
}
function render_question_data(){

    $testListTable['question'] = new gvadmin_questions_table();
	$doaction = question_input_validation();
	
 	if ($doaction == "add-question") {
		$testListTable['question']->add_question($_REQUEST['question_title'], $_REQUEST['question_order'], 
												$_REQUEST['question_group'], $_REQUEST['question_question']);
	}
	if ($doaction == "save-edit-question") { 
		$testListTable['question']->edit_question($_REQUEST['question_id'], $_REQUEST['question_title'], $_REQUEST['question_order'], 
												$_REQUEST['question_group'], $_REQUEST['question_question']);
	}

	render_question_add_form($doaction); 
	
	$testListTable['question']->prepare_items();
   ?>	

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="question-filter" method="get">
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="question" />
 		<?php $testListTable['question']->display() ?>
	</form>

    <?php
}
function render_sector_data(){

    $testListTable['sector'] = new gvadmin_sectors_table();
	$doaction = sector_input_validation();
	
 	if ($doaction == "add-sector") {
		$testListTable['sector']->add_sector($_REQUEST['sector_name'], $_REQUEST['sector_desc'], $_REQUEST['sector_visible']);
	}
	if ($doaction == "save-edit-sector") { 
		$testListTable['sector']->edit_sector($_REQUEST['sector_id'], $_REQUEST['sector_name'], $_REQUEST['sector_desc'], $_REQUEST['sector_visible']);
	}

	render_sector_add_form($doaction); 
	
	$testListTable['sector']->prepare_items();
   ?>	

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="sector-filter" method="get">
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="sector" />
 		<?php $testListTable['sector']->display() ?>
	</form>

    <?php
}
function render_background_data(){

    $testListTable['bgdata'] = new gvadmin_backgrounds_table();
	$doaction = bgdata_input_validation();
	
 	if ($doaction == "add-bgdata") {
		$testListTable['bgdata']->add_background($_REQUEST['bgdata_name'], $_REQUEST['bgdata_desc'], $_REQUEST['bgdata_group'], 
									$_REQUEST['bgdata_costmodel'], $_REQUEST['bgdata_visible'],
									$_REQUEST['bgdata_hassector'], $_REQUEST['bgdata_question']);
	}
	if ($doaction == "save-edit-bgdata") { 
		$testListTable['bgdata']->edit_background($_REQUEST['bgdata_id'], $_REQUEST['bgdata_name'], $_REQUEST['bgdata_desc'], $_REQUEST['bgdata_group'], 
									$_REQUEST['bgdata_costmodel'], $_REQUEST['bgdata_visible'],
									$_REQUEST['bgdata_hassector'], $_REQUEST['bgdata_question']);
	} 

	render_bgdata_add_form($doaction);
	
	$testListTable['bgdata']->prepare_items();
   ?>	

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="bgdata-filter" method="get">
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="bgdata" />
 		<?php $testListTable['bgdata']->display() ?>
	</form>

    <?php
}


function render_meritflaw_page($type){

    $testListTable[$type] = new gvadmin_meritsflaws_table();
	$doaction = merit_input_validation($type);
	/* echo "<p>Merit action: $doaction</p>"; */
	
	if ($doaction == "add-$type") {
		$testListTable[$type]->add_merit($_REQUEST[$type . '_name'], $_REQUEST[$type . '_group'], $_REQUEST[$type . '_sourcebook'], 
									$_REQUEST[$type . '_page_number'], $_REQUEST[$type . '_cost'], $_REQUEST[$type . '_xp_cost'], 
									$_REQUEST[$type . '_multiple'], $_REQUEST[$type . '_visible'], $_REQUEST[$type . '_desc'],
									$_REQUEST[$type . '_question']);
									
	}
	if ($doaction == "save-edit-$type") { 
		$testListTable[$type]->edit_merit($_REQUEST[$type . '_id'], $_REQUEST[$type . '_name'], $_REQUEST[$type . '_group'], 
									$_REQUEST[$type . '_sourcebook'], $_REQUEST[$type . '_page_number'], $_REQUEST[$type . '_cost'], 
									$_REQUEST[$type . '_xp_cost'], $_REQUEST[$type . '_multiple'], $_REQUEST[$type . '_visible'],
									$_REQUEST[$type . '_desc'], $_REQUEST[$type . '_question']);
	} 
	
	render_meritflaw_add_form($type, $doaction);
	
    $testListTable[$type]->prepare_items($type);

   ?>	

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="<?php print $type ?>-filter" method="get">
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="<?php print $type ?>" />
 		<?php $testListTable[$type]->display() ?>
	</form>

    <?php
}

function render_rituals_page(){

    $testListTable["rituals"] = new gvadmin_rituals_table();
	$doaction = ritual_input_validation();
	
	if ($doaction == "add-ritual") {
		$testListTable["rituals"]->add_ritual($_REQUEST['ritual_name'], $_REQUEST['ritual_desc'], 
			$_REQUEST['ritual_level'], $_REQUEST['ritual_discipline'], $_REQUEST['ritual_dicepool'], 
			$_REQUEST['ritual_difficulty'], $_REQUEST['ritual_cost'], $_REQUEST['ritual_sourcebook'], 
			$_REQUEST['ritual_page_number'], $_REQUEST['ritual_visible']);
									
	}
	if ($doaction == "save-edit-ritual") {
		$testListTable["rituals"]->edit_ritual($_REQUEST['ritual_id'], $_REQUEST['ritual_name'], $_REQUEST['ritual_desc'], 
			$_REQUEST['ritual_level'], $_REQUEST['ritual_discipline'], $_REQUEST['ritual_dicepool'], 
			$_REQUEST['ritual_difficulty'], $_REQUEST['ritual_cost'], $_REQUEST['ritual_sourcebook'], 
			$_REQUEST['ritual_page_number'], $_REQUEST['ritual_visible']);
									
	}

	render_ritual_add_form($doaction);
	$testListTable["rituals"]->prepare_items();
	?>	

	<form id="rituals-filter" method="get">
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="ritual" />
 		<?php $testListTable["rituals"]->display() ?>
	</form>

    <?php
}
function render_sourcebook_page(){

    $testListTable["books"] = new gvadmin_books_table();
	$doaction = book_input_validation();
	
	if ($doaction == "add-book") {
		$testListTable["books"]->add_book($_REQUEST['book_name'], $_REQUEST['book_code'], $_REQUEST['book_visible']);
									
	}
	if ($doaction == "save-edit-book") {
		$testListTable["books"]->edit_book($_REQUEST['book_id'], $_REQUEST['book_name'], $_REQUEST['book_code'], $_REQUEST['book_visible']);
									
	}

	render_book_add_form($doaction);
	$testListTable["books"]->prepare_items();
	?>	

	<form id="books-filter" method="get">
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="book" />
 		<?php $testListTable["books"]->display() ?>
	</form>

    <?php
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

function render_meritflaw_add_form($type, $addaction) {

	global $wpdb;
	
	/* echo "<p>Creating $type form based on action $addaction</p>"; */

	if ('fix-' . $type == $addaction) {
		$id = $_REQUEST['merit'];
		$name = $_REQUEST[$type . '_name'];
		$group = $_REQUEST[$type . '_group'];
		$bookid = $_REQUEST[$type . '_sourcebook'];
		$pagenum = $_REQUEST[$type . '_page_number'];
		$cost = $_REQUEST[$type . '_cost'];
		$xpcost = $_REQUEST[$type . '_xp_cost'];
		$multiple = $_REQUEST[$type . '_multiple'];
		$visible = $_REQUEST[$type . '_visible'];
		$desc = $_REQUEST[$type . '_desc'];
		$question = $_REQUEST[$type . '_question'];
	} else if ('edit-' . $type == $addaction) {
		/* Get values from database */
		$id   = $_REQUEST['merit'];
		
		$sql = "select merit.ID, merit.NAME as NAME, merit.DESCRIPTION as DESCRIPTION, merit.GROUPING as GROUPING,
						merit.COST as COST, merit.XP_COST as XP_COST, merit.MULTIPLE as MULTIPLE,
						books.ID as SOURCEBOOK, merit.PAGE_NUMBER as PAGE_NUMBER,
						merit.VISIBLE as VISIBLE, merit.BACKGROUND_QUESTION
						from " . GVLARP_TABLE_PREFIX . "MERIT merit, " . GVLARP_TABLE_PREFIX . "SOURCE_BOOK books 
						where merit.ID = '$id' and books.ID = merit.SOURCE_BOOK_ID;";
		
		/* echo "<p>$sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql));
		
		/* print_r($data); */
		
		$name = $data[0]->NAME;
		$group = $data[0]->GROUPING;
		$bookid = $data[0]->SOURCEBOOK;
		$pagenum = $data[0]->PAGE_NUMBER;
		$cost = $data[0]->COST;
		$xpcost = $data[0]->XP_COST;
		$multiple = $data[0]->MULTIPLE;
		$visible = $data[0]->VISIBLE;
		$desc = $data[0]->DESCRIPTION;
		$question = $data[0]->BACKGROUND_QUESTION;
		
	} else {
	
		/* defaults */
		$id = 1;
		$name = "";
		$group = "";
		$bookid = 1;
		$pagenum = 1;
		$cost = 0;
		$xpcost = 0;
		$multiple = "N";
		$visible = "Y";
		$desc = "";
		$question = "";
	}

	$booklist = get_booknames();

	?>
	<form id="new-<?php print $type; ?>" method="post">
		<input type="hidden" name="<?php print $type; ?>_id" value="<?php print $id; ?>"/>
		<input type="hidden" name="tab" value="<?php print $type; ?>" />
		<table>
		<tr>
			<td><?php print ucfirst($type); ?> Name:  </td><td><input type="text" name="<?php print $type; ?>_name" value="<?php print $name; ?>" size=20/></td> <!-- check sizes -->
			<td>Grouping:   </td><td><input type="text" name="<?php print $type; ?>_group" value="<?php print $group; ?>" size=20/></td>
			<td>Sourcebook: </td><td>
				<select name="<?php print $type; ?>_sourcebook">
					<?php
						foreach ($booklist as $book) {
							print "<option value='{$book->ID}' ";
							($book->ID == $bookid) ? print "selected" : print "";
							echo ">{$book->NAME}</option>";
						}
					?>
				</select>
			</td>
			<td>Page Number: </td><td><input type="number" name="<?php print $type; ?>_page_number" value="<?php print $pagenum; ?>" /></td>
		</tr>
		<tr>
			<td>Freebie Point Cost: </td><td><input type="number" name="<?php print $type; ?>_cost" value="<?php print $cost; ?>" /></td>
			<td>Experience Cost: </td><td><input type="number" name="<?php print $type; ?>_xp_cost" value="<?php print $xpcost; ?>" /></td>
			<td>Multiple?: </td><td>
				<select name="<?php print $type; ?>_multiple">
					<option value="N" <?php selected($multiple, "N"); ?>>No</option>
					<option value="Y" <?php selected($multiple, "Y"); ?>>Yes</option>
				</select>
			</td>
			<td>Visible to Players: </td><td>
				<select name="<?php print $type; ?>_visible">
					<option value="N" <?php selected($visible, "N"); ?>>No</option>
					<option value="Y" <?php selected($visible, "Y"); ?>>Yes</option>
				</select></td>
		</tr>
		<tr>
			<td>Description: </td><td colspan=3><input type="text" name="<?php print $type; ?>_desc" value="<?php print $desc; ?>" size=50/></td>
			<td>Extended Background Question: </td><td colspan=3><input type="text" name="<?php print $type; ?>_question" value="<?php print $question; ?>" size=50/></td>
		</tr>
		</table>
		<input type="submit" name="do_add_<?php print $type; ?>" class="button-primary" value="Save <?php print ucfirst($type); ?>" />
	</form>
	
	<?php
}
function render_ritual_add_form($addaction) {

	global $wpdb;
	
	$type = "ritual";
	
	/* echo "<p>Creating ritual form based on action $addaction</p>"; */

	if ('fix-' . $type == $addaction) {
		$id = $_REQUEST['ritual'];
		$name = $_REQUEST[$type . '_name'];

		$bookid = $_REQUEST[$type . '_sourcebook'];
		$pagenum = $_REQUEST[$type . '_page_number'];
		$cost = $_REQUEST[$type . '_cost'];
		$visible = $_REQUEST[$type . '_visible'];
		$desc = $_REQUEST[$type . '_desc'];
	
		$level = $_REQUEST[$type . '_level'];
		$disciplineid = $_REQUEST[$type . '_discipline'];
		$dicepool = $_REQUEST[$type . '_dicepool'];
		$diff = $_REQUEST[$type . '_difficulty'];
		
	} else if ('edit-' . $type == $addaction) {
		/* Get values from database */
		$id   = $_REQUEST['ritual'];
		
		$sql = "select ritual.ID, ritual.NAME, ritual.LEVEL, ritual.DISCIPLINE_ID as DISCIPLINE, ritual.DICE_POOL,
					ritual.COST, ritual.DIFFICULTY, ritual.SOURCE_BOOK_ID as SOURCEBOOK, ritual.PAGE_NUMBER, 
					ritual.VISIBLE, ritual.DESCRIPTION
				from " . GVLARP_TABLE_PREFIX . "RITUAL as ritual, 
					" . GVLARP_TABLE_PREFIX . "SOURCE_BOOK books,
					" . GVLARP_TABLE_PREFIX . "DISCIPLINE as discipline
				where ritual.DISCIPLINE_ID = discipline.ID and
					ritual.SOURCE_BOOK_ID = books.ID and
					ritual.ID = '$id';";
		
		/* echo "<p>$sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql));
		
		/* print_r($data); */
		
		$name = $data[0]->NAME;
		$desc = $data[0]->DESCRIPTION;
		$level = $data[0]->LEVEL;
		$disciplineid = $data[0]->DISCIPLINE;
		$dicepool = $data[0]->DICE_POOL;
		$cost = $data[0]->COST;
		$diff = $data[0]->DIFFICULTY;
		$bookid = $data[0]->SOURCEBOOK;
		$pagenum = $data[0]->PAGE_NUMBER;
		$visible = $data[0]->VISIBLE;
		
	} else {
	
		/* defaults */
		$name = "";
		$desc = "";
		$level = 1;
		$disciplineid = 1;
		$dicepool = "Intelligence + Occult";
		$cost = 1;
		$diff = 4;
		$bookid = 1;
		$pagenum = "";
		$visible = "Y";
	}

	$booklist = get_booknames();

	?>
	<form id="new-<?php print $type; ?>" method="post">
		<input type="hidden" name="<?php print $type; ?>_id" value="<?php print $id; ?>"/>
		<input type="hidden" name="tab" value="<?php print $type; ?>" />
		<table>
		<tr>
			<td><?php print ucfirst($type); ?> Name:  </td>
			<td colspan=7><input type="text" name="<?php print $type; ?>_name" value="<?php print $name; ?>" size=60 /></td> <!-- check sizes -->

		</tr>
		<tr>
			<td>Discipline:  </td>
			<td>
				<select name="<?php print $type; ?>_discipline">
					<?php
						foreach (get_disciplines() as $disc) {
							print "<option value='{$disc->ID}' ";
							($disc->ID == $disciplineid) ? print "selected" : print "";
							echo ">{$disc->NAME}</option>";
						}
					?>
				</select>
			</td> 

			<td>Level:  </td>
			<td><input type="text" name="<?php print $type; ?>_level" value="<?php print $level; ?>" size=3 /></td> <!-- check sizes -->

			<td>Sourcebook: </td>
			<td>
				<select name="<?php print $type; ?>_sourcebook">
					<?php
						foreach ($booklist as $book) {
							print "<option value='{$book->ID}' ";
							($book->ID == $bookid) ? print "selected" : print "";
							echo ">{$book->NAME}</option>";
						}
					?>
				</select>
			</td>
			
			<td>Page Number: </td>
			<td><input type="number" name="<?php print $type; ?>_page_number" value="<?php print $pagenum; ?>" size=3 /></td>

		</tr>
		<tr>
			<td>Dicepool: </td><td><input type="text" name="<?php print $type; ?>_dicepool" value="<?php print $dicepool; ?>" /></td>
			<td>Difficulty: </td><td><input type="number" name="<?php print $type; ?>_difficulty" value="<?php print $diff; ?>" size=3 /></td>
			<td>Experience Cost: </td>
			<td><input type="number" name="<?php print $type; ?>_cost" value="<?php print $cost; ?>" size=3 /></td>

			<td>Visible to Players: </td><td>
				<select name="<?php print $type; ?>_visible">
					<option value="N" <?php selected($visible, "N"); ?>>No</option>
					<option value="Y" <?php selected($visible, "Y"); ?>>Yes</option>
				</select></td>
		</tr>
		<tr>
			<td>Description: </td><td colspan=5><input type="text" name="<?php print $type; ?>_desc" value="<?php print $desc; ?>" size=120/></td>
		</tr>
		</table>
		<input type="submit" name="do_add_<?php print $type; ?>" class="button-primary" value="Save <?php print ucfirst($type); ?>" />
	</form>
	
	<?php
}
function render_book_add_form($addaction) {

	global $wpdb;
	
	$type = "book";
	
	/* echo "<p>Creating book form based on action $addaction</p>"; */

	if ('fix-' . $type == $addaction) {
		$id = $_REQUEST['book'];
		$name = $_REQUEST[$type . '_name'];

		$visible = $_REQUEST[$type . '_visible'];
		$code = $_REQUEST[$type . '_code'];
		
	} else if ('edit-' . $type == $addaction) {
		/* Get values from database */
		$id   = $_REQUEST['book'];
		
		$sql = "select books.ID, books.NAME, books.CODE, books.VISIBLE
				from " . GVLARP_TABLE_PREFIX . "SOURCE_BOOK books
				where books.ID = '$id';";
		
		/* echo "<p>$sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql));
		
		/* print_r($data); */
		
		$name = $data[0]->NAME;
		$code = $data[0]->CODE;
		$visible = $data[0]->VISIBLE;
		
	} else {
	
		/* defaults */
		$name = "";
		$code = "";
		$visible = "Y";
	}

	?>
	<form id="new-<?php print $type; ?>" method="post">
		<input type="hidden" name="<?php print $type; ?>_id" value="<?php print $id; ?>"/>
		<input type="hidden" name="tab" value="<?php print $type; ?>" />
		<table style='width:500px'>
		<tr>
			<td><?php print ucfirst($type); ?> Code:  </td>
			<td><input type="text" name="<?php print $type; ?>_code" value="<?php print $code; ?>" size=16 /></td> <!-- check sizes -->
		</tr>
		<tr>
			<td><?php print ucfirst($type); ?> Name:  </td>
			<td><input type="text" name="<?php print $type; ?>_name" value="<?php print $name; ?>" size=60 /></td> <!-- check sizes -->
		</tr>
		<tr>
			<td>Visible to Players: </td><td>
				<select name="<?php print $type; ?>_visible">
					<option value="N" <?php selected($visible, "N"); ?>>No</option>
					<option value="Y" <?php selected($visible, "Y"); ?>>Yes</option>
				</select></td>

		</tr>
		</table>
		<input type="submit" name="do_add_<?php print $type; ?>" class="button-primary" value="Save <?php print ucfirst($type); ?>" />
	</form>
	
	<?php
}
function render_bgdata_add_form($addaction) {

	global $wpdb;
	
	$type = "bgdata";
	
	/* echo "<p>Creating book form based on action $addaction</p>"; */

	if ('fix-' . $type == $addaction) {
		$id = $_REQUEST['background'];
		$name = $_REQUEST[$type . '_name'];

		$visible = $_REQUEST[$type . '_visible'];
		$desc = $_REQUEST[$type . '_desc'];
		$group = $_REQUEST[$type . '_group'];
		$costmodel_id = $_REQUEST[$type . '_costmodel'];
		$has_sector = $_REQUEST[$type . '_hassector'];
		$bgquestion = $_REQUEST[$type . '_question'];
		
	} else if ('edit-' . $type == $addaction) {
		/* Get values from database */
		$id   = $_REQUEST['background'];
		
		$sql = "select *
				from " . GVLARP_TABLE_PREFIX . "BACKGROUND 
				where ID = '$id';";
		
		/* echo "<p>$sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql));
		
		$name = $data[0]->NAME;
		$group = $data[0]->GROUPING;
		$costmodel_id = $data[0]->COST_MODEL_ID;
		$desc = $data[0]->DESCRIPTION;
		$visible = $data[0]->VISIBLE;
		$has_sector = $data[0]->HAS_SECTOR;
		$bgquestion = $data[0]->BACKGROUND_QUESTION;
		
	} else {
	
		/* defaults */
		$name = "";
		$group = "";
		$costmodel_id = 0;
		$desc = "";
		$visible = "Y";
		$has_sector = "N";
		$bgquestion = "";
	} 

	?>
	<form id="new-<?php print $type; ?>" method="post">
		<input type="hidden" name="<?php print $type; ?>_id" value="<?php print $id; ?>"/>
		<input type="hidden" name="tab" value="<?php print $type; ?>" />
		<table style='width:500px'>
		<tr>
			<td>Name:  </td>
			<td><input type="text" name="<?php print $type; ?>_name" value="<?php print $name; ?>" size=20 /></td>
		
			<td>Grouping:  </td>
			<td><input type="text" name="<?php print $type; ?>_group" value="<?php print $group; ?>" size=20 /></td>
		
			<td>Cost Model:  </td>
			<td>
				<select name="<?php print $type; ?>_costmodel">
					<?php
						print "<option value='0' ";
						selected($costmodel->ID, $costmodel_id);
						echo ">[Select]</option>";
						
						foreach (get_costmodels() as $costmodel) {
							print "<option value='{$costmodel->ID}' ";
							selected($costmodel->ID, $costmodel_id);
							echo ">{$costmodel->NAME}</option>";
						}
					?>
				</select></td>
			
			
			</td>
			<td>Visible to Players: </td><td>
				<select name="<?php print $type; ?>_visible">
					<option value="N" <?php selected($visible, "N"); ?>>No</option>
					<option value="Y" <?php selected($visible, "Y"); ?>>Yes</option>
				</select></td>
		
		</tr>
		<tr>
			<td>Has a Sector: </td><td>
				<select name="<?php print $type; ?>_hassector">
					<option value="N" <?php selected($has_sector, "N"); ?>>No</option>
					<option value="Y" <?php selected($has_sector, "Y"); ?>>Yes</option>
				</select></td>
				
			<td>Description:  </td>
			<td colspan=5><input type="text" name="<?php print $type; ?>_desc" value="<?php print $desc; ?>" size=90 /></td> <!-- check sizes -->

		</tr>
		<tr>
			<td colspan=8>Extended Background question (leave blank to exclude background from Extended Backgrounds):  </td>
		</tr>
		<tr>
			<td colspan=8>
				<textarea name="<?php print $type; ?>_question" rows="2" cols="100"><?php print $bgquestion; ?></textarea>
			</td>
		</tr>
		</table>
		<input type="submit" name="do_add_<?php print $type; ?>" class="button-primary" value="Save Background" />
	</form>
	
	<?php
}
function render_sector_add_form($addaction) {

	global $wpdb;
	
	$type = "sector";
	
	/* echo "<p>Creating sector form based on action $addaction</p>"; */

	if ('fix-' . $type == $addaction) {
		$id      = $_REQUEST['sector'];
		$name    = $_REQUEST[$type . '_name'];
		$visible = $_REQUEST[$type . '_visible'];
		$desc    = $_REQUEST[$type . '_desc'];
		
	} else if ('edit-' . $type == $addaction) {
		/* Get values from database */
		$id   = $_REQUEST['sector'];
		
		$sql = "select *
				from " . GVLARP_TABLE_PREFIX . "SECTOR 
				where ID = '$id';";
		
		/* echo "<p>$sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql));
		
		$name = $data[0]->NAME;
		$desc = $data[0]->DESCRIPTION;
		$visible = $data[0]->VISIBLE;
		
	} else {
	
		/* defaults */
		$name = "";
		$desc = "";
		$visible = "Y";
	} 

	?>
	<form id="new-<?php print $type; ?>" method="post">
		<input type="hidden" name="<?php print $type; ?>_id" value="<?php print $id; ?>"/>
		<input type="hidden" name="tab" value="<?php print $type; ?>" />
		<table style='width:500px'>
		<tr>
			<td>Name:  </td>
			<td><input type="text" name="<?php print $type; ?>_name" value="<?php print $name; ?>" size=20 /></td>
		
			<td>Visible to Players: </td><td>
				<select name="<?php print $type; ?>_visible">
					<option value="N" <?php selected($visible, "N"); ?>>No</option>
					<option value="Y" <?php selected($visible, "Y"); ?>>Yes</option>
				</select></td>
		
		</tr>
		<tr>
		
			<td>Description:  </td>
			<td colspan=3><input type="text" name="<?php print $type; ?>_desc" value="<?php print $desc; ?>" size=100 /></td> <!-- check sizes -->

		</tr>
		</table>
		<input type="submit" name="do_add_<?php print $type; ?>" class="button-primary" value="Save Sector" />
	</form>
	
	<?php
}
function render_question_add_form($addaction) {

	global $wpdb;
	
	$type = "question";
	
	/* echo "<p>Creating question form based on action $addaction</p>"; */

	if ('fix-' . $type == $addaction) {
		$id      = $_REQUEST['question'];
		$title   = $_REQUEST[$type . '_title'];
		$order   = $_REQUEST[$type . '_order'];
		$group   = $_REQUEST[$type . '_group'];
		$question = $_REQUEST[$type . '_question'];
		
	} else if ('edit-' . $type == $addaction) {
		/* Get values from database */
		$id   = $_REQUEST['question'];
		
		$sql = "select *
				from " . GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND 
				where ID = '$id';";
		
		/* echo "<p>$sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql));
		
		$title   = $data[0]->TITLE;
		$order   = $data[0]->ORDERING;
		$group   = $data[0]->GROUPING;
		$question = $data[0]->BACKGROUND_QUESTION;
		
	} else {
	
		$sql = "select * from " . GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND;";
		$order = count($wpdb->get_results($wpdb->prepare($sql))) + 1;
	
		/* defaults */
		$title   = "";
		$group   = "";
		$question = "";
	} 

	?>
	<form id="new-<?php print $type; ?>" method="post">
		<input type="hidden" name="<?php print $type; ?>_id" value="<?php print $id; ?>"/>
		<input type="hidden" name="tab" value="<?php print $type; ?>" />
		<table style='width:500px'>
		<tr>
			<td>Title:  </td>
			<td colspan=3><input type="text" name="<?php print $type; ?>_title" value="<?php print $title; ?>" size=60 /></td>
		

			
		</tr>
		<tr>
			<td>Question Order: </td><td>
			<input type="text" name="<?php print $type; ?>_order" value="<?php print $order; ?>" size=4 /></td>
			<td>Group: </td>
			<td><input type="text" name="<?php print $type; ?>_group" value="<?php print $group; ?>" size=30 /></td>
		</tr>
		<tr>
		
			<td>Question:  </td>
			<td colspan=3>
				<textarea name="<?php print $type; ?>_question" " rows="2" cols="100" ><?php print $question; ?></textarea>
			</td> 

		</tr>
		</table>
		<input type="submit" name="do_add_<?php print $type; ?>" class="button-primary" value="Save Question" />
	</form>
	
	<?php
}
function render_approve_form($showform, $id, $data) {
	
	$type = "gvapprove";
	
	if ($showform) {
		
		/* load from database */
		$table   = $data[$id]['TABLE'];
		$pending = $data[$id]['DESCRIPTION'];
		$tableid = $data[$id]['TABLE.ID'];
		$denied = "";	
	}

	
	if ($showform) {
	?>
	<form id="new-<?php print $type; ?>" method="post">
		<input type="hidden" name="table_id" value="<?php print $tableid; ?>" />
		<input type="hidden" name="table"    value="<?php print $table; ?>"/>
		<input type="hidden" name="tab"      value="<?php print $type; ?>" />
		<input type="hidden" name="extbackground" value="<?php print $id; ?>" />
		<table style='width:500px'>
		<tr>
			<td>Description: </td><td><?php print $pending; ?></td>
		</tr>
		<tr>
			<td>Denied Reason:  </td>
			<td><textarea name="<?php print $type; ?>_denied"><?php print $denied; ?></textarea></td>
		</tr>
		</table>
		<input type="submit" name="do_deny" class="button-primary" value="Deny" />
	</form>
	
	<?php
	}
}

function merit_input_validation($type) {

	if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && $_REQUEST['tab'] == $type)
		$doaction = "edit-$type";
		
	/* echo "<p>Requested action: " . $_REQUEST['action'] . ", " . $type . "_name: " . $_REQUEST[$type . '_name']; */
			
	if (!empty($_REQUEST[$type . '_name'])){

		if ($doaction == "edit-$type")
			$doaction = "save-edit-$type";
		else
			$doaction = "add-$type";
			
		/* Input Validation */
		if (empty($_REQUEST[$type . '_group']) || $_REQUEST[$type . '_group'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Name of group is missing</p>";
		}
		if (empty($_REQUEST[$type . '_desc']) || $_REQUEST[$type . '_desc'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Description is missing</p>";
		}
		/* Page number is a number */
		if (empty($_REQUEST[$type . '_page_number']) || $_REQUEST[$type . '_page_number'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: sourcebook page number is missing</p>";
		} else if ($_REQUEST[$type . '_page_number'] <= 0) {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Invalid sourcebook page number</p>";
		} 
		
		/* Freebies is greater than 0 */
		if (empty($_REQUEST[$type . '_cost']) || $_REQUEST[$type . '_cost'] == "") {
			echo "<p style='color:orange'>Warning: Freebie point cost is missing. Will save cost as 0.</p>";
		} else if ($_REQUEST[$type . '_cost'] == 0 && $type == "flaw") {
			echo "<p style='color:orange'>Warning: Freebie point cost is 0 and will be saved as a Merit</p>";
		} else if ($type == "merit" && $_REQUEST[$type . '_cost'] < 0) {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Freebie point cost for merits should greater than or equal to 0</p>";
		} else if ($type == "flaw" && $_REQUEST[$type . '_cost'] > 0) {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Freebie point cost for flaws should less than or equal to 0</p>";
		}

		
		/* XP is 0 or greater */
		if ($_REQUEST[$type . '_xp_cost'] < 0) {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Experience point cost should greater than or equal to 0</p>";
		}
		
	}
	
	/* echo "action: $doaction</p>"; */

	return $doaction;
}

function ritual_input_validation() {

	$type = "ritual";

	if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && $_REQUEST['tab'] == $type)
		$doaction = "edit-$type";
		
	/* echo "<p>Requested action: " . $_REQUEST['action'] . ", " . $type . "_name: " . $_REQUEST[$type . '_name']; */
			
	if (!empty($_REQUEST[$type . '_name'])){

		if ($doaction == "edit-$type")
			$doaction = "save-edit-$type";
		else
			$doaction = "add-$type";
			
		/* Input Validation */
		if (empty($_REQUEST[$type . '_desc']) || $_REQUEST[$type . '_desc'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Description is missing</p>";
		}
		
		/* Level is a number greater than 0 */
		if (empty($_REQUEST[$type . '_level']) || $_REQUEST[$type . '_level'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Level is missing</p>";
		} else if ($_REQUEST[$type . '_level'] <= 0) {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Level should be a number greater than 0</p>";
		} 
		/* Dice pool is not empty */
		if (empty($_REQUEST[$type . '_dicepool']) || $_REQUEST[$type . '_dicepool'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Dicepool is missing</p>";
		}
		
		/* Page number is a number */
		if (empty($_REQUEST[$type . '_page_number']) || $_REQUEST[$type . '_page_number'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: sourcebook page number is missing</p>";
		} else if ($_REQUEST[$type . '_page_number'] <= 0) {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Invalid sourcebook page number</p>";
		} 
		
		/* XP is 0 or greater */
		if ($_REQUEST[$type . '_cost'] < 0) {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Experience point cost should greater than or equal to 0</p>";
		}
		
		/* WARN if difficulty isn't level + 3 */
		
	}
	
	/* echo "action: $doaction</p>"; */

	return $doaction;
}

function book_input_validation() {

	$type = "book";

	if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && $_REQUEST['tab'] == $type)
		$doaction = "edit-$type";
		
	/* echo "<p>Requested action: " . $_REQUEST['action'] . ", " . $type . "_name: " . $_REQUEST[$type . '_name']; */
			
	if (!empty($_REQUEST[$type . '_name'])){

		if ($doaction == "edit-$type")
			$doaction = "save-edit-$type";
		else
			$doaction = "add-$type";
			
		/* Input Validation */
		if (empty($_REQUEST[$type . '_code']) || $_REQUEST[$type . '_code'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Book code is missing</p>";
		}
				
	}
	
	/* echo "action: $doaction</p>"; */

	return $doaction;
}
function bgdata_input_validation() {

	$type = "bgdata";

	if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && $_REQUEST['tab'] == $type)
		$doaction = "edit-$type";
		
	/* echo "<p>Requested action: " . $_REQUEST['action'] . ", " . $type . "_name: " . $_REQUEST[$type . '_name']; */
			
	if (!empty($_REQUEST[$type . '_name'])){

		if ($doaction == "edit-$type")
			$doaction = "save-edit-$type";
		else
			$doaction = "add-$type";
			
		/* Input Validation */
		if (empty($_REQUEST[$type . '_desc']) || $_REQUEST[$type . '_desc'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Background Description is missing</p>";
		} 
		if (empty($_REQUEST[$type . '_group']) || $_REQUEST[$type . '_group'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Background Group is missing</p>";
		} 
		if (empty($_REQUEST[$type . '_costmodel']) || $_REQUEST[$type . '_costmodel'] == 0) {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Select Background Cost Model</p>";
		} 
				
	}

	return $doaction;
}
function sector_input_validation() {

	$type = "sector";

	if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && $_REQUEST['tab'] == $type)
		$doaction = "edit-$type";
		
	if (!empty($_REQUEST[$type . '_name'])){

		if ($doaction == "edit-$type")
			$doaction = "save-edit-$type";
		else
			$doaction = "add-$type";
			
		/* Input Validation */
		if (empty($_REQUEST[$type . '_desc']) || $_REQUEST[$type . '_desc'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Sector Description is missing</p>";
		} 
				
	}

	return $doaction;
}

function question_input_validation() {

	$type = "question";

	if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && $_REQUEST['tab'] == $type)
		$doaction = "edit-$type";
		
	if (!empty($_REQUEST[$type . '_title'])){

		if ($doaction == "edit-$type")
			$doaction = "save-edit-$type";
		else
			$doaction = "add-$type";
			
		/* Input Validation */
		if (empty($_REQUEST[$type . '_order']) || $_REQUEST[$type . '_order'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Extended Background question order is missing</p>";
		} else if ($_REQUEST[$type . '_order'] <= 0) {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Extended Background order should be a number greater than 0</p>";
		} 
		if (empty($_REQUEST[$type . '_group']) || $_REQUEST[$type . '_group'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Extended Background group is missing</p>";
		} 
		if (empty($_REQUEST[$type . '_question']) || $_REQUEST[$type . '_question'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Extended Background question is missing</p>";
		} 
				
	}
	
	/* echo "<p>Doing action $doaction</p>"; */

	return $doaction;
}
?>