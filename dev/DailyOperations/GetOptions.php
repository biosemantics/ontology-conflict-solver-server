<?php

require_once '../../includes/DataBaseOperations.php';
$response = array(); 

    if($_SERVER['REQUEST_METHOD'] == 'GET'){
        
        $db = new DataBaseOperations();
        $result = $db->getOptions($_GET['ID']);
		    
        //output data of each row
        while($row = $result->fetch_assoc()){
            $term         = $row['term'];
            $option_      = $row['option_'];
            $definition   = $row['definition'];
            $image_link   = $row['image_link'];
            $count        = $db->getCategorySolutionCount($_GET['ID'], $option_, $_GET['expertId'])['count'];
            $data[] = array("option_"=>$option_, 
                            "definition"=>$definition,
                            "image_link"=>$image_link,
                            "count"=>$count
                        );
        }

        $resultNewOption = $db->getNewCategories($_GET['ID']);

        while($row = $resultNewOption->fetch_assoc()) {
            $option_ = $row['choice'];
            $ch = curl_init();
            $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
            $url .= str_replace("_", "%20", $option_);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            $searchResult = json_decode($response);
            $optionDefinition = "";
            $optionImageLink = "";
            foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                if ($indAnnotation->{'property'} == 'http://purl.obolibrary.org/obo/IAO_0000115') {
                    $optionDefinition = $indAnnotation->{'value'};
                }
                if ($indAnnotation->{'property'} == 'http://biosemantics.arizona.edu/ontologies/carex#elucidation') {
                    $optionImageLink = $indAnnotation->{'value'};
                }
            }
            if ($optionDefinition == "") {
                $ch = curl_init();
                $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                $url .= str_replace(" ", "_", $option_);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
                $searchResult = json_decode($response);
                foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                    if ($indAnnotation->{'property'} == 'http://purl.obolibrary.org/obo/IAO_0000115') {
                        $optionDefinition = $indAnnotation->{'value'};
                    }
                    if ($indAnnotation->{'property'} == 'http://biosemantics.arizona.edu/ontologies/carex#elucidation') {
                        $optionImageLink = $indAnnotation->{'value'};
                    }
                }
            }
            if ($optionDefinition == "") {
                $ch = curl_init();
                $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                $url .= $option_;
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
                $searchResult = json_decode($response);
                foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                    if ($indAnnotation->{'property'} == 'http://purl.obolibrary.org/obo/IAO_0000115') {
                        $optionDefinition = $indAnnotation->{'value'};
                    }
                    if ($indAnnotation->{'property'} == 'http://biosemantics.arizona.edu/ontologies/carex#elucidation') {
                        $optionImageLink = $indAnnotation->{'value'};
                    }
                }
            }
            $count = $db->getCategorySolutionCount($_GET['ID'], $option_, $_GET['expertId'])['count'];
            $data[] = array("option_"=>$option_, 
                            "definition"=>$optionDefinition,
                            "image_link"=>$optionImageLink,
                            "count"=>$count
                        );
        }

        $comments = array();
        $curComment = "";
        $commentsResult = $db->getComments($_GET['ID']);
        while ($row = $commentsResult->fetch_assoc()) {
            if ($row['writtenComment'] && $row['writtenComment'] != '') {
                if ($row['expertId'] != $_GET['expertId']) {
                    $comments[] = array("comment"=>$row['writtenComment']);
                } else {
                    $curComment = $row['writtenComment'];
                }
            }
        }

        $termDeclined = $db->isTermDeclinedByExpert($_GET['ID'], $_GET['expertId']);

        $decisions = array();
        $resultDecision = $db->getExpertDecisionOnCategory($_GET['ID'], $_GET['expertId']);
        while ($row = $resultDecision->fetch_assoc()) {
            array_push($decisions, $row['choice']);
        }
        
        $response = array("data" => $data,
                        "comments" => $comments,
                        "curComment" => $curComment,
                        "termDeclined" => $termDeclined,
                        "decisions"=>$decisions);
    }
    echo json_encode(array("options_data"=>$response));
?>
