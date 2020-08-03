<?php

require_once '../../includes/DataBaseOperations.php';

    if($_SERVER['REQUEST_METHOD'] == 'GET'){
        $db = new DataBaseOperations();

        $db->deleteDeclinedDecision($_GET['termId'], $_GET['expertId']);

        if ($db->setAddTermSolution($_GET['termId'], $_GET['expertId'], $_GET['termType'], $_GET['subPartString'], $_GET['superPartString'], $_GET['alwaysHasPartString'], $_GET['alwaysPartOfString'], $_GET['maybePartOfString'], $_GET['subclassOf'],$_GET['comment'])) {
            if ($db->addSynonyms($_GET['termId'], $_GET['expertId'], $_GET['experts'], $_GET['synonyms'])){
                return ['error' => false];
            }
            else {
                return ['error' => true, 'message' => 'falure 2'];
            }
        }
        else {
            return ['error' => true, 'message' => 'falure 1'];
        }
    }
?>
