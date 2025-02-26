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
		$this->Image("../media/pdf/sgjeps_receipt.jpg",0,0,210,148);	
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

}

//------------------------------------ Define Variables & Fetch Data from Database ----------------------------------

$id 	= $_REQUEST['id'];

$pdf = new PDF_AutoPrint('L','mm',array(210,148));
$pdf->SetAutoPageBreak(true, 5);
$pdf->setMargins(6, 6);
$title = "Receipt";
$pdf->SetTitle($title);
$pdf->AliasNbPages();

$sql_check = "SELECT COUNT(*) AS total FROM `exercise_books` WHERE `f_id` = '$id'";
$query_check = $db->query($sql_check);
$row_check = $query_check->fetch_assoc();

$marker = "ORIGINAL";
if($row_check['total'] > 0)
    $marker = "DUPLICATE";

$sql_log = "INSERT INTO `exercise_books` (`f_id`) VALUES ('$id')";
$query_log = $db->query($sql_log);

$sql = "SELECT * FROM `fee` WHERE `f_id` = '$id'";
$query = $db->query($sql);
$row = $query->fetch_assoc();

$pdf->AddPage();

$date 			= date('d-m-Y', $row['f_paid_date']);
$amount 		= money_format('%!i', $row['f_total_paid']);
$st_id          = $row['st_id'];

$sql_st = "SELECT * FROM `student` WHERE `st_id` = '$st_id'";
$query_st = $db->query($sql_st);
$row_st = $query_st->fetch_assoc();

$number = $row['f_total_paid'];

$no = round($number);
$point = round($number - $no, 2) * 100;
$hundred = null;
$digits_1 = strlen($no);
$i = 0;
$str = array();
$words = array('0' => '', '1' => 'one', '2' => 'two',
'3' => 'three', '4' => 'four', '5' => 'five', '6' => 'six',
'7' => 'seven', '8' => 'eight', '9' => 'nine',
'10' => 'ten', '11' => 'eleven', '12' => 'twelve',
'13' => 'thirteen', '14' => 'fourteen',
'15' => 'fifteen', '16' => 'sixteen', '17' => 'seventeen',
'18' => 'eighteen', '19' =>'nineteen', '20' => 'twenty',
'30' => 'thirty', '40' => 'forty', '50' => 'fifty',
'60' => 'sixty', '70' => 'seventy',
'80' => 'eighty', '90' => 'ninety');
$digits = array('', 'hundred', 'thousand', 'lakh', 'crore');
while ($i < $digits_1) {
 $divider = ($i == 2) ? 10 : 100;
 $number = floor($no % $divider);
 $no = floor($no / $divider);
 $i += ($divider == 10) ? 1 : 2;
 if ($number) {
    $plural = (($counter = count($str)) && $number > 9) ? '' : null;
    $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
    $str [] = ($number < 21) ? $words[$number] .
        " " . $digits[$counter] . $plural . " " . $hundred
        :
        $words[floor($number / 10) * 10]
        . " " . $words[$number % 10] . " "
        . $digits[$counter] . $plural . " " . $hundred;
 } else $str[] = null;
}
$str = array_reverse($str);
$result = implode('', $str);
$points = ($point) ?
"." . $words[$point / 10] . " " . 
      $words[$point = $point % 10] : '';

$pdf->setXY(20,5);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(70,7,$id,0,0,L);

$pdf->setXY(160,45);
$pdf->SetFont('Arial','',12);
$pdf->Cell(70,7,$date,0,0,L);

$name = $row_st['st_first_name'].' '.$row_st['st_last_name'];
$pdf->setXY(75,56);
$pdf->SetFont('Arial','',12);
$pdf->Cell(70,7,$name,0,0,L);

$pdf->setXY(50,69);
$pdf->SetFont('Arial','',12);
$pdf->Cell(70,7,$row_st['st_roll_no'],0,0,L);

$cg_id = $row_st['cg_id'];
$sql_cg = "SELECT * FROM `class_group` WHERE `cg_id` = '$cg_id'";
$query_cg = $db->query($sql_cg);
$row_cg = $query_cg->fetch_assoc();

$pdf->setXY(130,69);
$pdf->SetFont('Arial','',12);
$pdf->Cell(70,7,$row_cg['cg_name'],0,0,L);

$pdf->setXY(60,81);
$pdf->SetFont('Arial','',12);
$pdf->Cell(70,7,$row['fpp_name'],0,0,L);

$res= ucwords(trim($result))." Only ";
$pdf->setXY(60,93);
$pdf->SetFont('Arial','',12);
$pdf->Cell(70,7,$res,0,0,L);

$pdf->setXY(25,122);
$pdf->SetFont('Arial','B',24);
$pdf->Cell(40,7,$amount,0,0,C);

$pdf->setXY(160,122);
$pdf->SetFont('Arial','B',16);
$pdf->Cell(40,7,$marker,1,0,C);

//--------------------------------------------- Terms & Conditions Block ---------------------------------------------------

$name = $row_st['st_roll_no']."_Receipt.pdf";

$pdf->output('D',$name);

?>
