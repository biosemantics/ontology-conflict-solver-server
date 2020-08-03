<?php

require_once '../../includes/DataBaseOperations.php';

    if($_SERVER['REQUEST_METHOD'] == 'GET'){
        $db = new DataBaseOperations();

        if ($db->declineTerm($_GET['termId'], $_GET['expertId'], $_GET['reason'], $_GET['alternativeTerm'])) {
            return ['error' => false];
        }
        else {
            return ['error' => true, 'message' => 'falure 1'];
        }
    }
?>
