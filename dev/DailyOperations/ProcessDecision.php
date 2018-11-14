<?php 

require_once '../../includes/DataBaseOperations.php';

$response = array(); 

if($_SERVER['REQUEST_METHOD']=='POST'){

    $db = new DataBaseOperations();

    $resultSubmitDecision = $db->submitDecision($_POST['choice'], $_POST['writtenComment'], $_POST['voiceComment']);

    if($resultSubmitDecision == 1){
    	
        $conflictId = (int)$_POST['conflictId'];
        $expertId   = (int)$_POST['expertId'];
        $choiceId = $db->populate_J_Conflict_Expert_Choice($conflictId, $expertId);
           
        if($choiceId != -1){

       	    $response['error'] = false;
       	    $response['message'] = "Submission Successful";

            $result = $db->getRelatedTokens($conflictId, $expertId);	

            /*//output data of each row
            while($row = $result->fetch_assoc()){
            $term         = $row['expertId'];
            $option_      = $row['username'];
            $definition   = $row['token'];
            $data[] = array("option_"=>$option_, 
            "definition"=>$definition,
            "image_link"=>$image_link);*/
            //}     
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