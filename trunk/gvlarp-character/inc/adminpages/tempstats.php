<?php

function character_temp_stats() {
	global $wpdb;
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	$sql = "SELECT NAME FROM " . GVLARP_TABLE_PREFIX . "TEMPORARY_STAT ORDER BY ID";
	$tempstats = $wpdb->get_col($sql);
	
	?>
	<div class="wrap">
		<h2>Temporary Stat Changes</h2>
		<div class="gvadmin_nav">
			<ul>
			<?php
				foreach ($tempstats as $stat) {
					print "<li>" . get_tablink(esc_attr($stat), $stat) . "</li>";
				}
			?>
			</ul>
		</div>
		<div class="gvadmin_content">
		<?php
		
		$stat = isset($_REQUEST['tab']) ? $_REQUEST['tab'] : "Willpower";
		
		render_temp_stat_page($stat);
				
		?>
		</div>
	</div>
	
	<?php	
}

function render_temp_stat_page($stat) {
	global $wpdb;

	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

	$sql = "SELECT ID FROM " . GVLARP_TABLE_PREFIX . "TEMPORARY_STAT WHERE NAME = %s";
	$statID = $wpdb->get_var($wpdb->prepare($sql, $stat));
 
 // List of stats
	$sql = "SELECT ID FROM " . GVLARP_TABLE_PREFIX . "STAT WHERE NAME = %s";
	$sql = $wpdb->prepare($sql, $stat);
	$result = $wpdb->get_results($sql);
	if (count($result) > 0) {
		$filterstat = ucfirst($stat);
		$maxcol = "MAXSTAT";
	} else {
		$filterstat = "Willpower";
		$maxcol = "MAX" . strtoupper($stat);
	}
	
	$reasons = listTemporaryStatReasons();

	// Setup extra tablenav default options
	if ( isset( $_REQUEST[$stat . '_reason_bulk'] ) && array_key_exists( $_REQUEST[$stat . '_reason_bulk'], $reasons ) ) {
		$reasonID = sanitize_key( $_REQUEST[$stat . '_reason_bulk'] );
	} else {
		$reasonID = $default_bulk_reason;
	}
	if ( isset( $_REQUEST[$stat . '_amount_bulk'] ) ) {
		$amount = (int) sanitize_key( $_REQUEST[$stat . '_amount_bulk'] );
	} else {
		$amount = 1;
	}
	
	// DO UPDATES
	$maximums = $_REQUEST['max'];
	$currents = $_REQUEST['current'];
	$names    = $_REQUEST['charname'];
	$amounts  = $_REQUEST['amount'];
	$comments = $_REQUEST['comment'];
	$tmpreasons  = $_REQUEST['temp_reason'];
	$ids      = $_REQUEST['charID'];
	
	$errstat = 0;
	if (isset($_REQUEST['apply2all'])) {
		$errstat = 2;
		for ($i=0;$i<count($ids);$i++) {
			if (!update_temp_stat($ids[$i], $amount, $stat, $statID, $reasonID, 
							$maximums[$i], $currents[$i], $names[$i], ''))
				$err = 1;
		}
	}
	elseif (isset($_REQUEST['applychanges'])) {
		$errstat = 2;
		for ($i=0;$i<count($ids);$i++) {
			if (!empty($amounts[$i]))
				if (!update_temp_stat($ids[$i], $amounts[$i], $stat, $statID, $tmpreasons[$i], 
							$maximums[$i], $currents[$i], $names[$i], $comments[$i]))
					$err = 1;
		}
	}
	if ($errstat == 2) {
		echo "<p style='color:green'>$stat updates completed successfully</p>";
	}

	//Get the data from the database
	$sql = "SELECT 
				chara.ID as ID,
				chara.NAME as CHARACTERNAME,
				SUM(char_temp_stat.AMOUNT) as CURRENTSTAT,
				cstat.LEVEL as MAXSTAT,
				gen.BLOODPOOL as MAXBLOOD
			FROM
				" . GVLARP_TABLE_PREFIX . "CHARACTER chara,
				" . GVLARP_TABLE_PREFIX . "CHARACTER_STAT cstat,
				" . GVLARP_TABLE_PREFIX . "STAT stat,
				" . GVLARP_TABLE_PREFIX . "PLAYER player,
				" . GVLARP_TABLE_PREFIX . "PLAYER_STATUS pstatus,
				" . GVLARP_TABLE_PREFIX . "CHARACTER_TYPE ctype,
				" . GVLARP_TABLE_PREFIX . "CHARACTER_STATUS cstatus,
				" . GVLARP_TABLE_PREFIX . "GENERATION gen,
				" . GVLARP_TABLE_PREFIX . "CHARACTER_TEMPORARY_STAT char_temp_stat,
				" . GVLARP_TABLE_PREFIX . "TEMPORARY_STAT temp_stat
			WHERE 
				chara.id = cstat.character_id
				AND cstat.stat_id = stat.id
				AND chara.player_id = player.id
				AND player.player_status_id = pstatus.id
				AND chara.character_type_id = ctype.id
				AND chara.generation_id = gen.id
				AND char_temp_stat.character_id = chara.id
				AND char_temp_stat.temporary_stat_id = temp_stat.id
				AND char_temp_stat.character_id = chara.id
				AND chara.character_status_id = cstatus.id
				AND pstatus.name = 'Active'
				AND cstatus.name != 'Dead'
				AND chara.DELETED != 'Y'
				AND chara.VISIBLE = 'Y'
				AND stat.name = %s
				AND temp_stat.name = %s
			GROUP BY chara.id
			ORDER BY chara.name";
	$sql = $wpdb->prepare($sql, $filterstat, $stat);
	$data = $wpdb->get_results($sql, OBJECT_K);	
	
   ?>
   <h2><?php print $stat; ?> Changes</h2>

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="<?php print $stat ?>-filter" method="post" action='<?php print htmlentities($current_url); ?>'>
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="<?php print $stat ?>" />
 		
		<div class='gvfilter'>
		<select name='<?php print $stat; ?>_reason_bulk'>
		<?php
			foreach ($reasons as $reason) {
				echo "<option value='{$reason->id}'";
				selected($reason->id, $selectedreason);
				echo ">" . stripslashes($reason->name) . "</option>";
			}
		?>
		</select>
		<input type='text' name='<?php print $stat; ?>_amount_bulk' value='<?php print $amount; ?>' size=4 />
		<?php submit_button( 'Apply to all', 'primary', 'apply2all', false ); ?>
		</div>
		<table class="wp-list-table widefat">
		<tr><th class="manage-column">Character</th>
			<th class="manage-column">Current</th>
			<th class="manage-column">Maximum</th>
			<th class="manage-column">Reason</th>
			<th class="manage-column">Amount</th>
			<th class="manage-column">Comment</th>
		</tr>
		<?php
			foreach ($data as $item) {
				?>
				<tr>
					<?php 
					echo "<td><input type='hidden' name='charname[]' value='{$item->CHARACTERNAME}'/>
						<input type='hidden' name='charID[]' value='{$item->ID}'/>
						{$item->CHARACTERNAME}
						<span style='color:silver'>(ID:{$item->ID})</span></td>";
					echo "<td><input type='hidden' name='current[]' value='{$item->CURRENTSTAT}'/>
						{$item->CURRENTSTAT}</td>";
					echo "<td><input type='hidden' name='max[]' value='{$item->$maxcol}'/>
						{$item->$maxcol}</td>";
					echo "<td><select name='temp_reason[]'>\n";
					foreach ($reasons as $reason) {
						echo "<option value='{$reason->id}'>" . stripslashes($reason->name) . "</option>\n";
					}
					echo "</select></td>\n";
					echo "<td><input type='text' name='amount[]' value='' size=4 /></td>";
					echo "<td><input type='text' name='comment[]' value='' size=30 /></td>";
					?>
				</tr>
				<?php
			}
		?>
		</table>
		<?php submit_button( 'Apply Changes', 'primary', 'applychanges', false ); ?>
	</form>

    <?php
}

