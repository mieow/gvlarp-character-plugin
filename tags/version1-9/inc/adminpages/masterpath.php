<?php

function character_master_path() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	?>
	<div class="wrap">
		<h2>Path Changes</h2>
		<?php render_master_path_page(); ?>
	</div>
	
	<?php
}



function render_master_path_page(){
	global $wpdb;

	$type = "masterpath";
	
	if (isset($_REQUEST['do_update']) && $_REQUEST['do_update']) {
		//echo "<p>Saving...</p>";
		//print_r($_REQUEST['masterpath_change']);
		//print_r($_REQUEST['comment']);
		
		$reasons  = $_REQUEST['path_reason'];
		$comments = $_REQUEST['comment'];
		
		foreach( $_REQUEST['masterpath_change'] as $characterID => $change) {
			if (!empty($change) && is_numeric($change)) {
				
				$dataarray = array (
					'CHARACTER_ID'    => $characterID,
					'PATH_REASON_ID'  => $reasons[$characterID],
					'AWARDED'         => Date('Y-m-d'),
					'AMOUNT'          => $change,
					'COMMENT'         => $comments[$characterID]
				);
				
				$wpdb->insert(GVLARP_TABLE_PREFIX . "CHARACTER_ROAD_OR_PATH",
							$dataarray,
							array (
								'%d',
								'%d',
								'%s',
								'%d',
								'%s'
							)
						);
				
				$newid = $wpdb->insert_id;
				if ($newid  == 0) {
					echo "<p style='color:red'><b>Error:</b> Path change failed for data (";
					print_r($dataarray);
					$wpdb->print_error();
					echo ")</p>";
				} else {
					echo "<p style='color:green'>Path change made for character $characterID</p>";
					touch_last_updated($characterID);
				}
				

			}
		
		}
		
	}
	
	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );
  ?>	

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="<?php print $type ?>-filter" method="get" action='<?php print htmlentities($current_url); ?>'>
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="<?php print $type ?>" />
		
		<table class="wp-list-table widefat">
		<tr><th class="manage-column">Character</th>
			<th class="manage-column">Path Name</th>
			<th class="manage-column">Current Path</th>
			<th class="manage-column">Reason</th>
			<th class="manage-column">Path Change</th>
			<th class="manage-column">Comment</th></tr>
		<?php render_master_path_data(); ?>
		</table>
		<input type="submit" name="do_update" class="button-primary" value="Update" />
		
	</form>

    <?php

}

function render_master_path_data () {
	global $wpdb;

	$output = "";
	
	$sql = "SELECT ID FROM " . GVLARP_TABLE_PREFIX . "PATH_REASON WHERE NAME = 'Path Change'";
	$defaultreason = $wpdb->get_var($sql);
	
	$sql = "SELECT
				chara.ID as id,
				chara.name as charactername,
				paths.name as pathname,
				SUM(charpaths.AMOUNT) as pathrating
			FROM
				" . GVLARP_TABLE_PREFIX . "CHARACTER chara,
				" . GVLARP_TABLE_PREFIX . "PLAYER player,
				" . GVLARP_TABLE_PREFIX . "PLAYER_STATUS pstatus,
				" . GVLARP_TABLE_PREFIX . "CHARACTER_TYPE ctype,
				" . GVLARP_TABLE_PREFIX . "CHARACTER_STATUS cstatus,
				" . GVLARP_TABLE_PREFIX . "CHARACTER_ROAD_OR_PATH charpaths,
				" . GVLARP_TABLE_PREFIX . "ROAD_OR_PATH paths
			WHERE
				chara.PLAYER_ID = player.ID
				AND pstatus.ID = player.PLAYER_STATUS_ID
				AND cstatus.ID = chara.CHARACTER_STATUS_ID
				AND charpaths.CHARACTER_ID = chara.ID
				AND paths.ID = chara.ROAD_OR_PATH_ID
				AND ctype.ID = chara.CHARACTER_TYPE_ID
				AND pstatus.NAME = 'Active'
				AND cstatus.NAME = 'Alive'
				AND ctype.NAME   = 'PC'
				AND chara.DELETED != 'Y'
				AND chara.VISIBLE = 'Y'
			GROUP BY chara.ID
			ORDER BY charactername";
	
	//$output .= "<p>SQL: $sql</p>";
	$results = $wpdb->get_results($sql);
	//print_r ($results);
	

	foreach ($results as $row) {
	
		$output .= "<tr>";
		$output .= "<td>{$row->charactername} <span style='color:silver'>(id:{$row->id})</span></td>";
		$output .= "<td>{$row->pathname}</td>";
		$output .= "<td>{$row->pathrating}</td>";
		$output .= "<td><select name='path_reason[{$row->id}]'>";
		foreach (listPathReasons() as $reason) {
			$output .= "<option value='{$reason->id}' " . selected($reason->id, $defaultreason,false) . ">{$reason->name}</option>\n";
		}		
		$output .= "</select></td>";
		$output .= "<td><input name='masterpath_change[{$row->id}]' value=\"\" type=\"text\" size=4 /></td>";
		$output .= "<td><input name='comment[{$row->id}]' value=\"\" type=\"text\" size=30 /></td></tr>";
		
	}

	echo $output;

}



?>