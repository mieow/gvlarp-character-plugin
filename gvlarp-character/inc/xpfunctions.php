<?php

/* 
called by addPlayerXP
*/

 function doXPSpend($character, $xpSpendString, $xpBonus, $specialisation) {
        if (!isST()) {
            return "Only STs can spend XP on characters";
        }

        if ($xpBonus == null || $xpBonus == "") {
            $xpBonus = 0;
        }

        $character   = establishCharacter($character);
        $characterID = establishCharacterID($character);
        $playerID    = establishPlayerID($character);
        $xpReasonID  = establishXPReasonID("XP Spend");
        $tokens = explode("_", $xpSpendString);
        $attributes = array ("character" => $character, "group" => "TOTAL", maxrecords => "1");
        $xp_total = print_character_xp_table($attributes, null);

        global $wpdb;
        $table_prefix = GVLARP_TABLE_PREFIX;

        $checkSQL = "";
        $updateTraitSQL = "";
        $xp_cost = 1000;
        $traitName = "";
        $newValue     = "";

        $config = getConfig();
        $clanDisciplineDiscount = $config->CLAN_DISCIPLINE_DISCOUNT;
        $factor =  0;
        if ($clanDisciplineDiscount == 0) {
            $offset = 0;
        }
        else if ($clanDisciplineDiscount < 1) {
            $offset = 0;
            $factor = $clanDisciplineDiscount;
        }
        else {
            $offset = $clanDisciplineDiscount;
        }

        $innerTable = "(SELECT dis.name dis_name, " . $offset . " xp_offset, " . $factor . " xp_factor, dis.id dis_id, dis.cost_model_id cmid, dis.visible dis_vis
                                FROM " . $table_prefix . "DISCIPLINE dis,
                                     " . $table_prefix . "CHARACTER chara,
                                     " . $table_prefix . "CLAN_DISCIPLINE clandis
                                WHERE chara.private_clan_id = clandis.CLAN_ID
                                  AND clandis.DISCIPLINE_id = dis.ID
                                  AND chara.wordpress_id = %s
                                union
                                SELECT dis.name dis_id, 0 xp_offset, 0 xp_factor, dis.id dis_id, dis.cost_model_id cmid, dis.visible dis_vis
                                FROM " . $table_prefix . "DISCIPLINE dis
                                WHERE dis.ID NOT IN (SELECT dis.ID
                                                     FROM " . $table_prefix . "DISCIPLINE dis,
                                                          " . $table_prefix . "CHARACTER chara,
                                                          " . $table_prefix . "CLAN_DISCIPLINE clandis
                                                     WHERE chara.private_clan_id = clandis.CLAN_ID
                                                       AND clandis.DISCIPLINE_id = dis.ID
                                                       AND chara.wordpress_id = %s)) new_dis ";

        if ($tokens [0] == "stat") {
            $checkSQL = "SELECT stat.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "STAT stat,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep,
                                      " . $table_prefix . "CHARACTER_STAT cstat
                                 WHERE stat.COST_MODEL_ID    = cmstep.COST_MODEL_ID
                                   AND cmstep.CURRENT_VALUE  = cstat.level
                                   AND cstat.STAT_ID         = stat.ID
                                   AND cstat.CHARACTER_ID    = %s
                                   AND cstat.id              = %d";

            $updateStats = $wpdb->get_results($wpdb->prepare($checkSQL, $characterID, $tokens [1]));
            foreach ($updateStats as $updateStat) {
                $xp_cost      = $updateStat->xp_cost;
                $traitName    = $updateStat->name;
                $currentValue = $updateStat->current_value;
                $newValue     = $updateStat->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "UPDATE " . $table_prefix . "CHARACTER_STAT
                                           SET level   = %d,
                                               comment = %s
                                           WHERE ID = %d";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $newValue, $specialisation, $tokens [1]);
            }
        }
        else if ($tokens [0] == "skill" && $tokens [1] == "new") {
            $checkSQL = "SELECT skill.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "SKILL skill,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep
                                 WHERE skill.COST_MODEL_ID   = cmstep.COST_MODEL_ID
                                   AND cmstep.current_value  = 0
                                   AND skill.id              = %d";

            $newSkills = $wpdb->get_results($wpdb->prepare($checkSQL, $tokens [2]));
            foreach ($newSkills as $newSkill) {
                $xp_cost      = $newSkill->xp_cost;
                $traitName    = $newSkill->name;
                $currentValue = $newSkill->current_value;
                $newValue     = $newSkill->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "INSERT INTO " . $table_prefix . "CHARACTER_SKILL (character_id, skill_id, level, comment)
                                           VALUES (%d, %d, %d, %s)";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $characterID, $tokens [2], $newValue, $specialisation);
            }
        }
        else if ($tokens [0] == "skill") {
            $checkSQL = "SELECT skill.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "SKILL skill,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep,
                                      " . $table_prefix . "CHARACTER_SKILL cskill
                                 WHERE skill.COST_MODEL_ID   = cmstep.COST_MODEL_ID
                                   AND cmstep.CURRENT_VALUE  = cskill.level
                                   AND cskill.SKILL_ID       = skill.ID
                                   AND cskill.CHARACTER_ID   = %d
                                   AND cskill.id             = %d";

            $updateSkills = $wpdb->get_results($wpdb->prepare($checkSQL, $characterID, $tokens [1]));
            foreach ($updateSkills as $updateSkill) {
                $xp_cost      = $updateSkill->xp_cost;
                $traitName    = $updateSkill->name;
                $currentValue = $updateSkill->current_value;
                $newValue     = $updateSkill->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "UPDATE " . $table_prefix . "CHARACTER_SKILL
                                           SET level   = %d,
                                               comment = %s
                                           WHERE ID    = %d";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $newValue, $specialisation, $tokens [1]);
            }
        }
        else if ($tokens [0] == "dis" && $tokens [1] == "new") {
            $checkSQL = "SELECT new_dis.dis_name name, ROUND((cmstep.xp_cost - new_dis.xp_offset) * (1 - new_dis.xp_factor)) xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $innerTable   . ",
                                      " . $table_prefix . "COST_MODEL_STEP cmstep
                                 WHERE new_dis.cmid          = cmstep.COST_MODEL_ID
                                   AND cmstep.current_value  = 0
                                   AND new_dis.dis_id        = %d";
			$checkSQL = $wpdb->prepare($checkSQL, $character, $character, $tokens [2]);
            $newDisciplines = $wpdb->get_results($checkSQL);
			print_r($newDisciplines);
            foreach ($newDisciplines as $newDiscipline) {
                $xp_cost      = $newDiscipline->xp_cost;
                $traitName    = $newDiscipline->name;
                $currentValue = $newDiscipline->current_value;
                $newValue     = $newDiscipline->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "INSERT INTO " . $table_prefix . "CHARACTER_DISCIPLINE (character_id, discipline_id, level)
                                           VALUES (%d, %d, %d)";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $characterID, $tokens [2], $newValue);
            }
        }
        else if ($tokens [0] == "dis") {
            $checkSQL = "SELECT new_dis.dis_name name, ROUND((cmstep.xp_cost - new_dis.xp_offset) * (1 - new_dis.xp_factor)) xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $innerTable . ",
                                      " . $table_prefix . "COST_MODEL_STEP cmstep,
                                      " . $table_prefix . "CHARACTER_DISCIPLINE cdis
                                 WHERE new_dis.cmid         = cmstep.COST_MODEL_ID
                                   AND cmstep.CURRENT_VALUE = cdis.level
                                   AND cdis.DISCIPLINE_ID   = dis_id
                                   AND cdis.CHARACTER_ID    = %d
                                   AND cdis.id              = %d";

            $updateDisciplines = $wpdb->get_results($wpdb->prepare($checkSQL, $character, $character, $characterID, $tokens [1]));
            foreach ($updateDisciplines as $updateDiscipline) {
                $xp_cost      = $updateDiscipline->xp_cost;
                $traitName    = $updateDiscipline->name;
                $currentValue = $updateDiscipline->current_value;
                $newValue     = $updateDiscipline->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "UPDATE " . $table_prefix . "CHARACTER_DISCIPLINE
                                           SET level = %d
                                           WHERE ID  = %d";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $newValue, $tokens [1]);
            }
        }
        else if ($tokens [0] == "path" && $tokens [1] == "new") {
            $checkSQL = "SELECT path.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "PATH path,
                                      " . $table_prefix . "CHARACTER_DISCIPLINE cha_dis,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep
                                 WHERE path.COST_MODEL_ID    = cmstep.COST_MODEL_ID
                                   AND cmstep.current_value  = 0
                                   AND cha_dis.DISCIPLINE_ID = path.discipline_id
                                   AND cha_dis.level        >= cmstep.next_value
                                   AND cha_dis.CHARACTER_ID  = %d
                                   AND path.id               = %d";

            $newPaths = $wpdb->get_results($wpdb->prepare($checkSQL, $characterID, $tokens [2]));
            foreach ($newPaths as $newPath) {
                $xp_cost      = $newPath->xp_cost;
                $traitName    = $newPath->name;
                $currentValue = $newPath->current_value;
                $newValue     = $newPath->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "INSERT INTO " . $table_prefix . "CHARACTER_PATH (character_id, path_id, level)
                                           VALUES (%d, %d, %d)";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $characterID, $tokens [2], $newValue);
            }

        }
        else if ($tokens [0] == "path") {
            $checkSQL = "SELECT path.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "PATH path,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep,
                                      " . $table_prefix . "CHARACTER_PATH cpath,
                                      " . $table_prefix . "CHARACTER_DISCIPLINE cdis
                                 WHERE path.COST_MODEL_ID   = cmstep.COST_MODEL_ID
                                   AND cmstep.CURRENT_VALUE = cpath.level
                                   AND cpath.PATH_ID        = path.ID
                                   AND cdis.CHARACTER_ID    = cpath.CHARACTER_ID
                                   AND cdis.DISCIPLINE_ID   = path.DISCIPLINE_ID
                                   AND cdis.level          >= cmstep.next_value
                                   AND cpath.CHARACTER_ID   = %d
                                   AND cpath.id             = %d";

            $updatePaths = $wpdb->get_results($wpdb->prepare($checkSQL, $characterID, $tokens [1]));
            foreach ($updatePaths as $updatePath) {
                $xp_cost      = $updatePath->xp_cost;
                $traitName    = $updatePath->name;
                $currentValue = $updatePath->current_value;
                $newValue     = $updatePath->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "UPDATE " . $table_prefix . "CHARACTER_PATH
                                           SET level = %d
                                           WHERE ID  = %d";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $newValue, $tokens [1]);
            }
        }
        else if ($tokens [0] == "ritual" && $tokens [1] == "new") {
            $checkSQL = "SELECT rit.name, rit.cost xp_cost,  0 current_value, rit.level next_value
                                 FROM " . $table_prefix . "RITUAL rit,
                                      " . $table_prefix . "CHARACTER_DISCIPLINE cha_dis
                                 WHERE cha_dis.DISCIPLINE_ID = rit.DISCIPLINE_ID
                                   AND cha_dis.level        >= rit.level
                                   AND cha_dis.CHARACTER_ID  = %d
                                   AND rit.id                = %d";

            $newRituals = $wpdb->get_results($wpdb->prepare($checkSQL, $characterID, ((int) $tokens [2])));
            foreach ($newRituals as $newRitual) {
                $xp_cost      = $newRitual->xp_cost;
                $traitName    = $newRitual->name;
                $currentValue = $newRitual->current_value;
                $newValue     = $newRitual->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "INSERT INTO " . $table_prefix . "CHARACTER_RITUAL (character_id, ritual_id, level)
                                           VALUES (%d, %d, %d)";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $characterID, $tokens [2], $newValue);
            }
        }
        else if ($tokens [0] == "merit" && $tokens [1] == "new") {
            $checkSQL = "SELECT merit.name, merit.xp_cost, 0 current_value, merit.value next_value
                             FROM " . $table_prefix . "MERIT merit
                             WHERE merit.id = %d ";
            $newMerits = $wpdb->get_results($wpdb->prepare($checkSQL, $tokens [2]));
            foreach ($newMerits as $newMerit) {
                $xp_cost      = $newMerit->xp_cost;
                $traitName    = $newMerit->name;
                $currentValue = $newMerit->current_value;
                $newValue     = $newMerit->next_value;
            }

            if ($newValue != "") {
                $updateTraitSQL = "INSERT INTO " . $table_prefix . "CHARACTER_MERIT (character_id, merit_id, level)
                                       VALUES (%d, %d, %d)";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $characterID, $tokens [2], $newValue);
            }
        }
        else if ($tokens [0] == "merit") {
            $checkSQL = "SELECT merit.name, merit.xp_cost, 'Afflicted' current_value, 'Removed' next_value
                             FROM " . $table_prefix . "MERIT merit,
                                  " . $table_prefix . "CHARACTER_MERIT cmerit
                             WHERE cmerit.merit_id = merit.id
                               AND cmerit.CHARACTER_ID = %d
                               AND cmerit.id           = %d ";
            $updateMerits = $wpdb->get_results($wpdb->prepare($checkSQL, $characterID, $tokens [1]));
            foreach ($updateMerits as $updateMerit) {
                $xp_cost      = $updateMerit->xp_cost;
                $traitName    = $updateMerit->name;
                $currentValue = "Afflicted";
                $newValue     = "Cured";
            }

            if ($newValue != "") {
                $updateTraitSQL = "DELETE FROM " . $table_prefix . "CHARACTER_MERIT
                                       WHERE CHARACTER_ID = %d
                                         AND ID           = %d";
                $updateTraitSQL = $wpdb->prepare($updateTraitSQL, $characterID, $tokens [1]);
            }
        }

        else {
            return "Illegal combination of characer (" . $character . ") and xpSpendString (" . $xpSpendString . ")";
        }

        if ($updateTraitSQL == "") {
            return "Could not find trait to update for character (" . $character . ") and xpSpendString (" . $xpSpendString . ") "
                . $newValue . "<p>" . $checkSQL . "</p>";
        }

        // Minus the XP Bonus because its the cost and hence a negative
        if ((((int) $xp_total) - $xpBonus - ((int) $xp_cost)) < 0) {
            return "Not enough XP (" . $xp_total . ") for update (" . $traitName . " " . $currentValue . " > " . $newValue . ") which costs (" . $xp_cost . ")";
        }
        $comment = $traitName . " " . $currentValue . " > " . $newValue;
        $updateXPSQL = "INSERT INTO " . $table_prefix . "PLAYER_XP (player_id,
                                                                            character_id,
                                                                            xp_reason_id,
                                                                            awarded,
                                                                            amount,
                                                                            comment)
                                VALUES (%d, %d, %d, SYSDATE(), %d, %s)";

        $negativeCost = $xp_cost * -1;
        $wpdb->query($wpdb->prepare($updateXPSQL, $playerID, $characterID, $xpReasonID, $negativeCost, $comment));


        $wpdb->query($updateTraitSQL);

        return "XP Spend (" . $comment . ") applied to " . $character . ", " . $xp_cost . " XP deducted.";
    }


