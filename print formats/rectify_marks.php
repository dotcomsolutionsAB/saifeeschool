<?php
session_start();
require_once "connect.php";

$sql = "SELECT * FROM `studMarks_bk`";
$query = $db->query($sql);
while($row = $query->fetch_assoc())
{

	$st_roll_no = $row['st_roll_no'];
	$subj_id = $row['subj_id'];
	$cg_id = $row['cg_id'];
	$term_id = $row['term'];
	$marks = $row['marks'];

	if($marks != '')
	{
		$sql_check = "SELECT * FROM `studMarks` WHERE `st_roll_no` = '$st_roll_no' AND `subj_id` = '$subj_id' AND `term` = '$term_id'";
		$query_check = $db->query($sql_check);
		$row_check = $query_check->fetch_assoc();

		if($row_check['id'] != '' && $row_check['id'] != null)
		{
			$id = $row_check['id'];
			$sql_insert = "UPDATE `studMarks` SET `marks` = '$marks' WHERE `id` = '$id'";
			$query_insert = $db->query($sql_insert);
		}else{
			$sql_insert = "INSERT INTO `studMarks`(`session`, `st_roll_no`, `subj_id`, `cg_id`, `term`, `unit`, `marks`, `serialNo`) VALUES ('7','$st_roll_no','$subj_id','$cg_id','$term_id','1','$marks','1')";
			$query_insert = $db->query($sql_insert);
		}
	}

}