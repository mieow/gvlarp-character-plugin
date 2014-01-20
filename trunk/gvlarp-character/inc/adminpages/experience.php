<?php

/* EXPERIENCE APPROVALS
------------------------------------------------------------------- */

function character_experience() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	?>
	<div class="wrap">
		<h2>Experience Approvals</h2>
		<?php render_xp_approvals_page("xpapprove"); ?>
	</div>
	
	<?php
}

function render_xp_approvals_page($type){

    $testListTable['xpapprove'] = new gvadmin_xpapproval_table();
	
	$testListTable['xpapprove']->prepare_items();
 	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );
  ?>	

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="xpapprove-filter" method="get" action='<?php print htmlentities($current_url); ?>'>
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="xpapprove" />
		<?php $testListTable['xpapprove']->display() ?>
	</form>

    <?php

}


function render_costmodel_page($type){

	global $wpdb;
	
	$id = "";
	$type = "costmodel";

	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );

	$wpdb->show_errors();
	
	$id = 0;
	switch ($_REQUEST['action']) {
		case "loadmodel":
			$id = $_REQUEST['costmodel'];			
			break;
		case "save":
			if (isset($_REQUEST['do_new_' . $type]) || (isset($_REQUEST['do_save_' . $type]) && $_REQUEST['costmodel'] == 0) ) {
				/* insert */
				$dataarray = array (
					'NAME'        => $_REQUEST["costmodel_name"],
					'DESCRIPTION' => $_REQUEST["costmodel_desc"]
				);
				$wpdb->insert(GVLARP_TABLE_PREFIX . "COST_MODEL",
							$dataarray,
							array (
								'%s',
								'%s',
							)
						);
				
				$id = $wpdb->insert_id;
				if ($id == 0) {
					echo "<p style='color:red'><b>Error:</b> cost model could not be inserted (";
					echo ")</p>";
				} else {
				
					$updates = 0;
					$fail    = 0;
					for ($i=0;$i<11;$i++) {
								
						$dataarray = array (
							'COST_MODEL_ID'   => $id,
							'SEQUENCE'        => $i+1,
							'CURRENT_VALUE'   => $i,
							'NEXT_VALUE'      => $_REQUEST["nextvals"][$i],
							'FREEBIE_COST'    => $_REQUEST["freebie"][$i],
							'XP_COST'         => $_REQUEST["xpcost"][$i]
						);
						
						$wpdb->insert(GVLARP_TABLE_PREFIX . "COST_MODEL_STEP",
							$dataarray,
							array (
								'%d',
								'%d',
								'%d',
								'%d',
								'%d',
								'%d'
							)
						);
						if ($wpdb->insert_id) $updates++;
						else if ($wpdb->insert_id == 0) $fail = 1;
					}
					
					if ($fail) echo "<p style='color:red'>Could not add cost model</p>";
					elseif ($updates) echo "<p style='color:green'>Added cost model (ID: {$id})</p>";
					else echo "<p style='color:orange'>No additions made to cost model</p>";
				}
			} 
			elseif (isset($_REQUEST['do_delete_' . $type])) {
				if ($_REQUEST['costmodel'] == 0) {
					echo "<p style='color:red'>Select cost model before deleting</p>";
				} else {
					$id = $_REQUEST['costmodel'];
					/* delete */
					
					/* Check if model in use, clans, stats, skills, backgrounds
					   path, */
					$ok = 1;
					
					/* clans */
					$sql = "SELECT clans.NAME FROM " . GVLARP_TABLE_PREFIX . "CLAN clans
							WHERE	clans.CLAN_COST_MODEL_ID = %s
									OR clans.NONCLAN_COST_MODEL_ID = %s";
					$isused = $wpdb->get_results($wpdb->prepare($sql, $id, $id));
					if ($isused) {
						echo "<p style='color:red'>Cannot delete as this cost model is being used in the following clans:";
						echo "<ul>";
						foreach ($isused as $item)
							echo "<li style='color:red'>{$item->NAME}</li>";
						echo "</ul></p>";
						$ok = 0;
					}
					/* stats */
					$sql = "SELECT stats.NAME FROM " . GVLARP_TABLE_PREFIX . "STAT stats
							WHERE stats.COST_MODEL_ID = %s";
					$isused = $wpdb->get_results($wpdb->prepare($sql, $id));
					if ($isused) {
						echo "<p style='color:red'>Cannot delete as this cost model is being used in the following attributes:";
						echo "<ul>";
						foreach ($isused as $item)
							echo "<li style='color:red'>{$item->NAME}</li>";
						echo "</ul></p>";
						$ok = 0;
					}
					/* skills */
					$sql = "SELECT skills.NAME FROM " . GVLARP_TABLE_PREFIX . "SKILL skills
							WHERE skills.COST_MODEL_ID = %s";
					$isused = $wpdb->get_results($wpdb->prepare($sql, $id));
					if ($isused) {
						echo "<p style='color:red'>Cannot delete as this cost model is being used in the following abilities:";
						echo "<ul>";
						foreach ($isused as $item)
							echo "<li style='color:red'>{$item->NAME}</li>";
						echo "</ul></p>";
						$ok = 0;
					}
					/* backgrounds */
					$sql = "SELECT bgdnds.NAME FROM " . GVLARP_TABLE_PREFIX . "BACKGROUND bgdnds
							WHERE bgdnds.COST_MODEL_ID = %s";
					$isused = $wpdb->get_results($wpdb->prepare($sql, $id));
					if ($isused) {
						echo "<p style='color:red'>Cannot delete as this cost model is being used in the following backgrounds:";
						echo "<ul>";
						foreach ($isused as $item)
							echo "<li style='color:red'>{$item->NAME}</li>";
						echo "</ul></p>";
						$ok = 0;
					}
					/* path */
					$sql = "SELECT paths.NAME, disciplines.NAME as DISCIPLINE
							FROM 
								" . GVLARP_TABLE_PREFIX . "PATH paths,
								" . GVLARP_TABLE_PREFIX . "DISCIPLINE disciplines
							WHERE 
								paths.DISCIPLINE_ID = disciplines.ID
								AND paths.COST_MODEL_ID = %s";
					$isused = $wpdb->get_results($wpdb->prepare($sql, $id));
					if ($isused) {
						echo "<p style='color:red'>Cannot delete as this cost model is being used in the following paths:";
						echo "<ul>";
						foreach ($isused as $item)
							echo "<li style='color:red'>{$item->DISCIPLINE} path {$item->NAME}</li>";
						echo "</ul></p>";
						$ok = 0;
					}
					if ($ok) {
						/* delete _step */
						$sql = "delete from " . GVLARP_TABLE_PREFIX . "COST_MODEL_STEP where COST_MODEL_ID = %d;";
						$result = $wpdb->get_results($wpdb->prepare($sql, $id));
						/* delete cost model */
						$sql = "delete from " . GVLARP_TABLE_PREFIX . "COST_MODEL where ID = %d;";
						$result = $wpdb->get_results($wpdb->prepare($sql, $id));
						echo "<p style='color:green'>Deleted cost model {$_REQUEST['costmodel_name']}</p>";
					}
					
					
					$id = 0;
				}
				
			}
			else {
				/* update */
				$id = $_REQUEST['costmodel'];
				
				$updates = 0;
				$fail    = 0;
				for ($i=0;$i<11;$i++) {
							
					$dataarray = array (
						'COST_MODEL_ID'   => $id,
						'SEQUENCE'        => $i+1,
						'CURRENT_VALUE'   => $i,
						'NEXT_VALUE'      => $_REQUEST["nextvals"][$i],
						'FREEBIE_COST'    => $_REQUEST["freebie"][$i],
						'XP_COST'         => $_REQUEST["xpcost"][$i]
					);
					
					if (empty($_REQUEST["rowids"][$i])) {
						// add new step
						$wpdb->insert(GVLARP_TABLE_PREFIX . "COST_MODEL_STEP",
							$dataarray,
							array (
								'%d',
								'%d',
								'%d',
								'%d',
								'%d',
								'%d'
							)
						);
						$result = $wpdb->insert_id;
					} else {
						// update step
						$result = $wpdb->update(GVLARP_TABLE_PREFIX . "COST_MODEL_STEP",
							$dataarray,
							array ('ID' => $_REQUEST["rowids"][$i])
						);
					}
					
					if ($result) $updates++;
					else if ($result !== 0) $fail = 1;
				}
				
				$dataarray = array (
					'NAME'        => $_REQUEST["costmodel_name"],
					'DESCRIPTION' => $_REQUEST["costmodel_desc"]
				);
				
				$result = $wpdb->update(GVLARP_TABLE_PREFIX . "COST_MODEL",
					$dataarray,
					array (
						'ID' => $id
					)
				);
					
				if ($result) $updates++;
				else if ($result !== 0) $fail = 1;

				if ($fail) echo "<p style='color:red'>Could not update cost model</p>";
				elseif ($updates) echo "<p style='color:green'>Updated cost model</p>";
				else echo "<p style='color:orange'>No updates made to cost model</p>";
				
			}
			break;		
	}
	
	if ($id > 0) {
		
		$sql = "SELECT NAME, DESCRIPTION FROM " . GVLARP_TABLE_PREFIX . "COST_MODEL WHERE ID = %s";
		$sql = $wpdb->prepare($sql, $id);
		$result = $wpdb->get_results($sql);
		$name        = $result[0]->NAME;
		$description = $result[0]->DESCRIPTION;
		
		$sql = "SELECT * FROM " . GVLARP_TABLE_PREFIX . "COST_MODEL_STEP WHERE COST_MODEL_ID = %s ORDER BY SEQUENCE ASC";
		$sql = $wpdb->prepare($sql, $id);
		$result = $wpdb->get_results($sql);
			
	} else {
		$result = array();
		$name   = "";
		$description = "";
	}
	
	render_select_model();
	
	
