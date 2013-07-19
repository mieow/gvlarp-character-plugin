<?php
require_once GVLARP_CHARACTER_URL . 'lib/fpdf.php';

/* read these from config options or use defaults */
$title     = 'GVLARP Character Sheet';
$titlefont = 'Courier';
$titlecolour = array(187,05,06); /* RGB BB0506 */
$titlesize   = 16;
$textfont = 'Arial';
$textcolour = array(0,0,0); /* RGB */
$textsize      = 8; /* points */
$textrowheight = 4; /* mm */
$margin        = 5;
$pagewidth     = 210;
$headsize      = 10;
$headrowheight = 10;
$symbolsize    = 22;
$symbolrowheight = 8;
$dividerlinecolor = array(187,05,06); /* RGB */
$dividertextcolor = array(187,05,06); /* RGB */
$dividertextsize  = 12;
$dividerrowheight = 9;
$dividerlinewidth = 0.5;

$dotmaximum = 5;  /* get this from character */
			
function gv_print_redirect()
{
	global $title;
	global $margin;
	global $wpdb;

    if( $_SERVER['REQUEST_URI'] == get_stlink_url('printCharSheet') && is_user_logged_in() )
    {
		$character = establishCharacter('Ugly Duckling');
		$characterID = establishCharacterID($character);
		$mycharacter = new larpcharacter();
		$mycharacter->load($characterID);
		
		if (class_exists('FPDF')) {

			$pdf = new PDFcsheet('P','mm','A4');
			$pdf->SetTitle($title);
			$pdf->AliasNbPages();
			$pdf->SetMargins($margin, $margin , $margin);
			$pdf->AddPage();
			
			$pdf->BasicInfoTableRow(array(
					'Character Name:', $mycharacter->name,
					'Clan:', $mycharacter->private_clan,
					'Generation:',$mycharacter->generation,
					)
			);
			$pdf->BasicInfoTableRow(array(
					'Player Name:', $mycharacter->player,
					'Public Clan:', $mycharacter->clan,
					'Court:', $mycharacter->court,
					)
			);
			
			$pdf->Divider('Attributes');
			
			$physical = $mycharacter->getAttributes("Physical");
			$social   = $mycharacter->getAttributes("Social");
			$mental   = $mycharacter->getAttributes("Mental");
			
			for ($i=0;$i<3;$i++) {
				$data = array(
						$physical[$i]->name,$physical[$i]->specialty,$physical[$i]->level,
						$social[$i]->name,$social[$i]->specialty,$social[$i]->level,
						$mental[$i]->name,$mental[$i]->specialty,$mental[$i]->level,
						);
				if ($i==0)
					array_push($data, "Physical", "Social", "Mental");
					
				$pdf->FullWidthTableRow($data);
			}
			
			$pdf->Divider('Abilities');

			$talent    = $mycharacter->getAbilities("Talents");
			$skill     = $mycharacter->getAbilities("Skills");
			$knowledge = $mycharacter->getAbilities("Knowledges");
			
			$abilrows = count($talent);
			if ($abilrows < count($skill)) $abilrows = count($skill);
			if ($abilrows < count($knowledge)) $abilrows = count($knowledge);
			
			for ($i=0;$i<$abilrows;$i++) {
				$data = array(
						$talent[$i]->skillname,$talent[$i]->specialty,$talent[$i]->level,
						$skill[$i]->skillname,$skill[$i]->specialty,$skill[$i]->level,
						$knowledge[$i]->skillname,$knowledge[$i]->specialty,$knowledge[$i]->level
						);
				if ($i==0)
					array_push($data, "Talents", "Skills", "Knowledges");
				$pdf->FullWidthTableRow($data);
			
			}
			
			$pdf->Divider();
			
			$backgrounds =  $mycharacter->getBackgrounds();
			$disciplines =  $mycharacter->getDisciplines();
			
			$sql = "SELECT DISTINCT GROUPING FROM " . GVLARP_TABLE_PREFIX . "SKILL skills;";
			$allgroups = $wpdb->get_results($wpdb->prepare($sql));	
			
			$secondarygroups = array();
			foreach ($allgroups as $group) {
				if ($group->GROUPING != 'Talents' && $group->GROUPING != 'Skills' && $group->GROUPING != 'Knowledges')
					array_push($secondarygroups, $group->GROUPING);
			}	

			$secondary = array();
			foreach ($secondarygroups as $group)
					$secondary = array_merge($mycharacter->getAbilities($group), $secondary);	
			
			$rows = count($backgrounds);
			if ($rows < count($disciplines)) $rows = count($disciplines);
			if ($rows < count($secondary)) $rows = count($secondary);
			
			for ($i=0;$i<$rows;$i++) {
				$data = array (
					$backgrounds[$i]->background,
					(!empty($backgrounds[$i]->sector)) ?  $backgrounds[$i]->sector : $backgrounds[$i]->comment,
					$backgrounds[$i]->level,
					
					$disciplines[$i]->name,
					"",
					$disciplines[$i]->level,
					
					$secondary[$i]->skillname,
					$secondary[$i]->specialty,
					$secondary[$i]->level
				);
				if ($i==0)
					array_push($data, "Backgrounds", "Disciplines", "Secondary Abilities");
					
				$pdf->FullWidthTableRow($data);
			}
			
			$pdf->Divider();
			
			$ytop   = $pdf->GetY();
			$xstart = $pdf->GetX();
			/* COLUMN 1 */
			
				/* Merits and Flaws */
				$xnext = $pdf->SingleColumnHeading("Merits and Flaws");
				
				$merits = $mycharacter->meritsandflaws;
				foreach ($merits as $merit) {
					$string = $merit->name;
					if (!empty($merit->comment))
						$string .= " - " . $merit->comment;
					$string .= "(" . $merit->level . ")";
					$pdf->SingleColumnText($string);
				}
				
				$ybottom = $pdf->GetY();
			
			/* COLUMN 2 */
				/* Bloodpool */
				$pdf->SetXY($xnext, $ytop);
				$pdf->BloodPool($mycharacter->bloodpool);
				/* Willpower */
				$xnext = $pdf->Willpower($mycharacter->willpower,$mycharacter->current_willpower);
				
				if ($pdf->GetY() > $ybottom) $ybottom = $pdf->GetY();
			
			/* COLUMN 3 */
				/* Virtues */
				$pdf->SetXY($xnext, $ytop);
				$pdf->SingleColumnTable("Virtues", $mycharacter->getAttributes("Virtue"));
				
				/* Humanity */
				
				/* Health */
				
				if ($pdf->GetY() > $ybottom) $ybottom = $pdf->GetY();
			
			
			$pdf->SetXY($xstart, $ybottom);
			$pdf->Divider();
			
			/* Output PDF */
			
			$pdf->Output();
			
		} else {
			echo "<p>Class error</p>";
			exit;
		}
    }
}
add_action( 'template_redirect', 'gv_print_redirect' );

