<?php
function character_backgrounds() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	?>
	<div class="wrap">
		<h2>Backgrounds</h2>
		<script type="text/javascript">
			function tabSwitch(tab) {
				document.getElementById('gv-approve').style.display = 'none';
				document.getElementById('gv-bgdata').style.display = 'none';
				document.getElementById('gv-sectors').style.display = 'none';
				document.getElementById('gv-questions').style.display = 'none';
				document.getElementById(tab).style.display = '';
				return false;
			}
		</script>
		<div class="gvadmin_nav">
			<ul>
				<li><a href="javascript:void(0);" onclick="tabSwitch('gv-approve');">Approvals</a></li>
				<li><a href="javascript:void(0);" onclick="tabSwitch('gv-bgdata');">Background Data</a></li>
				<li><a href="javascript:void(0);" onclick="tabSwitch('gv-questions');">Background Questions</a></li>
				<li><a href="javascript:void(0);" onclick="tabSwitch('gv-sectors');">Sector Data</a></li>
			</ul>
		</div>
		<div class="gvadmin_content">
			<div id="gv-approve" <?php tabdisplay("gvapprove", "gvapprove"); ?>>
				<h1>Extended Background Approvals</h1>
				<?php render_approvals_data(); ?>
			</div>
			<div id="gv-bgdata" <?php tabdisplay("bgdata", "gvapprove"); ?>>
				<h1>Background Data</h1>
				<?php render_background_data(); ?>
			</div>
			<div id="gv-sectors" <?php tabdisplay("sector", "gvapprove"); ?>>
				<h1>Background Sectors</h1>
				<?php render_sector_data(); ?>
			</div>
			<div id="gv-questions" <?php tabdisplay("question", "gvapprove"); ?>>
				<h1>Extended Background Questions</h1>
				<?php render_question_data(); ?>
			</div>
		</div>

	</div>
	
	<?php
}


function render_approvals_data(){
	global $wpdb;

    $testListTable['gvapprove'] = new gvadmin_extbgapproval_table();

	$showform = 0;
	if (!empty($_REQUEST['do_deny'])) {
		/* save denial */
		
		$data = array(
			'DENIED_DETAIL'  => $_REQUEST['gvapprove_denied']
		);
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . $_REQUEST['table'],
			$data,
			array (
				'ID' => $_REQUEST['table_id']
			)
		);
		
		if ($result)
			echo "<p style='color:green'>Denied message saved</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not deny background</p>";
		}
		
		
		$id   = -1;
		$data = array();
	}
	else if (!empty($_REQUEST['action']) && 'string' == gettype($_REQUEST['extbackground']) && $_REQUEST['action'] == 'denyit') {
		
		/* load from database */
		$data = $testListTable['gvapprove']->read_data();
		$id   = $_REQUEST['extbackground'];

		$showform = 1;
	}

	render_approve_form($showform, $id, $data);
	
	$testListTable['gvapprove']->prepare_items();

	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );

   ?>	

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="approve-filter" method="get" action='<?php print $current_url; ?>'>
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="gvapprove" />
 		<?php $testListTable['gvapprove']->display() ?>
	</form>

    <?php
}
function render_question_data(){

    $testListTable['question'] = new gvadmin_questions_table();
	$doaction = question_input_validation();
	
 	if ($doaction == "add-question") {
		$testListTable['question']->add_question($_REQUEST['question_title'], $_REQUEST['question_order'], 
												$_REQUEST['question_group'], $_REQUEST['question_question'], $_REQUEST['question_visible']);
	}
	if ($doaction == "save-question") { 
		$testListTable['question']->edit_question($_REQUEST['question_id'], $_REQUEST['question_title'], $_REQUEST['question_order'], 
												$_REQUEST['question_group'], $_REQUEST['question_question'], $_REQUEST['question_visible']);
	}

	render_question_add_form($doaction); 
	
	$testListTable['question']->prepare_items();
 	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );
  ?>	

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="question-filter" method="get" action='<?php print $current_url; ?>'>
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="question" />
		<?php $testListTable['question']->display() ?>
	</form>

    <?php
}
function render_sector_data(){

    $testListTable['sector'] = new gvadmin_sectors_table();
	$doaction = sector_input_validation();
	
 	if ($doaction == "add-sector") {
		$testListTable['sector']->add_sector($_REQUEST['sector_name'], $_REQUEST['sector_desc'], $_REQUEST['sector_visible']);
	}
	if ($doaction == "save-sector") { 
		$testListTable['sector']->edit_sector($_REQUEST['sector_id'], $_REQUEST['sector_name'], $_REQUEST['sector_desc'], $_REQUEST['sector_visible']);
	}

	render_sector_add_form($doaction); 
	
	$testListTable['sector']->prepare_items();
 	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );
  ?>	

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="sector-filter" method="get" action='<?php print $current_url; ?>'>
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="sector" />
		<?php $testListTable['sector']->display() ?>
	</form>

    <?php
}

