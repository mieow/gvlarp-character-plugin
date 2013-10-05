<?php

/*
function character_datatables2() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	?>
	<div class="wrap">
		<h2>Database Tables</h2>
		<script type="text/javascript">
			function tabSwitch(tab) {
				setSwitchState('stat', tab == 'stat');
				setSwitchState('skill', tab == 'skill');
				setSwitchState('disc', tab == 'disc');
				return false;
			}
			function setSwitchState(tab, show) {
				document.getElementById('gv-'+tab).style.display = show ? 'block' : 'none';
				document.getElementById('gvm-'+tab).className = show ? 'shown' : '';
			}
		</script>
		<div class="gvadmin_nav">
			<ul>
				<li><?php echo get_tabanchor('stat',  'Attributes', 'stat'); ?></li>
				<li><?php echo get_tabanchor('skill', 'Abilities', 'skill'); ?></li>
				<li>
			</ul>
		</div>
		<div class="gvadmin_content">
			<div id="gv-stat" <?php echo get_tabdisplay('stat', 'stat'); ?>>
				<h1>Attributes and Stats</h1>
				<?php render_stat_page("stat"); ?>
			</div>
			<div id="gv-skill" <?php echo get_tabdisplay("skill", 'stat'); ?>>
				<h1>Abilities</h1>
				<?php render_skill_page("skill"); ?>
			</div>
		</div>

	</div>
	
	<?php
} */

function render_stat_page($type){


	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
    $testListTable[$type] = new gvadmin_stats_table();
	$doaction = stat_input_validation($type);
	
	if ($doaction == "save-$type") { 
		$testListTable[$type]->edit_stat($type);
	} 
	
	if (isset($_REQUEST['action']))
		render_stat_form($type, $doaction); 
	
    $testListTable[$type]->prepare_items($type);
	$current_url = remove_query_arg( 'action', $current_url );

   ?>	

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="<?php print $type ?>-filter" method="get" action='<?php print $current_url; ?>'>
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="<?php print $type ?>" />
 		<?php $testListTable[$type]->display() ?>
	</form>

    <?php 
}

function render_skill_page(){


    $testListTable["skill"] = new gvadmin_skills_table();
	$doaction = skill_input_validation("skill");
	
	/* echo "<p>action: $doaction</p>"; */
	
	if ($doaction == "add-skill") {
		$testListTable["skill"]->add_skill();		
	}
	if ($doaction == "save-skill") {
		$testListTable["skill"]->edit_skill();				
	}

	render_skill_add_form("skill", $doaction);
	$testListTable["skill"]->prepare_items();
	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );
	?>	

	<form id="skill-filter" method="get" action='<?php print $current_url; ?>'>
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="skill" />
 		<?php $testListTable["skill"]->display() ?>
	</form>

    <?php 
}

