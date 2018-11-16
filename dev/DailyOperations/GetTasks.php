<?php
require_once '../../includes/DataBaseOperations.php';

$response = array(); 
$err = array();
$data1 = array();
$data2 = array();
    if($_SERVER['REQUEST_METHOD'] == 'POST'){

        $expertId = $_POST["expertId"];

        $db = new DataBaseOperations();
        $resultSolved = $db->getSolvedTasks($expertId);
        $resultUnsolved = $db->getUnsolvedTasks($expertId);

        while( $row1 = $resultSolved->fetch_assoc() ) {
            $termId = $row1['termId'];
            $term = $row1['term'];
            $conflictId = $row1['conflictId'];
            $username = $row1['username'];
            $sentence = $row1['sentence'];
            $isSolved = 1;
            $data1[] = array("termId"=>$termId, "term"=>$term, "conflictId"=>$conflictId, "username"=>$username, "sentence"=>$sentence,"isSolved"=>$isSolved);
        }

        while( $row2 = $resultUnsolved->fetch_assoc() ) {
            $termId = $row2['termId'];
            $term = $row2['term'];
            $conflictId = $row2['conflictId'];
            $username = $row2['username'];
            $sentence = $row2['sentence'];
            $isSolved = 0;
            $data2[] = array("termId"=>$termId, "term"=>$term, "conflictId"=>$conflictId, "username"=>$username, "sentence"=>$sentence,"isSolved"=>$isSolved);
        }

        $response = array_merge($data1, $data2);

        if( empty($response) ){

            $stat = "Null";

        } else {

            $stat = "NotNull";

        }

}
echo json_encode(array("task_data"=>$response,"status"=>$stat));
?>
