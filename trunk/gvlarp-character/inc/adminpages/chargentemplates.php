<?php



function vtm_render_template_data(){
	global $wpdb;
	
	$id = "";
	$type = "template";
	
	//Default template options
	$settings = vtm_default_chargen_settings();

	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );
	$wpdb->show_errors();
	
	$roads = vtm_listRoadsOrPaths();
	
	$thisaction = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
	
	$id = 0;
	switch ($thisaction) {
		case "loadtemplate":
			$id = $_REQUEST['template'];			
			break;
		case "save":
			if (isset($_REQUEST['do_new_' . $type]) || (isset($_REQUEST['do_save_' . $type]) && $_REQUEST['template'] == 0) ) {
				/* insert */
				$dataarray = array (
					'NAME'        => $_REQUEST["template_name"],
					'DESCRIPTION' => $_REQUEST["template_desc"],
					'VISIBLE'     => $_REQUEST["template_visible"],
				);
				$wpdb->insert(VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE",
							$dataarray,
							array (
								'%s',
								'%s',
							)
						);
				
				$id = $wpdb->insert_id;
				if ($id == 0) {
					echo "<p style='color:red'><b>Error:</b>Character Template could not be inserted (";
					echo ")</p>";
				} else {
				
					// save template options
					foreach ($settings as $option => $val) {
						$wpdb->insert(VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE_OPTIONS",
							array(
								'NAME' => $option,
								'VALUE' => $_REQUEST[$option],
								'TEMPLATE_ID' => $id
							),
							array('%s', '%s', '%d')
						);
					}
					
				}
			} 
			elseif (isset($_REQUEST['do_delete_' . $type])) {
				if ($_REQUEST['template'] == 0) {
					echo "<p style='color:red'>Select template before deleting</p>";
				} else {
					$id = $_REQUEST['template'];
					/* delete */
					
					/* Check if model in use */
					$ok = 1;
					
					if ($ok) {
						/* delete options */
						$sql = "delete from " . VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE_OPTIONS where TEMPLATE_ID = %d;";
						$result = $wpdb->get_results($wpdb->prepare($sql, $id));
						/* delete template */
						$sql = "delete from " . VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE where ID = %d;";
						$result = $wpdb->get_results($wpdb->prepare($sql, $id));
						echo "<p style='color:green'>Deleted template {$_REQUEST['template_name']}</p>";
					}
					
					
					$id = 0;
				}
				
			}
			else {
				/* update */
				$id = $_REQUEST['template'];
				
				$updates = 0;
				$fail    = 0;
				
				// update options
				
				$dataarray = array (
					'NAME'        => $_REQUEST["template_name"],
					'DESCRIPTION' => $_REQUEST["template_desc"],
					'VISIBLE' => $_REQUEST["template_visible"]
				);
				
				$result = $wpdb->update(VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE",
					$dataarray,
					array (
						'ID' => $id
					)
				);
				
				$sql = "SELECT NAME, VALUE, ID FROM " . VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE_OPTIONS WHERE TEMPLATE_ID = %s";
				$sql = $wpdb->prepare($sql, $id);
				$results = $wpdb->get_results($sql, OBJECT_K);
				
				// save template options
				foreach ($settings as $option => $val) {
					$data = array(
								'NAME' => $option,
								'VALUE' => isset($_REQUEST[$option]) ? $_REQUEST[$option] : $val,
								'TEMPLATE_ID' => $id
							);
					if (isset($results[$option])) {
						$result = $wpdb->update(VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE_OPTIONS",
							$data,
							array ('ID' => $results[$option]->ID)
						);
						if (!$result && $result !== 0) {
							$wpdb->print_error();
							echo "<p style='color:red'>Could not update $option</p>";
						}
					} else {
						$wpdb->insert(VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE_OPTIONS",
							$data,
							array('%s', '%s', '%d')
						);
						if ($wpdb->insert_id == 0) {
							echo "<p style='color:red'><b>Error:</b> $option could not be inserted</p>";
						}
					}
				}
				
			}
			break;		
	}
	
	if ($id > 0) {
		
		$sql = "SELECT NAME, DESCRIPTION, VISIBLE FROM " . VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE WHERE ID = %s";
		$sql = $wpdb->prepare($sql, $id);
		$result = $wpdb->get_row($sql);
		$name        = $result->NAME;
		$description = $result->DESCRIPTION;
		$visible     = $result->VISIBLE;
		
		$sql = "SELECT NAME, VALUE FROM " . VTM_TABLE_PREFIX . "CHARGEN_TEMPLATE_OPTIONS WHERE TEMPLATE_ID = %s";
		$sql = $wpdb->prepare($sql, $id);
		$results = $wpdb->get_results($sql, OBJECT_K);
		
		$settings['attributes-method']    = isset($results['attributes-method']->VALUE) ? $results['attributes-method']->VALUE : $settings['attributes-method'];
		$settings['attributes-primary']   = isset($results['attributes-primary']->VALUE) ? $results['attributes-primary']->VALUE : $settings['attributes-primary'];
		$settings['attributes-secondary'] = isset($results['attributes-secondary']->VALUE) ? $results['attributes-secondary']->VALUE : $settings['attributes-secondary'];
		$settings['attributes-tertiary']  = isset($results['attributes-tertiary']->VALUE) ? $results['attributes-tertiary']->VALUE : $settings['attributes-tertiary'];
		$settings['attributes-points']    = isset($results['attributes-points']->VALUE) ? $results['attributes-points']->VALUE : $settings['attributes-points'];
		$settings['abilities-primary']    = isset($results['abilities-primary']->VALUE) ? $results['abilities-primary']->VALUE : $settings['abilities-primary'];
		$settings['abilities-secondary']  = isset($results['abilities-secondary']->VALUE) ? $results['abilities-secondary']->VALUE : $settings['abilities-secondary'];
		$settings['abilities-tertiary']   = isset($results['abilities-tertiary']->VALUE) ? $results['abilities-tertiary']->VALUE : $settings['abilities-tertiary'];
		$settings['abilities-max']        = isset($results['abilities-max']->VALUE) ? $results['abilities-max']->VALUE : $settings['abilities-max'];
		$settings['disciplines-points']   = isset($results['disciplines-points']->VALUE) ? $results['disciplines-points']->VALUE : $settings['disciplines-points'];
		$settings['virtues-points']       = isset($results['virtues-points']->VALUE) ? $results['virtues-points']->VALUE : $settings['virtues-points'];
		$settings['backgrounds-points']   = isset($results['backgrounds-points']->VALUE) ? $results['backgrounds-points']->VALUE : $settings['backgrounds-points'];
		$settings['road-multiplier']      = isset($results['road-multiplier']->VALUE) ? $results['road-multiplier']->VALUE : $settings['road-multiplier'];
		$settings['merits-max']           = isset($results['merits-max']->VALUE) ? $results['merits-max']->VALUE : $settings['merits-max'];
		$settings['flaws-max']            = isset($results['flaws-max']->VALUE) ? $results['flaws-max']->VALUE : $settings['flaws-max'];
		$settings['freebies-points']      = isset($results['freebies-points']->VALUE) ? $results['freebies-points']->VALUE : $settings['freebies-points'];
		$settings['rituals-method']       = isset($results['rituals-method']->VALUE) ? $results['rituals-method']->VALUE : $settings['rituals-method'];
		$settings['rituals-points']       = isset($results['rituals-points']->VALUE) ? $results['rituals-points']->VALUE : $settings['rituals-points'];
		$settings['limit-road-method']    = isset($results['limit-road-method']->VALUE) ? $results['limit-road-method']->VALUE : $settings['limit-road-method'];
		$settings['limit-road-id']        = isset($results['limit-road-id']->VALUE) ? $results['limit-road-id']->VALUE : $settings['limit-road-id'];
			
	} else {
		$name   = "";
		$description = "";
		$visible = "Y";
	}
	
	?>
	<p>Select a template to edit or create a new template.</p>
	
	<?php vtm_render_select_template();
	
	
