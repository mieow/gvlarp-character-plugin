<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


function render_owner_data(){
	global $wpdb;

    $ownerTable = new owner_table();
	$doaction = $_REQUEST['action'];
	if (!empty($_REQUEST['owner_name'])){
		if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && $_REQUEST['table'] == 'ownertable')
			$doaction = "edit";
		if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'show' && $_REQUEST['table'] == 'ownertable')
			$doaction = "add";
		if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'hide' && $_REQUEST['table'] == 'ownertable')
			$doaction = "add";
	}
	
	if (isset($_REQUEST['do_add_owner'])) {
		switch ($doaction) {
			case "edit":
				$ownerTable->edit();
				break;
			case "add":
				$ownerTable->add();
				break;
		
		}
	}
	echo "<p>Action: $doaction</p>";

	render_owner_add_form($doaction);
	
	$ownerTable->prepare_items();

	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );

   ?>	

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="owner-filter" method="get" action='<?php print htmlentities($current_url); ?>&amp;#owner-filter'>
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="table" value="ownertable" />
 		<?php $ownerTable->display() ?>
	</form>

    <?php
}
function render_domain_data(){
	global $wpdb;

    $domainTable = new domain_table();
	$defaultCoord = "lat,long";

	// Validation
	
	$doaction = $_REQUEST['action'];
	if (!empty($_REQUEST['domain_name'])){
		if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && $_REQUEST['table'] == 'domaintable')
			$doaction = "edit";
		if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'show' && $_REQUEST['table'] == 'domaintable')
			$doaction = "add";
		if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'hide' && $_REQUEST['table'] == 'domaintable')
			$doaction = "add";
				
			/* Input Validation */
			if (empty($_REQUEST['domain_coordinates']) || $_REQUEST['domain_coordinates'] == ""  || $_REQUEST['domain_coordinates'] == $defaultCoord) {
				$doaction = "fix";
				echo "<p style='color:red'>ERROR: Enter coordinates</p>";
			}
	}
	
	if (isset($_REQUEST['do_add_domain'])) {
		switch ($doaction) {
			case "edit":
				$domainTable->edit();
				break;
			case "add":
				$domainTable->add();
				break;
		
		}
	}
	echo "<p>Action: $doaction</p>";
	render_feedingdomain_add_form($doaction);
	
	$domainTable->prepare_items();

	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );

   ?>	

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="domain-filter" method="get" action='<?php print htmlentities($current_url); ?>'>
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="table" value="domaintable" />
 		<?php $domainTable->display() ?>
	</form>

    <?php
}

