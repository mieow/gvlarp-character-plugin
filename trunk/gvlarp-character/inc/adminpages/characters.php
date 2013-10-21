<?php


function character_options() {
	global $wpdb;

	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	$iconurl = plugins_url('adminpages/icons/',dirname(__FILE__));
	
	// setup filter options
	$options_player_status    = gvmake_filter($wpdb->get_results("SELECT ID, NAME FROM " . GVLARP_TABLE_PREFIX. "PLAYER_STATUS"));
	$options_character_status = gvmake_filter($wpdb->get_results("SELECT ID, NAME FROM " . GVLARP_TABLE_PREFIX. "CHARACTER_STATUS"));
	$options_character_type   = gvmake_filter($wpdb->get_results("SELECT ID, NAME FROM " . GVLARP_TABLE_PREFIX. "CHARACTER_TYPE"));
	
	// Set up default filter values
	$default_player_status     = $wpdb->get_var($wpdb->prepare("SELECT ID FROM " . GVLARP_TABLE_PREFIX. "PLAYER_STATUS     WHERE NAME = %s",'Active'));
	$default_character_status  = $wpdb->get_var($wpdb->prepare("SELECT ID FROM " . GVLARP_TABLE_PREFIX. "CHARACTER_STATUS  WHERE NAME = %s",'Alive'));
	$default_character_type    = $wpdb->get_var($wpdb->prepare("SELECT ID FROM " . GVLARP_TABLE_PREFIX. "CHARACTER_TYPE    WHERE NAME = %s",'PC'));
	$default_character_visible = "y";
	
	// set active filter
	if ( isset( $_REQUEST['player_status'] ) && array_key_exists( $_REQUEST['player_status'], $options_player_status ) )
		$active_player_status = sanitize_key( $_REQUEST['player_status'] );
	else $active_player_status = $default_player_status;
	if ( isset( $_REQUEST['character_status'] ) && array_key_exists( $_REQUEST['character_status'], $options_character_status ) )
		$active_character_status = sanitize_key( $_REQUEST['character_status'] );
	else $active_character_status = $default_character_status;
	if ( isset( $_REQUEST['character_type'] ) && array_key_exists( $_REQUEST['character_type'], $options_character_type ) )
		$active_character_type = sanitize_key( $_REQUEST['character_type'] );
	else $active_character_type = $default_character_type;
	if ( isset( $_REQUEST['character_visible'] ) ) $active_character_visible = sanitize_key( $_REQUEST['character_visible'] );
	else $active_character_visible = $default_character_visible;
	
	// Get web pages
	$stlinks = $wpdb->get_results("SELECT VALUE, LINK FROM " . GVLARP_TABLE_PREFIX. "ST_LINK ORDER BY ORDERING", OBJECT_K);
	
	?>
	<div class="wrap">
		<h2>Characters <a class="add-new-h2" href="<?php echo $stlinks['editCharSheet']->LINK ; ?>">Add New</a></h2>

		<?php 
		
		if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete' && $_REQUEST['characterID'] != 0) {
		
			?>
			<p>Confirm deletion of character <?php echo $_REQUEST['characterName']; ?></p>
			<div class="char_delete">
				
				<form id="character-delete" method="get" action='<?php print $current_url; ?>'>
				<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
				<input type="hidden" name="characterID" value="<?php print $_REQUEST['characterID'] ?>" />
				<input type='submit' name="cConfirm" value="Confirm Delete" />
				<input type='submit' name="cCancel" value="Cancel" />
				</form>
			
			</div>
		
		<?php
		} else {
		
			if (isset($_REQUEST['cConfirm'])) {
				echo deleteCharacter($_REQUEST['characterID']);
			} 

		?>
		
		<div class="char_clan_menu">
		<?php
			$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			
			$noclan_url = remove_query_arg( 'clan', $current_url );
			$arr = array('<a href="' . $noclan_url . '" class="nav_clan">All</a>');
			foreach (get_clans() as $clan) {
				$clanurl = add_query_arg('clan', $clan->ID);
				array_push($arr, '<a href="' . $clanurl . '" class="nav_clan">' . $clan->NAME . '</a>');
			}
			$text = implode(' | ', $arr);
			echo $text;
		?>
		</div>
		<div class="char_filters">
			
			<form id="character-filter" method="get" action='<?php print $current_url; ?>'>
				<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
				<label>Player Status: </label>
				<select name='player_status'>"
					<?php foreach( $options_player_status as $key => $value )
							echo '<option value="' . esc_attr( $key ) . '" ' . selected( $active_player_status, $key ) . '>' . esc_attr( $value ) . '</option>';
					?>
				</select>
				<label>Character Type: </label>
				<select name='character_type'>"
					<?php foreach( $options_character_type as $key => $value )
							echo '<option value="' . esc_attr( $key ) . '" ' . selected( $active_character_type, $key ) . '>' . esc_attr( $value ) . '</option>';
					?>
				</select>
				<label>Character Status: </label>
				<select name='character_status'>"
					<?php foreach( $options_character_status as $key => $value )
							echo '<option value="' . esc_attr( $key ) . '" ' . selected( $active_character_status, $key ) . '>' . esc_attr( $value ) . '</option>';
					?>
				</select>
				<label>Character Visibility: </label>
				<select name='character_visible'>
					<?php
					echo '<option value="all" ' . selected( $active_character_visible, 'all' ) . '>All</option>';
					echo '<option value="Y" '   . selected( $active_character_visible, 'y' )   . '>Yes</option>';
					echo '<option value="N" '   . selected( $active_character_visible, 'n' )   . '>No</option>';
					?>
				</select>
				
				<?php submit_button( 'Filter', 'secondary', false, false ); ?>
			</form>
		
		</div>
		<div>
			<table class="wp-list-table widefat">
			<tr>
				<th>Character Name</th>
				<th>Actions</th>
				<th>Clan</th>
				<th>Player Name</th>
				<th>Player Status</th>
				<th>Character Type</th>
				<th>Character Status</th>
				<th>Character Visible</th>
			</tr>
			<?php
			// Character Name / Clan / Player Name / Player Status / Character Type / Character Status / Character Visible
			
			
			$sql = "SELECT
						chara.ID,
						chara.name as charactername,
						clans.name as clan,
						players.name as player,
						pstatus.name as player_status,
						ctypes.name as character_type,
						cstatus.name as character_status,
						chara.visible,
						chara.wordpress_id
					FROM
						" . GVLARP_TABLE_PREFIX. "CHARACTER chara,
						" . GVLARP_TABLE_PREFIX. "CLAN clans,
						" . GVLARP_TABLE_PREFIX. "PLAYER players,
						" . GVLARP_TABLE_PREFIX. "PLAYER_STATUS pstatus,
						" . GVLARP_TABLE_PREFIX. "CHARACTER_TYPE ctypes,
						" . GVLARP_TABLE_PREFIX. "CHARACTER_STATUS cstatus
					WHERE
						clans.ID = chara.PRIVATE_CLAN_ID
						AND players.ID = chara.PLAYER_ID
						AND pstatus.ID = players.PLAYER_STATUS_ID
						AND ctypes.ID = chara.CHARACTER_TYPE_ID
						AND cstatus.ID = chara.CHARACTER_STATUS_ID
						AND chara.DELETED != 'Y'";
						
			$args = array();
					
			if ( "all" !== $active_player_status) {
				$sql .= " AND players.PLAYER_STATUS_ID = %s";
				array_push($args, $active_player_status);
			}
			if ( "all" !== $active_character_type) {
				$sql .= " AND chara.CHARACTER_TYPE_ID = %s";
				array_push($args, $active_character_type);
			}
			if ( "all" !== $active_character_status) {
				$sql .= " AND chara.CHARACTER_STATUS_ID = %s";
				array_push($args, $active_character_status);
			}
			if ( "all" !== $active_character_visible) {
				$sql .= " AND chara.VISIBLE = %s";
				array_push($args, $active_character_visible);
			}
			if ( isset($_REQUEST['clan']) ) {
				$sql .= " AND clans.ID = %s";
				array_push($args, $_REQUEST['clan']);
			}
						
			$sql .= " 	ORDER BY charactername, visible, character_type, character_status";
			$sql = $wpdb->prepare($sql,$args);
			$result = $wpdb->get_results($sql);
			//echo "<p>SQL: $sql</p>";
			//print_r($result);
		
			foreach ($result as $character) {
				echo "<tr>\n";
				echo "<th>";
				if (!empty($character->wordpress_id))
					echo '<a href="' . $stlinks['viewCharSheet']->LINK . '?CHARACTER='. urlencode($character->wordpress_id) . '">' . $character->charactername . '</a>';
				else 
					echo $character->charactername;
				
				echo "</th><td>";
				echo '<div>
					&nbsp;<a href="' . $stlinks['editCharSheet']->LINK . '?characterID=' . urlencode($character->ID) . '"><img src="' . $iconurl . 'edit.png" alt="Edit" title="Edit Character" /></a>';

				$delete_url = add_query_arg('action', 'delete', $current_url);
				$delete_url = add_query_arg('characterID', urlencode($character->ID), $delete_url);
				$delete_url = add_query_arg('characterName', urlencode($character->charactername), $delete_url);
				echo '&nbsp;<a href="' . $delete_url . '"><img src="' . $iconurl . 'delete.png" alt="Delete" title="Delete Character" /></a>';
				
				if (!empty($character->wordpress_id)) {
					echo '&nbsp;<a href="' . $stlinks['printCharSheet']->LINK  . '?CHARACTER='. urlencode($character->wordpress_id) . '"><img src="' . $iconurl . 'print.png" alt="Print" title="Print Character" /></a>';
					echo '&nbsp;<a href="' . $stlinks['viewProfile']->LINK     . '?CHARACTER='. urlencode($character->wordpress_id) . '"><img src="' . $iconurl . 'profile.png" alt="Profile" title="View Profile" /></a>';
					echo '&nbsp;<a href="' . $stlinks['viewXPSpend']->LINK     . '?CHARACTER='. urlencode($character->wordpress_id) . '"><img src="' . $iconurl . 'spendxp.png" alt="XP Spend" title="Spend Experience" /></a>';
					echo '&nbsp;<a href="' . $stlinks['viewExtBackgrnd']->LINK . '?CHARACTER='. urlencode($character->wordpress_id) . '"><img src="' . $iconurl . 'background.png" alt="Background" title="Extended Background" /></a>';
					echo '&nbsp;<a href="' . $stlinks['viewCustom']->LINK      . '?CHARACTER='. urlencode($character->wordpress_id) . '"><img src="' . $iconurl . 'custom.png" alt="Custom" title="View Custom Page as Character" /></a>';
				}
				echo "</div></td>";
				echo "<td>{$character->clan}</td>";
				echo "<td>{$character->player}</td>";
				echo "<td>{$character->player_status}</td>";
				echo "<td>{$character->character_type}</td>";
				echo "<td>{$character->character_status}</td>";
				echo "<td>{$character->visible}</td>";
				echo "</tr>\n";
			}
		
			?>
			</table>
		</div>
		
		<?php } ?>
	</div>
	<?php
}