function render_skill_add_form($type, $addaction) {
	global $wpdb;

	$id   = $_REQUEST['ability'];
		
	if ('fix-' . $type == $addaction) {
		$name = $_REQUEST[$type . "_name"];
		$desc = $_REQUEST[$type . "_desc"];
		$grouping = $_REQUEST[$type . "_group"];
		$costmodel_id = $_REQUEST[$type . "_costmodel"];
		$specialise_at = $_REQUEST[$type . "_spec_at"];
		$multiple = $_REQUEST[$type . "_multiple"];
		$visible = $_REQUEST[$type . "_visible"];
		
		$nextaction = $_REQUEST['action'];

	} elseif ('edit-' . $type == $addaction) {
		$sql = "SELECT * FROM " . GVLARP_TABLE_PREFIX . "SKILL WHERE ID = %s";
		$sql = $wpdb->prepare($sql, $id);
		$data =$wpdb->get_results($sql);
		/* echo "<p>SQL: $sql</p>";
		print_r($data); */
		
		$name = $data[0]->NAME;
		$desc = $data[0]->DESCRIPTION;
		$grouping = $data[0]->GROUPING;
		$costmodel_id = $data[0]->COST_MODEL_ID;
		$specialise_at = $data[0]->SPECIALISATION_AT;
		$multiple = $data[0]->MULTIPLE;
		$visible = $data[0]->VISIBLE;
		
		$nextaction = "save";

	} else {
	
		$name = "";
		$desc = "";
		$grouping = "";
		$costmodel_id = 0;
		$specialise_at = 4;
		$multiple = 'N';
		$visible = 'Y';
		
		$nextaction = "add";
		
	}
	
	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );
	?>
	<form id="new-<?php print $type; ?>" method="post" action='<?php print $current_url; ?>'>
		<input type="hidden" name="<?php print $type; ?>_id" value="<?php print $id; ?>"/>
		<input type="hidden" name="tab" value="<?php print $type; ?>" />
		<input type="hidden" name="action" value="<?php print $nextaction; ?>" />
		<table>
		<tr>
			<td>Name:</td>
			<td><input type="text" name="<?php print $type; ?>_name" value="<?php print $name; ?>" size=20 /></td>
			<td>Grouping:</td>
			<td><input type="text" name="<?php print $type; ?>_group" value="<?php print $grouping; ?>" size=20 /></td>
			<td>Specialise at level:  </td>
			<td colspan=3><input type="text" name="<?php print $type; ?>_spec_at" value="<?php print $specialise_at; ?>" size=10 /></td>
		</tr>
		<tr>
			<td>Cost Model:  </td>
			<td><select name="<?php print $type; ?>_costmodel">
					<?php
						foreach (get_costmodels() as $costmodel) {
							print "<option value='{$costmodel->ID}' ";
							selected($costmodel->ID, $costmodel_id);
							echo ">{$costmodel->NAME}</option>";
						}
					?>
				</select>
			</td>
			<td>Visible to Players: </td><td>
				<select name="<?php print $type; ?>_visible">
					<option value="N" <?php selected($visible, "N"); ?>>No</option>
					<option value="Y" <?php selected($visible, "Y"); ?>>Yes</option>
				</select>
			</td>
			<td>Multiple?: </td><td>
				<select name="<?php print $type; ?>_multiple">
					<option value="N" <?php echo selected($multiple, "N"); ?>>No</option>
					<option value="Y" <?php echo selected($multiple, "Y"); ?>>Yes</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>Description:  </td>
			<td colspan=5><input type="text" name="<?php print $type; ?>_desc" value="<?php print $desc; ?>" size=90 /></td> <!-- check sizes -->
		</tr>
		</table>
		<input type="submit" name="save_<?php print $type; ?>" class="button-primary" value="Save" />
	</form>
	
	<?php

}

