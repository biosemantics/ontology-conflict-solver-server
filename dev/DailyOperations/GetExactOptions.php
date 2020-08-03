<?php

require_once '../../includes/DataBaseOperations.php';
$response = array(); 

    if($_SERVER['REQUEST_METHOD'] == 'GET'){
        
        $db = new DataBaseOperations();
        $termData = $db->getConfusingTermByID($_GET['ID']);
        $result = $db->getExactOptions($_GET['ID']);
		    
        //output data of each row
        while($row = $result->fetch_assoc()){
            $label          = $row['label'];
            $iri            = $row['iri'];
            $elucidations   = $row['elucidations'];
            $sentences      = $row['sentences'];
            $definition     = $row['definition'];
            $count          = $db->getExactSolutionCount($_GET['ID'], $row['id'], $_GET['expertId'])['count'];
            $data[] = array("id"            => $row['id'], 
                            "label"         => $label,
                            "iri"           => $iri,
                            "elucidations"  => $elucidations,
                            "sentences"     => $sentences,
                            "definition"    => $definition,
                            "count"         =>  $count
                        );
        }

        $decisions = array();
        $noneCount = 0;
        $countSolutions = $db->getCountExpertDecisionOnExact($_GET['ID'], $_GET['expertId'])['count'];
        if ($countSolutions != 0) {
            $resultDecision = $db->getExpertDecisionOnExact($_GET['ID'], $_GET['expertId']);
            $count = 0;
            while ($row = $resultDecision->fetch_assoc()) {
                array_push($decisions, $row['caseId']);
                $count ++;
            }
        }

        $exactExperts = $db->getExpertsOnExact($_GET['ID']);
        while ($row = $exactExperts->fetch_assoc()) {
            if ($row['expertId'] != $_GET['expertId']) {
                if ($db->getCountOnExact($_GET['ID'], $row['expertId'])['count'] == 0) {
                    $noneCount ++;
                }
            }
        }

        $reasons = array();
        $curReason = "";
        $reasonResult = $db->getReasons($_GET['ID']);
        while ($row = $reasonResult->fetch_assoc()) {
            if ($row['reason'] && $row['reason'] != '') {
                if ($row['expertId'] != $_GET['expertId']) {
                    $reasons[] = array("comment" => $row['reason']);
                } else {
                    $curReason = $row['reason'];
                }
            }
        }
        
        $response = array("data" => $data,
                        "elucidations" => $termData['elucidations'],
                        "sentences" => $termData['sentences'],
                        "definition" => $termData['definition'],
                        "countSolution" => $countSolutions,
                        "decisions"=>$decisions,
                        "noneCount" => $noneCount,
                        "reasons" => $reasons,
                        "curReason" => $curReason
                    );
    }
    echo json_encode(array("options_data"=>$response));
?>