?>
	<h4>Add/Edit Cost Model</h4>
	
	<p>If the next level is set to the same as the current level then no further levels can be bought.</p>
	<p>If the XP Cost is set to 0 then XP cannot be used to buy up anything using that model</p>
	<p>If the Freebie Cost is set to 0 then Freebie points cannot be used to buy the next level using that model</p>

	<form id="new-<?php print $type; ?>" method="post" action='<?php print htmlentities($current_url); ?>'>
	<input type="hidden" name="tab" value="<?php print $type; ?>" />
	<input type="hidden" name="costmodel" value="<?php print $_REQUEST['costmodel']; ?>" />
	<input type="hidden" name="action" value="save" />
	<p>Cost Model Name:
	<input type="text"   name="costmodel_name" value="<?php print $name; ?>"></p>
	<p>Description:
	<input type="text"   name="costmodel_desc" value="<?php print $description; ?>"></p>
	<table class="wp-list-table costmodels widefat">
	<tr>
		<th class="costmodels">Current Level</th>
		<th class="costmodels">Next Level</th>
		<th class="costmodels">Freebie Cost Current&gt;Next</th>
		<th class="costmodels">Experience Cost Current&gt;Next</th>
	</tr>
	<?php
		for ($i=0;$i<11;$i++) {
			echo "<tr>\n";
			echo "<td class='costmodels'>$i";
			echo "<input type='hidden' name='rowids[" . $i . "]'    value='" . $result[$i]->ID . "'>";
			echo "</td>\n";
			if (isset($result[$i]))
				echo "<td class='costmodels'><input type='text' name='nextvals[" . $i . "]'    value='" . $result[$i]->NEXT_VALUE . "' size=5 ></td>\n";
			else
				echo "<td class='costmodels'><input type='text' name='nextvals[" . $i . "]'    value='" . ($i == 10 ? 10 : $i + 1) . "' size=5 ></td>\n";
			echo "<td class='costmodels'><input type='text' name='freebie[" . $i . "]'    value='" . $result[$i]->FREEBIE_COST . "' size=5 ></td>\n";
			echo "<td class='costmodels'><input type='text' name='xpcost[" . $i . "]'    value='" . $result[$i]->XP_COST . "' size=5 ></td>\n";
			echo "</tr>";
		}
	
	?>
	
	</table>
	<input type="submit" name="do_save_<?php print $type; ?>" class="button-primary" value="Save" />
	<input type="submit" name="do_new_<?php print $type; ?>" class="button-primary" value="New" />
	<input type="submit" name="do_delete_<?php print $type; ?>" class="button-primary" value="Delete" />
	</form>

