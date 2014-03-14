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
		<h3>Options</h3>
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
			$options = $wpdb->get_results($wpdb->prepare($sql,''));
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
		
		<h3>Page Links</h3>
		<?php 
			if (isset($_REQUEST['save_st_links'])) {
				for ($i=0; $i<$_REQUEST['linecount']; $i++) {
					if ($_REQUEST['selectpage' . $i] == "0")
						$link = $_REQUEST['link' . $i];
					else if ($_REQUEST['selectpage' . $i] == "vtmnewpage") {
					
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
			$stlinks = $wpdb->get_results($wpdb->prepare($sql,''));
			
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

		<h3>Feeding Map Options</h3>
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
			<?php submit_button("Save Map Options", "primary", "save_map_button"); ?>
		
		</form>

		
		<h3>General Options</h3>
		<form method="post" action="options.php">
		<?php
		
		settings_fields( 'vtm_options_group' );
		do_settings_sections('vtm_options_group');
		?>
		<h4>View Character Sheet Graphics</h4>
		<table>
			<tr>
				<td>View Background Colour (#RRGGBB)</td><td><input type="color" name="vtm_view_bgcolour" value="<?php echo get_option('vtm_view_bgcolour'); ?>" /></td>
				<td>View Dot/Box Colour (#RRGGBB)</td><td><input type="color" name="vtm_view_dotcolour" value="<?php echo get_option('vtm_view_dotcolour'); ?>" /></td>
				<td>View Dot/Box Line Width (mm)</td><td><input type="text" name="vtm_view_dotlinewidth" value="<?php echo get_option('vtm_view_dotlinewidth'); ?>" size=4 /></td>
				<td >
					<table><tr>
					<td><img alt="empty dot" width=16 src='<?php echo plugins_url( 'gvlarp-character/images/viewemptydot.jpg' ); ?>'></td>
					<td><img alt="full dot" width=16 src='<?php echo plugins_url( 'gvlarp-character/images/viewfulldot.jpg' ); ?>'></td>
					</tr></table>
				</td>
			</tr>
		</table>
		
		<h4>Experience Spend Graphics</h4>
		<table>
			<tr>
				<td>XP Spend Background Colour (#RRGGBB)</td><td><input type="color" name="vtm_xp_bgcolour" value="<?php echo get_option('vtm_xp_bgcolour'); ?>" /></td>
				<td>XP Spend Dot/Box Colour (#RRGGBB)</td><td><input type="color" name="vtm_xp_dotcolour" value="<?php echo get_option('vtm_xp_dotcolour'); ?>" /></td>
				<td>XP Spend Dot/Box Line Width (mm)</td><td><input type="text" name="vtm_xp_dotlinewidth" value="<?php echo get_option('vtm_xp_dotlinewidth'); ?>" size=4 /></td>
				<td ><img alt="xp dot" width=16 src='<?php echo plugins_url( 'gvlarp-character/images/xpdot.jpg' ); ?>'></td>
			</tr>
		</table>

		<h4>Pending Experience Spend Graphics</h4>
		<table>
			<tr>
				<td>Pending Background Colour (#RRGGBB)</td><td><input type="color" name="vtm_pend_bgcolour" value="<?php echo get_option('vtm_pend_bgcolour'); ?>" /></td>
				<td>Pending Dot/Box Colour (#RRGGBB)</td><td><input type="color" name="vtm_pend_dotcolour" value="<?php echo get_option('vtm_pend_dotcolour'); ?>" /></td>
				<td>Pending Dot/Box Line Width (mm)</td><td><input type="text" name="vtm_pend_dotlinewidth" value="<?php echo get_option('vtm_pend_dotlinewidth'); ?>" size=4 /></td>
				<td ><img alt="pending dot" width=16 src='<?php echo plugins_url( 'gvlarp-character/images/pendingdot.jpg' ); ?>'></td>
			</tr>
		</table>

		<h4>PDF Character Sheet Options</h4>
		<table>
			<tr>
				<td>Character Sheet Title</td><td><input type="text" name="vtm_pdf_title" value="<?php echo get_option('vtm_pdf_title'); ?>" size=30 /></td>
				<td>Title Font</td><td><select name="vtm_pdf_titlefont">
					<option value="Arial"     <?php if ('Arial' == get_option('vtm_pdf_titlefont')) echo "selected='selected'"; ?>>Arial</option>
					<option value="Courier"   <?php if ('Courier' == get_option('vtm_pdf_titlefont')) echo "selected='selected'"; ?>>Courier</option>
					<option value="Helvetica" <?php if ('Helvetica' == get_option('vtm_pdf_titlefont')) echo "selected='selected'"; ?>>Helvetica</option>
					<option value="Times"     <?php if ('Times' == get_option('vtm_pdf_titlefont')) echo "selected='selected'"; ?>>Times New Roman</option>
					</select>
				</td>
				<td>Title Text Colour (#RRGGBB)</td><td><input type="color" name="vtm_pdf_titlecolour" value="<?php echo get_option('vtm_pdf_titlecolour'); ?>" /></td>
			</tr>
			<tr>
				<td>Divider Line Colour (#RRGGBB)</td><td><input type="color" name="vtm_pdf_divcolour" value="<?php echo get_option('vtm_pdf_divcolour'); ?>" /></td>
				<td>Divider Text Colour (#RRGGBB)</td><td><input type="color" name="vtm_pdf_divtextcolour" value="<?php echo get_option('vtm_pdf_divtextcolour'); ?>" /></td>
				<td>Divider Line Width (mm)</td><td><input type="text" name="vtm_pdf_divlinewidth" value="<?php echo get_option('vtm_pdf_divlinewidth'); ?>" size=4 /></td>
			</tr>
			<tr>
				<td>Character Sheet Footer</td><td><input type="text" name="vtm_pdf_footer" value="<?php echo get_option('vtm_pdf_footer'); ?>" size=30 /></td>
			<?php if (class_exists('Imagick')) { ?>
				<td>Dot/Box Colour (#RRGGBB)</td><td><input type="color" name="vtm_pdf_dotcolour" value="<?php echo get_option('vtm_pdf_dotcolour'); ?>" /></td>
				<td>Dot/Box Line Width (mm)</td><td><input type="text" name="vtm_pdf_dotlinewidth" value="<?php echo get_option('vtm_pdf_dotlinewidth'); ?>" size=4 /></td>
			</tr>
			<tr>
				<td colspan = 6>
					<table><tr>
					<td><img alt="empty dot" width=16 src='<?php echo plugins_url( 'gvlarp-character/images/emptydot.jpg' ); ?>'></td>
					<td><img alt="full dot" width=16 src='<?php echo plugins_url( 'gvlarp-character/images/fulldot.jpg' ); ?>'></td>
					<td><img alt="box dot" width=16 src='<?php echo plugins_url( 'gvlarp-character/images/box.jpg' ); ?>'></td>
					<td><img alt="box2 dot" width=16 src='<?php echo plugins_url( 'gvlarp-character/images/boxcross1.jpg' ); ?>'></td>
					<td><img alt="box3 dot" width=16 src='<?php echo plugins_url( 'gvlarp-character/images/boxcross2.jpg' ); ?>'></td>
					<td><img alt="box4 dot" width=16 src='<?php echo plugins_url( 'gvlarp-character/images/boxcross3.jpg' ); ?>'></td>
					</tr></table>
				</td>
				<?php } else { ?>
				
			<tr>
				
				<td colspan=4>&nbsp;</td>
				<?php } ?>
			</tr>
		</table>
		
		<?php submit_button("Save General Options", "primary", "save_general_button"); ?>
		</form>
		
		<?php
					
		if (class_exists('Imagick')) {
				
			$drawwidth    = 32;
			$drawheight   = 32;
			$drawmargin   = 1;
			$imagetype    = 'jpg';
			
			$image = new Imagick();
			
			/* View Character Sheet Dots */
			$drawbgcolour = get_option('vtm_view_bgcolour');
			$drawcolour   = get_option('vtm_view_dotcolour');
			$drawborder   = get_option('vtm_view_dotlinewidth');
			
			if (!$drawcolour)   $drawcolour = '#CCCCCC';
			if (!$drawborder)   $drawborder = 2;
			if (!$drawbgcolour) $drawbgcolour = '#000000';
			
			$image->newImage($drawwidth, $drawheight, new ImagickPixel($drawbgcolour), $imagetype);
			$draw = new ImagickDraw();
			$draw->setStrokeColor($drawcolour);
			$draw->setStrokeWidth($drawborder);
			$draw->setFillColor($drawbgcolour);
			$draw->circle( ceil($drawwidth / 2), ceil($drawheight / 2), ceil($drawwidth / 2), $drawborder + $drawmargin);
			$image->drawImage($draw);
			$image->writeImage(VTM_CHARACTER_URL . 'images/viewemptydot.' . $imagetype);
			
			$image->newImage($drawwidth, $drawheight, new ImagickPixel($drawbgcolour), $imagetype);
			$draw = new ImagickDraw();
			$draw->setStrokeColor($drawcolour);
			$draw->setStrokeWidth($drawborder);
			$draw->setFillColor($drawcolour);
			$draw->circle( ceil($drawwidth / 2), ceil($drawheight / 2), ceil($drawwidth / 2), $drawborder + $drawmargin);
			$image->drawImage($draw);
			$image->writeImage(VTM_CHARACTER_URL . 'images/viewfulldot.' . $imagetype);

			/* Pending XP Dots */
			$drawbgcolour = get_option('vtm_pend_bgcolour');
			$drawcolour   = get_option('vtm_pend_dotcolour');
			$drawborder   = get_option('vtm_pend_dotlinewidth');
			
			if (!$drawcolour)   $drawcolour = '#BB0506';
			if (!$drawborder)   $drawborder = 2;
			if (!$drawbgcolour) $drawbgcolour = '#000000';
			
			$image->newImage($drawwidth, $drawheight, new ImagickPixel($drawbgcolour), $imagetype);
			$draw = new ImagickDraw();
			$draw->setStrokeColor($drawcolour);
			$draw->setStrokeWidth($drawborder);
			$draw->setFillColor($drawcolour);
			$draw->circle( ceil($drawwidth / 2), ceil($drawheight / 2), ceil($drawwidth / 2), $drawborder + $drawmargin);
			$image->drawImage($draw);
			$image->writeImage(VTM_CHARACTER_URL . 'images/pendingdot.' . $imagetype);
			
			/* Spend XP Dots */
			$drawbgcolour = get_option('vtm_xp_bgcolour');
			$drawcolour   = get_option('vtm_xp_dotcolour');
			$drawborder   = get_option('vtm_xp_dotlinewidth');
			
			if (!$drawcolour)   $drawcolour = '#BB0506';
			if (!$drawborder)   $drawborder = 2;
			if (!$drawbgcolour) $drawbgcolour = '#000000';
			
			$image->newImage($drawwidth, $drawheight, new ImagickPixel($drawbgcolour), $imagetype);
			$draw = new ImagickDraw();
			$draw->setStrokeColor($drawcolour);
			$draw->setStrokeWidth($drawborder);
			$draw->setFillColor($drawcolour);
			$draw->circle( ceil($drawwidth / 2), ceil($drawheight / 2), ceil($drawwidth / 2), $drawborder + $drawmargin);
			$image->drawImage($draw);
			$image->writeImage(VTM_CHARACTER_URL . 'images/xpdot.' . $imagetype);

			/* PDF Dots */
			$drawbgcolour = '#FFFFFF';
			$drawcolour   = get_option('vtm_pdf_dotcolour');
			$drawborder   = get_option('vtm_pdf_dotlinewidth');
			
			if ($drawcolour == '') $drawcolour = '#000000';
			if ($drawborder == '') $drawborder = 3;
			
			$image->newImage($drawwidth, $drawheight, new ImagickPixel($drawbgcolour), $imagetype);
			$draw = new ImagickDraw();
			$draw->setStrokeColor($drawcolour);
			$draw->setStrokeWidth($drawborder);
			$draw->setFillColor($drawbgcolour);
			$draw->circle( ceil($drawwidth / 2), ceil($drawheight / 2), ceil($drawwidth / 2), $drawborder + $drawmargin);
			$image->drawImage($draw);
			$image->writeImage(VTM_CHARACTER_URL . 'images/emptydot.' . $imagetype);
			
			$image->newImage($drawwidth, $drawheight, new ImagickPixel($drawbgcolour), $imagetype);
			$draw = new ImagickDraw();
			$draw->setStrokeColor($drawcolour);
			$draw->setStrokeWidth($drawborder);
			$draw->setFillColor($drawcolour);
			$draw->circle( ceil($drawwidth / 2), ceil($drawheight / 2), ceil($drawwidth / 2), $drawborder + $drawmargin);
			$image->drawImage($draw);
			$image->writeImage(VTM_CHARACTER_URL . 'images/fulldot.' . $imagetype);
			
			$image->newImage($drawwidth, $drawheight, new ImagickPixel($drawbgcolour), $imagetype);
			$draw = new ImagickDraw();
			$draw->setStrokeColor($drawcolour);
			$draw->setStrokeWidth($drawborder);
			$draw->setFillColor($drawbgcolour);
			$draw->rectangle( $drawborder, $drawborder, $drawwidth - $drawborder - 1, $drawheight - $drawborder - 1);
			$image->drawImage($draw);
			$image->writeImage(VTM_CHARACTER_URL . 'images/box.' . $imagetype);
			
			/* Add a line */
			$draw = new ImagickDraw();
			$draw->setStrokeColor($drawcolour);
			$draw->setStrokeWidth($drawborder);
			$draw->setFillColor($drawbgcolour);
			$draw->line( $drawborder, $drawborder, $drawwidth - $drawborder - 1, $drawheight - $drawborder - 1);
			$image->drawImage($draw);
			$image->writeImage(VTM_CHARACTER_URL . 'images/boxcross1.' . $imagetype);
			/* Add another line */
			$draw = new ImagickDraw();
			$draw->setStrokeColor($drawcolour);
			$draw->setStrokeWidth($drawborder);
			$draw->setFillColor($drawbgcolour);
			$draw->line( $drawborder, $drawheight - $drawborder - 1, $drawwidth - $drawborder - 1, $drawborder);
			$image->drawImage($draw);
			$image->writeImage(VTM_CHARACTER_URL . 'images/boxcross2.' . $imagetype);
			/* Add last line */
			$draw = new ImagickDraw();
			$draw->setStrokeColor($drawcolour);
			$draw->setStrokeWidth($drawborder);
			$draw->setFillColor($drawbgcolour);
			$draw->line( $drawborder, $drawheight/2, $drawwidth - $drawborder - 1, $drawheight / 2);
			$image->drawImage($draw);
			$image->writeImage(VTM_CHARACTER_URL . 'images/boxcross3.' . $imagetype);
			

		}
	?>
		
	</div>
	<?php
}


?>