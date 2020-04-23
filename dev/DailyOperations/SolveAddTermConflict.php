<?php

require_once '../../includes/DataBaseOperations.php';

    if($_SERVER['REQUEST_METHOD'] == 'GET'){
        $db = new DataBaseOperations();

        if ($db->setAddTermSolution($_GET['termId'], $_GET['expertId'], $_GET['termType'], $_GET['subpart'], $_GET['superpart'])) {
            if ($db->addSynonyms($_GET['termId'], $_GET['expertId'], $_GET['synonyms'])){
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
