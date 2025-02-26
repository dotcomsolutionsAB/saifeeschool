<?php 

// ini_set("display_errors", 1);

session_start();
require_once "connect.php";

$id = $_REQUEST['id'];

// $sql = "SELECT * FROM `manual_entries` WHERE `status` = '0' LIMIT 50";
// $sql = "SELECT * FROM `manual_entries` WHERE `status` = '0' AND st_id = '4393'";
$sql = "SELECT * FROM `manual_entries` WHERE `status` = '0' AND `id` > 11727 LIMIT 0, 20";
$query = $db->query($sql);
while($row = $query->fetch_assoc()){

	$id 			= $row['id'];
	$st_id 			= $row['st_id'];
	$amount 		= $row['amount'];
	$txn_details 	= $row['transaction_id'];
	$transaction_date 	= $row['transaction_date'];
	$row_f_id 		= $row['f_id'];

	$txn_details = trim($txn_details);
	$sql_check = "SELECT COUNT(*) AS total FROM `txn_detail` WHERE TRIM(txndet_pg_icici_id) LIKE '$txn_details'";

	$query_check = $db->query($sql_check);
	$row_check = $query_check->fetch_assoc();

	// echo $id.'<br/>';

	if($row_check['total'] == 0){

		$sql_temp = "UPDATE `manual_entries` SET `status` = 2 WHERE `id` = '$id'";
		$query_temp = $db->query($sql_temp);

		
		//Add Amount to Student Wallet
		$sql_st = "SELECT * FROM student WHERE `st_id` = '$st_id'";
		$query_st = $db->query($sql_st);
		$row_st = $query_st->fetch_assoc();

		echo $st_id." - ".$row_st['st_roll_no']." : ".$amount."<br/>";

		$wallet = $row_st['st_wallet'];

		$sql_1 = "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'fee_management' AND TABLE_NAME = 'txn' ";
		$query_1 = $db->query($sql_1);
		$row_1 = $query_1->fetch_assoc();

		$txn_id = $row_1['AUTO_INCREMENT'];
		$txn_date = strtotime($transaction_date);

		$sql_insert = "INSERT INTO `txn`(`st_id`, `sch_id`, `txn_type_id`, `txn_date`, `txn_mode`, `txn_amount`,  `f_normal`, `f_late`) VALUES ('$st_id','1','1','$txn_date','pg','$amount','0','0')";
		$query_insert = $db->query($sql_insert);

	    $sql_insert_1 = "INSERT INTO `txn_detail`(`txn_id`, `txndet_pg_icici_id`, `txndet_pg_credit_date`) VALUES ('$txn_id', '$txn_details', '$txn_date')";
	    $query_insert_1 = $db->query($sql_insert_1);

		$wallet += $amount;

		//Adjust amount for fees

		if($f_id != null || $f_id != '')
		{
			$sql_fee = "SELECT * FROM `fee` WHERE `f_id` IN ($row_f_id)";
		    $query_fee = $db->query($sql_fee);
		    while($row_fee = $query_fee->fetch_assoc()){

		    	if($row_fee['f_paid'] == 0)
		    	{
			        $row_id = $row_fee['f_id'];
			        $row_amount = $row_fee['fpp_amount'] - $row_fee['f_concession'];
			        $row_late_fee_amount = 0;
			        $due_date = $row_fee['fpp_due_date'];
			        if($txn_date > $due_date){
			            $row_late_fee_amount = $row_fee['fpp_late_fee'];
			        }

			        $row_total_amount = $row_amount + $row_late_fee_amount;

			        if($wallet >= $row_total_amount)
					{
				        $wallet -= $row_total_amount;

				        $paid_date = $txn_date;

				        $sql_update = "UPDATE `fee` SET `f_paid` = '1', `f_total_paid` = '$row_total_amount', `f_late_fee_paid` = '$row_late_fee_amount', `f_paid_date` = '$paid_date' WHERE `f_id` = '$row_id'";
				        $query_update = $db->query($sql_update);

				        $sql_insert = "INSERT INTO `txn`(`st_id`, `sch_id`, `txn_type_id`, `txn_date`, `txn_mode`, `txn_amount`, `f_id`, `f_normal`, `f_late`) VALUES ('$st_id','1','2','$txn_date','internal','$row_amount','$row_id','1','0')";
				        $query_insert = $db->query($sql_insert);

				        if($row_late_fee_amount > 0){
				            $sql_insert = "INSERT INTO `txn`(`st_id`, `sch_id`, `txn_type_id`, `txn_date`, `txn_mode`, `txn_amount`, `f_id`, `f_normal`, `f_late`) VALUES ('$st_id','1','3','$txn_date','internal','$row_late_fee_amount','$row_id','0','1')";
				            $query_insert = $db->query($sql_insert);
				        }
				    }
				}
		    }
		}
		// else{

		//     // $sql_fee = "SELECT * FROM `fee` WHERE `st_id` = '$st_id' AND `f_paid` = '0' AND `ay_id` = '6' ORDER BY `fee`.`fpp_due_date` ASC";
		//     // $query_fee = $db->query($sql_fee);
		//     // while($row_fee = $query_fee->fetch_assoc()){

		//     //     $row_id = $row_fee['f_id'];
		//     //     $row_amount = $row_fee['fpp_amount'] - $row_fee['f_concession'];
		//     //     $row_late_fee_amount = 0;
		//     //     $due_date = $row_fee['fpp_due_date'];
		//     //     if($txn_date > $due_date){
		//     //         $row_late_fee_amount = $row_fee['fpp_late_fee'];
		//     //     }

		//     //     $row_total_amount = $row_amount + $row_late_fee_amount;

		//     //     if($wallet >= $row_total_amount)
		// 	// 	{
		// 	//         $wallet -= $row_total_amount;

		// 	//         $paid_date = $txn_date;

		// 	//         $sql_update = "UPDATE `fee` SET `f_paid` = '1', `f_total_paid` = '$row_total_amount', `f_paid_date` = '$paid_date' WHERE `f_id` = '$row_id'";
		// 	//         $query_update = $db->query($sql_update);

		// 	//         $sql_insert = "INSERT INTO `txn`(`st_id`, `sch_id`, `txn_type_id`, `txn_date`, `txn_mode`, `txn_amount`, `f_id`, `f_normal`, `f_late`) VALUES ('$st_id','1','2','$txn_date','internal','$row_amount','$row_id','1','0')";
		// 	//         $query_insert = $db->query($sql_insert);

		// 	//         if($row_late_fee_amount > 0){
		// 	//             $sql_insert = "INSERT INTO `txn`(`st_id`, `sch_id`, `txn_type_id`, `txn_date`, `txn_mode`, `txn_amount`, `f_id`, `f_normal`, `f_late`) VALUES ('$st_id','1','3','$txn_date','internal','$row_late_fee_amount','$row_id','0','1')";
		// 	//             $query_insert = $db->query($sql_insert);
		// 	//         }
		// 	//     }

		//     // }
		// }

	    $sql_update = "UPDATE student SET `st_wallet` = '$wallet' WHERE `st_id` = '$st_id'";
	    $query_update = $db->query($sql_update);

	}else{
		$sql_temp = "DELETE FROM `manual_entries` WHERE `id` = '$id'";
		// $query_temp = $db->query($sql_temp);
		$sql_temp = "UPDATE `manual_entries` SET `status` = 3 WHERE `id` = '$id'";
		$query_temp = $db->query($sql_temp);
	}

	

}

?>