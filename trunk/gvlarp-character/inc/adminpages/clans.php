<?php
function character_clans() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	?>
	<div class="wrap">
		<h2>Clans and Disciplines</h2>
		<script type="text/javascript">
				document.getElementById('gv-clans').style.display = 'none';
				document.getElementById(tab).style.display = '';
				return false;
			}
		</script>
		<div class="gvadmin_nav">
			<ul>
				<li><a href="javascript:void(0);" onclick="tabSwitch('gv-clans');">Clans</a></li>
			</ul>
		</div>
		<div class="gvadmin_content">
			<div id="gv-clans" <?php tabdisplay("clan", "clan"); ?>>
				<h1>Clans</h1>
				<?php render_clan_page(); ?>
			</div>
		</div>

	</div>
	
	<?php
}

function render_clan_page(){

    $testListTable["clans"] = new gvadmin_clans_table();
	$doaction = clan_input_validation();
	
	if ($doaction == "add-clan") {
		$testListTable["clans"]->add_clan($_REQUEST['clan_name'], $_REQUEST['clan_description'], $_REQUEST['clan_iconlink'], 
			$_REQUEST['clan_clanpage'], $_REQUEST['clan_flaw'], $_REQUEST['clan_visible'], $_REQUEST['clan_costmodel'],
			$_REQUEST['clan_costmodel_nonclan']);
									
	}
	if ($doaction == "save-clan") {
		$testListTable["clans"]->edit_clan($_REQUEST['clan_id'], $_REQUEST['clan_name'], $_REQUEST['clan_description'], $_REQUEST['clan_iconlink'], 
			$_REQUEST['clan_clanpage'], $_REQUEST['clan_flaw'], $_REQUEST['clan_visible'], $_REQUEST['clan_costmodel'],
			$_REQUEST['clan_costmodel_nonclan']);
									
	}

	render_clan_add_form($doaction);
	$testListTable["clans"]->prepare_items();
	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );
	?>	

	<form id="clans-filter" method="get" action='<?php print $current_url; ?>'>
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="clan" />
 		<?php $testListTable["clans"]->display() ?>
	</form>

    <?php
}

