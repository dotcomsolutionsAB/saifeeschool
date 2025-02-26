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
		$this->Image("../media/pdf/debit_voucher.jpg",0,0,210,148);	
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
$title = "Expense";
$pdf->SetTitle($title);
$pdf->AliasNbPages();

$sql = "SELECT * FROM expense WHERE id = '$id'";
$query = $db->query($sql);
$row = $query->fetch_assoc();

$pdf->AddPage();

$date 			= date('d-m-Y', strtotime($row['date']));
$amount 		= money_format('%!i', $row['amount']);
$expense_no 	= $row['expense_no'];

$paid_to 		= $row['paid_to'];
$cheque_no 		= $row['cheque_no'];
$description 	= $row['description'];

$number = $row['amount'];

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


$pdf->setXY(23,34);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(70,7,$amount,0,0,L);

$pdf->setXY(190,17);
$pdf->Cell(15,7,$expense_no,'B',0,C);

$pdf->setXY(163,34);
$pdf->Cell(70,7,$date,0,0,L);

$pdf->setXY(30,61);
$pdf->Cell(70,7,$paid_to,0,0,L);

$pdf->setXY(74,74);
$pdf->Cell(70,7,$cheque_no,0,0,L);

$pdf->setXY(50,87);
$pdf->CellFitScale(125,7,$description,0,0,L);

$pdf->setXY(54,108);
$res= "( Rupees ".ucwords(trim($result))." Only )";
$pdf->CellFitScale(125,7,$res,0,1,L);



//--------------------------------------------- Terms & Conditions Block ---------------------------------------------------

$name = "Expense.pdf";

$pdf->output('I',$name);

?>
