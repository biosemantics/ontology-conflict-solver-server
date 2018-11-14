<?php
require_once '../../includes/DataBaseOperations.php';

$response = array(); 
$err = array();
$data1 = array();
$data2 = array();
    if($_SERVER['REQUEST_METHOD'] == 'POST'){

        $expertId = $_POST["expertId"];

        $db = new DataBaseOperations();
        $resultSolved = $db->getTasksSolved($expertId);
        $resultUnsolved = $db->getTasksUnsolved($expertId);

        while( $row = $resultSolved->fetch_assoc() ) {
            $termId = $row['termId'];
            $term = $row['term'];
            $conflictId = $row['conflictId'];
            $username = $row['username'];
            $sentence = $row['sentence'];
            $isSolved = 1;
            $data1[] = array("termId"=>$termId, "term"=>$term, "conflictId"=>$conflictId, "username"=>$username, "sentence"=>$sentence,"isSolved"=>$isSolved);
        }

        while( $row = $resultUnsolved->fetch_assoc() ) {
            $termId = $row['termId'];
            $term = $row['term'];
            $conflictId = $row['conflictId'];
            $username = $row['username'];
            $sentence = $row['sentence'];
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
