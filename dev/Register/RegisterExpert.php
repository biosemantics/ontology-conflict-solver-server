<?php
require_once '../../includes/DataBaseOperations.php';

    $response = array();

	if($_SERVER['REQUEST_METHOD'] == 'GET'){


		if( isset($_GET['username']) and isset($_GET['password']) and isset($_GET['firstname'])  and isset($_GET['lastname']) and isset($_GET['email']) ){
           // operate the data furter
	       $db = new DataBaseOperations();

		   $result = $db->createExpert($_GET['username'], $_GET['password'], $_GET['firstname'], $_GET['lastname'], $_GET['email']);

	       if($result == 1 ){

			//    $expertId = $db->setTasksToExpert();
			   $expert = $db->getExpertByUsername($_GET['username']);

	           $response['error'] = false;
			   $response['message'] = "The user was registered succesfully!";
			   $response['expertId'] = $expert['expertId'];

	       } else if($result == 0){

               $response['error'] = true; 
			   $response['message'] = "The username or email are already in use, please choose a different email or username";	
	       } else if($result == 2 ) {

	       	   $response['error'] = true;
               $response['message'] = "An error has occurred, please try again!";

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