function render_owner_add_form($doaction) {
	global $wpdb;

	switch ($doaction) {
	case "fix":
		$id          = $_REQUEST['owner'];
		$name        = $_REQUEST['owner_name'];
		$fillcolour  = $_REQUEST['owner_fill'];
		$visible     = $_REQUEST['owner_visible'];
		
		break;
	case "save":
	case "edit":
		$id          = $_REQUEST['owner'];
		
		$sql = "SELECT * FROM " . FEEDINGMAP_TABLE_PREFIX . "OWNER WHERE ID = %d";
		$data =$wpdb->get_row($wpdb->prepare($sql, $id));
		
		$name        = $data->NAME;
		$visible     = $data->VISIBLE;
		$fillcolour  = $data->FILL_COLOUR;
		
		break;
	default:
		$id = "";
		$name = "";
		$visible = 'Y';
		$fillcolour = "#FFFFFF";
			
	}
	if (isset($_REQUEST['action']))
		$nextaction = $_REQUEST['action'];	
	else
		$nextaction = "add";
	
	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );
	?>
	<form id="new-owner" method="post" action='<?php print htmlentities($current_url); ?>&amp;#owner-filter'>
		<input type="hidden" name="owner" value="<?php print $id; ?>"/>
		<input type="hidden" name="table" value="ownertable" />
		<input type="hidden" name="action" value="<?php print $nextaction; ?>" />
		<table style='width:500px'>
		<tr>
			<td>Name:  </td>
			<td><input type="text" name="owner_name" value="<?php print $name; ?>" /></td>
			<td>Fill Colour:  </td>
			<td><input type="color" name="owner_fill" value="<?php print $fillcolour; ?>" /></td>
			<td>Visible:  </td>
			<td>
				<select name="owner_visible">
					<option value="N" <?php selected($visible, "N"); ?>>No</option>
					<option value="Y" <?php selected($visible, "Y"); ?>>Yes</option>
				</select>
			</td>
		</tr>
		</table>
		
		
		
		</table>
		<input type="submit" name="do_add_owner" class="button-primary" value="Save Owner" />
	</form>
	
	<?php

}
function render_feedingdomain_add_form($doaction) {
	global $wpdb;

	$defaultCoord = "lat,long";
	

	switch ($doaction) {
		case "fix":
			$id          = $_REQUEST['domain'];
			$name        = $_REQUEST['domain_name'];
			$visible     = $_REQUEST['domain_visible'];
			$description = $_REQUEST['domain_desc'];
			$coordinates = $_REQUEST['domain_coordinates'];
			$ownerid     = $_REQUEST['domain_owner'];
			
			break;
		case "save":
		case "edit":
			$id          = $_REQUEST['domain'];
			
			$sql = "SELECT * FROM " . FEEDINGMAP_TABLE_PREFIX . "DOMAIN WHERE ID = %d";
			$data =$wpdb->get_row($wpdb->prepare($sql, $id));
			
			$name        = $data->NAME;
			$visible     = $data->VISIBLE;
			$description = $data->DESCRIPTION;
			$coordinates = $data->COORDINATES;
			$ownerid     = $data->OWNER_ID;
			
			break;
		default:
			$id = "";
			$name = "";
			$visible = 'Y';
			$description = "";
			$coordinates = $defaultCoord;
			$ownerid = 0;
			
	}
	if (isset($_REQUEST['action']))
		$nextaction = $_REQUEST['action'];	
	else
		$nextaction = "add";
	
	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );
	?>
	<form id="new-domain" method="post" action='<?php print htmlentities($current_url); ?>&amp;#domain-filter'>
		<input type="hidden" name="domain" value="<?php print $id; ?>"/>
		<input type="hidden" name="table" value="domaintable" />
		<input type="hidden" name="action" value="<?php print $nextaction; ?>" />
		<table style='width:500px'>
		<tr><td style='vertical-align:top;'>
			<table>
				<tr>
					<td>Name:  </td>
					<td colspan=3><input type="text" name="domain_name" value="<?php print $name; ?>" /></td>
				</tr>
				<tr>
					<td>Description:  </td>
					<td colspan=3><input type="text" name="domain_desc" value="<?php print $description; ?>" size=60 /></td>
				</tr>
				<tr>
					<td>Owner:  </td>
					<td>
						<select name="domain_owner">
						<?php
							foreach (get_owners() as $id => $info) {
								echo "<option value=\"$id\" ";
								selected($ownerid, $id);
								echo ">" . $info->NAME . "</option>\n";
							}
						?>
						</select>
					</td>
					<td>Visible to Players: </td>
					<td>
						<select name="domain_visible">
							<option value="N" <?php selected($visible, "N"); ?>>No</option>
							<option value="Y" <?php selected($visible, "Y"); ?>>Yes</option>
						</select>
					</td>
				</tr>
			</table>
		</td>
		<td style='vertical-align:top;'>Coordinates List:<i><br>lat,long<br>lat,long<br>...</i></td>
		<td>
			<textarea name="domain_coordinates" rows=10 cols=20><?php print $coordinates; ?></textarea>
		</td>
		</tr>
		</table>
		
		
		
		</table>
		<input type="submit" name="do_add_domain" class="button-primary" value="Save Domain" />
	</form>
	
	<?php
}

function get_owners() {
	global $wpdb;

	$sql = "SELECT ID, NAME FROM " . FEEDINGMAP_TABLE_PREFIX . "OWNER;";
	$list = $wpdb->get_results($sql,OBJECT_K);
	
	return $list;
}