class PDFcsheet extends FPDF
{
	function Header()
	{
		global $title;
		global $titlefont;
		global $titlecolour;
		global $titlesize;

		$this->SetFont('Courier','B',$titlesize);
		$this->SetTextColor($titlecolour[0],$titlecolour[1],$titlecolour[2]);
		$this->Cell(0,10,$title,0,1,'C');

		$this->Ln(2);
	}
	/* Page footer */
	function Footer()
	{
		$this->SetY(-15);
		$this->SetFont('Arial','I',8);
		$this->Cell(0,10,'www.gvlarp.com | Page ' . $this->PageNo().' of {nb} | Printed on <date>','T',0,'C');
	}
	
	function BasicInfoTableRow($data) {
	
		global $textcolour;
		global $textfont;
		global $textsize;
		global $textrowheight;
		global $dotmaximum;

		$numcols  = count($data);
		$colwidths = array(30,50,25,30,25,40);
		
		$this->SetTextColor($textcolour[0],$textcolour[1],$textcolour[2]);
		$this->SetDrawColor($textcolour[0],$textcolour[1],$textcolour[2]);
		$this->SetLineWidth(.3);
		
		for($i=0;$i<$numcols;$i=$i+2) {
			$this->SetFont($textfont,'B', $textsize);
			$this->Cell($colwidths[$i],$textrowheight,$data[$i],0,0,'R');
			
			$this->SetFont($textfont,'', $textsize);
			$this->Cell($colwidths[$i+1],$textrowheight,$data[$i+1],'B',0,'L'); 
		}
		
		$this->Ln();

	}
	
