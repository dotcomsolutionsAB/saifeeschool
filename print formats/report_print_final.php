<?php
// ini_set('display_errors', '1');
require('pdf_js.php');
include ("connect.php");
session_start();
setlocale(LC_MONETARY, 'en_IN');
header('Content-Type: text/html; charset=utf-8');

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
        $this->setY(16);
        $this->SetFont('Arial','B',14);
        $this->Cell(36,5,'',0,0,'C');
        $this->SetTextColor(116,1,34);

        $this->SetFont('Arial','B',14);
        $this->setY(35);
        $text = $GLOBALS['header'];
        $this->Cell(190,5,$text,0,1,'C');

	}

	// Page footer
	function Footer()
	{

	    $this->SetY(-15);
	    // Arial italic 8
	    $this->SetFont('Arial','I',8);
	    // Page number
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

}

//------------------------------------ Define Variables & Fetch Data from Database ----------------------------------

$id_list    = $_REQUEST['id_list'];
$part    = $_REQUEST['part'];
$marks_class 	= $_REQUEST['marks_class'];
$ay_id = $_SESSION['report_year'];

$allowed_classes = [426, 428, 427, 429, 430, 434, 435, 436, 433, 431, 432, 449,457,448,451,450,452,454,453,456,455];
// Class XI & XII
$higher_secondary = array(415,417,416,418,419,422,420,421,423,424,425,426,428,427,429,430,433,431,432,434,435,436,440,439,442,441,458,443,445,444,447,446,462,449,457,448,451,450,452,454,453,456,455);
$class_11_12 = array(440,439,442,441,458,443,445,444,447,446,462,449,457,448,451,450,452,454,453,456,455);
$class_9_10 = array(415,417,416,418,419,422,420,421,423,424,425,426,428,427,429,430,433,431,432,434,435,436);

if($ay_id == 7){
    $half = 'Half Yearly';
    $final = 'Final';
    $GLOBALS['header'] = 'PROGRESS REPORT FOR FINAL TERM (2023 - 2024)';
    if (in_array($marks_class, $allowed_classes)) {
        $GLOBALS['header'] = 'PROGRESS REPORT FOR REHEARSAL (2023 - 2024)';
        $half = 'Selections';
        $final = 'Rehearsal';
    }
}else if($ay_id == 8){
    $half = 'Half Yearly';
    $final = 'Final';
    $GLOBALS['header'] = 'PROGRESS REPORT FOR FINAL TERM (2023 - 2024)';
    if (in_array($marks_class, $allowed_classes)) {
        $GLOBALS['header'] = 'PROGRESS REPORT FOR REHEARSAL (2023 - 2024)';
        $half = 'Selections';
        $final = 'Rehearsal';
    }
}
$pdf = new PDF_AutoPrint('P','mm',array(297,210));
$pdf->SetAutoPageBreak(true, 10);
$pdf->setMargins(10, 10);
$title = "Report Card";
$pdf->SetTitle($title);
$pdf->AliasNbPages();
$pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.php'); // Use a font that supports UTF-8