<?php
}

function render_select_model () {

	echo "<h3>Select Cost Model</h3>";
	echo "<form id='select_model_form' method='post'>\n";
	echo "<input type='hidden' name='tab'   value='costmodel' />\n";
	echo "<input type='hidden' name='action' value='loadmodel' />\n";
	echo "<select name='costmodel'>\n";
	echo "<option value='0'>[Select/New]</option>\n";
	
	foreach (get_costmodels() as $model) {
		echo "<option value='{$model->ID}' ";
		selected($_REQUEST['costmodel'],$model->ID);
		echo ">{$model->NAME}</option>\n";
	}
	
	echo "</select>\n";
	echo "<input type='submit' name='submit_model' class='button-primary' value='Go' />\n";
	echo "</form>\n";
	

}


/* 
-----------------------------------------------
XP APPROVALS TABLE
------------------------------------------------ */
class gvadmin_xpapproval_table extends GVMultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'spend',     
            'plural'    => 'spends',    
            'ajax'      => false        
        ) );
    }
	
	function approve($selectedID) {
		global $wpdb;
		$wpdb->show_errors();
		
		$sql = "SELECT * FROM " . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND
				WHERE ID = %d";
		$sql = $wpdb->prepare($sql, $selectedID);
		$data = $wpdb->get_results($sql);
		
		$table    = $data[0]->CHARTABLE;
		$approvalok = 0;
		
		/* add to sheet */
		switch ($table) {
		case 'CHARACTER_STAT':
			$result = $this->approve_standard($data[0]);
			break;
		case 'CHARACTER_SKILL':
			$result = $this->approve_standard($data[0]);
			break;
		case 'CHARACTER_DISCIPLINE':
			$result = $this->approve_standard($data[0]);
			break;
		case 'CHARACTER_PATH':
			$result = $this->approve_standard($data[0]);
			break;
		case 'CHARACTER_RITUAL':
			$result = $this->approve_standard($data[0]);
			break;
		case 'CHARACTER_MERIT':
			$result = $this->approve_merit($data[0]);
			break;
		case 'CHARACTER_COMBO_DISCIPLINE':
			$result = $this->approve_combo($data[0]);
			break;
		}
		if ($result) {
			echo "<p style='color:green'>Approved spend</p>";
			$approvalok = 1;
		}
		else echo "<p style='color:red'>Could not approve spend</p>";
		
		if ($approvalok) {
			/* update current XP */
			$sql = "SELECT ID FROM " . GVLARP_TABLE_PREFIX . "XP_REASON WHERE NAME = 'XP Spend'";
			$result = $wpdb->get_results($sql);
			
			$specialisation = $data[0]->SPECIALISATION ? ("(" . $data[0]->SPECIALISATION . ") ") : "";
			touch_last_updated($data[0]->CHARACTER_ID);
			
			$data = array (
				'PLAYER_ID'    => $data[0]->PLAYER_ID,
				'CHARACTER_ID' => $data[0]->CHARACTER_ID,
				'XP_REASON_ID' => $result[0]->ID,
				'AWARDED'      => $data[0]->AWARDED,
				'AMOUNT'       => $data[0]->AMOUNT,
				'COMMENT'	   => $specialisation . $data[0]->COMMENT
			);
			$wpdb->insert(GVLARP_TABLE_PREFIX . "PLAYER_XP",
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
			if ($wpdb->insert_id  == 0) {
				echo "<p style='color:red'><b>Error:</b> XP spend not added";
			} 
			
			/* then delete from pending */
			$this->delete_pending($selectedID);
		
		}
		
	}
	
	function approve_standard ($data2update) {
		global $wpdb;
	
		$wpdb->show_errors();
	
		if ($data2update->CHARTABLE_ID != 0) {
			$data = array (
				'LEVEL'   => $data2update->CHARTABLE_LEVEL,
				'COMMENT' => $data2update->SPECIALISATION,
			);
			$result = $wpdb->update(GVLARP_TABLE_PREFIX . $data2update->CHARTABLE,
				$data,
				array ('ID' => $data2update->CHARTABLE_ID)
			);
		} else {
			$data = array (
				'CHARACTER_ID'         => $data2update->CHARACTER_ID,
				$data2update->ITEMNAME => $data2update->ITEMTABLE_ID,
				'LEVEL'                => $data2update->CHARTABLE_LEVEL,
				'COMMENT'              => $data2update->SPECIALISATION,
			);
			$result = $wpdb->insert(GVLARP_TABLE_PREFIX . $data2update->CHARTABLE,
				$data,
				array (
					'%d', '%d', '%d', '%s'
				)
			);
		}
	
		return $result;
	}
	
	
	function approve_merit ($data2update) {
		global $wpdb;
	
		$wpdb->show_errors();
		
		/*
		If it is a flaw that you already have (i.e. CHARTABLE_ID is not 0) then remove it
		If it is a merit that you don't have then add it
		*/
		
		if ($data2update->CHARTABLE_ID == 0 && $data2update->CHARTABLE_LEVEL >= 0) { /* add merit */
			$data = array (
				'CHARACTER_ID'         => $data2update->CHARACTER_ID,
				$data2update->ITEMNAME => $data2update->ITEMTABLE_ID,
				'LEVEL'                => $data2update->CHARTABLE_LEVEL,
				'COMMENT'              => $data2update->SPECIALISATION,
			);
			$result = $wpdb->insert(GVLARP_TABLE_PREFIX . $data2update->CHARTABLE,
				$data,
				array (
					'%d', '%d', '%d', '%s'
				)
			);
		}
		elseif ($data2update->CHARTABLE_ID != 0 && $data2update->CHARTABLE_LEVEL < 0) { /* remove flaw */
			$sql = "DELETE FROM " . GVLARP_TABLE_PREFIX . "CHARACTER_MERIT where ID = %d;";
			$result = $wpdb->get_results($wpdb->prepare($sql, $data2update->CHARTABLE_ID));
			$result = 1;
		} 
		else {
			$result = null;
		}
	
		return $result;
	}
	function approve_combo ($data2update) {
		global $wpdb;
	
		$wpdb->show_errors();
		
		
		$data = array (
			'CHARACTER_ID'         => $data2update->CHARACTER_ID,
			'COMBO_DISCIPLINE_ID'  => $data2update->ITEMTABLE_ID,
			'COMMENT'              => $data2update->SPECIALISATION,
		);
		$result = $wpdb->insert(GVLARP_TABLE_PREFIX . $data2update->CHARTABLE,
			$data,
			array (
				'%d', '%d', '%s'
			)
		);
	
		return $result;
	}
	
 	function deny($selectedID) {
	
		$this->delete_pending($selectedID);
		
		echo "<p style='color:green'>Denied spends</p>";
		
	}
	
	function delete_pending($selectedID) {
		global $wpdb;
		$sql = "DELETE FROM " . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND
				WHERE ID = %d";
		
		$sql = $wpdb->prepare($sql, $selectedID);
		/* echo "<p>SQL: $sql</p>"; */
		$result = $wpdb->get_results($sql);
		
	}
  
    function column_default($item, $column_name){
        switch($column_name){
            case 'PLAYER':
                return $item->$column_name;
            case 'COMMENT':
                return $item->$column_name;
            case 'SPECIALISATION':
                return $item->$column_name;
             case 'TRAINING_NOTE':
                return $item->$column_name;
            case 'CHARTABLE':
                return $item->$column_name;
            case 'CHARTABLE_ID':
                return $item->$column_name;
            case 'CHARTABLE_LEVEL':
                return $item->$column_name;
          default:
                return print_r($item,true); 
        }
    }
 
	function column_amount($item) {
		$val = $item->AMOUNT;
		return ($val * -1);
	}
 
    function column_charactername($item){
        
        $actions = array(
            'approveit' => sprintf('<a href="?page=%s&amp;action=%s&amp;spend=%s&amp;tab=%s">Approve</a>',$_REQUEST['page'],'approveit',$item->ID, $this->type),
            'denyit'    => sprintf('<a href="?page=%s&amp;action=%s&amp;spend=%s&amp;tab=%s">Deny</a>',$_REQUEST['page'],'denyit',$item->ID, $this->type),
        );
        
        
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item->CHARACTERNAME,
            $item->ID,
            $this->row_actions($actions)
        );
    }
   
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],  
            $item->ID
        );
    }

    function get_columns(){
        $columns = array(
            'cb'             => '<input type="checkbox" />', 
            'CHARACTERNAME'  => 'Character',
            'PLAYER'         => 'Player',
            'COMMENT'        => 'Spend',
            'SPECIALISATION' => 'Specialisation',
			'AMOUNT'         => 'XP Spent',
			'TRAINING_NOTE'  => 'Training Note',
			'CHARTABLE'       => 'Character Table',
			'CHARTABLE_ID'    => 'Table ID',
			'CHARTABLE_LEVEL' => 'New Level'
        );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'CHARACTERNAME'  => array('CHARACTERNAME',true),
            'PLAYER'        => array('PLAYER',false),
            'AMOUNT'        => array('AMOUNT',false)
       );
        return $sortable_columns;
    }
	
	
	
    
    function get_bulk_actions() {
        $actions = array(
            'approveit' => 'Approve',
            'denyit'    => 'Deny'
       );
        return $actions;
    }
    
    function process_bulk_action() {
        		
		if( 'approveit'===$this->current_action() && $_REQUEST['tab'] == $this->type && isset($_REQUEST['spend'])) {

			if ('string' == gettype($_REQUEST['spend'])) {
				$this->approve($_REQUEST['spend']);
			} else {
				foreach ($_REQUEST['spend'] as $spend) {
					$this->approve($spend);
				}
			}
        }
        if( 'denyit'===$this->current_action() && $_REQUEST['tab'] == $this->type && isset($_REQUEST['spend'])) {
			if ('string' == gettype($_REQUEST['spend'])) {
				$this->deny($_REQUEST['spend']);
			} else {
				foreach ($_REQUEST['spend'] as $spend) {
					$this->deny($spend);
				}
			}
        }
     }


        
    function prepare_items() {
		global $wpdb;
        
        $columns  = $this->get_columns();
        $hidden   = array('CHARTABLE', 'CHARTABLE_ID', 'CHARTABLE_LEVEL');
        $sortable = $this->get_sortable_columns();
		
		$type = "xpapprove";
        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = $type;
		
        $this->process_bulk_action();
		
		/* get table data */
		$sql = "SELECT pending.ID, pending.PLAYER_ID, pending.CHARACTER_ID, 
					players.NAME as PLAYER,  characters.NAME as CHARACTERNAME, 
					pending.CHARTABLE, pending.CHARTABLE_ID, pending.CHARTABLE_LEVEL,
					pending.AMOUNT, pending.COMMENT, pending.SPECIALISATION,
					pending.TRAINING_NOTE
				FROM
					" . GVLARP_TABLE_PREFIX . "PLAYER players,
					" . GVLARP_TABLE_PREFIX . "CHARACTER characters,
					" . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND pending
				WHERE
					players.ID = pending.PLAYER_ID
					AND characters.ID = pending.CHARACTER_ID";
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']))
			$sql .= " ORDER BY {$_REQUEST['orderby']} {$_REQUEST['order']}";
		
		/* echo "<p>SQL: $sql</p>"; */
		$data =$wpdb->get_results($sql);
		$this->items = $data;
        

        $current_page = $this->get_pagenum();
        $total_items = count($data);

        
        $this->items = $data;
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $total_items,                  
            'total_pages' => 1
        ) );
    }

}


