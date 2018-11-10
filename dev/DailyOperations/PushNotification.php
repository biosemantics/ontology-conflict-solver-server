<?php 
	function send_notification ($tokens, $message){

		$url = 'https://fcm.googleapis.com/fcm/send';

		$fields = array('registration_ids' => $tokens,
			            'data' => $message );

		$headers = array('Authorization:key = AAAAYXS_iEo:APA91bGZ50RhB0sZIBf6vmXohxOd_wJsDVCQPJCMeqtujIfG9JhLPUpA5C4Q_OFW-nacNXHfoSJPjJKMagr54b9i4JUFpcXocf2oAGzrVaTMsKpBNufnNAGRRQrO-CHGJ3eSdjnv9twF',
			             'Content-Type: application/json');
	   $ch = curl_init();

       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);  
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

       $result = curl_exec($ch);           
       if ($result === FALSE) {
           die('Curl failed: ' . curl_error($ch));
       }
       curl_close($ch);
       return $result;
	}
	
    require_once '../../includes/DataBaseOperations.php';

    $db = new DataBaseOperations();

    $result = $db->getAllTokens();

	$tokens = array();

	if(mysqli_num_rows($result) > 0 ){
		while ($row = $result->fetch_assoc()) {
			$tokens[] = $row["token"];
		}
	}

	$message = array("message" => " Conflict Solver has an update");
	$message_status = send_notification($tokens, $message);
	echo $message_status;
 ?>