/*
	Called by print_xp_spend_table
*/
    function doPendingXPSpend($character) {
        global $wpdb;
        $character   = establishCharacter($character);
        $characterID = establishCharacterID($character);
        $playerID    = establishPlayerID($character);
		
        $table_prefix = GVLARP_TABLE_PREFIX;
		
		/* Stats */
		if (isset($_REQUEST['stat_level'])) {
			$newid = save_to_pending('stat', 'CHARACTER_STAT', 'STAT', 'STAT_ID', $playerID, $characterID);
		}
		if (isset($_REQUEST['skill_level'])) {
			$newid = save_to_pending('skill', 'CHARACTER_SKILL', 'SKILL', 'SKILL_ID', $playerID, $characterID);
		}
		if (isset($_REQUEST['newskill_level'])) {
			$newid = save_to_pending('newskill', 'CHARACTER_SKILL', 'SKILL', 'SKILL_ID', $playerID, $characterID);
		}
		if (isset($_REQUEST['disc_level'])) {
			$newid = save_to_pending('disc', 'CHARACTER_DISCIPLINE', 'DISCIPLINE', 'DISCIPLINE_ID', $playerID, $characterID);
		}
		if (isset($_REQUEST['newdisc_level'])) {
			$newid = save_to_pending('newdisc', 'CHARACTER_DISCIPLINE', 'DISCIPLINE', 'DISCIPLINE_ID', $playerID, $characterID);
		}
		if (isset($_REQUEST['path_level'])) {
			$newid = save_to_pending('path', 'CHARACTER_PATH', 'PATH', 'PATH_ID', $playerID, $characterID);
		}

		/*
        $config = getConfig();
        $clanDisciplineDiscount = $config->CLAN_DISCIPLINE_DISCOUNT;
        $factor =  0;
        if ($clanDisciplineDiscount == 0) {
            $offset = 0;
        }
        else if ($clanDisciplineDiscount < 1) {
            $offset = 0;
            $factor = $clanDisciplineDiscount;
        }
        else {
            $offset = $clanDisciplineDiscount;
        }
		*/

		/*
        $innerTable = "(SELECT dis.name dis_name, " . $offset . " xp_offset, " . $factor . " xp_factor, dis.id dis_id, dis.cost_model_id cmid, dis.visible dis_vis
                                FROM " . $table_prefix . "DISCIPLINE dis,
                                     " . $table_prefix . "CHARACTER chara,
                                     " . $table_prefix . "CLAN_DISCIPLINE clandis
                                WHERE chara.private_clan_id = clandis.CLAN_ID
                                  AND clandis.DISCIPLINE_id = dis.ID
                                  AND chara.wordpress_id = %s
                                union
                                SELECT dis.name dis_id, 0 xp_offset, 0 xp_factor, dis.id dis_id, dis.cost_model_id cmid, dis.visible dis_vis
                                FROM " . $table_prefix . "DISCIPLINE dis
                                WHERE dis.ID NOT IN (SELECT dis.ID
                                                     FROM " . $table_prefix . "DISCIPLINE dis,
                                                          " . $table_prefix . "CHARACTER chara,
                                                          " . $table_prefix . "CLAN_DISCIPLINE clandis
                                                     WHERE chara.private_clan_id = clandis.CLAN_ID
                                                       AND clandis.DISCIPLINE_id = dis.ID
                                                       AND chara.wordpress_id = %s)) new_dis ";

        if ($tokens [0] == "stat") {
            $checkSQL = "SELECT stat.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "STAT stat,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep,
                                      " . $table_prefix . "CHARACTER_STAT cstat
                                 WHERE stat.COST_MODEL_ID    = cmstep.COST_MODEL_ID
                                   AND cmstep.CURRENT_VALUE  = cstat.level
                                   AND cstat.STAT_ID         = stat.ID
                                   AND cstat.CHARACTER_ID    = %d
                                   AND cstat.id              = %d";
            $checkSQL = $wpdb->prepare($checkSQL, $characterID, $tokens [1]);
        }
        else if ($tokens [0] == "skill" && $tokens [1] == "new") {
            $checkSQL = "SELECT skill.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "SKILL skill,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep
                                 WHERE skill.COST_MODEL_ID   = cmstep.COST_MODEL_ID
                                   AND cmstep.current_value  = 0
                                   AND skill.id              = %d";
            $checkSQL = $wpdb->prepare($checkSQL, $tokens [2]);
        }
        else if ($tokens [0] == "skill") {
            $checkSQL = "SELECT skill.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "SKILL skill,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep,
                                      " . $table_prefix . "CHARACTER_SKILL cskill
                                 WHERE skill.COST_MODEL_ID   = cmstep.COST_MODEL_ID
                                   AND cmstep.CURRENT_VALUE  = cskill.level
                                   AND cskill.SKILL_ID       = skill.ID
                                   AND cskill.CHARACTER_ID   = %d
                                   AND cskill.id             = %d";
            $checkSQL = $wpdb->prepare($checkSQL, $characterID, $tokens [1]);
        }
        else if ($tokens [0] == "dis" && $tokens [1] == "new") {
            $checkSQL = "SELECT new_dis.dis_name name, ROUND((cmstep.xp_cost - new_dis.xp_offset) * (1 - new_dis.xp_factor)) xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $innerTable   . ",
                                      " . $table_prefix . "COST_MODEL_STEP cmstep
                                 WHERE new_dis.cmid          = cmstep.COST_MODEL_ID
                                   AND cmstep.current_value  = 0
                                   AND new_dis.dis_id        = %d";
            $checkSQL = $wpdb->prepare($checkSQL, $character, $character, $tokens [2]);
        }
        else if ($tokens [0] == "dis") {
            $checkSQL = "SELECT new_dis.dis_name name, ROUND((cmstep.xp_cost - new_dis.xp_offset) * (1 - new_dis.xp_factor)) xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $innerTable . ",
                                      " . $table_prefix . "COST_MODEL_STEP cmstep,
                                      " . $table_prefix . "CHARACTER_DISCIPLINE cdis
                                 WHERE new_dis.cmid         = cmstep.COST_MODEL_ID
                                   AND cmstep.CURRENT_VALUE = cdis.level
                                   AND cdis.DISCIPLINE_ID   = dis_id
                                   AND cdis.CHARACTER_ID    = %d
                                   AND cdis.id              = %d";
            $checkSQL = $wpdb->prepare($checkSQL, $character, $character, $characterID, $tokens [1]);

        }
        else if ($tokens [0] == "path" && $tokens [1] == "new") {
            $checkSQL = "SELECT path.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "PATH path,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep
                                 WHERE path.COST_MODEL_ID   = cmstep.COST_MODEL_ID
                                   AND cmstep.current_value = 0
                                   AND path.id              = %d";
            $checkSQL = $wpdb->prepare($checkSQL, $tokens [2]);

        }
        else if ($tokens [0] == "path") {
            $checkSQL = "SELECT path.name, cmstep.xp_cost, cmstep.current_value, cmstep.next_value
                                 FROM " . $table_prefix . "PATH path,
                                      " . $table_prefix . "COST_MODEL_STEP cmstep,
                                      " . $table_prefix . "CHARACTER_PATH cpath,
                                      " . $table_prefix . "CHARACTER_DISCIPLINE cdis
                                 WHERE path.COST_MODEL_ID   = cmstep.COST_MODEL_ID
                                   AND cmstep.CURRENT_VALUE = cpath.level
                                   AND cpath.PATH_ID        = path.ID
                                   AND cdis.CHARACTER_ID    = cpath.CHARACTER_ID
                                   AND cdis.DISCIPLINE_ID   = path.DISCIPLINE_ID
                                   AND cdis.level          >= cmstep.next_value
                                   AND cpath.CHARACTER_ID   = %d
                                   AND cpath.id             = %d";
            $checkSQL = $wpdb->prepare($checkSQL, $characterID, $tokens [1]);

        }
        else if ($tokens [0] == "ritual" && $tokens [1] == "new") {
            $checkSQL = "SELECT rit.name, rit.cost xp_cost,  0 current_value, 'Learned' next_value
                                 FROM " . $table_prefix . "RITUAL rit
                                 WHERE rit.id = %d ";
            $checkSQL = $wpdb->prepare($checkSQL, $tokens [2]);
        }
        else if ($tokens [0] == "merit" && $tokens [1] == "new") {
            $checkSQL = "SELECT merit.name, merit.xp_cost, 0 current_value, 'Acquired' next_value
                             FROM " . $table_prefix . "MERIT merit
                             WHERE merit.id = %d ";
            $checkSQL = $wpdb->prepare($checkSQL, $tokens [2]);
        }
        else if ($tokens [0] == "merit") {
            $checkSQL = "SELECT merit.name, merit.xp_cost, 'Afflicted' current_value, 'Removed' next_value
                             FROM " . $table_prefix . "MERIT merit,
                                  " . $table_prefix . "CHARACTER_MERIT cmerit
                             WHERE cmerit.merit_id = merit.id
                               AND cmerit.CHARACTER_ID = %d
                               AND cmerit.id           = %d ";
            $checkSQL = $wpdb->prepare($checkSQL, $characterID, $tokens [1]);
        }
        else {
            return "Illegal combination of characer (" . $character . ") and xpSpendString (" . $xpSpendString . ")";
        }

        $xpSpendDetails = $wpdb->get_results($checkSQL);
        foreach ($xpSpendDetails as $xpSpend) {
            $xp_cost      = $xpSpend->xp_cost;
            $traitName    = $xpSpend->name;
            $currentValue = $xpSpend->current_value;
            $newValue     = $xpSpend->next_value;
        }

        if ($traitName == "") {
            return "Could not find trait to update for characer (" . $character . ") and xpSpendString (" . $xpSpendString . ") "
                . $newValue . "<p>" . $checkSQL . "</p>";
        }

        if ((((int) $xp_total) - ((int) $xp_cost)) < 0) {
            return "Not enough XP (" . $xp_total . ") for update (" . $traitName . " " . $currentValue . " > " . $newValue . ") which costs (" . $xp_cost . ")";
        }

        $comment = $traitName . " " . $currentValue . " > " . $newValue;
        $duplicateSQL = "SELECT id
                                 FROM " . $table_prefix . "PENDING_XP_SPEND
                                 WHERE character_id = %d
                                   AND comment      = %s
                                   AND code         = %s";

        $duplicateID = "";
        $duplicates = $wpdb->get_results($wpdb->prepare($duplicateSQL, $characterID, $comment, $xpSpendString));
        foreach ($duplicates as $duplicate) {
            $duplicateID = $duplicate->id;
        }

        if ($duplicateID != "") {
            return "A duplicate pending xp Spend (" . $comment . ") for " . $character . " is already pending.";
        }

        $duplicateSkill = "";
        if ($tokens [0] == "skill" && $tokens [2] == "spec") {
            $duplicateSQL = "SELECT skill.name, cskill_outer.comment
                                 FROM " . $table_prefix . "CHARACTER_SKILL cskill_inner,
                                      " . $table_prefix . "CHARACTER_SKILL cskill_outer,
                                      " . $table_prefix . "SKILL skill
                                 WHERE cskill_inner.CHARACTER_ID   = %d
                                   AND cskill_inner.id             = %d
                                   AND cskill_outer.skill_id       = cskill_inner.skill_id
                                   AND cskill_inner.skill_id       = skill.id
                                   AND cskill_outer.CHARACTER_ID   = cskill_inner.CHARACTER_ID";
            $duplicates = $wpdb->get_results($wpdb->prepare($duplicateSQL, $characterID, $tokens [1]));

            foreach ($duplicates as $duplicate) {
                if ($duplicate->comment == $specialisation) {
                    $duplicateSkill = $duplicates->name;
                }
            }
        }
        else if ($tokens [0] == "skill" && $tokens [3] == "spec") {
            $duplicateSQL = "SELECT skill.name, cskill.comment
                                 FROM " . $table_prefix . "CHARACTER_SKILL cskill,
                                      " . $table_prefix . "SKILL cskill
                                 WHERE cskill.CHARACTER_ID   = %d
                                   AND skill.id              = %d
                                   AND skill.skill_id        = cskill.skill_id";
            $duplicates = $wpdb->get_results($wpdb->prepare($duplicateSQL, $characterID, $tokens [2]));

            foreach ($duplicates as $duplicate) {
                if ($duplicate->comment == $specialisation) {
                    $duplicateSkill = $duplicates->name;
                }
            }
        }

        if ($duplicateSkill != "") {
            return "A duplicate skill (" . $duplicateSkill . ") with the same specialisation (" . $specialisation . ") already exists, XP Spend aborted";
        }

        $pendingXPSQL = "INSERT INTO " . $table_prefix . "PENDING_XP_SPEND(player_id,
                                                                                   character_id,
                                                                                   code,
                                                                                   awarded,
                                                                                   amount,
                                                                                   comment,
                                                                                   training_note,
                                                                                   specialisation)
                             VALUES (%d, %d, %s, SYSDATE(), %d, %s, %s, %s)";

        $negativeXpCost = $xp_cost * -1;

        $pendingXPSQL = $wpdb->prepare($pendingXPSQL, $playerID, $characterID, $xpSpendString, $negativeXpCost, $comment, $trainingNote, $specialisation);

        $wpdb->query($pendingXPSQL);

        return "PENDING XP Spend (" . $traitName . " " . $currentValue . " > " . $newValue . ") applied to " . $character
            . " " . $xp_cost . " XP reserved."; */
    }
	
