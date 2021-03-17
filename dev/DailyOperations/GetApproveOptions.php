<?php

require_once '../../includes/DataBaseOperations.php';
$sentences = array(); 
$definitions = array(); 
$approveData = array();
$comments = array();
$curComment = "";

    if($_SERVER['REQUEST_METHOD'] == 'GET'){
        
        $db = new DataBaseOperations();

        $result = $db->getSentences($_GET['termId']);
        $data = [];
        //output data of each row
        while($row = $result->fetch_assoc()){
            $id   = $row['id'];
            $sentence   = $row['sentence'];
            $sentences[] = array("id"=>$id,
                            "sentence"=>$sentence);
        }

        $result = $db->getDefinitions($_GET['termId'],  $_GET['expertId']);
        $data = [];
        //output data of each row
        while($row = $result->fetch_assoc()){
            $id   = $row['id'];
            $definition   = $row['definition'];
            $definitions[] = array("id"=>$id,
                            "definition"=>$definition,
                            "expertId"=>$row['expertId']);
        }
        
        $result = $db->getApproveData($_GET['termId'], $_GET['expertId']);
        $data = [];
        //output data of each row
        while($row = $result->fetch_assoc()){
            $sentenceId   = $row['sentenceId'];
            $definitionId   = $row['definitionId'];
            $approveData[] = array("sentenceId"=>$sentenceId,
                            "definitionId"=>$definitionId);
        }

        $commentsResult = $db->getApproveTermCommentsByTermId($_GET['termId']);
        while ($row = $commentsResult->fetch_assoc()) {
            if ($row['comment'] && $row['comment'] != '') {
                if ($row['expertId'] != $_GET['expertId']) {
                    $comments[] = array(
                        "comment"=>$row['comment'],
                        "username"=>$row['username']
                    );
                } else {
                    $curComment = $row['comment'];
                }
            }
        }

        $termDeclined = $db->isTermDeclinedByExpert($_GET['termId'], $_GET['expertId']);
        
    }
    echo json_encode(array("sentence"=>$sentences,
                        "definition"=>$definitions,
                        "approveData"=>$approveData,
                        "comments"=>$comments,
                        "termDeclined"=>$termDeclined,
                        "curComment"=>$curComment
        ));
?>
