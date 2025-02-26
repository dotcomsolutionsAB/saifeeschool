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
$pdf->SetTitle('Pending Fees');

$pdf->AliasNbPages();


$pdf->setX('10');
$pdf->setY('10');

$ay_id          = $_REQUEST['year'];
$cg_id          = $_REQUEST['class_name'];
$type           = $_REQUEST['type'];
$fee_status     = $_REQUEST['fee_status'];
$one_time_fees  = $_REQUEST['one_time_fees'];
$recurring_fees = $_REQUEST['recurring_fees'];
$due_from       = date('Y-m-d', strtotime($_REQUEST['due_from']));
$due_to         = date('Y-m-d', strtotime($_REQUEST['due_to']));

$students_array = array();

$sql_student = "SELECT * FROM `student`";
$query_student = $db->query($sql_student);
while($row_student = $query_student->fetch_assoc())
{
    $temp = array("st_id"=>$row_student['st_id'],"st_first_name"=>$row_student['st_first_name'],"st_last_name"=>$row_student['st_last_name']);
    $students_array[] = $temp;
}

if($cg_id == ''){
    $cg_id = 'all';
}

$filter_type = "";

if($type != '')
{
    if($type == 0)
    {
        $filter_type = "AND `fp_main_monthly_fee` = '1'";
    }
    if($type == 1)
    {
        $filter_type = "AND `fp_main_admission_fee` = '1'";
    }
    if($type == 2 && $one_time_fees == '')
    {
        $filter_type = "AND `fp_main_admission_fee` = '0' AND `fp_recurring` = '0' AND `fp_main_monthly_fee` = '0'";
    } elseif($type == 2 && $one_time_fees != '') {
        $fp_id = $one_time_fees;
        $filter_type = "AND `fp_main_admission_fee` = '0' AND `fp_recurring` = '0' AND `fp_main_monthly_fee` = '0' AND `fp_id` = '$fp_id'";
    }
    if($type == 3 && $recurring_fees == '')
    {
        $filter_type = "AND `fp_recurring` = '1' AND `fp_main_monthly_fee` = '0'";
    } elseif($type == 3 && $recurring_fees != '') {
        $fp_id = $recurring_fees;
        $filter_type = "AND `fp_recurring` = '1' AND `fp_main_monthly_fee` = '0'";
    }
}

if($fee_status == '' || $fee_status == '0') {
    $filter_fee_status = "AND `f_paid` = '0'";
} elseif ($fee_status == '1') {
    $filter_fee_status = "AND `f_paid` = '1'";
}

if($fp_id == '')
{
    $fp_id = '%';
}

$today_date = strtotime('today');

if($_REQUEST['due_from'] != '' && $_REQUEST['due_to'] == '')
{
    $due_from = strtotime($due_from);
    $date_search = "AND `fpp_due_date` >= '$due_from'";
}
else if($_REQUEST['due_from'] != '' && $_REQUEST['due_to'] != '')
{
    $due_from = strtotime($due_from);
    $due_to = strtotime($due_to);

    $date_search = "AND `fpp_due_date` BETWEEN '$due_from' AND '$due_to'";
}
else if($_REQUEST['due_from'] == '' && $_REQUEST['due_to'] != '')
{
    $due_to = strtotime($due_to);

    $date_search = "AND `fpp_due_date` <= '$due_to'";
}
else 
{
    $date_search = "AND `fpp_due_date` < '$today_date'";
    $date_search = '';

}