	function Divider($data = '') {
	
		global $textfont;
		global $margin;
		global $pagewidth;
		global $dividerlinecolor;
		global $dividertextcolor ;
		global $dividertextsize;
		global $dividerrowheight;
		global $dividerlinewidth;
	
		$padding = 7;
		
		$this->Ln(1);
		
		$this->SetFont($textfont,'B', $dividertextsize);
		$this->SetTextColor($dividertextcolor[0],$dividertextcolor[1],$dividertextcolor[2]);
		$this->SetDrawColor($dividerlinecolor[0],$dividerlinecolor[1],$dividerlinecolor[2]);
		$this->SetLineWidth($dividerlinewidth);
		
		if ($data == '') {
			$this->Ln($dividerrowheight);
			$y = $this->GetY()-($dividerrowheight/2);
			$this->Line($margin,$y,$pagewidth - $margin,$y);	
		} else {
			$datawidth = $this->GetStringWidth($data);
			
			$this->Cell(0,$dividerrowheight,$data,0,1,'C');
			
			$y = $this->GetY()-($dividerrowheight/2);
			$x1 = $margin;
			$x2 = ($pagewidth / 2) - ($datawidth / 2) - $padding;
			$this->Line($x1,$y,$x2,$y);	
			
			$x1 = ($pagewidth / 2) + ($datawidth / 2) + $padding;
			$x2 = $pagewidth - $margin;
			$this->Line($x1,$y,$x2,$y);
		}
		
	}
	
	function FullWidthTableRow($data ) {
	
		global $textcolour;
		global $textfont;
		global $margin;
		global $dotmaximum;
		global $pagewidth;
		global $textsize;
		global $textrowheight;
		global $headsize;

		
		$specialtysize = 7;
		$numcols   = 9;
		$itemwidth = 23;
		$specwidth = 23;
		$dotswidth = 20;
		
		$this->SetTextColor($textcolour[0],$textcolour[1],$textcolour[2]);
		
		if (count($data) > 9) {
			$this->SetFont($textfont,'B', $headsize);
			$this->Cell($itemwidth + $specwidth + $dotswidth,$textrowheight,$data[9],0, 0,'C');
			$this->Cell($itemwidth + $specwidth + $dotswidth,$textrowheight,$data[10],0, 0,'C');
			$this->Cell($itemwidth + $specwidth + $dotswidth,$textrowheight,$data[11],0, 0,'C');
			$this->Ln();
		}
		
		$this->SetDrawColor($textcolour[0],$textcolour[1],$textcolour[2]);
		$this->SetLineWidth(.3);
		
		for($i=0;$i<$numcols;$i=$i+3) {
			$this->FitToCell($data[$i+0], $itemwidth, $textfont, '', $textsize); 
			$this->Cell($itemwidth,$textrowheight,$data[$i+0],0,  0,'L');
			
			$this->FitToCell($data[$i+1], $specwidth, $textfont, 'I', $specialtysize); 
			$this->Cell($specwidth,$textrowheight,$data[$i+1],'B',0,'L');
			
			$this->FitToCell($data[$i+2], $dotswidth, $textfont, '', $textsize); 
			/* $this->Cell($dotswidth,$textrowheight,$data[$i+2],0,  0,'L'); */
			$this->Dots($data[$i+2], $dotswidth, $this->GetX(), $this->GetY(), $dotmaximum);
			$this->Cell($dotswidth,$textrowheight,'',0,  0,'L');
		}
		
		$this->Ln();
	
	}
	
	function FitToCell ($text, $cellwidth, $targetfont, $targetfmt, $targetfontsize) {
		$this->SetFont($targetfont,$targetfmt, $targetfontsize);
		while ($cellwidth < $this->GetStringWidth($text) && $targetfontsize > 5) {
			$targetfontsize--;
			$this->SetFont($targetfont,$targetfmt, $targetfontsize);
		}
		
	}
	
