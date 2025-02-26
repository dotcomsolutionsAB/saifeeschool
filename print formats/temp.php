<?php
session_start();
require_once "connect.php";

ini_set('display_errors',1);

$salt = "DCS1920";

$sql = "SELECT * FROM student WHERE `st_roll_no` LIKE '%2300%'";
$query = $db->query($sql);
while($row = $query->fetch_assoc())
{
    $st_id            = $row['st_id'];
    $st_roll_no       = $row['st_roll_no'];

    $password = "Saifee@123";

    $password = password_hash($password, PASSWORD_DEFAULT);

    // $dob= date('Y-m-d',$row['st_dob']);

    $sql_create = "UPDATE `student` SET `st_password_hash`='$password' WHERE `st_id` = '$st_id' ";
    // $query_create = $db->query($sql_create);

    echo $sql_create.'<br/>';

    if($query_create===true)
    {
        $validator['success'] = true;
        $validator['messages'] = "Successfully Added";
    }
    else
    {
        $validator['success'] = false;
        $validator['messages'] = "There was some error saving the records";

    }

}

    echo json_encode($validator);

?>
