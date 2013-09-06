<?php
require_once GVLARP_CHARACTER_URL . 'lib/fpdf.php';
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class larpcharacter {

	var $name; 
	var $clan;
	var $private_clan;
	var $court;
	var $player;
	var $player_id;
	var $wordpress_id;
	var $generation;
	var $bloodpool;
	var $willpower;
	var $current_willpower;
	var $path_of_enlightenment;
	var $path_rating;
	var $rituals;
	var $max_rating;
	var $date_of_birth;
	var $date_of_embrace;
	var $sire;
	var $combo_disciplines;
	var $current_experience;
	var $clan_flaw;
	
	function load ($characterID){
		global $wpdb;
		
		$wpdb->show_errors();
		
		/* Basic Character Info */
		$sql = "SELECT chara.name                      cname,
					   chara.character_status_comment  cstat_comment,
					   chara.wordpress_id              wpid,
					   player.name                     pname,
					   player.id                       player_id,
					   court.name                      court,
					   pub_clan.name                   public_clan,
					   priv_clan.name                  private_clan,
					   paths.name					   path,
					   gen.name						   generation,
                       gen.bloodpool,
                       gen.blood_per_round,
					   gen.max_rating,
					   chara.date_of_birth,
					   chara.date_of_embrace,
					   chara.sire,
					   priv_clan.clan_flaw
                    FROM " . GVLARP_TABLE_PREFIX . "CHARACTER chara,
                         " . GVLARP_TABLE_PREFIX . "PLAYER player,
                         " . GVLARP_TABLE_PREFIX . "COURT court,
                         " . GVLARP_TABLE_PREFIX . "CLAN pub_clan,
                         " . GVLARP_TABLE_PREFIX . "CLAN priv_clan,
						 " . GVLARP_TABLE_PREFIX . "GENERATION gen,
						 " . GVLARP_TABLE_PREFIX . "ROAD_OR_PATH paths
                    WHERE chara.PUBLIC_CLAN_ID = pub_clan.ID
                      AND chara.PRIVATE_CLAN_ID = priv_clan.ID
                      AND chara.COURT_ID = court.ID
                      AND chara.PLAYER_ID = player.ID
					  AND chara.GENERATION_ID = gen.ID
					  AND chara.ROAD_OR_PATH_ID = paths.ID
                      AND chara.ID = '%s';";
		$sql = $wpdb->prepare($sql, $characterID);
		/* echo "<p>SQL: $sql</p>"; */
		
		$result = $wpdb->get_results($sql);
		/* print_r($result); */
		
		$this->name         = $result[0]->cname;
		$this->clan         = $result[0]->public_clan;
		$this->private_clan = $result[0]->private_clan;
		$this->court        = $result[0]->court;
		$this->player       = $result[0]->pname;
		$this->wordpress_id = $result[0]->wpid;
		$this->generation   = $result[0]->generation;
		$this->max_rating   = $result[0]->max_rating;
		$this->path_of_enlightenment    = $result[0]->path;
		$this->bloodpool    = $result[0]->bloodpool;
		$this->sire         = $result[0]->sire;
		$this->date_of_birth   = $result[0]->date_of_birth;
		$this->date_of_embrace = $result[0]->date_of_embrace;
		$this->player_id    = $result[0]->player_id;
		$this->clan_flaw    = $result[0]->clan_flaw;
		
		/* Attributes */
		$sql = "SELECT stat.name		name,
					stat.grouping		grouping,
					stat.ordering		ordering,
					charstat.comment	specialty,
					charstat.level		level
				FROM
					" . GVLARP_TABLE_PREFIX . "STAT stat,
					" . GVLARP_TABLE_PREFIX . "CHARACTER_STAT charstat,
					" . GVLARP_TABLE_PREFIX . "CHARACTER chara
				WHERE
					charstat.CHARACTER_ID = chara.ID
					AND charstat.STAT_ID = stat.ID
					AND chara.id = '%s'
				ORDER BY stat.grouping, stat.ordering;";
		$sql = $wpdb->prepare($sql, $characterID);
		$result = $wpdb->get_results($sql);
		
		$this->attributes = $result;
		$this->attributegroups = array();
		for ($i=0;$i<count($result);$i++)
			if (array_key_exists($result[$i]->grouping, $this->attributegroups))
				array_push($this->attributegroups[$result[$i]->grouping], $this->attributes[$i]);
			else {
				$this->attributegroups[$result[$i]->grouping] = array($this->attributes[$i]);
			}
		
		/* Abilities */
		$sql = "SELECT skill.name		skillname,
					skill.grouping		grouping,
					charskill.comment	specialty,
					charskill.level		level
				FROM
					" . GVLARP_TABLE_PREFIX . "SKILL skill,
					" . GVLARP_TABLE_PREFIX . "CHARACTER_SKILL charskill,
					" . GVLARP_TABLE_PREFIX . "CHARACTER chara
				WHERE
					charskill.CHARACTER_ID = chara.ID
					AND charskill.SKILL_ID = skill.ID
					AND chara.id = '%s'
				ORDER BY skill.name ASC;";
		$sql = $wpdb->prepare($sql, $characterID);
		$result = $wpdb->get_results($sql);

		$this->abilities = $result;
		$this->abilitygroups = array();
		for ($i=0;$i<count($result);$i++)
			if (array_key_exists($result[$i]->grouping, $this->abilitygroups))
				array_push($this->abilitygroups[$result[$i]->grouping], $this->abilities[$i]);
			else {
				$this->abilitygroups[$result[$i]->grouping] = array($this->abilities[$i]);
			}
		
		/* Backgrounds */
		$sql = "SELECT bground.name		     background,
					sectors.name		     sector,
					charbgnd.comment	     comment,
					charbgnd.level		     level,
					charbgnd.approved_detail detail
				FROM
					" . GVLARP_TABLE_PREFIX . "BACKGROUND bground,
					" . GVLARP_TABLE_PREFIX . "CHARACTER chara,
					" . GVLARP_TABLE_PREFIX . "CHARACTER_BACKGROUND charbgnd
				LEFT JOIN 
					" . GVLARP_TABLE_PREFIX . "SECTOR sectors
				ON charbgnd.SECTOR_ID = sectors.ID
				WHERE
					charbgnd.CHARACTER_ID = chara.ID
					AND charbgnd.BACKGROUND_ID = bground.ID
					AND chara.id = '%s'
				ORDER BY bground.name ASC;";
		$sql = $wpdb->prepare($sql, $characterID);
		$result = $wpdb->get_results($sql);
		
		$this->backgrounds = $result;
		
		/* Disciplines */
		$sql = "SELECT disciplines.NAME		name,
					chardisc.level			level
				FROM
					" . GVLARP_TABLE_PREFIX . "DISCIPLINE disciplines,
					" . GVLARP_TABLE_PREFIX . "CHARACTER_DISCIPLINE chardisc,
					" . GVLARP_TABLE_PREFIX . "CHARACTER chara
				WHERE
					chardisc.DISCIPLINE_ID = disciplines.ID
					AND chardisc.CHARACTER_ID = chara.ID
					AND chara.id = '%s'
				ORDER BY disciplines.name ASC;";
		$sql = $wpdb->prepare($sql, $characterID);
		$result = $wpdb->get_results($sql);
		$this->disciplines = $result;
		
		/* Merits and Flaws */
		$sql = "SELECT merits.NAME		      name,
					charmerit.comment	      comment,
					charmerit.level		      level,
					charmerit.approved_detail detail
				FROM
					" . GVLARP_TABLE_PREFIX . "MERIT merits,
					" . GVLARP_TABLE_PREFIX . "CHARACTER_MERIT charmerit,
					" . GVLARP_TABLE_PREFIX . "CHARACTER chara
				WHERE
					charmerit.MERIT_ID = merits.ID
					AND charmerit.CHARACTER_ID = chara.ID
					AND chara.id = '%s'
				ORDER BY merits.name ASC;";
		$sql = $wpdb->prepare($sql, $characterID);
		$result = $wpdb->get_results($sql);
		$this->meritsandflaws = $result;

		/* Full Willpower */
		$sql = "SELECT charstat.level
				FROM " . GVLARP_TABLE_PREFIX . "CHARACTER_STAT charstat,
					" . GVLARP_TABLE_PREFIX . "STAT stat
				WHERE charstat.CHARACTER_ID = '%s' 
					AND charstat.STAT_ID = stat.ID
					AND stat.name = 'Willpower';";
		$sql = $wpdb->prepare($sql, $characterID);
		$result = $wpdb->get_results($sql);
		$this->willpower = $result[0]->level;
		
		/* Current Willpower */
        $sql = "SELECT SUM(char_temp_stat.amount) currentwp
                FROM " . GVLARP_TABLE_PREFIX . "CHARACTER_TEMPORARY_STAT char_temp_stat,
                     " . GVLARP_TABLE_PREFIX . "TEMPORARY_STAT tstat
                WHERE char_temp_stat.character_id = '%s'
					AND char_temp_stat.temporary_stat_id = tstat.id
					AND tstat.name = 'Willpower';";
		$sql = $wpdb->prepare($sql, $characterID);
		$result = $wpdb->get_results($sql);
		$this->current_willpower = $result[0]->currentwp;
		
		/* Humanity */
		$sql = "SELECT SUM(cpath.AMOUNT) path_rating
				FROM " . GVLARP_TABLE_PREFIX . "CHARACTER_ROAD_OR_PATH cpath
				WHERE cpath.CHARACTER_ID = %s;";	
		$sql = $wpdb->prepare($sql, $characterID);
		$result = $wpdb->get_results($sql);
		$this->path_rating = $result[0]->path_rating;
		
		/* Rituals */
		$sql = "SELECT disciplines.name as discname, rituals.name as ritualname, rituals.level
				FROM " . GVLARP_TABLE_PREFIX . "DISCIPLINE disciplines,
                    " . GVLARP_TABLE_PREFIX . "CHARACTER_RITUAL char_rit,
                    " . GVLARP_TABLE_PREFIX . "RITUAL rituals
				WHERE
					char_rit.CHARACTER_ID = '%s'
					AND char_rit.RITUAL_ID = rituals.ID
					AND rituals.DISCIPLINE_ID = disciplines.ID
				ORDER BY disciplines.name, rituals.level, rituals.name;";
		$sql = $wpdb->prepare($sql, $characterID);
		$result = $wpdb->get_results($sql);
		$i = 0;
		foreach ($result as $ritual) {
			$this->rituals[$ritual->discname][$i] = array('name' => $ritual->ritualname, 'level' => $ritual->level);
			$i++;
		}
		
		/* Combo disciplines */
		$sql = "SELECT combo.name
				FROM
					" . GVLARP_TABLE_PREFIX . "CHARACTER_COMBO_DISCIPLINE charcombo,
					" . GVLARP_TABLE_PREFIX . "COMBO_DISCIPLINE combo
				WHERE
					charcombo.COMBO_DISCIPLINE_ID = combo.ID
					AND charcombo.CHARACTER_ID = '%s'
				ORDER BY combo.name;";
		$sql = $wpdb->prepare($sql, $characterID);
		$result = $wpdb->get_results($sql);
		$this->combo_disciplines = array();
		for ($i=0;$i<count($result);$i++) {	
			$this->combo_disciplines[$i] = $result[$i]->name;
		}
		
		/* Current Experience */
		$sql = "SELECT SUM(xpspends.amount) as total
				FROM
					" . GVLARP_TABLE_PREFIX . "PLAYER_XP as xpspends
				WHERE
					xpspends.PLAYER_ID = '%s'";
		$sql = $wpdb->prepare($sql, $this->player_id);
		$result = $wpdb->get_results($sql);
		$this->current_experience = $result[0]->total;
		
		
	}
	function getAttributes($group = "") {
		$result = array();
		if ($group == "")
			return $this->attributes;
		else
			return $this->attributegroups[$group];
	}
	function getAbilities($group = "") {
		$result = array();
		if ($group == "")
			return $this->abilities;
		else
			if (isset($this->abilitygroups[$group]))
				return $this->abilitygroups[$group];
			else
				return array();
	}
	function getBackgrounds() {
		return $this->backgrounds;
	}
	function getDisciplines() {
		return $this->disciplines;
	}

}


