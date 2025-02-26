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

$id_list     = $_REQUEST['id'];

$pdf = new PDF_AutoPrint('P','mm',array(297,210));
$pdf->SetAutoPageBreak(true, 10);
$pdf->setMargins(10, 10);
$title = "Character Certificate";
$pdf->SetTitle($title);
$pdf->AliasNbPages();

// Convert the comma-separated string to an array
$idArray = explode(',', $id_list);

// Loop through each ID
foreach ($idArray as $id) {

    $sql = "SELECT * FROM `studCC` WHERE id = '$id'";
    $query = $db->query($sql);
    $row = $query->fetch_assoc();

    $pdf->AddPage();

    $pdf->Image("../media/pdf/cc.jpg",0,0,210,297);    

    $sql = "SELECT * FROM `character_certificate` WHERE `id` = '$id'";
    $query = $db->query($sql);
    $row = $query->fetch_assoc();

    $serial_no = $row['serial_no'];
    $registration_no = $row['registration_no'];
    $name = $row['name'];
    $joining_date = $row['joining_date'];
    $leaving_date = $row['leaving_date'];
    $stream = $row['stream'];
    $date_from = $row['date_from'];
    $dob = $row['dob'];
    $dateInWords = convertDateToWords($dob);

    $joining_datetime = new DateTime($joining_date);
    $leaving_datetime = new DateTime($leaving_date);

    // Get the year components of the DateTime objects
    $joining_year = (int)$joining_datetime->format('Y');
    $leaving_year = (int)$leaving_datetime->format('Y');

    // Calculate the difference in years
    $years = $leaving_year - $joining_year;

    // $interval = $joining_datetime->diff($leaving_datetime);

    // // Calculate the interval in years
    // $years = $interval->y;
    // $months = $interval->m;
    // $days = $interval->d;

    // Add 1 if there are any remaining months or days
    // if ($months > 0 || $days > 0) {
    //     $years++;
    // }

    // Function to convert number of years into word

    // Convert the number of years into words
    $years_in_words = yearsInWords($years);

    $pdf->setXY(30,82);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(20,8,$serial_no ,0,0,'C');

    $pdf->setXY(172,82);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(20,8,$registration_no ,0,0,'C');

    $pdf->setXY(50,100);
    $pdf->SetFont('Arial','I',12);
    $pdf->Cell(140,8,$name ,0,0,'C');

    $pdf->setXY(170,109);
    $pdf->SetFont('Arial','I',12);
    $pdf->Cell(30,8,strtoupper($years_in_words) ,0,0,'C');

    $pdf->setXY(35,116);
    $pdf->SetFont('Arial','I',12);
    $pdf->Cell(30,8,date('d-m-Y', strtotime($joining_date)) ,0,0,'C');

    $pdf->setXY(85,116);
    $pdf->SetFont('Arial','I',12);
    $pdf->Cell(30,8,date('d-m-Y', strtotime($leaving_date)) ,0,0,'C');

    $pdf->setXY(50,125);
    $pdf->SetFont('Arial','I',12);
    $pdf->Cell(30,8,$stream ,0,0,'C');

    $pdf->setXY(110,125);
    $pdf->SetFont('Arial','I',12);
    $pdf->Cell(50,8,$date_from ,0,0,'C');

    $pdf->setXY(10,145);
    $pdf->SetFont('Arial','I',12);
    $pdf->CellFitScale(190,8,$dateInWords ,0,0,'L');
}

$name = "Character_Certificate.pdf";

$pdf->output('I',$name);

function yearsInWords($years) {
    $words = array('zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve','thirteen','fourteen','fifteen','sixteen','seventeen','eighteen','nineteen','twenty');
    if ($years >= 0 && $years <= 20) {
        return $words[$years];
    } else {
        return $years;
    }
}

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
