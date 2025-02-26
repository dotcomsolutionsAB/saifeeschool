<?php
session_start();
require_once "connect.php";

$sql = "SELECT * FROM `student` WHERE `st_id` >= 4739";
$query = $db->query($sql);
while($row = $query->fetch_assoc()){

	$st_id = $row['st_id'];
	$pwd = $row['st_roll_no'];

	$password = password_hash($pwd, PASSWORD_DEFAULT);

	$sql_update = "UPDATE student SET `st_password_hash`='$password' WHERE `st_id` = '$st_id'";
	$query_update = $db->query($sql_update);
}

?>
