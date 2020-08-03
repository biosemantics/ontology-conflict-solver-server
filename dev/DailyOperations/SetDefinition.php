<?php

require_once '../../includes/DataBaseOperations.php';
$sentences = array(); 
$definitions = array(); 
$approveData = array();

    if($_SERVER['REQUEST_METHOD'] == 'GET'){
        $db = new DataBaseOperations();

        $db->deleteDeclinedDecision($_GET['termId'], $_GET['expertId']);

        $resDefinition = $db->getDefinitions($_GET['termId'], $_GET['expertId']);

        while ($row = $resDefinition->fetch_assoc()) {
            $definitionId = $row['id'];
            $db->deleteApproveDecision($definitionId, $_GET['expertId']);
        }

        $db->setApproveData($_GET['expertId'], $_GET['sentenceIds'], $_GET['definitionIds'], $_GET['comment']);

    }
?>