/* CREATE/EDIT CHARACTER PAGE
-------------------------------------------------------------- */

function gv_edit_character_content_filter($content) {

  if (is_page(get_stlink_page('editCharSheet')))
		if (is_user_logged_in()) {
			$content .= get_edit_character_content();
		} else {
			$content .= "<p>You must be logged in to view this content.</p>";
		}
  // otherwise returns the database content
  return $content;
}

add_filter( 'the_content', 'gv_edit_character_content_filter' );


function get_edit_character_content() {

	$output = "";

	if (isset($_REQUEST['characterID']))
		$characterID = $_REQUEST['characterID'];
	else
		$characterID = 0;

	if (isset($_REQUEST['cSubmit']) && $_REQUEST['cSubmit'] == "Submit character changes") {
		$characterID = processCharacterUpdate($characterID);
		$output .= "<br /><center><strong>Update successful</strong></center><br />";
	}
	$output .= displayUpdateCharacter($characterID);
	
	return $output;
}


function displayUpdateCharacter($characterID) {
	global $wpdb;
	$table_prefix = GVLARP_TABLE_PREFIX;
	$output = "";

	if ($characterID == "0" || (int) ($characterID) > 0) {
		$players           = listPlayers("", "");       // ID, name
		$clans             = listClans();               // ID, name
		$generations       = listGenerations();         // ID, name
		$domains           = listDomains();             // ID, name
		$sects             = get_Sects();               // ID, name
		$characterTypes    = listCharacterTypes();      // ID, name
		$characterStatuses = listCharacterStatuses();   // ID, name
		$roadsOrPaths      = listRoadsOrPaths();        // ID, name

		$config = getConfig();
					
		$characterName             = "New Name";
		$characterPublicClanId     = "";
		$characterPrivateClanId    = "";
		$characterGenerationId     = "";
		$characterDateOfBirth      = "";
		$characterDateOfEmbrace    = "";
		$characterSire             = "";
		$characterPlayerId         = "";
		$characterTypeId           = "";
		$characterStatusId         = "";
		$characterStatusComment    = "";
		$characterRoadOrPathId     = "";
		$characterRoadOrPathRating = "";
		$characterDomainId         = $config->HOME_DOMAIN_ID;
		$characterSectId           = $config->HOME_SECT_ID;
		$characterWordpressName    = "";
		$characterVisible          = "Y";
		$characterNatureId         = "";
		$characterDemeanourId      = "";

		$characterHarpyQuote       = "";
		$characterPortraitURL      = "";

		if ((int) ($characterID) > 0) {

			$sql = "SELECT NAME,
								   PUBLIC_CLAN_ID,
								   PRIVATE_CLAN_ID,
								   GENERATION_ID,
								   DATE_OF_BIRTH,
								   DATE_OF_EMBRACE,
								   SIRE,
								   PLAYER_ID,
								   CHARACTER_TYPE_ID,
								   CHARACTER_STATUS_ID,
								   CHARACTER_STATUS_COMMENT,
								   ROAD_OR_PATH_ID,
								   ROAD_OR_PATH_RATING,
								   DOMAIN_ID,
								   SECT_ID,
								   WORDPRESS_ID,
								   VISIBLE
							FROM " . $table_prefix . "CHARACTER
							WHERE ID = %d";

			$characterDetails = $wpdb->get_results($wpdb->prepare($sql, $characterID));

			foreach ($characterDetails as $characterDetail) {
				$characterName             = $characterDetail->NAME;
				$characterPublicClanId     = $characterDetail->PUBLIC_CLAN_ID;
				$characterPrivateClanId    = $characterDetail->PRIVATE_CLAN_ID;
				$characterGenerationId     = $characterDetail->GENERATION_ID;
				$characterDateOfBirth      = $characterDetail->DATE_OF_BIRTH;
				$characterDateOfEmbrace    = $characterDetail->DATE_OF_EMBRACE;
				$characterSire             = $characterDetail->SIRE;
				$characterPlayerId         = $characterDetail->PLAYER_ID;
				$characterTypeId           = $characterDetail->CHARACTER_TYPE_ID;
				$characterStatusId         = $characterDetail->CHARACTER_STATUS_ID;
				$characterStatusComment    = $characterDetail->CHARACTER_STATUS_COMMENT;
				$characterRoadOrPathId     = $characterDetail->ROAD_OR_PATH_ID;
				$characterRoadOrPathRating = $characterDetail->ROAD_OR_PATH_RATING;
				$characterDomainId         = $characterDetail->DOMAIN_ID;
				$characterSectId           = $characterDetail->SECT_ID;
				$characterWordpressName    = $characterDetail->WORDPRESS_ID;
				$characterVisible          = $characterDetail->VISIBLE;
			}

			$sql = "SELECT QUOTE, PORTRAIT
							FROM " . $table_prefix . "CHARACTER_PROFILE
							WHERE CHARACTER_ID = %d";

			$characterProfiles = $wpdb->get_results($wpdb->prepare($sql, $characterID));

			foreach ($characterProfiles as $characterProfile) {
				$characterHarpyQuote  = stripslashes($characterProfile->QUOTE);
				$characterPortraitURL = $characterProfile->PORTRAIT;
			}
			
			if ($config->USE_NATURE_DEMEANOUR == 'Y') {
				$sql = "SELECT
							NATURE_ID,
							DEMEANOUR_ID
						FROM " . $table_prefix . "CHARACTER
						WHERE ID = %d";
				$characterND = $wpdb->get_row($wpdb->prepare($sql, $characterID));
				
				$characterNatureId    = $characterND->NATURE_ID;
				$characterDemeanourId = $characterND->DEMEANOUR_ID;
			
			}
		}

		$output  = "<form name=\"CHARACTER_UPDATE_FORM\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
		$output .= "<input type='HIDDEN' name=\"GVLARP_FORM\" value=\"displayUpdateCharacter\" />";
		$output .= "<table class='gvplugin' id=\"gvid_ucti\">
					<tr><td class=\"gvcol_1 gvcol_val\">
						<input type='submit' name=\"cSubmit\" value=\"Submit character changes\" /></td>
					</tr></table>";

		if ((int) ($characterID) > 0) { $output .= "<input type='HIDDEN' name=\"characterID\" value=\"" . $characterID . "\" />"; }
		$output .= "<table class='gvplugin' id=\"gvid_uctu\">
						<tr><td class=\"gvcol_1 gvcol_key\">Character Name</td>
							<td class=\"gvcol_2 gvcol_val\" colspan=2><input type='text' maxlength=60 name=\"charName\" value=\"" . $characterName . "\"></td>
							<td class=\"gvcol_4 gvcol_key\">Player Name</td>
							<td class='gvcol_5 gvcol_val' colspan=2><select name=\"charPlayer\">";
		foreach ($players as $player) {
			$output .= "<option value=\"" . $player->ID . "\" ";
			if ($player->ID == $characterPlayerId) {
				$output .= "SELECTED";
			}
			$output .= ">" . $player->name . "</option>";
		}
		$output .= "</select></td></tr>";

		$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Public Clan</td>
						<td class=\"gvcol_2 gvcol_val\" colspan=2><select name=\"charPubClan\">";
		foreach ($clans as $clan) {
			$output .= "<option value=\"" . $clan->ID . "\" ";
			if ($clan->ID == $characterPublicClanId) {
				$output .= "SELECTED";
			}
			$output .= ">" . $clan->name . "</option>";
		}
		$output .= "</select></td><td class=\"gvcol_4 gvcol_key\">Private Clan</td>
								  <td class='gvcol_5 gvcol_val' colspan=2><select name=\"charPrivClan\">";
		foreach ($clans as $clan) {
			$output .= "<option value=\"" . $clan->ID . "\" ";
			if ($clan->ID == $characterPrivateClanId) {
				$output .= "SELECTED";
			}
			$output .= ">" . $clan->name . "</option>";
		}
		$output .= "</select></td></tr>";
		$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Generation</td>
						<td class=\"gvcol_2 gvcol_val\" colspan=2><select name=\"charGen\">";
		foreach ($generations as $generation) {
			$output .= "<option value=\"" . $generation->ID . "\" ";
			if ($generation->ID == $characterGenerationId) {
				$output .= "SELECTED";
			}
			$output .= ">" . $generation->name . "th</option>";
		}
		$output .= "</select></td><td class=\"gvcol_4 gvcol_key\">Sire</td>
						   <td class='gvcol_5 gvcol_val' colspan=2><input type='text' maxlength=60 name=\"charSire\" value=\"" . $characterSire . "\" /></td></tr>";
		$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Date of birth</td>
						   <td class=\"gvcol_2 gvcol_val\" colspan=2><input type='text' maxlength=10 name=\"charDoB\" value=\"" . $characterDateOfBirth . "\" /> YYYY-MM-DD</td>
								<td class=\"gvcol_4 gvcol_key\">Date of Embrace</td>
						   <td class='gvcol_5 gvcol_val' colspan=2><input type='text' maxlength=10 name=\"charDoE\" value=\"" . $characterDateOfEmbrace . "\" /> YYYY-MM-DD</td></tr>";
		$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Road or Path</td>
							<td class=\"gvcol_2 gvcol_val\"><select name=\"charRoadOrPath\">";
		foreach ($roadsOrPaths as $roadOrPath) {
			$output .= "<option value=\"" . $roadOrPath->ID . "\" ";
			if ($roadOrPath->ID == $characterRoadOrPathId || ($characterID == 0 && $roadOrPath->name == 'Humanity')) {
				$output .= "SELECTED";
			}
			$output .= ">" . $roadOrPath->name . "</option>";
		}
		$output .= "</select></td><td class=\"gvcol_3 gvcol_val\"><input type='text' maxlength=3 name=\"charRoadOrPathRating\" value=\"" . $characterRoadOrPathRating . "\" /></td>";
		$output .= "<td class=\"gvcol_4 gvcol_key\">Domain</td>
						<td class='gvcol_5 gvcol_val' colspan=2><select name=\"charDomain\">";
		foreach ($domains as $domain) {
			$output .= "<option value=\"" . $domain->ID . "\" ";
			if ($domain->ID == $characterDomainId) {
				$output .= "SELECTED";
			}
			$output .= ">" . $domain->name . "</option>";
		}
		$output .= "</select></td></tr>";
		$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Character Type</td>
							<td class=\"gvcol_2 gvcol_val\" colspan=2><select name=\"charType\">";
		foreach ($characterTypes as $characterType) {
			$output .= "<option value=\"" . $characterType->ID . "\" ";
			if ($characterType->ID == $characterTypeId || ($characterID == 0 && $characterType->name == 'PC')) {
				$output .= "SELECTED";
			}
			$output .= ">" . $characterType->name . "</option>";
		}
		$output .= "</select></td><td class=\"gvcol_4 gvcol_key\">Character Status</td>
									  <td class='gvcol_5 gvcol_val'><select name=\"charStatus\">";
		foreach ($characterStatuses as $characterStatus) {
			$output .= "<option value=\"" . $characterStatus->ID . "\" ";
			if ($characterStatus->ID == $characterStatusId) {
				$output .= "SELECTED";
			}
			$output .= ">" . $characterStatus->name . "</option>";
		}
		$output .= "</select></td><td class='gvcol_6 gvcol_val'><input type='text' maxlength=30 name=\"charStatusComment\" value=\"" . $characterStatusComment . "\" /></td></tr>";

		$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Harpy Quote</td><td class=\"gvcol_2 gvcol_val\" colspan=5><textarea name=\"charHarpyQuote\" rows=\"5\" cols=\"100\">" . $characterHarpyQuote . "</textarea></td></tr>";
		$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Portrait URL</td><td class=\"gvcol_2 gvcol_val\" colspan=5><input type='text' maxlength=250 size=100 name=\"charPortraitURL\" value=\"" . $characterPortraitURL . "\" /></td></tr>";

		$output .= "<tr><td class=\"gvcol_1 gvcol_key\">Visible</td><td class=\"gvcol_2 gvcol_val\" colspan=2><select name=\"charVisible\"><option value=\"Y\" ";
		if ($characterVisible == "Y" ) {
			$output .= "SELECTED";
		}
		$output .= ">Yes</option><option value=\"N\" ";
		if ($characterVisible != "Y") {
			$output .= "SELECTED";
		}
		$output .= ">No</option></select></td><td class=\"gvcol_4 gvcol_key\">WordPress Account</td>
												  <td class='gvcol_5 gvcol_val' colspan=2><input type='text' maxlength=30 name=\"charWordPress\" value=\"" . $characterWordpressName . "\" /></td></tr>";
		
		$output .= "<tr><td class='gvcol_key'>Sect</td><td class='gvcol_val'>\n";
		$output .= "<select name = \"charSect\">";
		foreach ($sects as $sect) {
			$output .= "<option value=\"" . $sect->ID . "\" ";
			if ($sect->ID == $characterSectId || ($characterID == 0 && $sect->NAME == 'Camarilla')) {
				$output .= "SELECTED";
			}
			$output .= ">" . $sect->NAME . "</option>";
		}
		$output .= "</select></td><td></td></tr>";
		
		if ($config->USE_NATURE_DEMEANOUR == 'Y') {
			$output .= "<tr><td>Nature</td><td colspan=2>";
			$output .= "<select name = \"charNature\">";
			$output .= "<option value=\"0\">[Select]</option>";
			foreach (get_natures() as $nature) {
				$output .= "<option value=\"" . $nature->ID . "\" ";
				if ($nature->ID == $characterNatureId) {
					$output .= "SELECTED";
				}
				$output .= ">" . $nature->NAME . "</option>";
			}
			$output .= "</select></td><td>Demeanour</td><td colspan=2>";
			$output .= "<select name = \"charDemeanour\">";
			$output .= "<option value=\"0\">[Select]</option>";
			foreach (get_natures() as $nature) {
				$output .= "<option value=\"" . $nature->ID . "\" ";
				if ($nature->ID == $characterDemeanourId) {
					$output .= "SELECTED";
				}
				$output .= ">" . $nature->NAME . "</option>";
			}
			$output .= "</select></td></td>";
		}
		
		$output .= "</table>";

		$sql = "SELECT stat.name,
							   stat.grouping,
							   stat.id statid,
							   cstat.level,
							   cstat.comment,
							   cstat.id cstatid
						FROM " . $table_prefix . "CHARACTER_STAT cstat,
							 " . $table_prefix . "STAT stat
						WHERE cstat.stat_id = stat.id
						  AND character_id = %d
						ORDER BY stat.ordering";

		$characterStats = $wpdb->get_results($wpdb->prepare($sql, $characterID));

		$arr = array("0" => "0");
		foreach ($characterStats as $characterStat) {
			$arr[$characterStat->name] = $characterStat;
		}
		$stats = listStats();

		$output .= "<hr /><table class='gvplugin' id=\"gvid_uctsto\">
							  <tr><td class=\"gvcol_1 gvcol_val\"><table class='gvplugin' id=\"gvid_uctsti\">
									  <tr><th class=\"gvthead gvcol_1\">Stat Name</th>
										  <th class=\"gvthead gvcol_2\">Value</th>
										  <th class=\"gvthead gvcol_3\">Comment</th>
										  <th class=\"gvthead gvcol_4\">Delete</th></tr>";

		$i = 0;
		foreach ($stats as $stat) {
			if ($i == 3) {
				$output .= "</table></td><td class=\"gvcol\"><table class='gvplugin' id=\"gvid_uctsti\"><tr><th class=\"gvthead gvcol_1\">Stat Name</th>
																								  <th class=\"gvthead gvcol_2\">Value</th>
																								  <th class=\"gvthead gvcol_3\">Comment</th>
																								  <th class=\"gvthead gvcol_4\">Delete</th></tr>";
			}
			elseif ($i == 9) {
				$output .= "</table></td><td class=\"gvcol\"><table class='gvplugin' id=\"gvid_uctsti\">";
			}
			elseif ($i == 6) {
				$output .= "</table></td></tr><tr><td class=\"gvcol\"><table class='gvplugin' id=\"gvid_uctsti\">";
			}
			elseif ($i == 14) {
				$output .= "</table></td></tr><tr><td class=\"gvcol\"><table class='gvplugin' id=\"gvid_uctsti\">";
			}
			$statName = $stat->name;
			$currentStat = $arr[$statName];
			$output .= "<tr><td class=\"gvcol_1 gvcol_key\">" . $stat->name . "</td>"
				. "<td class=\"gvcol_2 gvcol_val\">" . printSelectCounter($statName, $currentStat->level, 0, 10) . "</td>"
				. "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\"" . $statName . "Comment\" value=\"" . stripslashes($currentStat->comment) . "\" /></td>"
				. "<td class='gvcol_4 gvcol_val'>";

			if ($currentStat->grouping == "Virtue"  && $statName != "Courage") {
				$output .= "<input type=\"checkbox\" name=\"" . $statName . "Delete\" value=\"" . $currentStat->cstatid . "\" />";
			}

			$output .= "<input type='HIDDEN' name=\"" . $statName . "ID\" value=\"" . $currentStat->cstatid . "\" />"
				. "</td></tr>";
			$i++;
		}
		$output .= "</table></td></tr></table><hr />";

		$sql = "SELECT skill.name,
							   skill.grouping,
							   skill.id skillid,
							   cskill.level,
							   cskill.comment,
							   cskill.id cskillid
						FROM " . $table_prefix . "CHARACTER_SKILL cskill,
							 " . $table_prefix . "SKILL skill
						WHERE cskill.skill_id = skill.id
						  AND character_id = %d
						ORDER BY skill.grouping DESC, skill.name";

		$characterSkills = $wpdb->get_results($wpdb->prepare($sql, $characterID));

		$lastGroup = "Something";
		$currentGroup = "";

		$skillCount = 0;
		$arr = array();
		foreach($characterSkills as $characterSkill) {
			$currentGroup = $characterSkill->grouping;
			if ($currentGroup != $lastGroup) {
				if ($lastGroup != "Something") {
					$output .= "</table><br />";
				}
				$output .= "<table class='gvplugin' id=\"gvid_uctskg\"><tr><th class=\"gvthead gvcol_1\">" . $characterSkill->grouping . " name</th>
																				 <th class=\"gvthead gvcol_2\">Value</th>
																				 <th class=\"gvthead gvcol_3\">Comment</th>
																				 <th class=\"gvthead gvcol_4\">Delete</th></tr>";
				$lastGroup = $currentGroup;
			}

			$skillName = "skill" . $skillCount;
			$output .= "<tr><td class=\"gvcol_1 gvcol_key\">" . $characterSkill->name . "</td>"
				. "<td class=\"gvcol_2 gvcol_val\">" . printSelectCounter($skillName, $characterSkill->level, 0, 10) . "</td>"
				. "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $skillName . "Comment\" value=\"" . stripslashes($characterSkill->comment)  . "\" /></td>"
				. "<td class='gvcol_4 gvcol_val'><input type=\"checkbox\" name=\"" . $skillName . "Delete\" value=\""  . $characterSkill->cskillid . "\" />"
				.     "<input type='HIDDEN' name=\""   . $skillName . "ID\" value=\""      . $characterSkill->cskillid . "\" /></td></tr>";

			$skillCount++;
		}
		$output .= "</table><br />";
		$output .= "<input type='HIDDEN' name=\"maxOldSkillCount\" value=\"" . $skillCount . "\" />";

		$output .= "<table class='gvplugin' id=\"gvid_uctskn\"><tr><th class=\"gvthead gvcol_1\">New skill name</th>
																		 <th class=\"gvthead gvcol_2\">Value</th>
																		 <th class=\"gvthead gvcol_3\">Comment</th>
																		 <th class=\"gvthead gvcol_4\">Delete</th></tr>";

		$skillBlock = "";
		$skills = listSkills("", "Y");
		foreach ($skills as $skill) {
			$skillBlock .= "<option value=\"" . $skill->id . "\">" . $skill->name . "</option>";
		}

		for ($i = 0; $i < 20; ) {
			$skillName = "skill" . $skillCount;
			$output .= "<tr><td class=\"gvcol_1 gvcol_key\"><select name=\"" . $skillName . "SID\">" . $skillBlock . "</select></td>"
				. "<td class=\"gvcol_2 gvcol_val\">" . printSelectCounter($skillName, "", 0, 10) . "</td>"
				. "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\"" . $skillName . "Comment\" /></td>"
				. "<td class='gvcol_4 gvcol_val'></td></tr>";

			$i++;
			$skillCount++;
		}
		$output .= "<input type='HIDDEN' name=\"maxNewSkillCount\" value=\"" . $skillCount . "\" />";
		$output .= "</table><hr />";

		/*******************************************************************************************/
		/*******************************************************************************************/

		$sql = "SELECT discipline.name,
							   discipline.id disid,
							   cdiscipline.level,
							   cdiscipline.comment,
							   cdiscipline.id cdisciplineid
						FROM " . $table_prefix . "CHARACTER_DISCIPLINE cdiscipline,
							 " . $table_prefix . "DISCIPLINE discipline
						WHERE cdiscipline.discipline_id = discipline.id
						  AND character_id = %d
						ORDER BY discipline.name";

		$characterDisciplines = $wpdb->get_results($wpdb->prepare($sql, $characterID));

		$output .= "<table class='gvplugin' id=\"gvid_uctdi\"><tr><th class=\"gvthead gvcol_1\">Discipline name</th>
																		<th class=\"gvthead gvcol_2\">Value</th>
																		<th class=\"gvthead gvcol_3\">Comment</th>
																		<th class=\"gvthead gvcol_4\">Delete</th>
																		<th class=\"gvthead gvcol_5\">Discipline name</th>
																		<th class=\"gvthead gvcol_6\">Value</th>
																		<th class=\"gvthead gvcol_7\">Comment</th>
																		<th class=\"gvthead gvcol_8\">Delete</th></tr>";
		$colOffset = 0;
		$i = 0;
		$disciplineCount = 0;
		$arr = array();
		foreach($characterDisciplines as $characterDiscipline) {
			if ($i % 2 == 0) {
				$output .= "<tr>";
			}
			$colOffset = 4 * ($i % 2);

			$disciplineName = "discipline" . $disciplineCount;
			$output .= "<td class=\"gvcol_" . (1 + $colOffset) . " gvcol_key\">" . $characterDiscipline->name . "</td>"
				. "<td class=\"gvcol_" . (2 + $colOffset) . " gvcol_val\">" . printSelectCounter($disciplineName, $characterDiscipline->level, 0, 10) . "</td>"
				. "<td class=\"gvcol_" . (3 + $colOffset) . " gvcol_val\"><input type='text' name=\""     . $disciplineName . "Comment\" value=\"" . stripslashes($characterDiscipline->comment)  . "\" /></td>"
				. "<td class=\"gvcol_" . (4 + $colOffset) . " gvcol_val\"><input type=\"checkbox\" name=\"" . $disciplineName . "Delete\" value=\""  . $characterDiscipline->cdisciplineid . "\" />"
				.     "<input type='HIDDEN' name=\""   . $disciplineName . "ID\" value=\""      . $characterDiscipline->cdisciplineid . "\" /></td>";

			$i++;
			$disciplineCount++;
			if ($i % 2 == 0) {
				$output .= "</tr>";
			}
		}
		if ($i % 2 != 0) {
			$output .= "</tr>";
		}

		$output .= "<tr style='display:none'><td colspan=8><input type='HIDDEN' name=\"maxOldDisciplineCount\" value=\"" . $disciplineCount . "\" /></td></tr>";

		$disciplineBlock = "";
		$disciplines = listDisciplines("Y");
		foreach ($disciplines as $discipline) {
			$disciplineBlock .= "<option value=\"" . $discipline->id . "\">" . $discipline->name . "</option>";
		}

		for ($i = 0; $i < 4; ) {
			if ($i % 2 == 0) {
				$output .= "<tr>";
			}
			$disciplineName = "discipline" . $disciplineCount;
			$output .= "<td class=\"gvcol_" . (1 + $colOffset) . " gvcol_key\"><select name=\"" . $disciplineName . "SID\">" . $disciplineBlock . "</select></td>"
				. "<td class=\"gvcol_" . (2 + $colOffset) . " gvcol_val\">" . printSelectCounter($disciplineName, "", 0, 10) . "</td>"
				. "<td class=\"gvcol_" . (3 + $colOffset) . " gvcol_val\"><input type='text' name=\""     . $disciplineName . "Comment\" /></td>"
				. "<td class=\"gvcol_" . (4 + $colOffset) . " gvcol_val\"></td>";

			$i++;
			$disciplineCount++;
			if ($i % 2 == 0) {
				$output .= "</tr>";
			}
		}
		$output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxNewDisciplineCount\" value=\"" . $disciplineCount . "\" /></td></tr>";
		$output .= "</table><hr />";

		/*******************************************************************************************/
		/*******************************************************************************************/

		$sql = "SELECT background.name,
							   background.grouping,
							   background.id statid,
							   cbackground.level,
							   cbackground.comment,
							   cbackground.id cbackgroundid
						FROM " . $table_prefix . "CHARACTER_BACKGROUND cbackground,
							 " . $table_prefix . "BACKGROUND background
						WHERE cbackground.background_id = background.id
						  AND character_id = %d
						ORDER BY background.name";

		$characterBackgrounds = $wpdb->get_results($wpdb->prepare($sql, $characterID));

		$output .= "<table class='gvplugin' id=\"gvid_uctdi\"><tr><th class=\"gvthead gvcol_1\">Background name</th>
																		<th class=\"gvthead gvcol_2\">Value</th>
																		<th class=\"gvthead gvcol_3\">Comment</th>
																		<th class=\"gvthead gvcol_4\">Delete</th>
																		<th class=\"gvthead gvcol_5\">Background name</th>
																		<th class=\"gvthead gvcol_6\">Value</th>
																		<th class=\"gvthead gvcol_7\">Comment</th>
																		<th class=\"gvthead gvcol_8\">Delete</th></tr>";
		$i = 0;
		$backgroundCount = 0;
		$arr = array();
		foreach($characterBackgrounds as $characterBackground) {
			if ($i % 2 == 0) {
				$output .= "<tr>";
			}
			$colOffset = 4 * ($i % 2);

			$backgroundName = "background" . $backgroundCount;
			$output .= "<td class=\"gvcol_" . (1 + $colOffset) . " gvcol_key\">" . $characterBackground->name . "</td>"
				. "<td class=\"gvcol_" . (2 + $colOffset) . " gvcol_val\">" . printSelectCounter($backgroundName, $characterBackground->level, 0, 10) . "</td>"
				. "<td class=\"gvcol_" . (3 + $colOffset) . " gvcol_val\"><input type='text' name=\""     . $backgroundName . "Comment\" value=\"" . stripslashes($characterBackground->comment)  . "\" /></td>"
				. "<td class=\"gvcol_" . (4 + $colOffset) . " gvcol_val\"><input type=\"checkbox\" name=\"" . $backgroundName . "Delete\" value=\""  . $characterBackground->cbackgroundid . "\" />"
				.     "<input type='HIDDEN' name=\""   . $backgroundName . "ID\" value=\""      . $characterBackground->cbackgroundid . "\" /></td>";

			$i++;
			$backgroundCount++;
			if ($i % 2 == 0) {
				$output .= "</tr>";
			}
		}
		if ($i % 2 != 0) {
			$output .= "</tr>";
		}

		$output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxOldBackgroundCount\" value=\"" . $backgroundCount . "\" /></td></tr>";

		$backgroundBlock = "";
		$backgrounds = listBackgrounds("", "Y");
		foreach ($backgrounds as $background) {
			$backgroundBlock .= "<option value=\"" . $background->id . "\">" . $background->name . "</option>";
		}

		for ($i = 0; $i < 6; ) {
			if ($i % 2 == 0) {
				$output .= "<tr>";
			}
			$backgroundName = "background" . $backgroundCount;
			$output .= "<td class=\"gvcol_" . (1 + $colOffset) . " gvcol_key\"><select name=\"" . $backgroundName . "SID\">" . $backgroundBlock . "</select></td>"
				. "<td class=\"gvcol_" . (2 + $colOffset) . " gvcol_val\">" . printSelectCounter($backgroundName, "", 0, 10) . "</td>"
				. "<td class=\"gvcol_" . (3 + $colOffset) . " gvcol_val\"><input type='text' name=\""     . $backgroundName . "Comment\" /></td>"
				. "<td class=\"gvcol_" . (4 + $colOffset) . " gvcol_val\"></td>";

			$i++;
			$backgroundCount++;
			if ($i % 2 == 0) {
				$output .= "</tr>";
			}
		}
		$output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxNewBackgroundCount\" value=\"" . $backgroundCount . "\" /></td></tr>";
		$output .= "</table><hr />";

		/*******************************************************************************************/

		$sql = "SELECT merit.name,
							   merit.grouping,
							   merit.id statid,
							   merit.value,
							   cmerit.level,
							   cmerit.comment,
							   cmerit.id cmeritid
						FROM " . $table_prefix . "CHARACTER_MERIT cmerit,
							 " . $table_prefix . "MERIT merit
						WHERE cmerit.merit_id = merit.id
						  AND character_id = %d
						ORDER BY merit.name";

		$characterMerits = $wpdb->get_results($wpdb->prepare($sql, $characterID));

		$output .= "<table class='gvplugin' id=\gvid_uctme\"><tr><th class=\"gvthead gvcol_1\">Merit name</th>
																	   <th class=\"gvthead gvcol_2\">Value</th>
																	   <th class=\"gvthead gvcol_3\">Comment</th>
																	   <th class=\"gvthead gvcol_4\">Delete</th></tr>";
		$meritCount = 0;
		$arr = array();
		foreach($characterMerits as $characterMerit) {
			$meritName = "merit" . $meritCount;
			$output .= "<tr><td class=\"gvcol_1 gvcol_key\">" . $characterMerit->name . " (" . $characterMerit->value . ")</td>"
				. "<td class=\"gvcol_2 gvcol_val\">" . printSelectCounter($meritName, $characterMerit->level, -7, 7) . "</td>"
				. "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $meritName . "Comment\" value=\"" . stripslashes($characterMerit->comment)  . "\" /></td>"
				. "<td class='gvcol_4 gvcol_val'><input type=\"checkbox\" name=\"" . $meritName . "Delete\" value=\""  . $characterMerit->cmeritid . "\" />"
				.     "<input type='HIDDEN' name=\""   . $meritName . "ID\" value=\""      . $characterMerit->cmeritid . "\" /></td></tr>";

			$i++;
			$meritCount++;
		}

		$output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxOldMeritCount\" value=\"" . $meritCount . "\" /></td></tr>";

		$meritBlock = "";
		$merits = listMerits("", "Y");
		foreach ($merits as $merit) {
			$meritBlock .= "<option value=\"" . $merit->id . "\">" . $merit->name . " (" . $merit->value . ")</option>";
		}

		for ($i = 0; $i < 6; $i++) {
			$meritName = "merit" . $meritCount;
			$output .= "<tr><td class=\"gvcol_1 gvcol_key\"><select name=\"" . $meritName . "SID\">" . $meritBlock . "</select></td>"
				. "<td class=\"gvcol_2 gvcol_val\">" . printSelectCounter($meritName, "", -7, 7) . "</td>"
				. "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $meritName . "Comment\" /></td>"
				. "<td class='gvcol_4 gvcol_val'></td></tr>";

			$meritCount++;
		}
		$output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxNewMeritCount\" value=\"" . $meritCount . "\" /></td></tr>";
		$output .= "</table><hr />";

		/*******************************************************************************************/


		$sql = "SELECT combo_discipline.name,
							   combo_discipline.id disid,
							   ccombo_discipline.comment,
							   ccombo_discipline.id ccombo_disciplineid
						FROM " . $table_prefix . "CHARACTER_COMBO_DISCIPLINE ccombo_discipline,
							 " . $table_prefix . "COMBO_DISCIPLINE combo_discipline
						WHERE ccombo_discipline.combo_discipline_id = combo_discipline.id
						  AND character_id = %d
						ORDER BY combo_discipline.name";

		$characterComboDisciplines = $wpdb->get_results($wpdb->prepare($sql, $characterID));

		$output .= "<table class='gvplugin' id=\"gvid_uctcd\"><tr><th class=\"gvthead gvcol_1\">Combo Discipline name</th>
																		<th class=\"gvthead gvcol_2\">Value</th>
																		<th class=\"gvthead gvcol_3\">Comment</th>
																		<th class=\"gvthead gvcol_4\">Delete</th></tr>";

		$comboDisciplineCount = 0;
		$arr = array();
		foreach($characterComboDisciplines as $characterComboDiscipline) {
			$comboDisciplineName = "comboDiscipline" . $comboDisciplineCount;
			$output .= "<tr><td class=\"gvcol_1 gvcol_key\">" . $characterComboDiscipline->name . "</td>"
				. "<td class=\"gvcol_2 gvcol_val\">Learned<input type='HIDDEN' name=\"" . $comboDisciplineName . "\" value=\"0\" /></td>"
				. "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $comboDisciplineName . "Comment\" value=\"" . stripslashes($characterComboDiscipline->comment)  . "\" /></td>"
				. "<td class='gvcol_4 gvcol_val'><input type=\"checkbox\" name=\"" . $comboDisciplineName . "Delete\" value=\""  . $characterComboDiscipline->ccombo_disciplineid . "\" />"
				.     "<input type='HIDDEN' name=\""   . $comboDisciplineName . "ID\" value=\""      . $characterComboDiscipline->ccombo_disciplineid . "\" /></td></tr>";

			$comboDisciplineCount++;
		}
		$output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxOldComboDisciplineCount\" value=\"" . $comboDisciplineCount . "\" /></td></tr>";

		$comboDisciplineBlock = "";
		$comboDisciplines = listComboDisciplines("Y");
		foreach ($comboDisciplines as $comboDiscipline) {
			$comboDisciplineBlock .= "<option value=\"" . $comboDiscipline->id . "\">" . $comboDiscipline->name . "</option>";
		}

		$comboDisciplineName = "comboDiscipline" . $comboDisciplineCount;
		$output .= "<tr><td class=\"gvcol_1 gvcol_key\"><select name=\"" . $comboDisciplineName . "SID\">" . $comboDisciplineBlock . "</select></td>"
			. "<td class=\"gvcol_2 gvcol_val\"><select name=\"" . $comboDisciplineName . "\"><option value=\"-100\">Not Learned</option><option value=\"1\">Learned</option></select></td>"
			. "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\"" . $comboDisciplineName . "Comment\" /></td>"
			. "<td class='gvcol_4 gvcol_val'></td></tr>";
		$comboDisciplineCount++;

		$output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxNewComboDisciplineCount\" value=\"" . $comboDisciplineCount . "\" /></td></tr>";
		$output .= "</table><hr />";

		/*******************************************************************************************/
		/*******************************************************************************************/

		$sql = "SELECT path.name,
							   path.id disid,
							   dis.name disname,
							   cpath.level,
							   cpath.comment,
							   cpath.id cpathid
						FROM " . $table_prefix . "CHARACTER_PATH cpath,
							 " . $table_prefix . "PATH path,
							 " . $table_prefix . "DISCIPLINE dis
						WHERE cpath.path_id = path.id
						  AND path.discipline_id = dis.id
						  AND character_id = %d
						ORDER BY disname, path.name";

		$characterPaths = $wpdb->get_results($wpdb->prepare($sql, $characterID));

		$output .= "<table class='gvplugin' id=\"gvid_uctpa\"><tr><th class=\"gvthead gvcol_1\">Path name</th>
																		<th class=\"gvthead gvcol_2\">Value</th>
																		<th class=\"gvthead gvcol_3\">Comment</th>
																		<th class=\"gvthead gvcol_4\">Delete</th></tr>";

		$pathCount = 0;
		$arr = array();
		foreach($characterPaths as $characterPath) {
			$pathName = "path" . $pathCount;
			$output .= "<tr><td class=\"gvcol_1 gvcol_key\">" . $characterPath->name . " (" . substr($characterPath->disname, 0, 5)  .")</td>"
				. "<td class=\"gvcol_2 gvcol_val\">" . printSelectCounter($pathName, $characterPath->level, 0, 10) . "</td>"
				. "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $pathName . "Comment\" value=\"" . stripslashes($characterPath->comment)  . "\" /></td>"
				. "<td class='gvcol_4 gvcol_val'><input type=\"checkbox\" name=\"" . $pathName . "Delete\" value=\""  . $characterPath->cpathid . "\" />"
				.     "<input type='HIDDEN' name=\""   . $pathName . "ID\" value=\""      . $characterPath->cpathid . "\" /></td></tr>";

			$i++;
			$pathCount++;
		}

		$output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxOldPathCount\" value=\"" . $pathCount . "\" /></td></tr>";

		$pathBlock = "";
		$paths = listPaths("Y");
		foreach ($paths as $path) {
			$pathBlock .= "<option value=\"" . $path->id . "\">" . $path->name . " (" . substr($path->disname, 0, 5)  .")</option>";
		}

		for ($i = 0; $i < 2; $i++) {
			$pathName = "path" . $pathCount;
			$output .= "<tr><td class=\"gvcol_1 gvcol_key\"><select name=\"" . $pathName . "SID\">" . $pathBlock . "</select></td>"
				. "<td class=\"gvcol_2 gvcol_val\">" . printSelectCounter($pathName, "", 0, 10) . "</td>"
				. "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $pathName . "Comment\" /></td>"
				. "<td class='gvcol_4 gvcol_val'></td></tr>";
			$pathCount++;
		}
		$output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxNewPathCount\" value=\"" . $pathCount . "\" /></td></tr>";
		$output .= "</table><hr />";

		/*******************************************************************************************/
		/*******************************************************************************************/

		$sql = "SELECT ritual.name,
							   ritual.id disid,
							   ritual.level ritlevel,
							   dis.name disname,
							   critual.level,
							   critual.comment,
							   critual.id critualid
						FROM " . $table_prefix . "CHARACTER_RITUAL critual,
							 " . $table_prefix . "RITUAL ritual,
							 " . $table_prefix . "DISCIPLINE dis
						WHERE critual.ritual_id = ritual.id
						  AND ritual.discipline_id = dis.id
						  AND character_id = %d
						ORDER BY disname, level, ritual.name";

		$characterRituals = $wpdb->get_results($wpdb->prepare($sql, $characterID));

		$output .= "<table class='gvplugin' id=\"gvid_uctri\"><tr><th class=\"gvthead gvcol_1\">Ritual name</th>
																		<th class=\"gvthead gvcol_2\">Value</th>
																		<th class=\"gvthead gvcol_3\">Comment</th>
																		<th class=\"gvthead gvcol_4\">Delete</th></tr>";

		$ritualCount = 0;
		$arr = array();
		foreach($characterRituals as $characterRitual) {
			$ritualName = "ritual" . $ritualCount;
			$output .= "<tr><td class=\"gvcol_1 gvcol_key\">" . $characterRitual->name . " (" . substr($characterRitual->disname, 0, 5)  . " " . $characterRitual->ritlevel .")</td>"
				. "<td class=\"gvcol_2 gvcol_val\">Learned<input type='HIDDEN' name=\"" . $ritualName . "\" value=\"0\" /></td>"
				. "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $ritualName . "Comment\" value=\"" . stripslashes($characterRitual->comment)  . "\" /></td>"
				. "<td class='gvcol_4 gvcol_val'><input type=\"checkbox\" name=\"" . $ritualName . "Delete\" value=\""  . $characterRitual->critualid . "\" />"
				.     "<input type='HIDDEN' name=\""   . $ritualName . "ID\" value=\""      . $characterRitual->critualid . "\" /></td></tr>";

			$i++;
			$ritualCount++;
		}

		$output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxOldRitualCount\" value=\"" . $ritualCount . "\" /></td></tr>";

		$ritualBlock = "";
		$rituals = listRituals("Y");
		foreach ($rituals as $ritual) {
			$ritualBlock .= "<option value=\"" . $ritual->id . "\">" . $ritual->name . " (" . substr($ritual->disname, 0, 5)  . " " . $ritual->level . ")</option>";
		}

		for ($i = 0; $i < 5; $i++) {
			$ritualName = "ritual" . $ritualCount;
			$output .= "<tr><td class=\"gvcol_1 gvcol_key\"><select name=\"" . $ritualName . "SID\">" . $ritualBlock . "</select></td>"
				. "<td class=\"gvcol_2 gvcol_val\"><select name=\"" . $ritualName . "\"><option value=\"-100\">Not Learned</option><option value=\"1\">Learned</option></select></td>"
				. "<td class=\"gvcol_3 gvcol_val\"><input type='text' name=\""     . $ritualName . "Comment\" /></td>"
				. "<td class='gvcol_4 gvcol_val'></td></tr>";
			$ritualCount++;
		}
		$output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxNewRitualCount\" value=\"" . $ritualCount . "\" /></td></tr>";
		$output .= "</table><hr />";

		/*******************************************************************************************/
		/*******************************************************************************************/

		$sql = "SELECT office.name,
							   office.id disid,
							   domain.name domainname,
							   coffice.comment,
							   coffice.id cofficeid
						FROM " . $table_prefix . "CHARACTER_OFFICE coffice,
							 " . $table_prefix . "OFFICE office,
							 " . $table_prefix . "DOMAIN domain
						WHERE coffice.office_id = office.id
						  AND coffice.domain_id  = domain.id
						  AND character_id = %d
						ORDER BY office.ordering, office.name, domain.name";

		$characterOffices = $wpdb->get_results($wpdb->prepare($sql, $characterID));

		$output .= "<table class='gvplugin' id=\"gvid_uctof\"><tr><th class=\"gvthead gvcol_1\">Office name</th>
																		<th class=\"gvthead gvcol_2\">Domain</th>
																		<th class=\"gvthead gvcol_3\">Status</th>
																		<th class=\"gvthead gvcol_4\">Comment</th>
																		<th class=\"gvthead gvcol_5\">Delete</th></tr>";

		$officeCount = 0;
		$arr = array();
		foreach($characterOffices as $characterOffice) {
			$officeName = "office" . $officeCount;
			$output .= "<tr><td class=\"gvcol_1 gvcol_key\">" . $characterOffice->name . "</td>"
				. "<td class=\"gvcol_2 gvcol_val\">" . $characterOffice->domainname . "</td>"
				. "<td class=\"gvcol_3 gvcol_val\">In office<input type='HIDDEN' name=\"" . $officeName . "\" value=\"0\" /></td>"
				. "<td class='gvcol_4 gvcol_val'><input type='text' name=\""     . $officeName . "Comment\" value=\"" . stripslashes($characterOffice->comment)  . "\" /></td>"
				. "<td class='gvcol_5 gvcol_val'><input type=\"checkbox\" name=\"" . $officeName . "Delete\" value=\""  . $characterOffice->cofficeid . "\" />"
				.     "<input type='HIDDEN' name=\""   . $officeName . "ID\" value=\""      . $characterOffice->cofficeid . "\" /></td></tr>";
			$i++;
			$officeCount++;
		}

		$output .= "<tr style='display:none'><td colspan=4><input type='HIDDEN' name=\"maxOldOfficeCount\" value=\"" . $officeCount . "\" /></td></tr>";

		$officeBlock = "";
		$offices = listOffices("Y");
		foreach ($offices as $office) {
			$officeBlock .= "<option value=\"" . $office->ID . "\">" . $office->name . "</option>";
		}

		$domainBlock = "";
		$domains = listDomains();
		foreach ($domains as $domain) {
			$domainBlock .= "<option value=\"" . $domain->ID ."\">" . $domain->name . "</option>";
		}

		for ($i = 0; $i < 2; $i++) {
			$officeName = "office" . $officeCount;
			$output .= "<tr><td class=\"gvcol_1 gvcol_key\"><select name=\"" . $officeName . "OID\">" . $officeBlock . "</select></td>"
				. "<td class=\"gvcol_2 gvcol_val\"><select name=\"" . $officeName . "CID\">" . $domainBlock . "</select></td>"
				. "<td class=\"gvcol_3 gvcol_val\"><select name=\"" . $officeName . "\"><option value=\"-100\">Not in office</option><option value=\"1\">In office</option></select></td>"
				. "<td class='gvcol_4 gvcol_val'><input type='text' name=\""     . $officeName . "Comment\" /></td>"
				. "<td class='gvcol_5 gvcol_val'></td></tr>";
			$officeCount++;
		}
		$output .= "<tr style='display:none'><td colspan=5><input type='HIDDEN' name=\"maxNewOfficeCount\" value=\"" . $officeCount . "\" /></td></tr>";
		$output .= "</table><hr />";

		/*******************************************************************************************/
		/*******************************************************************************************/

		$output .= "<table class='gvplugin' id=\"gvid_scc\"><tr><td class=\"gvcol_1 gvcol_val\">
					<input type='submit' name=\"cSubmit\" value=\"Submit character changes\" /></td>
					</tr></table>";
		$output .= "</form>";
	}
	else {
		$output .= "We encountered an illegal Character ID (". $characterID . ")";
	}
	return $output;
}