/*
	master_xp_update GVLARP_FORM
*/
    function handleMasterXP() {
        $counter = 1;
        while (isset($_POST['counter_' . $counter])) {
            $current_player_id = $_POST['counter_' . $counter];
            $current_xp_value  = $_POST[$current_player_id . '_xp_value'];
            if (is_numeric($current_xp_value) && ((int) $current_xp_value != 0)) {
                addPlayerXP($current_player_id,
                    $_POST[$current_player_id . '_character'],
                    $_POST[$current_player_id . '_xp_reason'],
                    $current_xp_value,
                    $_POST[$current_player_id . '_xp_comment']);
            }
            $counter++;
        }
    }
	
/* Add XP to the database
	- called by handleMasterXP
	- and handleGVLarpForm
 */
function addPlayerXP($player, $character, $xpReason, $value, $comment) {
	global $wpdb;
	$table_prefix = GVLARP_TABLE_PREFIX;
	$sql = "INSERT INTO " . $table_prefix . "PLAYER_XP (player_id, amount, character_id, xp_reason_id, comment, awarded)
					VALUES (%d, %d, %d, %d, %s, SYSDATE())";
	$wpdb->query($wpdb->prepare($sql, $player, ((int) $value), $character, $xpReason, $comment));
}

/* shortcode */