function render_stat_form($type, $addaction) {
	global $wpdb;

	$id   = $_REQUEST['stat'];
		
	if ('fix-' . $type == $addaction) {
		$name = $_REQUEST[$type . "_name"];
		$grouping = $_REQUEST[$type . "_group"];
		$ordering = $_REQUEST[$type . "_order"];
		$costmodel_id = $_REQUEST[$type . "_costmodel"];
		$specialise_at = $_REQUEST[$type . "_spec_at"];
		$desc = $_REQUEST[$type . "_desc"];

	} else {
	
		$sql = "SELECT * FROM " . GVLARP_TABLE_PREFIX . "STAT WHERE ID = %s";
		$sql = $wpdb->prepare($sql, $id);
		$data =$wpdb->get_results($sql);
		/* echo "<p>SQL: $sql</p>";
		print_r($data); */
		
		$name = $data[0]->NAME;
		$grouping = $data[0]->GROUPING;
		$ordering = $data[0]->ORDERING;
		$costmodel_id = $data[0]->COST_MODEL_ID;
		$specialise_at = $data[0]->SPECIALISATION_AT;
		$desc = $data[0]->DESCRIPTION;
		
		
	}
	$nextaction = "save";
	
	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );
	?>
	<form id="new-<?php print $type; ?>" method="post" action='<?php print $current_url; ?>'>
		<input type="hidden" name="<?php print $type; ?>_id" value="<?php print $id; ?>"/>
		<input type="hidden" name="tab" value="<?php print $type; ?>" />
		<input type="hidden" name="action" value="<?php print $nextaction; ?>" />
		<input type="hidden" name="<?php print $type; ?>_name" value="<?php print $name; ?>" />
		<input type="hidden" name="<?php print $type; ?>_group" value="<?php print $grouping; ?>" />
		<input type="hidden" name="<?php print $type; ?>_order" value="<?php print $ordering; ?>" />
		<table>
		<tr>
			<td>Name:</td>
			<td><?php print $name; ?></td>
			<td>Grouping:</td>
			<td><?php print $grouping; ?></td>
			<td>Display Order:</td>
			<td><?php print $ordering; ?></td>
		</tr>
		<tr>
			<td>Cost Model:  </td>
			<td><select name="<?php print $type; ?>_costmodel">
					<?php
						foreach (get_costmodels() as $costmodel) {
							print "<option value='{$costmodel->ID}' ";
							selected($costmodel->ID, $costmodel_id);
							echo ">{$costmodel->NAME}</option>";
						}
					?>
				</select>
			</td>
			<td>Specialise at level:  </td>
			<td colspan=3><input type="text" name="<?php print $type; ?>_spec_at" value="<?php print $specialise_at; ?>" size=10 /></td>
		</tr>
		</tr>
		<tr>
			<td>Description:  </td>
			<td colspan=5><input type="text" name="<?php print $type; ?>_desc" value="<?php print $desc; ?>" size=90 /></td> <!-- check sizes -->
		</tr>
		</table>
		<input type="submit" name="save_<?php print $type; ?>" class="button-primary" value="Save <?php print ucfirst($type); ?>" />
	</form>
	
	<?php

}

