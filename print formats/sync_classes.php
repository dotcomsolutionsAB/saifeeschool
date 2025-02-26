<?php 

require('pdf_js.php');
include ("connect.php");
session_start();
setlocale(LC_MONETARY, 'en_IN');

$sql_student = "SELECT * FROM `student`";
$query_student = $db->query($sql_student);
while($row_student = $query_student->fetch_assoc())
{
	$st_id = $row_student['st_id'];
	$cg_id = $row_student['cg_id'];

	$sql_update = "UPDATE `fee` SET `cg_id` = '$cg_id' WHERE `st_id` = '$st_id' AND `ay_id` = '7'";
	$query_update = $db->query($sql_update);

	echo $sql_update.'<br/>';


}


?>