function render_background_data(){

    $testListTable['bgdata'] = new gvadmin_backgrounds_table();
	$doaction = bgdata_input_validation();
	
 	if ($doaction == "add-bgdata") {
		$testListTable['bgdata']->add_background($_REQUEST['bgdata_name'], $_REQUEST['bgdata_desc'], $_REQUEST['bgdata_group'], 
									$_REQUEST['bgdata_costmodel'], $_REQUEST['bgdata_visible'],
									$_REQUEST['bgdata_hassector'], $_REQUEST['bgdata_question']);
	}
	if ($doaction == "save-bgdata") { 
		$testListTable['bgdata']->edit_background($_REQUEST['bgdata_id'], $_REQUEST['bgdata_name'], $_REQUEST['bgdata_desc'], $_REQUEST['bgdata_group'], 
									$_REQUEST['bgdata_costmodel'], $_REQUEST['bgdata_visible'],
									$_REQUEST['bgdata_hassector'], $_REQUEST['bgdata_question']);
	} 

	render_bgdata_add_form($doaction);
	
	$testListTable['bgdata']->prepare_items();
 	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );
  ?>	

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="bgdata-filter" method="get" action='<?php print $current_url; ?>'>
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="bgdata" />
		<?php $testListTable['bgdata']->display() ?>
	</form>

    <?php
}

function render_bgdata_add_form($addaction) {

	global $wpdb;
	
	$type = "bgdata";
	
	/* echo "<p>Creating book form based on action $addaction</p>"; */

	if ('fix-' . $type == $addaction) {
		$id = $_REQUEST['background'];
		$name = $_REQUEST[$type . '_name'];

		$visible = $_REQUEST[$type . '_visible'];
		$desc = $_REQUEST[$type . '_desc'];
		$group = $_REQUEST[$type . '_group'];
		$costmodel_id = $_REQUEST[$type . '_costmodel'];
		$has_sector = $_REQUEST[$type . '_hassector'];
		$bgquestion = $_REQUEST[$type . '_question'];
		
		$nextaction = $_REQUEST['action'];
		
	} else if ('edit-' . $type == $addaction) {
		/* Get values from database */
		$id   = $_REQUEST['background'];
		
		$sql = "select *
				from " . GVLARP_TABLE_PREFIX . "BACKGROUND 
				where ID = %d;";
		
		/* echo "<p>$sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql, $id));
		
		$name = $data[0]->NAME;
		$group = $data[0]->GROUPING;
		$costmodel_id = $data[0]->COST_MODEL_ID;
		$desc = $data[0]->DESCRIPTION;
		$visible = $data[0]->VISIBLE;
		$has_sector = $data[0]->HAS_SECTOR;
		$bgquestion = $data[0]->BACKGROUND_QUESTION;
		
		$nextaction = "save";
		
	} else {
	
		/* defaults */
		$name = "";
		$group = "";
		$costmodel_id = 0;
		$desc = "";
		$visible = "Y";
		$has_sector = "N";
		$bgquestion = "";
		
		$nextaction = "add";
	} 
	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );

	?>
	<form id="new-<?php print $type; ?>" method="post" action='<?php print $current_url; ?>'>
		<input type="hidden" name="<?php print $type; ?>_id" value="<?php print $id; ?>"/>
		<input type="hidden" name="tab" value="<?php print $type; ?>" />
		<input type="hidden" name="action" value="<?php print $nextaction; ?>" />
		<table style='width:500px'>
		<tr>
			<td>Name:  </td>
			<td><input type="text" name="<?php print $type; ?>_name" value="<?php print $name; ?>" size=20 /></td>
		
			<td>Grouping:  </td>
			<td><input type="text" name="<?php print $type; ?>_group" value="<?php print $group; ?>" size=20 /></td>
		
			<td>Cost Model:  </td>
			<td>
				<select name="<?php print $type; ?>_costmodel">
					<?php
						print "<option value='0' ";
						selected($costmodel->ID, $costmodel_id);
						echo ">[Select]</option>";
						
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
				</select></td>
		
		</tr>
		<tr>
			<td>Has a Sector: </td><td>
				<select name="<?php print $type; ?>_hassector">
					<option value="N" <?php selected($has_sector, "N"); ?>>No</option>
					<option value="Y" <?php selected($has_sector, "Y"); ?>>Yes</option>
				</select></td>
				
			<td>Description:  </td>
			<td colspan=5><input type="text" name="<?php print $type; ?>_desc" value="<?php print $desc; ?>" size=90 /></td> <!-- check sizes -->

		</tr>
		<tr>
			<td colspan=8>Extended Background question (leave blank to exclude background from Extended Backgrounds):  </td>
		</tr>
		<tr>
			<td colspan=8>
				<textarea name="<?php print $type; ?>_question" rows="2" cols="100"><?php print $bgquestion; ?></textarea>
			</td>
		</tr>
		</table>
		<input type="submit" name="do_add_<?php print $type; ?>" class="button-primary" value="Save Background" />
	</form>
	
	<?php
}

