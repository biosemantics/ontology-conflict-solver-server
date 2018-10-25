<?php 

require_once '../../includes/DataBaseOperations.php';

$response = array(); 

if($_SERVER['REQUEST_METHOD']=='POST'){

    $db = new DataBaseOperations();

    $resultSubmitDecision = $db->submitDecision($_POST['choice'], $_POST['writtenComment']);

    if($resultSubmitDecision == 1){
    	
        $conflictId = (int)$_POST['conflictId'];
        $expertId   = (int)$_POST['expertId'];
        $resultSubmitDecision = $db->populate_J_Conflict_Expert_Choice($conflictId, $expertId);

        if($resultSubmitDecision == 1){

            $resultSubmitDecision = $db->populate_Conflict($conflictId);

            if($resultSubmitDecision == 1){

       	        $response['error'] = false;
       	        $response['message'] = "Submission Successful";		
 

       	    } else {

       	        $response['error'] = true;
       	        $response['message'] = "Submission Failed";		
       	    }

        } else {

            $response['error'] = true;
            $response['message'] = "Submission Failed";		
 
        }

    } else {

           $response['error'] = true;
           $response['message'] = "Submission Failed";		

 
    }
}

echo json_encode($response);