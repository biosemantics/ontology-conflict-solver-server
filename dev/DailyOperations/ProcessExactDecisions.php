<?php 

require_once '../../includes/DataBaseOperations.php';

$response = array(); 


if($_SERVER['REQUEST_METHOD']=='GET'){

    $choice         = $_GET['choice'];
    $termId         = (int)$_GET['termId'];
    $expertId       = (int)$_GET['expertId'];

    $reason         = $_GET['reason'];

    $db = new DataBaseOperations();

    $db->deleteCurrentExactDecisions($termId, $expertId);

    $optionResult = $db->getExactOptions($termId);

    $resultSubmitDecision = 0;

    while ($row = $optionResult->fetch_assoc()) {
      $selected = 0;
      foreach ($choice as $indChoicekey => $indChoice) {
        if ($row['id'] == $indChoice) {
          $selected = 1;
        }
      }
      $resultSubmitDecision = $db->submitExactDecision($termId, $expertId, $row['id'], $selected, $reason);
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