function print_xp_spend_table($atts, $content=null) {
	extract(shortcode_atts(array ("character" => "null", "visible" => "N"), $atts));
	$character = establishCharacter($character);
	$characterID = establishCharacterID($character);
	
	if (!isST()) {
		$visible = "N";
	}

	global $wpdb;
	$table_prefix = GVLARP_TABLE_PREFIX;
	
	/* Exit if this character has been deleted */
	/* TO DO */

	// Replacing all underscores with strings
	$defaultTrainingString = "Tell us how you are learning this";
	$defaultSpecialisation = "New Specialisation";
	$trainingNote          = $_POST['trainingNote'];
	/* $xpSpend               = $_POST['xp_spend'];
	$specialisation        = $_POST['spec_' . $xpSpend]; */
	$fulldoturl            = plugins_url( 'gvlarp-character/images/viewfulldot.jpg' );
	$emptydoturl           = plugins_url( 'gvlarp-character/images/viewemptydot.jpg' );
	$pendingdoturl         = plugins_url( 'gvlarp-character/images/pendingdot.jpg' );

	$output    = "";

	$postedCharacter = $_POST['character'];

	if ($_POST['GVLARP_FORM'] == "applyXPSpend"
		&& $postedCharacter != ""
		&& $_POST['xSubmit'] == "Spend XP") {
		
		if (isset($_REQUEST['stat_level'])) {
			$spec_at = $_REQUEST['stat_spec_at'];
			$statlevels = $_REQUEST['stat_level'];
			$stat_specialisations = $_REQUEST['stat_spec'];
			$stattraining = $_REQUEST['stat_training'];
			foreach ($stat_specialisations as $id => $specialisation) {
				if ($spec_at[$id] <= $statlevels[$id] &&
					($specialisation == "" || $specialisation == $defaultSpecialisation)) {
					$stat_spec_error[$id] = 1;
				}
			}
			if (count($stat_spec_error))
				$output .= "<p>Please fix mising or invalid specialisations</p>";
				
			foreach ($stattraining as $id => $trainingnote) {
				if ($statlevels[$id] &&
					($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
					$stat_train_error[$id] = 1;
				}
			}
			if (count($stat_train_error))
				$output .= "<p>Please fix mising training notes</p>";
	
		}
		if (isset($_REQUEST['skill_level'])) {
			$spec_at = $_REQUEST['skill_spec_at'];
			$skilllevels = $_REQUEST['skill_level'];
			$skill_specialisations = $_REQUEST['skill_spec'];
			$skilltraining = $_REQUEST['skill_training'];
			foreach ($skill_specialisations as $id => $specialisation) {
				if ($spec_at[$id] <= $skilllevels[$id] &&
					($specialisation == "" || $specialisation == $defaultSpecialisation)) {
					$skill_spec_error[$id] = 1;
				}
			}
			if (count($skill_spec_error))
				$output .= "<p>Please fix mising or invalid specialisations</p>";
				
			foreach ($skilltraining as $id => $trainingnote) {
				if ($skilllevels[$id] &&
					($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
					$skill_train_error[$id] = 1;
				}
			}
			if (count($skill_train_error))
				$output .= "<p>Please fix mising training notes</p>";
	
		}

		if (isset($_REQUEST['newskill_level'])) {
			$spec_at = $_REQUEST['newskill_spec_at'];
			$newskilllevels = $_REQUEST['newskill_level'];
			$newskill_specialisations = $_REQUEST['newskill_spec'];
			$newskilltraining = $_REQUEST['newskill_training'];
			foreach ($newskill_specialisations as $id => $specialisation) {
				if ($spec_at[$id] <= $newskilllevels[$id] &&
					($specialisation == "" || $specialisation == $defaultSpecialisation)) {
					$newskill_spec_error[$id] = 1;
				}
			}
			if (count($newskill_spec_error))
				$output .= "<p>Please fix mising or invalid specialisations</p>";
				
			foreach ($newskilltraining as $id => $trainingnote) {
				if ($newskilllevels[$id] &&
					($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
					$newskill_train_error[$id] = 1;
				}
			}
			if (count($newskill_train_error))
				$output .= "<p>Please fix mising training notes</p>";
	
		}
		if (isset($_REQUEST['disc_level'])) {
			$disclevels = $_REQUEST['disc_level'];
			$disctraining = $_REQUEST['disc_training'];
				
			foreach ($disctraining as $id => $trainingnote) {
				if ($disclevels[$id] &&
					($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
					$disc_train_error[$id] = 1;
				}
			}
			if (count($disc_train_error))
				$output .= "<p>Please fix mising training notes</p>";
	
		}
		if (isset($_REQUEST['newdisc_level'])) {
			$newdisclevels = $_REQUEST['newdisc_level'];
			$newdisctraining = $_REQUEST['newdisc_training'];
				
			foreach ($newdisctraining as $id => $trainingnote) {
				if ($newdisclevels[$id] &&
					($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
					$newdisc_train_error[$id] = 1;
				}
			}
			if (count($newdisc_train_error))
				$output .= "<p>Please fix mising training notes</p>";
	
		}
		if (isset($_REQUEST['path_level'])) {
			$pathlevels = $_REQUEST['path_level'];
			$pathtraining = $_REQUEST['path_training'];
				
			foreach ($pathtraining as $id => $trainingnote) {
				if ($pathlevels[$id] &&
					($trainingnote == "" || $trainingnote == $defaultTrainingString)) {
					$path_train_error[$id] = 1;
				}
			}
			if (count($path_train_error))
				$output .= "<p>Please fix mising training notes</p>";
	
		}
		
		if ($output == "") {
		
			/* if (!isST()) { */
				$output = "<p>" . doPendingXPSpend($postedCharacter) . "</p>";
			/* }
			else {
				$output = "<p>" . doXPSpend($postedCharacter, $_POST['xp_spend'], 0, $specialisation) . "</p>";
			} */
		}

	}

	/* how much XP is available to spend */
	$attributes = array ("character" => $character, "group" => "TOTAL", "maxrecords" => "1");
	$xp_total = print_character_xp_table($attributes, null);

	/* work out the maximum ratings for this character based on generation */
	$maxRating     = 5;
	$maxDiscipline = 5;

	$sql = "SELECT gen.max_rating, gen.max_discipline
				FROM " . $table_prefix . "CHARACTER chara,
					 " . $table_prefix . "GENERATION gen
				WHERE chara.generation_id = gen.id
				  AND chara.ID = %s";

	$characterMaximums = $wpdb->get_results($wpdb->prepare($sql, $characterID));
	foreach ($characterMaximums as $charMax) {
		$maxRating = $charMax->max_rating;
		$maxDiscipline = $charMax->max_discipline;
	}

	/* Attributes 
	------------------------------------------------------------------------*/
	
	/* get current stat levels */
	$sqlOutput = "";
	$sql = "SELECT stat.name, cha_stat.comment, cha_stat.level, cha_stat.id, 
				stat.specialisation_at spec_at, stat.ID as stat_id
				FROM " . $table_prefix . "CHARACTER_STAT cha_stat,
					 " . $table_prefix . "STAT stat
				WHERE cha_stat.STAT_ID      = stat.ID
				  AND cha_stat.CHARACTER_ID = %s
			   ORDER BY stat.ordering";
	$character_stats_xp = $wpdb->get_results($wpdb->prepare($sql, $characterID));

	/* get the dot maximum to display: 5 or 10 */
	$max2display = get_max_dots($character_stats_xp, $maxRating);
	
	/* get pending stat levels */
	$stat_spends = get_pending($characterID, 'CHARACTER_STAT');

	foreach ($character_stats_xp as $stat_xp) {
		
		/* get costs per level */
		$stat_costs = get_xp_costs_per_level("STAT", $stat_xp->stat_id, $stat_xp->level);
		
		$sqlOutput .= render_spend_row("stat", $stat_xp, $stat_specialisations,
						$stat_spec_error, $stat_spends, $max2display, $maxRating,
						$stat_costs, $statlevels, $stattraining, $stat_train_error,
						$defaultTrainingString, $fulldoturl, $emptydoturl, 
						$pendingdoturl);
		
		
		
	}

	$output .= "<form name=\"SPEND_XP_FORM\" method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\">";
	$output .= "<table class='gvplugin' id=\"gvid_xpst\">";

	if ($sqlOutput != "") {
		$output .= "<tr><th class=\"gvthead gvcol_1\">Name</th><th class='gvthead gvcol_2'>Specialisation</th>";
		for($i=1;$i<=$max2display;$i++) {
			$output .= "<th colspan=2 class='gvthead gvcol_radiohead gvcol_" . ($i + 2) . "'>" . $i . "</th>";
		}
		$output .= "<th class=\"gvthead gvcol_radiohead gvcol_7\">N/A</th>";
		$output .= "<th class=\"gvthead gvcol_7\">Training Notes</th></tr>";
		$output .= $sqlOutput;
	}
			
	
	/* Abilities - current
	------------------------------------------------------------*/
	/* current skills */
	$sql = "SELECT skill.name, cha_skill.comment, cha_skill.level, cha_skill.id,
				skill.specialisation_at spec_at, skill.ID as item_id
			FROM
				" . $table_prefix . "CHARACTER_SKILL cha_skill,
				" . $table_prefix . "SKILL skill
			WHERE
				cha_skill.SKILL_ID = skill.ID
				AND cha_skill.CHARACTER_ID = %s
			ORDER BY skill.name ASC";
	$sql = $wpdb->prepare($sql, $characterID);
	$character_skills_xp = $wpdb->get_results($sql);
			
	/* get the dot maximum to display: 5 or 10 */
	$max2display = get_max_dots($character_skills_xp, $maxRating);
	
	/* get pending levels */
	$skill_spends = get_pending($characterID, 'CHARACTER_SKILL');
	
	$sqlOutput = "";
	foreach ($character_skills_xp as $skillxp) {

		/* get costs per level */
		$skill_costs = get_xp_costs_per_level("SKILL", $skillxp->item_id, $skillxp->level);
		
		$sqlOutput .= render_spend_row("skill", $skillxp, $skill_specialisations,
						$skill_spec_error, $skill_spends, $max2display, $maxRating,
						$skill_costs, $skilllevels, $skilltraining, $skill_train_error,
						$defaultTrainingString, $fulldoturl, $emptydoturl, 
						$pendingdoturl);
		
		
		
	}
	
	if ($sqlOutput != "") {
		$output .= "<tr><td>&nbsp;</td></tr>\n";
		$output .= "<tr><th class=\"gvthead gvcol_1\">Name</th><th class='gvthead gvcol_2'>Specialisation</th>";
		for($i=1;$i<=$max2display;$i++) {
			$output .= "<th colspan=2 class='gvthead gvcol_radiohead gvcol_" . ($i + 2) . "'>" . $i . "</th>";
		}
		$output .= "<th class=\"gvthead gvcol_radiohead gvcol_7\">N/A</th>";
		$output .= "<th class=\"gvthead gvcol_7\">Training Notes</th></tr>";
		$output .= $sqlOutput;
	}
	
	/* Abilities - new / multiple
	------------------------------------------------------------*/
	/* new skills */
	$sql = "SELECT skill.name, \"\" as comment, 0 as level, 0 as id,
				skill.specialisation_at spec_at, skill.ID as item_id
			FROM
				" . $table_prefix . "SKILL skill
				LEFT JOIN
					(
					SELECT DISTINCT SKILL_ID 
					FROM 
						" . $table_prefix . "CHARACTER_SKILL
					WHERE
						CHARACTER_ID = %d
					) as char_skill
				ON
					skill.ID = char_skill.SKILL_ID
			WHERE ";
	if (!isST())
		$sql .= "skill.VISIBLE = 'Y' AND ";
	$sql .= "(skill.MULTIPLE = 'Y' OR ISNULL(char_skill.SKILL_ID))
			ORDER BY skill.name ASC";
	$sql = $wpdb->prepare($sql, $characterID);
	/* echo "<pre>$sql</pre>"; */
	$character_newskills_xp = $wpdb->get_results($sql);

	/* get pending levels */
	$newskill_spends = get_pending($characterID, 'CHARACTER_SKILL');

	/* get the dot maximum to display: 5 or 10 */
	$max2display = get_max_dots($character_newskills_xp, $maxRating);

	$sqlOutput = "";
	foreach ($character_newskills_xp as $skillxp) {

		/* get costs per level */
		$newskill_costs = get_xp_costs_per_level("SKILL", $skillxp->item_id, $skillxp->level);
					
		$sqlOutput .= render_spend_row("newskill", $skillxp, $newskill_specialisations,
						$newskill_spec_error, $newskill_spends, $max2display, $maxRating,
						$newskill_costs, $newskilllevels, $newskilltraining, $newskill_train_error,
						$defaultTrainingString, $fulldoturl, $emptydoturl, 
						$pendingdoturl);
		
	}
	if ($sqlOutput != "") {
		$output .= "<tr><td>&nbsp;</td></tr>\n";
		$output .= $sqlOutput;
	}
	
	/* Disciplines - current
	------------------------------------------------------------*/
	$sql = "SELECT discipline.name, cha_dis.comment, cha_dis.level, cha_dis.id,
				discipline.ID as item_id
			FROM
				" . $table_prefix . "CHARACTER_DISCIPLINE cha_dis,
				" . $table_prefix . "DISCIPLINE discipline
			WHERE
				cha_dis.DISCIPLINE_ID = discipline.ID
				AND cha_dis.CHARACTER_ID = %s
			ORDER BY discipline.name ASC";
	$sql = $wpdb->prepare($sql, $characterID);
	$character_discipline_xp = $wpdb->get_results($sql);
			
	/* get the dot maximum to display: 5 or 10 */
	$max2display = get_max_dots($character_discipline_xp, $maxRating);
	
	/* get pending levels */
	$disc_spends = get_pending($characterID, 'CHARACTER_DISCIPLINE');
	
	$sqlOutput = "";
	foreach ($character_discipline_xp as $discxp) {

		/* get costs per level */
		$disc_costs = get_xp_costs_per_level("DISCIPLINE", $discxp->item_id, $discxp->level);
		
		$sqlOutput .= render_spend_row("disc", $discxp, $disc_specialisations,
						$disc_spec_error, $disc_spends, $max2display, $maxRating,
						$disc_costs, $disclevels, $disctraining, $disc_train_error,
						$defaultTrainingString, $fulldoturl, $emptydoturl, 
						$pendingdoturl, 1);
	}
	
	if ($sqlOutput != "") {
		$output .= "<tr><td>&nbsp;</td></tr>\n";
		$output .= "<tr><th colspan=2 class=\"gvthead gvcol_1\">Name</th>";
		for($i=1;$i<=$max2display;$i++) {
			$output .= "<th colspan=2 class='gvthead gvcol_radiohead gvcol_" . ($i + 2) . "'>" . $i . "</th>";
		}
		$output .= "<th class=\"gvthead gvcol_radiohead gvcol_7\">N/A</th>";
		$output .= "<th class=\"gvthead gvcol_7\">Training Notes</th></tr>";
		$output .= $sqlOutput;
	}

	/* Disciplines - new
	------------------------------------------------------------*/
	$sql = "SELECT discipline.name, \"\" as comment, 0 as level, 0 as id,
				discipline.ID as item_id
			FROM
				" . $table_prefix . "DISCIPLINE discipline
				LEFT JOIN
					(
					SELECT DISTINCT DISCIPLINE_ID 
					FROM 
						" . $table_prefix . "CHARACTER_DISCIPLINE
					WHERE
						CHARACTER_ID = %d
					) as char_disc
				ON
					discipline.ID = char_disc.DISCIPLINE_ID
			WHERE ISNULL(char_disc.DISCIPLINE_ID) ";
	if (!isST())
		$sql .= "AND discipline.VISIBLE = 'Y' ";
	$sql .= "ORDER BY discipline.name ASC";
	
	$sql = $wpdb->prepare($sql, $characterID);
	/* echo "<pre>$sql</pre>"; */
	$character_newdisc_xp = $wpdb->get_results($sql);

	/* get pending levels */
	$newdisc_spends = get_pending($characterID, 'CHARACTER_DISCIPLINE');

	/* get the dot maximum to display: 5 or 10 */
	$max2display = get_max_dots($character_newdiscs_xp, $maxRating);

	$sqlOutput = "";
	foreach ($character_newdisc_xp as $discxp) {

		/* get costs per level */
		$newdisc_costs = get_xp_costs_per_level("DISCIPLINE", $discxp->item_id, $discxp->level);
					
		$sqlOutput .= render_spend_row("newdisc", $discxp, $newdisc_specialisations,
						$newdisc_spec_error, $newdisc_spends, $max2display, $maxRating,
						$newdisc_costs, $newdisclevels, $newdisctraining, $newdisc_train_error,
						$defaultTrainingString, $fulldoturl, $emptydoturl, 
						$pendingdoturl, 1);
		
	}
	if ($sqlOutput != "") {
		$output .= "<tr><td>&nbsp;</td></tr>\n";
		$output .= $sqlOutput;
	}

	/* Magik Paths
	------------------------------------------------------------*/
	$sql = "SELECT path.name, cha_path.comment, cha_path.level, cha_path.id,
				path.ID as item_id
			FROM
				" . $table_prefix . "CHARACTER_PATH cha_path,
				" . $table_prefix . "PATH path
			WHERE
				cha_path.PATH_ID = path.ID
				AND cha_path.CHARACTER_ID = %s
			ORDER BY path.name ASC";
	$sql = $wpdb->prepare($sql, $characterID);
	$character_path_xp = $wpdb->get_results($sql);
			
	/* get the dot maximum to display: 5 or 10 */
	$max2display = get_max_dots($character_path_xp, $maxRating);
	
	/* get pending levels */
	$path_spends = get_pending($characterID, 'CHARACTER_PATH');
	
	$sqlOutput = "";
	foreach ($character_path_xp as $pathxp) {

		/* get costs per level */
		$path_costs = get_xp_costs_per_level("PATH", $pathxp->item_id, $pathxp->level);
		
		$sqlOutput .= render_spend_row("path", $pathxp, $path_specialisations,
						$path_spec_error, $path_spends, $max2display, $maxRating,
						$path_costs, $pathlevels, $pathtraining, $path_train_error,
						$defaultTrainingString, $fulldoturl, $emptydoturl, 
						$pendingdoturl, 1);
	}
	
	if ($sqlOutput != "") {
		$output .= "<tr><td>&nbsp;</td></tr>\n";
		$output .= "<tr><th colspan=2 class=\"gvthead gvcol_1\">Name</th>";
		for($i=1;$i<=$max2display;$i++) {
			$output .= "<th colspan=2 class='gvthead gvcol_radiohead gvcol_" . ($i + 2) . "'>" . $i . "</th>";
		}
		$output .= "<th class=\"gvthead gvcol_radiohead gvcol_7\">N/A</th>";
		$output .= "<th class=\"gvthead gvcol_7\">Training Notes</th></tr>";
		$output .= $sqlOutput;
	}


	
	/*******************************************************************************************/

	/*
	$visibleSector = "";
	if ($visible == 'N') {
		$visibleSector = " AND path.visible = 'Y' ";
	}

	$sql = "SELECT path.name path_name, dis.name dis_name, cha_path.level, cha_path.id, cmstep.xp_cost, cmstep.next_value, cha_dis.level cha_dis_level, 1 ordering
					FROM " . $table_prefix . "CHARACTER_PATH cha_path,
						 " . $table_prefix . "PATH path,
						 " . $table_prefix . "DISCIPLINE dis,
						 " . $table_prefix . "CHARACTER chara,
						 " . $table_prefix . "CHARACTER_DISCIPLINE cha_dis,
						 " . $table_prefix . "COST_MODEL_STEP cmstep
					WHERE cha_path.PATH_ID       = path.ID
					  AND path.DISCIPLINE_ID     = dis.ID
					  AND cha_path.CHARACTER_ID  = chara.ID
					  AND path.COST_MODEL_ID     = cmstep.COST_MODEL_ID
					  AND cmstep.current_value   = cha_path.level
					  AND cha_dis.CHARACTER_ID   = chara.ID
					  AND cha_dis.DISCIPLINE_ID  = dis.ID
					  AND chara.DELETED != 'Y'
					  AND chara.WORDPRESS_ID = %s
					union
					SELECT path.name path_name, dis.name dis_name, 0 level, path.id, cmstep.xp_cost, cmstep.next_value, cha_dis.level cha_dis_level, 2 ordering
					FROM " . $table_prefix . "PATH path,
						 " . $table_prefix . "CHARACTER chara,
						 " . $table_prefix . "DISCIPLINE dis,
						 " . $table_prefix . "CHARACTER_DISCIPLINE cha_dis,
						 " . $table_prefix . "COST_MODEL_STEP cmstep
					WHERE path.COST_MODEL_ID    = cmstep.COST_MODEL_ID
					  AND cmstep.current_value  = 0
					  AND cha_dis.CHARACTER_ID  = chara.ID
					  AND cha_dis.DISCIPLINE_ID = path.DISCIPLINE_ID
					  AND path.DISCIPLINE_ID    = dis.ID
					  AND cha_dis.level        >= cmstep.next_value
					  AND chara.WORDPRESS_ID = %s
					  AND chara.DELETED != 'Y' "
		. $visibleSector . "
					  AND path.id NOT IN (SELECT path_id
										  FROM " . $table_prefix . "CHARACTER_PATH cha_path,
											   " . $table_prefix . "CHARACTER chara
										  WHERE chara.ID = cha_path.CHARACTER_ID
											AND chara.WORDPRESS_ID = %s)
					ORDER BY dis_name, ordering, path_name";

	$character_path_xp = $wpdb->get_results($wpdb->prepare($sql, $character, $character, $character));

	$sqlOutput = "";
	foreach ($character_path_xp as $path_xp) {
		$sqlOutput .= "<tr><th class='gvthleft'>" . $path_xp->path_name
			. "</th><td class='gvcol_2 gvcol_val'>" . $path_xp->dis_name
			. "</td><td class=\"gvcol_3 gvcol_val\">" . $path_xp->level
			. "</td><td class='gvcol_4 gvcol_val'>=></td>";

		if (((int)$path_xp->next_value) >  ((int) $path_xp->level)
			&& ((int)$path_xp->next_value) <= ((int) $path_xp->cha_dis_level)
			&& ((int)$path_xp->next_value) <= $maxDiscipline) {
			$sqlOutput .= "<td class='gvcol_5 gvcol_val'>" . $path_xp->next_value
				.  "</td><td class='gvcol_6 gvcol_val'>" . $path_xp->xp_cost . "</td>";
			if (((int) $xp_total) >= ((int)$path_xp->xp_cost)) {
				$sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"><input type='RADIO' name=\"xp_spend\" value=\"path_";
				if ((int)$path_xp->level == 0) {
					$sqlOutput .= "new_";
				}
				$sqlOutput .= $path_xp->id . "\" /></td>";
			}
			else {
				$sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"></td>";
			}
			$sqlOutput .= "</tr>";
		}
		else {
			$sqlOutput .= "<td class='gvcol_5 gvcol_val' colspan=3>No xp spend available</td>";
		}
	}

	if ($sqlOutput != "") {
		$output .= "<tr><th class=\"gvthead gvcol_1\">Path</th>
							<th class=\"gvthead gvcol_2\">Discipline</th>
							<th class=\"gvthead gvcol_3\">Current</th>
							<th class=\"gvthead gvcol_4\"></th>
							<th class=\"gvthead gvcol_5\">New</th>
							<th class=\"gvthead gvcol_6\">Cost</th>
							<th class=\"gvthead gvcol_7\"></th></tr>"
			. $sqlOutput;
	}

	*/
	/*******************************************************************************************/

	/*
	$visibleSector = "";
	if ($visible == 'N') {
		$visibleSector = " AND rit.visible = 'Y' ";
	}

	$sql = "SELECT rit.name rit_name, dis.name dis_name, rit.level, rit.id, rit.cost, 1 ordering
					FROM " . $table_prefix . "CHARACTER_RITUAL cha_rit,
						 " . $table_prefix . "RITUAL rit,
						 " . $table_prefix . "CHARACTER chara,
						 " . $table_prefix . "DISCIPLINE dis
					WHERE cha_rit.RITUAL_ID     = rit.ID
					  AND cha_rit.CHARACTER_ID  = chara.ID
					  AND rit.DISCIPLINE_ID     = dis.ID
					  AND chara.DELETED != 'Y'
					  AND chara.WORDPRESS_ID = %s
					union
					SELECT rit.name rit_name, dis.name dis_name, rit.level, rit.id, rit.cost, 2 ordering
					FROM " . $table_prefix . "RITUAL rit,
						 " . $table_prefix . "DISCIPLINE dis,
						 " . $table_prefix . "CHARACTER chara,
						 " . $table_prefix . "CHARACTER_DISCIPLINE cha_dis
					WHERE cha_dis.CHARACTER_ID = chara.ID
					  AND cha_dis.DISCIPLINE_ID = rit.DISCIPLINE_ID
					  AND rit.DISCIPLINE_ID = dis.ID
					  AND cha_dis.level >= rit.level
					  AND chara.WORDPRESS_ID = %s
					  AND chara.DELETED != 'Y' "
		. $visibleSector . "
					  AND rit.id NOT IN (SELECT ritual_id
										   FROM " . $table_prefix . "CHARACTER_RITUAL cha_rit,
												" . $table_prefix . "CHARACTER chara
										   WHERE chara.ID = cha_rit.CHARACTER_ID
											 AND chara.WORDPRESS_ID = %s)
					ORDER BY dis_name, ordering, level, rit_name";

	$character_ritual_xp = $wpdb->get_results($wpdb->prepare($sql, $character, $character, $character));

	$sqlOutput = "";
	foreach ($character_ritual_xp as $ritual_xp) {
		$sqlOutput .= "<tr><th class='gvthleft'>" . $ritual_xp->rit_name
			. "</th><td class='gvcol_2 gvcol_val'>" . $ritual_xp->dis_name
			. "</td><td class=\"gvcol_3 gvcol_val\">" . $ritual_xp->level
			. "</td>";

		if ($ritual_xp->ordering == 2
			&& ((int)$ritual_xp->level) <= $maxDiscipline) {
			$sqlOutput .= "<td class='gvcol_4 gvcol_val' colspan=2>&nbsp;</td><td class='gvcol_6 gvcol_val'>" . $ritual_xp->cost . "</td>";
			if (((int) $xp_total) >= ((int)$ritual_xp->cost)) {
				$sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"><input type='RADIO' name=\"xp_spend\" value=\"ritual_new_";
				$sqlOutput .= $ritual_xp->id . "\" /></td>";
			}
			else {
				$sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"></td>";
			}
			$sqlOutput .= "</tr>";
		}
		else {
			$sqlOutput .= "<td class='gvcol_4 gvcol_val' colspan=4>Ritual known</td>";
		}
	}

	if ($sqlOutput != "") {
		$output .= "<tr><th class=\"gvthead gvcol_1\">Ritual</th>
							<th class=\"gvthead gvcol_2\">Discipline</th>
							<th class=\"gvthead gvcol_3\">Level</th>
							<th class=\"gvthead gvcol_4\"></th>
							<th class=\"gvthead gvcol_5\"></th>
							<th class=\"gvthead gvcol_6\">Cost</th>
							<th class=\"gvthead gvcol_7\"></th></tr>"
			. $sqlOutput;
	}

	*/
	/*****************************************************************/

	/*
	$visibleSector = "";
	if ($visible == 'N') {
		$visibleSector = " AND merit.visible = 'Y' ";
	}

	$sql = "SELECT merit.name merit_name, merit.id, merit.value, merit.xp_cost, cha_merit.id cha_merit_id, 1 ordering
				FROM " . $table_prefix . "CHARACTER_MERIT cha_merit,
					 " . $table_prefix . "MERIT merit,
					 " . $table_prefix . "CHARACTER chara
				WHERE cha_merit.MERIT_ID     = merit.ID
				  AND cha_merit.CHARACTER_ID = chara.ID
				  AND chara.DELETED != 'Y'
				  AND chara.WORDPRESS_ID = %s
				  AND merit.value > 0
				union
				SELECT merit.name merit_name, merit.id, merit.value, merit.xp_cost, cha_merit.id cha_merit_id, 2 ordering
				FROM " . $table_prefix . "CHARACTER_MERIT cha_merit,
					 " . $table_prefix . "MERIT merit,
					 " . $table_prefix . "CHARACTER chara
				WHERE cha_merit.MERIT_ID     = merit.ID
				  AND cha_merit.CHARACTER_ID = chara.ID
				  AND chara.DELETED != 'Y'
				  AND chara.WORDPRESS_ID = %s
				  AND merit.value < 0
				  AND merit.xp_cost = 0
				union
				SELECT merit.name merit_name, merit.id, merit.value, merit.xp_cost, cha_merit.id cha_merit_id, 3 ordering
				FROM " . $table_prefix . "CHARACTER_MERIT cha_merit,
					 " . $table_prefix . "MERIT merit,
					 " . $table_prefix . "CHARACTER chara
				WHERE cha_merit.MERIT_ID     = merit.ID
				  AND cha_merit.CHARACTER_ID = chara.ID
				  AND chara.DELETED != 'Y'
				  AND chara.WORDPRESS_ID = %s
				  AND merit.value < 0
				  AND merit.xp_cost != 0
				union
				SELECT merit.name merit_name, merit.id, merit.value, merit.xp_cost, 0 cha_merit_id, 4 ordering
				FROM " . $table_prefix . "MERIT merit
				WHERE merit.xp_cost != 0
				  AND merit.value > 0 "
		. $visibleSector . "
				  AND (merit.id NOT IN (SELECT merit_id
										FROM " . $table_prefix . "CHARACTER_MERIT cha_merit,
											 " . $table_prefix . "CHARACTER chara
										WHERE chara.ID = cha_merit.CHARACTER_ID
										  AND chara.WORDPRESS_ID = %s)
					   OR merit.id IN (SELECT id
									   FROM " . $table_prefix . "MERIT
									   WHERE multiple = 'Y'))
				  ORDER BY ordering, merit_name";

	$sql = $wpdb->prepare($sql, $character, $character, $character, $character);
	$character_merit_xp = $wpdb->get_results($sql);

	$sqlOutput = "";
	foreach ($character_merit_xp as $merit_xp) {
		$sqlOutput .= "<tr><th class='gvthleft' colspan=2>" . $merit_xp->merit_name
			. "</th><td class=\"gvcol_3 gvcol_val\">" . $merit_xp->value
			. "</td>";

		if ($merit_xp->ordering == 3) {
			$sqlOutput .= "<td class='gvcol_4 gvcol_val' colspan=2>&nbsp;</td><td class='gvcol_6 gvcol_val'>" . $merit_xp->xp_cost . "</td>";
			if (((int) $xp_total) >= ((int) $merit_xp->xp_cost)) {
				$sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"><input type='RADIO' name=\"xp_spend\" value=\"merit_";
				$sqlOutput .= $merit_xp->cha_merit_id . "\" /></td>";
			}
			else {
				$sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"></td>";
			}
		}
		else if ($merit_xp->ordering == 4) {
			$sqlOutput .= "<td class='gvcol_4 gvcol_val' colspan=2>&nbsp;</td><td class='gvcol_6 gvcol_val'>" . $merit_xp->xp_cost . "</td>";
			if (((int) $xp_total) >= ((int) $merit_xp->xp_cost)) {
				$sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"><input type='RADIO' name=\"xp_spend\" value=\"merit_new_";
				$sqlOutput .= $merit_xp->id . "\" /></td>";
			}
			else {
				$sqlOutput .= "<td class=\"gvcol_7 gvcol_val\"></td>";
			}
		}
		else {
			$meritFlaw = "Merit already acquired";
			if ($merit_xp->ordering == 2) {
				$meritFlaw = "Flaw cannot be bought off";
			}
			$sqlOutput .= "<td class='gvcol_4 gvcol_val' colspan=4>" . $meritFlaw . "</td>";
		}
		$sqlOutput .= "</tr>";
	}

	if ($sqlOutput != "") {
		$output .= "<tr><th class=\"gvthead gvcol_1\" colspan=2>Merit</th>
							<th class=\"gvthead gvcol_3\">Value</th>
							<th class=\"gvthead gvcol_4\"></th>
							<th class=\"gvthead gvcol_5\"></th>
							<th class=\"gvthead gvcol_6\">Cost</th>
							<th class=\"gvthead gvcol_7\"></th></tr>"
			. $sqlOutput;
	}

	*/
	/*****************************************************************/


	if ($_POST['GVLARP_CHARACTER'] != "") {
		$output .= "<tr style='display:none'><td colspan=7><input type='HIDDEN' name=\"GVLARP_CHARACTER\" value=\"" . $_POST['GVLARP_CHARACTER'] . "\" /></td></tr>\n";
	}

	$output .= "<tr style='display:none'><td colspan=7><input type='HIDDEN' name=\"character\" value=\"" . $character . "\"></td></tr>\n";
	$output .= "<tr style='display:none'><td colspan=7><input type='HIDDEN' name=\"GVLARP_FORM\" value=\"applyXPSpend\" /></td></tr>\n";
	$output .= "<tr><td colspan=7><input type='submit' name=\"xSubmit\" value=\"Spend XP\"></td></tr></table></form>\n";

	return $output;
}
add_shortcode('xp_spend_table', 'print_xp_spend_table');


function get_xp_cost($dbdata, $current, $new) {

	$cost = 0;
	
	$selected = $current;
	$row = 0;
	while ($selected < $new && $row < count($dbdata)) {
		if ($selected == $dbdata[$row]->CURRENT_VALUE) {
			if ($dbdata[$row]->CURRENT_VALUE == $dbdata[$row]->NEXT_VALUE ||
				$dbdata[$row]->XP_COST == 0 ||
				$dbdata[$row]->NEXT_VALUE > $new) {
				$cost = 0;
				break;
			} else {
				$cost += $dbdata[$row]->XP_COST;
				$selected = $dbdata[$row]->NEXT_VALUE;
			}
		}
		$row++;
	}
	
	return $cost;
}

function get_pending($characterID, $table) {
	global $wpdb;

	$sql = "SELECT CHARTABLE_ID, CHARTABLE_LEVEL, ITEMTABLE, ITEMTABLE_ID, TRAINING_NOTE
			FROM " . GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND
			WHERE
				CHARACTER_ID = %s
				AND CHARTABLE = '%s'";
	$sql = $wpdb->prepare($sql, $characterID, $table);
	/* echo "<p>SQL: $sql</p>"; */
	$result = $wpdb->get_results($sql);

	/* print_r($result); */
	
	/* foreach ($result as $spend)
		$output[$spend->CHARTABLE_ID] = $spend->CHARTABLE_LEVEL; */
	
	return $result;
}

function get_xp_costs_per_level($table, $tableid, $level) {
	global $wpdb;

	$sql = "SELECT steps.CURRENT_VALUE, steps.NEXT_VALUE, steps.XP_COST
		FROM
			" . GVLARP_TABLE_PREFIX . "COST_MODEL_STEP steps,
			" . GVLARP_TABLE_PREFIX . "COST_MODEL models,
			" . GVLARP_TABLE_PREFIX . $table . " mytable
		WHERE
			steps.COST_MODEL_ID = models.ID
			AND mytable.COST_MODEL_ID = models.ID
			AND mytable.ID = %s
			AND steps.NEXT_VALUE > %s
		ORDER BY steps.CURRENT_VALUE ASC";

	$sql = $wpdb->prepare($sql, $tableid, $level);
	
	return $wpdb->get_results($sql);

}

function get_max_dots($data, $maxRating) {
	$max2display = 5;
	if ($maxRating > 5)
		$max2display = 10;
	else {
		/* check what the character has, in case they have the merit that increases
		something above max */
		foreach ($data as $row) {
			if ($row->level > $max2display)
				$max2display = 10;
		}
	}
	return $max2display;
}

function pending_level ($pendingdata, $chartableid, $itemid) {

	$result = 0;
		
	if ($chartableid != 0) {
		foreach ($pendingdata as $row)
			if ($row->CHARTABLE_ID == $chartableid) {
				$result = $row->CHARTABLE_LEVEL;
				break;
			}
	} else {
		foreach ($pendingdata as $row)
			if ($row->ITEMTABLE_ID == $itemid && $row->CHARTABLE_ID == 0) {
				$result = $row->CHARTABLE_LEVEL;
				break;
			}
	}
	
	/* echo "<p>charid: $chartableid, itemid: $itemid, result: $result</p>"; */
	return $result;

}

function pending_training ($pendingdata, $chartableid, $itemid) {

	$result = 0;
		
	if ($chartableid != 0) {
		foreach ($pendingdata as $row)
			if ($row->CHARTABLE_ID == $chartableid) {
				$result = $row->TRAINING_NOTE;
				break;
			}
	} else {
		foreach ($pendingdata as $row)
			if ($row->ITEMTABLE_ID == $itemid && $row->CHARTABLE_ID == 0) {
				$result = $row->TRAINING_NOTE;
				break;
			}
	}
	
	/* echo "<p>charid: $chartableid, itemid: $itemid, result: $result</p>"; */
	return $result;

}


function render_spend_row($type, $xpdata, $specdata, $specerrors, $pending, 
						$max2display, $maxRating, $typecosts, $levelsdata,
						$training, $trainerrors, $traindefault,
						$fulldoturl, $emptydoturl, $pendingdoturl, $nospec = 0) {

	$output = "";
    $output .= "<tr>\n";
	/* Name */
	$output .= "<th ";
	if ($nospec)
		$output .= "colspan=2 ";
	$output .= " class='gvthleft'>" . $xpdata->name . "\n";
	$output .= "<input type='hidden' name='{$type}_spec_at[" . $id . "]' value='" . $xpdata->spec_at . "' >";
	$output .= "<input type='hidden' name='{$type}_curr[" . $id . "]' value='" . $xpdata->level . "' >\n";
	$output .= "<input type='hidden' name='{$type}_itemid[" . $id . "]' value='" . $xpdata->item_id . "' >\n";
	$output .= "<input type='hidden' name='{$type}_new[" . $id . "]' value='" . ($xpdata->id == 0) . "' >\n";
	$output .= " </th>\n";

	$pendinglvl = pending_level($pending, $xpdata->id, $xpdata->item_id);
	
	if ($xpdata->id == 0)
		$id = $xpdata->item_id;
	else
		$id = $xpdata->id;

	/* Specialisation */
	if (!$nospec) {
		$output .= "<td class='gvcol_2 gvcol_val";
		$spec = isset($specdata[$id]) ? $specdata[$id] : stripslashes($xpdata->comment);
		if ($specerrors[$id])
			$output .= " gvcol_error";
		if ($pendinglvl)
			$output .= "'>$spec";
		else
			$output .= "'><input type='text' name='${type}_spec[" . $id . "]' value='" . $spec . "' size=15 maxlength=60>";
		$output .= "</td>";
	} 
	

	$xpcost = 0;
	for ($i=1;$i<=$max2display;$i++) {
		$radiogroup = $type . "_level[" . $id . "]";
		$radiovalue = "$i";
		if ($i <= $xpdata->level) {
			$output .= "<td class='gvxp_dot'><img src='$fulldoturl'></td><td>&nbsp;</td>";
		} 
		elseif ($i > $maxRating) {
			$output .= "<td class='gvxp_dot'><img src='$emptydoturl'></td><td>&nbsp;</td>";
		}
		elseif ($pendinglvl)
			if ($pendinglvl >= $i)
				$output .= "<td class='gvxp_dot'><img src='$pendingdoturl'></td><td>&nbsp;</td>";
			else
				$output .= "<td class='gvxp_dot'><img src='$emptydoturl'></td><td>&nbsp;</td>";
		else {
			$xpcost    = get_xp_cost($typecosts, $xpdata->level, $i);
			if (!$xpcost) 
				$output .= "<td class='gvxp_dot'><img src='$emptydoturl'></td>";
			else {
				$output .= "<td class='gvxp_radio'><input type='RADIO' name='$radiogroup' value='$radiovalue' ";
				if (isset($levelsdata[$id]) && $i == $levelsdata[$id])
					$output .= "checked";
				$output .= ">";
				$output .= "<input type='hidden' name='${type}_cost_" . $i . "[" . $id . "]' value='" . $xpcost . "' >";
				$output .= "<input type='hidden' name='${type}_comment_" . $i . "[" . $id . "]' value='" . $xpdata->name . " " . $xpdata->level . " > " . $i . "' ></td>";
				$output .= "</td>";
			}
			$output .= "<td>" . ($xpcost ? $xpcost : "&nbsp;");"</td>";
		}
	}
	
	if ($pendinglvl)
		$output .= "<td class='gvxp_radio'>&nbsp;</td>"; /* no change dot */
	else
		$output .= "<td class='gvxp_radio'><input type='RADIO' name='{$type}_level[{$id}]' value='0' selected-'selected'></td>"; /* no change dot */
	
	/* no training note if you cannot buy */
	$trainingString = isset($training[$id]) ? $training[$id] : $traindefault;
	$output .= "<td";
	if ($trainerrors[$id])
		$output .= " class='gvcol_error'";
	if ($pendinglvl)
		$output .= ">" . pending_training($pending, $xpdata->id, $xpdata->item_id) . "</td></tr></td>";
	else
		$output .= "><input type='text'  name='{$type}_training[" . $id . "]' value='" . $trainingString ."' size=30 maxlength=160 /></td></tr></td>";


	
	return $output;
}

function save_to_pending ($type, $table, $itemtable, $itemidname, $playerID, $characterID) {
	global $wpdb;

	$newid = "";

	$spec_at         = $_REQUEST[$type . '_spec_at'];
	$levels          = $_REQUEST[$type . '_level'];
	$specialisations = $_REQUEST[$type . '_spec'];
	$training        = $_REQUEST[$type . '_training'];
	$itemid          = $_REQUEST[$type . '_itemid'];
	$isnew          = $_REQUEST[$type . '_new'];
	for ($i=1;$i<=10;$i++) $costlvls[$i] = $_REQUEST[$type . '_cost_' . $i];
	for ($i=1;$i<=10;$i++) $comments[$i] = $_REQUEST[$type . '_comment_' . $i];
			
	foreach ($levels as $id => $level) {
		
		if ($level) {
			$dataarray = array (
				'PLAYER_ID'       => $playerID,
				'CHARACTER_ID'    => $characterID,
				'CHARTABLE'       => $table,
				'CHARTABLE_ID'    => ($isnew[$id] ? 0 : $id),
				'CHARTABLE_LEVEL' => $level,
				'AWARDED'         => Date('Y-m-d'),
				'AMOUNT'          => $costlvls[$level][$id] * -1,
				'COMMENT'         => $comments[$level][$id],
				'SPECIALISATION'  => $specialisations[$id],
				'TRAINING_NOTE'   => $training[$id],
				'ITEMTABLE'       => $itemtable,
				'ITEMNAME'        => $itemidname,
				'ITEMTABLE_ID'    => $itemid[$id]
			);
			
			$wpdb->insert(GVLARP_TABLE_PREFIX . "PENDING_XP_SPEND",
						$dataarray,
						array (
							'%d',
							'%d',
							'%s',
							'%d',
							'%d',
							'%s',
							'%d',
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
							'%d'
						)
					);
			
			$newid = $wpdb->insert_id;
			if ($newid  == 0) {
				echo "<p style='color:red'><b>Error:</b> XP (";
				$wpdb->print_error();
				echo ")</p>";
			} 
		}
	
	}	
	
	return $newid;
							
}

?>