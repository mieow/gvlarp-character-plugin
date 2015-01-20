<?php
require_once VTM_CHARACTER_URL . 'lib/fpdf.php';
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class vtmclass_character {

	var $name;
	var $display_name;
	var $sect;
	var $clan;
	var $private_clan;
	var $private_icon;
	var $public_icon;
	var $domain;
	var $player;
	var $player_id;
	var $wordpress_id;
	var $generation;
	var $bloodpool;
	var $blood_per_round;
	var $willpower;
	var $pending_willpower;
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
	var $pending_experience;
	var $spent_experience;
	var $nature;
	var $demeanour;
	var $clan_flaw;
	var $quote;
	var $portrait;
	var $char_status_comment;
	var $char_status;
	var $offices;
	var $history;
	var $last_updated;
	var $concept;
	var $email;
	
	function load ($characterID){
		global $wpdb;
		global $vtmglobal;
		
		$wpdb->show_errors();
				
		/* Basic Character Info */
		$sql = "SELECT chara.name                      cname,
					   chara.character_status_comment  cstat_comment,
					   cstatus.name                    cstat,
					   chara.wordpress_id              wpid,
					   chara.last_updated			   last_updated,
					   player.name                     pname,
					   player.id                       player_id,
					   domains.name                    domain,
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
					   priv_clan.clan_flaw,
					   sects.name                      sect,
					   pub_clan.icon_link			   public_icon,
					   priv_clan.icon_link			   private_icon,
					   chara.concept				   concept,
					   chara.email
                    FROM " . VTM_TABLE_PREFIX . "CHARACTER chara,
                         " . VTM_TABLE_PREFIX . "PLAYER player,
                         " . VTM_TABLE_PREFIX . "DOMAIN domains,
                         " . VTM_TABLE_PREFIX . "CLAN pub_clan,
                         " . VTM_TABLE_PREFIX . "CLAN priv_clan,
						 " . VTM_TABLE_PREFIX . "GENERATION gen,
						 " . VTM_TABLE_PREFIX . "ROAD_OR_PATH paths,
						 " . VTM_TABLE_PREFIX . "SECT sects,
						 " . VTM_TABLE_PREFIX . "CHARACTER_STATUS cstatus
                    WHERE chara.PUBLIC_CLAN_ID = pub_clan.ID
                      AND chara.PRIVATE_CLAN_ID = priv_clan.ID
                      AND chara.DOMAIN_ID = domains.ID
                      AND chara.PLAYER_ID = player.ID
					  AND chara.GENERATION_ID = gen.ID
					  AND chara.ROAD_OR_PATH_ID = paths.ID
					  AND chara.SECT_ID = sects.ID
					  AND chara.CHARACTER_STATUS_ID = cstatus.ID
                      AND chara.ID = '%s';";
		$sql = $wpdb->prepare($sql, $characterID);
		//echo "<p>SQL: ($characterID) $sql</p>";
		
		$result = $wpdb->get_results($sql);
		//print_r($result);
		
		if (count($result) > 0) {
			$this->name         = stripslashes($result[0]->cname);
			$this->clan         = $result[0]->public_clan;
			$this->private_clan = $result[0]->private_clan;
			$this->public_icon  = $result[0]->public_icon;
			$this->private_icon = $result[0]->private_icon;
			$this->domain       = $result[0]->domain;
			$this->player       = stripslashes($result[0]->pname);
			$this->wordpress_id = $result[0]->wpid;
			$this->generation   = $result[0]->generation;
			$this->max_rating   = $result[0]->max_rating;
			$this->player_id    = $result[0]->player_id;
			$this->clan_flaw    = stripslashes($result[0]->clan_flaw);
			$this->sect         = $result[0]->sect;
			$this->bloodpool    = $result[0]->bloodpool;
			$this->sire         = stripslashes($result[0]->sire);
			$this->char_status  = $result[0]->cstat;
			$this->last_updated = $result[0]->last_updated;
			$this->concept      = stripslashes($result[0]->concept);
			$this->blood_per_round = $result[0]->blood_per_round;
			$this->date_of_birth   = $result[0]->date_of_birth;
			$this->date_of_embrace = $result[0]->date_of_embrace;
			$this->char_status_comment   = stripslashes($result[0]->cstat_comment);
			$this->path_of_enlightenment = stripslashes($result[0]->path);
			$this->email        = $result[0]->email;
		} else {
			$this->name         = 'No character selected';
			$this->clan         = '';
			$this->private_clan = '';
			$this->public_icon  = '';
			$this->private_icon = '';
			$this->domain       = '';
			$this->player       = 'No player selected';
			$this->wordpress_id = '';
			$this->generation   = '';
			$this->max_rating   = 5;
			$this->player_id    = 0;
			$this->clan_flaw    = '';
			$this->sect         = '';
			$this->bloodpool    = 10;
			$this->sire         = '';
			$this->char_status  = '';
			$this->last_updated = '';
			$this->blood_per_round = 1;
			$this->date_of_birth   = '';
			$this->date_of_embrace = '';
			$this->char_status_comment   = '';
			$this->path_of_enlightenment = '';
			$this->concept      = '';
			$this->email        = '';
		}
		
        $user = get_user_by('login',$this->name);
        $this->display_name = isset($user->display_name) ? $user->display_name : 'No character selected';
		
		// Profile
		$sql = "SELECT QUOTE, PORTRAIT
				FROM 
					" . VTM_TABLE_PREFIX . "CHARACTER_PROFILE
				WHERE
					CHARACTER_ID = %s";
		$result = $wpdb->get_row($wpdb->prepare($sql, $characterID));
		$this->quote    = isset($result->QUOTE) ? $result->QUOTE : '';
		if (empty($result->PORTRAIT))
			$this->portrait = $vtmglobal['config']->PLACEHOLDER_IMAGE;
		else
			$this->portrait = $result->PORTRAIT;
		
		/* Nature / Demeanour, if used */
		if ($vtmglobal['config']->USE_NATURE_DEMEANOUR == 'Y') {
			$sql = "SELECT 
						natures.name as nature,
						demeanours.name as demeanour
					FROM
						" . VTM_TABLE_PREFIX . "CHARACTER chara,
						" . VTM_TABLE_PREFIX . "NATURE natures,
						" . VTM_TABLE_PREFIX . "NATURE demeanours
					WHERE
						chara.NATURE_ID = natures.ID
						AND chara.DEMEANOUR_ID = demeanours.ID
						AND chara.ID = %s";
			$result = $wpdb->get_row($wpdb->prepare($sql, $characterID));
			
			$this->nature    = isset($result->nature) ? $result->nature : '';
			$this->demeanour = isset($result->demeanour) ? $result->demeanour : '';
		}
		
		/* Attributes */
		$sql = "SELECT stat.name		name,
					stat.grouping		grouping,
					stat.ordering		ordering,
					IFNULL(freebie.SPECIALISATION,charstat.comment)	specialty,
					IFNULL(freebie.LEVEL_TO,charstat.level) level,
					xp.CHARTABLE_LEVEL  pending
				FROM
					" . VTM_TABLE_PREFIX . "STAT stat
					LEFT JOIN (
						SELECT ITEMTABLE_ID, LEVEL_TO, SPECIALISATION
						FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
						WHERE
							ITEMTABLE = 'STAT'
							AND CHARACTER_ID = %s
					) freebie
					ON
						freebie.ITEMTABLE_ID = stat.ID
					LEFT JOIN (
						SELECT ITEMTABLE_ID, CHARTABLE_LEVEL
						FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
						WHERE
							ITEMTABLE = 'STAT'
							AND CHARACTER_ID = %s
					) xp
					ON
						xp.ITEMTABLE_ID = stat.ID,
					" . VTM_TABLE_PREFIX . "CHARACTER_STAT charstat,
					" . VTM_TABLE_PREFIX . "CHARACTER chara
				WHERE
					charstat.CHARACTER_ID = chara.ID
					AND charstat.STAT_ID = stat.ID
					AND chara.id = '%s'
				ORDER BY stat.grouping, stat.ordering;";
		$sql = $wpdb->prepare($sql, $characterID, $characterID, $characterID);
		$result = $wpdb->get_results($sql);
		//echo "<p>SQL: $sql</p>";
		//print_r($result);
		
		$this->attributes = $result;
		$this->attributegroups = array();
		for ($i=0;$i<count($result);$i++)
			if (array_key_exists($result[$i]->grouping, $this->attributegroups))
				array_push($this->attributegroups[$result[$i]->grouping], $this->attributes[$i]);
			else {
				$this->attributegroups[$result[$i]->grouping] = array($this->attributes[$i]);
			}
		
		/* Abilities */
		// Abilities from skill table + freebie points with pending XP
		$sql = "SELECT skill.name		skillname,
					skilltype.name		grouping,
					IFNULL(freebie.SPECIALISATION,charskill.comment)	specialty,
					IFNULL(freebie.LEVEL_TO,charskill.level) level,
					xp.CHARTABLE_LEVEL  pending,
					skill.multiple      multiple
				FROM
					" . VTM_TABLE_PREFIX . "SKILL skill,
					" . VTM_TABLE_PREFIX . "SKILL_TYPE skilltype,
					" . VTM_TABLE_PREFIX . "CHARACTER_SKILL charskill
					LEFT JOIN (
						SELECT CHARTABLE_ID, LEVEL_TO, SPECIALISATION
						FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
						WHERE
							ITEMTABLE = 'SKILL'
							AND CHARACTER_ID = %s
					) freebie
					ON
						freebie.CHARTABLE_ID = charskill.ID
					LEFT JOIN (
						SELECT CHARTABLE_ID, CHARTABLE_LEVEL, SPECIALISATION
						FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
						WHERE
							CHARTABLE = 'CHARACTER_SKILL'
							AND CHARACTER_ID = %s
					) xp
					ON
						xp.CHARTABLE_ID = charskill.ID,
					" . VTM_TABLE_PREFIX . "CHARACTER chara
				WHERE
					charskill.CHARACTER_ID = chara.ID
					AND charskill.SKILL_ID = skill.ID
					AND skilltype.ID = skill.SKILL_TYPE_ID
					AND chara.id = '%s'
				ORDER BY skill.name ASC;";
		$sql = $wpdb->prepare($sql, $characterID, $characterID, $characterID);
		$result = $wpdb->get_results($sql);
		//echo "<p>SQL: $sql</p>";
		//print_r($result);
		
		// freebie points spend with pending xp
		$sql = "SELECT skill.NAME		skillname,
					skilltype.name		grouping,
					freebie.SPECIALISATION	specialty,
					freebie.LEVEL_TO 		level,
					xp.CHARTABLE_LEVEL      pending,
					skill.multiple      	multiple,
					freebie.CHARTABLE_ID	chartableid
			FROM
				" . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND freebie
					LEFT JOIN (
						SELECT CHARTABLE_ID, CHARTABLE_LEVEL, SPECIALISATION
						FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
						WHERE
							CHARTABLE = 'PENDING_FREEBIE_SPEND'
							AND ITEMTABLE = 'SKILL'
							AND CHARACTER_ID = %s
					) xp
					ON
						xp.CHARTABLE_ID = freebie.ID,
				" . VTM_TABLE_PREFIX . "SKILL skill,
				" . VTM_TABLE_PREFIX . "SKILL_TYPE skilltype
			WHERE
				freebie.CHARACTER_ID = %s
				AND skill.ID = freebie.ITEMTABLE_ID
				AND skilltype.ID = skill.SKILL_TYPE_ID
				AND freebie.ITEMTABLE = 'SKILL'
				AND freebie.CHARTABLE_ID = 0";
		$sql = $wpdb->prepare($sql, $characterID, $characterID);
		$freebies = $wpdb->get_results($sql);
		//echo "SQL: $sql</p>";
		//print_r($freebies);
		
		// pending xp for new skills
		$sql = "SELECT skill.NAME			skillname,
					skilltype.name		grouping,
					xp.SPECIALISATION		specialty,
					0 						level,
					xp.CHARTABLE_LEVEL      pending,
					skill.multiple      	multiple,
					0						chartableid
			FROM
				" . VTM_TABLE_PREFIX . "PENDING_XP_SPEND xp,
				" . VTM_TABLE_PREFIX . "SKILL skill,
				" . VTM_TABLE_PREFIX . "SKILL_TYPE skilltype
			WHERE
				xp.CHARACTER_ID = %s
				AND skill.ID = xp.ITEMTABLE_ID
				AND skilltype.ID = skill.SKILL_TYPE_ID
				AND xp.ITEMTABLE = 'SKILL'
				AND xp.CHARTABLE_ID = 0";
		$sql = $wpdb->prepare($sql, $characterID);
		$xp = $wpdb->get_results($sql);
		//echo "SQL: $sql</p>";
		//print_r($xp);

		$result = array_merge($result, $freebies, $xp);
		$this->abilities = $result;
		$this->abilitygroups = array();
		for ($i=0;$i<count($result);$i++) {
			if (array_key_exists($result[$i]->grouping, $this->abilitygroups))
				array_push($this->abilitygroups[$result[$i]->grouping], $this->abilities[$i]);
			else {
				$this->abilitygroups[$result[$i]->grouping] = array($this->abilities[$i]);
			}
			
		}
		
		/* Backgrounds */
		$sql = "SELECT bground.name		     background,
					sectors.name		     sector,
					charbgnd.comment	     comment,
					IFNULL(freebie.LEVEL_TO,charbgnd.level) level,
					IFNULL(charbgnd.approved_detail,charbgnd.pending_detail) detail
				FROM
					" . VTM_TABLE_PREFIX . "BACKGROUND bground,
					" . VTM_TABLE_PREFIX . "CHARACTER chara,
					" . VTM_TABLE_PREFIX . "CHARACTER_BACKGROUND charbgnd
					LEFT JOIN (
						SELECT CHARTABLE_ID, LEVEL_TO
						FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
						WHERE
							ITEMTABLE = 'BACKGROUND'
							AND CHARACTER_ID = %s
					) freebie
					ON
						freebie.CHARTABLE_ID = charbgnd.ID
					LEFT JOIN 
						" . VTM_TABLE_PREFIX . "SECTOR sectors
					ON charbgnd.SECTOR_ID = sectors.ID
				WHERE
					charbgnd.CHARACTER_ID = chara.ID
					AND charbgnd.BACKGROUND_ID = bground.ID
					AND chara.id = '%s'
				ORDER BY bground.name ASC;";
		$sql = $wpdb->prepare($sql, $characterID, $characterID);
		$result = $wpdb->get_results($sql);
		$sql = "SELECT bground.NAME			background,
					''						sector,
					freebie.SPECIALISATION	comment,
					freebie.LEVEL_TO 		level,
					freebie.PENDING_DETAIL  detail
			FROM
				" . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND freebie,
				" . VTM_TABLE_PREFIX . "BACKGROUND bground
			WHERE
				freebie.CHARACTER_ID = %s
				AND bground.ID = freebie.ITEMTABLE_ID
				AND freebie.ITEMTABLE = 'BACKGROUND'
				AND freebie.CHARTABLE_ID = ''";
		$sql = $wpdb->prepare($sql, $characterID);
		//echo "<p>SQL: $sql</p>";
		$freebies = $wpdb->get_results($sql);
		
		$this->backgrounds = array_merge($result, $freebies);
		
		/* Disciplines */
		// Disciplines from table with freebie points and pending xp
		$sql = "SELECT disciplines.NAME		name,
					IFNULL(freebie.LEVEL_TO,chardisc.level) level,
					xp.CHARTABLE_LEVEL      pending
				FROM
					" . VTM_TABLE_PREFIX . "DISCIPLINE disciplines,
					" . VTM_TABLE_PREFIX . "CHARACTER_DISCIPLINE chardisc
					LEFT JOIN (
						SELECT CHARTABLE_ID, LEVEL_TO
						FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
						WHERE
							ITEMTABLE = 'DISCIPLINE'
							AND CHARACTER_ID = %s
					) freebie
					ON
						freebie.CHARTABLE_ID = chardisc.ID
					LEFT JOIN (
						SELECT CHARTABLE_ID, CHARTABLE_LEVEL
						FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
						WHERE
							ITEMTABLE = 'DISCIPLINE'
							AND CHARTABLE = 'CHARACTER_DISCIPLINE'
							AND CHARACTER_ID = %s
					) xp
					ON
						xp.CHARTABLE_ID = chardisc.ID,
					" . VTM_TABLE_PREFIX . "CHARACTER chara
				WHERE
					chardisc.DISCIPLINE_ID = disciplines.ID
					AND chardisc.CHARACTER_ID = chara.ID
					AND chara.id = '%s'
				ORDER BY disciplines.name ASC;";
		$sql = $wpdb->prepare($sql, $characterID, $characterID, $characterID);
		$result = $wpdb->get_results($sql);
		// Disciplines from freebie points with pending xp
		$sql = "SELECT disciplines.NAME		name,
					freebie.LEVEL_TO 		level,
					xp.CHARTABLE_LEVEL      pending
			FROM
				" . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND freebie
					LEFT JOIN (
						SELECT CHARTABLE_ID, CHARTABLE_LEVEL, SPECIALISATION
						FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
						WHERE
							CHARTABLE = 'PENDING_FREEBIE_SPEND'
							AND ITEMTABLE = 'DISCIPLINE'
							AND CHARACTER_ID = %s
					) xp
					ON
						xp.CHARTABLE_ID = freebie.ID,
				" . VTM_TABLE_PREFIX . "DISCIPLINE disciplines
			WHERE
				freebie.CHARACTER_ID = %s
				AND disciplines.ID = freebie.ITEMTABLE_ID
				AND freebie.ITEMTABLE = 'DISCIPLINE'
				AND freebie.CHARTABLE_ID = ''";
		$sql = $wpdb->prepare($sql, $characterID, $characterID);
		$freebies = $wpdb->get_results($sql);
		// pending xp for new
		$sql = "SELECT disciplines.NAME		name,
					0 						level,
					xp.CHARTABLE_LEVEL      pending
			FROM
				" . VTM_TABLE_PREFIX . "PENDING_XP_SPEND xp,
				" . VTM_TABLE_PREFIX . "DISCIPLINE disciplines
			WHERE
				xp.CHARACTER_ID = %s
				AND disciplines.ID = xp.ITEMTABLE_ID
				AND xp.ITEMTABLE = 'DISCIPLINE'
				AND xp.CHARTABLE_ID = 0";
		$sql = $wpdb->prepare($sql, $characterID);
		$xp = $wpdb->get_results($sql);

		$this->disciplines = array_merge($result, $freebies, $xp);

		/* Majik Paths */
		// Paths from tabel with freebie points and pending xp
		$sql = "SELECT paths.NAME           name,
					disciplines.NAME		discipline,
					IFNULL(freebie.LEVEL_TO,charpath.level)		level,
					xp.CHARTABLE_LEVEL      pending
				FROM
					" . VTM_TABLE_PREFIX . "DISCIPLINE disciplines,
					" . VTM_TABLE_PREFIX . "CHARACTER_PATH charpath
					LEFT JOIN (
						SELECT CHARTABLE_ID, LEVEL_TO
						FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
						WHERE
							CHARTABLE = 'CHARACTER_PATH'
							AND CHARACTER_ID = %s
					) freebie
					ON
						freebie.CHARTABLE_ID = charpath.ID
					LEFT JOIN (
						SELECT CHARTABLE_ID, CHARTABLE_LEVEL
						FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
						WHERE
							ITEMTABLE = 'PATH'
							AND CHARTABLE = 'CHARACTER_PATH'
							AND CHARACTER_ID = %s
					) xp
					ON
						xp.CHARTABLE_ID = charpath.ID,
					" . VTM_TABLE_PREFIX . "PATH paths,
					" . VTM_TABLE_PREFIX . "CHARACTER chara
				WHERE
					charpath.PATH_ID = paths.ID
					AND paths.DISCIPLINE_ID = disciplines.ID
					AND charpath.CHARACTER_ID = chara.ID
					AND chara.id = '%s'
				ORDER BY disciplines.name ASC, paths.NAME;";
		$sql = $wpdb->prepare($sql, $characterID, $characterID, $characterID);
		$result = $wpdb->get_results($sql);
		//echo "<p>SQL: $sql</p>";
		//print_r($result);
		// Disciplines from freebie points with pending xp
		$sql = "SELECT paths.NAME		name,
					disciplines.NAME	discipline,
					freebie.LEVEL_TO 	level,
					xp.CHARTABLE_LEVEL  pending
			FROM
				" . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND freebie
					LEFT JOIN (
						SELECT CHARTABLE_ID, CHARTABLE_LEVEL, SPECIALISATION
						FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
						WHERE
							CHARTABLE = 'PENDING_FREEBIE_SPEND'
							AND ITEMTABLE = 'PATH'
							AND CHARACTER_ID = %s
					) xp
					ON
						xp.CHARTABLE_ID = freebie.ID,
				" . VTM_TABLE_PREFIX . "DISCIPLINE disciplines,
				" . VTM_TABLE_PREFIX . "PATH paths
			WHERE
				freebie.CHARACTER_ID = %s
				AND paths.DISCIPLINE_ID = disciplines.ID
				AND paths.ID = freebie.ITEMTABLE_ID
				AND freebie.ITEMTABLE = 'PATH'
				AND freebie.CHARTABLE_ID = ''";
		$sql = $wpdb->prepare($sql, $characterID, $characterID);
		$freebies = $wpdb->get_results($sql);
		//echo "<p>SQL: $sql</p>";
		//print_r($freebies);
		// pending xp for new
		$sql = "SELECT paths.NAME		name,
					disciplines.NAME	discipline,
					0 						level,
					xp.CHARTABLE_LEVEL      pending
			FROM
				" . VTM_TABLE_PREFIX . "PENDING_XP_SPEND xp,
				" . VTM_TABLE_PREFIX . "PATH paths,
				" . VTM_TABLE_PREFIX . "DISCIPLINE disciplines
			WHERE
				xp.CHARACTER_ID = %s
				AND paths.ID = xp.ITEMTABLE_ID
				AND paths.DISCIPLINE_ID = disciplines.ID
				AND xp.ITEMTABLE = 'PATH'
				AND xp.CHARTABLE_ID = 0";
		$sql = $wpdb->prepare($sql, $characterID);
		$xp = $wpdb->get_results($sql);
		
		$merged = array_merge($result, $freebies, $xp);
		
		// Reformat:
		//	[discipline] = ( [name] = (level, pending) )
		$this->paths = array();
		foreach ($merged as $majikpath) {
			$this->paths[$majikpath->discipline][$majikpath->name] = array($majikpath->level, $majikpath->pending);
		}
		//print_r($this->paths);
		
		/* Merits and Flaws */
		$sql = "(SELECT merits.NAME		      name,
					charmerit.comment	      comment,
					charmerit.level		      level,
					0						  pending,
					IFNULL(charmerit.approved_detail,charmerit.pending_detail) detail
				FROM
					" . VTM_TABLE_PREFIX . "MERIT merits,
					" . VTM_TABLE_PREFIX . "CHARACTER_MERIT charmerit,
					" . VTM_TABLE_PREFIX . "CHARACTER chara
				WHERE
					charmerit.MERIT_ID = merits.ID
					AND charmerit.CHARACTER_ID = chara.ID
					AND chara.id = '%s'
				ORDER BY merits.name ASC)
				UNION
				(SELECT merits.NAME			name,
					freebie.SPECIALISATION	comment,
					freebie.LEVEL_TO		level,
					0						pending,
					freebie.PENDING_DETAIL	detail
				FROM
					" . VTM_TABLE_PREFIX . "MERIT merits,
					" . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND freebie
				WHERE
					freebie.CHARACTER_ID = %s
					AND freebie.ITEMTABLE = 'MERIT'
					AND freebie.ITEMTABLE_ID = merits.ID)
				UNION
				(SELECT merits.NAME			name,
					xp.SPECIALISATION	    comment,
					xp.CHARTABLE_LEVEL		level,
					xp.CHARTABLE_LEVEL		pending,
					''						detail
				FROM
					" . VTM_TABLE_PREFIX . "MERIT merits,
					" . VTM_TABLE_PREFIX . "PENDING_XP_SPEND xp
				WHERE
					xp.CHARACTER_ID = %s
					AND xp.ITEMTABLE = 'MERIT'
					AND xp.ITEMTABLE_ID = merits.ID)";
		$sql = $wpdb->prepare($sql, $characterID, $characterID, $characterID);
		$result = $wpdb->get_results($sql);
		$this->meritsandflaws = $result;

		/* Full Willpower */
		$sql = "SELECT 
					IFNULL(freebie.LEVEL_TO,charstat.level) as level,
					xp.CHARTABLE_LEVEL as pending
				FROM " . VTM_TABLE_PREFIX . "CHARACTER_STAT charstat
					LEFT JOIN (
						SELECT CHARTABLE_ID, LEVEL_TO
						FROM " . VTM_TABLE_PREFIX . "PENDING_FREEBIE_SPEND
						WHERE
							ITEMTABLE = 'STAT'
							AND CHARACTER_ID = %s
					) freebie
					ON
						freebie.CHARTABLE_ID = charstat.ID
					LEFT JOIN (
						SELECT CHARTABLE_ID, CHARTABLE_LEVEL
						FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND
						WHERE
							ITEMTABLE = 'STAT'
							AND CHARTABLE = 'CHARACTER_STAT'
							AND CHARACTER_ID = %s
					) xp
					ON
						xp.CHARTABLE_ID = charstat.ID,
					" . VTM_TABLE_PREFIX . "STAT stat
				WHERE 
					charstat.CHARACTER_ID = '%s' 
					AND charstat.STAT_ID = stat.ID
					AND stat.name = 'Willpower';";
		$sql = $wpdb->prepare($sql, $characterID, $characterID, $characterID);
		//echo "<p>SQL: $sql</p>";
		$result = $wpdb->get_row($sql);
		$this->willpower         = isset($result->level) ? $result->level : 0;
		$this->pending_willpower = isset($result->pending) ? $result->pending : 0;
		
		/* Current Willpower */
        $sql = "SELECT SUM(char_temp_stat.amount) currentwp
                FROM " . VTM_TABLE_PREFIX . "CHARACTER_TEMPORARY_STAT char_temp_stat,
                     " . VTM_TABLE_PREFIX . "TEMPORARY_STAT tstat
                WHERE char_temp_stat.character_id = '%s'
					AND char_temp_stat.temporary_stat_id = tstat.id
					AND tstat.name = 'Willpower';";
		$sql = $wpdb->prepare($sql, $characterID);
		$result = $wpdb->get_var($sql);
		$this->current_willpower = isset($result) ? $result : 0;
		
		/* Humanity */
		$sql = "SELECT SUM(cpath.AMOUNT) path_rating
				FROM " . VTM_TABLE_PREFIX . "CHARACTER_ROAD_OR_PATH cpath
				WHERE cpath.CHARACTER_ID = %s;";	
		$sql = $wpdb->prepare($sql, $characterID);
		$result = $wpdb->get_var($sql);
		$sql = "SELECT ROAD_OR_PATH_RATING FROM " . VTM_TABLE_PREFIX . "CHARACTER WHERE ID = %s";
		$sql = $wpdb->prepare($sql, $characterID);
		$default = $wpdb->get_var($sql);
		//echo "<p>SQL: $sql ($result / $default)</p>";
		$this->path_rating = isset($result) && $result > 0 ? $result : $default;
		
		/* Rituals */
		$sql = "(SELECT disciplines.name as discname, rituals.name as ritualname, rituals.level,
					rituals.description, rituals.dice_pool, rituals.difficulty,
					0 as pending
				FROM " . VTM_TABLE_PREFIX . "DISCIPLINE disciplines,
                    " . VTM_TABLE_PREFIX . "CHARACTER_RITUAL char_rit,
                    " . VTM_TABLE_PREFIX . "RITUAL rituals
				WHERE
					char_rit.CHARACTER_ID = '%s'
					AND char_rit.RITUAL_ID = rituals.ID
					AND rituals.DISCIPLINE_ID = disciplines.ID
				ORDER BY disciplines.name, rituals.level, rituals.name)
				UNION
				(SELECT disciplines.name as discname, rituals.name as ritualname, rituals.level,
					rituals.description, rituals.dice_pool, rituals.difficulty,
					rituals.level as pending
				FROM " . VTM_TABLE_PREFIX . "DISCIPLINE disciplines,
                    " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND xp,
                    " . VTM_TABLE_PREFIX . "RITUAL rituals
				WHERE
					xp.CHARACTER_ID = %s
					AND xp.ITEMTABLE = 'RITUAL'
					AND xp.ITEMTABLE_ID = rituals.id
					AND rituals.DISCIPLINE_ID = disciplines.ID)";
		$sql = $wpdb->prepare($sql, $characterID, $characterID);
		$result = $wpdb->get_results($sql);
		$i = 0;
		foreach ($result as $ritual) {
			$this->rituals[$ritual->discname][$i] = array(
				'name' => $ritual->ritualname, 
				'level' => $ritual->level,
				'roll'  => $ritual->dice_pool . ", diff " . $ritual->difficulty,
				'description' => $ritual->description,
				'pending' => $ritual->pending
			);
			$i++;
		}
		
		/* Combo disciplines */
		$sql = "(SELECT combo.name, 0 as pending
				FROM
					" . VTM_TABLE_PREFIX . "CHARACTER_COMBO_DISCIPLINE charcombo,
					" . VTM_TABLE_PREFIX . "COMBO_DISCIPLINE combo
				WHERE
					charcombo.COMBO_DISCIPLINE_ID = combo.ID
					AND charcombo.CHARACTER_ID = '%s'
				ORDER BY combo.name)
				UNION
				(SELECT combo.name, 1 as pending
				FROM
					" . VTM_TABLE_PREFIX . "PENDING_XP_SPEND xp,
					" . VTM_TABLE_PREFIX . "COMBO_DISCIPLINE combo
				WHERE
					xp.CHARACTER_ID = '%s'
					AND xp.ITEMTABLE = 'COMBO_DISCIPLINE'
					AND xp.ITEMTABLE_ID = combo.id
				ORDER BY combo.name)";
		$sql = $wpdb->prepare($sql, $characterID, $characterID);
		$result = $wpdb->get_results($sql);
		//print_r($result);
		//echo "<p>SQL: $sql</p>";
		$this->combo_disciplines = array();
		for ($i=0;$i<count($result);$i++) {	
			$name = $result[$i]->pending ? $result[$i]->name . " - PENDING" : $result[$i]->name;
			$this->combo_disciplines[$i] = $name;
		}
		
		/* Current Experience */
		$this->current_experience = vtm_get_total_xp($this->player_id, $characterID);
		$this->pending_experience = vtm_get_pending_xp($this->player_id, $characterID);
		$this->spent_experience  = $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM " . VTM_TABLE_PREFIX . "PLAYER_XP WHERE CHARACTER_ID = '%s' AND amount < 0", $characterID)) * -1;
		$this->spent_experience += $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM " . VTM_TABLE_PREFIX . "PENDING_XP_SPEND WHERE CHARACTER_ID = '%s'", $characterID)) * -1;
		
		// Offices / Positions
		$sql = "SELECT offices.name, offices.visible, domains.name as domain
				FROM
					" . VTM_TABLE_PREFIX . "CHARACTER_OFFICE charoffice,
					" . VTM_TABLE_PREFIX . "OFFICE offices,
					" . VTM_TABLE_PREFIX . "DOMAIN domains
				WHERE	
					charoffice.OFFICE_ID = offices.ID
					AND charoffice.DOMAIN_ID = domains.ID
					AND charoffice.CHARACTER_ID = '%s'
				ORDER BY offices.ORDERING";
		$sql = $wpdb->prepare($sql, $characterID);
		$this->offices = $wpdb->get_results($sql);
		
		// History
		$sql = "SELECT 
					eb.title				as title,
					eb.BACKGROUND_QUESTION	as question,
					IF(ceb.APPROVED_DETAIL = '',ceb.PENDING_DETAIL, ceb.APPROVED_DETAIL) as detail
				FROM
					" . VTM_TABLE_PREFIX . "CHARACTER_EXTENDED_BACKGROUND ceb,
					" . VTM_TABLE_PREFIX . "EXTENDED_BACKGROUND eb
				WHERE
					CHARACTER_ID = %s
					AND eb.ID = ceb.QUESTION_ID
					AND eb.VISIBLE = 'Y'
				ORDER BY eb.ORDERING";
		$sql = $wpdb->prepare($sql, $characterID);
		$this->history = $wpdb->get_results($sql);
		
	}
	function getAttributes($group = "") {
		$result = array();
		if ($group == "")
			return $this->attributes;
		elseif (isset($this->attributegroups[$group]))
			return $this->attributegroups[$group];
		else
			return array();
	}
	function getAbilities($group = "") {
		$result = array();
		if ($group == "")
			return $this->abilities;
		elseif (isset($this->abilitygroups[$group]))
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


class vtmclass_MultiPage_ListTable extends WP_List_Table {
      
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
   
	/* Need own version of this function vtm_to deal with tabs */
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

class vtmclass_Report_ListTable extends WP_List_Table {

	var $pagewidth;
	var $lineheight;
	var $columnstartX;		// array of X values where table columns start
	var $dotable = false;	// outputting table?
	var $ytop_page;
	var $ytop_cell;
	var $ytop_data;
	var $ybottom_page;
	var $row = 0;
      
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
		
		$sql = "SELECT ID FROM " . VTM_TABLE_PREFIX. "PLAYER_STATUS WHERE NAME = %s";
		$result = $wpdb->get_results($wpdb->prepare($sql,'Active'));
		$default_player_status = $result[0]->ID;
		
		$sql = "SELECT ID FROM " . VTM_TABLE_PREFIX. "CHARACTER_TYPE WHERE NAME = %s";
		$result = $wpdb->get_results($wpdb->prepare($sql,'PC'));
		$default_character_type    = $result[0]->ID;
		
		$sql = "SELECT ID FROM " . VTM_TABLE_PREFIX. "CHARACTER_STATUS WHERE NAME = %s";
		$result = $wpdb->get_results($wpdb->prepare($sql,'Alive'));
		$default_character_status  = $result[0]->ID;
		
		/* get filter options */
		$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX. "PLAYER_STATUS";
		$this->filter_player_status = vtm_make_filter($wpdb->get_results($sql));
		
		$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX. "CHARACTER_TYPE";
		$this->filter_character_type = vtm_make_filter($wpdb->get_results($sql));
		
		$sql = "SELECT ID, NAME FROM " . VTM_TABLE_PREFIX. "CHARACTER_STATUS";
		$this->filter_character_status = vtm_make_filter($wpdb->get_results($sql));		
		
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
	
	function get_filter_sql() {
	
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

	function filter_tablenav() {
			echo "<label>Player Status: </label>";
			if ( !empty( $this->filter_player_status ) ) {
				echo "<select name='player_status'>";
				foreach( $this->filter_player_status as $key => $value ) {
					echo '<option value="' . esc_attr( $key ) . '" ';
					selected( $this->active_filter_player_status, $key );
					echo '>' . esc_attr( $value ) . '</option>';
				}
				echo '</select>';
			}
			
			echo "<label>Character Type: </label>";
			if ( !empty( $this->filter_character_type ) ) {
				echo "<select name='character_type'>";
				foreach( $this->filter_character_type as $key => $value ) {
					echo '<option value="' . esc_attr( $key ) . '" ';
					selected( $this->active_filter_character_type, $key );
					echo '>' . esc_attr( $value ) . '</option>';
				}
				echo '</select>';
			}
			
			echo "<label>Character Status: </label>";
			if ( !empty( $this->filter_character_status ) ) {
				echo "<select name='character_status'>";
				foreach( $this->filter_character_status as $key => $value ) {
					echo '<option value="' . esc_attr( $key ) . '" ';
					selected( $this->active_filter_character_status, $key );
					echo '>' . esc_attr( $value ) . '</option>';
				}
				echo '</select>';
			}
			
			echo "<label>Character Visibility: </label>";
			echo "<select name='character_visible'>";
			echo '<option value="all" ';
					selected( $this->active_filter_character_visible, 'all' );
					echo '>All</option>';
			echo '<option value="Y" ';
					selected( $this->active_filter_character_visible, 'Y' );
					echo '>Yes</option>';
			echo '<option value="N" ';
					selected( $this->active_filter_character_visible, 'N' );
					echo '>No</option>';
			echo '</select>';
			
			submit_button( 'Filter', 'secondary', 'do_filter_tablenav', false );
			echo "<label>Download: </label>";
			echo "<a class='button-primary' href='" . plugins_url( 'vtm-character/tmp/report.pdf') . "'>PDF</a>";
			echo "<a class='button-primary' href='" . plugins_url( 'vtm-character/tmp/report.csv') . "'>CSV</a>";
	}
	 
	function extra_tablenav($which) {
		if ($which == 'top')  {
			echo "<div class='gvfilter'>";
			$this->filter_tablenav();
		
			echo "</div>";
		}
	}
	
	/* Add Headings function vtm_to add report name to sort url */
       function print_column_headers( $with_id = true ) {	
			list( $columns, $hidden, $sortable ) = $this->get_column_info();

			$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			$current_url = remove_query_arg( 'paged', $current_url );
			if (isset($_REQUEST['report']))
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
	function get_column_names($columns = "") {
		$colnames = array();
		$col = 0;
		if (!empty($columns))
			foreach ($columns as $column => $coldesc) {
				$colnames[$col] = $column;
				$col++;
			}
		return $colnames;
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
 
	
	function output_report ($title, $orientation = 'L') {
		
		$pdf = new vtmclass_PDFreport($orientation,'mm','A4');
		
		if ($orientation == 'L') $pdf->pagewidth = 297;
		if ($orientation == 'P') $pdf->pagewidth = 210;
		
		$pdf->title = $title;
		$pdf->SetTitle($title);
		$pdf->AliasNbPages();
		$pdf->SetMargins(5, 5, 5);
		$pdf->AddPage();
		
		$this->ytop_page = $pdf->GetY();
		$pdf->SetY(-15);
		$this->ybottom_page = $pdf->GetY();
		$pdf->SetY($this->ytop_page);
		
		$columns = $this->get_columns();
		$this->pagewidth = $pdf->pagewidth;
		$colwidths  = $this->set_column_widths($columns);
		$colalign   = $this->set_column_alignment($columns);
		$colnames   = $this->get_column_names($columns);
		$lineheight = isset($this->lineheight) ? $this->lineheight : 5;
		
		$pdf->SetFont('Arial','B',9);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetFillColor(255,0,0);
		
		$pdf->autobreak = false;
		$col = 0;
		foreach ($columns as $columnname => $columndesc) {
			$this->columnstartX[$col] = $pdf->GetX();
			$pdf->Cell($colwidths[$columnname],$lineheight,$columndesc,1,0,'C',1);
			$col++;
		}
		$pdf->Ln();
		
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFillColor(200);
		$pdf->SetFont('Arial','',9);
		$row = 0;
		
		if (count($this->items) > 0) {
				
			// get row heights
			$rowheights = array();
			$row = 0;
			foreach ($this->items as $datarow) {
				$rowheight = 0;
				foreach ($columns as $columnname => $columndesc) {
					$raw = isset($datarow->$columnname) ? $datarow->$columnname : '';
					$text = $pdf->PrepareText($raw);
					$cellheight = $pdf->GetCellHeight($text, $colwidths[$columnname], $lineheight);
					if ($cellheight > $rowheight) $rowheight = $cellheight;
				}
				$rowheights[$row] = $rowheight;
				$row++;
			}
			
			// Print page-by-page
			//		And column-by-column
			//			And row-by-row
			$pagestartrow = 0;
			$pageendrow   = count($this->items) - 1;
			$tableendrow  = count($this->items) - 1;
			$this->row = $pagestartrow;
			$pdf->col = 0;
			$pdf->tablecols = count($columns);
			$this->ytop_data = $pdf->GetY();
			
			$ybottomcell = 0;
		
			
			while ($this->row <= $tableendrow) {
				$datarow = $this->items[$this->row];
				$columnname = $colnames[$pdf->col];
				$raw = isset($datarow->$columnname) ? $datarow->$columnname : '';
				$text    = $pdf->PrepareText($raw);
				
				if ($pdf->col == 0)
					$this->ytop_cell = $pdf->GetY();
				
				$cellheight = $pdf->GetCellHeight($text, $colwidths[$columnname], $lineheight);
				$rowheight = $rowheights[$this->row];
				
				if ($cellheight == $rowheight)
					$h = $lineheight;
				elseif ($cellheight = $lineheight)
					$h = $rowheight;
				else
					$h = $rowheight / $cellheight;
				
				//$text .= $pdf->tablecols;
				if ( ($this->ytop_cell + $rowheight) > $this->ybottom_page && $pdf->col == 0) {
					$this->ytop_cell = $this->ytop_page;
					$pdf->AddPage();
				}
				
				$pdf->SetX($this->columnstartX[$pdf->col]);
				$pdf->MultiCell($colwidths[$columnname],$h,$text,1,$colalign[$columnname], $this->row % 2);
				$ybottomcell =  $pdf->GetY();
				
				if ($pdf->col < $pdf->tablecols - 1) {
					//if ($pdf->col == 1) {$this->row = $tableendrow+1;}
					$pdf->col = $pdf->col+1;
					$pdf->SetY($this->ytop_cell);
				} else {
					$pdf->col = 0;
					$pdf->SetX($this->columnstartX[$pdf->col]);
					$this->row++;
					
				}
				
			}
			
		
			/* $this->ytop_data = $pdf->GetY();
			// output table, column by column
			foreach ($columns as $columnname => $columndesc) {
				$row = 0;
				foreach ($this->items as $datarow) {
				
					$text = $pdf->PrepareText($datarow->$columnname);
					
					$cellheight = $rowheights[$row];
					if ($cellheight == $rowheight)
						$h = $lineheight;
					elseif ($cellheight = $lineheight)
						$h = $rowheight;
					else
						$h = $rowheight / $cellheight;
					
					$pdf->MultiCell($colwidths[$columnname],$h,$text,1,$colalign[$columnname], $row % 2);
					$row++;
				}
				
				$pdf->SetY($this->ytop_data);
			}
			*/
		
		} 
		$pdf->autobreak = false;
		
		$pdf->Output(VTM_CHARACTER_URL . 'tmp/report.pdf', 'F');
		
	}
	
	function output_csv() {
		
		/* open file */
		$file = fopen(VTM_CHARACTER_URL . "tmp/report.csv","w");
		
		/* write headings */
		$columns = $this->get_columns();
		fputcsv($file, array_values($columns));
		
		/* write data */
		if (count($this->items) > 0) {
			foreach ($this->items as $datarow) {
				$data = array();
				foreach ($columns as $columnname => $columndesc) {
					$raw = isset($datarow->$columnname) ? $datarow->$columnname : '';
					array_push($data, $this->PrepareCSVText($raw));
				}
				fputcsv($file, $data);
			}
		}
		
		/* close file */
		fclose($file);
	}
	
	function PrepareCSVText($text) {
		
		$text = stripslashes($text);
		$text = str_ireplace("\r", "", $text);
		
		/* remove extra whitespace and trailing newlines */
		$text = trim($text);
	
		return $text;
	}

}

/* 
-----------------------------------------------
PRINT REPORT
------------------------------------------------ */
class vtmclass_PDFreport extends FPDF {

	var $title;
	var $pagewidth = 297;
	var $col = 0;
	var $tablecols = 0;
	var $autobreak = true;

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
	
	function GetCellHeight($text, $cellwidth, $lineheight) {
		
		$lines = ceil( $this->GetStringWidth($text) / ($cellwidth - 1) );
		
		$height = ceil($lineheight * $lines);
		
		/* plus anything from extra newlines */
		$height = $height + ($lineheight * substr_count($text, "\n"));
		
		return $height;
	}

	function PrepareText($text) {
		
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
	
	function AcceptPageBreak()
	{
		if ($this->autobreak)
			return true;
			
		// Method accepting or not automatic page break
		if($this->col < $this->tablecols)
		{
			// Keep on page
			return false;
		}
		else
		{
			return true;
		}
	} 

}



?>