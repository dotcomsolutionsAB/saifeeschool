<?php
// ini_set('display_errors', '1');
require('pdf_js.php');
include ("connect.php");
session_start();
setlocale(LC_MONETARY, 'en_IN');

class PDF_AutoPrint extends PDF_JavaScript
{
    function AutoPrint($printer='')
    {
        // Open the print dialog
        if($printer)
        {
            $printer = str_replace('\\', '\\\\', $printer);
            $script = "var pp = getPrintParams();";
            $script .= "pp.interactive = pp.constants.interactionLevel.full;";
            $script .= "pp.printerName = '$printer'";
            $script .= "print(pp);";
        }
        else
            $script = 'print(true);';
        $this->IncludeJS($script);
    }
    // Page header
	function Header()
	{
        $this->setY(10);
        $this->SetFont('Arial','B',14);
        $this->SetTextColor(116,1,34);
        $this->Cell(277,5,'SAIFEE GOLDEN JUBILEE ENGLISH PUBLIC SCHOOL',0,1,'C');
        $this->SetTextColor(0,0,0);
        $this->SetFont('Arial','I',9);
        $this->Cell(277,5,'TABULATION SHEET',0,1,'C');
	}

	// Page footer
	function Footer()
	{

	    $this->SetY(-15);
	    // Arial italic 8
	    $this->SetFont('Arial','I',8);
	    // Page number
	    // $this->Cell(0,20,'Page '.$GLOBALS["pages"],0,0,'C');
	}

	//Cell with horizontal scaling if text is too wide
    function CellFit($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='', $scale=false, $force=true)
    {
        //Get string width
        $str_width=$this->GetStringWidth($txt);

        //Calculate ratio to fit cell
        if($w==0)
            $w = $this->w-$this->rMargin-$this->x;
        $ratio = ($w-$this->cMargin*2)/$str_width;

        $fit = ($ratio < 1 || ($ratio > 1 && $force));
        if ($fit)
        {
            if ($scale)
            {
                //Calculate horizontal scaling
                $horiz_scale=$ratio*100.0;
                //Set horizontal scaling
                $this->_out(sprintf('BT %.2F Tz ET',$horiz_scale));
            }
            else
            {
                //Calculate character spacing in points
                $char_space=($w-$this->cMargin*2-$str_width)/max(strlen($txt)-1,1)*$this->k;
                //Set character spacing
                $this->_out(sprintf('BT %.2F Tc ET',$char_space));
            }
            //Override user alignment (since text will fill up cell)
            $align='';
        }

        //Pass on to Cell method
        $this->Cell($w,$h,$txt,$border,$ln,$align,$fill,$link);

        //Reset character spacing/horizontal scaling
        if ($fit)
            $this->_out('BT '.($scale ? '100 Tz' : '0 Tc').' ET');
    }

    //Cell with horizontal scaling only if necessary
    function CellFitScale($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        $this->CellFit($w,$h,$txt,$border,$ln,$align,$fill,$link,true,false);
    }

    //Cell with horizontal scaling always
    function CellFitScaleForce($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        $this->CellFit($w,$h,$txt,$border,$ln,$align,$fill,$link,true,true);
    }

    //Cell with character spacing only if necessary
    function CellFitSpace($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        $this->CellFit($w,$h,$txt,$border,$ln,$align,$fill,$link,false,false);
    }

    //Cell with character spacing always
    function CellFitSpaceForce($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        //Same as calling CellFit directly
        $this->CellFit($w,$h,$txt,$border,$ln,$align,$fill,$link,false,true);
    }

    var $angle=0;

