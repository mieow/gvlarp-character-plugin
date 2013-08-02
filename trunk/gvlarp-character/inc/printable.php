<?php
require_once GVLARP_CHARACTER_URL . 'lib/fpdf.php';

/* read these from config options or use defaults */
$title       = 'Character Sheet';
$footer      = 'plugin.gvlarp.com';
$titlefont   = 'Arial';
$titlecolour = array(0,0,0); /* RGB BB0506 */
$dividerlinecolor = array(187,05,06); /* RGB */
$dividertextcolor = array(187,05,06); /* RGB */
$dividerlinewidth = 0.5;

/* Not read from config options */
$titlesize     = 16;
$textfont      = 'Arial';
$textcolour    = array(0,0,0); /* RGB */
$textsize      = 9; /* points */
$textrowheight = 5; /* mm */
$margin        = 5;
$pagewidth     = 210;
$headsize      = 10;
$headrowheight = 10;
$dividertextsize  = 12;
$dividerrowheight = 9;

$dotmaximum = 5;  /* get this from character */
			
function gv_print_redirect()
{
	global $title;
	global $margin;
	global $wpdb;
	global $textsize;
	global $textfont;
	global $textrowheight;

    if( $_SERVER['REQUEST_URI'] == get_stlink_url('printCharSheet') && is_user_logged_in() )
    {
		$character = establishCharacter('');
		$characterID = establishCharacterID($character);
		$mycharacter = new larpcharacter();
		$mycharacter->load($characterID);
		
		if (class_exists('FPDF')) {
		
			$dotmaximum = $mycharacter->max_rating;

			$pdf = new PDFcsheet('P','mm','A4');
			$pdf->LoadOptions();
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
						$physical[$i]->name,stripslashes($physical[$i]->specialty),$physical[$i]->level,
						$social[$i]->name,stripslashes($social[$i]->specialty),$social[$i]->level,
						$mental[$i]->name,stripslashes($mental[$i]->specialty),$mental[$i]->level,
						);
				if ($i==0)
					array_push($data, "Physical", "Social", "Mental");
					
				$pdf->FullWidthTableRow($data);
			}
			
			$pdf->Divider('Abilities');

			$talent    = $mycharacter->getAbilities("Talents");
			$skill     = $mycharacter->getAbilities("Skills");
			$knowledge = $mycharacter->getAbilities("Knowledges");
			
			$abilrows = 3;
			if ($abilrows < count($talent)) $abilrows = count($talent);
			if ($abilrows < count($skill)) $abilrows = count($skill);
			if ($abilrows < count($knowledge)) $abilrows = count($knowledge);
			
			for ($i=0;$i<$abilrows;$i++) {
				$data = array(
						$talent[$i]->skillname,stripslashes($talent[$i]->specialty),$talent[$i]->level,
						$skill[$i]->skillname,stripslashes($skill[$i]->specialty),$skill[$i]->level,
						$knowledge[$i]->skillname,stripslashes($knowledge[$i]->specialty),$knowledge[$i]->level
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
			
			$rows = 3;
			if ($rows < count($backgrounds)) $rows = count($backgrounds);
			if ($rows < count($disciplines)) $rows = count($disciplines);
			if ($rows < count($secondary)) $rows = count($secondary);
			
			for ($i=0;$i<$rows;$i++) {
				$data = array (
					$backgrounds[$i]->background,
					(!empty($backgrounds[$i]->sector)) ?  $backgrounds[$i]->sector : stripslashes($backgrounds[$i]->comment),
					$backgrounds[$i]->level,
					
					$disciplines[$i]->name,
					"",
					$disciplines[$i]->level,
					
					$secondary[$i]->skillname,
					stripslashes($secondary[$i]->specialty),
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
				$merits = $mycharacter->meritsandflaws;
				if (count($merits) > 0) {
					$pdf->SingleColumnHeading("Merits and Flaws");
					
					foreach ($merits as $merit) {
						$string = $merit->name;
						if (!empty($merit->comment))
							$string .= " - " . stripslashes($merit->comment);
						$string .= " (" . $merit->level . ")";
						$pdf->SingleColumnText($string);
					}
				}
				
				$xnext = $pdf->SingleColumnHeading('Current Experience');
				$pdf->SingleColumnCell($mycharacter->current_experience);
				
				$ybottom = $pdf->GetY();
			
			/* COLUMN 2 */
				$pdf->SetXY($xnext, $ytop);
				
				/* Humanity */
				$x1 = $xnext;
				$pdf->SingleColumnHeading($mycharacter->path_of_enlightenment);
				$pdf->SetX($x1);
				$pdf->SingleColumnCell($mycharacter->path_rating);
				$pdf->SetX($x1);
				
				/* Willpower */
				$xnext = $pdf->Willpower($mycharacter->willpower,$mycharacter->current_willpower);
				
				/* Bloodpool */
				$pdf->SetX($x1);
				$pdf->BloodPool($mycharacter->bloodpool);
				
				if ($pdf->GetY() > $ybottom) $ybottom = $pdf->GetY();
			
			/* COLUMN 3 */
				/* Virtues */
				$pdf->SetXY($xnext, $ytop);
				$virtues = $mycharacter->getAttributes("Virtue");
				if (count($virtues) > 0)
					$pdf->SingleColumnTable("Virtues", $virtues);
				
				/* Health */
				$pdf->Health();
				
				if ($pdf->GetY() > $ybottom) $ybottom = $pdf->GetY();
			
			
			$pdf->SetXY($xstart, $ybottom);
			
			/* NEXT PAGE */
			$pdf->AddPage();
			
			/* Dates, Sire */
			$pdf->Divider('Character Information');
			$pdf->BasicInfoTableRow( array(
					'Date of Birth', date_i18n(get_option('date_format'),$mycharacter->date_of_birth),
					'Date of Embrace', date_i18n(get_option('date_format'),$mycharacter->date_of_embrace),
					'Sire',          $mycharacter->sire
				)
			);
			$pdf->BasicInfoTableRow( array(
					'Clan Flaw',     $mycharacter->clan_flaw,
					'Site Login',    $mycharacter->wordpress_id,
					'', ''
				)
			);
			$pdf->Ln();
			
			/* Rituals */
			$rituals = $mycharacter->rituals;
			if (count($rituals) > 0) {
				foreach ($rituals as $majikdiscipline => $rituallist) {
				$pdf->Divider($majikdiscipline . ' Rituals');
					foreach ($rituallist as $ritual) {
						$pdf->FullWidthText("(Level " . $ritual[level] . ") " . $ritual[name]);
					} 
				}
			
			}
			
			/* Combo Disciplines */
			$combodisciplines = $mycharacter->combo_disciplines;
			if (count($combodisciplines) > 0) {
				$pdf->Divider('Combo Disciplines');
				foreach ($combodisciplines as $discipline) {
					$pdf->FullWidthText($discipline);
				}
			}
			
			/* Extended backgrounds - backgrounds with full details */
			if (count($backgrounds) > 0) {
				$pdf->Divider('Extended Backgrounds');
				for ($i=0;$i<count($backgrounds);$i++) {
					if (!empty($backgrounds[$i]->sector) || !empty($backgrounds[$i]->comment) || !empty($backgrounds[$i]->detail)) {
						$text = $backgrounds[$i]->background . " " . $backgrounds[$i]->level;
						if (!empty($backgrounds[$i]->sector))  $text .= " (" . $backgrounds[$i]->sector . ")";
						if (!empty($backgrounds[$i]->comment)) $text .= " " . stripslashes($backgrounds[$i]->comment);
					
						$pdf->FullWidthText($text, 'B');
						if (!empty($backgrounds[$i]->detail))  $pdf->FullWidthText(stripslashes($backgrounds[$i]->detail));
						$pdf->Ln($textrowheight/2);
					}
				}
			}
			
			/* Extended backgrounds - merits and flaws with full details */
			if (count($merits) > 0) {
				$pdf->Divider('Extended Merits and Flaws');
				for ($i=0;$i<count($merits);$i++) {
					if (!empty($merits[$i]->comment) || !empty($merits[$i]->detail)) {
						$text = $merits[$i]->name;
						if (!empty($merits[$i]->comment)) $text .= " (" . stripslashes($merits[$i]->comment) . ")";
					
						$pdf->FullWidthText($text, 'B');
						if (!empty($merits[$i]->detail))  $pdf->FullWidthText(stripslashes($merits[$i]->detail));
						$pdf->Ln($textrowheight/2);
					}
				}
			}
			
			/* Output PDF */
			
			$pdf->Output();
			
		} else {
			echo "<p>Class error</p>";
			exit;
		}
    }
}
add_action( 'template_redirect', 'gv_print_redirect' );


function hex2rgb($hex) {
   $hex = str_replace("#", "", $hex);

   if(strlen($hex) == 3) {
      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
   } else {
      $r = hexdec(substr($hex,0,2));
      $g = hexdec(substr($hex,2,2));
      $b = hexdec(substr($hex,4,2));
   }
   $rgb = array($r, $g, $b);
   //return implode(",", $rgb); // returns the rgb values separated by commas
   return $rgb; // returns an array with the rgb values
}



class PDFcsheet extends FPDF
{

	function LoadOptions() {
		global $title;
		global $footer;
		global $titlefont;
		global $titlecolour; 
		global $dividerlinecolor; /* RGB */
		global $dividertextcolor; /* RGB */
		global $dividerlinewidth;
		
		$title     = get_option('gvcharacter_pdf_title');
		$titlefont = get_option('gvcharacter_pdf_titlefont');
		$footer    = get_option('gvcharacter_pdf_footer');
		$dividerlinewidth = get_option('gvcharacter_pdf_divlinewidth');
		
		$titlecolour      = hex2rgb(get_option('gvcharacter_pdf_titlecolour'));
		$dividerlinecolor = hex2rgb(get_option('gvcharacter_pdf_divcolour'));
		$dividertextcolor = hex2rgb(get_option('gvcharacter_pdf_divtextcolour'));
	
	}

	function Header()
	{
		global $title;
		global $titlefont;
		global $titlecolour;
		global $titlesize;

		$this->SetFont($titlefont,'B',$titlesize);
		$this->SetTextColor($titlecolour[0],$titlecolour[1],$titlecolour[2]);
		$this->Cell(0,10,$title,0,1,'C');

		$this->Ln(2);
	}
	/* Page footer */
	function Footer()
	{
		global $textcolour;
		global $footer;
		
		$footerdate = date_i18n(get_option('date_format'));
	
		$this->SetY(-15);
		$this->SetFont('Arial','I',8);
		$this->SetLineWidth(0.3);
		$this->SetTextColor($textcolour[0],$textcolour[1],$textcolour[2]);
		
		$this->Cell(0,10,$footer . ' | Page ' . $this->PageNo().' of {nb} | Printed on ' . $footerdate,'T',0,'C');
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
	
	function FullWidthTableRow($data) {
	
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
			$this->Dots($data[$i+2], $dotswidth, $this->GetX(), $this->GetY(), $dotmaximum, $textrowheight);
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
	
	function Dots ($level, $cellwidth, $xorig, $y, $max = 5, $rowheight, $dotheight = 0) {
	
		$padding = 0;
	
		if ($level > $max) $max = 10;
		if (empty($dotheight)) {
			$dotwidth = ($cellwidth - 2) / $max;
			$dotheight = $dotwidth;
		}
		else
			$dotwidth = 0;
			
		$yoffset = ($rowheight - $dotheight) / 2;
	
		for ($i=1;$i<=$max;$i++) {
			$x = $xorig + $padding + ($i - 1) * ($dotwidth ? $dotwidth : $dotheight);
			if ($i <= $level)
				$this->Image(GVLARP_CHARACTER_URL . "images/fulldot.jpg", $x, $y + $yoffset, $dotwidth, $dotheight);
			else
				$this->Image(GVLARP_CHARACTER_URL . "images/emptydot.jpg", $x, $y + $yoffset, $dotwidth, $dotheight);
		}
	}
	
	function BloodPool ($bloodpool) {
		global $pagewidth;
		global $margin;
		global $headsize;
		global $headrowheight;
		global $textfont;
		global $textcolour;
	
		$sectionwidth = ($pagewidth - 2 * $margin) / 3;
		$xorig = $this->GetX();
		
		$this->SetFont($textfont,'B',$headsize);
		$this->SetTextColor($textcolour[0],$textcolour[1],$textcolour[2]);
		$this->Cell($sectionwidth, $headrowheight, 'Bloodpool', 0, 1, 'C');

		$padding = 0;
	
		$boxwidth = ($sectionwidth - 2) / 10;
		$boxheight = $boxwidth;
	
		$x = $xorig + $padding;
		$xoffset = 0;
		$y = $this->GetY();
		for ($i=1;$i<=$bloodpool;$i++) {
			$this->Image(GVLARP_CHARACTER_URL . "images/box.jpg", $x + $xoffset, $y, $boxwidth, $boxheight);
			if ( ($i % 10) == 0) {
				$xoffset = 0;
				$y = $y + $boxheight;
			} else {
				$xoffset = ($i % 10) * $boxwidth;
			}
		}
			
		$this->SetXY($xorig, $y);
	
		return $this->GetY();
	}
	
	function Willpower ($max, $current = 0) {
		global $pagewidth;
		global $margin;
		global $headsize;
		global $headrowheight;
		global $textfont;
		global $textcolour;
	
		$sectionwidth = ($pagewidth - 2 * $margin) / 3;
		$dotwidth  = ($sectionwidth - 2) / 10;
		$dotheight = $dotwidth;
		$padding = 0;
		
		$x1 = $this->GetX();
		
		$this->SetFont($textfont,'B',$headsize);
		$this->SetTextColor($textcolour[0],$textcolour[1],$textcolour[2]);
		$this->Cell($sectionwidth, $headrowheight, 'Willpower', 0, 0, 'C');
		$xnext = $this->GetX();
		$this->Ln($headrowheight);
	
		/* Max WP - dots */
		$this->Dots($max, $sectionwidth, $x1, $this->GetY(), 10, $dotheight);
		
		$this->SetXY($x1, $this->GetY() + $dotheight + 2);
		
		/* Current WP - boxes */
		$xoffset = 0;
		$y = $this->GetY();
		for ($i=1;$i<=10;$i++) {
			if ($i > $current && $i <= $max)
				$this->Image(GVLARP_CHARACTER_URL . "images/boxcross2.jpg", $x1 + $padding + $xoffset, $y, $dotheight, $dotheight);
			else
				$this->Image(GVLARP_CHARACTER_URL . "images/box.jpg", $x1 + $padding + $xoffset, $y, $dotheight, $dotheight);
			$xoffset = $i * $dotheight;
		}
		

		$this->SetXY($x1, $y + $dotheight);
	
		return $xnext;
	}

	function SingleColumnHeading ($heading) {
		global $pagewidth;
		global $margin;
		global $headsize;
		global $headrowheight;
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
	function SingleColumnCell ($text) {
		global $pagewidth;
		global $margin;
		global $textsize;
		global $textrowheight;
		global $textfont;
		global $textcolour;
		
		$cellpadding = 3;
	
		$sectionwidth = ($pagewidth - 2 * $margin) / 3;
	
		$this->SetTextColor($textcolour[0],$textcolour[1],$textcolour[2]);
		$this->SetDrawColor($textcolour[0],$textcolour[1],$textcolour[2]);
		$this->SetFont($textfont,'B',$textsize);
		$this->SetLineWidth(.3);
		$this->Cell($cellpadding,$textrowheight,'',0,0,'L');
		$this->Cell($sectionwidth - $cellpadding * 2, $textrowheight, $text, 1, 1, 'C');
		
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
		$this->SetFont($textfont,'',$textsize);
		$this->WriteWordWrap($text, $sectionwidth, $textrowheight);
		$this->Ln();
		
	}
	function FullWidthText ($text, $textweight = '' ) {
	
		global $pagewidth;
		global $margin;
		global $textsize;
		global $textrowheight;
		global $textfont;
		global $textcolour;
	
		$sectionwidth = $pagewidth - 2 * $margin;
		$x1 = $this->GetX;
		
		$this->SetTextColor($textcolour[0],$textcolour[1],$textcolour[2]);
		$this->SetFont($textfont,$textweight,$textsize);
		$this->WriteWordWrap($text, $sectionwidth, $textrowheight);
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
			$this->Dots($item->level, $colwidths[2], $this->GetX(),$this->GetY(),5, $textrowheight);
			$this->Ln();
			$this->SetX($x1);
		}
		
		return $xnext;
	}
	
	function Health() {
		global $pagewidth;
		global $margin;
		global $textcolour;
		global $textfont;
		global $textsize;
	
		$x1 = $this->GetX();
		$xnext = $this->SingleColumnHeading('Health');
		$colwidths = array(26, 20, 20);
		$sectionwidth = ($pagewidth - 2 * $margin) / 3;
		$boxwidth = ($sectionwidth - 2) / 10;
		$boxheight = $boxwidth;
		
		$this->SetTextColor($textcolour[0],$textcolour[1],$textcolour[2]);
		$this->SetFont($textfont,'',$textsize);
		
		$data = array( 	array('Bruised', ''),
						array('Hurt',	'(-1)'),
						array('Injured','(-1)'),
						array('Wounded','(-2)'),
						array('Mauled',	'(-2)'),
						array('Crippled','(-5)'),
						array('Incapacitated','')
		);
		
		foreach ($data as $item) {
			$this->SetX($x1);
			$this->Cell($colwidths[0], $boxheight, $item[0],  0,   0, 'R');
			$this->Cell($colwidths[1], $boxheight, $item[1],  0,   0, 'C');
			$this->Image(GVLARP_CHARACTER_URL . "images/box.jpg", $this->GetX(), $this->GetY(), 0, $boxheight);
			$this->Ln();
		}

	
		return $xnext;
	}
	
	function WriteWordWrap ($text, $columnwidth, $rowheight) {
	
		if ($columnwidth < $this->GetStringWidth($text)) {
			$approxcharwidth = $this->GetStringWidth($text) / strlen($text);
			$approxwrapwidth = ($columnwidth - 1) / $approxcharwidth;
			
			$text = wordwrap($text, $approxwrapwidth, "\n  ", true);
		}
		
		$this->Write($rowheight, $text);
	}
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