/* 
-----------------------------------------------
MULTI-PAGE LIST TABLE
------------------------------------------------ */


class GVMultiPage_ListTable extends WP_List_Table {
      
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],  
            $item->ID               
        );
    }

    function column_visible($item){
		return ($item->VISIBLE == "Y") ? "Yes" : "No";
    }
   
	/* Need own version of this function to deal with tabs */
	function print_column_headers( $with_id = true ) {
		list( $columns, $hidden, $sortable ) = $this->get_column_info();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url = remove_query_arg( 'paged', $current_url );
		$current_url = remove_query_arg( 'action', $current_url );
		$current_url = add_query_arg('tab', $this->type, $current_url);

		if ( isset( $_GET['orderby'] ) && (!isset($_GET['tab']) || (isset($_GET['tab']) && $_GET['tab'] == $this->type ) ) )
			$current_orderby = $_GET['orderby'];
		else
			$current_orderby = '';

		if ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] && (!isset($_GET['tab']) || (isset($_GET['tab']) && $_GET['tab'] == $this->type ) )  )
			$current_order = 'desc';
		else
			$current_order = 'asc';

		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;
			$columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All' ) . '</label>'
				. '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
			$cb_counter++;
		}

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			$style = '';
			if ( in_array( $column_key, $hidden ) )
				$style = 'display:none;';

			$style = ' style="' . $style . '"';

			if ( 'cb' == $column_key )
				$class[] = 'check-column';
			elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) )
				$class[] = 'num';

			if ( isset( $sortable[$column_key] ) ) {
				list( $orderby, $desc_first ) = $sortable[$column_key];

				if ( $current_orderby == $orderby ) {
					$order = 'asc' == $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					$order = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}

				$column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
			}

			$id = $with_id ? "id='$column_key'" : '';

			if ( !empty( $class ) )
				$class = "class='" . join( ' ', $class ) . "'";

			echo "<th scope='col' $id $class $style>$column_display_name</th>";
		}
	}


}