function processCharacterUpdate($characterID) {
	global $wpdb;
	$table_prefix = GVLARP_TABLE_PREFIX;

	$characterName             = $_POST['charName'];
	$characterPlayer           = $_POST['charPlayer'];
	$characterPublicClan       = $_POST['charPubClan'];
	$characterPrivateClan      = $_POST['charPrivClan'];
	$characterGeneration       = $_POST['charGen'];
	$characterSire             = $_POST['charSire'];
	$characterDateOfBirth      = $_POST['charDoB'];
	$characterDateOfEmbrace    = $_POST['charDoE'];
	$characterRoadOrPath       = $_POST['charRoadOrPath'];
	$characterRoadOrPathRating = $_POST['charRoadOrPathRating'];
	$characterDomain           = $_POST['charDomain'];
	$characterSect             = $_POST['charSect'];
	$characterType             = $_POST['charType'];
	$characterStatus           = $_POST['charStatus'];
	$characterStatusComment    = $_POST['charStatusComment'];
	$characterVisible          = $_POST['charVisible'];
	$characterWordPress        = $_POST['charWordPress'];
	$characterNature           = $_POST['charNature'];
	$characterDemeanour        = $_POST['charDemeanour'];
			
	if (get_magic_quotes_gpc()) {
		$characterHarpyQuote = stripslashes($_POST['charHarpyQuote']);
	}
	else {
		$characterHarpyQuote = $_POST['charHarpyQuote'];
	}
	$characterPortraitURL      = $_POST['charPortraitURL'];

	if ((int) $characterID > 0) {
		$sql = "UPDATE " . $table_prefix . "CHARACTER
						SET name                     = %s,
							public_clan_id           = %d,
							private_clan_id          = %d,
							generation_id            = %d,
							date_of_birth            = %s,
							date_of_embrace          = %s,
							sire                     = %s,
							player_id                = %d,
							character_type_id        = %d,
							character_status_id      = %d,
							character_status_comment = %s,
							road_or_path_id          = %d,
							road_or_path_rating      = %d,
							domain_id                = %d,
							sect_id                  = %d,
							wordpress_id             = %s,
							visible                  = %s
						WHERE ID = " . $characterID;
		$sql = $wpdb->prepare($sql, $characterName,             $characterPublicClan,    $characterPrivateClan,   $characterGeneration,
			$characterDateOfBirth,      $characterDateOfEmbrace, $characterSire,          $characterPlayer,
			$characterType,             $characterStatus,        $characterStatusComment, $characterRoadOrPath,
			$characterRoadOrPathRating, $characterDomain,        $characterSect,
			$characterWordPress,     $characterVisible);
					}
	else {
		$sql = "INSERT INTO " . $table_prefix . "CHARACTER (name,                public_clan_id,      private_clan_id,          generation_id,
															date_of_birth,       date_of_embrace,     sire,                     player_id,
															character_type_id,   character_status_id, character_status_comment, road_or_path_id,
															road_or_path_rating, domain_id,           sect_id,					wordpress_id,             
															visible, deleted)
						VALUES (%s, %d, %d, %d, %s, %s, %s, %d, %d, %d, %s, %d, %d, %d, %d, %s, %s, 'N')";
		$sql = $wpdb->prepare($sql, $characterName,             $characterPublicClan,    $characterPrivateClan,   $characterGeneration,
			$characterDateOfBirth,      $characterDateOfEmbrace, $characterSire,          $characterPlayer,
			$characterType,             $characterStatus,        $characterStatusComment, $characterRoadOrPath,
			$characterRoadOrPathRating, $characterDomain,        $characterSect,
			$characterWordPress,     $characterVisible);
	}
	$wpdb->query($sql);

	if (!((int) $characterID > 0)) {
		$sql = "SELECT id
						FROM " . $table_prefix . "CHARACTER
						WHERE name         = %s
						  AND wordpress_id = %s";
		$characterIDs = $wpdb->get_results($wpdb->prepare($sql, $characterName, $characterWordPress));
		foreach ($characterIDs as $id) {
			$characterID = $id->id;
		}

		$sql = "INSERT INTO ". $table_prefix . "CHARACTER_PROFILE (character_id, quote, portrait)
						VALUES (%d, %s, %s)";
		$wpdb->query($wpdb->prepare($sql, $characterID, $characterHarpyQuote, $characterPortraitURL));

		$xpReasonID = establishXPReasonID('Initial XP');
		$sql = "INSERT INTO " . $table_prefix . "PLAYER_XP (player_id, character_id, xp_reason_id, awarded, amount, comment) "
			. "VALUES (%d, %d, %d, SYSDATE(), 0, 'New character added')";
		$wpdb->query($wpdb->prepare($sql, $characterPlayer, $characterID, $xpReasonID));

		$pathReasonID = establishPathReasonID('Initial');
		$sql = "INSERT INTO ". $table_prefix . "CHARACTER_ROAD_OR_PATH (character_id, path_reason_id, awarded, amount, comment) "
			.  "VALUES (%d, %d, SYSDATE(), %d, 'Character creation')";
		$sql = $wpdb->prepare($sql, $characterID, $pathReasonID, $characterRoadOrPathRating);
		//echo "<p>SQL: $sql</p>";
		$wpdb->query($sql);

		$tempStatReasonID = establishTempStatReasonID('Initial');
		$tempStatID = establishTempStatID('Blood');
		$sql = "INSERT INTO " . $table_prefix . "CHARACTER_TEMPORARY_STAT (character_id, temporary_stat_id, temporary_stat_reason_id, awarded, amount, comment) "
			. " VALUES (%d, %d, %d, SYSDATE(), 10, 'Character creation')";
		$wpdb->query($wpdb->prepare($sql, $characterID, $tempStatID, $tempStatReasonID));
	}
	else {
		$sql = "SELECT id
						FROM " . $table_prefix . "CHARACTER_PROFILE
						WHERE character_id = %d";

		$profileIDs = $characterIDs = $wpdb->get_results($wpdb->prepare($sql, $characterID));
		$profileID = -1;

		foreach ($profileIDs as $currentProfileID) {
			$profileID = $currentProfileID->id;
		}

		if ($profileID == -1) {
			$sql = "INSERT INTO ". $table_prefix . "CHARACTER_PROFILE (character_id, quote, portrait)
							VALUES (%d, %s, %s)";
			$sql = $wpdb->prepare($sql, $characterID, $characterHarpyQuote, $characterPortraitURL);
		}
		else {
			$sql = "UPDATE " . $table_prefix . "CHARACTER_PROFILE
							SET quote    = %s,
								portrait = %s
							WHERE id = %d";
			$sql = $wpdb->prepare($sql, $characterHarpyQuote, $characterPortraitURL, $profileID);
		}
		$wpdb->query($sql);
	}

	$stats = listStats();
	foreach ($stats as $stat) {
		$currentStat = str_replace(" ", "_", $stat->name);
		if ($_POST[$currentStat] != "" && $_POST[$currentStat] != "-100") {
			if ((int) $_POST[$currentStat . "Delete"] > 0) {
				$sql = "DELETE FROM " . $table_prefix . "CHARACTER_STAT WHERE id = %d";
				$sql = $wpdb->prepare($sql, $_POST[$currentStat . "Delete"]);
			}
			elseif ((int) $_POST[$currentStat . "ID"] > 0) {
				$sql = "UPDATE " . $table_prefix . "CHARACTER_STAT
								SET level   =  %d,
									comment =  %s
								WHERE id = %d";
				$sql = $wpdb->prepare($sql, $_POST[$currentStat], $_POST[$currentStat . "Comment"], $_POST[$currentStat . "ID"]);
			}
			else {
				if ($currentStat == "Willpower") {
					$tempStatReasonID = establishTempStatReasonID('Initial');
					$tempStatID       = establishTempStatID('Willpower');
					$sql = "INSERT INTO " . $table_prefix . "CHARACTER_TEMPORARY_STAT (character_id, temporary_stat_id, temporary_stat_reason_id, awarded, amount, comment) "
						. " VALUES (%d, %d, %d, SYSDATE(), %d, 'Character creation')";
					$wpdb->query($wpdb->prepare($sql, $characterID, $tempStatID, $tempStatReasonID, $_POST[$currentStat]));
				}

				$sql = "INSERT INTO " . $table_prefix . "CHARACTER_STAT (character_id, stat_id, level, comment)
								VALUES (%d, %d, %d, %s)";
				$sql = $wpdb->prepare($sql, $characterID, $stat->id, $_POST[$currentStat], $_POST[$currentStat . "Comment"]);
			}
			$wpdb->query($sql);
		}
	}

	$maxOldSkillCount = $_POST['maxOldSkillCount'];
	$maxSkillCount    = $_POST['maxNewSkillCount'];
	$skillCounter = 0;
	$currentSkill = "";

	while ($skillCounter < $maxSkillCount) {
		$currentSkill = "skill" . $skillCounter;
		if ($_POST[$currentSkill] != "" && $_POST[$currentSkill] != "-100") {
			if ($skillCounter < $maxOldSkillCount) {
				if ((int) $_POST[$currentSkill . "Delete"] > 0) {
					$sql = "DELETE FROM " . $table_prefix . "CHARACTER_SKILL WHERE id = %d";
					$sql = $wpdb->prepare($sql, $_POST[$currentSkill . "Delete"]);
				}
				elseif ((int) $_POST[$currentSkill . "ID"] > 0) {
					$sql = "UPDATE " . $table_prefix . "CHARACTER_SKILL
									SET level   = %d,
										comment = %s
									WHERE id = %d";
					$sql = $wpdb->prepare($sql, $_POST[$currentSkill], $_POST[$currentSkill . "Comment"], $_POST[$currentSkill . "ID"]);
				}
			}
			else {
				$sql = "INSERT INTO " . $table_prefix . "CHARACTER_SKILL (character_id, skill_id, level, comment)
								VALUES (%d, %d, %d, %s)";
				$sql = $wpdb->prepare($sql, $characterID, $_POST[$currentSkill . "SID"], $_POST[$currentSkill], $_POST[$currentSkill . "Comment"]);
			}
			$wpdb->query($sql);
		}
		$skillCounter++;
	}

	$maxOldDisciplineCount = $_POST['maxOldDisciplineCount'];
	$maxDisciplineCount    = $_POST['maxNewDisciplineCount'];
	$disciplineCounter = 0;
	$currentDiscipline = "";

	while ($disciplineCounter < $maxDisciplineCount) {
		$currentDiscipline = "discipline" . $disciplineCounter;
		if ($_POST[$currentDiscipline] != "" && $_POST[$currentDiscipline] != "-100") {
			if ($disciplineCounter < $maxOldDisciplineCount) {
				if ((int) $_POST[$currentDiscipline . "Delete"] > 0) {
					$sql = "DELETE FROM " . $table_prefix . "CHARACTER_DISCIPLINE WHERE id = %d";
					$sql = $wpdb->prepare($sql, $_POST[$currentDiscipline . "Delete"]);
				}
				elseif ((int) $_POST[$currentDiscipline . "ID"] > 0) {
					$sql = "UPDATE " . $table_prefix . "CHARACTER_DISCIPLINE
									SET level   = %d,
										comment = %s
									WHERE id = %d";
					$sql = $wpdb->prepare($sql, $_POST[$currentDiscipline], $_POST[$currentDiscipline . "Comment"], $_POST[$currentDiscipline . "ID"]);
				}
			}
			else {
				$sql = "INSERT INTO " . $table_prefix . "CHARACTER_DISCIPLINE (character_id, discipline_id, level, comment)
								VALUES (%d, %d, %d, %s)";
				$sql = $wpdb->prepare($sql, $characterID, $_POST[$currentDiscipline . "SID"], $_POST[$currentDiscipline], $_POST[$currentDiscipline . "Comment"]);
			}
			$wpdb->query($sql);
		}
		$disciplineCounter++;
	}

	$maxOldComboDisciplineCount = $_POST['maxOldComboDisciplineCount'];
	$maxComboDisciplineCount    = $_POST['maxNewComboDisciplineCount'];
	$comboDisciplineCounter = 0;
	$currentComboDiscipline = "";
	while ($comboDisciplineCounter < $maxComboDisciplineCount) {
		$currentComboDiscipline = "comboDiscipline" . $comboDisciplineCounter;
		if ($_POST[$currentComboDiscipline] != "" && $_POST[$currentComboDiscipline] != "-100") {
			if ($comboDisciplineCounter < $maxOldComboDisciplineCount) {
				if ((int) $_POST[$currentComboDiscipline . "Delete"] > 0) {
					$sql = "DELETE FROM " . $table_prefix . "CHARACTER_COMBO_DISCIPLINE WHERE id = %d";
					$sql = $wpdb->prepare($sql, $_POST[$currentComboDiscipline . "Delete"]);
				}
				elseif ((int) $_POST[$currentComboDiscipline . "ID"] > 0) {
					$sql = "UPDATE " . $table_prefix . "CHARACTER_COMBO_DISCIPLINE
									SET comment = %s
									WHERE id = %d";
					$sql = $wpdb->prepare($sql, $_POST[$currentComboDiscipline . "Comment"], $_POST[$currentComboDiscipline . "ID"]);
				}
			}
			else {
				$sql = "INSERT INTO " . $table_prefix . "CHARACTER_COMBO_DISCIPLINE (character_id, combo_discipline_id, comment)
								VALUES (%d, %d, %s)";
				$sql = $wpdb->prepare($sql, $characterID, $_POST[$currentComboDiscipline . "SID"], $_POST[$currentComboDiscipline . "Comment"]);
			}
			$wpdb->query($sql);
			$sql = "";
		}
		$comboDisciplineCounter++;
	}

	$maxOldPathCount = $_POST['maxOldPathCount'];
	$maxPathCount    = $_POST['maxNewPathCount'];
	$pathCounter = 0;
	$currentPath = "";

	while ($pathCounter < $maxPathCount) {
		$currentPath = "path" . $pathCounter;
		if ($_POST[$currentPath] != "" && $_POST[$currentPath] != "-100") {
			if ($pathCounter < $maxOldPathCount) {
				if ((int) $_POST[$currentPath . "Delete"] > 0) {
					$sql = "DELETE FROM " . $table_prefix . "CHARACTER_PATH WHERE id = %d";
					$sql = $wpdb->prepare($sql, $_POST[$currentPath . "Delete"]);
				}
				elseif ((int) $_POST[$currentPath . "ID"] > 0) {
					$sql = "UPDATE " . $table_prefix . "CHARACTER_PATH
									SET level   = %d,
										comment = %s
									WHERE id = %d";
					$sql = $wpdb->prepare($sql, $_POST[$currentPath], $_POST[$currentPath . "Comment"], $_POST[$currentPath . "ID"]);
				}
			}
			else {
				$sql = "INSERT INTO " . $table_prefix . "CHARACTER_PATH (character_id,
																				 path_id,
																				 level,
																				 comment)
								VALUES (%d, %d, %d, %s)";
				$sql = $wpdb->prepare($sql, $characterID, $_POST[$currentPath . "SID"], $_POST[$currentPath], $_POST[$currentPath . "Comment"]);
			}
			$wpdb->query($sql);
		}
		$pathCounter++;
	}

	$maxOldRitualCount = $_POST['maxOldRitualCount'];
	$maxRitualCount    = $_POST['maxNewRitualCount'];
	$ritualCounter = 0;
	$currentRitual = "";

	while ($ritualCounter < $maxRitualCount) {
		$currentRitual = "ritual" . $ritualCounter;
		if ($_POST[$currentRitual] != "" && $_POST[$currentRitual] != "-100") {
			if ($ritualCounter < $maxOldRitualCount) {
				if ((int) $_POST[$currentRitual . "Delete"] > 0) {
					$sql = "DELETE FROM " . $table_prefix . "CHARACTER_RITUAL WHERE id = %d";
					$sql = $wpdb->prepare($sql, $_POST[$currentRitual . "Delete"]);
				}
				elseif ((int) $_POST[$currentRitual . "ID"] > 0) {
					$sql = "UPDATE " . $table_prefix . "CHARACTER_RITUAL
									SET level   = %d,
										comment = %s
									WHERE id = %d";
					$sql = $wpdb->prepare($sql, $_POST[$currentRitual], $_POST[$currentRitual . "Comment"], $_POST[$currentRitual . "ID"]);
				}
			}
			else {
				$sql = "INSERT INTO " . $table_prefix . "CHARACTER_RITUAL (character_id, ritual_id, level, comment)
								VALUES (%d, %d, %d, %s)";
				$sql = $wpdb->prepare($sql, $characterID, $_POST[$currentRitual . "SID"], $_POST[$currentRitual], $_POST[$currentRitual . "Comment"]);
			}
			$wpdb->query($sql);
		}
		$ritualCounter++;
	}

	$maxOldBackgroundCount = $_POST['maxOldBackgroundCount'];
	$maxBackgroundCount    = $_POST['maxNewBackgroundCount'];
	$backgroundCounter = 0;
	$currentBackground = "";

	while ($backgroundCounter < $maxBackgroundCount) {
		$currentBackground = "background" . $backgroundCounter;
		if ($_POST[$currentBackground] != "" && $_POST[$currentBackground] != "-100") {
			if ($backgroundCounter < $maxOldBackgroundCount) {
				if ((int) $_POST[$currentBackground . "Delete"] > 0) {
					$sql = "DELETE FROM " . $table_prefix . "CHARACTER_BACKGROUND WHERE id = %d";
					$sql = $wpdb->prepare($sql, $_POST[$currentBackground . "Delete"]);
				}
				elseif ((int) $_POST[$currentBackground . "ID"] > 0) {
					$sql = "UPDATE " . $table_prefix . "CHARACTER_BACKGROUND
									SET level   = %d,
										comment = %s
									WHERE id = %d";
					$sql = $wpdb->prepare($sql, $_POST[$currentBackground], $_POST[$currentBackground . "Comment"], $_POST[$currentBackground . "ID"]);
				}
			}
			else {
				$sql = "INSERT INTO " . $table_prefix . "CHARACTER_BACKGROUND (character_id, background_id, level, comment)
								VALUES (%d, %d, %d, %s)";
				$sql = $wpdb->prepare($sql, $characterID, $_POST[$currentBackground . "SID"], $_POST[$currentBackground], $_POST[$currentBackground . "Comment"]);
			}
			$wpdb->query($sql);
		}
		$backgroundCounter++;
	}

	$maxOldMeritCount = $_POST['maxOldMeritCount'];
	$maxMeritCount    = $_POST['maxNewMeritCount'];
	$meritCounter = 0;
	$currentMerit = "";

	while ($meritCounter < $maxMeritCount) {
		$currentMerit = "merit" . $meritCounter;
		if ($_POST[$currentMerit] != "" && $_POST[$currentMerit] != "-100") {
			if ($meritCounter < $maxOldMeritCount) {
				if ((int) $_POST[$currentMerit . "Delete"] > 0) {
					$sql = "DELETE FROM " . $table_prefix . "CHARACTER_MERIT WHERE id =  %d";
					$sql = $wpdb->prepare($sql, $_POST[$currentMerit . "Delete"]);
				}
				elseif ((int) $_POST[$currentMerit . "ID"] > 0) {
					$sql = "UPDATE " . $table_prefix . "CHARACTER_MERIT
									SET level   = %d,
										comment = %s
									WHERE id = %d";
					$sql = $wpdb->prepare($sql, $_POST[$currentMerit], $_POST[$currentMerit . "Comment"], $_POST[$currentMerit . "ID"]);
				}
			}
			else {
				$sql = "INSERT INTO " . $table_prefix . "CHARACTER_MERIT (character_id, merit_id, level, comment)
								VALUES (%d, %d, %d, %s)";
				$sql = $wpdb->prepare($sql, $characterID, $_POST[$currentMerit . "SID"], $_POST[$currentMerit], $_POST[$currentMerit . "Comment"]);
			}
			$wpdb->query($sql);
		}
		$meritCounter++;
	}

	$maxOldOfficeCount = $_POST['maxOldOfficeCount'];
	$maxOfficeCount    = $_POST['maxNewOfficeCount'];
	$officeCounter = 0;
	$currentOffice = "";

	while ($officeCounter < $maxOfficeCount) {
		$currentOffice = "office" . $officeCounter;
		if ($_POST[$currentOffice] != "" && $_POST[$currentOffice] != "-100") {
			if ($officeCounter < $maxOldOfficeCount) {
				if ((int) $_POST[$currentOffice . "Delete"] > 0) {
					$sql = "DELETE FROM " . $table_prefix . "CHARACTER_OFFICE WHERE id = " . $_POST[$currentOffice . "Delete"];
				}
				elseif ((int) $_POST[$currentOffice . "ID"] > 0) {
					$sql = "UPDATE " . $table_prefix . "CHARACTER_OFFICE
									SET comment = '" . $_POST[$currentOffice . "Comment"]  . "'
									WHERE id = " . $_POST[$currentOffice . "ID"];
				}
			}
			else {
				$sql = "INSERT INTO " . $table_prefix . "CHARACTER_OFFICE (character_id, office_id, domain_id, comment)
								VALUES (%d, %d, %d, %s)";
				$sql = $wpdb->prepare($sql, $characterID, $_POST[$currentOffice . "OID"], $_POST[$currentOffice . "CID"], $_POST[$currentOffice . "Comment"]);
			}
			$wpdb->query($sql);
		}
		$officeCounter++;
	}
	
	$config = getConfig();
	if ($config->USE_NATURE_DEMEANOUR == 'Y') {
		$dataarray = array(
			'NATURE_ID'    => $characterNature,
			'DEMEANOUR_ID' => $characterDemeanour,
		);
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "CHARACTER",
					$dataarray,
					array ('ID' => $characterID)
				);
	
	
	}
	
	touch_last_updated($characterID);

	return $characterID;
}


function deleteCharacter($characterID) {
	global $wpdb;
	$table_prefix = GVLARP_TABLE_PREFIX;

	$sql = "UPDATE " . $table_prefix . "CHARACTER
					SET DELETED = 'Y'
					WHERE ID = %d";

	$sql = $wpdb->prepare($sql, $characterID);
	$wpdb->query($sql);

	//echo "<p>SQL del: $sql</p>";
	$output = "Problem with delete, contact webmaster";

	$sql = "SELECT name
			FROM " . $table_prefix . "CHARACTER
			WHERE 
				ID = %d
				AND DELETED = 'Y'";

	$sql = $wpdb->prepare($sql, $characterID);
	//echo "<p>SQL check: $sql</p>";
	$characterNames = $wpdb->get_results($sql);
	//print_r($characterNames);
	$sqlOutput = "";

	foreach ($characterNames as $characterName) {
		$sqlOutput .= $characterName->name . " ";
	}

	if ($sqlOutput != "") {
		$output = "Deleted character " . $sqlOutput;
	}
	
	touch_last_updated($characterID);
	
	return $output;
}

?>