<?php
require_once '../../includes/DataBaseOperations.php';

$response = array(); 
$err = array();
$solvedData   = array();
$unsolvedData = array();
    if($_SERVER['REQUEST_METHOD'] == 'POST'){

        $expertId = $_POST["expertId"];

        $db = new DataBaseOperations();
        $resultSolved = $db->getSolvedTasks($expertId);
        $resultUnsolved = $db->getUnsolvedTasks($expertId);

        while( $solved = $resultSolved->fetch_assoc() ) {
            $termId     = $solved['termId'];
            $term       = $solved['term'];
            $conflictId = $solved['conflictId'];
            $username   = $solved['username'];
            $sentence   = $solved['sentence'];
            $isSolved   = 1;
            $result     = $db->countSolvedConflicts($conflictId);
            $count      = $result['COUNT(conflictId)'];
            $solvedData[] = array("termId"=>$termId, "term"=>$term, "conflictId"=>$conflictId, "username"=>$username, "sentence"=>$sentence,"isSolved"=>$isSolved, "count"=>$count);
        }

        while( $unsolved = $resultUnsolved->fetch_assoc() ) {
            $termId     = $unsolved['termId'];
            $term       = $unsolved['term'];
            $conflictId = $unsolved['conflictId'];
            $username   = $unsolved['username'];
            $sentence   = $unsolved['sentence'];
            $isSolved   = 0;
            $result     = $db->countSolvedConflicts($conflictId);
            $count      = $result['COUNT(conflictId)'];            
            $unsolvedData[] = array("termId"=>$termId, "term"=>$term, "conflictId"=>$conflictId, "username"=>$username, "sentence"=>$sentence,"isSolved"=>$isSolved, "count"=>$count);
        }

        $response = array_merge($solvedData, $unsolvedData);

        if( empty($response) ){

            $stat = "Null";

        } else {

            $stat = "NotNull";

        }

}
echo json_encode(array("task_data"=>$response,"status"=>$stat));
?>