class GVReport_ListTable extends WP_List_Table {

	var $pagewidth;
      
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'row',     
            'plural'    => 'rows',    
            'ajax'      => false        
        ) );
    }
	
    function column_visible($item){
		return ($item->VISIBLE == "Y") ? "Yes" : "No";
    }
   
    function get_bulk_actions() {
        $actions = array();
        return $actions;
    }
    function process_bulk_action() {
        		
        
    }
	
	function load_filters() {
		global $wpdb;
		
		/* get defaults */
		$default_character_visible = "Y";
		
		$sql = "SELECT ID FROM " . GVLARP_TABLE_PREFIX. "PLAYER_STATUS WHERE NAME = %s";
		$result = $wpdb->get_results($wpdb->prepare($sql,'Active'));
		$default_player_status = $result[0]->ID;
		
		$sql = "SELECT ID FROM " . GVLARP_TABLE_PREFIX. "CHARACTER_TYPE WHERE NAME = %s";
		$result = $wpdb->get_results($wpdb->prepare($sql,'PC'));
		$default_character_type    = $result[0]->ID;
		
		$sql = "SELECT ID FROM " . GVLARP_TABLE_PREFIX. "CHARACTER_STATUS WHERE NAME = %s";
		$result = $wpdb->get_results($wpdb->prepare($sql,'Alive'));
		$default_character_status  = $result[0]->ID;
		
		/* get filter options */
		$sql = "SELECT ID, NAME FROM " . GVLARP_TABLE_PREFIX. "PLAYER_STATUS";
		$this->filter_player_status = gvmake_filter($wpdb->get_results($sql));
		
		$sql = "SELECT ID, NAME FROM " . GVLARP_TABLE_PREFIX. "CHARACTER_TYPE";
		$this->filter_character_type = gvmake_filter($wpdb->get_results($sql));
		
		$sql = "SELECT ID, NAME FROM " . GVLARP_TABLE_PREFIX. "CHARACTER_STATUS";
		$this->filter_character_status = gvmake_filter($wpdb->get_results($sql));		
		
		/* set active filters */
		if ( isset( $_REQUEST['player_status'] ) && array_key_exists( $_REQUEST['player_status'], $this->filter_player_status ) ) {
			$this->active_filter_player_status = sanitize_key( $_REQUEST['player_status'] );
		} else {
			$this->active_filter_player_status = $default_player_status;
		}
		if ( isset( $_REQUEST['character_type'] ) && array_key_exists( $_REQUEST['character_type'], $this->filter_character_type ) ) {
			$this->active_filter_character_type = sanitize_key( $_REQUEST['character_type'] );
		} else {
			$this->active_filter_character_type = $default_character_type;
		}
		if ( isset( $_REQUEST['character_status'] ) && array_key_exists( $_REQUEST['character_status'], $this->filter_character_status ) ) {
			$this->active_filter_character_status = sanitize_key( $_REQUEST['character_status'] );
		} else {
			$this->active_filter_character_status = $default_character_status;
		}
		if ( isset( $_REQUEST['character_visible'] )) {
			$this->active_filter_character_visible = strtoupper(sanitize_key( $_REQUEST['character_visible'] ));
		} else {
			$this->active_filter_character_visible = $default_character_visible;
		}
		
	
	}
	
	function get_filter_sql () {
	
		$sql = "";
		$args = array();
				
		if ( "all" !== $this->active_filter_player_status) {
			$sql .= " AND players.PLAYER_STATUS_ID = %s";
			array_push($args, $this->active_filter_player_status);
		}
		if ( "all" !== $this->active_filter_character_type) {
			$sql .= " AND characters.CHARACTER_TYPE_ID = %s";
			array_push($args, $this->active_filter_character_type);
		}
		if ( "all" !== $this->active_filter_character_status) {
			$sql .= " AND characters.CHARACTER_STATUS_ID = %s";
			array_push($args, $this->active_filter_character_status);
		}
		if ( "ALL" !== $this->active_filter_character_visible) {
			$sql .= " AND characters.VISIBLE = %s";
			array_push($args, $this->active_filter_character_visible);
		}
		
		return array($sql, $args);
	
	}

	function filter_tablenav () {
			echo "<span>Player Status: </span>";
			if ( !empty( $this->filter_player_status ) ) {
				echo "<select name='player_status'>";
				foreach( $this->filter_player_status as $key => $value ) {
					echo '<option value="' . esc_attr( $key ) . '" ' . selected( $this->active_filter_player_status, $key ) . '>' . esc_attr( $value ) . '</option>';
				}
				echo '</select>';
			}
			
			echo "<span>Character Type: </span>";
			if ( !empty( $this->filter_character_type ) ) {
				echo "<select name='character_type'>";
				foreach( $this->filter_character_type as $key => $value ) {
					echo '<option value="' . esc_attr( $key ) . '" ' . selected( $this->active_filter_character_type, $key ) . '>' . esc_attr( $value ) . '</option>';
				}
				echo '</select>';
			}
			
			echo "<span>Character Status: </span>";
			if ( !empty( $this->filter_character_status ) ) {
				echo "<select name='character_status'>";
				foreach( $this->filter_character_status as $key => $value ) {
					echo '<option value="' . esc_attr( $key ) . '" ' . selected( $this->active_filter_character_status, $key ) . '>' . esc_attr( $value ) . '</option>';
				}
				echo '</select>';
			}
			
			echo "<span>Character Visibility: </span>";
			echo "<select name='character_visible'>";
			echo '<option value="all" ' . selected( $this->active_filter_character_visible, 'all' ) . '>All</option>';
			echo '<option value="Y" '   . selected( $this->active_filter_character_visible, 'Y' )   . '>Yes</option>';
			echo '<option value="N" '   . selected( $this->active_filter_character_visible, 'N' )   . '>No</option>';
			echo '</select>';
			
			submit_button( 'Filter', 'secondary', false, false );
			echo "<span>Download: </span>";
			echo "<a class='button-primary' href='" . plugins_url( 'gvlarp-character/tmp/report.pdf') . "'>PDF</a>";
	}
	 
	function extra_tablenav($which) {
		if ($which == 'top')  {
			echo "<div class='gvfilter'>";
			$this->filter_tablenav();
		
			echo "</div>";
		}
	}
	
	/* Add Headings function to add report name to sort url */
       function print_column_headers( $with_id = true ) {	
			list( $columns, $hidden, $sortable ) = $this->get_column_info();

			$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			$current_url = remove_query_arg( 'paged', $current_url );
			$current_url = add_query_arg('report', $_REQUEST['report']);

			if ( isset( $_GET['orderby'] ) )
					$current_orderby = $_GET['orderby'];
			else
					$current_orderby = '';

			if ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] )
					$current_order = 'desc';
			else
					$current_order = 'asc';

			if ( ! empty( $columns['cb'] ) ) {
					static $cb_counter = 1;
					$columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All' ) . '</label>'
							. '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
					$cb_counter++;
			}

			foreach ( $columns as $column_key => $column_display_name ) {
					$class = array( 'manage-column', "column-$column_key" );

					$style = '';
					if ( in_array( $column_key, $hidden ) )
							$style = 'display:none;';

					$style = ' style="' . $style . '"';

					if ( 'cb' == $column_key )
							$class[] = 'check-column';
					elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) )
							$class[] = 'num';

					if ( isset( $sortable[$column_key] ) ) {
							list( $orderby, $desc_first ) = $sortable[$column_key];

							if ( $current_orderby == $orderby ) {
									$order = 'asc' == $current_order ? 'desc' : 'asc';
									$class[] = 'sorted';
									$class[] = $current_order;
							} else {
									$order = $desc_first ? 'desc' : 'asc';
									$class[] = 'sortable';
									$class[] = $desc_first ? 'asc' : 'desc';
							}

							$column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
					}

					$id = $with_id ? "id='$column_key'" : '';

					if ( !empty( $class ) )
							$class = "class='" . join( ' ', $class ) . "'";

					echo "<th scope='col' $id $class $style>$column_display_name</th>";
			}
	}
	
	function set_column_widths($columns = "") {
		$colwidths = array();
		$count = count($columns);
		if (!empty($columns))
			foreach ($columns as $column => $coldesc) {
				$colwidths[$column] = ($this->pagewidth - 10) / $count;
			}
		return $colwidths;
	}
	function set_column_alignment($columns = "") {
		$colwidths = array();
		$count = count($columns);
		if (!empty($columns))
			foreach ($columns as $column => $coldesc) {
				$colwidths[$column] = 'L';
			}
		return $colwidths;
	}
	
	function output_report ($title) {
		
		$pdf = new PDFreport('L','mm','A4');
		$pdf->title = $title;
		$pdf->SetTitle($title);
		$pdf->AliasNbPages();
		$pdf->SetMargins(5, 5, 5);
		$pdf->AddPage();
		
		$lineheight = 5;
		$columns = $this->get_columns();
		$this->pagewidth = $pdf->pagewidth;
		$colwidths = $this->set_column_widths($columns);
		$colalign  = $this->set_column_alignment($columns);
		
		$pdf->SetFont('Arial','B',9);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetFillColor(255,0,0);
		
		foreach ($columns as $columnname => $columndesc) {
			$pdf->Cell($colwidths[$columnname],5,$columndesc,1,0,'C',1);
		}
		$pdf->Ln();
		
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFillColor(200);
		$pdf->SetFont('Arial','',9);
		$row = 0;
		
		if (count($this->items) > 0) {
		
			foreach ($this->items as $datarow) {
				$rowheight = 0;
				
				/* get row height */
				foreach ($columns as $columnname => $columndesc) {
					$text = $pdf->PrepareText($datarow->$columnname);
					$cellheight = $pdf->GetCellHeight($text, $colwidths[$columnname], $lineheight);
					if ($cellheight > $rowheight) $rowheight = $cellheight;
				}
			
				foreach ($columns as $columnname => $columndesc) {
					$x = $pdf->GetX();
					$y = $pdf->GetY();
					
					$text = $pdf->PrepareText($datarow->$columnname);
					
					$cellheight = $pdf->GetCellHeight($text, $colwidths[$columnname], $lineheight);
					
					if ($cellheight == $rowheight)
						$h = $lineheight;
					elseif ($cellheight = $lineheight)
						$h = $rowheight;
					else
						$h = $rowheight / $cellheight;
					
					$pdf->MultiCell($colwidths[$columnname],$h,$text,1,$colalign[$columnname], $row % 2);
					$pdf->SetXY($x + $colwidths[$columnname], $y);
				}
				$pdf->SetY($y + $rowheight);
				
				$row++;
			}
		}
		
		$pdf->Output(GVLARP_CHARACTER_URL . 'tmp/report.pdf', 'F');
		
	}

}