function update_temp_stat($selectedID, $amount, $stat, $statID, $reasonID, 
							$max, $current, $char, $comment) {
	global $wpdb;
	
	$wpdb->show_errors();
		
	$change = $amount;
			
	if ( $current + $amount > $max ) {
		$change = $max - $current;
		echo "<p style='color:orange'>Current $stat for $char is capped at the maximum</p>";
	}
	elseif ($current + $amount < 0) {
		$change = $current * -1;
		echo "<p style='color:orange'>Current $stat for $char is capped at the minimum of 0</p>";
	}
	
	if ($change != 0) {
		touch_last_updated($selectedID);
	}
	
	$data = array (
		'CHARACTER_ID'      => $selectedID,
		'TEMPORARY_STAT_ID' => $statID,
		'TEMPORARY_STAT_REASON_ID' => $reasonID,
		'AWARDED'  => Date('Y-m-d'),
		'AMOUNT'   => $change,
		'COMMENT'  => $comment
	);
	$wpdb->insert(GVLARP_TABLE_PREFIX . "CHARACTER_TEMPORARY_STAT",
			$data,
			array (
				'%d',
				'%d',
				'%d',
				'%s',
				'%d',
				'%s'
			)
		);

	if ($wpdb->insert_id == 0) {
		echo "<p style='color:red'><b>Error:</b>$stat update for character $char failed";
	} else {
		 touch_last_updated($characterID);
	}
	
	return ($wpdb->insert_id != 0);
}


?>