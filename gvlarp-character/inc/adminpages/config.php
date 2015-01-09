<?php


function vtm_character_config() {
	global $wpdb;

	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
			
	?>
	<div class="wrap">
		<?php 
			$activation_output = get_option('vtm_plugin_error');
			if (isset($activation_output) && $activation_output != "") {
				echo $activation_output;
			}
		?>
		<h2>Configuration</h2>
		<div class="gvadmin_nav">
			<ul>
				<li><?php echo vtm_get_tablink('general',   'General'); ?></li>
				<li><?php echo vtm_get_tablink('pagelinks', 'Page Links'); ?></li>
				<li><?php echo vtm_get_tablink('maps',      'Map Options'); ?></li>
				<li><?php echo vtm_get_tablink('chargen',   'Character Generation'); ?></li>
				<li><?php echo vtm_get_tablink('skinning',  'Skinning'); ?></li>
			</ul>
		</div>
		<div class="gvadmin_content">
		<?php
		
		$tabselect = isset($_REQUEST['tab']) ? $_REQUEST['tab'] : '';
		
		switch ($tabselect) {
			case 'general':
				vtm_render_config_general();
				break;
			case 'pagelinks':
				vtm_render_config_pagelinks();
				break;
			case 'maps':
				vtm_render_config_maps();
				break;
			case 'chargen':
				vtm_render_config_chargen();
				break;
			case 'skinning':
				vtm_render_config_skinning();
				break;
			default:
				vtm_render_config_general();
		}
		
		?>
		</div>
	</div>
	<?php
}