/* 
-----------------------------------------------
PRINT REPORT
------------------------------------------------ */
class PDFreport extends FPDF {

	var $title;
	var $pagewidth = 297;

	function Header()
	{

		$this->SetFont('Arial','B',16);
		$this->SetTextColor(0,0,0);
		$this->Cell(0,10,$this->title,0,1,'C');

		$this->Ln(2);
	}

	function Footer()
	{		
		$footerdate = date_i18n(get_option('date_format'));
	
		$this->SetY(-15);
		$this->SetFont('Arial','I',8);
		$this->SetLineWidth(0.3);
		
		$this->Cell(0,10,'Report | Page ' . $this->PageNo().' of {nb} | Generated on ' . $footerdate,'T',0,'C');
	}
	
	function GetCellHeight ($text, $cellwidth, $lineheight) {
		
		$lines = ceil( $this->GetStringWidth($text) / ($cellwidth - 1) );
		
		$height = ceil($lineheight * $lines);
		
		/* plus anything from extra newlines */
		$height = $height + ($lineheight * substr_count($text, "\n"));
		
		return $height;
	}

	function PrepareText ($text) {
		
		$text = stripslashes($text);
		$text = str_ireplace("<br>", "\n", $text);
		$text = str_ireplace("<br />", "\n", $text);
		$text = str_ireplace("<i>", "", $text);
		$text = str_ireplace("</i>", "", $text);
		$text = str_ireplace("<b>", "", $text);
		$text = str_ireplace("</b>", "", $text);
		
		/* remove extra whitespace and trailing newlines */
		$text = trim($text);
	
		return $text;
	}

}



?>