/* ASSIGN EXPERIENCE
------------------------------------------------------------------- */

function character_xp_assign() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	?>
	<div class="wrap">
		<h2>Assign Experience</h2>
		<?php render_xp_assign_page(); ?>
	</div>
	
	<?php
}

function addPlayerXP($player, $character, $xpReason, $value, $comment) {
	global $wpdb;
	$table_prefix = GVLARP_TABLE_PREFIX;
	$sql = "INSERT INTO " . $table_prefix . "PLAYER_XP (player_id, amount, character_id, xp_reason_id, comment, awarded)
					VALUES (%d, %d, %d, %d, %s, SYSDATE())";
	$wpdb->query($wpdb->prepare($sql, $player, ((int) $value), $character, $xpReason, $comment));
	
	touch_last_updated($character);
}


function render_xp_assign_page(){

	$type = "xpassign";
	
	if ($_REQUEST['do_update']) {
		//echo "<p>Saving...</p>";
		//print_r($_REQUEST['xp_reason']);
		//print_r($_REQUEST['xp_change']);
		//print_r($_REQUEST['comment']);
		
		$reasons  = $_REQUEST['xp_reason'];
		$comments = $_REQUEST['comment'];
		$players  = $_REQUEST['xp_player'];
		
		foreach( $_REQUEST['xp_change'] as $characterID => $change) {
			if (!empty($change) && is_numeric($change)) {
				
				addPlayerXP(
					$players[$characterID],
					$characterID,
					$reasons[$characterID],
					$change,
					$comments[$characterID]);
			}
		
		}
		
	}
	
	
	$config = getConfig();

 	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );
  ?>	

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="<?php print $type ?>-filter" method="get" action='<?php print htmlentities($current_url); ?>'>
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="<?php print $type ?>" />
		
		<table class="wp-list-table widefat">
		<tr><th class="manage-column">Player</th>
			<th class="manage-column">Character</th><th>Character Status</th>
			<th class="manage-column">Current Experience</th>
			<th class="manage-column">Reason</th>
			<th class="manage-column">XP Change</th>
			<th class="manage-column">Comment</th></tr>
		<?php
			if ($config->ASSIGN_XP_BY_PLAYER == 'Y')
				render_xp_by_player();
			else
				render_xp_by_character();
		
		?>
		</table>
		<input type="submit" name="do_update" class="button-primary" value="Update" />
		
	</form>

    <?php

}


