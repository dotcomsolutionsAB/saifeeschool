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

$id     = $_REQUEST['id'];

$pdf = new PDF_AutoPrint('P','mm',array(297,210));
$pdf->SetAutoPageBreak(true, 1);
$pdf->setMargins(10, 10);
$title = "Transfer Certificate";
$pdf->SetTitle($title);
$pdf->AliasNbPages();

$pdf->AddPage();

$pdf->Image("../media/pdf/tc.jpg",0,0,210,297);    

$sql = "SELECT * FROM `transfer_certificate` WHERE `st_roll_no` = '$id'";
$query = $db->query($sql);
$row = $query->fetch_assoc();

$serial_no = $row['serial_no'];
$registration_no = $row['registration_no'];
$name = $row['name'];
$father_name = $row['father_name'];
$joining_class = $row['joining_class'];
$joining_date = $row['joining_date'];
$leaving_date = $row['leaving_date'];
$character = $row['character'];
$prev_school = $row['prev_school'];
$class = $row['class'];
$stream = $row['stream'];
$date_from = $row['date_from'];
$date_to = $row['date_to'];
$dob = $row['dob'];
$promotion = $row['promotion'];
$dated = date('d-m-Y', strtotime($row['dated']));


$pdf->setXY(35,82);
$pdf->SetFont('Arial','',14);
$pdf->Cell(20,8,$serial_no ,0,0,'C');

$pdf->setXY(172,82);
$pdf->SetFont('Arial','',14);
$pdf->Cell(20,8,$registration_no ,0,0,'C');

$pdf->setXY(50,98);
$pdf->SetFont('Arial','I',14);
$pdf->Cell(140,8,$name ,0,0,'C');

$pdf->setXY(25,108);
$pdf->SetFont('Arial','I',14);
$pdf->Cell(165,8,$father_name ,0,0,'C');

$pdf->setXY(72,119);
$pdf->SetFont('Arial','I',14);
$pdf->Cell(60,8,$joining_date ,0,0,'C');

$pdf->setXY(120,119);
$pdf->SetFont('Arial','I',14);
$pdf->Cell(60,8,$joining_class ,0,0,'C');


$pdf->setXY(160,129);
$pdf->SetFont('Arial','I',14);
$pdf->Cell(30,8,$leaving_date ,0,0,'C');

$pdf->setXY(10,129);
$pdf->SetFont('Arial','I',14);
$pdf->CellFitScale(120,8,$prev_school ,0,0,'C');

$pdf->setXY(20,140);
$pdf->SetFont('Arial','I',14);
$pdf->Cell(30,8,$character ,0,0,'C');

$pdf->setXY(80,151);
$pdf->SetFont('Arial','I',14);
$pdf->Cell(30,8,$class ,0,0,'C');

$pdf->setXY(150,151);
$pdf->SetFont('Arial','I',14);
$pdf->Cell(30,8,$stream ,0,0,'C');

$pdf->setXY(65,161);
$pdf->SetFont('Arial','I',14);
$pdf->Cell(30,8,$date_from ,0,0,'C');

$pdf->setXY(125,161);
$pdf->SetFont('Arial','I',14);
$pdf->Cell(30,8,$date_to ,0,0,'C');

$pdf->setXY(150,192);
$pdf->SetFont('Arial','I',14);
$pdf->Cell(30,8,date('d-m-Y', strtotime($dob)) ,0,0,'C');

$dateInWords = convertDateToWords($dob);

$pdf->setXY(25,203);
$pdf->SetFont('Arial','I',12);
$pdf->CellFitScale(165,8,$dateInWords ,0,0,'C');

$pdf->setXY(50,214);
$pdf->SetFont('Arial','I',14);
$pdf->CellFitScale(50,8,strtoupper($promotion) ,0,0,'C');

$pdf->setXY(23,232);
$pdf->SetFont('Arial','',12);
$pdf->CellFitScale(50,8,$dated ,0,0,'L');

if($row['status'] == '0')
{
    $pdf->setXY(85,280);
    $pdf->SetFont('Arial','',12);
    $pdf->CellFitScale(40,8,'ORIGINAL',1,0,'C');
}else{
    $pdf->setXY(85,280);
    $pdf->SetFont('Arial','',12);
    $pdf->CellFitScale(40,8,'DUPLICATE',1,0,'C');
}

$sql_update = "UPDATE `transfer_certificate` SET `status` = '1' WHERE `st_roll_no` = '$id'";
$query_update = $db->query($sql_update);

$name = "Trasnfer_Certificate.pdf";

$pdf->output('I',$name);

function convertDateToWords($dateString) {
    // Parse the date
    $timestamp = strtotime($dateString);

    // Get the day, month, and year
    $day = date('j', $timestamp);
    $month = date('n', $timestamp); // Adjusted to get month as number
    $year = date('Y', $timestamp);

    // Array of number to word conversion for days
    $days = array(
        1 => 'FIRST', 2 => 'SECOND', 3 => 'THIRD', 4 => 'FOURTH', 5 => 'FIFTH',
        6 => 'SIXTH', 7 => 'SEVENTH', 8 => 'EIGHTH', 9 => 'NINTH', 10 => 'TENTH',
        11 => 'ELEVENTH', 12 => 'TWELFTH', 13 => 'THIRTEENTH', 14 => 'FOURTEENTH',
        15 => 'FIFTEENTH', 16 => 'SIXTEENTH', 17 => 'SEVENTEENTH', 18 => 'EIGHTEENTH',
        19 => 'NINETEENTH', 20 => 'TWENTIETH', 21 => 'TWENTY FIRST', 22 => 'TWENTY SECOND',
        23 => 'TWENTY THIRD', 24 => 'TWENTY FOURTH', 25 => 'TWENTY FIFTH', 26 => 'TWENTY SIXTH',
        27 => 'TWENTY SEVENTH', 28 => 'TWENTY EIGHTH', 29 => 'TWENTY NINTH', 30 => 'THIRTIETH',
        31 => 'THIRTY FIRST'
    );

    // Array of number to word conversion for months
    $months = array(
        'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE', 'JULY', 'AUGUST',
        'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'
    );

    // Convert year to words
    $yearInWords = strtoupper(convertNumberToWords($year));

    // Return the formatted date string
    return $days[$day] . ' DAY OF ' . $months[$month - 1] . ', ' . $yearInWords;
}

function convertNumberToWords($number) {
    // Array of number to word conversion for each digit
    $words = array(
        '', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE',
        'TEN', 'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN',
        'SEVENTEEN', 'EIGHTEEN', 'NINETEEN'
    );

    // Array of number to word conversion for tens
    $tens = array(
        '', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'
    );

    // Handle special cases
    if ($number < 20) {
        return $words[$number];
    } elseif ($number < 100) {
        return $tens[floor($number / 10)] . ' ' . $words[$number % 10];
    } elseif ($number < 1000) {
        $result = $words[floor($number / 100)] . ' HUNDRED';
        if ($number % 100 != 0) {
            $result .= ' AND ' . convertNumberToWords($number % 100);
        }
        return $result;
    } elseif ($number < 1000000) {
        return convertNumberToWords(floor($number / 1000)) . ' THOUSAND ' . convertNumberToWords($number % 1000);
    } else {
        return 'Number out of range';
    }
}




?>
