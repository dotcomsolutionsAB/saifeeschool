<?php
// ini_set("display_errors",1);
session_start();
$marks_class 	= $_REQUEST['marks_class'];
$marks_term 	= $_REQUEST['marks_term'] == '' ? 1 : $_REQUEST['marks_term'];
$ay_id          = $_SESSION['report_year'];

// die($ay_id);
if($ay_id == 7){
    if($marks_term == 1)
        include('_reports/2023/half_yearly.php');
    else
        include('_reports/2023/final.php');
}

if($ay_id == 8){
    if($marks_term == 1)
        include('_reports/2024/half_yearly.php');
    else
        include('_reports/2024/final.php');
}