function stat_input_validation($type) {
	$doaction = "save";
	
	if (!empty($_REQUEST[$type . '_name'])){
	
		$doaction = $_REQUEST['action'] . "-" . $type;
		
		if (empty($_REQUEST[$type . '_desc']) || $_REQUEST[$type . '_desc'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Description is missing</p>";
		}
	
	}
	
	return $doaction;

}
function skill_input_validation($type) {
	
	
	if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && $_REQUEST['tab'] == $type)
		$doaction = "edit-$type";

	if (!empty($_REQUEST[$type . '_name'])){
	
		$doaction = $_REQUEST['action'] . "-" . $type;
		
		if (empty($_REQUEST[$type . '_desc']) || $_REQUEST[$type . '_desc'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Description is missing</p>";
		}
		if (empty($_REQUEST[$type . '_group']) || $_REQUEST[$type . '_group'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Grouping is missing</p>";
		}
		if (empty($_REQUEST[$type . '_spec_at']) || $_REQUEST[$type . '_spec_at'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Specialisation level is missing</p>";
		} else if ($_REQUEST[$type . '_spec_at'] <= 0) {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Specialisation level should be a number greater than 0</p>";
		} 
	
	}
	
	return $doaction;

}

/* 
-----------------------------------------------
STATS TABLE
------------------------------------------------ */


class gvadmin_stats_table extends GVMultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'stat',     
            'plural'    => 'stats',    
            'ajax'      => false        
        ) );
    }

 	function edit_stat($type) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'DESCRIPTION'       => $_REQUEST[$type . '_desc'],
						'COST_MODEL_ID'     => $_REQUEST[$type . '_costmodel'],
						'SPECIALISATION_AT' => $_REQUEST[$type . '_spec_at']
					);
		
		
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "STAT",
					$dataarray,
					array (
						'ID' => $_REQUEST[$type . '_id']
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated Stat</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No updates made</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update stat ({$_REQUEST[$type . '_id']})</p>";
		}
		
	}
   
    function column_default($item, $column_name){
        switch($column_name){
            case 'DESCRIPTION':
                return $item->$column_name;
            case 'GROUPING':
                return $item->$column_name;
            case 'ORDERING':
                return $item->$column_name;
            case 'COST_MODEL':
                return $item->$column_name;
            case 'SPECIALISATION_AT':
                return $item->$column_name;
            default:
                return print_r($item,true); 
        }
    }
 
    function column_name($item){
        
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&amp;action=%s&stat=%s&amp;tab=%s">Edit</a>',$_REQUEST['page'],'edit',$item->ID, $this->type),
        );
        
        
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item->NAME,
            $item->ID,
            $this->row_actions($actions)
        );
    }
   

    function get_columns(){
        $columns = array(
            'NAME'              => 'Name',
            'DESCRIPTION'       => 'Description',
            'GROUPING'          => 'Grouping',
            'ORDERING'          => 'Display Order',
            'COST_MODEL'        => 'Cost Model',
            'SPECIALISATION_AT' => 'Specialise at level'
        );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'NAME'        => array('NAME',true),
            'GROUPING'    => array('GROUPING',false),
            'ORDERING'    => array('ORDERING',false)
        );
        return $sortable_columns;
    }
	
	
	
    
    function get_bulk_actions() {
        $actions = array(
            
       );
        return $actions;
    }
    
    function process_bulk_action() {
        		
     }

        
    function prepare_items() {
        global $wpdb; 
        
        $columns = $this->get_columns();
        $hidden = array("ORDERING");
        $sortable = $this->get_sortable_columns();
		
		$type = "stat";
        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = $type;
        
        $this->process_bulk_action();
		
		/* Get the data from the database */
		$sql = "select stats.ID, stats.NAME, stats.DESCRIPTION, stats.GROUPING, stats.ORDERING,
					models.NAME as COST_MODEL, stats.SPECIALISATION_AT
				from 
					" . GVLARP_TABLE_PREFIX . "STAT stats,
					" . GVLARP_TABLE_PREFIX . "COST_MODEL models
				where models.ID = stats.COST_MODEL_ID";
				
		/* order the data according to sort columns */
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']))
			$sql .= " ORDER BY stats.{$_REQUEST['orderby']} {$_REQUEST['order']}";
		
		$sql .= ";";
		
		/* echo "<p>SQL: $sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql,''));
        
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


/* 
-----------------------------------------------
SKILLS TABLE
------------------------------------------------ */


class gvadmin_skills_table extends GVMultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'ability',     
            'plural'    => 'abilities',    
            'ajax'      => false        
        ) );
    }
 	function add_skill() {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME'        => $_REQUEST['skill_name'],
						'DESCRIPTION' => $_REQUEST['skill_desc'],
						'GROUPING'    => $_REQUEST['skill_group'],
						'COST_MODEL_ID' => $_REQUEST['skill_costmodel'],
						'MULTIPLE'    => $_REQUEST['skill_multiple'],
						'VISIBLE'     => $_REQUEST['skill_visible'],
						'SPECIALISATION_AT' => $_REQUEST['skill_spec_at']
					);
		
		/* print_r($dataarray); */
		
		$wpdb->insert(GVLARP_TABLE_PREFIX . "SKILL",
					$dataarray,
					array (
						'%s',
						'%s',
						'%s',
						'%d',
						'%s',
						'%s',
						'%d'
					)
				);
		
		if ($wpdb->insert_id == 0) {
			echo "<p style='color:red'><b>Error:</b> " . stripslashes($_REQUEST['skill_name']) . " could not be inserted (";
			$wpdb->print_error();
			echo ")</p>";
		} else {
			echo "<p style='color:green'>Added " . stripslashes($_REQUEST['skill_group']) . " '" . stripslashes($_REQUEST['skill_name']) . "' (ID: {$wpdb->insert_id})</p>";
		}
	}

 	function edit_skill() {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME'        => $_REQUEST['skill_name'],
						'DESCRIPTION' => $_REQUEST['skill_desc'],
						'GROUPING'    => $_REQUEST['skill_group'],
						'COST_MODEL_ID' => $_REQUEST['skill_costmodel'],
						'MULTIPLE'    => $_REQUEST['skill_multiple'],
						'VISIBLE'     => $_REQUEST['skill_visible'],
						'SPECIALISATION_AT' => $_REQUEST['skill_spec_at']
					);
		
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "SKILL",
					$dataarray,
					array (
						'ID' => $_REQUEST['skill_id']
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated Ability</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No updates made</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update Ability ({$_REQUEST[$type . '_id']})</p>";
		}
		 
	}
	
 	function delete_skill($selectedID) {
		global $wpdb;
		
		/* Check if question in use */
		$sql = "select characters.NAME
				from " . GVLARP_TABLE_PREFIX . "CHARACTER_SKILL charskills, 
					" . GVLARP_TABLE_PREFIX . "CHARACTER characters,
					" . GVLARP_TABLE_PREFIX . "SKILL skills
				where charskills.SKILL_ID = skills.ID 
					and characters.ID = charskills.CHARACTER_ID
					and skills.ID = %d;";
					
		$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this skill has been use for the following characters:";
			echo "<ul>";
			foreach ($isused as $item)
				echo "<li style='color:red'>{$item->NAME}</li>";
			echo "</ul></p>";
			return;
			
		} else {
		
			$sql = "delete from " . GVLARP_TABLE_PREFIX . "SKILL where ID = %d;";
			
			$result = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		
			echo "<p style='color:green'>Deleted skill $selectedID</p>";
		}
	}
  
    function column_default($item, $column_name){
        switch($column_name){
            case 'DESCRIPTION':
                return $item->$column_name;
            case 'GROUPING':
                return $item->$column_name;
            case 'COST_MODEL':
                return $item->$column_name;
            case 'SPECIALISATION_AT':
                return $item->$column_name;
            case 'VISIBLE':
                return $item->$column_name;
            default:
                return print_r($item,true); 
        }
    }
 
 	function column_multiple($item){
		return ($item->MULTIPLE == "Y") ? "Yes" : "No";
    }
   function column_name($item){
        
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&amp;action=%s&ability=%s&amp;tab=%s">Edit</a>',$_REQUEST['page'],'edit',$item->ID, $this->type),
            'delete'    => sprintf('<a href="?page=%s&amp;action=%s&ability=%s&amp;tab=%s">Delete</a>',$_REQUEST['page'],'delete',$item->ID, $this->type),
       );
        
        
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item->NAME,
            $item->ID,
            $this->row_actions($actions)
        );
    }
   

    function get_columns(){
        $columns = array(
            'cb'                => '<input type="checkbox" />', 
            'NAME'              => 'Name',
            'DESCRIPTION'       => 'Description',
            'GROUPING'          => 'Grouping',
            'COST_MODEL'        => 'Cost Model',
            'SPECIALISATION_AT' => 'Specialise at level',
            'MULTIPLE'          => 'Can be bought multiple times?',
            'VISIBLE'           => 'Visible to Players',
         );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'NAME'        => array('NAME',true),
            'GROUPING'    => array('GROUPING',false)
        );
        return $sortable_columns;
    }
	
    
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete'
       );
        return $actions;
    }
    
    function process_bulk_action() {
        if( 'delete'===$this->current_action() && $_REQUEST['tab'] == $this->type) {
			if ('string' == gettype($_REQUEST['ability'])) {
				$this->delete_skill($_REQUEST['ability']);
			} else {
				foreach ($_REQUEST['ability'] as $ability) {
					$this->delete_skill($ability);
				}
			}
        }
        		
     }

        
    function prepare_items() {
        global $wpdb; 
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
		
		$type = "skill";
        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = $type;
        
        $this->process_bulk_action();
		
		/* Get the data from the database */
		$sql = "select skills.ID, skills.NAME, skills.DESCRIPTION, skills.GROUPING, skills.MULTIPLE,
					models.NAME as COST_MODEL, skills.SPECIALISATION_AT, skills.VISIBLE
				from 
					" . GVLARP_TABLE_PREFIX . "SKILL skills,
					" . GVLARP_TABLE_PREFIX . "COST_MODEL models
				where models.ID = skills.COST_MODEL_ID";
				
		/* order the data according to sort columns */
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']))
			$sql .= " ORDER BY skills.{$_REQUEST['orderby']} {$_REQUEST['order']}";
		
		$sql .= ";";
		
		/* echo "<p>SQL: $sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql,''));
        
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