	function Dots ($level, $cellwidth, $xorig, $y, $max = 5, $dotheight = 0) {
	
		$padding = 1;
	
		if ($level > $max) $max = 10;
		if (empty($dotheight))
			$dotwidth = ($cellwidth - 2) / $max;
		else
			$dotwidth = 0;
	
		for ($i=1;$i<=$max;$i++) {
			$x = $xorig + $padding + ($i - 1) * ($dotwidth ? $dotwidth : $dotheight);
			if ($i <= $level)
				$this->Image(GVLARP_CHARACTER_URL . "images/fulldot.jpg", $x, $y, $dotwidth, $dotheight);
			else
				$this->Image(GVLARP_CHARACTER_URL . "images/emptydot.jpg", $x, $y, $dotwidth, $dotheight);
		}
	}
	
	function BloodPool ($bloodpool) {
		global $pagewidth;
		global $margin;
		global $headsize;
		global $headrowheight;
		global $symbolsize;
		global $textfont;
		global $symbolrowheight;
		global $textcolour;
	
		$sectionwidth = ($pagewidth - 2 * $margin) / 3;
		$x1 = $this->GetX();
		
		$this->SetFont($textfont,'B',$headsize);
		$this->SetTextColor($textcolour[0],$textcolour[1],$textcolour[2]);
		$this->Cell($sectionwidth, $headrowheight, 'Bloodpool', 0, 1, 'C');
		/* $this->Ln($headrowheight); */
	
		$this->SetFont('ZapfDingbats','',$symbolsize);
		
		$linewidth = $this->GetStringWidth('oooooooooo');
		$centre = $x1 + $sectionwidth/2;
		$x2 = $centre - $linewidth/2;
		
		$this->SetX($x2);
		
		for ($i=1;$i<=$bloodpool;$i++) {
			$this->Write($symbolrowheight,'o');
			if (($i % 10) == 0 && $i > 1) {
				$this->Ln($symbolrowheight);
				$this->SetX($x2);
			}
		}
		
		/* $this->Ln($symbolrowheight); */
		$this->SetX($x1);
	
		return $this->GetY();
	}
	
	function Willpower ($max, $current = 0) {
		global $pagewidth;
		global $margin;
		global $headsize;
		global $headrowheight;
		global $symbolsize;
		global $textfont;
		global $textcolour;
		global $symbolrowheight;
	
		$sectionwidth = ($pagewidth - 2 * $margin) / 3;
		$dotheight    = 8;
		
		$x1 = $this->GetX();
		
		$this->SetFont($textfont,'B',$headsize);
		$this->SetTextColor($textcolour[0],$textcolour[1],$textcolour[2]);
		$this->Cell($sectionwidth, $headrowheight, 'Willpower', 0, 0, 'C');
		$xnext = $this->GetX();
		$this->Ln($headrowheight);
	
		/* Max WP - dots */
		$this->Dots($max, $sectionwidth - 5, $x1 + 3, $this->GetY(), 10);
		
		$this->SetXY($x1, $this->GetY() + $dotheight);
		
		/* Current WP - boxes */
		$this->SetFont('ZapfDingbats','',$symbolsize);
		
		if ($current == 0)
			$current = $max;

		$string = "";
		for ($i=1;$i<=10;$i++)
			if ($i > $current && $i <= $max)
				$string .= "6"; /* cross */
			else
				$string .= "o"; /* box */
		
		$linewidth = $this->GetStringWidth($string);
		$centre = $x1 + $sectionwidth/2;
		$x2 = $centre - $linewidth/2;
		
		$this->SetX($x2);
		$this->Write($symbolrowheight,$string); 
			
		$this->Ln($$symbolrowheight);
		$this->SetX($x1);
	
		return $xnext;
	}

	function SingleColumnHeading ($heading) {
		global $pagewidth;
		global $margin;
		global $headsize;
		global $headrowheight;
		global $symbolsize;
		global $textfont;
		global $textcolour;
	
		$sectionwidth = ($pagewidth - 2 * $margin) / 3;
	
		$this->SetTextColor($textcolour[0],$textcolour[1],$textcolour[2]);
		$this->SetFont($textfont,'B',$headsize);
		$this->Cell($sectionwidth, $headrowheight, $heading, 0, 0, 'C');
		$nextcol = $this->GetX();
		$this->Ln();
		
		return $nextcol;
	}
	