function render_xp_by_player () {
	global $wpdb;
	
	$sql = "SELECT
				player.ID,
				SUM(xp.amount) as PLAYER_XP
			FROM
				" . GVLARP_TABLE_PREFIX . "PLAYER_XP xp,
				" . GVLARP_TABLE_PREFIX . "PLAYER player,
				" . GVLARP_TABLE_PREFIX . "PLAYER_STATUS pstatus
			WHERE
				pstatus.ID = player.PLAYER_STATUS_ID
				AND xp.PLAYER_ID = player.ID
				AND pstatus.NAME = 'Active'
			GROUP BY player.ID";
	//echo "<p>SQL1: $sql</p>";
	$player_xp = $wpdb->get_results($sql, OBJECT_K);
	
	//print_r($player_xp);
	
	$sql = "SELECT
				chara.ID as ID,
				chara.name as CHARACTERNAME,
				player.name as PLAYER,
				player.ID as PLAYER_ID,
				cstatus.name as CSTATUS
			FROM
				" . GVLARP_TABLE_PREFIX . "CHARACTER chara,
				" . GVLARP_TABLE_PREFIX . "PLAYER player,
				" . GVLARP_TABLE_PREFIX . "PLAYER_STATUS pstatus,
				" . GVLARP_TABLE_PREFIX . "CHARACTER_STATUS cstatus
			WHERE
				chara.PLAYER_ID = player.ID
				AND pstatus.ID = player.PLAYER_STATUS_ID
				AND cstatus.ID = chara.CHARACTER_STATUS_ID
				AND pstatus.NAME = 'Active'
				AND cstatus.NAME != 'Dead'
				AND chara.DELETED != 'Y'
				AND chara.VISIBLE = 'Y'
			GROUP BY chara.ID
			ORDER BY PLAYER, CHARACTERNAME, cstatus.ID";
	
	//echo "<p>SQL2: $sql</p>";
	$results = $wpdb->get_results($sql);
	
	$output = "";
	$lastplayer = "";
	$rowclasses = array(" class=\"alternate\"", "");
	$rowclass = 1;
	foreach ($results as $row) {
		if ($lastplayer == $row->PLAYER) {
			$player = "&nbsp;";
			$xp = "&nbsp;";
		} else {
			$player = $row->PLAYER;
			$xp = $player_xp[$row->PLAYER_ID]->PLAYER_XP;
			$rowclass = !$rowclass;
		}
		$lastplayer = $row->PLAYER;
	
		$output .= "<tr" . $rowclasses[$rowclass] . ">";
		$output .= "<td>$player<input name='xp_player[{$row->ID}]' value=\"{$row->PLAYER_ID}\" type=\"hidden\" /></td>";
		$output .= "<td>{$row->CHARACTERNAME}</td><td>{$row->CSTATUS}</td><td>$xp</td>";
		$output .= "<td><select name='xp_reason[{$row->ID}]'>\n";
		foreach (listXpReasons() as $reason) {
			$output .= "<option value='{$reason->id}'>{$reason->name}</option>\n";
		}
		$output .= "</select></td>\n";
		$output .= "<td><input name='xp_change[{$row->ID}]' value=\"\" type=\"text\" size=4 /></td>";
		$output .= "<td><input name='comment[{$row->ID}]' value=\"\" type=\"text\" size=30 /></td>";
		$output .= "</tr>";
	}
	
	echo $output;

}
function render_xp_by_character () {
	global $wpdb;

	$sql = "SELECT
				chara.ID as ID,
				chara.name as CHARACTERNAME,
				player.name as PLAYER,
				player.ID as PLAYER_ID,
				cstatus.name as CSTATUS,
				SUM(xp.amount) as CHARACTER_XP
			FROM
				" . GVLARP_TABLE_PREFIX . "CHARACTER chara,
				" . GVLARP_TABLE_PREFIX . "PLAYER player,
				" . GVLARP_TABLE_PREFIX . "PLAYER_STATUS pstatus,
				" . GVLARP_TABLE_PREFIX . "CHARACTER_STATUS cstatus,
				" . GVLARP_TABLE_PREFIX . "PLAYER_XP xp
			WHERE
				chara.PLAYER_ID = player.ID
				AND pstatus.ID = player.PLAYER_STATUS_ID
				AND cstatus.ID = chara.CHARACTER_STATUS_ID
				AND xp.CHARACTER_ID = chara.ID
				AND xp.PLAYER_ID = player.ID
				AND pstatus.NAME = 'Active'
				AND cstatus.NAME != 'Dead'
				AND chara.DELETED != 'Y'
				AND chara.VISIBLE = 'Y'
			GROUP BY chara.ID
			ORDER BY PLAYER, CHARACTERNAME, cstatus.ID, CHARACTER_XP";
	
	//echo "<p>SQL: $sql</p>";
	$results = $wpdb->get_results($sql);
	//print_r ($results);
	
	$output = "";
	$lastplayer = "";
	foreach ($results as $row) {
		$player = $lastplayer == $row->PLAYER ? "&nbsp;" : $row->PLAYER;
		$lastplayer = $row->PLAYER;
	
		$output .= "<tr>";
		$output .= "<td>$player<input name='xp_player[{$row->ID}]' value=\"{$row->PLAYER_ID}\" type=\"hidden\" /></td>";
		$output .= "<td>{$row->CHARACTERNAME}</td><td>{$row->CSTATUS}</td><td>{$row->CHARACTER_XP}</td>";
		$output .= "<td><select name='xp_reason[{$row->ID}]'>\n";
		foreach (listXpReasons() as $reason) {
			$output .= "<option value='{$reason->id}'>{$reason->name}</option>\n";
		}
		$output .= "</select></td>\n";
		$output .= "<td><input name='xp_change[{$row->ID}]' value=\"\" type=\"text\" size=4 /></td>";
		$output .= "<td><input name='comment[{$row->ID}]' value=\"\" type=\"text\" size=30 /></td>";
		$output .= "</tr>";
	}
	
	echo $output;

}