function render_clan_add_form($addaction) {

	global $wpdb;
	
	$type = "clan";
	
	/* echo "<p>Creating clan form based on action $addaction</p>"; */

	if ('fix-' . $type == $addaction) {
		$id = $_REQUEST['clan'];
		$name = $_REQUEST[$type . '_name'];

		$description = $_REQUEST[$type . '_description'];
		$iconlink = $_REQUEST[$type . '_iconlink'];
		$clanpage = $_REQUEST[$type . '_clanpage'];
		$clanflaw = $_REQUEST[$type . '_flaw'];
		$visible = $_REQUEST[$type . '_visible'];
		$costmodel_id = $_REQUEST[$type . '_costmodel'];
		$nonclan_costmodel_id = $_REQUEST[$type . '_costmodel_nonclan'];
		
		$nextaction = $_REQUEST['action'];
		
	} else if ('edit-' . $type == $addaction) {
		/* Get values from database */
		$id   = $_REQUEST['clan'];
		
		$sql = "select *
				from " . GVLARP_TABLE_PREFIX . "CLAN clan
				where clan.ID = %d;";
		
		/* echo "<p>$sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql, $id));
		
		/* print_r($data); */
		
		$name = $data[0]->NAME;
		$description = $data[0]->DESCRIPTION;
		$iconlink = $data[0]->ICON_LINK;
		$clanpage = $data[0]->CLAN_PAGE_LINK;
		$clanflaw = $data[0]->CLAN_FLAW;
		$visible = $data[0]->VISIBLE;
		$costmodel_id = $data[0]->CLAN_COST_MODEL_ID;
		$nonclan_costmodel_id = $data[0]->NONCLAN_COST_MODEL_ID;
		
		$nextaction = "save";
		
	} else {
	
		/* defaults */
		$name = "";
		$description = "";
		$iconlink = "";
		$clanpage = "";
		$clanflaw = "";
		$visible  = "Y";
		$costmodel_id = "";
		$nonclan_costmodel_id = "";
		
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
			<td>Clan Name:  </td>
			<td><input type="text" name="<?php print $type; ?>_name" value="<?php print $name; ?>" size=30 /></td>
			<td>Visible to Players: </td>
			<td>
				<select name="<?php print $type; ?>_visible">
					<option value="N" <?php selected($visible, "N"); ?>>No</option>
					<option value="Y" <?php selected($visible, "Y"); ?>>Yes</option>
				</select>
			</td>
			<td>Cost Model for Clan Disciplines: </td>
			<td>
				<select name="<?php print $type; ?>_costmodel">
					<?php
						foreach (get_costmodels() as $costmodel) {
							print "<option value='{$costmodel->ID}' ";
							selected($costmodel->ID, $costmodel_id);
							echo ">{$costmodel->NAME}</option>";
						}
					?>
				</select></td>
			</td>
		</tr>
		<tr>
			<td>Link to clan icon:  </td>
			<td colspan=3><input type="text" name="<?php print $type; ?>_iconlink" value="<?php print $iconlink; ?>" size=60 /></td>
			<td>Cost Model for Non-Clan Disciplines: </td>
			<td>
				<select name="<?php print $type; ?>_costmodel_nonclan">
					<?php
						foreach (get_costmodels() as $costmodel) {
							print "<option value='{$costmodel->ID}' ";
							selected($costmodel->ID, $nonclan_costmodel_id);
							echo ">{$costmodel->NAME}</option>";
						}
					?>
				</select></td>
			</td>
		</tr>
		<tr>
			<td>Link to clan webpage:  </td>
			<td colspan=5><input type="text" name="<?php print $type; ?>_clanpage" value="<?php print $clanpage; ?>" size=60 /></td>
		</tr>
		<tr>
			<td>Clan Flaw:  </td>
			<td colspan=5><input type="text" name="<?php print $type; ?>_flaw" value="<?php print $clanflaw; ?>" size=60 /></td>
		</tr>
		<tr>
			<td>Description:  </td>
			<td colspan=5><input type="text" name="<?php print $type; ?>_description" value="<?php print $description; ?>" size=60 /></td>
		</tr>
		<tr>

		</tr>
		</table>
		<input type="submit" name="do_add_<?php print $type; ?>" class="button-primary" value="Save <?php print ucfirst($type); ?>" />
	</form>
	
	<?php
}

function clan_input_validation() {

	$type = "clan";

	if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && $_REQUEST['tab'] == $type)
		$doaction = "edit-$type"; 
		
	/* echo "<p>Requested action: " . $_REQUEST['action'] . ", " . $type . "_name: " . $_REQUEST[$type . '_name'];  */
	
	if (!empty($_REQUEST[$type . '_name'])){
			
		$doaction = $_REQUEST['action'] . "-" . $type;
		
		/* Input Validation */
		if (empty($_REQUEST[$type . '_description']) || $_REQUEST[$type . '_description'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Description is missing</p>";
		} 
		if (empty($_REQUEST[$type . '_iconlink']) || $_REQUEST[$type . '_iconlink'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Icon Link is missing</p>";
		} 
		if (empty($_REQUEST[$type . '_flaw']) || $_REQUEST[$type . '_flaw'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Clan Flaw is missing</p>";
		} 
				
	}
	
	/* echo " action: $doaction</p>"; */

	return $doaction;
}


/* 
-----------------------------------------------
CLANS
------------------------------------------------ */
class gvadmin_clans_table extends GVMultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'clan',     
            'plural'    => 'clans',    
            'ajax'      => false        
        ) );
    }
	
	function delete_clan($selectedID) {
		global $wpdb;
		
		/* Check if clan id in use in a character */
		$sql = "select characters.NAME 
			from " . GVLARP_TABLE_PREFIX . "CHARACTER characters
			where characters.PUBLIC_CLAN_ID = %d or characters.PRIVATE_CLAN_ID = %d;";
		$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID, $selectedID));
		
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this {$this->type} is being used in the following characters:";
			echo "<ul>";
			foreach ($isused as $character)
				echo "<li style='color:red'>{$character->NAME}</li>";
			echo "</ul></p>";
		} else {
		
			/* Check if clan id in use in a clan discipline */
			$sql = "select disciplines.NAME 
				from " . GVLARP_TABLE_PREFIX . "CLAN_DISCIPLINE clandisc, 
					" . GVLARP_TABLE_PREFIX . "DISCIPLINE disciplines
				where disciplines.ID = clandisc.DISCIPLINE_ID
					and clandisc.CLAN_ID = %d;";
			$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID));

			if ($isused) {
				echo "<p style='color:red'>Cannot delete as this {$this->type} is being used in the following clan disciplines:";
				echo "<ul>";
				foreach ($isused as $disc)
					echo "<li style='color:red'>{$disc->NAME}</li>";
				echo "</ul></p>";
			
			} else {
				$sql = "delete from " . GVLARP_TABLE_PREFIX . "CLAN where ID = %d;";
				
				$result = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
			
				/* print_r($result); */
				echo "<p style='color:green'>Deleted item $selectedID</p>";
			}
		}
	}
	
 	function add_clan($name, $description, $iconlink, $clanpage,
						$clanflaw, $visible, $costmodel, $nonclanmodel) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME' => $name,
						'DESCRIPTION' => $description,
						'ICON_LINK' => $iconlink,
						'CLAN_PAGE_LINK' => $clanpage,
						'CLAN_FLAW' => $clanflaw,
						'VISIBLE' => $visible,
						'CLAN_COST_MODEL_ID' => $costmodel,
						'NONCLAN_COST_MODEL_ID' => $nonclanmodel
					);
		
		/* print_r($dataarray); */
		
		$wpdb->insert(GVLARP_TABLE_PREFIX . "CLAN",
					$dataarray,
					array (
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%d',
						'%d'
					)
				);
		
		if ($wpdb->insert_id == 0) {
			echo "<p style='color:red'><b>Error:</b> $name could not be inserted (";
			$wpdb->print_error();
			echo ")</p>";
		} else {
			echo "<p style='color:green'>Added clan '$name' (ID: {$wpdb->insert_id})</p>";
		}
	}
 	function edit_clan($clanid, $name, $description, $iconlink, $clanpage,
						$clanflaw, $visible, $costmodel, $nonclanmodel) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME' => $name,
						'DESCRIPTION' => $description,
						'ICON_LINK' => $iconlink,
						'CLAN_PAGE_LINK' => $clanpage,
						'CLAN_FLAW' => $clanflaw,
						'VISIBLE' => $visible,
						'CLAN_COST_MODEL_ID' => $costmodel,
						'NONCLAN_COST_MODEL_ID' => $nonclanmodel
					);
		
		/* print_r($dataarray); */
		
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "CLAN",
					$dataarray,
					array (
						'ID' => $clanid
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated $name</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No updates made to $name</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update $name ($clanid)</p>";
		}
	}
   
    function column_default($item, $column_name){
        switch($column_name){
            case 'DESCRIPTION':
                return $item->$column_name;
            case 'CLAN_PAGE_LINK':
                return $item->$column_name;
             case 'ICON_LINK':
                return $item->$column_name;
           case 'CLAN_FLAW':
                return $item->$column_name;
           case 'COST_MODEL':
                return $item->$column_name;
           case 'NONCLAN_MODEL':
                return $item->$column_name;
            default:
                return print_r($item,true); 
        }
    }
 
    function column_name($item){
	
		$act = ($item->VISIBLE === 'Y') ? 'hide' : 'show';
        
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&amp;action=%s&clan=%s&amp;tab=%s">Edit</a>',$_REQUEST['page'],'edit',$item->ID, $this->type),
            'delete'    => sprintf('<a href="?page=%s&amp;action=%s&clan=%s&amp;tab=%s">Delete</a>',$_REQUEST['page'],'delete',$item->ID, $this->type),
        );
        
        
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item->NAME,
            $item->ID,
            $this->row_actions($actions)
        );
    }
      

    function get_columns(){
        $columns = array(
            'cb'           => '<input type="checkbox" />', 
            'NAME'         => 'Name',
            'DESCRIPTION'  => 'Description',
            'ICON_LINK'    => 'Link to clan icon',
            'CLAN_PAGE_LINK' => 'Link to clan page',
            'CLAN_FLAW'    => 'Clan Flaw',
            'VISIBLE'      => 'Visible to Players',
            'COST_MODEL'   => 'Clan Cost Model',
            'NONCLAN_MODEL'  => 'Non-Clan Cost Model',
        );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'NAME'        => array('NAME',true),
            'VISIBLE'    => array('GROUPING',false),
        );
        return $sortable_columns;
    }
	
    
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete',
       );
        return $actions;
    }
    
    function process_bulk_action() {
        
		/* echo "<p>Bulk action " . $this->current_action() . ", currently on tab {$_REQUEST['tab']} and will do action if {$this->type}.</p>"; */
		
        if( 'delete'===$this->current_action() && $_REQUEST['tab'] == $this->type) {
			if ('string' == gettype($_REQUEST['clan'])) {
				$this->delete_clan($_REQUEST['clan']);
			} else {
				foreach ($_REQUEST['clan'] as $clan) {
					$this->delete_clan($clan);
				}
			}
        }
		
    } 
    
    function prepare_items() {
        global $wpdb; 

        /* $per_page = 20; */
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = "clan";
        
        $this->process_bulk_action();
		
		/* Get the data from the database */
		$sql = "select clan.ID, clan.NAME, clan.DESCRIPTION, clan.ICON_LINK, clan.CLAN_PAGE_LINK, 
					clan.CLAN_FLAW, clan.VISIBLE,
					clancosts.NAME as COST_MODEL, nonclancosts.NAME as NONCLAN_MODEL
				from " . GVLARP_TABLE_PREFIX. "CLAN clan,
					" . GVLARP_TABLE_PREFIX. "COST_MODEL clancosts,
					" . GVLARP_TABLE_PREFIX. "COST_MODEL nonclancosts
				where
					clancosts.ID = clan.CLAN_COST_MODEL_ID
					AND nonclancosts.ID = clan.NONCLAN_COST_MODEL_ID";
		
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']) && $type == $_REQUEST['tab'])
			$sql .= " ORDER BY clan.{$_REQUEST['orderby']} {$_REQUEST['order']}";
		
		$sql .= ";";
		/* echo "<p>SQL: " . $sql . "</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql,''));
        
        $current_page = $this->get_pagenum();
        $total_items = count($data);
                
        $this->items = $data;
        
        /* $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $per_page,                  
            'total_pages' => ceil($total_items/$per_page)
        ) ); */
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $total_items,                  
            'total_pages' => 1
        ) );
    }

}
?>