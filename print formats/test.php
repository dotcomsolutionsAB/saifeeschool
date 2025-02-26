<?php
session_start();
require_once "connect.php";

// $password_plain_text = 'swrkhs37';

// $password_hash = password_hash($password_plain_text, PASSWORD_DEFAULT);
// $password_hash = '$2y$10$kLAweD0/DTdBOoHR0HeKEeQiTA2x9gClTx96dnPEGiM03Nwk5y77C';

// echo $password_hash;
// echo password_verify($password_plain_text, $password_hash);

// $sql = "SELECT * FROM `fee_plan` WHERE `ay_id` = 6";
// $query = $db->query($sql);
// while($row = $query->fetch_assoc()){
// 	$cg_id = $row['cg_id'];

// 	$str_arr = explode (",", $cg_id); 

// 	$len = sizeof($str_arr);

// 	for($i=0;$i<$len;$i++){
// 		echo $str_arr[$i].'<br/>';
// 	}
// }


// $sql = "SELECT * FROM `log_response`";
// $query = $db->query($sql);
// while($row = $query->fetch_assoc()){
	
// 	$response = json_decode($row['response'], true);

// 	$Response_Code 			= $response['Response_Code'];
// 	$Unique_Ref_Number 		= $response['Unique_Ref_Number'];
// 	$Total_Amount 			= $response['Total_Amount'];
// 	$Transaction_Amount 	= $response['Transaction_Amount'];
// 	$Transaction_Date 		= strtotime($response['Transaction_Date']);
// 	$Interchange_Value 		= $response['Interchange_Value'];
// 	$TDR 					= $response['TDR'];
// 	$Payment_Mode 			= $response['Payment_Mode'];
// 	$SubMerchantId 			= $response['SubMerchantId'];
// 	$ReferenceNo 			= $response['ReferenceNo'];
// 	$ICID 					= $response['ID'];
// 	$RS 					= $response['RS'];
// 	$TPS 					= $response['TPS'];
// 	$mandatory_fields 		= $response['mandatory_fields'];
// 	$optional_fields 		= $response['optional_fields'];
// 	$RSV 					= $response['RSV'];
// 	$timestamp 				= date('Y-m-d H:i:s', $Transaction_Date);

// 	$sql_check = "SELECT COUNT(*) AS total FROM `pg_response` WHERE `ReferenceNo` = '$ReferenceNo'";
// 	$query_check = $db->query($sql_check);
// 	$row_check = $query_check->fetch_assoc();

// 	if($row_check['total'] == 0){
// 		$sql_insert = "INSERT INTO `pg_response`(`Response_Code`, `Unique_Ref_Number`, `Transaction_Date`, `Total_Amount`, `Interchange_Value`, `TDR`, `Payment_Mode`, `SubMerchantId`, `ReferenceNo`, `ICID`, `RS`, `TPS`, `mandatory_fields`, `optional_fields`, `RSV`, `timestamp`) VALUES ('$Response_Code','$Unique_Ref_Number','$Transaction_Date ','$Transaction_Amount','$Interchange_Value','$TDR','$Payment_Mode','$SubMerchantId','$ReferenceNo','$ICID','$RS','$TPS','$mandatory_fields','$optional_fields','$RSV','$timestamp')";
// 		// $query_insert = $db->query($sql_insert);
// 	}
// }

// $sql = "SELECT * FROM `pg_logs`";
// $query = $db->query($sql);
// while($row = $query->fetch_assoc()){
	
// 	$id = $row['id'];
// 	$pg_reference_no = $row['pg_reference_no'];

// 	$sql_check = "SELECT * FROM `pg_response` WHERE `ReferenceNo` = '$pg_reference_no'";
// 	$query_check = $db->query($sql_check);
// 	$row_check = $query_check->fetch_assoc();

// 	if($row_check['Response_Code'] != '')
// 	{
// 		$Response_Code 		= $row_check['Response_Code'];
// 		$Unique_Ref_Number 	= $row_check['Unique_Ref_Number'];
// 		$sql_update = "UPDATE `pg_logs` SET `ResponseCode`='$Response_Code',`Unique_Ref_Number`='$Unique_Ref_Number' WHERE `id` = '$id'";
// 		$query_update = $db->query($sql_update);
// 	}
// }


// $sql = "SELECT * FROM `new_admission`";
// $query = $db->query($sql);
// while($row = $query->fetch_assoc()){

// 	$id = $row['id'];

	// $father_details = $row['father_details'];
	// $mother_details = $row['mother_details'];
	// $address 		= $row['address'];

	// $father_details = json_decode($father_details, true);
	// $mother_details = json_decode($mother_details, true);
	// $address 		= json_decode($address, true);

	// if($father_details['last_name'] == '' && $father_details['first_name'] == '') {
	// 	$father_details['first_name'] = '';
	// 	$father_details['last_name'] = '';
	// 	$father_details = json_encode($father_details);

	// 	$sql_update = "UPDATE `new_admission` SET `father_details` = '$father_details' WHERE `id` = '$id'";
	// 	$query_update = $db->query($sql_update);
	// }

	// if($mother_details['last_name'] == '' && $mother_details['first_name'] == '') {
	// 	$mother_details['first_name'] = '';
	// 	$mother_details['last_name'] = '';
	// 	$mother_details = json_encode($mother_details);

	// 	$sql_update = "UPDATE `new_admission` SET `mother_details` = '$mother_details' WHERE `id` = '$id'";
	// 	$query_update = $db->query($sql_update);
	// }

	// if($address['address_1'] == '' && $address['address_2'] == '') {
	// 	$address['address_1'] = $address['address'];
	// 	// $address['address_2'] = '';
	// 	$address = json_encode($address);

	// 	$sql_update = "UPDATE `new_admission` SET `address` = '$address' WHERE `id` = '$id'";
	// 	$query_update = $db->query($sql_update);
	// }


	// if(!json_validator($father_details) || !json_validator($mother_details)){
	// 	echo $row['id'].'<br/>';
	// }

// }


// function json_validator($data) {
//     if (!empty($data)) {
//         return is_string($data) && 
//           is_array(json_decode($data, true)) ? true : false;
//     }
//     return false;
// }

date_default_timezone_set('Asia/Kolkata');

$yesterday = date('Y-m-d H:i:s', strtotime("-1 hour"));

$sql = "SELECT * FROM `pg_logs` WHERE `cron` = '0' AND `timestamp` <= '$yesterday' ORDER BY `id` LIMIT 10";
// $sql = "SELECT * FROM `pg_logs` WHERE `cron` = '0' AND `pg_sub_merchant` IN ('458') LIMIT 10";
// $query = $db->query($sql);
// while($row = $query->fetch_assoc())
// {

// }

echo $sql;

?>
