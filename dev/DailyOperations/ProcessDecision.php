<?php 

require_once '../../includes/DataBaseOperations.php';

$response = array(); 

if($_SERVER['REQUEST_METHOD']=='POST'){

	$db = new DataBaseOperations();

    $resultSubmitDecision = $db->submitDecision($_POST['choice'], $_POST['writtenComment']);

    if( $resultSubmitDecision == 1){
    	
    	$resultSubmitDecision = $db->populate_J_Conflict_Expert_Choice($_POST['conflictId'], $_POST['expertId']);
     
       if($resultSubmitDecision == 1){

           $response['error'] = false; 

       } else {

           $response['error'] = true; 
       }

    } else {

           $response['error'] = true; 
    }
}

echo json_encode($response);