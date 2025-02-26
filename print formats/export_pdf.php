<?php

// ini_set("display_errors", 1);

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
        $this->Cell(190,3,'',0,2,C);
    }

    // Page footer
    function Footer()
    {

        // Position at 1.5 cm from bottom
        $this->SetY(-10);
        // Arial italic 8
        $this->SetFont('Arial','I',8);

        
    }

    //Cell with horizontal scaling if text is too wide
    function CellFit($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='', $scale=false, $force=true)
    {
        //Get string width
        $str_width=$this->GetStringWidth($txt);

        //Calculate ratio to fit cell
        if($w==0)
            $w = $this->w-$this->rMargin-$this->x;
        if($str_width != 0)
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

//--------------------------------------------- Define Variables & Fetch Data from Database --------------------------------------

$pdf = new PDF_AutoPrint();
$pdf->SetAutoPageBreak(true, 10);
$pdf->setMargins(10, 10);
$pdf->SetTitle('SGJEPS Student List');

$pdf->AliasNbPages();


$pdf->setX('10');
$pdf->setY('10');

$ay_id          = $_REQUEST['year'];
$cg_id          = $_REQUEST['cg_id'];

$cg_id_filter = '';

if($cg_id != '')
    $cg_id_filter = "AND `cg_id` IN ($cg_id)";

if($ay_id == '')
    $ay_id = 8;

$flag_cg_id = '';
$flag_prev_cg_id = '';

$sql_temp = "SELECT * FROM `student` WHERE `cg_id` IN (SELECT cg_id FROM class_group WHERE ay_id = '$ay_id') $cg_id_filter AND `st_on_roll` = '1' AND `st_external` = '0'  ORDER BY `cg_id`, `st_roll_no`";
$query_temp = $db->query($sql_temp);
while($row_temp = $query_temp->fetch_assoc())
{

    $flag_prev_cg_id = $flag_cg_id;
    $flag_cg_id = $row_temp['cg_id'];

    if($flag_cg_id != $flag_prev_cg_id){
        $count = 1;

        $pdf->AddPage();

        $sql = "SELECT * FROM class_group WHERE cg_id = '$flag_cg_id'";
        $query = $db->query($sql);
        $row = $query->fetch_assoc();

        $class_name = $row['cg_name'];

        $pdf->SetFont('Arial','B',8);

        $pdf->Cell(190,5,'Class : '.$class_name,0,1,C);
        $pdf->Cell(10,5,'SN',1,0,C);
        $pdf->CellFitScale(25,5,'Roll No',1,0,C);
        $pdf->CellFitScale(60,5,'Name',1,0,C);
        $pdf->CellFitScale(25,5,'Mobile',1,0,C);
        $pdf->CellFitScale(15,5,'House',1,0,C);
        $pdf->CellFitScale(55,5,'',1,1,C);
    }

    $student_name = $row_temp['st_first_name'].' '.$row_temp['st_last_name'];

    $roll_no = $row_temp['st_roll_no'];
    $mobile = $row_temp['st_mobile_no'];
    $house = $row_temp['st_house'];

    $pdf->SetFont('Arial','',8);

    $pdf->Cell(10,5,$count,1,0,C);
    $pdf->CellFitScale(25,5,$roll_no,1,0,C);
    $pdf->CellFitScale(60,5,ucwords(strtolower($student_name)),1,0,L);
    $pdf->CellFitScale(25,5,$mobile,1,0,C);
    $pdf->CellFitScale(15,5,$house,1,0,C);
    $pdf->CellFitScale(55,5,"",1,1,C);

    $count++;

}

$name = "Students_List.pdf";

$pdf->output('I',$name);

?>