if($id_list != '' && false)
{
    $id_array = explode(",", $id_list);

    foreach($id_array AS $st_id)
    {

        $sql = "SELECT * FROM `student_2023` WHERE `st_id` = '$st_id'";
        $query = $db->query($sql);
        $row = $query->fetch_assoc();

        $name = $row['st_first_name'].' '.$row['st_last_name'];
        $roll_no = $row['st_roll_no'];
        $class = $row['cg_id'];

        $sql_temp = "SELECT * FROM `class_group` WHERE `cg_id` = '$class'";
        $query_temp = $db->query($sql_temp);
        $row_temp = $query_temp->fetch_assoc();

        $class = $row_temp['cg_name'];

        $pdf->AddPage();

        $pdf->Rect(10,42,190,20);
        $pdf->setY(44);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(20,8,'Name :',0,0,'C');
        $pdf->Cell(110,8,strtoupper($name),0,0,'L');
        $pdf->Cell(20,8,'Roll No :',0,0,'C');
        $pdf->Cell(40,8,$roll_no,0,1,'L');
        $pdf->Cell(20,8,'Class :',0,0,'C');
        $pdf->Cell(110,8,$class,0,0,'L');

        $pdf->setY(59);
        //Table Header
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(90,15,'SUBJECTS',1,0,'C');
        $pdf->setY(65);
        $pdf->setX(100);
        $pdf->Cell(20,5,'Full',1,0,'C');
        $pdf->Cell(40,5,$half,1,0,'C');
        $pdf->Cell(40,5,$final,1,1,'C');
        $pdf->setX(100);
        $pdf->Cell(20,10,'Marks',1,0,'C');
        $pdf->Cell(20,10,'M.O.',1,0,'C');
        $pdf->Cell(20,10,'H.M.',1,0,'C');
        $pdf->Cell(20,10,'M.O.',1,0,'C');
        $pdf->Cell(20,10,'H.M.',1,1,'C');

        for($i=0;$i<13;$i++){
            $pdf->SetFont('Arial','I',10);
            $pdf->Cell(115,6,'',1,0,'L');
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(20,6,'',1,0,'C');
            $pdf->Cell(20,6,'',1,0,'C');
            $pdf->Cell(20,6,'',1,0,'C');
            $pdf->Cell(20,6,'',1,0,'C');
            $pdf->Cell(20,6,'',1,1,'C');
        }

        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(115,8,'Grand Total',1,0,'L');
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(20,8,'','LTB',0,'C');
        $pdf->Cell(20,8,'','TB',0,'C');
        $pdf->Cell(20,8,'','TB',0,'C');
        $pdf->Cell(20,8,'','TB',0,'C');
        $pdf->Cell(20,8,'','RTB',1,'C');

        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(90,8,'Percentage',1,0,'L');
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(100,8,'',1,1,'C');

        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(90,8,'1st Student\'s Total',1,0,'L');
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(100,8,'',1,1,'C');
        $pdf->Cell(190,4,'',0,1,'C');

        $pdf->SetFillColor(200,200,200);
        //Grade Key
        $pdf->setX(160);
        $pdf->SetFont('Arial','I',9);
        $pdf->Cell(40,4,'Key',1,2,'C',1);
        $pdf->Cell(10,4,'A',1,0,'C');
        $pdf->Cell(30,4,'Excellent',1,1,'C');
        $pdf->setX(160);
        $pdf->Cell(10,4,'B',1,0,'C');
        $pdf->Cell(30,4,'Good',1,1,'C');
        $pdf->setX(160);
        $pdf->Cell(10,4,'C',1,0,'C');
        $pdf->Cell(30,4,'Fair',1,1,'C');
        $pdf->setX(160);
        $pdf->Cell(10,4,'D',1,0,'C');
        $pdf->Cell(30,4,'Unsatisfactory',1,1,'C');
        $pdf->setX(160);
        $pdf->Cell(10,4,'E',1,0,'C');
        $pdf->Cell(30,4,'Fail',1,1,'C');
        $pdf->Ln();
        $pdf->Ln();

        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(40,6,'Acting Principal',0,0,'C');
        $pdf->Cell(45,6,'','B',0,'C');
        $pdf->Cell(10,6,'',0,0,'C');
        $pdf->Cell(30,6,'Class Teacher',0,0,'C');
        $pdf->Cell(40,6,'','B',0,'C');
        $pdf->Cell(25,6,'',0,1,'C');

        $pdf->Ln();
        $pdf->Cell(95,6,'',0,0,'C');
        $pdf->Cell(30,6,'Date',0,0,'C');
        $pdf->Cell(40,6,'','B',1,'C');

        $pdf->Ln();
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(190,4,'IMPORTANT',0,1,'L');
        $pdf->SetFont('Arial','I',9);
        $pdf->Cell(190,4,'1. Any student failing two consecutive years in a class will automatically be struck off the School Rolls.',0,1,'L');
        $pdf->Cell(190,4,'2. Any alteration of Marks and remarks as entered in this Report Card will be severely dealt with.',0,1,'L');
        $pdf->Cell(190,4,'3. In order to be promoted a student should secure 40% marks in each of the subjects in all Terms',0,1,'L');
        $pdf->Cell(190,4,'4. Decision on Promotion or otherwise is Final and will not be changed. There is no "Promotion on Trial"',0,1,'L');
        $pdf->Cell(190,4,'5. Report will not be issued if fees are in arrears.',0,1,'L');
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(190,10,'****** This Report card is computer generatred and does not require signature *******',0,1,'C');
    }
}else{
    $id_list = isset($id_list) ? $id_list : '';

    $filter = '';
    if (!empty($id_list)) {
        // Sanitize and format the id_list for the SQL IN clause
        $id_array = explode(',', $id_list);
        $id_array = array_map('intval', $id_array); // Ensure all values are integers to prevent SQL injection
        $id_list = implode(',', $id_array); // Reconstruct the sanitized id list
        $filter = " AND `st_id` IN ($id_list)";
    }

    if ($part == 2) {       
        $sql = "SELECT * FROM `student_2023` WHERE `cg_id` = '$marks_class' AND `st_on_roll` = '1' $filter ORDER BY st_roll_no LIMIT 30,30";
    } else {
        $sql = "SELECT * FROM `student_2023` WHERE `cg_id` = '$marks_class' AND `st_on_roll` = '1' $filter ORDER BY st_roll_no LIMIT 0,30";
    }

    // $sql = "SELECT * FROM `student_2023` WHERE `cg_id` = '$marks_class' AND `st_on_roll` = '1' ORDER BY st_roll_no";
    $query = $db->query($sql);
        while($row = $query->fetch_assoc()){

        $name = $row['st_first_name'].' '.$row['st_last_name'];
        $roll_no = $row['st_roll_no'];
        $cg_id = $row['cg_id'];
        $st_id = $row['st_id'];

        $sql_temp = "SELECT * FROM `class_group` WHERE `cg_id` = '$cg_id'";
        $query_temp = $db->query($sql_temp);
        $row_temp = $query_temp->fetch_assoc();

        $class = $row_temp['cg_name'];

        $pdf->AddPage();

        $pdf->Rect(10,42,190,16);
        $pdf->setY(44);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(20,6,'Name :',0,0,'C');
        $pdf->Cell(110,6,strtoupper($name),0,0,'L');
        $pdf->Cell(20,6,'Roll No :',0,0,'C');
        $pdf->Cell(40,6,$roll_no,0,1,'L');
        $pdf->Cell(20,6,'Class :',0,0,'C');
        $pdf->Cell(110,6,$class,0,0,'L');


        $preprimary = array('374','375','376','377','414');

        if(in_array($cg_id, $preprimary))
        {
            $row_height = 5;

            $pdf->setY(59);

            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(115,$row_height,'WORK HABITS',1,0,'L');
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(25,$row_height,'Always',1,0,'C');
            $pdf->Cell(25,$row_height,'Often',1,0,'C');
            $pdf->Cell(25,$row_height,'Rarely',1,1,'C');

            $sql_temp = "SELECT * FROM `studAttendance` WHERE `st_roll_no` LIKE '$roll_no' AND `term` LIKE '2'";
            $query_temp = $db->query($sql_temp);
            $row_temp = $query_temp->fetch_assoc();

            $attendance = $row_temp['attendance'].' / '.$row_temp['total_days'];

            $sql_temp = "SELECT * FROM `studSubjFullMarks` AS FM, `studSubj` AS SB WHERE FM.subj_id = SB.sub_id AND FM.`cg_id` = '$cg_id' AND SB.category = 'WH' AND `term_id` = '2' ORDER BY SB.serial";
            $query_temp = $db->query($sql_temp);
            while($row_temp = $query_temp->fetch_assoc()){

                $subj_id = $row_temp['subj_id'];

                $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no'";
                $query_master = $db->query($sql_master);
                $row_master = $query_master->fetch_assoc();
                
                $marks = $row_master['marks'];

                $always = '';
                $often = '';
                $rarely = '';

                if($marks == 'A'){
                    $always = "";
                    $x = 135;
                }
                if($marks == 'O'){
                    $often = "";
                    $x = 160;
                }
                if($marks == 'R'){
                    $rarely = "";
                    $x = 185;
                }

                $pdf->SetFont('Arial','I',9);
                $pdf->Cell(115,$row_height,$row_temp['subj_name'],1,0,'L');
                $y = $pdf->getY();
                $pdf->SetFont('DejaVu', '', 9);
                $pdf->Image("../media/misc/checkmark.png",$x,$y,$row_height,$row_height);
                $pdf->Cell(25,$row_height,$always,1,0,'C');
                $pdf->Cell(25,$row_height,$often,1,0,'C');
                $pdf->Cell(25,$row_height,$rarely,1,1,'C');

            }

            $y = $pdf->getY();
            $pdf->SetLineWidth(0.6);
            $pdf->Line(10,$y,200,$y);
            $pdf->SetLineWidth(0.2);
            
            //Table Header
            $pdf->SetFont('Arial','',10);
            $pdf->Cell(100,$row_height,'SUBJECTS',1,0,'C');
            $pdf->Cell(45,$row_height,'Half Yearly',1,0,'C');
            $pdf->Cell(45,$row_height,'Final Term',1,1,'C');

            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(100,$row_height,'DRAWING COLOURING AND CRAFT',1,0,'L');
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(90,$row_height,'GRADE',1,1,'C');

            $sql_temp = "SELECT * FROM `studSubjFullMarks` AS FM, `studSubj` AS SB WHERE FM.subj_id = SB.sub_id AND FM.`cg_id` = '$cg_id' AND SB.category = 'DCC' AND `term_id` = '2' ORDER BY SB.serial";
            $query_temp = $db->query($sql_temp);
            while($row_temp = $query_temp->fetch_assoc()){

                $subj_id = $row_temp['subj_id'];

                $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no' AND `term` = 1";
                $query_master = $db->query($sql_master);
                $row_master = $query_master->fetch_assoc();
                
                $marks = $row_master['marks'];

                $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no' AND `term` = 2";
                $query_master = $db->query($sql_master);
                $row_master = $query_master->fetch_assoc();
                
                $marks_final = $row_master['marks'];

                $pdf->SetFont('Arial','I',9);
                $pdf->Cell(100,$row_height,$row_temp['subj_name'],1,0,'L');
                $pdf->SetFont('Arial','B',9);
                $pdf->Cell(45,$row_height,$marks,1,0,'C');
                $pdf->Cell(45,$row_height,$marks_final,1,1,'C');
            }

            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(100,$row_height,'RECITATION',1,0,'L');
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(90,$row_height,'',1,1,'C');

            $sql_temp = "SELECT * FROM `studSubjFullMarks` AS FM, `studSubj` AS SB WHERE FM.subj_id = SB.sub_id AND FM.`cg_id` = '$cg_id' AND SB.category = 'RC' AND `term_id` = '2' ORDER BY SB.serial";
            $query_temp = $db->query($sql_temp);
            while($row_temp = $query_temp->fetch_assoc()){

                $subj_id = $row_temp['subj_id'];

                $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no' AND `term` = 1";
                $query_master = $db->query($sql_master);
                $row_master = $query_master->fetch_assoc();
                
                $marks = $row_master['marks'];

                $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no' AND `term` = 2";
                $query_master = $db->query($sql_master);
                $row_master = $query_master->fetch_assoc();
                
                $marks_final = $row_master['marks'];

                $pdf->SetFont('Arial','I',9);
                $pdf->Cell(100,$row_height,$row_temp['subj_name'],1,0,'L');
                $pdf->SetFont('Arial','B',9);
                $pdf->Cell(45,$row_height,$marks,1,0,'C');
                $pdf->Cell(45,$row_height,$marks_final,1,1,'C');
            }

            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(100,$row_height,'READING',1,0,'L');
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(90,$row_height,'',1,1,'C');

            $sql_temp = "SELECT * FROM `studSubjFullMarks` AS FM, `studSubj` AS SB WHERE FM.subj_id = SB.sub_id AND FM.`cg_id` = '$cg_id' AND SB.category = 'RD' AND `term_id` = '2' ORDER BY SB.serial";
            $query_temp = $db->query($sql_temp);
            while($row_temp = $query_temp->fetch_assoc()){
                $subj_id = $row_temp['subj_id'];

                $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no' AND `term` = 1";
                $query_master = $db->query($sql_master);
                $row_master = $query_master->fetch_assoc();
                
                $marks = $row_master['marks'];

                $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no' AND `term` = 2";
                $query_master = $db->query($sql_master);
                $row_master = $query_master->fetch_assoc();
                
                $marks_final = $row_master['marks'];

                $pdf->SetFont('Arial','I',9);
                $pdf->Cell(100,$row_height,$row_temp['subj_name'],1,0,'L');
                $pdf->SetFont('Arial','B',9);
                $pdf->Cell(45,$row_height,$marks,1,0,'C');
                $pdf->Cell(45,$row_height,$marks_final,1,1,'C');
            }

            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(100,$row_height,'SPEAKING SKILLS',1,0,'L');
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(90,$row_height,'',1,1,'C');

            $sql_temp = "SELECT * FROM `studSubjFullMarks` AS FM, `studSubj` AS SB WHERE FM.subj_id = SB.sub_id AND FM.`cg_id` = '$cg_id' AND SB.category = 'SP' AND `term_id` = '2' ORDER BY SB.serial";
            $query_temp = $db->query($sql_temp);
            while($row_temp = $query_temp->fetch_assoc()){

                $subj_id = $row_temp['subj_id'];

                $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no' AND `term` = 1";
                $query_master = $db->query($sql_master);
                $row_master = $query_master->fetch_assoc();
                
                $marks = $row_master['marks'];

                $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no' AND `term` = 2";
                $query_master = $db->query($sql_master);
                $row_master = $query_master->fetch_assoc();
                
                $marks_final = $row_master['marks'];

                $pdf->SetFont('Arial','I',9);
                $pdf->Cell(100,$row_height,$row_temp['subj_name'],1,0,'L');
                $pdf->SetFont('Arial','B',9);
                $pdf->Cell(45,$row_height,$marks,1,0,'C');
                $pdf->Cell(45,$row_height,$marks_final,1,1,'C');
            }

            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(100,$row_height,'MATHEMATICAL AND LOGICAL SKILLS',1,0,'L');
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(45,$row_height,'',1,1,'C');

            $sql_temp = "SELECT * FROM `studSubjFullMarks` AS FM, `studSubj` AS SB WHERE FM.subj_id = SB.sub_id AND FM.`cg_id` = '$cg_id' AND SB.category = 'MT' AND `term_id` = '2' ORDER BY SB.serial";
            $query_temp = $db->query($sql_temp);
            while($row_temp = $query_temp->fetch_assoc()){

                $subj_id = $row_temp['subj_id'];

                $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no' AND `term` = 1";
                $query_master = $db->query($sql_master);
                $row_master = $query_master->fetch_assoc();
                
                $marks = $row_master['marks'];

                $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no' AND `term` = 2";
                $query_master = $db->query($sql_master);
                $row_master = $query_master->fetch_assoc();
                
                $marks_final = $row_master['marks'];

                $pdf->SetFont('Arial','I',9);
                $pdf->Cell(100,$row_height,$row_temp['subj_name'],1,0,'L');
                $pdf->SetFont('Arial','B',9);
                $pdf->Cell(45,$row_height,$marks,1,0,'C');
                $pdf->Cell(45,$row_height,$marks_final,1,1,'C');
            }

            if($cg_id == '376' || $cg_id == '377' || $cg_id == '414')
            {
                $pdf->SetFont('Arial','B',9);
                $pdf->Cell(100,$row_height,'INDIAN LANGUAGE',1,0,'L');
                $pdf->SetFont('Arial','B',10);
                $pdf->Cell(90,$row_height,'',1,1,'C');

                $sql_temp = "SELECT * FROM `studSubjFullMarks` AS FM, `studSubj` AS SB WHERE FM.subj_id = SB.sub_id AND FM.`cg_id` = '$cg_id' AND SB.category = 'IL' AND `term_id` = '2' ORDER BY SB.serial";
                $query_temp = $db->query($sql_temp);
                while($row_temp = $query_temp->fetch_assoc()){

                    $subj_id = $row_temp['subj_id'];

                    $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no' AND `term` = 1";
                    $query_master = $db->query($sql_master);
                    $row_master = $query_master->fetch_assoc();
                    
                    $marks = $row_master['marks'];

                    $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no' AND `term` = 2";
                    $query_master = $db->query($sql_master);
                    $row_master = $query_master->fetch_assoc();
                    
                    $marks_final = $row_master['marks'];

                    $pdf->SetFont('Arial','I',9);
                    $pdf->Cell(100,$row_height,$row_temp['subj_name'],1,0,'L');
                    $pdf->SetFont('Arial','B',9);
                    $pdf->Cell(45,$row_height,$marks,1,0,'C');
                    $pdf->Cell(45,$row_height,$marks_final,1,1,'C');
                }
            }

            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(100,$row_height,'WRITTEN WORK IN SUBJECTS',1,0,'L');
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(90,$row_height,'',1,1,'C');

            $sql_temp = "SELECT * FROM `studSubjFullMarks` AS FM, `studSubj` AS SB WHERE FM.subj_id = SB.sub_id AND FM.`cg_id` = '$cg_id' AND SB.category = 'WR' AND `term_id` = '2' ORDER BY SB.serial";
            $query_temp = $db->query($sql_temp);
            while($row_temp = $query_temp->fetch_assoc()){

                $subj_id = $row_temp['subj_id'];

                $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no' AND `term` = 1";
                $query_master = $db->query($sql_master);
                $row_master = $query_master->fetch_assoc();
                
                $marks = $row_master['marks'];

                $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no' AND `term` = 2";
                $query_master = $db->query($sql_master);
                $row_master = $query_master->fetch_assoc();
                
                $marks_final = $row_master['marks'];

                $pdf->SetFont('Arial','I',9);
                $pdf->Cell(100,$row_height,$row_temp['subj_name'],1,0,'L');
                $pdf->SetFont('Arial','B',9);
                $pdf->Cell(45,$row_height,$marks,1,0,'C');
                $pdf->Cell(45,$row_height,$marks_final,1,1,'C');
            }

            

            

            $pdf->SetFillColor(200,200,200);
            $pdf->Cell(65,3,'',0,1,'C');

            // $pdf->Ln();

            $y = $pdf->getY();

            $pdf->SetFont('Arial','B',9);
            // $pdf->Cell(50,6,'CONDUCT & STUDY',1,0,'L');
            // $pdf->Cell(65,6,'Half Yearly',1,1,'C');
            $pdf->SetFont('Arial','',9);
            $pdf->Cell(50,6,'Attendance',1,0,'L');
            $pdf->Cell(65,6,$attendance,1,1,'C');

            $yy = $pdf->gety();
            $pdf->SetFont('Arial','B',9);
            $pdf->SetDrawColor(0,0,0);
            $pdf->Rect(10, $yy+1, 148, 21, 'D');
            $pdf->Cell(50,6,'Remarks',0,1,'L');



            //Grade Key
            $pdf->sety($y);
            $pdf->setX(160);
            $pdf->SetFont('Arial','I',8);
            $pdf->Cell(40,4,'Key',1,2,'C',1);
            $pdf->Cell(10,4,'A',1,0,'C');
            $pdf->Cell(30,4,'Excellent',1,1,'C');
            $pdf->setX(160);
            $pdf->Cell(10,4,'B',1,0,'C');
            $pdf->Cell(30,4,'Good',1,1,'C');
            $pdf->setX(160);
            $pdf->Cell(10,4,'C',1,0,'C');
            $pdf->Cell(30,4,'Fair',1,1,'C');
            $pdf->setX(160);
            $pdf->Cell(10,4,'D',1,0,'C');
            $pdf->Cell(30,4,'Unsatisfactory',1,1,'C');
            $pdf->setX(160);
            $pdf->Cell(10,4,'E',1,0,'C');
            $pdf->Cell(30,4,'Fail',1,1,'C');

            $pdf->Cell(190,7,'',0,1,'C');

            $yy = $pdf->getY();

            $pdf->SetFont('Arial','',9);
            $pdf->Cell(20,4,'Principal',0,0,'L');
            $pdf->Cell(40,4,'','B',0,'C');
            $pdf->Cell(5,4,'',0,0,'C');
            $pdf->Cell(25,4,'Class Teacher',0,0,'L');
            $pdf->Cell(25,4,'','B',0,'C');
            $pdf->Cell(20,4,'',0,0,'C');
            $pdf->Cell(10,4,'Date',0,0,'L');
            $pdf->Cell(35,4,'23rd March 2024','B',0,'C');
            $pdf->Cell(10,4,'',0,1,'C');

            // $pdf->Image("../media/pdf/principal.png",30,$yy-7,40,15);    


            $pdf->SetFont('Arial','B',8);
            $pdf->Cell(190,4,'IMPORTANT',0,1,'L');
            $pdf->SetFont('Arial','I',8);
            $pdf->Cell(190,3,'1. Any student failing two consecutive years in a class will automatically be struck off the School Rolls.',0,1,'L');
            $pdf->Cell(190,3,'2. Any alteration of Marks and remarks as entered in this Report Card will be severely dealt with.',0,1,'L');
            $pdf->Cell(190,3,'3. In order to be promoted a student should secure 40% marks in each of the subjects in all Terms',0,1,'L');
            $pdf->Cell(190,3,'4. Decision on Promotion or otherwise is Final and will not be changed. There is no "Promotion on Trial"',0,1,'L');
            $pdf->Cell(190,3,'5. Report will not be issued if fees are in arrears.',0,1,'L');
            $pdf->SetFont('Arial','B',10);
            // $pdf->Cell(190,8,'****** This Report card is computer generatred and does not require signature *******',0,1,'C');
        }
        else {

            $row_height = 6;

            $pdf->setY(60);
            //Table Header
            $pdf->SetFont('Arial','',10);
            $pdf->Cell(90,15,'SUBJECTS',1,0,'C');
            $pdf->setY(60);
            $pdf->setX(100);
            $pdf->Cell(20,5,'Full',1,0,'C');
            $pdf->Cell(40,5,$half,1,0,'C');
            $pdf->Cell(40,5,$final,1,1,'C');
            $pdf->setX(100);
            if (!in_array($marks_class, $class_11_12)){
                $pdf->Cell(20,10,'Marks',1,0,'C');
                $pdf->Cell(20,10,'M.O.',1,0,'C');
                $pdf->Cell(20,10,'H.M.',1,0,'C');
                $pdf->Cell(20,10,'M.O.',1,0,'C');
                $pdf->Cell(20,10,'H.M.',1,1,'C');
            }
            else{
                $pdf->Cell(20,10,'Marks',1,0,'C');
                $pdf->Cell(20,10,'M.O.',1,0,'C');
                $pdf->Cell(20,10,'Points',1,0,'C');
                $pdf->Cell(20,10,'M.O.',1,0,'C');
                $pdf->Cell(20,10,'Points',1,1,'C');
            }
            $grand_total = 0;
            $grand_fm = 0;
            $grand_hm = 0;

            //English Aggregate
            $eng_total = 0;
            $eng_agg_marks = 0;
            $eng_hm_agg_marks = 0;

            $eng_marks_obtained = 0;
            $ss_marks_obtained = 0;
            $sci_marks_obtained = 0;

            $grand_total_final = 0;
            $grand_fm_final = 0;
            $grand_hm_final = 0;

            //English Aggregate
            $eng_total_final = 0;
            $eng_agg_marks_final = 0;
            $eng_hm_agg_marks_final = 0;

            $eng_marks_obtained_final = 0;
            $ss_marks_obtained_final = 0;
            $sci_marks_obtained_final = 0;

            $sql_temp = "SELECT * FROM `studAttendance` WHERE `st_roll_no` LIKE '$roll_no' AND `term` LIKE '1'";
            $query_temp = $db->query($sql_temp);
            $row_temp = $query_temp->fetch_assoc();

            $attendance = $row_temp['attendance'].' / '.$row_temp['total_days'];

            $sql_temp = "SELECT * FROM `studAttendance` WHERE `st_roll_no` LIKE '$roll_no' AND `term` LIKE '2'";
            $query_temp = $db->query($sql_temp);
            $row_temp = $query_temp->fetch_assoc();

            $attendance_final = $row_temp['attendance'].' / '.$row_temp['total_days'];

            $highest_marks_final_cache = [];
            $highest_marks_cache = [];

            
            $hm_count = 1;
            $sql_temp = "SELECT * FROM `studSubjFullMarks` AS FM, `studSubj` AS SB WHERE FM.subj_id = SB.sub_id AND FM.`cg_id` = '$cg_id'  AND `term_id` = '2' ORDER BY CAST(SB.serial AS unsigned)";
            $query_temp = $db->query($sql_temp);
            while($row_temp = $query_temp->fetch_assoc()){

                $subj_id = $row_temp['subj_id'];

                $class_for_total = $marks_class;
                if($marks_class == '416' || $marks_class == '417'){
                    $class_for_total = '416,417';
                }
                if($marks_class == '418' || $marks_class == '419'){
                    $class_for_total = '418,419';
                }
                if($marks_class == '420' || $marks_class == '421' || $marks_class == '422'){
                    $class_for_total = '420,421,422';
                }
                if($marks_class == '423' || $marks_class == '424' || $marks_class == '425'){
                    $class_for_total = '423,424,425';
                }
                if($marks_class == '427' || $marks_class == '428'){
                    $class_for_total = '427,428';
                }
                if($marks_class == '429' || $marks_class == '430'){
                    $class_for_total = '429,430';
                }
                if($marks_class == '431' || $marks_class == '432' || $marks_class == '433'){
                    $class_for_total = '431,432,433';
                }
                if($marks_class == '434' || $marks_class == '435' || $marks_class == '436'){
                    $class_for_total = '434,435,436';
                }

                // Array to store cached highest marks

                if (!isset($highest_marks_cache[$class_for_total][$subj_id]))
                {
                    // Half Yearly
                    $sql_hm = "SELECT *,  MAX(CAST(`marks` AS UNSIGNED)) AS highest_marks  FROM `studMarks` WHERE `cg_id` IN ($class_for_total) AND `subj_id` = '$subj_id' AND `term` = 1";
                    $query_hm = $db->query($sql_hm);
                    $row_hm = $query_hm->fetch_assoc();

                    $sql_hm_prac = "SELECT *,  MAX(CAST(`prac` AS UNSIGNED)) AS highest_marks  FROM `studMarks` WHERE `cg_id` IN ($class_for_total) AND `subj_id` = '$subj_id' AND `term` = 1";
                    $query_hm_prac = $db->query($sql_hm_prac);
                    $row_hm_prac = $query_hm_prac->fetch_assoc();

                    $highest_marks_cache[$class_for_total][$subj_id] = [
                        'highest_marks' => $row_hm['highest_marks'],
                        'highest_marks_prac' => $row_hm_prac['highest_marks']
                    ];

                }

                // Retrieve highest marks from cache
                $hm = $highest_marks_cache[$class_for_total][$subj_id]['highest_marks'];
                $hm_prac = $highest_marks_cache[$class_for_total][$subj_id]['highest_marks_prac'];

                $sql_sthm = "SELECT `st_roll_no`, SUM(`marks`) AS highest_total_marks FROM `studMarks` WHERE `cg_id` IN ($class_for_total) AND `session` = 7 AND `term` = 1 GROUP BY `st_roll_no` ORDER BY highest_total_marks DESC LIMIT 1";
                $query_sthm = $db->query($sql_sthm);
                $row_sthm = $query_sthm->fetch_assoc();

                $grand_hm = $row_sthm['highest_total_marks'];

                $sql_sthm = "SELECT `st_roll_no`, SUM(`marks`) AS highest_total_marks FROM `studMarks` WHERE `cg_id` IN ($class_for_total) AND `session` = 7 AND `term` = 2 GROUP BY `st_roll_no` ORDER BY highest_total_marks DESC LIMIT 1";
                $query_sthm = $db->query($sql_sthm);
                $row_sthm = $query_sthm->fetch_assoc();

                $grand_hm_final = $row_sthm['highest_total_marks'];

                $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no' AND `term` = 1";
                $query_master = $db->query($sql_master);
                $row_master = $query_master->fetch_assoc();
                
                $marks  = $row_master['marks'];
                $full_marks = '';
                $highest_marks = '';
                $prac   = $row_master['prac'];
                $full_marks_prac = '';
                $highest_marks_prac = '';
                
                if($row_temp['type'] == 'M'){
                    $full_marks = $row_temp['theory'];
                    $full_marks_prac = $row_temp['prac'];
                    if (!in_array($marks_class, $class_11_12)){
                        $highest_marks = $hm;
                    }else{
                        if($marks >= 90)
                            $highest_marks = 1;
                        if($marks >= 80 && $marks <= 89)
                            $highest_marks = 2;
                        if($marks >= 70 && $marks <= 79)
                            $highest_marks = 3;
                        if($marks >= 60 && $marks <= 69)
                            $highest_marks = 4;
                        if($marks >= 50 && $marks <= 59)
                            $highest_marks = 5;
                        if($marks >= 40 && $marks <= 49)
                            $highest_marks = 6;
                        if($marks >= 30 && $marks <= 39)
                            $highest_marks = 7;
                        if($marks >= 25 && $marks <= 29)
                            $highest_marks = 8;
                        if($marks <= 24)
                            $highest_marks = 9;

                        if($row_temp['subj_name'] == 'English I' || $row_temp['subj_name'] == 'English II'){
                            $highest_marks = '';
                        }
                    }
                    $highest_marks_prac = $hm_prac;

                    $grand_total += intval($marks);
                    $grand_fm += intval($full_marks) + intval($full_marks_prac);

                    if($row_temp['subj_name'] == 'English Language' || $row_temp['subj_name'] == 'English Literature'){
                        $eng_total += intval($full_marks);
                        $eng_agg_marks += intval($marks);
                        $eng_hm_agg_marks += intval($highest_marks);
                    }
                }

                if($row_temp['subj_name'] == 'Discipline')
                {
                    $discipline = $marks;
                }else if($row_temp['subj_name'] == 'Application')
                {
                    $application = $marks;
                }


                if (!isset($highest_marks_final_cache[$class_for_total][$subj_id]))
                {
                    // Final Yearly
                    $sql_hm = "SELECT *,  MAX(CAST(`marks` AS UNSIGNED)) AS highest_marks  FROM `studMarks` WHERE `cg_id` IN ($class_for_total) AND `subj_id` = '$subj_id' AND `term` = 2";
                    $query_hm = $db->query($sql_hm);
                    $row_hm = $query_hm->fetch_assoc();

                    $sql_hm_prac = "SELECT *,  MAX(CAST(`prac` AS UNSIGNED)) AS highest_marks  FROM `studMarks` WHERE `cg_id` IN ($class_for_total) AND `subj_id` = '$subj_id' AND `term` = 2";
                    $query_hm_prac = $db->query($sql_hm_prac);
                    $row_hm_prac = $query_hm_prac->fetch_assoc();

                    $highest_marks_final_cache[$class_for_total][$subj_id] = [
                        'highest_marks' => $row_hm['highest_marks'],
                        'highest_marks_prac' => $row_hm_prac['highest_marks']
                    ];
                }

                // Retrieve highest marks from cache
                $hm_final = $highest_marks_final_cache[$class_for_total][$subj_id]['highest_marks'];
                $hm_prac_final = $highest_marks_final_cache[$class_for_total][$subj_id]['highest_marks_prac'];

                $sql_sthm = "SELECT `st_roll_no`, SUM(`marks`) AS highest_total_marks FROM `studMarks` WHERE `cg_id` IN ($class_for_total) AND `session` = 7 AND `term` = 2 GROUP BY `st_roll_no` ORDER BY highest_total_marks DESC LIMIT 1";
                $query_sthm = $db->query($sql_sthm);
                $row_sthm = $query_sthm->fetch_assoc();

                $grand_hm = $row_sthm['highest_total_marks'];

                $sql_master = "SELECT * FROM `studMarks` WHERE `cg_id` = '$marks_class' AND `subj_id` = '$subj_id' AND `st_roll_no` = '$roll_no' AND `term` = 2";
                $query_master = $db->query($sql_master);
                $row_master = $query_master->fetch_assoc();
                
                $marks_final  = $row_master['marks'];
                $full_marks_final = '';
                $highest_marks_final = '';
                $prac_final   = $row_master['prac'];
                $full_marks_prac_final = '';
                $highest_marks_prac_final = '';
                
                if($row_temp['type'] == 'M'){
                    $full_marks_final = $row_temp['theory'];
                    $full_marks_prac_final = $row_temp['prac'];
                    if (!in_array($marks_class, $class_11_12)){
                        $highest_marks_final = $hm_final;
                    }else{
                        if($marks_final >= 90)
                            $highest_marks_final = 1;
                        if($marks_final >= 80 && $marks_final <= 89)
                            $highest_marks_final = 2;
                        if($marks_final >= 70 && $marks_final <= 79)
                            $highest_marks_final = 3;
                        if($marks_final >= 60 && $marks_final <= 69)
                            $highest_marks_final = 4;
                        if($marks_final >= 50 && $marks_final <= 59)
                            $highest_marks_final = 5;
                        if($marks_final >= 40 && $marks_final <= 49)
                            $highest_marks_final = 6;
                        if($marks_final >= 30 && $marks_final <= 39)
                            $highest_marks_final = 7;
                        if($marks_final >= 25 && $marks_final <= 29)
                            $highest_marks_final = 8;
                        if($marks_final <= 24)
                            $highest_marks_final = 9;

                        if($row_temp['subj_name'] == 'English I' || $row_temp['subj_name'] == 'English II'){
                            $highest_marks_final = '';
                        }
                    }
                    $highest_marks_prac_final = $hm_prac_final;

                    $grand_total_final += intval($marks_final);
                    $grand_fm_final += intval($full_marks_final) + intval($full_marks_prac_final);

                    if($row_temp['subj_name'] == 'English Language' || $row_temp['subj_name'] == 'English Literature'){
                        $eng_total_final += intval($full_marks_final);
                        $eng_agg_marks_final += intval($marks_final);
                        $eng_hm_agg_marks_final += intval($highest_marks_final);
                    }
                }

                if($row_temp['subj_name'] == 'Discipline')
                {
                    $discipline_final = $marks_final;
                }else if($row_temp['subj_name'] == 'Application')
                {
                    $application_final = $marks_final;
                }else{

                    if($prac == '')
                    {
                        if(($marks_class == '444' || $marks_class == '453') && $row_temp['subj_name'] == 'Art')
                        {
                            if($marks > 0)
                            {
                                $pdf->SetFont('Arial','I',10);
                                $pdf->Cell(90,$row_height,$row_temp['subj_name'],1,0,'L');
                                $pdf->SetFont('Arial','B',10);
                                $pdf->Cell(20,$row_height,$full_marks,1,0,'C');
                                $pdf->Cell(20,$row_height,$marks,1,0,'C');
                                $pdf->Cell(20,$row_height,$highest_marks,1,0,'C');
                                $pdf->Cell(20,$row_height,$marks_final,1,0,'C');
                                $pdf->Cell(20,$row_height,$highest_marks_final,1,1,'C');
                            }
                        }
                        else{
                            $pdf->SetFont('Arial','I',10);
                            $pdf->Cell(90,$row_height,$row_temp['subj_name'],1,0,'L');
                            $pdf->SetFont('Arial','B',10);
                            $pdf->Cell(20,$row_height,$full_marks,1,0,'C');
                            $pdf->Cell(20,$row_height,$marks,1,0,'C');
                            $pdf->Cell(20,$row_height,$highest_marks,1,0,'C');
                            $pdf->Cell(20,$row_height,$marks_final,1,0,'C');
                            $pdf->Cell(20,$row_height,$highest_marks_final,1,1,'C');
                        }
                    }else{
                        if(($marks_class == '444' || $marks_class == '453') && $row_temp['subj_name'] == 'Biology')
                        {
                            if($marks > 0)
                            {
                                $pdf->SetFont('Arial','I',10);
                                $pdf->Cell(90,$row_height,$row_temp['subj_name'].' (Theory)',1,0,'L');
                                $pdf->SetFont('Arial','B',10);
                                $pdf->Cell(20,$row_height,$full_marks,1,0,'C');
                                $pdf->Cell(20,$row_height,$marks,1,0,'C');
                                $pdf->Cell(20,$row_height,'',1,0,'C');
                                $pdf->Cell(20,$row_height,$marks_final,1,0,'C');
                                $pdf->Cell(20,$row_height,'',1,1,'C');

                                $pdf->SetFont('Arial','I',10);
                                $pdf->Cell(90,$row_height,$row_temp['subj_name'].' (Practical)',1,0,'L');
                                $pdf->SetFont('Arial','B',10);
                                $pdf->Cell(20,$row_height,$full_marks_prac,1,0,'C');
                                $pdf->Cell(20,$row_height,$prac,1,0,'C');
                                $pdf->Cell(20,$row_height,'',1,0,'C');
                                $pdf->Cell(20,$row_height,$prac_final,1,0,'C');
                                $pdf->Cell(20,$row_height,'',1,1,'C');

                                $temp_fm = $full_marks + $full_marks_prac;
                                $temp_marks = $marks + $prac;
                                $temp_h_marks = $highest_marks + $highest_marks_prac;

                                $temp_fm_final = $full_marks_final + $full_marks_prac_final;
                                $temp_marks_final = $marks_final + $prac_final;
                                $temp_h_marks_final = $highest_marks_final + $highest_marks_prac_final;

                                if (in_array($marks_class, $class_11_12)){
                                    if($temp_marks >= 90)
                                        $temp_h_marks = 1;
                                    if($temp_marks >= 80 && $temp_marks <= 89)
                                        $temp_h_marks = 2;
                                    if($temp_marks >= 70 && $temp_marks <= 79)
                                        $temp_h_marks = 3;
                                    if($temp_marks >= 60 && $temp_marks <= 69)
                                        $temp_h_marks = 4;
                                    if($temp_marks >= 50 && $temp_marks <= 59)
                                        $temp_h_marks = 5;
                                    if($temp_marks >= 40 && $temp_marks <= 49)
                                        $temp_h_marks = 6;
                                    if($temp_marks >= 30 && $temp_marks <= 39)
                                        $temp_h_marks = 7;
                                    if($temp_marks >= 25 && $temp_marks <= 29)
                                        $temp_h_marks = 8;
                                    if($temp_marks <= 24)
                                        $temp_h_marks = 9;

                                    if($temp_marks_final >= 90)
                                        $temp_h_marks_final = 1;
                                    if($temp_marks_final >= 80 && $temp_marks_final <= 89)
                                        $temp_h_marks_final = 2;
                                    if($temp_marks_final >= 70 && $temp_marks_final <= 79)
                                        $temp_h_marks_final = 3;
                                    if($temp_marks_final >= 60 && $temp_marks_final <= 69)
                                        $temp_h_marks_final = 4;
                                    if($temp_marks_final >= 50 && $temp_marks_final <= 59)
                                        $temp_h_marks_final = 5;
                                    if($temp_marks_final >= 40 && $temp_marks_final <= 49)
                                        $temp_h_marks_final = 6;
                                    if($temp_marks_final >= 30 && $temp_marks_final <= 39)
                                        $temp_h_marks_final = 7;
                                    if($temp_marks_final >= 25 && $temp_marks_final <= 29)
                                        $temp_h_marks_final = 8;
                                    if($temp_marks_final <= 24)
                                        $temp_h_marks_final = 9;
                                }

                                $pdf->SetFont('Arial','I',10);
                                $pdf->Cell(90,$row_height,$row_temp['subj_name'],1,0,'L');
                                $pdf->SetFont('Arial','B',10);
                                $pdf->Cell(20,$row_height,$temp_fm,1,0,'C');
                                $pdf->Cell(20,$row_height,$temp_marks,1,0,'C');
                                $pdf->Cell(20,$row_height,$temp_h_marks,1,0,'C');
                                $pdf->Cell(20,$row_height,$temp_marks_final,1,0,'C');
                                $pdf->Cell(20,$row_height,$temp_h_marks_final,1,1,'C');
                            }
                        }else{
                            $pdf->SetFont('Arial','I',10);
                            $pdf->Cell(90,$row_height,$row_temp['subj_name'].' (Theory)',1,0,'L');
                            $pdf->SetFont('Arial','B',10);
                            $pdf->Cell(20,$row_height,$full_marks,1,0,'C');
                            $pdf->Cell(20,$row_height,$marks,1,0,'C');
                            $pdf->Cell(20,$row_height,'',1,0,'C');
                            $pdf->Cell(20,$row_height,$marks_final,1,0,'C');
                            $pdf->Cell(20,$row_height,'',1,1,'C');

                            $pdf->SetFont('Arial','I',10);
                            $pdf->Cell(90,$row_height,$row_temp['subj_name'].' (Practical)',1,0,'L');
                            $pdf->SetFont('Arial','B',10);
                            $pdf->Cell(20,$row_height,$full_marks_prac,1,0,'C');
                            $pdf->Cell(20,$row_height,$prac,1,0,'C');
                            $pdf->Cell(20,$row_height,'',1,0,'C');
                            $pdf->Cell(20,$row_height,$prac_final,1,0,'C');
                            $pdf->Cell(20,$row_height,'',1,1,'C');

                            $temp_fm = $full_marks + $full_marks_prac;
                            $temp_marks = $marks + $prac;
                            $temp_h_marks = $highest_marks + $highest_marks_prac;

                            $temp_fm_final = $full_marks_final + $full_marks_prac_final;
                            $temp_marks_final = $marks_final + $prac_final;
                            $temp_h_marks_final = $highest_marks_final + $highest_marks_prac_final;

                            if (in_array($marks_class, $class_11_12)){
                                if($temp_marks >= 90)
                                    $temp_h_marks = 1;
                                if($temp_marks >= 80 && $temp_marks <= 89)
                                    $temp_h_marks = 2;
                                if($temp_marks >= 70 && $temp_marks <= 79)
                                    $temp_h_marks = 3;
                                if($temp_marks >= 60 && $temp_marks <= 69)
                                    $temp_h_marks = 4;
                                if($temp_marks >= 50 && $temp_marks <= 59)
                                    $temp_h_marks = 5;
                                if($temp_marks >= 40 && $temp_marks <= 49)
                                    $temp_h_marks = 6;
                                if($temp_marks >= 30 && $temp_marks <= 39)
                                    $temp_h_marks = 7;
                                if($temp_marks >= 25 && $temp_marks <= 29)
                                    $temp_h_marks = 8;
                                if($temp_marks <= 24)
                                    $temp_h_marks = 9;

                                if($temp_marks_final >= 90)
                                    $temp_h_marks_final = 1;
                                if($temp_marks_final >= 80 && $temp_marks_final <= 89)
                                    $temp_h_marks_final = 2;
                                if($temp_marks_final >= 70 && $temp_marks_final <= 79)
                                    $temp_h_marks_final = 3;
                                if($temp_marks_final >= 60 && $temp_marks_final <= 69)
                                    $temp_h_marks_final = 4;
                                if($temp_marks_final >= 50 && $temp_marks_final <= 59)
                                    $temp_h_marks_final = 5;
                                if($temp_marks_final >= 40 && $temp_marks_final <= 49)
                                    $temp_h_marks_final = 6;
                                if($temp_marks_final >= 30 && $temp_marks_final <= 39)
                                    $temp_h_marks_final = 7;
                                if($temp_marks_final >= 25 && $temp_marks_final <= 29)
                                    $temp_h_marks_final = 8;
                                if($temp_marks_final <= 24)
                                    $temp_h_marks_final = 9;
                            }

                            $pdf->SetFont('Arial','I',10);
                            $pdf->Cell(90,$row_height,$row_temp['subj_name'],1,0,'L');
                            $pdf->SetFont('Arial','B',10);
                            $pdf->Cell(20,$row_height,$temp_fm,1,0,'C');
                            $pdf->Cell(20,$row_height,$temp_marks,1,0,'C');
                            $pdf->Cell(20,$row_height,$temp_h_marks,1,0,'C');
                            $pdf->Cell(20,$row_height,$temp_marks_final,1,0,'C');
                            $pdf->Cell(20,$row_height,$temp_h_marks_final,1,1,'C');
                        }
                    }

                    if($row_temp['subj_name'] == 'English Literature'){
                        $pdf->SetFont('Arial','I',10);
                        $pdf->Cell(90,$row_height,'English Aggregate',1,0,'L');
                        $pdf->SetFont('Arial','B',10);
                        $pdf->Cell(20,$row_height,$eng_total,1,0,'C');
                        $pdf->Cell(20,$row_height,$eng_agg_marks,1,0,'C');
                        $pdf->Cell(20,$row_height,$eng_hm_agg_marks,1,0,'C');
                        $pdf->Cell(20,$row_height,$eng_agg_marks_final,1,0,'C');
                        $pdf->Cell(20,$row_height,$eng_hm_agg_marks_final,1,1,'C');

                    }

                    if($row_temp['subj_name'] == 'English I' || $row_temp['subj_name'] == 'English II'){
                        $eng_marks_obtained += intval($marks);
                        $eng_marks_obtained_final += intval($marks_final);
                    }

                    if (in_array($marks_class, $higher_secondary) && $row_temp['subj_name'] == 'English II') {
                        $english_avg = round($eng_marks_obtained / 2);
                        $grand_total += intval($english_avg);

                        $english_avg_final = round($eng_marks_obtained_final / 2);
                        $grand_total_final += intval($english_avg_final);

                        if (in_array($marks_class, $class_11_12)){
                            if($english_avg >= 90)
                                $temp_h_marks = 1;
                            if($english_avg >= 80 && $english_avg <= 89)
                                $temp_h_marks = 2;
                            if($english_avg >= 70 && $english_avg <= 79)
                                $temp_h_marks = 3;
                            if($english_avg >= 60 && $english_avg <= 69)
                                $temp_h_marks = 4;
                            if($english_avg >= 50 && $english_avg <= 59)
                                $temp_h_marks = 5;
                            if($english_avg >= 40 && $english_avg <= 49)
                                $temp_h_marks = 6;
                            if($english_avg >= 30 && $english_avg <= 39)
                                $temp_h_marks = 7;
                            if($english_avg >= 25 && $english_avg <= 29)
                                $temp_h_marks = 8;
                            if($english_avg <= 24)
                                $temp_h_marks = 9;

                            if($english_avg_final >= 90)
                                $temp_h_marks_final = 1;
                            if($english_avg_final >= 80 && $english_avg_final <= 89)
                                $temp_h_marks_final = 2;
                            if($english_avg_final >= 70 && $english_avg_final <= 79)
                                $temp_h_marks_final = 3;
                            if($english_avg_final >= 60 && $english_avg_final <= 69)
                                $temp_h_marks_final = 4;
                            if($english_avg_final >= 50 && $english_avg_final <= 59)
                                $temp_h_marks_final = 5;
                            if($english_avg_final >= 40 && $english_avg_final <= 49)
                                $temp_h_marks_final = 6;
                            if($english_avg_final >= 30 && $english_avg_final <= 39)
                                $temp_h_marks_final = 7;
                            if($english_avg_final >= 25 && $english_avg_final <= 29)
                                $temp_h_marks_final = 8;
                            if($english_avg_final <= 24)
                                $temp_h_marks_final = 9;
                        }
                                
                        $pdf->SetFont('Arial','I',10);
                        $pdf->Cell(90,$row_height,'English Average',1,0,'L');
                        $pdf->SetFont('Arial','B',10);
                        $pdf->Cell(20,$row_height,'100',1,0,'C');
                        $pdf->Cell(20,$row_height,$english_avg,1,0,'C');
                        $pdf->Cell(20,$row_height,$temp_h_marks,1,0,'C');
                        $pdf->Cell(20,$row_height,$english_avg_final,1,0,'C');
                        $pdf->Cell(20,$row_height,$temp_h_marks_final,1,1,'C');
                    }

                    if($row_temp['subj_name'] == 'History' || $row_temp['subj_name'] == 'Geography'){
                        $ss_marks_obtained += intval($marks);
                        $ss_marks_obtained_final += intval($marks_final);
                    }

                    if (in_array($marks_class, $higher_secondary) && $row_temp['subj_name'] == 'Geography') {
                        if (!in_array($marks_class, $class_11_12)){
                            $average = round($ss_marks_obtained / 2);
                            $grand_total += intval($average);

                            $average_final = round($ss_marks_obtained_final / 2);
                            $grand_total_final += intval($average_final);

                            $pdf->SetFont('Arial','I',10);
                            $pdf->Cell(90,$row_height,'History, Civics & Geography Average',1,0,'L');
                            $pdf->SetFont('Arial','B',10);
                            $pdf->Cell(20,$row_height,'100',1,0,'C');
                            $pdf->Cell(20,$row_height,$average,1,0,'C');
                            $pdf->Cell(20,$row_height,'',1,0,'C');
                            $pdf->Cell(20,$row_height,$average_final,1,0,'C');
                            $pdf->Cell(20,$row_height,'',1,1,'C');
                        }
                    }

                    if($row_temp['subj_name'] == 'Physics' || $row_temp['subj_name'] == 'Chemistry' || $row_temp['subj_name'] == 'Biology'){
                        $sci_marks_obtained += intval($marks) + intval($prac);
                        $sci_marks_obtained_final += intval($marks_final) + intval($prac_final);
                    }

                    if (in_array($marks_class, $higher_secondary) && $row_temp['subj_name'] == 'Biology') {
                        if (!in_array($marks_class, $class_11_12)){
                            if($roll_no == 'GS23008')
                                $average = round($sci_marks_obtained / 2);
                            else
                                $average = round($sci_marks_obtained / 3);
                            $grand_total += intval($average);

                            if($roll_no == 'GS23008')
                                $average_final = round($sci_marks_obtained_final / 2);
                            else
                                $average_final = round($sci_marks_obtained_final / 3);
                            $grand_total_final += intval($average_final);

                            $pdf->SetFont('Arial','I',10);
                            $pdf->Cell(90,$row_height,'Science Average',1,0,'L');
                            $pdf->SetFont('Arial','B',10);
                            $pdf->Cell(20,$row_height,'100',1,0,'C');
                            $pdf->Cell(20,$row_height,$average,1,0,'C');
                            $pdf->Cell(20,$row_height,'',1,0,'C');
                            $pdf->Cell(20,$row_height,$average_final,1,0,'C');
                            $pdf->Cell(20,$row_height,'',1,1,'C');
                        }
                    }

                    if($row_temp['subj_name'] == 'G.K. & Moral Sc.'){
                        $y = $pdf->getY();
                        $pdf->SetLineWidth(0.6);
                        $pdf->Line(10,$y,200,$y);
                        $pdf->SetLineWidth(0.2);
                    }
                }

            }

            $percentage = round(($grand_total / $grand_fm * 100),2);
            $percentage_final = round(($grand_total_final / $grand_fm_final * 100),2);

            if (!in_array($marks_class, $class_11_12)){

                if (!in_array($marks_class, $class_9_10)){
                    $pdf->SetFont('Arial','B',10);
                    $pdf->Cell(90,8,'Grand Total',1,0,'L');
                    $pdf->SetFont('Arial','B',10);
                    $pdf->Cell(20,8,'','LTB',0,'C');
                    $pdf->Cell(20,8,$grand_total,'TB',0,'C');
                    $pdf->Cell(20,8,'','TB',0,'C');
                    $pdf->Cell(20,8,$grand_total_final,'TB',0,'C');
                    $pdf->Cell(20,8,'','RTB',1,'C');

                    $pdf->SetFont('Arial','B',10);
                    $pdf->Cell(90,8,'Percentage',1,0,'L');
                    $pdf->SetFont('Arial','B',10);
                    $pdf->Cell(100,8,$percentage_final.'%',1,1,'C');

                    $pdf->SetFont('Arial','B',10);
                    $pdf->Cell(90,8,'1st Student\'s Total',1,0,'L');
                    $pdf->SetFont('Arial','B',10);
                    $pdf->Cell(100,8,$grand_hm_final,1,1,'C');
                    $pdf->Cell(190,4,'',0,1,'C');
                }else{
                    $pdf->Cell(190,3,'',0,1,'L');

                }
            }else{
                $pdf->Cell(190,3,'',0,1,'L');

            }

            $y = $pdf->getY();

            if (!in_array($marks_class, $class_11_12)){

                $pdf->SetFont('Arial','B',9);
                $pdf->Cell(50,6,'',1,0,'L');
                $pdf->Cell(40,6,$half,1,0,'C');
                $pdf->Cell(40,6,$final,1,1,'C');
                $pdf->SetFont('Arial','',9);
                $pdf->Cell(50,6,'Discipline',1,0,'L');
                $pdf->Cell(40,6,$discipline,1,0,'C');
                $pdf->Cell(40,6,$discipline_final,1,1,'C');
                $pdf->SetFont('Arial','',9);
                $pdf->Cell(50,6,'Application',1,0,'L');
                $pdf->Cell(40,6,$application,1,0,'C');
                $pdf->Cell(40,6,$application_final,1,1,'C');
                $pdf->SetFont('Arial','',9);
                $pdf->Cell(50,6,'Attendance',1,0,'L');
                $pdf->Cell(40,6,$attendance,1,0,'C');
                $pdf->Cell(40,6,$attendance_final,1,1,'C');
            }else{
                $pdf->SetFont('Arial','B',9);
                $pdf->Cell(45,6,'',1,0,'L');
                $pdf->Cell(20,6,$half,1,0,'C');
                $pdf->Cell(20,6,$final,1,1,'C');
                $pdf->SetFont('Arial','',9);
                $pdf->Cell(45,6,'Discipline',1,0,'L');
                $pdf->Cell(20,6,$discipline,1,0,'C');
                $pdf->Cell(20,6,$discipline_final,1,1,'C');
                $pdf->SetFont('Arial','',9);
                $pdf->Cell(45,6,'Application',1,0,'L');
                $pdf->Cell(20,6,$application,1,0,'C');
                $pdf->Cell(20,6,$application_final,1,1,'C');
                $pdf->SetFont('Arial','',9);
                $pdf->Cell(45,6,'Attendance',1,0,'L');
                $pdf->Cell(20,6,$attendance,1,0,'C');
                $pdf->Cell(20,6,$attendance_final,1,1,'C');
            }

            if (in_array($marks_class, $class_11_12)){
                $yy = $pdf->gety();
                $pdf->setY($yy + 19);
                $pdf->SetFont('Arial','B',9);
                $pdf->SetDrawColor(0,0,0);
                $pdf->Rect(10, $yy+19, 190, 21, 'D');
                $pdf->Cell(50,6,'Remarks',0,1,'L');
            }else{
                $yy = $pdf->gety();
                $pdf->setY($yy + 1);
                $pdf->SetFont('Arial','B',9);
                $pdf->SetDrawColor(0,0,0);
                $pdf->Rect(10, $yy+1, 190, 21, 'D');
                $pdf->Cell(50,6,'Remarks',0,1,'L');
            }

            $yy += 30;

            $pdf->SetFillColor(200,200,200);
            //Grade Key
            $pdf->sety($y);
            $pdf->setX(160);
            $pdf->SetFont('Arial','I',9);
            $pdf->Cell(40,4,'Key',1,2,'C',1);
            $pdf->Cell(10,4,'A',1,0,'C');
            $pdf->Cell(30,4,'Excellent',1,1,'C');
            $pdf->setX(160);
            $pdf->Cell(10,4,'B',1,0,'C');
            $pdf->Cell(30,4,'V. Good',1,1,'C');
            $pdf->setX(160);
            $pdf->Cell(10,4,'C',1,0,'C');
            $pdf->Cell(30,4,'Good',1,1,'C');
            $pdf->setX(160);
            $pdf->Cell(10,4,'D',1,0,'C');
            $pdf->Cell(30,4,'Unsatisfactory',1,1,'C');
            $pdf->setX(160);
            $pdf->Cell(10,4,'E',1,0,'C');
            $pdf->Cell(30,4,'Needs Improvement',1,1,'C');
            $pdf->Ln();
            $pdf->Ln();

            if (in_array($marks_class, $class_11_12)){

                $pdf->sety($y);
                $pdf->setX(110);
                $pdf->SetFont('Arial','I',9);
                $pdf->Cell(30,4,'Percentage',1,0,'C',1);
                $pdf->Cell(10,4,'Points',1,1,'C',1);
                $pdf->setX(110);
                $pdf->Cell(30,4,'90% - 100%',1,0,'C');
                $pdf->Cell(10,4,'1',1,1,'C');
                $pdf->setX(110);
                $pdf->Cell(30,4,'80% - 89%',1,0,'C');
                $pdf->Cell(10,4,'2',1,1,'C');
                $pdf->setX(110);
                $pdf->Cell(30,4,'70% - 79%',1,0,'C');
                $pdf->Cell(10,4,'3',1,1,'C');
                $pdf->setX(110);
                $pdf->Cell(30,4,'60% - 69%',1,0,'C');
                $pdf->Cell(10,4,'4',1,1,'C');
                $pdf->setX(110);
                $pdf->Cell(30,4,'50% - 59%',1,0,'C');
                $pdf->Cell(10,4,'5',1,1,'C');
                $pdf->setX(110);
                $pdf->Cell(30,4,'40% - 49%',1,0,'C');
                $pdf->Cell(10,4,'6',1,1,'C');
                $pdf->setX(110);
                $pdf->Cell(30,4,'30% - 39%',1,0,'C');
                $pdf->Cell(10,4,'7',1,1,'C');
                $pdf->setX(110);
                $pdf->Cell(30,4,'25% - 29%',1,0,'C');
                $pdf->Cell(10,4,'8',1,1,'C');
                $pdf->setX(110);
                $pdf->Cell(30,4,'Below 25%',1,0,'C');
                $pdf->Cell(10,4,'9',1,1,'C');
                $pdf->Ln();
                $pdf->Ln();
            }

            if (in_array($marks_class, $class_11_12)){
                $yy = 240;
            }else{
                $yy = 240;
            }
            $pdf->sety($yy);
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(40,6,'Principal',0,0,'C');
            $pdf->Cell(45,6,'','B',0,'C');
            $pdf->Cell(10,6,'',0,0,'C');
            $pdf->Cell(30,6,'Class Teacher',0,0,'C');
            $pdf->Cell(40,6,'','B',0,'C');
            $pdf->Cell(25,6,'',0,1,'C');
            $pdf->Cell(25,2,'',0,1,'C');

            // $pdf->Image("../media/pdf/principal.png",48,$yy-8,50,19);    

            $pdf->Cell(95,6,'',0,0,'C');
            $pdf->Cell(30,6,'Date',0,0,'C');
            $pdf->SetFont('Arial','',11);
            if (in_array($marks_class, $class_9_10)){
                $pdf->Cell(40,6,'23rd March 2024','B',1,'C');
            }
            else if (in_array($marks_class, $class_11_12)){
                $pdf->Cell(40,6,'23rd March 2024','B',1,'C');
            }else{
                $pdf->Cell(40,6,'23rd March 2024','B',1,'C');
            }


            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(190,6,'IMPORTANT',0,1,'L');
            $pdf->SetFont('Arial','I',9);
            $pdf->Cell(190,4,'1. Any student failing two consecutive years in a class will automatically be struck off the School Rolls.',0,1,'L');
            $pdf->Cell(190,4,'2. Any alteration of Marks and remarks as entered in this Report Card will be severely dealt with.',0,1,'L');
            $pdf->Cell(190,4,'3. In order to be promoted a student should secure 40% marks in each of the subjects in all Terms',0,1,'L');
            $pdf->Cell(190,4,'4. Decision on Promotion or otherwise is Final and will not be changed. There is no "Promotion on Trial"',0,1,'L');
            $pdf->Cell(190,4,'5. Report will not be issued if fees are in arrears.',0,1,'L');
            $pdf->SetFont('Arial','B',10);
            //$pdf->Cell(190,10,'****** This Report card is computer generated and does not require signature *******',0,1,'C');
        }
    }
}


$name = "Report.pdf";

$pdf->output('I',$name);

?>
