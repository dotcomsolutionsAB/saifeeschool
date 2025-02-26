<?php
session_start();
require_once "connect.php";
// ini_set('display_errors', 1);

$cg_group = $_REQUEST['id'];

$sql = "SELECT * FROM `class_group` WHERE `ay_id` = 7 AND `cg_group` = '$cg_group'";
$query = $db->query($sql);
while($row = $query->fetch_assoc())
{
	$cg_id = $row['cg_id'];

	$sql_term = "SELECT * FROM `studTerm` WHERE `cg_id` = '$cg_id' AND `term` = '1'";
	$query_term = $db->query($sql_term);
    $row_term = $query_term->fetch_assoc();

    $term_id = $row_term['term'];

    $sql_temp = "SELECT * FROM `studSubj` WHERE `cg_group` = '$cg_group' AND `sub_id` >= 248";
    $query_temp = $db->query($sql_temp);
    while($row_temp = $query_temp->fetch_assoc())
    {

        $subj = $row_temp['sub_id'];
        // $sql_temp = "SELECT * FROM `studSubj` WHERE `sub_id` = '$subj'";
        // $query_temp = $db->query($sql_temp);
        // $row_temp = $query_temp->fetch_assoc();

        $subj_name = $row_temp['subject'];
        $subj_init = $row_temp['SubInit'];
        $type = $row_temp['type'];
        $marks = $row_temp['marks'];
        $prac = $row_temp['prac'];

    	$sql_insert = "INSERT INTO `studSubjFullMarks`(`subj_id`,`subj_name`,`subj_init`, `cg_id`, `term_id`, `type`, `theory`, `oral`, `prac`, `marks`) VALUES ('$subj','$subj_name','$subj_init','$cg_id','$term_id','$type','$marks','','$prac','$marks')";
        if($cg_group != '')
        {
            echo $sql_insert.'<br/>';
    	    $query_insert = $db->query($sql_insert);
        }
    }

}