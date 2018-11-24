<?php
require_once '../../includes/DataBaseOperations.php';

    $response = array();

	if($_SERVER['REQUEST_METHOD'] == 'POST'){


		if( isset($_POST['username']) and isset($_POST['password']) and isset($_POST['firstname'])  and isset($_POST['lastname']) and isset($_POST['email']) ){
           // operate the data furter
	       $db = new DataBaseOperations();

		   $result = $db->createExpert($_POST['username'], $_POST['password'], $_POST['firstname'], $_POST['lastname'], $_POST['email']);

	       if($result == 1 ){

               $expertId = $db->setTasksToExpert();
               $result = getTokenByExpertId($expertId);

               if(mysqli_num_rows($result) > 0 ){

                    // Output the data of each row
                    while($row = $result->fetch_assoc()){
                        $token   = $row['token'];
                        $username   = $row['username'];
                    }
                }

                $text = $rowUsername['username'] . "You have new tasks!";

                $message = array("message" => $text);
                $message_status = $db->sendNotification($token, $message);


	           $response['error'] = false;
               $response['message'] = "The user was registered succesfully!";



	       } else if($result == 2 ) {

	       	   $response['error'] = true;
               $response['message'] = "An error has occurred, please try again!";

	       } else if($result == 0){

               $response['error'] = true; 
			   $response['message'] = "The username or email are already in use, please choose a different email or username";	
	       }
               
		} else {

            $response['error'] = true;
            $response['message'] = "The required fields are missing";
	    }
	       

	} else {
		$response['error'] = true;
        $response['message'] = "Invalid Request";
	}

	echo json_encode($response);

?>	