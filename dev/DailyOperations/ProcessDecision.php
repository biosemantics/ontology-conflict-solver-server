<?php 

require_once '../../includes/DataBaseOperations.php';

$response = array(); 

if($_SERVER['REQUEST_METHOD']=='POST'){

    $choice         = $_POST['choice'];
    //$writtenComment = $_POST['writtenComment'];
    //$voiceComment   = $_POST['voiceComment'];

    $db = new DataBaseOperations();

    $resultSubmitDecision = $db->submitDecision($choice, $_POST['writtenComment'], $_POST['voiceComment']);

    if($resultSubmitDecision == 1){
    	
        $conflictId = (int)$_POST['conflictId'];
        $expertId   = (int)$_POST['expertId'];
                    
        $choiceId = $db->populate_J_Conflict_Expert_Choice($conflictId, $expertId);

        if($choiceId != -1){

            $resultSubmitDecision = $db->populate_J_Conflict_Expert($conflictId, $expertId);

            if($resultSubmitDecision == 1){

           	    $response['error'] = false;
           	    $response['message'] = "Submission Successful";

                $result = $db->getExpertsByConflict($conflictId, $expertId);	
                $tokens = array();

                if(mysqli_num_rows($result) > 0 ){

                    // Output the data of each row
                    while($row = $result->fetch_assoc()){
                        $tokens[]   = $row['token'];
                    }
                }

                $rowUsername = $db->getExpertUsernameById($expertId);
                $rowTerm     = $db->getTermByConflict($conflictId);

                $text = $rowUsername['username'] . " answered " . $rowTerm['term'] . " conflict as " . $choice;

                $message = array("message" => $text);
                $message_status = $db->sendNotification($tokens, $message);
//                echo $message_status;   



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