    function Rotate($angle,$x=-1,$y=-1)
    {
        if($x==-1)
            $x=$this->x;
        if($y==-1)
            $y=$this->y;
        if($this->angle!=0)
            $this->_out('Q');
        $this->angle=$angle;
        if($angle!=0)
        {
            $angle*=M_PI/180;
            $c=cos($angle);
            $s=sin($angle);
            $cx=$x*$this->k;
            $cy=($this->h-$y)*$this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy));
        }
    }

    function _endpage()
    {
        if($this->angle!=0)
        {
            $this->angle=0;
            $this->_out('Q');
        }
        parent::_endpage();
    }

    function RotatedText($x,$y,$txt,$angle)
    {
        //Text rotated around its origin

        $this->Rotate($angle,$x,$y);
        $this->SetFont('Arial','',6);
        $this->CellFitScale(40,8,$txt,1);
        $this->Rotate(0);
    }

    function TextWithDirection($x, $y, $txt, $direction='R')
    {
        if ($direction=='R')
            $s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',1,0,0,1,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
        elseif ($direction=='L')
            $s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',-1,0,0,-1,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
        elseif ($direction=='U')
            $s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',0,1,-1,0,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
        elseif ($direction=='D')
            $s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',0,-1,1,0,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
        else
            $s=sprintf('BT %.2F %.2F Td (%s) Tj ET',$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
        if ($this->ColorFlag)
            $s='q '.$this->TextColor.' '.$s.' Q';
        $this->_out($s);
    }

}

//------------------------------------ Define Variables & Fetch Data from Database ----------------------------------

$marks_class 	= $_REQUEST['marks_class'];

$ay_id = $_SESSION['report_year'];
$table = "student";
if($ay_id == 7)
   $table = "student_2023";

$pdf = new PDF_AutoPrint('L','mm',array(297,210));
$pdf->SetAutoPageBreak(true, 10);
$pdf->setMargins(10, 10);
$title = "TABULATION SHEET";
$pdf->SetTitle($title);
$pdf->AliasNbPages();

// Class XI & XII
$higher_secondary = array(415,417,416,418,419,422,420,421,423,424,425,426,428,427,429,430,433,431,432,434,435,436,440,439,442,441,458,443,445,444,447,446,462,449,457,448,451,450,452,454,453,456,455);
$class_11 = array(415,417,416,418,419,422,420,421,423,424,425,426,428,427,429,430,433,431,432,434,435,436,440,439,442,441,458,443,445,444,447,446,462,449,457,448,451,450,452,454,453,456,455);
$class_9_10 = array(415,417,416,418,419,422,420,421,423,424,425,426,428,427,429,430,433,431,432,434,435,436);