	function SingleColumnText ($text) {
	
		global $pagewidth;
		global $margin;
		global $textsize;
		global $textrowheight;
		global $textfont;
		global $textcolour;
	
		$sectionwidth = ($pagewidth - 2 * $margin) / 3;
		$x1 = $this->GetX;
		
		$this->SetTextColor($textcolour[0],$textcolour[1],$textcolour[2]);
		$this->SetFont($textfont,'B',$textsize);
		$this->Write($textrowheight, $text);
		$this->Ln();
		
	}
	function SingleColumnTable ($heading, $data) {
	
		global $pagewidth;
		global $margin;
		global $textsize;
		global $textrowheight;
		global $textfont;
		global $textcolour;
	
		$colwidths = array(23, 23, 20);
		$sectionwidth = ($pagewidth - 2 * $margin) / 3;
		$x1 = $this->GetX();
		
		$xnext = $this->SingleColumnHeading($heading);
	
		$this->SetTextColor($textcolour[0],$textcolour[1],$textcolour[2]);
		$this->SetFont($textfont,'B',$textsize);
		$this->SetDrawColor($textcolour[0],$textcolour[1],$textcolour[2]);
		$this->SetLineWidth(.3);
		
		$this->SetX($x1);
		
		foreach ($data as $item) {
			$this->Cell($colwidths[0], $textrowheight, $item->name,  0,   0, 'L');
			$this->Cell($colwidths[1], $textrowheight, ''         ,  'B', 0, 'L');
			$this->Dots($item->level, $colwidths[2], $this->GetX(),$this->GetY(),5);
			$this->Ln();
			$this->SetX($x1);
		}
		
		return $xnext;
	}
}


class larpcharacter {

	var $name; 
	var $clan;
	var $private_clan;
	var $court;
	var $player;
	var $wordpress_id;
	var $generation;
	var $bloodpool;
	var $willpower;
	var $current_willpower;
	
	function load ($characterID){
		global $wpdb;
		
		$wpdb->show_errors();
		
		/* Basic Character Info */
		$sql = "SELECT chara.name                      cname,
					   chara.character_status_comment  cstat_comment,
					   chara.wordpress_id              wpid,
					   player.name                     pname,
					   court.name                      court,
					   pub_clan.name                   public_clan,
					   priv_clan.name                  private_clan,
					   gen.name						   generation,
                       gen.bloodpool,
                       gen.blood_per_round
                    FROM " . GVLARP_TABLE_PREFIX . "CHARACTER chara,
                         " . GVLARP_TABLE_PREFIX . "PLAYER player,
                         " . GVLARP_TABLE_PREFIX . "COURT court,
                         " . GVLARP_TABLE_PREFIX . "CLAN pub_clan,
                         " . GVLARP_TABLE_PREFIX . "CLAN priv_clan,
						 " . GVLARP_TABLE_PREFIX . "GENERATION gen
                    WHERE chara.PUBLIC_CLAN_ID = pub_clan.ID
                      AND chara.PRIVATE_CLAN_ID = priv_clan.ID
                      AND chara.COURT_ID = court.ID
                      AND chara.PLAYER_ID = player.ID
					  AND chara.GENERATION_ID = gen.ID
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
		$this->bloodpool    = $result[0]->bloodpool;
		
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
		$sql = "SELECT bground.name		background,
					sectors.name		sector,
					charbgnd.comment	comment,
					charbgnd.level		level
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
		$sql = "SELECT merits.NAME		name,
					charmerit.comment	comment,
					charmerit.level		level
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

		
		/* Combo disciplines */
		
		
		
	}
	function getAttributes($group) {
		$result = array();
		if ($group == "")
			return $this->attributes;
		else
			return $this->attributegroups[$group];
	}
	function getAbilities($group) {
		$result = array();
		if ($group == "")
			return array_keys($this->abilities);
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

?>