function render_sector_add_form($addaction) {

	global $wpdb;
	
	$type = "sector";
	
	/* echo "<p>Creating sector form based on action $addaction</p>"; */

	if ('fix-' . $type == $addaction) {
		$id         = $_REQUEST['sector'];
		$name       = $_REQUEST[$type . '_name'];
		$visible    = $_REQUEST[$type . '_visible'];
		$desc       = $_REQUEST[$type . '_desc'];
		$nextaction = $_REQUEST['action'];
		
	} else if ('edit-' . $type == $addaction) {
		/* Get values from database */
		$id   = $_REQUEST['sector'];
		
		$sql = "select *
				from " . GVLARP_TABLE_PREFIX . "SECTOR 
				where ID = %d;";
		
		/* echo "<p>$sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql, $id));
		
		$name = $data[0]->NAME;
		$desc = $data[0]->DESCRIPTION;
		$visible = $data[0]->VISIBLE;
		
		$nextaction = "save";
		
	} else {
	
		/* defaults */
		$id   = "";
		$name = "";
		$desc = "";
		$visible = "Y";
		
		$nextaction = "add";
	} 
	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );

	?>
	<form id="new-<?php print $type; ?>" method="post" action='<?php print $current_url; ?>'>
		<input type="hidden" name="<?php print $type; ?>_id" value="<?php print $id; ?>"/>
		<input type="hidden" name="tab" value="<?php print $type; ?>" />
		<input type="hidden" name="action" value="<?php print $nextaction; ?>" />
		<table style='width:500px'>
		<tr>
			<td>Name:  </td>
			<td><input type="text" name="<?php print $type; ?>_name" value="<?php print $name; ?>" size=20 /></td>
		
			<td>Visible to Players: </td><td>
				<select name="<?php print $type; ?>_visible">
					<option value="N" <?php selected($visible, "N"); ?>>No</option>
					<option value="Y" <?php selected($visible, "Y"); ?>>Yes</option>
				</select></td>
		
		</tr>
		<tr>
		
			<td>Description:  </td>
			<td colspan=3><input type="text" name="<?php print $type; ?>_desc" value="<?php print $desc; ?>" size=100 /></td> <!-- check sizes -->

		</tr>
		</table>
		<input type="submit" name="do_add_<?php print $type; ?>" class="button-primary" value="Save Sector" />
	</form>
	
	<?php
}

function render_question_add_form($addaction) {

	global $wpdb;
	
	$type = "question";
	
	/* echo "<p>Creating question form based on action $addaction</p>"; */

	if ('fix-' . $type == $addaction) {
		$id      = $_REQUEST['question'];
		$title   = $_REQUEST[$type . '_title'];
		$order   = $_REQUEST[$type . '_order'];
		$group   = $_REQUEST[$type . '_group'];
		$question = $_REQUEST[$type . '_question'];
		$visible = $_REQUEST[$type . '_visible'];
		
		$nextaction = $_REQUEST['action'];
		
	} else if ('edit-' . $type == $addaction) {
		/* Get values from database */
		$id   = $_REQUEST['question'];
		
		$sql = "select *
				from " . GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND 
				where ID = %d;";
		
		/* echo "<p>$sql</p>"; */
		
		$data =$wpdb->get_results($wpdb->prepare($sql, $id));
		
		$title   = $data[0]->TITLE;
		$order   = $data[0]->ORDERING;
		$group   = $data[0]->GROUPING;
		$question = stripslashes($data[0]->BACKGROUND_QUESTION);
		$visible = $data[0]->VISIBLE;
		
		$nextaction = "save";
		
	} else {
	
		$sql = "select * from " . GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND;";
		$order = count($wpdb->get_results($wpdb->prepare($sql,''))) + 1;
	
		/* defaults */
		$title   = "";
		$group   = "";
		$question = "";
		$visible  = "Y";
		
		$nextaction = "add";
	} 
	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );

	?>
	<form id="new-<?php print $type; ?>" method="post" action='<?php print $current_url; ?>'>
		<input type="hidden" name="<?php print $type; ?>_id" value="<?php print $id; ?>"/>
		<input type="hidden" name="tab" value="<?php print $type; ?>" />
		<input type="hidden" name="action" value="<?php print $nextaction; ?>" />
		<table style='width:500px'>
		<tr>
			<td>Title:  </td>
			<td colspan=3><input type="text" name="<?php print $type; ?>_title" value="<?php print $title; ?>" size=60 /></td>
		
			<td>Visible:</td>
			<td><select name="<?php print $type; ?>_visible">
				<option value="Y" <?php selected($visible, "Y"); ?>>Yes</option>
				<option value="N" <?php selected($visible, "N"); ?>>No</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>Question Order: </td>
			<td><input type="text" name="<?php print $type; ?>_order" value="<?php print $order; ?>" size=4 /></td>
			<td>Group: </td>
			<td colspan=3><input type="text" name="<?php print $type; ?>_group" value="<?php print $group; ?>" size=30 /></td>
		</tr>
		<tr>
			<td>Question:  </td>
			<td colspan=5>
				<textarea name="<?php print $type; ?>_question" " rows="2" cols="100" ><?php print $question; ?></textarea>
			</td> 

		</tr>
		</table>
		<input type="submit" name="do_add_<?php print $type; ?>" class="button-primary" value="Save Question" />
	</form>
	
	<?php
}
function render_approve_form($showform, $id, $data) {
	
	$type = "gvapprove";
	
	if ($showform) {
		
		/* load from database */
		$table   = $data[$id]['TABLE'];
		$pending = $data[$id]['DESCRIPTION'];
		$tableid = $data[$id]['TABLE.ID'];
		$denied = "";	
	}

	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );
	
	if ($showform) {
	?>
	<form id="new-<?php print $type; ?>" method="post" action='<?php print $current_url; ?>'>
		<input type="hidden" name="table_id" value="<?php print $tableid; ?>" />
		<input type="hidden" name="table"    value="<?php print $table; ?>"/>
		<input type="hidden" name="tab"      value="<?php print $type; ?>" />
		<input type="hidden" name="extbackground" value="<?php print $id; ?>" />
		<table style='width:500px'>
		<tr>
			<td>Description: </td><td><?php print $pending; ?></td>
		</tr>
		<tr>
			<td>Denied Reason:  </td>
			<td><textarea name="<?php print $type; ?>_denied"><?php print $denied; ?></textarea></td>
		</tr>
		</table>
		<input type="submit" name="do_deny" class="button-primary" value="Deny" />
	</form>
	
	<?php
	}
}