$sql_temp = "SELECT * FROM `class_group` WHERE `cg_id` IN ($marks_class)";
$query_temp = $db->query($sql_temp);
while($row_temp = $query_temp->fetch_assoc()){

    $marks_class = $row_temp['cg_id'];

    $class = $row_temp['cg_name'];

    $sql_year = "SELECT * FROM `academic_year` WHERE `ay_id` = '$ay_id'";
    $query_year = $db->query($sql_year);
    $row_year = $query_year->fetch_assoc();

    $sql = "SELECT * FROM `$table` WHERE `st_id` = '$st_id'";
    $query = $db->query($sql);
    $row = $query->fetch_assoc();

    $name = $row['st_first_name'].' '.$row['st_last_name'];
    $roll_no = $row['st_roll_no'];

    $pdf->AddPage();

    $pdf->SetFont('Arial','',12);
    $pdf->Cell(20,8,'Class :',0,0,'L');
    $pdf->Cell(110,8,$class,0,0,'L');
    $pdf->Cell(20,8,'Term :',0,0,'L');
    $pdf->Cell(110,8,$row_year['ay_name'],0,1,'L');

    $subj_array = array();
    $subj_name = array();

    //Table Header
    $pdf->SetFont('Arial','',8);
    $pdf->CellFitScale(7,40,'SN',1,0,'C');
    $pdf->CellFitScale(20,40,'Roll No',1,0,'C');
    $pdf->CellFitScale(40,40,'Name',1,0,'C');

    $x = 81;
    $y = 67;

    // $sql_subj = "SELECT DISTINCT(`subj_name`), `subj_id` FROM `studSubjFullMarks` WHERE `cg_id` = '$marks_class'";
    $sql_subj = "SELECT DISTINCT(`subj_name`), `subj_id`, FM.`prac` FROM `studSubjFullMarks` AS FM, `studSubj` AS SB WHERE FM.subj_id = SB.sub_id AND FM.`cg_id` = '$marks_class' AND `term_id` = '1'  ORDER BY CAST(SB.serial AS unsigned)";
    $query_subj = $db->query($sql_subj);
    while($row_subj = $query_subj->fetch_assoc()){

        $subj = substr($row_subj['subj_name'],0,28);
        $subj_name[] = $row_subj['subj_name'];
        $subj_array[] = $row_subj['subj_id'];

        // $pdf->SetFont('Arial','I',8);
        // $pdf->CellFitScale(8,40,"",1);
        // $pdf->TextWithDirection($x,$y,$subj,'U');

        // $x = $pdf->getX() + 4;



        if($row_subj['prac'] != ''){
            $pdf->SetFont('Arial','I',8);
            $pdf->CellFitScale(8,40,"",1);
            $pdf->TextWithDirection($x,$y,$subj.' - Theory','U');

            $x = $pdf->getX() + 4;

            $pdf->SetFont('Arial','I',8);
            $pdf->CellFitScale(8,40,"",1);
            $pdf->TextWithDirection($x,$y,$subj.' - Practical','U');

            $x = $pdf->getX() + 4;

            $pdf->SetFont('Arial','I',8);
            $pdf->CellFitScale(8,40,"",1);
            $pdf->TextWithDirection($x,$y,$subj.' - Total','U');

            $x = $pdf->getX() + 4;

            $flag[] = 1;

        }else{
            $pdf->SetFont('Arial','I',8);
            $pdf->CellFitScale(8,40,"",1);
            $pdf->TextWithDirection($x,$y,$subj,'U');

            $x = $pdf->getX() + 4;

            $flag[] = 0;
        }

        if (in_array($marks_class, $higher_secondary) && $subj == 'English II') {
            $pdf->SetFont('Arial','I',8);
            $pdf->CellFitScale(8,40,"",1);
            $pdf->TextWithDirection($x,$y,'English Aggregate','U');

            $x = $pdf->getX() + 4;

            $pdf->SetFont('Arial','I',8);
            $pdf->CellFitScale(8,40,"",1);
            $pdf->TextWithDirection($x,$y,'English Avg.','U');

            $x = $pdf->getX() + 4;
        }

        if ( $subj == 'English Literature') {
            $pdf->SetFont('Arial','I',8);
            $pdf->CellFitScale(8,40,"",1);
            $pdf->TextWithDirection($x,$y,'English Aggregate','U');

            $x = $pdf->getX() + 4;
        }
    }

    $pdf->SetFont('Arial','I',8);
    $pdf->CellFitScale(8,40,"",1);
    $pdf->TextWithDirection($x,$y,'Total Marks','U');

    $x = $pdf->getX() + 4;

    $pdf->SetFont('Arial','I',8);
    $pdf->CellFitScale(8,40,"",1);
    $pdf->TextWithDirection($x,$y,'Attendance','U');

    $x = $pdf->getX() + 4;
    $pdf->setY(68);
    $pdf->setX(10);

    $count = 1;

    $sql_stud = "SELECT *
FROM $table
WHERE cg_id = '$marks_class' AND st_on_roll = '1'
ORDER BY CASE
    WHEN st_roll_no REGEXP '^[0-9]+$' THEN CAST(st_roll_no AS UNSIGNED)
    ELSE st_roll_no
END";
    $query_stud = $db->query($sql_stud);
    while($row_stud = $query_stud->fetch_assoc()){

        $grand_total = 0;
        $eng_marks_obtained = 0;
        $eng_aggregate = 0;

        $name = $row_stud['st_first_name'].' '.$row_stud['st_last_name'];
        $st_id = $row_stud['st_id'];
        $st_roll_no = $row_stud['st_roll_no'];

        $pdf->CellFitScale(7,6,$count++,1,0,'C');
        $pdf->CellFitScale(20,6,$st_roll_no,1,0,'C');
        $pdf->CellFitScale(40,6,strtoupper($name),1,0,'L');

        $len = sizeof($subj_array);
        for($i=0;$i<$len;$i++){

            $subj_id = $subj_array[$i];
            $sql_fetch = "SELECT * FROM `studMarks` WHERE `st_roll_no` LIKE '$st_roll_no' AND `subj_id` LIKE '$subj_id' AND `cg_id` LIKE '$marks_class' AND `term` = 2 LIMIT 1";
            $query_fetch = $db->query($sql_fetch);
            $row_fetch = $query_fetch->fetch_assoc();

            $marks = $row_fetch['marks'];
            if($subj_name[$i] != 'English I' && $subj_name[$i] != 'English II'){
                $grand_total += intval($marks);
            }

            if($i==($len-1)){
                $pdf->CellFitScale(8,6,$marks,1,0,'C');
            }else{
                $pdf->CellFitScale(8,6,$marks,1,0,'C');
            }

            $prac = $row_fetch['prac'];

            if($flag[$i] == 1){
                $grand_total += intval($prac);

                if($i==($len-1)){
                    $pdf->CellFitScale(8,6,$prac,1,0,'C');
                }else{
                    $pdf->CellFitScale(8,6,$prac,1,0,'C');
                }

                $temp_total = $marks + $prac;
				if($temp_total == 0)
					$temp_total = '';
                if($i==($len-1)){
                    $pdf->CellFitScale(8,6,$temp_total,1,0,'C');
                }else{
                    $pdf->CellFitScale(8,6,$temp_total,1,0,'C');
                }
            }

            if($subj_name[$i] == 'English I' || $subj_name[$i] == 'English II'){
                $eng_marks_obtained += intval($marks);
            }

            if($subj_name[$i] == 'English Language' || $subj_name[$i] == 'English Literature'){
                $eng_aggregate += intval($marks);
            }

            if (in_array($marks_class, $higher_secondary) && $subj_name[$i] == 'English II') {
                $english_avg = round($eng_marks_obtained / 2);
                if (in_array($marks_class, $class_9_10)){
                    $grand_total += intval($eng_marks_obtained);
                }else{
                    $grand_total += intval($english_avg);
                }
                if($eng_marks_obtained == 0)
					$eng_marks_obtained = '';
                $pdf->CellFitScale(8,6,$eng_marks_obtained,1,0,'C');
				
				if($english_avg == 0)
					$english_avg = '';
                if($i==($len-1)){
                    $pdf->CellFitScale(8,6,$english_avg,1,0,'C');
                }else{
                    $pdf->CellFitScale(8,6,$english_avg,1,0,'C');
                }


            }

            if ($subj_name[$i] == 'English Literature') {
				
				if($eng_aggregate == 0)
					$eng_aggregate = '';
                if($i==($len-1)){
                    $pdf->CellFitScale(8,6,$eng_aggregate,1,0,'C');
                }else{
                    $pdf->CellFitScale(8,6,$eng_aggregate,1,0,'C');
                }
            }
        }
		if($grand_total == 0)
			$grand_total = '';
        $pdf->CellFitScale(8,6,$grand_total,1,0,'C');

        $sql_fetch = "SELECT * FROM `studAttendance` WHERE `st_roll_no` LIKE '$st_roll_no' AND `term` LIKE '2'";
        $query_fetch = $db->query($sql_fetch);
        $row_fetch = $query_fetch->fetch_assoc();

        $attendance = $row_fetch['attendance'];

        $pdf->CellFitScale(8,6,$attendance,1,1,'C');

        $yy = $pdf->getY();
        if($yy >= 190){

            $pdf->AddPage();

            $pdf->SetFont('Arial','',12);
            $pdf->Cell(20,8,'Class :',0,0,'L');
            $pdf->Cell(110,8,$class,0,0,'L');
            $pdf->Cell(20,8,'Term :',0,0,'L');
            $pdf->Cell(110,8,'2023-24',0,1,'L');

            //Table Header
            $pdf->SetFont('Arial','',8);
            $pdf->CellFitScale(7,40,'SN',1,0,'C');
            $pdf->CellFitScale(20,40,'Roll No',1,0,'C');
            $pdf->CellFitScale(40,40,'Name',1,0,'C');

            $x = 81;
            $y = 67;

            $sql_subj = "SELECT DISTINCT(`subj_name`), `subj_id` FROM `studSubjFullMarks` WHERE `cg_id` = '$marks_class'";
            $sql_subj = "SELECT DISTINCT(`subj_name`), `subj_id`, FM.`prac` FROM `studSubjFullMarks` AS FM, `studSubj` AS SB WHERE FM.subj_id = SB.sub_id AND FM.`cg_id` = '$marks_class' AND `term_id` = '2' ORDER BY CAST(SB.serial AS unsigned)";
            $query_subj = $db->query($sql_subj);
            while($row_subj = $query_subj->fetch_assoc()){

                $subj = substr($row_subj['subj_name'],0,28);

                if($row_subj['prac'] != ''){
                    $pdf->SetFont('Arial','I',8);
                    $pdf->CellFitScale(8,40,"",1);
                    $pdf->TextWithDirection($x,$y,$subj.' - Theory','U');

                    $x = $pdf->getX() + 4;

                    $pdf->SetFont('Arial','I',8);
                    $pdf->CellFitScale(8,40,"",1);
                    $pdf->TextWithDirection($x,$y,$subj.' - Practical','U');

                    $x = $pdf->getX() + 4;

                    $pdf->SetFont('Arial','I',8);
                    $pdf->CellFitScale(8,40,"",1);
                    $pdf->TextWithDirection($x,$y,$subj.' - Total','U');

                    $x = $pdf->getX() + 4;
                }else{
                    $pdf->SetFont('Arial','I',8);
                    $pdf->CellFitScale(8,40,"",1);
                    $pdf->TextWithDirection($x,$y,$subj,'U');

                    $x = $pdf->getX() + 4;
                }

                if (in_array($marks_class, $higher_secondary) && $subj == 'English II') {
                    $pdf->SetFont('Arial','I',8);
                    $pdf->CellFitScale(8,40,"",1);
                    $pdf->TextWithDirection($x,$y,'English Aggregate','U');

                    $x = $pdf->getX() + 4;

                    $pdf->SetFont('Arial','I',8);
                    $pdf->CellFitScale(8,40,"",1);
                    $pdf->TextWithDirection($x,$y,'English Avg.','U');

                    $x = $pdf->getX() + 4;
                }

                if ($subj == 'English Literature') {
                    $pdf->SetFont('Arial','I',8);
                    $pdf->CellFitScale(8,40,"",1);
                    $pdf->TextWithDirection($x,$y,'English Aggregate','U');

                    $x = $pdf->getX() + 4;
                }
            }

            $pdf->SetFont('Arial','I',8);
            $pdf->CellFitScale(8,40,"",1);
            $pdf->TextWithDirection($x,$y,'Total Marks','U');

            $x = $pdf->getX() + 4;

            $pdf->SetFont('Arial','I',8);
            $pdf->CellFitScale(8,40,"",1);
            $pdf->TextWithDirection($x,$y,'Attendance','U');

            $pdf->setY(68);
            $pdf->setX(10);

        }

    }
}

$name = "Tabulation.pdf";

$pdf->output('I',$name);

?>