/* 
-----------------------------------------------
XP APPROVALS TABLE
------------------------------------------------ */
class gvadmin_xpassign_table extends GVMultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'assignment',     
            'plural'    => 'assignments',    
            'ajax'      => false        
        ) );
    }
  
    function column_default($item, $column_name){
        switch($column_name){
            case 'PLAYER':
                return $item->$column_name;
            case 'CHARACTER':
                return $item->$column_name;
            case 'CSTATUS':
                return $item->$column_name;
            case 'CURRENT_XP':
                return $item->$column_name;
          default:
                return print_r($item,true); 
        }
    }
 
	function column_xp_reason($item) {
	
		$output = '<select name="xp_reason[%s]">\n';
		foreach (listXpReasons() as $reason) {
			$output .= "<option value='{$reason->id}'>{$reason->name}</option>\n";
		}
		$output .= "</select>\n";
	
		return sprintf($output, $item->ID);
	}
	function column_xp_change($item) {
		$output = '<input name="xp_change[%s]" value="" type="text" size=4 />';
	
		return sprintf($output, $item->ID);
	}
	function column_comment($item) {
		$output = '<input name="comment[%s]" value="" type="text" size=15 />';
	
		return sprintf($output, $item->ID);
	}
 
    function column_charactername($item){
                
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>',
            $item->CHARACTERNAME,
            $item->ID
        );
    }
   
    function get_columns(){
        $columns = array(
            'PLAYER'         => 'Player',
            'CHARACTERNAME'  => 'Character',
			'CSTATUS'        => 'Character Status',
            'CURRENT_XP'     => 'Current XP',
            'XP_REASON'      => 'Reason for change',
			'XP_CHANGE'      => 'XP Change',
			'COMMENT'        => 'Comment'
        );
        return $columns;
		
    }
    
	        
    function prepare_items() {
		global $wpdb;
        
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = array();
		
		$type = "xpassign";
        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = $type;
		
        //$this->process_bulk_action();
		
		
		/* get table data */
		$sql = "SELECT
					chara.ID as ID,
					chara.name as CHARACTERNAME,
					player.name as PLAYER,
					cstatus.name as CSTATUS,
					0 as CHARACTER_XP
				FROM
					" . GVLARP_TABLE_PREFIX . "CHARACTER chara,
					" . GVLARP_TABLE_PREFIX . "PLAYER player,
					" . GVLARP_TABLE_PREFIX . "PLAYER_STATUS pstatus,
					" . GVLARP_TABLE_PREFIX . "CHARACTER_STATUS cstatus
				WHERE
					chara.PLAYER_ID = player.ID
					AND pstatus.ID = player.PLAYER_STATUS_ID
					AND cstatus.ID = chara.CHARACTER_STATUS_ID
					AND pstatus.NAME = 'Active'
					AND cstatus.NAME != 'Dead'
					AND chara.DELETED != 'Y'
				ORDER BY PLAYER, CHARACTERNAME, cstatus.ID, CHARACTER_XP";
		
		//echo "<p>SQL: $sql</p>";
		$data =$wpdb->get_results($sql);
		$this->items = $data;
        

        $current_page = $this->get_pagenum();
        $total_items = count($data);

        
        $this->items = $data;
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $total_items,                  
            'total_pages' => 1
        ) );
    }

}


?>