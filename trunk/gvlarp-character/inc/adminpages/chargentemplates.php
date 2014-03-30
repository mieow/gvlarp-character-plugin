<?php

function vtm_render_template_data(){

	global $wpdb;
	
	$id = "";
	$type = "template";
	
	//Default template options
	$settings = array(
		'attributes-method' => "PST",
		'attributes-primary' => 7,
		'attributes-secondary' => 5,
		'attributes-tertiary' => 3
	);

	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );

	$wpdb->show_errors();
	
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
					
				if ($result) $updates++;
				else if ($result !== 0) $fail = 1;

				if ($fail) echo "<p style='color:red'>Could not update template</p>";
				elseif ($updates) echo "<p style='color:green'>Updated template</p>";
				else echo "<p style='color:orange'>No updates made to template</p>";
				
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
		
		$settings['attributes-method'] = $results['attributes-method']->VALUE;
			
	} else {
		$name   = "";
		$description = "";
		$visible = "Y";
	}
	
	vtm_render_select_template();
	
	
?>
	<h4>Add/Edit Character Generation Template</h4>
	
	<p>/Description text/</p>

	<form id="new-<?php print $type; ?>" method="post" action='<?php print htmlentities($current_url); ?>'>
	<input type="hidden" name="tab" value="<?php print $type; ?>" />
	<input type="hidden" name="template" value="<?php print $_REQUEST['template']; ?>" />
	<input type="hidden" name="action" value="save" />
	<p>Template Name:
	<input type="text"   name="template_name" value="<?php print $name; ?>"></p>
	<p>Description:
	<input type="text"   name="template_desc" value="<?php print $description; ?>"></p>
	<p>Visible:
	[pulldown]</p>

	<h4>Character Generation Template Options</h4>
	<table>
	<tr>
		<td rowspan=1>Assigning Attributes</td>
		<td><input type="radio" name="attributes-method" value="PST" <?php checked( 'PST', $settings['attributes-method']); ?>>Primary/Secondary/Tertiary
			<table>
			<tr><td>Primary Dots</td><td>[box]</td></tr>
			<tr><td>Secondary Dots</td><td>[box]</td></tr>
			<tr><td>Tertiary Dots</td><td>[box]</td></tr>
			</table>
		</td>
		<td><input type="radio" name="attributes-method" value="point" <?php checked( 'point', $settings['attributes-method']); ?>>Point Spend
			<table>
			<tr><td>Dots</td><td>[box]</td></tr>
			</table>
		</td>
	</tr>
	</table>
	<?php
	
	// template options
	
	
	?>
	
	</table>
	<input type="submit" name="do_save_<?php print $type; ?>" class="button-primary" value="Save" />
	<input type="submit" name="do_new_<?php print $type; ?>" class="button-primary" value="New" />
	<input type="submit" name="do_delete_<?php print $type; ?>" class="button-primary" value="Delete" />
	</form>

<?php
}


function vtm_render_select_template () {

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