function vtm_render_config_general() {	
	global $wpdb;
	
		?>
		<h3>General Options</h3>
		<?php 
		
			if (isset($_REQUEST['save_options'])) {
			
				$sql = "SELECT ID FROM " . VTM_TABLE_PREFIX . "CONFIG ORDER BY ID";
				$configid = $wpdb->get_var($sql);
			
				$wpdb->show_errors();
				$dataarray = array (
					'PLACEHOLDER_IMAGE' => $_REQUEST['placeholder'],
					'ANDROID_LINK' => $_REQUEST['androidlink'],
					'HOME_DOMAIN_ID' => $_REQUEST['homedomain'],
					'HOME_SECT_ID'   => $_REQUEST['homesect'],
					'ASSIGN_XP_BY_PLAYER' => $_REQUEST['assignxp'],
					'USE_NATURE_DEMEANOUR' => $_REQUEST['usenature'],
					'DISPLAY_BACKGROUND_IN_PROFILE' => $_REQUEST['displaybg'],
					'DEFAULT_GENERATION_ID' => $_REQUEST['generation'],
				);
				
				$result = $wpdb->update(VTM_TABLE_PREFIX . "CONFIG",
					$dataarray,
					array (
						'ID' => $configid
					),
					array('%s', '%s', '%d', '%d', '%s', '%s', '%d', '%d')
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
			$sql = "select * from " . VTM_TABLE_PREFIX . "CONFIG;";
			$options = $wpdb->get_results($sql);
		?>

		<form id='options_form' method='post'>
			<table>
			<tr>
				<td>URL to Android XML Output</td>
				<td><input type="text" name="androidlink" value="<?php print $options[0]->ANDROID_LINK; ?>" size=60 /></td>
				<td>Page where android app connects to for character sheet output.</td>
			</tr><tr>
				<td>URL to Profile Placeholder image</td>
				<td><input type="text" name="placeholder" value="<?php print $options[0]->PLACEHOLDER_IMAGE; ?>" size=60 /></td>
				<td>This image is used in place of a character portrait on the profile page.</td>
			</tr><tr>
				<td>Home Domain</td>
				<td>
				<select name="homedomain">
					<?php
					foreach (vtm_get_domains() as $domain) {
						echo '<option value="' . $domain->ID . '" ';
						selected( $options[0]->HOME_DOMAIN_ID, $domain->ID );
						echo '>' . $domain->NAME , '</option>';
					}
					?>
				</select>
				</td>
				<td>Select which in-character domain your game is based in</td>
			</tr><tr>
				<td>Default Sect</td>
				<td>
				<select name="homesect">
					<?php
					foreach (vtm_get_sects() as $sect) {
						echo '<option value="' . $sect->ID . '" ';
						selected( $options[0]->HOME_SECT_ID, $sect->ID );
						echo '>' . $sect->NAME , '</option>';
					}
					?>
				</select>
				</td>
				<td>Select what is the default sect for new character</td>
			</tr><tr>
				<td>Assign XP By</td>
				<td>
				<input type="radio" name="assignxp" value="Y" <?php if ($options[0]->ASSIGN_XP_BY_PLAYER == 'Y') print "checked"; ?>>Player
				<input type="radio" name="assignxp" value="N" <?php if ($options[0]->ASSIGN_XP_BY_PLAYER == 'N') print "checked"; ?>>Character	
				<td>Experience can be assigned to players or to characters</td>
			</tr><tr>
				<td>Use Nature/Demeanour</td>
				<td>
				<input type="radio" name="usenature" value="Y" <?php if ($options[0]->USE_NATURE_DEMEANOUR == 'Y') print "checked"; ?>>Yes
				<input type="radio" name="usenature" value="N" <?php if ($options[0]->USE_NATURE_DEMEANOUR == 'N') print "checked"; ?>>No	
				<td>Enter and Display Nature and Demeanours for characters.</td>
			</tr><tr>
				<td>Display a Character Background on the Character Profile</td>
				<td>
				<select name="displaybg">
					<option value="0">Not displayed</option>
					<?php
					foreach (vtm_get_backgrounds() as $bg) {
						echo '<option value="' . $bg->ID . '" ';
						selected( $options[0]->DISPLAY_BACKGROUND_IN_PROFILE, $bg->ID );
						echo '>' . $bg->NAME , '</option>';
					}
					?>
				</select>
				<td>Specify if a background (e.g. Status) is displayed on the character profile.</td>
			</tr><tr>
				<td>Default Character Generation</td>
				<td>
				<select name="generation">
					<?php
					foreach (vtm_get_generations() as $gen) {
						echo '<option value="' . $gen->ID . '" ';
						selected( $options[0]->DEFAULT_GENERATION_ID, $gen->ID );
						echo '>' . $gen->NAME , '</option>';
					}
					?>
				</select>
				<td>What is the base generation for new characters.</td>
			</tr>
			</table>
			<input type="submit" name="save_options" class="button-primary" value="Save Options" />
		</form>
		
	<?php 
}
function vtm_render_config_pagelinks() {	
	global $wpdb;

		?><h3>Page Links</h3>
		<?php 
			if (isset($_REQUEST['save_st_links'])) {
				for ($i=0; $i<$_REQUEST['linecount']; $i++) {
					if ($_REQUEST['selectpage' . $i] == "0")
						$link = $_REQUEST['link' . $i];
					else if ($_REQUEST['selectpage' . $i] == "vtmnewpage") {
					
						//check if page with name $_REQUEST['value' . $i] exists 
					
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
					
					$result = $wpdb->update(VTM_TABLE_PREFIX . "ST_LINK",
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
			$sql = "select * from " . VTM_TABLE_PREFIX . "ST_LINK;";
			$stlinks = $wpdb->get_results($sql);
			
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
			$pageuris = array();
			$pagetitles = array();
			foreach ( $pages as $page ) {
				$pageuris[$page->ID] = get_page_uri( $page->ID );
				$pagetitles[$page->ID] = $page->post_title;
			}								
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
								foreach ( $pageuris as $pageid => $pageuri ) {
									if ('/' . $pageuri == $stlink->LINK)
										$match = 1;
									echo "<option value='/$pageuri' ";
									selected('/' . $pageuri, $stlink->LINK);
									echo ">{$pagetitles[$pageid]}</option>";
								}								
								echo "<option value='0' ";
								if (!$match)
									echo "selected";
								echo ">[Specify Link]</option>";
								echo "<option value='vtmnewpage'>[New Page]</option>";
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

	<?php 
}
function vtm_render_config_maps() {	
	global $wpdb;

		?><h3>Feeding Map Options</h3>
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
				<td><input type="text" name="feedingmap_centre_lat" value="<?php echo get_option('feedingmap_centre_lat'); ?>" style="width:120px;" /></td>
			</tr>
			<tr>
				<td><label>Centre Point, Longitude:</label></td>
				<td><input type="text" name="feedingmap_centre_long" value="<?php echo get_option('feedingmap_centre_long'); ?>" style="width:120px;" /></td>
			</tr>
			<tr>
				<td><label>Map Zoom:</label></td>
				<td><input type="number" name="feedingmap_zoom" value="<?php echo get_option('feedingmap_zoom'); ?>" style="width:50px;" /></td>
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
			<?php submit_button("Save Map Options", "primary", "save_map_button"); ?>
		
		</form>

		
	<?php 
}
function vtm_render_config_chargen() {	
	global $wpdb;

		?><h3>Character Generation Options</h3>
		<form method="post" action="options.php">
		<?php
		
		settings_fields( 'vtm_chargen_options_group' );
		do_settings_sections('vtm_chargen_options_group');
		?>

		<table>
		<tr>
			<td><label>User must be logged in: </label></td>
			<td><input type="checkbox" name="vtm_chargen_mustbeloggedin" value="1" <?php checked( '1', get_option( 'vtm_chargen_mustbeloggedin', '0' ) ); ?> /></td>
		</tr>
		<tr>
			<td><label>Tag to add to the start of notification email subject: </label></td>
			<td><input type="text" name="vtm_chargen_emailtag" value="<?php echo get_option( 'vtm_chargen_emailtag' ); ?>" /></td>
		</tr>
		<tr>
			<td><label>From name of notification emails: </label></td>
			<td><input type="text" name="vtm_chargen_email_from_name" value="<?php echo get_option( 'vtm_chargen_email_from_name', 'The Storytellers'); ?>" /></td>
		</tr>
		<tr>
			<td><label>From address of notification emails: </label></td>
			<td><input type="text" name="vtm_chargen_email_from_address" value="<?php echo get_option( 'vtm_chargen_email_from_address', get_bloginfo('admin_email') ); ?>" /></td>
		</tr>
		</table>
		<?php submit_button("Save Character Generation Options", "primary", "save_chargen_button"); ?>
		</form>
		
	<?php 
}
function vtm_render_config_skinning() {	
	global $wpdb;
	
		?><h3>Skinning</h3>
		<form method="post" action="options.php">
		<?php
		
		settings_fields( 'vtm_options_group' );
		do_settings_sections('vtm_options_group');
		?>
		<h4>Report Options</h4>
		<table>
			<tr>
				<td>Extra columns for sign-in report (comma-separated):</td>
				<td><input type="text" name="vtm_signin_columns" value="<?php echo get_option('vtm_signin_columns'); ?>" /></td>
			</tr>
		</table>
		
		<h4>Web Page Layout</h4>
		<table>
			<tr>
				<td>Number of columns:</td>
				<td>
					<input type="radio" name="vtm_web_columns" value="1" <?php if (get_option('vtm_web_columns', 3) == 1) print "checked"; ?>>1 Column
					<input type="radio" name="vtm_web_columns" value="3" <?php if (get_option('vtm_web_columns', 3) == 3) print "checked"; ?>>3 Columns	
				</td>
			</tr>
		</table>
		
		<h4>Web Page Graphics</h4>
		<?php 
			$drawbgcolour = get_option('vtm_view_bgcolour', '#000000');
			$drawborder   = get_option('vtm_view_dotlinewidth', '2');
			$dot1colour   = get_option('vtm_dot1colour', get_option('vtm_view_dotcolour', '#FFFFFF'));
			$dot2colour   = get_option('vtm_dot2colour', get_option('vtm_xp_dotcolour',   '#FF0000'));
			$dot3colour   = get_option('vtm_dot3colour', get_option('vtm_pend_dotcolour', '#00FF00'));
			$dot4colour   = get_option('vtm_dot4colour', get_option('vtm_chargen_freebie', '#0000FF'));
		?>
		
		<table>
			<tr>
				<td>Background Colour (#RRGGBB)</td><td><input type="color" name="vtm_view_bgcolour" value="<?php echo $drawbgcolour; ?>" /></td>
				<td>Dot/Box Line Width (mm)</td><td><input type="text" name="vtm_view_dotlinewidth" value="<?php echo $drawborder; ?>" size=4 /></td>
			</tr><tr>
				<td>Dot1 colour (#RRGGBB)</td><td><input type="color" name="vtm_dot1colour" value="<?php echo $dot1colour; ?>" /></td>
				<td>Dot2 Colour (#RRGGBB)</td><td><input type="color" name="vtm_dot2colour" value="<?php echo $dot2colour; ?>" /></td>
			</tr><tr>
				<td>Dot3 Colour (#RRGGBB)</td><td><input type="color" name="vtm_dot3colour" value="<?php echo $dot3colour; ?>" /></td>
				<td>Dot4 Colour (#RRGGBB)</td><td><input type="color" name="vtm_dot4colour" value="<?php echo $dot4colour; ?>" /></td>
			</tr>
		</table>
		<table>
		<tr>
		<td><img alt="empty dot1" width=16 src='<?php echo plugins_url( 'gvlarp-character/images/dot1empty.jpg' ); ?>'></td>
		<td><img alt="full dot1"  width=16 src='<?php echo plugins_url( 'gvlarp-character/images/dot1full.jpg' ); ?>'></td>
		<td><img alt="dot2"       width=16 src='<?php echo plugins_url( 'gvlarp-character/images/dot2.jpg' ); ?>'></td>
		<td><img alt="dot3"       width=16 src='<?php echo plugins_url( 'gvlarp-character/images/dot3.jpg' ); ?>'></td>
		<td><img alt="dot4"       width=16 src='<?php echo plugins_url( 'gvlarp-character/images/dot4.jpg' ); ?>'></td>
		<td><img alt="crossclear" width=16 src='<?php echo plugins_url( 'gvlarp-character/images/crossclear.jpg' ); ?>'></td>
		<td><img alt="box"        width=16 src='<?php echo plugins_url( 'gvlarp-character/images/webbox.jpg' ); ?>'></td>
		<td><img alt="checked"    width=16 src='<?php echo plugins_url( 'gvlarp-character/images/check.jpg' ); ?>'></td>
		</tr>
		</table>

		<h4>PDF Character Sheet Options</h4>
		<table>
			<tr>
				<td>Character Sheet Title</td><td><input type="text" name="vtm_pdf_title" value="<?php echo get_option('vtm_pdf_title', 'Character Sheet'); ?>" size=30 /></td>
				<td>Title Font</td><td><select name="vtm_pdf_titlefont">
					<option value="Arial"     <?php if ('Arial'     == get_option('vtm_pdf_titlefont')) echo "selected='selected'"; ?>>Arial</option>
					<option value="Courier"   <?php if ('Courier'   == get_option('vtm_pdf_titlefont')) echo "selected='selected'"; ?>>Courier</option>
					<option value="Helvetica" <?php if ('Helvetica' == get_option('vtm_pdf_titlefont')) echo "selected='selected'"; ?>>Helvetica</option>
					<option value="Times"     <?php if ('Times'     == get_option('vtm_pdf_titlefont')) echo "selected='selected'"; ?>>Times New Roman</option>
					</select>
				</td>
				<td>Title Text Colour (#RRGGBB)</td><td><input type="color" name="vtm_pdf_titlecolour" value="<?php echo get_option('vtm_pdf_titlecolour', '#000000'); ?>" /></td>
			</tr>
			<tr>
				<td>Divider Line Colour (#RRGGBB)</td><td><input type="color" name="vtm_pdf_divcolour" value="<?php echo get_option('vtm_pdf_divcolour', '#000000'); ?>" /></td>
				<td>Divider Text Colour (#RRGGBB)</td><td><input type="color" name="vtm_pdf_divtextcolour" value="<?php echo get_option('vtm_pdf_divtextcolour', '#000000'); ?>" /></td>
				<td>Divider Line Width (mm)</td><td><input type="text" name="vtm_pdf_divlinewidth" value="<?php echo get_option('vtm_pdf_divlinewidth', '1'); ?>" size=4 /></td>
			</tr>
			<tr>
				<td>Character Sheet Footer</td><td><input type="text" name="vtm_pdf_footer" value="<?php echo get_option('vtm_pdf_footer'); ?>" size=30 /></td>
				<td>Dot/Box Colour (#RRGGBB)</td><td><input type="color" name="vtm_pdf_dotcolour" value="<?php echo get_option('vtm_pdf_dotcolour', '#000000'); ?>" /></td>
				<td>Dot/Box Line Width (mm)</td><td><input type="text" name="vtm_pdf_dotlinewidth" value="<?php echo get_option('vtm_pdf_dotlinewidth', '1'); ?>" size=4 /></td>
			</tr>
		</table>
		<table>
		<tr>
		<td><img alt="empty dot"  width=16 src='<?php echo plugins_url( 'gvlarp-character/images/emptydot.jpg' ); ?>'></td>
		<td><img alt="full dot"  width=16 src='<?php echo plugins_url( 'gvlarp-character/images/fulldot.jpg' ); ?>'></td>
		<td><img alt="xp dot"  width=16 src='<?php echo plugins_url( 'gvlarp-character/images/pdfxpdot.jpg' ); ?>'></td>
		<td><img alt="box dot"  width=16 src='<?php echo plugins_url( 'gvlarp-character/images/box.jpg' ); ?>'></td>
		<td><img alt="box2 dot" width=16 src='<?php echo plugins_url( 'gvlarp-character/images/boxcross1.jpg' ); ?>'></td>
		<td><img alt="box3 dot" width=16 src='<?php echo plugins_url( 'gvlarp-character/images/boxcross2.jpg' ); ?>'></td>
		<td><img alt="box4 dot" width=16 src='<?php echo plugins_url( 'gvlarp-character/images/boxcross3.jpg' ); ?>'></td>
		</tr>
		</table>
		
		<?php submit_button("Save General Options", "primary", "save_general_button"); ?>
		</form>
		
		<?php
		
		// Webpage dots
		vtm_draw_dot("dot1empty", $dot1colour, $drawbgcolour, $drawborder, 0);
		vtm_draw_dot("dot1full",  $dot1colour, $drawbgcolour, $drawborder, 1);
		vtm_draw_box("crossclear", $dot1colour, $drawbgcolour, $drawborder, 2);
		vtm_draw_box("webbox", $dot1colour, $drawbgcolour, $drawborder, 0);
		vtm_draw_dot("dot2",   $dot2colour, $drawbgcolour, $drawborder, 1);
		vtm_draw_dot("dot3",   $dot3colour, $drawbgcolour, $drawborder, 1);
		vtm_draw_dot("dot4",   $dot4colour, $drawbgcolour, $drawborder, 1);
		vtm_draw_check("check", $dot1colour, $drawbgcolour, $drawborder);
		
		// PDF dots
		$drawborder   = get_option('vtm_pdf_dotlinewidth', '3');
		$drawcolour   = get_option('vtm_pdf_dotcolour', '#000000');
		$drawbgcolour = '#FFFFFF';
		vtm_draw_dot("emptydot", $drawcolour, $drawbgcolour, $drawborder, 0);
		vtm_draw_dot("fulldot",  $drawcolour, $drawbgcolour, $drawborder, 1);
		vtm_draw_dot("pdfxpdot", $drawcolour, $drawbgcolour, $drawborder, 0, 1);
		
		vtm_draw_box("box", $drawcolour, $drawbgcolour, $drawborder, 0);
		vtm_draw_box("boxcross1", $drawcolour, $drawbgcolour, $drawborder, 1);
		vtm_draw_box("boxcross2", $drawcolour, $drawbgcolour, $drawborder, 2);
		vtm_draw_box("boxcross3", $drawcolour, $drawbgcolour, $drawborder, 3);
		
}

function vtm_draw_dot($name, $drawcolour, $drawbgcolour, $drawborder, $fill = 1, $filldot = 0) {

	if (class_exists('Imagick')) {
		$drawwidth    = 32;
		$drawheight   = 32;
		$drawmargin   = 1;
		$imagetype    = 'jpg';

		$image = new Imagick();

		$image->newImage($drawwidth, $drawheight, new ImagickPixel($drawbgcolour), $imagetype);
		$draw = new ImagickDraw();
		$draw->setStrokeColor($drawcolour);
		$draw->setStrokeWidth($drawborder);
		if ($fill)
			$draw->setFillColor($drawcolour);
		else
			$draw->setFillColor($drawbgcolour);
		$draw->circle( ceil($drawwidth / 2), ceil($drawheight / 2), ceil($drawwidth / 2), $drawborder + $drawmargin);
		
		if ($filldot) {
			$draw->setFillColor($drawcolour);
			$draw->circle( ceil($drawwidth / 2), ceil($drawheight / 2), ceil($drawwidth / 2), ceil($drawwidth / 4));
		}
		
		$image->drawImage($draw);
		$image->writeImage(VTM_CHARACTER_URL . "images/{$name}." . $imagetype);

		$image = "";
	}
}
function vtm_draw_box($name, $drawcolour, $drawbgcolour, $drawborder, $crosses) {
	if (class_exists('Imagick')) {
		$drawwidth    = 32;
		$drawheight   = 32;
		$drawmargin   = 1;
		$imagetype    = 'jpg';

		$image = new Imagick();
		
		$image->newImage($drawwidth, $drawheight, new ImagickPixel($drawbgcolour), $imagetype);
		$draw = new ImagickDraw();
		$draw->setStrokeColor($drawcolour);
		$draw->setStrokeWidth($drawborder);
		$draw->setFillColor($drawbgcolour);
		$draw->rectangle( $drawborder, $drawborder, $drawwidth - $drawborder - 1, $drawheight - $drawborder - 1);
		
		if ($crosses >= 1)
			$draw->line( $drawborder, $drawborder, $drawwidth - $drawborder - 1, $drawheight - $drawborder - 1);
		if ($crosses >= 2)
			$draw->line( $drawborder, $drawheight - $drawborder - 1, $drawwidth - $drawborder - 1, $drawborder);
		if ($crosses >= 3)
			$draw->line( $drawborder, $drawheight/2, $drawwidth - $drawborder - 1, $drawheight / 2);
		
		$image->drawImage($draw);
		$image->writeImage(VTM_CHARACTER_URL . "images/{$name}." . $imagetype);

		$image = "";
	}
	
}
function vtm_draw_check($name, $drawcolour, $drawbgcolour, $drawborder) {
	if (class_exists('Imagick')) {
		$drawwidth    = 32;
		$drawheight   = 32;
		$drawmargin   = 1;
		$imagetype    = 'jpg';

		$image = new Imagick();
		
		$image->newImage($drawwidth, $drawheight, new ImagickPixel($drawbgcolour), $imagetype);
		$draw = new ImagickDraw();
		$draw->setStrokeColor($drawcolour);
		$draw->setStrokeWidth($drawborder);
		$draw->setFillColor($drawbgcolour);
		$draw->rectangle( $drawborder, $drawborder, $drawwidth - $drawborder - 1, $drawheight - $drawborder - 1);
		
		$Lx = $drawborder;
		$Ty = $drawborder;
		$Rx = $drawheight - $drawborder - $drawmargin;
		$By = $drawwidth - $drawborder - $drawmargin;
		$MIDx = $drawwidth/2;
		$MIDy = $drawheight/2;
		$gap = $drawborder * 2;
		
		$draw->setStrokeWidth($drawborder * 2);
		$draw->line($Lx + $gap, $MIDy, 		$MIDx, $By - $gap);
		$draw->line($MIDx, $By - $gap,		$Rx - $gap, $Ty + $gap);
		
		$image->drawImage($draw);
		$image->writeImage(VTM_CHARACTER_URL . "images/{$name}." . $imagetype);

		$image = "";
	}
	
}
?>