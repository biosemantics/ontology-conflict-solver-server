<?php 

require_once '../../includes/DataBaseOperations.php';

$response = array(); 

if($_SERVER['REQUEST_METHOD']=='POST'){

	$db = new DataBaseOperations();
    $result = $db->submitDecision($_POST['termId'], $_POST['choice'], $_POST['writtenComment']);

    $response['error'] = false; 

}

echo json_encode($response);