function bgdata_input_validation() {

	$type = "bgdata";

	if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && $_REQUEST['tab'] == $type)
		$doaction = "edit-$type";
		
	/* echo "<p>Requested action: " . $_REQUEST['action'] . ", " . $type . "_name: " . $_REQUEST[$type . '_name']; */
	
	
	if (!empty($_REQUEST[$type . '_name'])){
			
		$doaction = $_REQUEST['action'] . "-" . $type;
		/* Input Validation */
		if (empty($_REQUEST[$type . '_desc']) || $_REQUEST[$type . '_desc'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Background Description is missing</p>";
		} 
		if (empty($_REQUEST[$type . '_group']) || $_REQUEST[$type . '_group'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Background Group is missing</p>";
		} 
		if (empty($_REQUEST[$type . '_costmodel']) || $_REQUEST[$type . '_costmodel'] == 0) {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Select Background Cost Model</p>";
		} 
				
	}

	return $doaction;
}


function sector_input_validation() {

	$type = "sector";
	
	/* echo "<p>Requested action: " . $_REQUEST['action'] . ", " . $type . "_name: " . $_REQUEST[$type . '_name']; */

	if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && $_REQUEST['tab'] == $type)
		$doaction = "edit-$type";
		
	
	if (!empty($_REQUEST['action']) && !empty($_REQUEST[$type . '_name']) ){

		$doaction = $_REQUEST['action'] . "-" . $type;
		
			
		/* Input Validation */
		if (empty($_REQUEST[$type . '_desc']) || $_REQUEST[$type . '_desc'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Sector Description is missing</p>";
		} 
				
	}
	
	/* echo "<p>Doing action $doaction</p>"; */

	return $doaction;
}

function question_input_validation() {

	$type = "question";

	if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && $_REQUEST['tab'] == $type)
		$doaction = "edit-$type";
	
	if (!empty($_REQUEST[$type . '_title'])){
			
		$doaction = $_REQUEST['action'] . "-" . $type;
		
		/* Input Validation */
		if (empty($_REQUEST[$type . '_order']) || $_REQUEST[$type . '_order'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Extended Background question order is missing</p>";
		} else if ($_REQUEST[$type . '_order'] <= 0) {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Extended Background order should be a number greater than 0</p>";
		} 
		if (empty($_REQUEST[$type . '_group']) || $_REQUEST[$type . '_group'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Extended Background group is missing</p>";
		} 
		if (empty($_REQUEST[$type . '_question']) || $_REQUEST[$type . '_question'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Extended Background question is missing</p>";
		} 
				
	}
	
	/* echo "<p>Doing action $doaction</p>"; */

	return $doaction;
}


class gvadmin_extbgapproval_table extends GVMultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'extbackground',     
            'plural'    => 'extbackgrounds',    
            'ajax'      => false        
        ) );
    }
	
	function approve($tableid) {
		global $wpdb;
		$table = $this->items[$tableid]['TABLE'];
		
		$data = array(
			'PENDING_DETAIL'  => '',
			'APPROVED_DETAIL' => $this->items[$tableid]['TABLE.DETAIL']
		);
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . $table,
			$data,
			array ('ID' => $this->items[$tableid]['TABLE.ID'])
		);
		
		if ($result) echo "<p style='color:green'>Approved extended background</p>";
		else echo "<p style='color:red'>Could not approve extended background</p>";
		
		
	}
	
 	function deny($tableid, $deny_message = 'Denied - see storytellers for more information') {
		global $wpdb;
		$table = $this->items[$tableid]['TABLE'];
		
		$data = array('DENIED_DETAIL'  => $deny_message);
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . $table,
			$data,
			array ('ID' => $this->items[$tableid]['TABLE.ID'])
		);
		
		if ($result) echo "<p style='color:green'>Denied extended background</p>";
		else echo "<p style='color:red'>Could not deny extended background</p>";
		
		
	}
  
    function column_default($item, $column_name){
        switch($column_name){
            case 'DESCRIPTION':
                return $item[$column_name];
            case 'TABLE':
                return $item[$column_name];
            case 'TABLE.ID':
                return $item[$column_name];
            case 'TABLE.DETAIL':
                return $item[$column_name];
            case 'TABLE.OLD':
                return $item[$column_name];
           default:
                return print_r($item,true); 
        }
    }
 
    function column_name($item){
        
        $actions = array(
            'approveit' => sprintf('<a href="?page=%s&amp;action=%s&extbackground=%s&amp;tab=%s">Approve</a>',$_REQUEST['page'],'approveit',$item['ID'], $this->type),
            'denyit'    => sprintf('<a href="?page=%s&amp;action=%s&extbackground=%s&amp;tab=%s">Deny</a>',$_REQUEST['page'],'denyit',$item['ID'], $this->type),
        );
        
        
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item[NAME],
            $item[ID],
            $this->row_actions($actions)
        );
    }
   
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],  
            $item[ID]
        );
    }

    function get_columns(){
        $columns = array(
            'cb'           => '<input type="checkbox" />', 
            'NAME'         => 'Name',
            'DESCRIPTION'  => 'Description',
            'TABLE'        => 'Table Name',
            'TABLE.ID'     => 'ID of item in table',
			'TABLE.DETAIL' => 'Data for table',
			'TABLE.OLD'    => 'Previously Approved'
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
            'approveit' => 'Approve',
            'denyit'    => 'Deny'
       );
        return $actions;
    }
    
    function process_bulk_action() {
        		
		if( 'approveit'===$this->current_action() && $_REQUEST['tab'] == $this->type) {

			if ('string' == gettype($_REQUEST['extbackground'])) {
				$this->approve($_REQUEST['extbackground']);
			} else {
				foreach ($_REQUEST['extbackground'] as $extbackground) {
					$this->approve($extbackground);
				}
			}
        }
        if( 'denyit'===$this->current_action() && $_REQUEST['tab'] == $this->type) {
			if ('string' == gettype($_REQUEST['extbackground'])) {
				/* $this->deny($_REQUEST['extbackground']); */
			} else {
				foreach ($_REQUEST['extbackground'] as $extbackground) {
					$this->deny($extbackground);
				}
			}
        }
     }

	function read_data() {
		global $wpdb;
		
		$data = array();
	
		/* Get the data from the database - backgrounds */
		$sql = "select characters.ID charID, charbgs.ID chargbID, characters.NAME charname, 
					backgrounds.NAME background, charbgs.LEVEL, 
					sectors.NAME sector, charbgs.PENDING_DETAIL, charbgs.DENIED_DETAIL,
					backgrounds.HAS_SECTOR, charbgs.COMMENT, charbgs.APPROVED_DETAIL
				from	" . GVLARP_TABLE_PREFIX . "BACKGROUND backgrounds,
						" . GVLARP_TABLE_PREFIX . "CHARACTER characters,
						" . GVLARP_TABLE_PREFIX . "CHARACTER_BACKGROUND charbgs
				left join
						" . GVLARP_TABLE_PREFIX . "SECTOR sectors
				on
					charbgs.SECTOR_ID = sectors.ID
				where	backgrounds.ID = charbgs.BACKGROUND_ID
					and characters.ID = charbgs.CHARACTER_ID
					and charbgs.PENDING_DETAIL != ''
					and charbgs.DENIED_DETAIL = ''
					and	(backgrounds.BACKGROUND_QUESTION != '' OR charbgs.SECTOR_ID > 0);";
				
		$tempdata =$wpdb->get_results($wpdb->prepare($sql,''));
		//echo "<p>SQL: $sql</p>";
		//print_r($tempdata);
		
		
		$row = 0;
		foreach ($tempdata as $tablerow) {
			$description = "<strong>{$tablerow->background} {$tablerow->LEVEL}";
			$description .= ($tablerow->sector) ? " ({$tablerow->sector})" : "";
			$description .= ($tablerow->COMMENT) ? " ({$tablerow->COMMENT})" : "";
			$description .= "</strong><br /><span>" . stripslashes($tablerow->PENDING_DETAIL) . "</span>";
			$description = str_replace("\n", "<br>", $description);
			
			$data[$row] = array (
				'ID'          => $row,
				'NAME'        => $tablerow->charname,
				'TABLE.ID'    => $tablerow->chargbID,
				'TABLE'       => "CHARACTER_BACKGROUND",
				'TABLE.DETAIL' => $tablerow->PENDING_DETAIL,
				'DESCRIPTION'  => $description,
				'COMMENT'      => $tablerow->COMMENT,
				'TABLE.OLD'    => str_replace("\n", "<br>", "<span>" . stripslashes($tablerow->APPROVED_DETAIL) . "</span>")
			);
			$row++;
		}

		/* Get the data from the database - merits and flaws */
		$sql = "select characters.ID charID, charmerit.ID charmeritID, characters.NAME charname, 
					merits.NAME merit, charmerit.COMMENT,
					charmerit.PENDING_DETAIL, charmerit.DENIED_DETAIL, charmerit.APPROVED_DETAIL
				from	" . GVLARP_TABLE_PREFIX . "MERIT merits,
						" . GVLARP_TABLE_PREFIX . "CHARACTER characters,
						" . GVLARP_TABLE_PREFIX . "CHARACTER_MERIT charmerit
				where	merits.ID = charmerit.MERIT_ID
					and characters.ID = charmerit.CHARACTER_ID
					and charmerit.PENDING_DETAIL != ''
					and charmerit.DENIED_DETAIL = ''
					and	merits.BACKGROUND_QUESTION != '';";
				
		
		$tempdata =$wpdb->get_results($wpdb->prepare($sql,''));
		//echo "<p>SQL: $sql</p>";
		//print_r($tempdata);
		foreach ($tempdata as $tablerow) {
			$description = "<strong>{$tablerow->merit}";
			$description .= ($tablerow->COMMENT) ? " ({$tablerow->COMMENT})" : "";
			$description .= "</strong><br />
				<span>" . stripslashes($tablerow->PENDING_DETAIL) . "</span>";
			
			$data[$row] = array (
				'ID'          => $row,
				'NAME'        => $tablerow->charname,
				'TABLE.ID'    => $tablerow->charmeritID,
				'TABLE'       => "CHARACTER_MERIT",
				'TABLE.DETAIL' => $tablerow->PENDING_DETAIL,
				'DESCRIPTION'  => $description,
				'COMMENT'      => $tablerow->COMMENT,
				'TABLE.OLD'    => str_replace("\n", "<br>", "<span>" . stripslashes($tablerow->APPROVED_DETAIL) . "</span>")
			);
			$row++;
		}
		
		/* Get the data from the database - questions */
		$sql = "select characters.ID charID, answers.ID answerID, characters.NAME charname, 
					questions.TITLE, questions.GROUPING,
					answers.PENDING_DETAIL, answers.DENIED_DETAIL, answers.APPROVED_DETAIL
				from	" . GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND questions,
						" . GVLARP_TABLE_PREFIX . "CHARACTER characters,
						" . GVLARP_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND answers
				where	questions.ID = answers.QUESTION_ID
					and characters.ID = answers.CHARACTER_ID
					and answers.PENDING_DETAIL != ''
					and answers.DENIED_DETAIL = '';";
					
		$tempdata =$wpdb->get_results($wpdb->prepare($sql,''));
		//echo "<p>SQL: $sql</p>";
		//print_r($tempdata);
		foreach ($tempdata as $tablerow) {
			$description = "<strong>{$tablerow->TITLE} ({$tablerow->GROUPING})</strong><br />
				<span>" . stripslashes($tablerow->PENDING_DETAIL) . "</span>";
			
			$data[$row] = array (
				'ID'          => $row,
				'NAME'        => $tablerow->charname,
				'TABLE.ID'    => $tablerow->answerID,
				'TABLE'       => "CHARACTER_EXTENDED_BACKGROUND",
				'TABLE.DETAIL' => $tablerow->PENDING_DETAIL,
				'DESCRIPTION'  => $description,
				'COMMENT'      => '',
				'TABLE.OLD'    => str_replace("\n", "<br>", "<span>" . stripslashes($tablerow->APPROVED_DETAIL) . "</span>")
			);
			$row++;
		}
		
		
		return $data;
	}
        
    function prepare_items() {
        
        $columns  = $this->get_columns();
        $hidden   = array('TABLE', 'TABLE.ID', 'TABLE.DETAIL');
        $sortable = $this->get_sortable_columns();
		
		$type = "gvapprove";
        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = $type;
		
		$data = $this->read_data();
		$this->items = $data;
        
        $this->process_bulk_action();
		
		$data = $this->read_data();
		$this->items = $data;
		
        function usort_reorder($a,$b){

            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'name';
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; 
            $result = strcmp($a[$orderby], $b[$orderby]); 
            return ($order==='asc') ? $result : -$result; 
        }
        usort($data, 'usort_reorder');
       
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
EXTENDED BACKGROUNDS QUESTIONS TABLE
------------------------------------------------ */


class gvadmin_questions_table extends GVMultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'question',     
            'plural'    => 'questions',    
            'ajax'      => false        
        ) );
    }
	
	function delete_question($selectedID) {
		global $wpdb;
		
		/* Check if question in use */
		$sql = "select characters.NAME
				from " . GVLARP_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND charbgs, 
					" . GVLARP_TABLE_PREFIX . "CHARACTER characters,
					" . GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND questions
				where charbgs.QUESTION_ID = questions.ID 
					and characters.ID = charbgs.CHARACTER_ID
					and questions.ID = %d;";
		$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this question has been filled in for the following characters:";
			echo "<ul>";
			foreach ($isused as $item)
				echo "<li style='color:red'>{$item->NAME}</li>";
			echo "</ul></p>";
			return;
			
		} else {
		
			$sql = "delete from " . GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND where ID = %d;";
			
			$result = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		
			echo "<p style='color:green'>Deleted question $selectedID</p>";
		}
	}
	
 	function add_question($title, $ordering, $grouping, $question, $visible) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'TITLE'          => $title,
						'ORDERING'       => $ordering,
						'GROUPING'       => $grouping,
						'BACKGROUND_QUESTION' => $question,
						'VISIBLE'        => $visible
					);
		
		/* print_r($dataarray); */
		
		$wpdb->insert(GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND",
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
			echo "<p style='color:red'><b>Error:</b> $title could not be inserted (";
			$wpdb->print_error();
			echo ")</p>";
		} else {
			echo "<p style='color:green'>Added question '$title' (ID: {$wpdb->insert_id})</p>";
		}
	}
 	function edit_question($id, $title, $ordering, $grouping, $question, $visible) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'TITLE'          => $title,
						'ORDERING'       => $ordering,
						'GROUPING'       => $grouping,
						'BACKGROUND_QUESTION' => $question,
						'VISIBLE'        => $visible
					);
		
		/* print_r($dataarray); */
		
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND",
					$dataarray,
					array (
						'ID' => $id
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated $title</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No updates made to $title</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update $title ($id)</p>";
		}
	}
   
    function column_default($item, $column_name){
        switch($column_name){
            case 'ORDERING':
                return $item->$column_name;
            case 'GROUPING':
                return $item->$column_name;
            case 'BACKGROUND_QUESTION':
                return stripslashes($item->$column_name);
            default:
                return print_r($item,true); 
        }
    }
 
    function column_title($item){
        
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&amp;action=%s&question=%s&amp;tab=%s">Edit</a>',$_REQUEST['page'],'edit',$item->ID, $this->type),
            'delete'    => sprintf('<a href="?page=%s&amp;action=%s&question=%s&amp;tab=%s">Delete</a>',$_REQUEST['page'],'delete',$item->ID, $this->type),
        );
        
        
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item->TITLE,
            $item->ID,
            $this->row_actions($actions)
        );
    }
   

    function get_columns(){
        $columns = array(
            'cb'            => '<input type="checkbox" />', 
            'TITLE'         => 'Title',
            'ORDERING'      => 'Order',
            'GROUPING'      => 'Group',
 			'VISIBLE'       => 'Question visible to players',
           'BACKGROUND_QUESTION'  => 'Question'
        );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'TITLE'        => array('NAME',true),
            'GROUPING'     => array('GROUPING',false),
            'ORDERING'     => array('ORDERING',false)
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
			if ('string' == gettype($_REQUEST['question'])) {
				$this->delete_question($_REQUEST['question']);
			} else {
				foreach ($_REQUEST['question'] as $question) {
					$this->delete_question($question);
				}
			}
        }
     }

        
    function prepare_items() {
        global $wpdb; 
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
		
		$type = "question";
        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = $type;
        
        $this->process_bulk_action();
		
		/* Get the data from the database */
		$sql = "select questions.ID, questions.TITLE, questions.ORDERING, questions.GROUPING, questions.BACKGROUND_QUESTION, questions.VISIBLE
			from " . GVLARP_TABLE_PREFIX . "EXTENDED_BACKGROUND questions;";
				
		/* order the data according to sort columns */
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']))
			$sql .= " ORDER BY questions.{$_REQUEST['orderby']} {$_REQUEST['order']}";
			
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
SECTORS TABLE
------------------------------------------------ */


class gvadmin_sectors_table extends GVMultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'sector',     
            'plural'    => 'sectors',    
            'ajax'      => false        
        ) );
    }
	
	function delete_sector($selectedID) {
		global $wpdb;
		
		/* Check if sector in use */
		$sql = "select characters.NAME
				from " . GVLARP_TABLE_PREFIX . "CHARACTER_BACKGROUND charbgs, 
					" . GVLARP_TABLE_PREFIX . "CHARACTER characters,
					" . GVLARP_TABLE_PREFIX . "SECTOR sectors
				where charbgs.SECTOR_ID = sectors.ID 
					and characters.ID = charbgs.CHARACTER_ID
					and sectors.ID = %d;";
		$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this sector is in use for the following characters:";
			echo "<ul>";
			foreach ($isused as $item)
				echo "<li style='color:red'>{$item->NAME}</li>";
			echo "</ul></p>";
			return;
			
		} else {
		
			$sql = "delete from " . GVLARP_TABLE_PREFIX . "SECTOR where ID = %d;";
			
			$result = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		
			/* print_r($result); */
			echo "<p style='color:green'>Deleted sector $selectedID</p>";
		}
	}
	
 	function add_sector($name, $description, $visible) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME'          => $name,
						'DESCRIPTION'   => $description,
						'VISIBLE'       => $visible
					);
		
		/* print_r($dataarray); */
		
		$wpdb->insert(GVLARP_TABLE_PREFIX . "SECTOR",
					$dataarray,
					array (
						'%s',
						'%s',
						'%s',
					)
				);
		
		if ($wpdb->insert_id == 0) {
			echo "<p style='color:red'><b>Error:</b> $name could not be inserted (";
			$wpdb->print_error();
			echo ")</p>";
		} else {
			echo "<p style='color:green'>Added sector '$name' (ID: {$wpdb->insert_id})</p>";
		}
	}
 	function edit_sector($id, $name, $description, $visible) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME'          => $name,
						'DESCRIPTION'   => $description,
						'VISIBLE'       => $visible
					);
		
		/* print_r($dataarray); */
		
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "SECTOR",
					$dataarray,
					array (
						'ID' => $id
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated $name</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No updates made to $name</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update $name ($id)</p>";
		}
	}
   
    function column_default($item, $column_name){
        switch($column_name){
            case 'DESCRIPTION':
                return $item->$column_name;
            case 'VISIBLE':
                return $item->$column_name;
           default:
                return print_r($item,true); 
        }
    }
 
    function column_name($item){
        
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&amp;action=%s&sector=%s&amp;tab=%s">Edit</a>',$_REQUEST['page'],'edit',$item->ID, $this->type),
            'delete'    => sprintf('<a href="?page=%s&amp;action=%s&sector=%s&amp;tab=%s">Delete</a>',$_REQUEST['page'],'delete',$item->ID, $this->type),
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
            'VISIBLE'      => 'Visible to Players'
        );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'NAME'        => array('NAME',true),
            'VISIBLE'     => array('VISIBLE',false)
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
			if ('string' == gettype($_REQUEST['sector'])) {
				$this->delete_sector($_REQUEST['sector']);
			} else {
				foreach ($_REQUEST['sector'] as $sector) {
					$this->delete_sector($sector);
				}
			}
        }
     }

        
    function prepare_items() {
        global $wpdb; 
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
		
		$type = "sector";
        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = $type;
        
        $this->process_bulk_action();
		
		/* Get the data from the database */
		$sql = "select sectors.ID, sectors.NAME, sectors.DESCRIPTION, sectors.VISIBLE
			from " . GVLARP_TABLE_PREFIX . "SECTOR sectors;";
				
		/* order the data according to sort columns */
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']))
			$sql .= " ORDER BY sectors.{$_REQUEST['orderby']} {$_REQUEST['order']}";
			
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
BACKGROUNDS TABLE
------------------------------------------------ */


class gvadmin_backgrounds_table extends GVMultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'background',     
            'plural'    => 'backgrounds',    
            'ajax'      => false        
        ) );
    }
	
	function delete_background($selectedID) {
		global $wpdb;
		
		/* Check if background in use */
		$sql = "select characters.NAME
				from " . GVLARP_TABLE_PREFIX . "CHARACTER_BACKGROUND charbgs, 
					" . GVLARP_TABLE_PREFIX . "BACKGROUND backgrounds,
					" . GVLARP_TABLE_PREFIX . "CHARACTER characters
				where charbgs.BACKGROUND_ID = backgrounds.ID 
					and characters.ID = charbgs.CHARACTER_ID
					and backgrounds.ID = %d;";
		$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this background is in use for the following characters:";
			echo "<ul>";
			foreach ($isused as $item)
				echo "<li style='color:red'>{$item->NAME}</li>";
			echo "</ul></p>";
			return;
			
		} else {
		
			$sql = "delete from " . GVLARP_TABLE_PREFIX . "BACKGROUND where ID = %d;";
			
			$result = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		
			/* print_r($result); */
			echo "<p style='color:green'>Deleted background $selectedID</p>";
		}
	}
	
 	function add_background($name, $description, $group, $costmodel_id, $visible, $has_sector, $question) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME'          => $name,
						'DESCRIPTION'   => $description,
						'GROUPING'      => $group,
						'COST_MODEL_ID' => $costmodel_id,
						'VISIBLE'       => $visible,
						'HAS_SECTOR'          => $has_sector,
						'BACKGROUND_QUESTION' => $question
					);
		
		/* print_r($dataarray); */
		
		$wpdb->insert(GVLARP_TABLE_PREFIX . "BACKGROUND",
					$dataarray,
					array (
						'%s',
						'%s',
						'%s',
						'%d',
						'%s',
						'%s',
						'%s'
					)
				);
		
		if ($wpdb->insert_id == 0) {
			echo "<p style='color:red'><b>Error:</b> $name could not be inserted (";
			$wpdb->print_error();
			echo ")</p>";
		} else {
			echo "<p style='color:green'>Added background '$name' (ID: {$wpdb->insert_id})</p>";
		}
	}
 	function edit_background($id, $name, $description, $group, $costmodel_id, $visible, $has_sector, $question) {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME'          => $name,
						'DESCRIPTION'   => $description,
						'GROUPING'      => $group,
						'COST_MODEL_ID' => $costmodel_id,
						'VISIBLE'       => $visible,
						'HAS_SECTOR'          => $has_sector,
						'BACKGROUND_QUESTION' => $question
					);
		
		/* print_r($dataarray); */
		
		$result = $wpdb->update(GVLARP_TABLE_PREFIX . "BACKGROUND",
					$dataarray,
					array (
						'ID' => $id
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated $name</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No updates made to $name</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update $name ($id)</p>";
		}
	}
   
    function column_default($item, $column_name){
        switch($column_name){
            case 'DESCRIPTION':
                return $item->$column_name;
            case 'GROUPING':
                return $item->$column_name;
            case 'VISIBLE':
                return $item->$column_name;
            case 'COSTMODEL':
                return $item->$column_name;
           default:
                return print_r($item,true); 
        }
    }
 
    function column_name($item){
        
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&amp;action=%s&background=%s&amp;tab=%s">Edit</a>',$_REQUEST['page'],'edit',$item->ID, $this->type),
            'delete'    => sprintf('<a href="?page=%s&amp;action=%s&background=%s&amp;tab=%s">Delete</a>',$_REQUEST['page'],'delete',$item->ID, $this->type),
        );
        
        
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item->NAME,
            $item->ID,
            $this->row_actions($actions)
        );
    }

	function column_has_bg_q($item) {
		if ($item->BACKGROUND_QUESTION == "")
			return "No";
		else
			return "Yes";
			
	}
	function column_has_sector($item) {
		if ($item->HAS_SECTOR == "Y")
			return "Yes";
		else
			return "No";
			
	}

    function get_columns(){
        $columns = array(
            'cb'           => '<input type="checkbox" />', 
            'NAME'         => 'Name',
            'DESCRIPTION'  => 'Description',
            'GROUPING'     => 'Background Group',
            'COSTMODEL'    => 'Cost Model',
            'HAS_SECTOR'   => 'Has a Sector',
            'VISIBLE'      => 'Visible to Players',
            'HAS_BG_Q'     => 'Extended Background'
        );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'NAME'        => array('NAME',true),
            'VISIBLE'     => array('VISIBLE',false)
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
			if ('string' == gettype($_REQUEST['background'])) {
				$this->delete_background($_REQUEST['background']);
			} else {
				foreach ($_REQUEST['background'] as $background) {
					$this->delete_background($background);
				}
			}
        }
     }

        
    function prepare_items() {
        global $wpdb; 
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
		
		$type = "bgdata";
        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = $type;
        
        $this->process_bulk_action();
		
		/* Get the data from the database */
		$sql = "select 
					backgrounds.ID, 
					backgrounds.NAME, 
					backgrounds.DESCRIPTION, 
					backgrounds.GROUPING, 
					costmodels.NAME as COSTMODEL, 
					backgrounds.VISIBLE,
					backgrounds.HAS_SECTOR,
					backgrounds.BACKGROUND_QUESTION
			from " . GVLARP_TABLE_PREFIX . "BACKGROUND backgrounds, " . GVLARP_TABLE_PREFIX . "COST_MODEL costmodels
			where backgrounds.COST_MODEL_ID = costmodels.ID";
				
		/* order the data according to sort columns */
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']))
			$sql .= " ORDER BY backgrounds.{$_REQUEST['orderby']} {$_REQUEST['order']}";
			
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