class owner_table extends WP_List_Table {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'owner',     
            'plural'    => 'owners',    
            'ajax'      => false        
        ) );
    }
	
	function add($selectedID) {
		global $wpdb;
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME' => $_REQUEST['owner_name'],
						'FILL_COLOUR' => $_REQUEST['owner_fill'],
						'VISIBLE' => $_REQUEST['owner_visible']
					);
	
		$wpdb->insert(FEEDINGMAP_TABLE_PREFIX . "OWNER",
					$dataarray,
					array (
						'%s',
						'%s',
						'%s'
					)
				);
		
		if ($wpdb->insert_id == 0) {
			echo "<p style='color:red'><b>Error:</b> {$_REQUEST['owner_name']} could not be inserted</p>";
		} else {
			echo "<p style='color:green'>Added owner '{$_REQUEST['owner_name']}' (ID: {$wpdb->insert_id})</p>";
		}
	}
 	function edit() {
		global $wpdb;
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME' => $_REQUEST['owner_name'],
						'FILL_COLOUR' => $_REQUEST['owner_fill'],
						'VISIBLE' => $_REQUEST['owner_visible']
					);
	
		$result = $wpdb->update(FEEDINGMAP_TABLE_PREFIX . "OWNER",
					$dataarray,
					array (
						'ID' => $_REQUEST['owner']
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated {$_REQUEST['owner_name']}</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No updates made to {$_REQUEST['owner_name']}</p>";
		else {
			echo "<p style='color:red'>Could not update {$_REQUEST['owner_name']} ({$_REQUEST['owner']})</p>";
		}
	}
	function delete($selectedID) {
		global $wpdb;
		
		$sql = "select domains.NAME 
			from 
				" . FEEDINGMAP_TABLE_PREFIX . "OWNER owners , 
				" . FEEDINGMAP_TABLE_PREFIX . "DOMAIN domains
			where owners.ID = %d and domains.OWNER_ID = owners.ID;";
		$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as owner is assigned to the following domains:";
			echo "<ul>";
			foreach ($isused as $domain)
				echo "<li style='color:red'>" . stripslashes($domain->NAME) . "</li>";
			echo "</ul></p>";
		} else {
			$sql = "delete from " . FEEDINGMAP_TABLE_PREFIX . "OWNER where ID = %d;";
			
			$result = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		
			/* print_r($result); */
			echo "<p style='color:green'>Deleted item $selectedID</p>";
		}
	}
 	function showhide($selectedID, $showhide) {
		global $wpdb;
		
		//echo "id: $selectedID, setting: $showhide";
		if (empty($selectedID)) return;
		
		$wpdb->show_errors();
		
		$visiblity = $showhide == 'hide' ? 'N' : 'Y';
		
		$result = $wpdb->update( FEEDINGMAP_TABLE_PREFIX . "OWNER", 
			array (
				'VISIBLE' => $visiblity
			), 
			array (
				'ID' => $selectedID
			)
		);
		
		if ($result) 
			echo "<p style='color:green'>" . ucfirst($showhide) . " item $selectedID successful</p>";
		else if ($result === 0)
			echo "<p style='color:orange'>Item $selectedID has not been changed</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Item $selectedID could not be updated</p>";
		}
	}
  
    function column_default($item, $column_name){
        switch($column_name){
             default:
                return print_r($item,true); 
        }
    }
	
    function column_visible($item){
		return ($item->VISIBLE == "Y") ? "Yes" : "No";
    }
    function column_fill_colour($item){
		return "<span style='background-color:" . $item->FILL_COLOUR . ";'>" . $item->FILL_COLOUR . "</span>";
    }
 
    function column_name($item){
		$act = ($item->VISIBLE === 'Y') ? 'hide' : 'show';
        
        $actions = array(
            $act     => sprintf('<a href="?page=%s&amp;action=%s&amp;owner=%s&amp;table=%s&amp;#owner-filter">%s</a>',$_REQUEST['page'],$act,$item->ID, 'ownertable', ucfirst($act)),
            'edit'   => sprintf('<a href="?page=%s&amp;action=%s&amp;owner=%s&amp;table=%s&amp;#owner-filter">Edit</a>',$_REQUEST['page'],'edit',$item->ID, 'ownertable'),
            'delete' => sprintf('<a href="?page=%s&amp;action=%s&amp;owner=%s&amp;table=%s&amp;#owner-filter">Delete</a>',$_REQUEST['page'],'delete',$item->ID, 'ownertable'),
        );
        
        
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item->NAME,
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
            'cb'           => '<input type="checkbox" />', 
            'NAME'         => 'Name',
            'FILL_COLOUR'  => 'Fill Colour',
            'VISIBLE'      => 'Visible',
        );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'NAME'        => array('NAME',true)
        );
        return $sortable_columns;
    }
	
	
	
    
    function get_bulk_actions() {
        $actions = array(
            'show'   => 'Show',
            'hide'   => 'Hide',
            'delete' => 'Delete',
       );
        return $actions;
    }
    
    function process_bulk_action() {
        		
 		if( 'delete'===$this->current_action() && $_REQUEST['table'] == 'ownertable' && isset($_REQUEST['owner'])) {

			if ('string' == gettype($_REQUEST['owner'])) {
				$this->delete($_REQUEST['owner']);
			} else {
				foreach ($_REQUEST['owner'] as $owner) {
					$this->delete($owner);
				}
			}
        }
       if( 'hide'===$this->current_action() && $_REQUEST['table'] == 'ownertable' && isset($_REQUEST['owner']) ) {
			if ('string' == gettype($_REQUEST['owner'])) {
				$this->showhide($_REQUEST['owner'], "hide");
			} else {
				foreach ($_REQUEST['owner'] as $owner) {
					$this->showhide($owner, "hide");
				}
			}
        }
        if( 'show'===$this->current_action() && $_REQUEST['table'] == 'ownertable' && isset($_REQUEST['owner']) ) {
			if ('string' == gettype($_REQUEST['owner'])) {
				$this->showhide($_REQUEST['owner'], "show");
			} else {
				foreach ($_REQUEST['owner'] as $owner) {
					$this->showhide($owner, "show");
				}
			}
        }

     }
        
    function prepare_items() {
		global $wpdb;
        
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
		        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        		
        $this->process_bulk_action();
		
		$sql  = "SELECT * FROM " . FEEDINGMAP_TABLE_PREFIX . "OWNER ORDER BY NAME";
		$data = $wpdb->get_results($sql);

		$this->items = $data;
        
		

        $current_page = $this->get_pagenum();
        $total_items = count($data);

                
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $total_items,                  
            'total_pages' => 1
        ) );
    }

}

