<?php 

require_once '../../includes/DataBaseOperations.php';

$response = array(); 


if($_SERVER['REQUEST_METHOD']=='GET'){

    $choice         = $_GET['choice'];
    $termId         = (int)$_GET['termId'];
    $expertId       = (int)$_GET['expertId'];

    $writtenComment = $_GET['writtenComment'];
    $voiceComment   = $_GET['voiceComment'];

    $db = new DataBaseOperations();

    $db->deleteDeclinedDecision($termId, $expertId);

    $db->deleteCurrentDecisions($termId, $expertId);

    $resultSubmitDecision = 0;
    foreach($choice as $indChoicekey => $indChoice) {
      $resultSubmitDecision = $db->submitDecision($termId, $expertId, $indChoice, $_GET['writtenComment'], $_GET['voiceComment']);
    }

    if($resultSubmitDecision == 1){

        $response['error'] = false;
        $response['message'] = "Submission Successful";

    } else {

      $response['error'] = true;
      $response['message'] = "Submission Failed";

    }
}
echo json_encode($response);
