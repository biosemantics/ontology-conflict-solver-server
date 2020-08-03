<?php
require_once '../../includes/DataBaseOperations.php';

$response = array(); 
$err = array();
$categoryData   = array();
$approveData = array();
$addTermData = array();
$exactTermData = array();
$equivTermData = array();

    if($_SERVER['REQUEST_METHOD'] == 'POST'){

        $expertId = $_POST["expertId"];

        $db = new DataBaseOperations();
        $categoryTasks = $db->getSolvedCategoryTask($expertId);

        while( $task = $categoryTasks->fetch_assoc() ) {
            $termId     = $task['termId'];
            $term       = $task['term'];
            $data       = $task['data'];
            $isSolved   = 1;
            $result     = $db->countSolvedConflicts($termId);
            $resultDeclined = $db->countDeclinedTerm($termId);
            $count      = $result['count'] + $resultDeclined['COUNT(expertId)'];
            $type       = 'category';
            $status     = $task['status'];

            $categoryData[] = array("termId"=>$termId, "term"=>$term, "type"=>$type, "data"=>$data,"isSolved"=>$isSolved, "count"=>$count, "status"=>$status);
        }

        $categoryTasks = $db->getUnSolvedCategoryTask($expertId);

        while( $task = $categoryTasks->fetch_assoc() ) {
            $termId     = $task['termId'];
            $term       = $task['term'];
            $data       = $task['data'];
            $isSolved   = 0;
            $result     = $db->countSolvedConflicts($termId);
            $resultDeclined = $db->countDeclinedTerm($termId);
            $count      = $result['count'] + $resultDeclined['COUNT(expertId)'];
            $type       = 'category';
            $status     = $task['status'];

            $categoryData[] = array("termId"=>$termId, "term"=>$term, "type"=>$type, "data"=>$data,"isSolved"=>$isSolved, "count"=>$count, "status"=>$status);
        }

        $approveTasks = $db->getApproveTask($expertId);
        
        while( $task = $approveTasks->fetch_assoc() ) {
            $termId     = $task['termId'];
            $term       = $task['term'];
            $data       = $task['data'];
            $isSolved   = $task['isSolved'];
            $resultDeclined = $db->countDeclinedTerm($termId);
            $count      = $db->countSolvedApproveConflicts($termId) + $resultDeclined['COUNT(expertId)'];
            // $count      = $result['COUNT(expertId)'];
            $type       = 'synonym';
            $status     = $task['status'];

            $approveData[] = array("termId"=>$termId, "term"=>$term, "type"=>$type, "data"=>$data,"isSolved"=>$isSolved, "count"=>$count, "sCount" => $task['sentenceCount'], "status"=>$status);
        }

        $addTermTasks = $db->getAddTermTasks($expertId);
        
        while( $task = $addTermTasks->fetch_assoc() ) {
            $termId     = $task['termId'];
            $term       = $task['term'];
            $data       = $task['data'];
            $isSolved   = $task['isSolved'];
            $result     = $db->countSolvedAddTermConflicts($termId);
            $resultDeclined = $db->countDeclinedTerm($termId);
            $count      = $result['COUNT(expertId)'] + $resultDeclined['COUNT(expertId)'];
            $type       = 'addTerm';
            $status     = $task['status'];

            $addTermData[] = array("termId"=>$termId, "term"=>$term, "type"=>$type, "data"=>$data,"isSolved"=>$isSolved, "count"=>$count, "status"=>$status);
        }

        $exactTasks = $db->getSolvedExactTasks($expertId);

        while( $task = $exactTasks->fetch_assoc() ) {
            $termId     = $task['termId'];
            $term       = $task['term'];
            $data       = $task['data'];
            $isSolved   = 1;
            $result     = $db->countExactSolvedConflicts($termId);
            $count      = $result['count'];
            $type       = 'exact';
            $status     = $task['status'];

            $exactTermData[] = array("termId"=>$termId, "term"=>$term, "type"=>$type, "data"=>$data,"isSolved"=>$isSolved, "count"=>$count, "status"=>$status);
        }

        $exactTasks = $db->getUnsolvedExactTask($expertId);

        while( $task = $exactTasks->fetch_assoc() ) {
            $termId     = $task['termId'];
            $term       = $task['term'];
            $data       = $task['data'];
            $isSolved   = 0;
            $result     = $db->countExactSolvedConflicts($termId);
            $count      = $result['count'];
            $type       = 'exact';
            $status     = $task['status'];

            $exactTermData[] = array("termId"=>$termId, "term"=>$term, "type"=>$type, "data"=>$data,"isSolved"=>$isSolved, "count"=>$count, "status"=>$status);
        }

        $equivTasks = $db->getSolvedEquivTasks($expertId);

        while( $task = $equivTasks->fetch_assoc() ) {
            $termId     = $task['termId'];
            $term       = $task['term'];
            $data       = $task['data'];
            $isSolved   = 1;
            $result     = $db->countExactSolvedConflicts($termId);
            $count      = $result['count'];
            $type       = 'equiv';
            $status     = $task['status'];

            $equivTermData[] = array("termId"=>$termId, "term"=>$term, "type"=>$type, "data"=>$data,"isSolved"=>$isSolved, "count"=>$count, "status"=>$status);
        }

        $equivTasks = $db->getUnsolvedEquivTask($expertId);

        while( $task = $equivTasks->fetch_assoc() ) {
            $termId     = $task['termId'];
            $term       = $task['term'];
            $data       = $task['data'];
            $isSolved   = 0;
            $result     = $db->countExactSolvedConflicts($termId);
            $count      = $result['count'];
            $type       = 'equiv';
            $status     = $task['status'];

            $equivTermData[] = array("termId"=>$termId, "term"=>$term, "type"=>$type, "data"=>$data,"isSolved"=>$isSolved, "count"=>$count, "status"=>$status);
        }

        $response = array_merge($categoryData, $approveData, $addTermData, $exactTermData, $equivTermData);

        if( empty($response) ){

            $stat = "Null";

        } else {

            $stat = "NotNull";

        }

    }
echo json_encode(array("task_data"=>$response,"status"=>$stat));
?>