?>
	<h3>Add/Edit Character Generation Template</h3>

	<form id="new-<?php print $type; ?>" method="post" action='<?php print htmlentities($current_url); ?>'>

	<h4>Character Generation Template Information</h4>
	
	<input type="hidden" name="tab" value="<?php print $type; ?>" />
	<input type="hidden" name="template" value="<?php print $_REQUEST['template']; ?>" />
	<input type="hidden" name="action" value="save" />
	<div class="datatables_info">
	<p>Template Name:
	<input type="text"   name="template_name" value="<?php print $name; ?>" size=30 /></p>
	<p>Description:
	<input type="text"   name="template_desc" value="<?php print $description; ?>" size=70 /></p>
	<p>Visible:
		<select name="template_visible">
			<option value="N" <?php selected($visible, "N"); ?>>No</option>
			<option value="Y" <?php selected($visible, "Y"); ?>>Yes</option>
		</select>
	</p>
	</div>

	<h4>Character Generation Template Options</h4>
	<div class="datatables_detail">
	<table>
	<tr class="template_option_row">
		<td rowspan=1>Assigning Attributes</td>
		<td><input type="radio" name="attributes-method" value="PST" <?php checked( 'PST', $settings['attributes-method']); ?>>Primary/Secondary/Tertiary
			<table>
			<tr><th>Primary Dots</th>  <td><input type="text" name="attributes-primary"   value="<?php print $settings['attributes-primary']; ?>"></td></tr>
			<tr><th>Secondary Dots</th><td><input type="text" name="attributes-secondary" value="<?php print $settings['attributes-secondary']; ?>"></td></tr>
			<tr><th>Tertiary Dots</th> <td><input type="text" name="attributes-tertiary"  value="<?php print $settings['attributes-tertiary']; ?>"></td></tr>
			</table>
		</td>
		<td><input type="radio" name="attributes-method" value="point" <?php checked( 'point', $settings['attributes-method']); ?>>Point Spend
			<table>
			<tr><th>Dots</th><td><input type="text" name="attributes-points"  value="<?php print $settings['attributes-points']; ?>"></td></tr>
			</table>
		</td>
	</tr>
	<tr class="template_option_row">
		<td rowspan=1>Assigning Abilities</td>
		<td colspan=2>
			<table>
			<tr>
				<th>Maximum in any one Ability at Abilities Step</th>
				<td><input type="text" name="abilities-max"   value="<?php print $settings['abilities-max']; ?>"></td>
			</tr>
			<tr><th>Primary Dots</th>  <td><input type="text" name="abilities-primary"   value="<?php print $settings['abilities-primary']; ?>"></td></tr>
			<tr><th>Secondary Dots</th><td><input type="text" name="abilities-secondary" value="<?php print $settings['abilities-secondary']; ?>"></td></tr>
			<tr><th>Tertiary Dots</th> <td><input type="text" name="abilities-tertiary"  value="<?php print $settings['abilities-tertiary']; ?>"></td></tr>
			</table>
		</td>
	</tr>
	<tr class="template_option_row">
		<td rowspan=1>Assigning Disciplines</td>
		<td colspan=2>
			<table>
			<tr><th>Number of Discipline Dots</th> <td><input type="text" name="disciplines-points"  value="<?php print $settings['disciplines-points']; ?>"></td></tr>
			</table>
		</td>
	</tr>
	<tr class="template_option_row">
		<td rowspan=1>Assigning Backgrounds</td>
		<td colspan=2>
			<table>
			<tr><th>Number of Background Dots</th> <td><input type="text" name="backgrounds-points"  value="<?php print $settings['backgrounds-points']; ?>"></td></tr>
			</table>
		</td>
	</tr>
	<tr class="template_option_row">
		<td rowspan=1>Assigning Virtues</td>
		<td colspan=2>
			<table>
			<tr><th>Number of Virtue Dots</th> <td><input type="text" name="virtues-points"  value="<?php print $settings['virtues-points']; ?>"></td></tr>
			</table>
		</td>
	</tr>
	<tr class="template_option_row">
		<td rowspan=1>Paths of Enlightenment</td>
		<td colspan=2>
			<table>
			<tr><th>Rating is Conscience + Self-Control multiplied by</th><td><input type="text" name="road-multiplier"  value="<?php print $settings['road-multiplier']; ?>" size=5 ></td></tr>
			<tr>
				<th>Limiting Paths</th>
				<td>
					<select name="limit-road-method">
						<option value="none" <?php selected($settings['limit-road-method'], "none"); ?>>No Limit</option>
						<option value="only" <?php selected($settings['limit-road-method'], "only"); ?>>Limit to a specific path</option>
						<option value="exclude" <?php selected($settings['limit-road-method'], "exclude"); ?>>Exclude a specific path</option>
					</select>
				</td>
			</tr>
			<tr>
				<th>Select Path for limit</th>
				<td>
					<select name="limit-road-id">
					<?php 
						foreach ($roads as $road) {
							print "<option value='{$road->ID}' " . selected($settings['limit-road-id'],$road->ID, false) . ">{$road->name}</option>\n";
						}
					?>
					</select>
				</td>
			</tr>
			</table>
		</td>
	</tr>
	<tr class="template_option_row">
		<td rowspan=1>Merits and Flaws</td>
		<td colspan=2>
			<table>
			<tr><th>Maximum points spent in Merits (0 for no limit)</th> <td><input type="text" name="merits-max"  value="<?php print $settings['merits-max']; ?>" size=5 ></td></tr>
			<tr><th>Maximum points spent in Flaws (0 for no limit)</th> <td><input type="text" name="flaws-max"  value="<?php print $settings['flaws-max']; ?>" size=5 ></td></tr>
			</table>
		</td>
	</tr>
	<tr class="template_option_row">
		<td rowspan=1>Freebie Points</td>
		<td colspan=2>
			<table>
			<tr><th>Number of Freebie Points</th> <td><input type="text" name="freebies-points"  value="<?php print $settings['freebies-points']; ?>" size=5 ></td></tr>
			</table>
		</td>
	</tr>
	<tr class="template_option_row">
		<td rowspan=1>Assigning Rituals</td>
		<td colspan=2>
			<table>
			<tr><td><input type="radio" name="rituals-method" value="none" <?php checked( 'none', $settings['rituals-method']); ?>>Don't assign rituals during character generation</td></tr>
			<tr>
				<td>
					<input type="radio" name="rituals-method" value="point" <?php checked( 'point', $settings['rituals-method']); ?>>Get a set number of points to spend 
					<input type="text" name="rituals-points" value="<?php print $settings['rituals-points']; ?>">
				</td>
			</tr>
			<tr><td><input type="radio" name="rituals-method" value="discipline" <?php checked( 'discipline', $settings['rituals-method']); ?>>Points equal Thaumaturgy level (Thaum 5 gives 5 levels of disciplines)</td></tr>
			<tr><td><input type="radio" name="rituals-method" value="accumulate" <?php checked( 'accumulate', $settings['rituals-method']); ?>>Points equal to accumulated Thaum level (Thaum 5 gives 1+2+3+4+5=15 levels)</td></tr>
			</table>
		</td>
	</tr>
	</table>
	</div>

	<br />	
	</table>
	<input type="submit" name="do_save_<?php print $type; ?>" class="button-primary" value="Save" />
	<input type="submit" name="do_new_<?php print $type; ?>" class="button-primary" value="New" />
	<input type="submit" name="do_delete_<?php print $type; ?>" class="button-primary" value="Delete" />
	</form>


<?php
}


function vtm_render_select_template() {

	$selected = isset($_REQUEST['template']) ? $_REQUEST['template'] : '';

	echo "<h3>Select Template</h3>";
	echo "<form id='select_template_form' method='post'>\n";
	echo "<input type='hidden' name='tab'   value='template' />\n";
	echo "<input type='hidden' name='action' value='loadtemplate' />\n";
	echo "<select name='template'>\n";
	echo "<option value='0'>[Select/New]</option>\n";
	
	foreach (vtm_get_templates() as $template) {
		echo "<option value='{$template->ID}' ";
		selected($selected,$template->ID);
		echo ">{$template->NAME}</option>\n";
	}
	
	echo "</select>\n";
	echo "<input type='submit' name='submit_model' class='button-primary' value='Go' />\n";
	echo "</form>\n";
	

}

?>