if($cg_id != 'all')
{
    // $cg_id_array = explode(',', $cg_id);

    $flag_st_id = '';
    $flag_prev_st_id = '';

    $flag_cg_id = '';
    $flag_prev_cg_id = '';

    $st_fee_count = 1;

    $st_total_amount = 0;
    $class_total_amount = 0;

    $sql_temp = "SELECT * FROM `fee` WHERE `cg_id` IN ($cg_id) AND `ay_id` = '$ay_id' $filter_fee_status $date_search AND `f_active` = '1'  $filter_type ORDER BY `cg_id`,`st_roll_no`";
    $query_temp = $db->query($sql_temp);

    // echo $sql_temp;
    while($row_temp = $query_temp->fetch_assoc())
    {

        $flag_prev_st_id = $flag_st_id;
        $flag_st_id = $row_temp['st_id'];

        if($flag_prev_st_id != '' && $flag_st_id != $flag_prev_st_id){

            if($st_fee_count > 1){

                $pdf->SetFont('Arial','B',8);

                $pdf->Cell(10,5,'','LB',0,C);
                $pdf->CellFitScale(70,5,'','B',0,L);
                $pdf->CellFitScale(25,5,'','B',0,C);
                $pdf->CellFitScale(60,5,'Total:',1,0,C);
                $pdf->CellFitScale(25,5,$st_total_amount,1,1,C);

            }
            $flag = 0;
            $st_total_amount = 0;
            $st_fee_count = 1;
        }

        $flag_prev_cg_id = $flag_cg_id;
        $flag_cg_id = $row_temp['cg_id'];

        if($flag_cg_id != $flag_prev_cg_id){
            $count = 1;

            if($class_total_amount > 0)
            {
                $pdf->SetFont('Arial','B',8);

                $pdf->CellFitScale(165,5,'Class Total:',1,0,C);
                $pdf->CellFitScale(25,5,$class_total_amount,1,1,C);
            }

            $class_total_amount = 0;

            $pdf->AddPage();

            $sql = "SELECT * FROM class_group WHERE cg_id = '$flag_cg_id'";
            $query = $db->query($sql);
            $row = $query->fetch_assoc();

            $class_name = $row['cg_name'];

            $pdf->SetFont('Arial','B',10);
            $pdf->SetFillColor(200,200,200);
            $pdf->SetDrawColor(175,175,175);
            $pdf->Cell(190,6,$class_name.' ('.date('d-m-Y h:i:s A', strtotime('now')).')',1,1,C,1);

            $pdf->SetDrawColor(175,175,175);
            $pdf->SetFont('Arial','B',8);

            $pdf->Cell(10,5,'SN',1,0,C);
            $pdf->CellFitScale(70,5,'Student',1,0,C);
            $pdf->CellFitScale(25,5,'Roll No',1,0,C);
            $pdf->CellFitScale(60,5,'Fee',1,0,C);
            $pdf->CellFitScale(25,5,'Total',1,1,C);
        }

        $key = array_search($flag_st_id, array_column($students_array, 'st_id'));
        $student_name = $students_array[$key]['st_first_name'].' '.$students_array[$key]['st_last_name'];

        $roll_no = $row_temp['st_roll_no'];
        $topic = $row_temp['fpp_name'];
        $due_date = date('d-m-Y',$row_temp['fpp_due_date']);
        $due_date_temp = date('d-m-Y',$row_temp['fpp_due_date']);

        $late_fee_amount = 0;

        $fee_amount = $row_temp['fpp_amount'] - $row_temp['f_concession'];

        if($fee_status == 0)
        {
            if(strtotime('today') > $row_temp['fpp_due_date']){
                $late_fee_amount = $row_temp['fpp_late_fee'];
            }
        }else{
            $late_fee_amount = $row_temp['f_late_fee_paid'];
        }

        $total_amount = $late_fee_amount + $row_temp['fpp_amount'] - $row_temp['f_concession'];

        $st_total_amount += $total_amount;
        $class_total_amount += $total_amount;

        if($flag == 0)
        {
            // $pdf->SetFont('Arial','B',8);

            // $pdf->Cell(10,5,'','LB',0,C);
            // $pdf->CellFitScale(70,5,'','B',0,L);
            // $pdf->CellFitScale(25,5,'','B',0,C);
            // $pdf->CellFitScale(60,5,'Grand Total:',1,0,C);
            // $pdf->CellFitScale(25,5,$final_total_amount,1,1,C);

            $pdf->SetFont('Arial','',8);

            $pdf->Cell(10,5,$count,1,0,C);
            $pdf->CellFitScale(70,5,$student_name,1,0,L);
            $pdf->CellFitScale(25,5,$roll_no,1,0,C);
            $pdf->CellFitScale(60,5,$topic,1,0,C);
            $pdf->CellFitScale(25,5,$total_amount,1,1,C);

            $flag = 1;
            $count++;
        }
        else
        {
            $pdf->Cell(10,5,'','L',0,C);
            $pdf->CellFitScale(70,5,'',0,0,L);
            $pdf->CellFitScale(25,5,'',0,0,C);
            $pdf->CellFitScale(60,5,$topic,1,0,C);
            $pdf->CellFitScale(25,5,$total_amount,1,1,C);

            $st_fee_count++;
        }
    }
}