class domain_table extends WP_List_Table {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'domain',     
            'plural'    => 'domains',    
            'ajax'      => false        
        ) );
    }
	
	function add() {
		global $wpdb;
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME' => $_REQUEST['domain_name'],
						'OWNER_ID' => $_REQUEST['domain_owner'],
						'DESCRIPTION' => $_REQUEST['domain_desc'],
						'COORDINATES' => $_REQUEST['domain_coordinates'],
						'VISIBLE' => $_REQUEST['domain_visible']
					);
	
		$wpdb->insert(FEEDINGMAP_TABLE_PREFIX . "DOMAIN",
					$dataarray,
					array (
						'%s',
						'%d',
						'%s',
						'%s',
						'%s'
					)
				);
		
		if ($wpdb->insert_id == 0) {
			echo "<p style='color:red'><b>Error:</b> {$_REQUEST['domain_name']} could not be inserted</p>";
		} else {
			echo "<p style='color:green'>Added domain '{$_REQUEST['domain_name']}' (ID: {$wpdb->insert_id})</p>";
		}
	}
 	function edit() {
		global $wpdb;
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME' => $_REQUEST['domain_name'],
						'OWNER_ID' => $_REQUEST['domain_owner'],
						'DESCRIPTION' => $_REQUEST['domain_desc'],
						'COORDINATES' => $_REQUEST['domain_coordinates'],
						'VISIBLE' => $_REQUEST['domain_visible']
					);
	
		$result = $wpdb->update(FEEDINGMAP_TABLE_PREFIX . "DOMAIN",
					$dataarray,
					array (
						'ID' => $_REQUEST['domain']
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated {$_REQUEST['domain_name']}</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No updates made to {$_REQUEST['domain_name']}</p>";
		else {
			echo "<p style='color:red'>Could not update {$_REQUEST['domain_name']} ({$_REQUEST['domain']})</p>";
		}
	}
	
	function delete($selectedID) {
		global $wpdb;
		
		$sql = "delete from " . FEEDINGMAP_TABLE_PREFIX . "DOMAIN where ID = %d;";
		$wpdb->get_results($wpdb->prepare($sql, $selectedID));
		echo "<p style='color:green'>Deleted domain {$selectedID}</p>";
	}
	
 	function showhide($selectedID, $showhide) {
		global $wpdb;
		
		//echo "id: $selectedID, setting: $showhide";
		if (empty($selectedID)) return;
		
		$wpdb->show_errors();
		
		$visiblity = $showhide == 'hide' ? 'N' : 'Y';
		
		$result = $wpdb->update( FEEDINGMAP_TABLE_PREFIX . "DOMAIN", 
			array (
				'VISIBLE' => $visiblity
			), 
			array (
				'ID' => $selectedID
			)
		);
		
		if ($result) 
			echo "<p style='color:green'>" . ucfirst($showhide) . " item $selectedID successful</p>";
		else if ($result === 0)
			echo "<p style='color:orange'>Item $selectedID has not been changed</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Item $selectedID could not be updated</p>";
		}
	}

    function column_default($item, $column_name){
        switch($column_name){
            case 'DESCRIPTION':
                return $item->$column_name;
            case 'OWNER':
                return $item->$column_name;
             default:
                return print_r($item,true); 
        }
    }
	
    function column_visible($item){
		return ($item->VISIBLE == "Y") ? "Yes" : "No";
    }
    function column_fill_colour($item){
		return "<span style='background-color:" . $item->FILL_COLOUR . ";'>" . $item->FILL_COLOUR . "</span>";
    }
 
    function column_name($item){
		$act = ($item->VISIBLE === 'Y') ? 'hide' : 'show';
        
        $actions = array(
            $act     => sprintf('<a href="?page=%s&amp;action=%s&amp;domain=%s&amp;table=%s&amp;#domain-filter">%s</a>',$_REQUEST['page'],$act,$item->ID, 'domaintable', ucfirst($act)),
            'edit'   => sprintf('<a href="?page=%s&amp;action=%s&amp;domain=%s&amp;table=%s&amp;#domain-filter">Edit</a>',$_REQUEST['page'],'edit',$item->ID, 'domaintable'),
            'delete' => sprintf('<a href="?page=%s&amp;action=%s&amp;domain=%s&amp;table=%s&amp;#domain-filter">Delete</a>',$_REQUEST['page'],'delete',$item->ID, 'domaintable'),
        );
        
        
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item->NAME,
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
            'cb'           => '<input type="checkbox" />', 
            'NAME'         => 'Name',
            'OWNER'        => 'Domain Owner',
            'DESCRIPTION'  => 'Description',
            'VISIBLE'      => 'Visible',
        );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'NAME'        => array('NAME',true),
            'OWNER'       => array('OWNER',false),
            'VISIBLE'       => array('VISIBLE',false)
      );
        return $sortable_columns;
    }
	
	
	
    //assign owner, remove owner, show/hide, delete
    function get_bulk_actions() {
        $actions = array(
            'show'     => 'Show',
            'hide'     => 'Hide',
            'assign'   => 'Assign',
            'delete'   => 'Delete',
      );
        return $actions;
    }
    
    function process_bulk_action() {
        		
		if( 'delete'===$this->current_action() && $_REQUEST['table'] == 'domaintable' && isset($_REQUEST['domain'])) {

			if ('string' == gettype($_REQUEST['domain'])) {
				$this->delete($_REQUEST['domain']);
			} else {
				foreach ($_REQUEST['domain'] as $domain) {
					$this->delete($domain);
				}
			}
        }
        if( 'hide'===$this->current_action() && $_REQUEST['table'] == 'domaintable' && isset($_REQUEST['domain']) ) {
			if ('string' == gettype($_REQUEST['domain'])) {
				$this->showhide($_REQUEST['domain'], "hide");
			} else {
				foreach ($_REQUEST['domain'] as $domain) {
					$this->showhide($domain, "hide");
				}
			}
        }
        if( 'show'===$this->current_action() && $_REQUEST['table'] == 'domaintable' && isset($_REQUEST['domain']) ) {
			if ('string' == gettype($_REQUEST['domain'])) {
				$this->showhide($_REQUEST['domain'], "show");
			} else {
				foreach ($_REQUEST['domain'] as $domain) {
					$this->showhide($domain, "show");
				}
			}
        }

     }
        
    function prepare_items() {
		global $wpdb;
        
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
		        			
		$this->_column_headers = array($columns, $hidden, $sortable);
		
        $this->process_bulk_action();
        		
		$sql  = "SELECT 
					domains.ID, domains.NAME, owners.NAME as OWNER, domains.DESCRIPTION, domains.VISIBLE
				FROM 
					" . FEEDINGMAP_TABLE_PREFIX . "OWNER owners,
					" . FEEDINGMAP_TABLE_PREFIX . "DOMAIN domains
				WHERE
					domains.OWNER_ID = owners.ID";
		$data = $wpdb->get_results($sql);

		$this->items = $data;

        $current_page = $this->get_pagenum();
        $total_items = count($data);

                
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $total_items,                  
            'total_pages' => 1
        ) );
    }

}


?>