else if($cg_id == 'all')
{   

    // echo 'here';
    $flag_st_id = '';
    $flag_prev_st_id = '';

    $flag_cg_id = '';
    $flag_prev_cg_id = '';

    $st_fee_count = 1;

    $st_total_amount = 0;
    $class_total_amount = 0;

    $sql_temp = "SELECT * FROM `fee` WHERE `ay_id` = '$ay_id' $filter_fee_status $date_search AND `f_active` = '1'  $filter_type ORDER BY `cg_id`,`st_roll_no`";
    $query_temp = $db->query($sql_temp);
    while($row_temp = $query_temp->fetch_assoc())
    {

        $flag_prev_st_id = $flag_st_id;
        $flag_st_id = $row_temp['st_id'];

        if($flag_prev_st_id != '' && $flag_st_id != $flag_prev_st_id){

            if($st_fee_count > 1){

                $pdf->SetFont('Arial','B',8);

                $pdf->Cell(10,5,'','LB',0,C);
                $pdf->CellFitScale(70,5,'','B',0,L);
                $pdf->CellFitScale(25,5,'','B',0,C);
                $pdf->CellFitScale(60,5,'Total:',1,0,C);
                $pdf->CellFitScale(25,5,$st_total_amount,1,1,C);

            }
            $flag = 0;
            $st_total_amount = 0;
            $st_fee_count = 1;
        }

        $flag_prev_cg_id = $flag_cg_id;
        $flag_cg_id = $row_temp['cg_id'];

        if($flag_cg_id != $flag_prev_cg_id){
            $count = 1;

            if($class_total_amount > 0)
            {
                $pdf->SetFont('Arial','B',8);

                $pdf->CellFitScale(165,5,'Class Total:',1,0,C);
                $pdf->CellFitScale(25,5,$class_total_amount,1,1,C);
            }

            $class_total_amount = 0;

            $pdf->AddPage();

            $sql = "SELECT * FROM class_group WHERE cg_id = '$flag_cg_id'";
            $query = $db->query($sql);
            $row = $query->fetch_assoc();

            $class_name = $row['cg_name'];

            $pdf->SetFont('Arial','B',10);
            $pdf->SetFillColor(200,200,200);
            $pdf->SetDrawColor(175,175,175);
            $pdf->Cell(190,6,$class_name.' ('.date('d-m-Y h:i:s A', strtotime('now')).')',1,1,C,1);

            $pdf->SetDrawColor(175,175,175);
            $pdf->SetFont('Arial','B',8);

            $pdf->Cell(10,5,'SN',1,0,C);
            $pdf->CellFitScale(70,5,'Student',1,0,C);
            $pdf->CellFitScale(25,5,'Roll No',1,0,C);
            $pdf->CellFitScale(60,5,'Fee',1,0,C);
            $pdf->CellFitScale(25,5,'Total',1,1,C);
        }

        $key = array_search($flag_st_id, array_column($students_array, 'st_id'));
        $student_name = $students_array[$key]['st_first_name'].' '.$students_array[$key]['st_last_name'];

        $roll_no = $row_temp['st_roll_no'];
        $topic = $row_temp['fpp_name'];
        $due_date = date('d-m-Y',$row_temp['fpp_due_date']);
        $due_date_temp = date('d-m-Y',$row_temp['fpp_due_date']);

        $late_fee_amount = 0;

        $fee_amount = $row_temp['fpp_amount'] - $row_temp['f_concession'];

        if($fee_status == 0)
        {
            if(strtotime('today') > $due_date_temp){
                $late_fee_amount = $row_temp['fpp_late_fee'];
            }
        }else{
            $late_fee_amount = $row_temp['f_late_fee_paid'];
        }

        $total_amount = $late_fee_amount + $row_temp['fpp_amount'] - $row_temp['f_concession'];

        $st_total_amount += $total_amount;
        $class_total_amount += $total_amount;

        if($flag == 0)
        {
            // $pdf->SetFont('Arial','B',8);

            // $pdf->Cell(10,5,'','LB',0,C);
            // $pdf->CellFitScale(70,5,'','B',0,L);
            // $pdf->CellFitScale(25,5,'','B',0,C);
            // $pdf->CellFitScale(60,5,'Grand Total:',1,0,C);
            // $pdf->CellFitScale(25,5,$final_total_amount,1,1,C);

            $pdf->SetFont('Arial','',8);

            $pdf->Cell(10,5,$count,1,0,C);
            $pdf->CellFitScale(70,5,$student_name,1,0,L);
            $pdf->CellFitScale(25,5,$roll_no,1,0,C);
            $pdf->CellFitScale(60,5,$topic,1,0,C);
            $pdf->CellFitScale(25,5,$total_amount,1,1,C);

            $flag = 1;
            $count++;
        }
        else
        {
            $pdf->Cell(10,5,'','L',0,C);
            $pdf->CellFitScale(70,5,'',0,0,L);
            $pdf->CellFitScale(25,5,'',0,0,C);
            $pdf->CellFitScale(60,5,$topic,1,0,C);
            $pdf->CellFitScale(25,5,$total_amount,1,1,C);

            $st_fee_count++;
        }
    }
}

// $pdf->AddPage();
// $pdf->SetFont('Arial','B',10);
// $pdf->Cell(190,6,$sql_temp,1,0,R);

// $pdf->SetDrawColor(175,175,175);

$name = "Pending_Fees.pdf";

$pdf->output('I',$name);

?>


