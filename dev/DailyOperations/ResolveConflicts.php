<?php
require_once '../../includes/DataBaseOperations.php';

    if($_SERVER['REQUEST_METHOD'] == 'GET') {

        $db = new DataBaseOperations();

        $allTasks = $db->getAllConflictsTasks();
        $currentDate = date("Y-m-d");
        $currentDate1 = date("m-m-y");
        while ( $task = $allTasks->fetch_assoc() ) {
            $termId     = $task['termId'];
            $term       = $task['term'];
            $data       = $task['data'];
            $type       = $task['type'];
            $termIRI    = $task['IRI'];
            if ($type == 'category') {
                $countSolved = $db->countSolvedConflicts($termId);
                $countDeclined = $db->countDeclinedTerm($termId);
                $count      = $countSolved['count'] + $countDeclined['COUNT(expertId)'];
                if ($count > 1) {
                    echo ($termId);
                    echo (" ");
                    echo ($count);
                    echo (" ");
                    echo ($countDeclined['COUNT(expertId)']);
                    echo ("<br/>");
                    if (($countDeclined['COUNT(expertId)'] > 1) && ($countDeclined['COUNT(expertId)'] * 1.0 / $count >= 0.6)) {
                        echo ("strong agreement on decline ");
                        echo ($term);
                        echo ("<br/>");
                        echo ("/deprecate api<br/>");
                        $decisionExperts = "";
                        $reasons = "";
                        $alternativeTerms = "";
                        $resDeclinedData = $db->getDeclinedDataByTermId($termId);
                        while ($rowDeclinedData = $resDeclinedData->fetch_assoc()) {
                            $decisionExperts .= "[";
                            $decisionExperts .= $rowDeclinedData['username'];
                            $decisionExperts .= "]";
                            $reasons .= "[";
                            $reasons .= $rowDeclinedData['reason'];
                            $reasons .= "]";
                            $alternativeTerms .= "[";
                            $alternativeTerms .= $rowDeclinedData['alternativeTerm'];
                            $alternativeTerms .= "]";
                        }
                        echo ("user: ''<br/>");
                        echo ("ontology: 'carex'<br/>");
                        echo ("decisionDate: ");
                        echo ($currentDate);
                        echo ('<br/>');
                        echo ("classIRI: ");
                        echo ($termIRI);
                        echo ("<br/>");
                        echo ("decisionExperts: ");
                        echo ($decisionExperts);
                        echo ("<br/>");
                        echo ("reasons: ");
                        echo ($reasons);
                        echo ("<br/>");
                        echo ("alternativeTerm: ");
                        echo ($alternativeTerms);
                        echo ("<br/>");

                        $ch = curl_init();
                        $url = "http://shark.sbs.arizona.edu:8080/deprecate";
                        $fileds = array(
                            "user" => "",
                            "ontology" => "carex",
                            "decisionDate" => $currentDate,
                            "classIRI" => $termIRI,
                            "decisionExperts" => $decisionExperts,
                            "reasons" => $reasons,
                            "alternativeTerm" => $alternativeTerms
                        );
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                        $result = curl_exec($ch);
                        echo($result);
                        echo ("<br/>");
                        curl_close($ch);

                        echo("/Save api<br/>");
                        $ch = curl_init();
                        $url = "http://shark.sbs.arizona.edu:8080/save";
                        $fileds = array(
                            "user" => "",
                            "ontology" => "carex",
                        );
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                        $result = curl_exec($ch);
                        echo($result);
                        echo ("<br/>");
                        curl_close($ch);

                        $db->markTermDeclined($termId);
                    } else {
                        $options = array();
                        $origOptions = array();
                        $resOptions = $db->getOptions($termId);
                        while ($rowOption = $resOptions->fetch_assoc() ) {
                            $options[] = array("option_"=>$rowOption['option_']);
                            $origOptions[] = array("option_"=>$rowOption['option_']);
                        }
                        $resSolutionOptions = $db->getSolutionOptions($termId);
                        while ($rowSolutionOption = $resSolutionOptions->fetch_assoc() ) {
                            for ($i = 0; $i < count($options); $i ++) {
                                if ($options[$i]['option_'] == $rowSolutionOption['choice']) {
                                    break;
                                }
                            }
                            if ($i == count($options)) {
                                $options[] = array("option_"=>$rowSolutionOption['choice']);
                            }
                        }

                        $solved = 0;
                        for($i = 0; $i < count($options); $i ++) {
                            $option = $options[$i]['option_'];
                            echo ($option);
                            echo (" ");
                            $countAgreed = $db->countExpertAgreedoption($termId, $option);
                            echo ($countAgreed['count']);
                            echo (" ");
                            if ($countAgreed['count'] * 1.0 / $count >= 0.6) {
                                $solved = 1;
                                echo ("strong agreement on ");
                                echo ($option);
                            }
                            echo ("<br/>");
                        }
                        if ($solved == 0) {
                            echo ("no strong agreement. mark this term as bold<br/>");
                            $db->markTermTough($termId);
                        } else {
                            echo ("solved<br/>");
                            for ($i = 0; $i < count($options); $i ++) {
                                $option = $options[$i]['option_'];
                                $countAgreed = $db->countExpertAgreedoption($termId, $option);
                                if ($countAgreed['count'] * 1.0 / $count < 0.6) {
                                    for ($j = 0; $j < count($origOptions); $j ++) {
                                        if ($origOptions[$j]['option_'] == $option) {
                                            break;
                                        }
                                    }
                                    if ($j != count($origOptions)) {
                                        $decisionExperts = "";
                                        $resSolvedConflictsDecision = $db->getSolvedConflictsByIDAndOption($termId, $option);
                                        while ($rowSolvedConflictsDecision = $resSolvedConflictsDecision->fetch_assoc()) {
                                            $decisionExperts .= "[";
                                            $decisionExperts .= $rowSolvedConflictsDecision["username"];
                                            $decisionExperts .= "]";
                                        }
                                        $ch = curl_init();
                                        $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                        $url .= str_replace(" ", "_", $option);
                                        curl_setopt($ch, CURLOPT_URL, $url);
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        $response = curl_exec($ch);
                                        curl_close($ch);
                                        $searchResult = json_decode($response);
                                        $optionIRI = "";
                                        foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                            if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                $optionIRI = $indAnnotation->{'value'};
                                                break;
                                            }
                                        }
                                        if ($optionIRI == "") {
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                            $url .= str_replace("_", "%20", $option);
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            $response = curl_exec($ch);
                                            curl_close($ch);
                                            $searchResult = json_decode($response);
                                            foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                    $optionIRI = $indAnnotation->{'value'};
                                                    break;
                                                }
                                            }
                                        }
                                        if ($optionIRI == "") {
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                            $url .= $option;
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            $response = curl_exec($ch);
                                            curl_close($ch);
                                            $searchResult = json_decode($response);
                                            foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                    $optionIRI = $indAnnotation->{'value'};
                                                    break;
                                                }
                                            }
                                        }
                                        echo ("detach ");
                                        echo ($option);
                                        echo ("<br/>");
                                        echo ("/detachFromSuperClass api<br/>");
                                        echo ("user: ''<br/>");
                                        echo ("ontology: 'carex'<br/>");
                                        echo ("superclassIRI: ");
                                        echo ($optionIRI);
                                        //echo ($option);
                                        echo ("<br/>");
                                        echo ("subclassIRI: ");
                                        echo ($termIRI);
                                        echo ("<br/>");
                                        echo ("decisionDate: ");
                                        echo ($currentDate);
                                        echo ("<br/>");
                                        echo ("decisionExperts: ");
                                        echo ($decisionExperts);
                                        echo ("<br/>");

                                        $url = "http://shark.sbs.arizona.edu:8080/detachFromSuperclass";
                                        $fileds = array(
                                            "user" => "",
                                            "ontology" => "carex",
                                            "superclassIRI" => $optionIRI,
                                            "subclassIRI" => $termIRI,
                                            "decisionDate" => $currentDate,
                                            "decisionExperts" => $decisionExperts
                                        );
                                        $ch = curl_init();
                                        curl_setopt($ch, CURLOPT_URL, $url);
                                        curl_setopt($ch, CURLOPT_POST, true);
                                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                        $result = curl_exec($ch);
                                        echo($result);
                                        echo("<br/>");
                                        curl_close($ch);

                                        echo("/Save api<br/>");
                                        $ch = curl_init();
                                        $url = "http://shark.sbs.arizona.edu:8080/save";
                                        $fileds = array(
                                            "user" => "",
                                            "ontology" => "carex",
                                        );
                                        curl_setopt($ch, CURLOPT_URL, $url);
                                        curl_setopt($ch, CURLOPT_POST, true);
                                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                        $result = curl_exec($ch);
                                        echo($result);
                                        echo ("<br/>");
                                        curl_close($ch);
                                    }
                                } else {
                                    for ($j = 0; $j < count($origOptions); $j ++) {
                                        if ($origOptions[$j]['option_'] == $option) {
                                            break;
                                        }
                                    }
                                    if ($j == count($origOptions)) {
                                        $decisionExperts = "";
                                        $resSolvedConflictsDecision = $db->getSolvedConflictsNewByIDAndOption($termId, $option);
                                        while ($rowSolvedConflictsDecision = $resSolvedConflictsDecision->fetch_assoc()) {
                                            $decisionExperts .= "[";
                                            $decisionExperts .= $rowSolvedConflictsDecision["username"];
                                            $decisionExperts .= "]";
                                        }
                                        $ch = curl_init();
                                        $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                        $url .= str_replace(" ", "_", $option);
                                        curl_setopt($ch, CURLOPT_URL, $url);
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        $response = curl_exec($ch);
                                        curl_close($ch);
                                        $searchResult = json_decode($response);
                                        $optionIRI = "";
                                        foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                            if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                $optionIRI = $indAnnotation->{'value'};
                                                break;
                                            }
                                        }
                                        if ($optionIRI == "") {
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                            $url .= str_replace("_", "%20", $option);
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            $response = curl_exec($ch);
                                            curl_close($ch);
                                            $searchResult = json_decode($response);
                                            foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                    $optionIRI = $indAnnotation->{'value'};
                                                    break;
                                                }
                                            }
                                        }
                                        if ($optionIRI == "") {
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                            $url .= $option;
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            $response = curl_exec($ch);
                                            curl_close($ch);
                                            $searchResult = json_decode($response);
                                            foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                    $optionIRI = $indAnnotation->{'value'};
                                                    break;
                                                }
                                            }
                                        }
                                        echo ("/setSuperclass api<br/>");
                                        echo ("user: ''<br/>");
                                        echo ("ontology: 'carex'<br/>");
                                        echo ("superclassIRI: ");
                                        echo ($optionIRI);
                                        //echo ($option);
                                        echo ("<br/>");
                                        echo ("decisionExperts: ");
                                        echo ($decisionExperts);
                                        echo ("<br/>");
                                        echo ("decisionDate: ");
                                        echo ($currentDate);
                                        echo ("<br/>");
                                        echo ("subclassIRI: ");
                                        echo ($termIRI);
                                        echo ("<br/>");
        
                                        $ch = curl_init();
                                        $url = "http://shark.sbs.arizona.edu:8080/setSuperclass";
                                        $fileds = array(
                                            "user" => "",
                                            "ontology" => "carex",
                                            "superclassIRI" => $optionIRI,
                                            "decisionExperts" => $decisionExperts,
                                            "decisionDate" => $currentDate,
                                            "subclassIRI" => $termIRI
                                        );
                                        curl_setopt($ch, CURLOPT_URL, $url);
                                        curl_setopt($ch, CURLOPT_POST, true);
                                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                        $result = curl_exec($ch);
                                        echo($result);
                                        echo("<br/>");
                                        curl_close($ch);
        
                                        echo("/Save api<br/>");
                                        $ch = curl_init();
                                        $url = "http://shark.sbs.arizona.edu:8080/save";
                                        $fileds = array(
                                            "user" => "",
                                            "ontology" => "carex",
                                        );
                                        curl_setopt($ch, CURLOPT_URL, $url);
                                        curl_setopt($ch, CURLOPT_POST, true);
                                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                        $result = curl_exec($ch);
                                        echo($result);
                                        echo ("<br/>");
                                        curl_close($ch);
                                    }
                                }
                            }
                            
                            $resComments = $db->getComments($termId);
                            while ($rowComment = $resComments->fetch_assoc()) {
                                if ($rowComment['writtenComment'] != '') {
                                    echo ("/comment api<br/>");
                                    echo ("user: ''<br/>");
                                    echo ("ontology: 'carex'<br/>");
                                    echo ("comment: ");
                                    echo ($rowComment['writtenComment']);
                                    echo ("<br/>");
                                    echo ("providedBy: ");
                                    echo ($rowComment['username']);
                                    echo ("<br/>");
                                    echo ("exmapleSentence: ");
                                    echo ($data);
                                    echo ("<br/>");
                                    echo ("classIRI: ");
                                    echo ($termIRI);
                                    echo ("<br/>");

                                    $ch = curl_init();
                                    $url = "http://shark.sbs.arizona.edu:8080/comment";
                                    $fileds = array(
                                        "user" => "",
                                        "ontology" => "carex",
                                        "comment" => $rowComment['writtenComment'],
                                        "providedBy" => $rowComment['username'],
                                        "exampleSentence" => $data,
                                        "classIRI" => $termIRI
                                    );
                                    curl_setopt($ch, CURLOPT_URL, $url);
                                    curl_setopt($ch, CURLOPT_POST, true);
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                    $result = curl_exec($ch);
                                    echo($result);
                                    echo("<br/>");
                                    curl_close($ch);

                                    echo("/Save api<br/>");
                                    $ch = curl_init();
                                    $url = "http://shark.sbs.arizona.edu:8080/save";
                                    $fileds = array(
                                        "user" => "",
                                        "ontology" => "carex",
                                    );
                                    curl_setopt($ch, CURLOPT_URL, $url);
                                    curl_setopt($ch, CURLOPT_POST, true);
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                    $result = curl_exec($ch);
                                    echo($result);
                                    echo ("<br/>");
                                    curl_close($ch);
                                }
                            }
                            if ($countDeclined['COUNT(expertId)'] > 0) {
                                echo ("there is weak rejection. mark this term bold<br/>");
                                $db->markTermTough($termId);
                            } else {
                                echo ("no rejection. resolved<br/>");
                                $db->markTermSolved($termId);
                            }
                        }
                    }
                } else if ($countDeclined['COUNT(expertId)'] == 1) {
                    echo ("only 1 decline. mark this term as bold<br/>");
                    $db->markTermTough($termId);
                }
            } else if ($type == 'synonym') {
                $countSolved = $db->countSolvedApproveConflicts($termId);
                $countDeclined = $db->countDeclinedTerm($termId);
                $count      = $countSolved + $countDeclined['COUNT(expertId)'];
                if ($count > 0) {
                    echo ($termId);
                    echo (" ");
                    echo ($count);
                    echo (" ");
                    echo ($countDeclined['COUNT(expertId)']);
                    echo (" ");
                    if ($countSolved == 0 && $countDeclined['COUNT(expertId)'] == 1) {
                        echo("only 1 declined, mark this term bold<br/>");
                        $db->markTermTough($termId);
                    } else if ($countSolved == 1 && $countDeclined['COUNT(expertId)'] == 0) {
                        $resApproveDataByTerm = $db->getApproveDataByTermID($termId);
                        while ($indApproveDataByTerm = $resApproveDataByTerm->fetch_assoc()) {
                            echo($indApproveDataByTerm['definitionId']);
                            echo("<br/>");
                            $definition = $db->getDefinitionByDefinitionID($indApproveDataByTerm['definitionId'])->fetch_assoc()['definition'];
                            $resSentenceIds = $db->getSentenceIdByDefinitionId($indApproveDataByTerm['definitionId']);
                            $exampleSentence = "";
                            $ind = 0;
                            while ($indSentenceId = $resSentenceIds->fetch_assoc()) {
                                if ($ind != 0) {
                                    $exampleSentence .= ", ";
                                }
                                $exampleSentence .= '"';
                                $exampleSentence .= $db->getSentenceBySentenceId($indSentenceId['sentenceId'])->fetch_assoc()['sentence'];
                                $exampleSentence .= '"';
                                $ind ++;
                            }
                            $decisionExperts = $db->getExpertUsernameById($indApproveDataByTerm['expertId'])['username'];
                            echo ("/definition api<br/>");
                            echo ("user: ''<br/>");
                            echo ("ontology: 'carex'<br/>");
                            echo ("definition: ");
                            echo ($definition);
                            echo ("<br/>");
                            echo ("providedBy: ");
                            echo ($decisionExperts);
                            echo ("<br/>");
                            echo ("exampleSentence: ");
                            echo ($exampleSentence);
                            echo ("<br/>");
                            echo ("classIRI: ");
                            echo ($termIRI);
                            echo ("<br/>");
                            echo ("decisionExperts: ");
                            echo ($decisionExperts." via Conflict Resolver");
                            echo ("<br/>");
                            echo ("decisionDate: ");
                            echo ($currentDate);
                            echo ("<br/>");
                            $decisonExpertsConflict = $decisionExperts." via Conflict Resolver";
                            $ch = curl_init();
                            $url = "http://shark.sbs.arizona.edu:8080/definition";
                            $fileds = array(
                                "user" => "",
                                "ontology" => "carex",
                                "definition" => $definition,
                                "providedBy" => $decisionExperts,
                                "exampleSentence" => $exampleSentence,
                                "classIRI" => $termIRI,
                                "decisionExperts" => $decisonExpertsConflict,
                                "decisionDate" => $currentDate
                            );
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                            echo( curl_exec($ch));
                            echo ("<br/>");
                            curl_close($ch);

                            echo("/Save api<br/>");
                            $ch = curl_init();
                            $url = "http://shark.sbs.arizona.edu:8080/save";
                            $fileds = array(
                                "user" => "",
                                "ontology" => "carex",
                            );
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                            echo( curl_exec($ch));
                            echo ("<br/>");
                            curl_close($ch);
                        }
                        echo("mark this term solved<br/>");
                        $db->markTermSolved($termId);
                    } else if ($countSolved == 1 && $countDeclined['COUNT(expertId)'] == 1) {
                        $resApproveDataByTerm = $db->getApproveDataByTermID($termId);
                        while ($indApproveDataByTerm = $resApproveDataByTerm->fetch_assoc()) {
                            $definition = $db->getDefinitionByDefinitionID($indApproveDataByTerm['definitionId'])->fetch_assoc()['definition'];
                            $resSentenceIds = $db->getSentenceIdByDefinitionId($indApproveDataByTerm['definitionId']);
                            $exampleSentence = "";
                            $ind = 0;
                            while ($indSentenceId = $resSentenceIds->fetch_assoc()) {
                                if ($ind != 0) {
                                    $exampleSentence .= ", ";
                                }
                                $exampleSentence .= '"';
                                $exampleSentence .= $db->getSentenceBySentenceId($indSentenceId['sentenceId'])->fetch_assoc()['sentence'];
                                $exampleSentence .= '"';
                                $ind ++;
                            }
                            $decisionExperts = $db->getExpertUsernameById($indApproveDataByTerm['expertId'])['username'];
                            echo ("/definition api<br/>");
                            echo ("user: ''<br/>");
                            echo ("ontology: 'carex'<br/>");
                            echo ("definition: ");
                            echo ($definition);
                            echo ("<br/>");
                            echo ("providedBy: ");
                            echo ($decisionExperts);
                            echo ("<br/>");
                            echo ("exampleSentence: ");
                            echo ($exampleSentence);
                            echo ("<br/>");
                            echo ("classIRI: ");
                            echo ($termIRI);
                            echo ("<br/>");
                            echo ("decisionExperts: ");
                            echo ($decisionExperts." via Conflict Resolver");
                            echo ("<br/>");
                            echo ("decisionDate: ");
                            echo ($currentDate);
                            echo ("<br/>");
                            $decisonExpertsConflict = $decisionExperts." via Conflict Resolver";
                            $ch = curl_init();
                            $url = "http://shark.sbs.arizona.edu:8080/definition";
                            $fileds = array(
                                "user" => "",
                                "ontology" => "carex",
                                "definition" => $definition,
                                "providedBy" => $decisionExperts,
                                "exampleSentence" => $exampleSentence,
                                "classIRI" => $termIRI,
                                "decisionExperts" => $decisonExpertsConflict,
                                "decisionDate" => $currentDate
                            );
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                            echo( curl_exec($ch));
                            echo ("<br/>");
                            curl_close($ch);

                            echo("/Save api<br/>");
                            $ch = curl_init();
                            $url = "http://shark.sbs.arizona.edu:8080/save";
                            $fileds = array(
                                "user" => "",
                                "ontology" => "carex",
                            );
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                            echo( curl_exec($ch));
                            echo ("<br/>");
                            curl_close($ch);
                        }
                        echo("mark this term tough<br/>");
                        $db->markTermTough($termId);
                    } else if (($countDeclined['COUNT(expertId)'] > 1) && ($countDeclined['COUNT(expertId)'] * 1.0 / $count >= 0.6)) {
                        echo ("strong agreement on decline ");
                        echo ($term);
                        echo ("<br/>");
                        echo ("/deprecate api<br/>");
                        $decisionExperts = "";
                        $reasons = "";
                        $alternativeTerms = "";
                        $resDeclinedData = $db->getDeclinedDataByTermId($termId);
                        while ($rowDeclinedData = $resDeclinedData->fetch_assoc()) {
                            $decisionExperts .= "[";
                            $decisionExperts .= $rowDeclinedData['username'];
                            $decisionExperts .= "]";
                            $reasons .= "[";
                            $reasons .= $rowDeclinedData['reason'];
                            $reasons .= "]";
                            $alternativeTerms .= "[";
                            $alternativeTerms .= $rowDeclinedData['alternativeTerm'];
                            $alternativeTerms .= "]";
                        }
                        echo ("user: ''<br/>");
                        echo ("ontology: 'carex'<br/>");
                        echo ("decisionDate: ");
                        echo ($currentDate);
                        echo ('<br/>');
                        echo ("classIRI: ");
                        echo ($termIRI);
                        echo ("<br/>");
                        echo ("decisionExperts: ");
                        echo ($decisionExperts);
                        echo ("<br/>");
                        echo ("reasons: ");
                        echo ($reasons);
                        echo ("<br/>");
                        echo ("alternativeTerm: ");
                        echo ($alternativeTerms);
                        echo ("<br/>");

                        $ch = curl_init();
                        $url = "http://shark.sbs.arizona.edu:8080/deprecate";
                        $fileds = array(
                            "user" => "",
                            "ontology" => "carex",
                            "decisionDate" => $currentDate,
                            "classIRI" => $termIRI,
                            "decisionExperts" => $decisionExperts,
                            "reasons" => $reasons,
                            "alternativeTerm" => $alternativeTerms
                        );
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                        $result = curl_exec($ch);
                        echo($result);
                        echo ("<br/>");
                        curl_close($ch);

                        echo("/Save api<br/>");
                        $ch = curl_init();
                        $url = "http://shark.sbs.arizona.edu:8080/save";
                        $fileds = array(
                            "user" => "",
                            "ontology" => "carex",
                        );
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                        $result = curl_exec($ch);
                        echo($result);
                        echo ("<br/>");
                        curl_close($ch);

                        $db->markTermDeclined($termId);
                    } else {
                        $resSolvedExperts = $db->getSolvedApproveConflictsExperts($termId);
                        $solvedExperts = array();
                        while ($rowSolvedExpert = $resSolvedExperts->fetch_assoc() ) {
                            $solvedExperts[] = array("expertId"=>$rowSolvedExpert['expertId']);
                        }
                        $solvedFlg = 0;
                        $orginCount = $db->getCountDefinitionsByTermId($termId);
                        if ($orginCount['count'] > 0) {
                            echo ($orginCount['count']);
                            echo ("<br/>");
                            $result = $db->getDefinitionsByTermId($termId);
                            $resSentences = $db->getSentences($termId);
                            $sentences = array();
                            while ($rowSentence = $resSentences->fetch_assoc() ) {
                                $sentences[] = array("id"=>$rowSentence['id'],"sentence"=>$rowSentence['sentence']);
                            }
                            while ( $row = $result->fetch_assoc() ) {
                                $definition = $row['definition'];
                                $definitionId = $row['id'];
                                $exampleSentence = "";
                                $decisionExperts = "";
                                $experts = array();
                                $ind = 0;

                                for ($i = 0; $i < count($sentences); $i ++) {
                                    $synonymSolutionCount = $db->getCountSynonymSolutionExpert($definitionId, $sentences[$i]['id']);
                                    if ($synonymSolutionCount * 1.0 / $count >= 0.6) {
                                        if ($ind != 0) {
                                            $exampleSentence .= ", ";
                                        }
                                        if ($ind == 0) {
                                            $resultExperts = $db->getSolutionSentencesExpertsBySentence($definitionId, $sentences[$i]['id']);
                                            while ($rowExpert = $resultExperts->fetch_assoc()) {
                                                $decisionExperts .= '[';
                                                $decisionExperts .= $rowExpert['username'];
                                                $decisionExperts .= ']';
                                            }
                                        }
                                        $exampleSentence .= '"';
                                        $exampleSentence .= $sentences[$i]['sentence'];
                                        $exampleSentence .= '"';
                                        $ind ++;
                                    }
                                }

                                if ($exampleSentence != "") {
                                    $solvedFlg = 1;
                                    echo ("/definition api<br/>");
                                    echo ("user: ''<br/>");
                                    echo ("ontology: 'carex'<br/>");
                                    echo ("definition: ");
                                    echo ($definition);
                                    echo ("<br/>");
                                    echo ("providedBy: ");
                                    echo ($decisionExperts);
                                    echo ("<br/>");
                                    echo ("exampleSentence: ");
                                    echo ($exampleSentence);
                                    echo ("<br/>");
                                    echo ("classIRI: ");
                                    echo ($termIRI);
                                    echo ("<br/>");
                                    echo ("decisionExperts: ");
                                    echo ($decisionExperts." via Conflict Resolver");
                                    echo ("<br/>");
                                    echo ("decisionDate: ");
                                    echo ($currentDate);
                                    echo ("<br/>");
                                    $decisonExpertsConflict = $decisionExperts." via Conflict Resolver";
                                    $ch = curl_init();
                                    $url = "http://shark.sbs.arizona.edu:8080/definition";
                                    $fileds = array(
                                        "user" => "",
                                        "ontology" => "carex",
                                        "definition" => $definition,
                                        "providedBy" => $decisionExperts,
                                        "exampleSentence" => $exampleSentence,
                                        "classIRI" => $termIRI,
                                        "decisionExperts" => $decisonExpertsConflict,
                                        "decisionDate" => $currentDate
                                    );
                                    curl_setopt($ch, CURLOPT_URL, $url);
                                    curl_setopt($ch, CURLOPT_POST, true);
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                    echo( curl_exec($ch));
                                    echo ("<br/>");
                                    curl_close($ch);

                                    echo("/Save api<br/>");
                                    $ch = curl_init();
                                    $url = "http://shark.sbs.arizona.edu:8080/save";
                                    $fileds = array(
                                        "user" => "",
                                        "ontology" => "carex",
                                    );
                                    curl_setopt($ch, CURLOPT_URL, $url);
                                    curl_setopt($ch, CURLOPT_POST, true);
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                    echo( curl_exec($ch));
                                    echo ("<br/>");
                                    curl_close($ch);
                                }
                            }

                            if ($solvedFlg == 1) {
                                if ($countDeclined['COUNT(expertId)'] > 0) {
                                    echo ("there is weak rejection. mark this term bold<br/>");
                                    $db->markTermTough($termId);
                                } else {
                                    echo ("no rejection. resolved<br/>");
                                    $db->markTermSolved($termId);
                                }
                                $resComments = $db->getApproveTermCommentsByTermId($termId);
                                while ($rowComment = $resComments->fetch_assoc()) {
                                    if ($rowComment['comment'] != '') {
                                        echo ("/comment api<br/>");
                                        echo ("user: ''<br/>");
                                        echo ("ontology: 'carex'<br/>");
                                        echo ("comment: ");
                                        echo ($rowComment['comment']);
                                        echo ("<br/>");
                                        echo ("providedBy: ");
                                        $username = $db->getExpertUsernameById($rowComment['expertId'])['username'];
                                        echo ($username);
                                        echo ("<br/>");
                                        echo ("exmapleSentence: ");
                                        echo ($data);
                                        echo ("<br/>");
                                        echo ("classIRI: ");
                                        echo ($termIRI);
                                        echo ("<br/>");

                                        $ch = curl_init();
                                        $url = "http://shark.sbs.arizona.edu:8080/comment";
                                        $fileds = array(
                                            "user" => "",
                                            "ontology" => "carex",
                                            "comment" => $rowComment['comment'],
                                            "providedBy" => $username,
                                            "exampleSentence" => $data,
                                            "classIRI" => $termIRI
                                        );
                                        curl_setopt($ch, CURLOPT_URL, $url);
                                        curl_setopt($ch, CURLOPT_POST, true);
                                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                        $result = curl_exec($ch);
                                        echo($result);
                                        echo("<br/>");
                                        curl_close($ch);

                                        echo("/Save api<br/>");
                                        $ch = curl_init();
                                        $url = "http://shark.sbs.arizona.edu:8080/save";
                                        $fileds = array(
                                            "user" => "",
                                            "ontology" => "carex",
                                        );
                                        curl_setopt($ch, CURLOPT_URL, $url);
                                        curl_setopt($ch, CURLOPT_POST, true);
                                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                        $result = curl_exec($ch);
                                        echo($result);
                                        echo ("<br/>");
                                        curl_close($ch);
                                    }
                                }
                            } else {
                                echo("Mark this term tough <br/>");
                                $db->markTermTough($termId);
                            }
                        }
                    }
                }
            } else if ($type == 'addTerm') {
                $countSolved = $db->countSolvedAddTermConflicts($termId);
                $countDeclined = $db->countDeclinedTerm($termId);
                $count      = $countSolved['COUNT(expertId)'] + $countDeclined['COUNT(expertId)'];
                $termSolved = 0;
                if ($count > 1) {
                    echo ($termId);
                    echo (" ");
                    echo ($count);
                    echo (" ");
                    echo ($countDeclined['COUNT(expertId)']);
                    echo (" ");
                    if (($countDeclined['COUNT(expertId)'] > 1) && ($countDeclined['COUNT(expertId)'] * 1.0 / $count >= 0.6)) {
                        echo ("strong agreement on decline ");
                        echo ($term);
                        echo ("<br/>");
                        echo ("/deprecate api<br/>");
                        $decisionExperts = "";
                        $reasons = "";
                        $alternativeTerms = "";
                        $resDeclinedData = $db->getDeclinedDataByTermId($termId);
                        while ($rowDeclinedData = $resDeclinedData->fetch_assoc()) {
                            $decisionExperts .= "[";
                            $decisionExperts .= $rowDeclinedData['username'];
                            $decisionExperts .= "]";
                            $reasons .= "[";
                            $reasons .= $rowDeclinedData['reason'];
                            $reasons .= "]";
                            $alternativeTerms .= "[";
                            $alternativeTerms .= $rowDeclinedData['alternativeTerm'];
                            $alternativeTerms .= "]";
                        }
                        echo ("user: ''<br/>");
                        echo ("ontology: 'carex'<br/>");
                        echo ("decisionDate: ");
                        echo ($currentDate);
                        echo ('<br/>');
                        echo ("classIRI: ");
                        echo ($termIRI);
                        echo ("<br/>");
                        echo ("decisionExperts: ");
                        echo ($decisionExperts);
                        echo ("<br/>");
                        echo ("reasons: ");
                        echo ($reasons);
                        echo ("<br/>");
                        echo ("alternativeTerm: ");
                        echo ($alternativeTerms);
                        echo ("<br/>");

                        $ch = curl_init();
                        $url = "http://shark.sbs.arizona.edu:8080/deprecate";
                        $fileds = array(
                            "user" => "",
                            "ontology" => "carex",
                            "decisionDate" => $currentDate,
                            "classIRI" => $termIRI,
                            "decisionExperts" => $decisionExperts,
                            "reasons" => $reasons,
                            "alternativeTerm" => $alternativeTerms
                        );
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                        $result = curl_exec($ch);
                        echo($result);
                        echo ("<br/>");
                        curl_close($ch);

                        echo("/Save api<br/>");
                        $ch = curl_init();
                        $url = "http://shark.sbs.arizona.edu:8080/save";
                        $fileds = array(
                            "user" => "",
                            "ontology" => "carex",
                        );
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                        $result = curl_exec($ch);
                        echo($result);
                        echo ("<br/>");
                        curl_close($ch);

                        $db->markTermDeclined($termId);
                    } else {
                        $resAddTermSolutions = $db->getAddTermSolutionByTermId($termId);
                        $addTermSolutions = array();
                        $characterCount = 0;
                        $structureCount = 0;
                        $strongType = "";
                        while ($rowAddTermSolution = $resAddTermSolutions->fetch_assoc() ) {
                            $addTermSolutions[] = array("expertId"=>$rowAddTermSolution['expertId'], 
                                                        "type"=>$rowAddTermSolution['type'], 
                                                        "subpart"=>$rowAddTermSolution['subpart'], 
                                                        "superpart"=>$rowAddTermSolution['superpart'],
                                                        "alwaysHasPart"=>$rowAddTermSolution['alwaysHasPart'],
                                                        "alwaysPartOf"=>$rowAddTermSolution['alwaysPartOf'],
                                                        "maybePartOf"=>$rowAddTermSolution['maybePartOf'],
                                                        "subclassOf"=>$rowAddTermSolution['subclassOf'],
                                                        "username"=>$rowAddTermSolution['username']);
                            if ($rowAddTermSolution['type'] == 'Character') {
                                $characterCount ++;
                            } else {
                                $structureCount ++;
                            }
                        }
                        echo("<br/>");
                        echo("Character ");
                        echo($characterCount);
                        echo("<br/>");
                        echo("Structure ");
                        echo($structureCount);
                        echo("<br/>");
                        if ($characterCount * 1.0 / $count >= 0.6) {
                            echo("Strong agreement on character<br/>");
                            $strongType = "Character";
                            for ($i = 0; $i < count($addTermSolutions); $i ++) {
                                if ($addTermSolutions[$i]['type'] == 'Character') {
                                    $curCount = 0;
                                    $decisionExperts = "";
                                    for ($j = 0; $j < count($addTermSolutions); $j ++) {
                                        if ($addTermSolutions[$i]['subpart'] == $addTermSolutions[$j]['subpart']) {
                                            $curCount ++;
                                            $decisionExperts .= "[";
                                            $decisionExperts .= $addTermSolutions[$j]['username'];
                                            $decisionExperts .= "]";
                                        }
                                    }
                                    echo($addTermSolutions[$i]['subpart']);
                                    echo(" ");
                                    echo($curCount);
                                    echo(" ");
                                    if ($curCount * 1.0 / $characterCount >= 0.6) {
                                        echo("strong agreement");
                                        echo("<br/>");
                                        $termSolved = 1;

                                        $ch = curl_init();
                                        $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                        $url .= str_replace(" ", "_", $addTermSolutions[$i]['subpart']);
                                        curl_setopt($ch, CURLOPT_URL, $url);
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        $response = curl_exec($ch);
                                        curl_close($ch);
                                        $searchResult = json_decode($response);
                                        $subPartIRI = "";
                                        foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                            if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                $subPartIRI = $indAnnotation->{'value'};
                                                break;
                                            }
                                        }

                                        if ($subPartIRI == "") {
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                            $url .= str_replace("_", "%20", $addTermSolutions[$i]['subpart']);
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            $response = curl_exec($ch);
                                            curl_close($ch);
                                            $searchResult = json_decode($response);
                                            foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                    $subPartIRI = $indAnnotation->{'value'};
                                                    break;
                                                }
                                            }
                                        }
                                        if ($subPartIRI == "") {
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                            $url .= $addTermSolutions[$i]['subpart'];
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            $response = curl_exec($ch);
                                            curl_close($ch);
                                            $searchResult = json_decode($response);
                                            foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                    $subPartIRI = $indAnnotation->{'value'};
                                                    break;
                                                }
                                            }
                                        }
                                        
                                        echo ("/moveFromToreviewToSuperclass api<br/>");
                                        echo ("user: ''<br/>");
                                        echo ("ontology: 'carex'<br/>");
                                        echo ("subclassIRI: ");
                                        echo ($termIRI);
                                        echo ("<br/>");
                                        echo ("superclassIRI: ");
                                        echo ($subPartIRI);
                                        //echo ($addTermSolutions[$i]['subpart']);
                                        echo ("<br/>");
                                        echo ("subclassTerm: ");
                                        echo ($term);
                                        echo ("<br/>");
                                        echo ("decisionExperts: ");
                                        echo ($decisionExperts);
                                        echo ("<br/>");
                                        echo ("decisionDate: ");
                                        echo ($currentDate);
                                        echo ('<br/>');

                                        $ch = curl_init();
                                        $url = "http://shark.sbs.arizona.edu:8080/moveFromToreviewToSuperclass";
                                        $fileds = array(
                                            "user" => "",
                                            "ontology" => "carex",
                                            "subclassIRI" => $termIRI,
                                            "superclassIRI" => $subPartIRI,
                                            "subclassTerm" => $term,
                                            "decisionExperts" => $decisionExperts,
                                            "decisionDate" => $currentDate
                                        );
                                        curl_setopt($ch, CURLOPT_URL, $url);
                                        curl_setopt($ch, CURLOPT_POST, true);
                                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                        $result = curl_exec($ch);
                                        echo ($result);
                                        echo ("<br/>");
                                        curl_close($ch);

                                        echo("/Save api<br/>");
                                        $ch = curl_init();
                                        $url = "http://shark.sbs.arizona.edu:8080/save";
                                        $fileds = array(
                                            "user" => "",
                                            "ontology" => "carex",
                                        );
                                        curl_setopt($ch, CURLOPT_URL, $url);
                                        curl_setopt($ch, CURLOPT_POST, true);
                                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                        $result = curl_exec($ch);
                                        echo($result);
                                        echo ("<br/>");
                                        curl_close($ch);
                                        break;
                                    }
                                }
                            }
                            if ($i == count($addTermSolutions)) {
                                echo("no strong agreement, mark bold</br>");
                                $termSolved = 2;
                            }
                        } else if ($structureCount * 1.0 / $count >= 0.6) {
                            echo("Strong agreement on structure<br/>");
                            $strongType = "Structure";

                            $subpartData = array();
                            $subpartCount = 0;
                            $superpartData = array();
                            $superpartCount = 0;
                            $alwaysHasPartData = array();
                            $alwaysHasPartCount = 0;
                            $alwaysPartOfData = array();
                            $alwaysPartOfCount = 0;
                            $maybePartOfData = array();
                            $maybePartOfCount = 0;
                            $subclassOfData = array();
                            $subclassOfCount = 0;
                            $termSolved = 1;

                            for ($i = 0; $i < count($addTermSolutions); $i ++) {

                                if ($addTermSolutions[$i]['type'] == 'Structure') {
                                    $subpart = $addTermSolutions[$i]['subpart'];
                                    if ($subpart && $subpart != '') {
                                        $subpartCount ++;
                                        $subpartpiece = explode(",", $subpart);
                                        for ($k = 0; $k < count($subpartpiece); $k ++) {
                                            for ($j = 0; $j < count($subpartData); $j ++) {
                                                if ($subpartData[$j]['name'] == $subpartpiece[$k]) {
                                                    break;
                                                }
                                            }
                                            if ($j == count($subpartData)) {
                                                $subpartData[] = array("name"=>$subpartpiece[$k],
                                                                        "count"=>1);
                                            } else {
                                                $subpartData[$j]['count'] ++;
                                            }
                                        }
                                    }
        
                                    $superpart = $addTermSolutions[$i]['superpart'];
                                    if ($superpart && $superpart != '') {
                                        $superpartCount ++;
                                        $superpartpiece = explode(",", $superpart);
                                        for ($k = 0; $k < count($superpartpiece); $k ++) {
                                            for ($j = 0; $j < count($superpartData); $j ++) {
                                                if ($superpartData[$j]['name'] == $superpartpiece[$k]) {
                                                    break;
                                                }
                                            }
                                            if ($j == count($superpartData)) {
                                                $superpartData[] = array("name"=>$superpartpiece[$k],
                                                                        "count"=>1);
                                            } else {
                                                $superpartData[$j]['count'] ++;
                                            }
                                        }
                                    }
        
                                    $alwaysHasPart = $addTermSolutions[$i]['alwaysHasPart'];
                                    if ($alwaysHasPart && $alwaysHasPart != '') {
                                        $alwaysHasPartCount ++;
                                        $alwaysHasPartpiece = explode(",", $alwaysHasPart);
                                        for ($k = 0; $k < count($alwaysHasPartpiece); $k ++) {
                                            for ($j = 0; $j < count($alwaysHasPartData); $j ++) {
                                                if ($alwaysHasPartData[$j]['name'] == $alwaysHasPartpiece[$k]) {
                                                    break;
                                                }
                                            }
                                            if ($j == count($alwaysHasPartData)) {
                                                $alwaysHasPartData[] = array("name"=>$alwaysHasPartpiece[$k],
                                                                        "count"=>1);
                                            } else {
                                                $alwaysHasPartData[$j]['count'] ++;
                                            }
                                        }
                                    }
        
                                    $alwaysPartOf = $addTermSolutions[$i]['alwaysPartOf'];
                                    if ($alwaysPartOf && $alwaysPartOf != '') {
                                        $alwaysPartOfCount ++;
                                        $alwaysPartOfpiece = explode(",", $alwaysPartOf);
                                        for ($k = 0; $k < count($alwaysPartOfpiece); $k ++) {
                                            for ($j = 0; $j < count($alwaysPartOfData); $j ++) {
                                                if ($alwaysPartOfData[$j]['name'] == $alwaysPartOfpiece[$k]) {
                                                    break;
                                                }
                                            }
                                            if ($j == count($alwaysPartOfData)) {
                                                $alwaysPartOfData[] = array("name"=>$alwaysPartOfpiece[$k],
                                                                        "count"=>1);
                                            } else {
                                                $alwaysPartOfData[$j]['count'] ++;
                                            }
                                        }
                                    }
        
                                    $maybePartOf = $addTermSolutions[$i]['maybePartOf'];
                                    if ($maybePartOf && $maybePartOf != '') {
                                        $maybePartOfCount ++;
                                        $maybePartOfpiece = explode(",", $maybePartOf);
                                        for ($k = 0; $k < count($maybePartOfpiece); $k ++) {
                                            for ($j = 0; $j < count($maybePartOfData); $j ++) {
                                                if ($maybePartOfData[$j]['name'] == $maybePartOfpiece[$k]) {
                                                    break;
                                                }
                                            }
                                            if ($j == count($maybePartOfData)) {
                                                $maybePartOfData[] = array("name"=>$maybePartOfpiece[$k],
                                                                        "count"=>1);
                                            } else {
                                                $maybePartOfData[$j]['count'] ++;
                                            }
                                        }
                                    }
        
                                    $subclassOf = $addTermSolutions[$i]['subclassOf'];
                                    if ($subclassOf && $subclassOf != '') {
                                        $subclassOfCount ++;
                                        $subclassOfpiece = explode(",", $subclassOf);
                                        for ($k = 0; $k < count($subclassOfpiece); $k ++) {
                                            for ($j = 0; $j < count($subclassOfData); $j ++) {
                                                if ($subclassOfData[$j]['name'] == $subclassOfpiece[$k]) {
                                                    break;
                                                }
                                            }
                                            if ($j == count($subclassOfData)) {
                                                $subclassOfData[] = array("name"=>$subclassOfpiece[$k],
                                                                        "count"=>1);
                                            } else {
                                                $subclassOfData[$j]['count'] ++;
                                            }
                                        }
                                    }
                                }
                            }

                            if (count($subclassOfData) > 0) {
                                echo("subclassOf ");
                                echo($subclassOfCount);
                                echo("<br/>");
                                if ($subclassOfCount > 1) {
                                    $flgStructure = 0;
                                    for ($j = 0; $j < count($subclassOfData); $j ++) {
                                        echo($subclassOfData[$j]['name']);
                                        echo(" ");
                                        echo($subclassOfData[$j]['count']);
                                        echo("<br/>");
                                        if ($subclassOfData[$j]['count'] * 1.0 / $subclassOfCount >= 0.6) {
                                            $flgStructure = 1;
                                            $decisionExperts = "";
                                            for ($k = 0; $k < count($addTermSolutions); $k ++) {
                                                if ($addTermSolutions[$k]['type'] == 'Structure') {
                                                    if (strpos($addTermSolutions[$k]['subclassOf'], $subclassOfData[$j]['name']) >= 0 ) {
                                                        $decisionExperts .= "[";
                                                        $decisionExperts .= $addTermSolutions[$k]['username'];
                                                        $decisionExperts .= "]";
                                                    }
                                                }
                                            }
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                            $url .= str_replace(" ", "_", $subclassOfData[$j]['name']);
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            $response = curl_exec($ch);
                                            curl_close($ch);
                                            $searchResult = json_decode($response);
                                            $strongIRI = "";
                                            foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                    $strongIRI = $indAnnotation->{'value'};
                                                    break;
                                                }
                                            }

                                            if ($strongIRI == "") {
                                                $ch = curl_init();
                                                $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                                $url .= str_replace("_", "%20", $subclassOfData[$j]['name']);
                                                curl_setopt($ch, CURLOPT_URL, $url);
                                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                $response = curl_exec($ch);
                                                curl_close($ch);
                                                $searchResult = json_decode($response);
                                                foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                    if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                        $strongIRI = $indAnnotation->{'value'};
                                                        break;
                                                    }
                                                }
                                            }
                                            if ($strongIRI == "") {
                                                $ch = curl_init();
                                                $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                                $url .= $subclassOfData[$j]['name'];
                                                curl_setopt($ch, CURLOPT_URL, $url);
                                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                $response = curl_exec($ch);
                                                curl_close($ch);
                                                $searchResult = json_decode($response);
                                                foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                    if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                        $strongIRI = $indAnnotation->{'value'};
                                                        break;
                                                    }
                                                }
                                            }
                                            
                                            echo ("/moveFromToreviewToSuperclass api<br/>");
                                            echo ("user: ''<br/>");
                                            echo ("ontology: 'carex'<br/>");
                                            echo ("subclassIRI: ");
                                            echo ($termIRI);
                                            echo ("<br/>");
                                            echo ("superclassIRI: ");
                                            echo ($strongIRI);
                                            //echo ($subclassOfData[$j]['name']);
                                            echo ("<br/>");
                                            echo ("subclassTerm: ");
                                            echo ($term);
                                            echo ("<br/>");
                                            echo ("decisionExperts: ");
                                            echo ($decisionExperts);
                                            echo ("<br/>");
                                            echo ("decisionDate: ");
                                            echo ($currentDate);
                                            echo ('<br/>');

                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/moveFromToreviewToSuperclass";
                                            $fileds = array(
                                                "user" => "",
                                                "ontology" => "carex",
                                                "subclassIRI" => $termIRI,
                                                "superclassIRI" => $strongIRI,
                                                "subclassTerm" => $term,
                                                "decisionExperts" => $decisionExperts,
                                                "decisionDate" => $currentDate
                                            );
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_POST, true);
                                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                            $result = curl_exec($ch);
                                            echo ($result);
                                            echo ("<br/>");
                                            curl_close($ch);

                                            echo("/Save api<br/>");
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/save";
                                            $fileds = array(
                                                "user" => "",
                                                "ontology" => "carex",
                                            );
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_POST, true);
                                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                            $result = curl_exec($ch);
                                            echo($result);
                                            echo ("<br/>");
                                            curl_close($ch);

                                            for ($k = 0; $k < count($addTermSolutions); $k ++) {
                                                if ($addTermSolutions[$k]['type'] == 'Structure') {
                                                    if (strpos($addTermSolutions[$k]['subclassOf'], $subclassOfData[$j]['name']) >= 0 ) {
                                                        $resSynonyms = $db->getSynonymsByTermIdExpertId($termId, $addTermSolutions[$k]['expertId']);

                                                        while ($rowSynonym = $resSynonyms->fetch_assoc()) {
                                                            echo ("/esynonym api<br/>");
                                                            echo ("user: ''<br/>");
                                                            echo ("ontology: 'carex'<br/>");
                                                            echo ("term: ");
                                                            echo ($rowSynonym['synonym']);
                                                            echo ("<br/>");
                                                            echo ("decisionExperts: ");
                                                            echo ($rowSynonym['username']);
                                                            echo ("<br/>");
                                                            echo ("decisionDate: ");
                                                            echo ($currentDate);
                                                            echo ('<br/>');
            
                                                            $ch = curl_init();
                                                            $url = "http://shark.sbs.arizona.edu:8080/esynonym";
                                                            $fileds = array(
                                                                "user" => "",
                                                                "ontology" => "carex",
                                                                "term" => $rowSynonym['synonym'],
                                                                "classIRI" => $termIRI,
                                                                "decisionExperts" => $rowSynonym['username'],
                                                                "decisionDate" => $currentDate
                                                            );
                                                            curl_setopt($ch, CURLOPT_URL, $url);
                                                            curl_setopt($ch, CURLOPT_POST, true);
                                                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                                            $result = curl_exec($ch);
                                                            echo ($result);
                                                            echo ("<br/>");
                                                            curl_close($ch);
            
                                                            echo("/Save api<br/>");
                                                            $ch = curl_init();
                                                            $url = "http://shark.sbs.arizona.edu:8080/save";
                                                            $fileds = array(
                                                                "user" => "",
                                                                "ontology" => "carex",
                                                            );
                                                            curl_setopt($ch, CURLOPT_URL, $url);
                                                            curl_setopt($ch, CURLOPT_POST, true);
                                                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                                            $result = curl_exec($ch);
                                                            echo($result);
                                                            echo ("<br/>");
                                                            curl_close($ch);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    if ($flgStructure == 0) {
                                        $termSolved = 2;
                                    }
                                }
                            }

                            if (count($subpartData) > 0) {
                                echo("subpart ");
                                echo($subpartCount);
                                echo("<br/>");
                                if ($subpartCount > 1) {
                                    $flgStructure = 0;
                                    for ($j = 0; $j < count($subpartData); $j ++) {
                                        echo($subpartData[$j]['name']);
                                        echo(" ");
                                        echo($subpartData[$j]['count']);
                                        echo("<br/>");
                                        if ($subpartData[$j]['count'] * 1.0 / $subpartCount >= 0.6) {
                                            $flgStructure = 1;
                                            $decisionExperts = "";
                                            for ($k = 0; $k < count($addTermSolutions); $k ++) {
                                                if ($addTermSolutions[$k]['type'] == 'Structure') {
                                                    if (strpos($addTermSolutions[$k]['subpart'], $subpartData[$j]['name']) >= 0 ) {
                                                        $decisionExperts .= "[";
                                                        $decisionExperts .= $addTermSolutions[$k]['username'];
                                                        $decisionExperts .= "]";
                                                    }
                                                }
                                            }
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                            $url .= str_replace(" ", "_", $subpartData[$j]['name']);
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            $response = curl_exec($ch);
                                            curl_close($ch);
                                            $searchResult = json_decode($response);
                                            $strongIRI = "";
                                            foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                    $strongIRI = $indAnnotation->{'value'};
                                                    break;
                                                }
                                            }

                                            if ($strongIRI == "") {
                                                $ch = curl_init();
                                                $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                                $url .= str_replace("_", "%20", $subpartData[$j]['name']);
                                                curl_setopt($ch, CURLOPT_URL, $url);
                                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                $response = curl_exec($ch);
                                                curl_close($ch);
                                                $searchResult = json_decode($response);
                                                foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                    if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                        $strongIRI = $indAnnotation->{'value'};
                                                        break;
                                                    }
                                                }
                                            }
                                            if ($strongIRI == "") {
                                                $ch = curl_init();
                                                $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                                $url .= $subpartData[$j]['name'];
                                                curl_setopt($ch, CURLOPT_URL, $url);
                                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                $response = curl_exec($ch);
                                                curl_close($ch);
                                                $searchResult = json_decode($response);
                                                foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                    if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                        $strongIRI = $indAnnotation->{'value'};
                                                        break;
                                                    }
                                                }
                                            }
                                            
                                            echo ("/partOf api<br/>");
                                            echo ("user: ''<br/>");
                                            echo ("ontology: 'carex'<br/>");
                                            echo ("bearerIRI: ");
                                            echo ($termIRI);
                                            echo ("<br/>");
                                            echo ("partIRI: ");
                                            //echo ($subpartData[$j]['name']);
                                            echo ($strongIRI);
                                            echo ("<br/>");
                                            echo ("decisionExperts: ");
                                            echo ($decisionExperts);
                                            echo ("<br/>");
                                            echo ("decisionDate: ");
                                            echo ($currentDate);
                                            echo ('<br/>');

                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/partOf";
                                            $fileds = array(
                                                "user" => "",
                                                "ontology" => "carex",
                                                "bearerIRI" => $termIRI,
                                                "partIRI" => $strongIRI,
                                                "decisionExperts" => $decisionExperts,
                                                "decisionDate" => $currentDate
                                            );
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_POST, true);
                                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                            $result = curl_exec($ch);
                                            echo ($result);
                                            echo ("<br/>");
                                            curl_close($ch);

                                            echo("/Save api<br/>");
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/save";
                                            $fileds = array(
                                                "user" => "",
                                                "ontology" => "carex",
                                            );
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_POST, true);
                                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                            $result = curl_exec($ch);
                                            echo($result);
                                            echo ("<br/>");
                                            curl_close($ch);
                                        }
                                    }
                                    if ($flgStructure == 0) {
                                        // $termSolved = 2;
                                    }
                                }
                            }

                            if (count($superpartData) > 0) {
                                echo("superpart ");
                                echo($superpartCount);
                                echo("<br/>");
                                if ($superpartCount > 1) {
                                    $flgStructure = 0;
                                    for ($j = 0; $j < count($superpartData); $j ++) {
                                        echo($superpartData[$j]['name']);
                                        echo(" ");
                                        echo($superpartData[$j]['count']);
                                        echo("<br/>");
                                        if ($superpartData[$j]['count'] * 1.0 / $superpartCount >= 0.6) {
                                            $flgStructure = 1;
                                            $decisionExperts = "";
                                            for ($k = 0; $k < count($addTermSolutions); $k ++) {
                                                if ($addTermSolutions[$k]['type'] == 'Structure') {
                                                    if (strpos($addTermSolutions[$k]['superpart'], $superpartData[$j]['name']) >= 0 ) {
                                                        $decisionExperts .= "[";
                                                        $decisionExperts .= $addTermSolutions[$k]['username'];
                                                        $decisionExperts .= "]";
                                                    }
                                                }
                                            }
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                            $url .= str_replace(" ", "_", $superpartData[$j]['name']);
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            $response = curl_exec($ch);
                                            curl_close($ch);
                                            $searchResult = json_decode($response);
                                            $strongIRI = "";
                                            foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                    $strongIRI = $indAnnotation->{'value'};
                                                    break;
                                                }
                                            }

                                            if ($strongIRI == "") {
                                                $ch = curl_init();
                                                $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                                $url .= str_replace("_", "%20", $superpartData[$j]['name']);
                                                curl_setopt($ch, CURLOPT_URL, $url);
                                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                $response = curl_exec($ch);
                                                curl_close($ch);
                                                $searchResult = json_decode($response);
                                                foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                    if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                        $strongIRI = $indAnnotation->{'value'};
                                                        break;
                                                    }
                                                }
                                            }
                                            if ($strongIRI == "") {
                                                $ch = curl_init();
                                                $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                                $url .= $superpartData[$j]['name'];
                                                curl_setopt($ch, CURLOPT_URL, $url);
                                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                $response = curl_exec($ch);
                                                curl_close($ch);
                                                $searchResult = json_decode($response);
                                                foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                    if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                        $strongIRI = $indAnnotation->{'value'};
                                                        break;
                                                    }
                                                }
                                            }
                                            
                                            echo ("/hasPart api<br/>");
                                            echo ("user: ''<br/>");
                                            echo ("ontology: 'carex'<br/>");
                                            echo ("bearerIRI: ");
                                            //echo ($superpartData[$j]['name']);
                                            echo ($strongIRI);
                                            echo ("<br/>");
                                            echo ("partIRI: ");
                                            echo ($termIRI);
                                            echo ("<br/>");
                                            echo ("decisionExperts: ");
                                            echo ($decisionExperts);
                                            echo ("<br/>");
                                            echo ("decisionDate: ");
                                            echo ($currentDate);
                                            echo ('<br/>');

                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/hasPart";
                                            $fileds = array(
                                                "user" => "",
                                                "ontology" => "carex",
                                                "bearerIRI" => $strongIRI,
                                                "partIRI" => $termIRI,
                                                "decisionExperts" => $decisionExperts,
                                                "decisionDate" => $currentDate
                                            );
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_POST, true);
                                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                            $result = curl_exec($ch);
                                            echo ($result);
                                            echo ("<br/>");
                                            curl_close($ch);

                                            echo("/Save api<br/>");
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/save";
                                            $fileds = array(
                                                "user" => "",
                                                "ontology" => "carex",
                                            );
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_POST, true);
                                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                            $result = curl_exec($ch);
                                            echo($result);
                                            echo ("<br/>");
                                            curl_close($ch);
                                        }
                                    }
                                    if ($flgStructure == 0) {
                                        // $termSolved = 2;
                                    }
                                }
                            }

                            if (count($alwaysHasPartData) > 0) {
                                echo("alwaysHasPart ");
                                echo($alwaysHasPartCount);
                                echo("<br/>");
                                if ($alwaysHasPartCount > 1) {
                                    $flgStructure = 0;
                                    for ($j = 0; $j < count($alwaysHasPartData); $j ++) {
                                        echo($alwaysHasPartData[$j]['name']);
                                        echo(" ");
                                        echo($alwaysHasPartData[$j]['count']);
                                        echo("<br/>");
                                        if ($alwaysHasPartData[$j]['count'] * 1.0 / $alwaysHasPartCount >= 0.6) {
                                            $flgStructure = 1;
                                            $decisionExperts = "";
                                            for ($k = 0; $k < count($addTermSolutions); $k ++) {
                                                if ($addTermSolutions[$k]['type'] == 'Structure') {
                                                    if (strpos($addTermSolutions[$k]['alwaysHasPart'], $alwaysHasPartData[$j]['name']) >= 0 ) {
                                                        $decisionExperts .= "[";
                                                        $decisionExperts .= $addTermSolutions[$k]['username'];
                                                        $decisionExperts .= "]";
                                                    }
                                                }
                                            }
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                            $url .= str_replace(" ", "_", $alwaysHasPartData[$j]['name']);
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            $response = curl_exec($ch);
                                            curl_close($ch);
                                            $searchResult = json_decode($response);
                                            $strongIRI = "";
                                            foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                    $strongIRI = $indAnnotation->{'value'};
                                                    break;
                                                }
                                            }

                                            if ($strongIRI == "") {
                                                $ch = curl_init();
                                                $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                                $url .= str_replace("_", "%20", $alwaysHasPartData[$j]['name']);
                                                curl_setopt($ch, CURLOPT_URL, $url);
                                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                $response = curl_exec($ch);
                                                curl_close($ch);
                                                $searchResult = json_decode($response);
                                                foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                    if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                        $strongIRI = $indAnnotation->{'value'};
                                                        break;
                                                    }
                                                }
                                            }
                                            if ($strongIRI == "") {
                                                $ch = curl_init();
                                                $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                                $url .= $alwaysHasPartData[$j]['name'];
                                                curl_setopt($ch, CURLOPT_URL, $url);
                                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                $response = curl_exec($ch);
                                                curl_close($ch);
                                                $searchResult = json_decode($response);
                                                foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                    if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                        $strongIRI = $indAnnotation->{'value'};
                                                        break;
                                                    }
                                                }
                                            }
                                            
                                            echo ("/partOf api<br/>");
                                            echo ("user: ''<br/>");
                                            echo ("ontology: 'carex'<br/>");
                                            echo ("bearerIRI: ");
                                            //echo ($alwaysHasPartData[$j]['name']);
                                            echo ($strongIRI);
                                            echo ("<br/>");
                                            echo ("partIRI: ");
                                            echo ($termIRI);
                                            echo ("<br/>");
                                            echo ("decisionExperts: ");
                                            echo ($decisionExperts);
                                            echo ("<br/>");
                                            echo ("decisionDate: ");
                                            echo ($currentDate);
                                            echo ('<br/>');

                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/partOf";
                                            $fileds = array(
                                                "user" => "",
                                                "ontology" => "carex",
                                                "bearerIRI" => $strongIRI,
                                                "partIRI" => $termIRI,
                                                "decisionExperts" => $decisionExperts,
                                                "decisionDate" => $currentDate
                                            );
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_POST, true);
                                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                            $result = curl_exec($ch);
                                            echo ($result);
                                            echo ("<br/>");
                                            curl_close($ch);

                                            echo("/Save api<br/>");
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/save";
                                            $fileds = array(
                                                "user" => "",
                                                "ontology" => "carex",
                                            );
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_POST, true);
                                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                            $result = curl_exec($ch);
                                            echo($result);
                                            echo ("<br/>");
                                            curl_close($ch);
                                        }
                                    }
                                    if ($flgStructure == 0) {
                                        // $termSolved = 2;
                                    }
                                }
                            }

                            if (count($alwaysPartOfData) > 0) {
                                echo("alwasyPartOf ");
                                echo($alwaysPartOfCount);
                                echo("<br/>");
                                if ($alwaysPartOfCount > 1) {
                                    $flgStructure = 0;
                                    for ($j = 0; $j < count($alwaysPartOfData); $j ++) {
                                        echo($alwaysPartOfData[$j]['name']);
                                        echo(" ");
                                        echo($alwaysPartOfData[$j]['count']);
                                        echo("<br/>");
                                        if ($alwaysPartOfData[$j]['count'] * 1.0 / $alwaysPartOfCount >= 0.6) {
                                            $flgStructure = 1;
                                            $decisionExperts = "";
                                            for ($k = 0; $k < count($addTermSolutions); $k ++) {
                                                if ($addTermSolutions[$k]['type'] == 'Structure') {
                                                    if (strpos($addTermSolutions[$k]['alwaysPartOf'], $alwaysPartOfData[$j]['name']) >= 0 ) {
                                                        $decisionExperts .= "[";
                                                        $decisionExperts .= $addTermSolutions[$k]['username'];
                                                        $decisionExperts .= "]";
                                                    }
                                                }
                                            }
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                            $url .= str_replace(" ", "_", $alwaysPartOfData[$j]['name']);
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            $response = curl_exec($ch);
                                            curl_close($ch);
                                            $searchResult = json_decode($response);
                                            $strongIRI = "";
                                            foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                    $strongIRI = $indAnnotation->{'value'};
                                                    break;
                                                }
                                            }

                                            if ($strongIRI == "") {
                                                $ch = curl_init();
                                                $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                                $url .= str_replace("_", "%20", $alwaysPartOfData[$j]['name']);
                                                curl_setopt($ch, CURLOPT_URL, $url);
                                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                $response = curl_exec($ch);
                                                curl_close($ch);
                                                $searchResult = json_decode($response);
                                                foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                    if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                        $strongIRI = $indAnnotation->{'value'};
                                                        break;
                                                    }
                                                }
                                            }
                                            if ($strongIRI == "") {
                                                $ch = curl_init();
                                                $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                                $url .= $alwaysPartOfData[$j]['name'];
                                                curl_setopt($ch, CURLOPT_URL, $url);
                                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                $response = curl_exec($ch);
                                                curl_close($ch);
                                                $searchResult = json_decode($response);
                                                foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                    if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                        $strongIRI = $indAnnotation->{'value'};
                                                        break;
                                                    }
                                                }
                                            }
                                            
                                            echo ("/hasPart api<br/>");
                                            echo ("user: ''<br/>");
                                            echo ("ontology: 'carex'<br/>");
                                            echo ("bearerIRI: ");
                                            echo ($termIRI);
                                            echo ("<br/>");
                                            echo ("partIRI: ");
                                            //echo ($alwaysPartOfData[$j]['name']);
                                            echo ($strongIRI);
                                            echo ("<br/>");
                                            echo ("decisionExperts: ");
                                            echo ($decisionExperts);
                                            echo ("<br/>");
                                            echo ("decisionDate: ");
                                            echo ($currentDate);
                                            echo ('<br/>');

                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/hasPart";
                                            $fileds = array(
                                                "user" => "",
                                                "ontology" => "carex",
                                                "bearerIRI" => $termIRI,
                                                "partIRI" => $strongIRI,
                                                "decisionExperts" => $decisionExperts,
                                                "decisionDate" => $currentDate
                                            );
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_POST, true);
                                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                            $result = curl_exec($ch);
                                            echo ($result);
                                            echo ("<br/>");
                                            curl_close($ch);

                                            echo("/Save api<br/>");
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/save";
                                            $fileds = array(
                                                "user" => "",
                                                "ontology" => "carex",
                                            );
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_POST, true);
                                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                            $result = curl_exec($ch);
                                            echo($result);
                                            echo ("<br/>");
                                            curl_close($ch);
                                        }
                                    }
                                    if ($flgStructure == 0) {
                                        // $termSolved = 2;
                                    }
                                }
                            }

                            if (count($maybePartOfData) > 0) {
                                echo("maybePartOf ");
                                echo($maybePartOfCount);
                                echo("<br/>");
                                if ($maybePartOfCount > 1) {
                                    $flgStructure = 0;
                                    for ($j = 0; $j < count($maybePartOfData); $j ++) {
                                        echo($maybePartOfData[$j]['name']);
                                        echo(" ");
                                        echo($maybePartOfData[$j]['count']);
                                        echo("<br/>");
                                        if ($maybePartOfData[$j]['count'] * 1.0 / $maybePartOfCount >= 0.6) {
                                            $flgStructure = 1;
                                            $decisionExperts = "";
                                            for ($k = 0; $k < count($addTermSolutions); $k ++) {
                                                if ($addTermSolutions[$k]['type'] == 'Structure') {
                                                    if (strpos($addTermSolutions[$k]['maybePartOf'], $maybePartOfData[$j]['name']) >= 0 ) {
                                                        $decisionExperts .= "[";
                                                        $decisionExperts .= $addTermSolutions[$k]['username'];
                                                        $decisionExperts .= "]";
                                                    }
                                                }
                                            }
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                            $url .= str_replace(" ", "_", $maybePartOfData[$j]['name']);
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            $response = curl_exec($ch);
                                            curl_close($ch);
                                            $searchResult = json_decode($response);
                                            $strongIRI = "";
                                            foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                    $strongIRI = $indAnnotation->{'value'};
                                                    break;
                                                }
                                            }

                                            if ($strongIRI == "") {
                                                $ch = curl_init();
                                                $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                                $url .= str_replace("_", "%20", $maybePartOfData[$j]['name']);
                                                curl_setopt($ch, CURLOPT_URL, $url);
                                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                $response = curl_exec($ch);
                                                curl_close($ch);
                                                $searchResult = json_decode($response);
                                                foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                    if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                        $strongIRI = $indAnnotation->{'value'};
                                                        break;
                                                    }
                                                }
                                            }
                                            if ($strongIRI == "") {
                                                $ch = curl_init();
                                                $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                                                $url .= $maybePartOfData[$j]['name'];
                                                curl_setopt($ch, CURLOPT_URL, $url);
                                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                $response = curl_exec($ch);
                                                curl_close($ch);
                                                $searchResult = json_decode($response);
                                                foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                                                    if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                                                        $strongIRI = $indAnnotation->{'value'};
                                                        break;
                                                    }
                                                }
                                            }
                                            
                                            echo ("/maybePartOf api<br/>");
                                            echo ("user: ''<br/>");
                                            echo ("ontology: 'carex'<br/>");
                                            echo ("bearerIRI: ");
                                            //echo ($maybePartOfData[$j]['name']);
                                            echo ($termIRI);
                                            echo ("<br/>");
                                            echo ("partIRI: ");
                                            echo ($strongIRI);
                                            echo ("<br/>");
                                            echo ("decisionExperts: ");
                                            echo ($decisionExperts);
                                            echo ("<br/>");
                                            echo ("decisionDate: ");
                                            echo ($currentDate);
                                            echo ('<br/>');

                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/maybePartOf";
                                            $fileds = array(
                                                "user" => "",
                                                "ontology" => "carex",
                                                "bearerIRI" => $termIRI,
                                                "partIRI" => $strongIRI,
                                                "decisionExperts" => $decisionExperts,
                                                "decisionDate" => $currentDate
                                            );
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_POST, true);
                                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                            $result = curl_exec($ch);
                                            echo ($result);
                                            echo ("<br/>");
                                            curl_close($ch);

                                            echo("/Save api<br/>");
                                            $ch = curl_init();
                                            $url = "http://shark.sbs.arizona.edu:8080/save";
                                            $fileds = array(
                                                "user" => "",
                                                "ontology" => "carex",
                                            );
                                            curl_setopt($ch, CURLOPT_URL, $url);
                                            curl_setopt($ch, CURLOPT_POST, true);
                                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                            $result = curl_exec($ch);
                                            echo($result);
                                            echo ("<br/>");
                                            curl_close($ch);
                                        }
                                    }
                                    if ($flgStructure == 0) {
                                        // $termSolved = 2;
                                    }
                                } 
                            }

                            if ($termSolved == 1) {
                                echo("this term is solved<br/>");
                            } else {
                                echo("no strong, mark this term bold<br/>");
                            }

                        } else {
                            echo("no strong agreement, mark this term bold<br/>");
                            $db->markTermTough($termId);
                        }
                        
                        $resComments = $db->getAddTermCommentsByTermId($termId);
                        while ($rowComment = $resComments->fetch_assoc()) {
                            if ($rowComment['comment'] != '') {
                                echo ("/comment api<br/>");
                                echo ("user: ''<br/>");
                                echo ("ontology: 'carex'<br/>");
                                echo ("comment: ");
                                echo ($rowComment['comment']);
                                echo ("<br/>");
                                echo ("providedBy: ");
                                echo ($rowComment['username']);
                                echo ("<br/>");
                                echo ("exmapleSentence: ");
                                echo ($data);
                                echo ("<br/>");
                                echo ("classIRI: ");
                                echo ($termIRI);
                                echo ("<br/>");

                                $ch = curl_init();
                                $url = "http://shark.sbs.arizona.edu:8080/comment";
                                $fileds = array(
                                    "user" => "",
                                    "ontology" => "carex",
                                    "comment" => $rowComment['comment'],
                                    "providedBy" => $rowComment['username'],
                                    "exampleSentence" => $data,
                                    "classIRI" => $termIRI
                                );
                                curl_setopt($ch, CURLOPT_URL, $url);
                                curl_setopt($ch, CURLOPT_POST, true);
                                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                $result = curl_exec($ch);
                                echo($result);
                                echo("<br/>");
                                curl_close($ch);

                                echo("/Save api<br/>");
                                $ch = curl_init();
                                $url = "http://shark.sbs.arizona.edu:8080/save";
                                $fileds = array(
                                    "user" => "",
                                    "ontology" => "carex",
                                );
                                curl_setopt($ch, CURLOPT_URL, $url);
                                curl_setopt($ch, CURLOPT_POST, true);
                                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                $result = curl_exec($ch);
                                echo($result);
                                echo ("<br/>");
                                curl_close($ch);
                            }
                        }
                        if ($termSolved == 1) {
                            if ($countDeclined['COUNT(expertId)'] > 0) {
                                echo ("there is weak rejection. mark this term bold<br/>");
                                $db->markTermTough($termId);
                            } else {
                                echo ("no rejection. resolved<br/>");
                                $db->markTermSolved($termId);
                            }                            
                        } else if ($termSolved == 2) {
                            echo ("no strong agreement. mark this term as bold");
                            $db->markTermTough($termId);
                            echo ("<br/>");
                        }
                    }
                } else if ($countDeclined['COUNT(expertId)'] == 1) {
                    echo ("only 1 decline. mark this term as bold<br/>");
                    $db->markTermTough($termId);
                }
            } else if ($type == 'exact') {
                echo ($termId);
                echo (" ");
                echo ($term);
                echo (" ");
                echo ($termIRI);
                echo (" ");
                echo ("<br/>");
                $expertCount = $db->getExpertCountOnExact($termId)['count'];
                echo ("expertCount: ");
                echo ($expertCount);
                echo ("<br/>");
                if ($expertCount > 1) {
                    $optionResult = $db->getExactOptions($termId);
                    $options = array();
                    $strongOptions = array();
                    $notStrongOptions = array();
                    while ($row = $optionResult->fetch_assoc()) {
                        $caseId = $row['id'];
                        $caseLabel = $row['label'];
                        $caseIRI = $row['iri'];
                        $options[] = array("caseId" => $caseId,"caseLabel" => $caseLabel,"caseIRI" => $caseIRI);
                    }
                    for ($i = 0; $i < count($options); $i ++) {
                        $caseId = $options[$i]['caseId'];
                        $caseLabel = $options[$i]['caseLabel'];
                        $caseIRI = $options[$i]['caseIRI'];
                        echo ($caseId);
                        echo (" ");
                        echo ($caseLabel);
                        echo (" ");
                        echo ($caseIRI);
                        echo (" ");
                        $caseCount = $db->getExpertCountOnCase($termId, $caseId)['count'];
                        echo ($caseCount);
                        if ($caseCount * 1.0 / $expertCount >= 0.6) {
                            $strongOptions[] = array("caseId" => $caseId,"caseIRI" => $caseIRI, "caseLabel" => $caseLabel);
                        } else {
                            $notStrongOptions[] = array("caseId" => $caseId,"caseIRI" => $caseIRI, "caseLabel" => $caseLabel);
                        }
                        echo (" ");
                        echo ("<br/>");
                    }
                    if (count($strongOptions) == 0) {
                        echo ("not strong agreement. mark this term as bold<br/>");
                        $db->markTermTough($termId);
                    } else {
                        for ($i = 0; $i < count($notStrongOptions); $i ++) {
                            $decisionExperts = "";
                            $reason = "";
                            $expertData = $db->getExpertDataOnExactSolution($termId, $notStrongOptions[$i]['caseId']);
                            while ($row = $expertData->fetch_assoc()) {
                                $decisionExperts .= "[";
                                $decisionExperts .= $row['username'];
                                $decisionExperts .= "]";
                                $reason .= "[";
                                $reason .= $row['reason'];
                                $reason .= "]";
                            }
                            echo ("/removeESynonym api<br/>");
                            echo ("user: ''<br/>");
                            echo ("classIRI: ");
                            echo ($notStrongOptions[$i]['caseIRI']);
                            echo ("<br/>");
                            echo ("ontology: 'carex'<br/>");
                            echo ("term: ");
                            echo ($term);
                            echo ("<br/>");
                            echo ("decisionExperts: ");
                            echo ($decisionExperts);
                            echo ("<br/>");
                            echo ("decisionDate: ");
                            echo ($currentDate1);
                            echo ("<br/>");
                            echo ("reason: ");
                            echo ($reason);
                            echo ("<br/>");

                            $ch = curl_init();
                            $url = "http://shark.sbs.arizona.edu:8080/removeESynonym";
                            $fileds = array(
                                "user" => "",
                                "classIRI" => $notStrongOptions[$i]['caseIRI'],
                                "ontology" => "carex",
                                "term" => $term,
                                "decisionExperts" => $decisionExperts,
                                "decisionDate" => $currentDate1,
                                "reason" => $reason
                            );
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                            $result = curl_exec($ch);
                            echo($result);
                            echo ("<br/>");
                            curl_close($ch);
                        }
                        if (count($strongOptions) > 1) {
                            for ($i = 0; $i < count($strongOptions) - 1; $i ++) {
                                $decisionExperts = "";
                                $reason = "";
                                $expertData = $db->getExpertDataOnExactSolution($termId, $strongOptions[$i]['caseId']);
                                while ($row = $expertData->fetch_assoc()) {
                                    $decisionExperts .= "[";
                                    $decisionExperts .= $row['username'];
                                    $decisionExperts .= "]";
                                    $reason .= "[";
                                    $reason .= $row['reason'];
                                    $reason .= "]";
                                }
                                for ($j = $i + 1; $j < count($strongOptions); $j ++) {
                                    echo ("/makeEquivalent api<br/>");
                                    echo ("user: ''<br/>");
                                    echo ("classIRI1: ");
                                    echo ($strongOptions[$i]['caseIRI']);
                                    echo ("<br/>");
                                    echo ("classIRI2: ");
                                    echo ($strongOptions[$j]['caseIRI']);
                                    echo ("<br/>");
                                    echo ("ontology: 'carex'<br/>");
                                    echo ("decisionExperts: ");
                                    echo ($decisionExperts);
                                    echo ("<br/>");
                                    echo ("decisionDate: ");
                                    echo ($currentDate1);
                                    echo ("<br/>");
                                    echo ("reason: ");
                                    echo ($reason);
                                    echo ("<br/>");

                                    $ch = curl_init();
                                    $url = "http://shark.sbs.arizona.edu:8080/makeEquivalent";
                                    $fileds = array(
                                        "user" => "",
                                        "classIRI1" => $strongOptions[$i]['caseIRI'],
                                        "classIRI2" => $strongOptions[$j]['caseIRI'],
                                        "ontology" => "carex",
                                        "decisionExperts" => $decisionExperts,
                                        "decisionDate" => $currentDate1,
                                        "reason" => $reason
                                    );
                                    curl_setopt($ch, CURLOPT_URL, $url);
                                    curl_setopt($ch, CURLOPT_POST, true);
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                                    $result = curl_exec($ch);
                                    echo($result);
                                    echo ("<br/>");
                                    curl_close($ch);
                                }
                            }
                        }
                        echo ("this term is solved<br/>");
                        $db->markTermSolved($termId);
                    }
                }

            } else if ($type == 'equiv') {
                echo ($termId);
                echo (" ");
                echo ($term);
                echo (" ");
                echo ($termIRI);
                echo (" ");
                echo ("<br/>");
                $expertCount = $db->getExpertCountOnExact($termId)['count'];
                echo ("expertCount: ");
                echo ($expertCount);
                echo ("<br/>");
                if ($expertCount > 1) {
                    $optionResult = $db->getExactOptions($termId);
                    $options = array();
                    $strongOptions = array();
                    $notStrongOptions = array();
                    while ($row = $optionResult->fetch_assoc()) {
                        $caseId = $row['id'];
                        $caseLabel = $row['label'];
                        $caseIRI = $row['iri'];
                        $options[] = array("caseId" => $caseId,"caseLabel" => $caseLabel,"caseIRI" => $caseIRI);
                    }
                    for ($i = 0; $i < count($options); $i ++) {
                        $caseId = $options[$i]['caseId'];
                        $caseLabel = $options[$i]['caseLabel'];
                        $caseIRI = $options[$i]['caseIRI'];
                        echo ($caseId);
                        echo (" ");
                        echo ($caseLabel);
                        echo (" ");
                        echo ($caseIRI);
                        echo (" ");
                        $caseCount = $db->getExpertCountOnCase($termId, $caseId)['count'];
                        echo ($caseCount);
                        if ($caseCount * 1.0 / $expertCount >= 0.6) {
                            $strongOptions[] = array("caseId" => $caseId,"caseIRI" => $caseIRI, "caseLabel" => $caseLabel);
                        } else {
                            $notStrongOptions[] = array("caseId" => $caseId,"caseIRI" => $caseIRI, "caseLabel" => $caseLabel);
                        }
                        echo (" ");
                        echo ("<br/>");
                    }
                    if (count($strongOptions) == 0) {
                        echo ("not strong agreement. mark this term as bold<br/>");
                        $db->markTermTough($termId);
                    } else {
                        for ($i = 0; $i < count($notStrongOptions); $i ++) {
                            $decisionExperts = "";
                            $reason = "";
                            $expertData = $db->getExpertDataOnExactSolution($termId, $notStrongOptions[$i]['caseId']);
                            while ($row = $expertData->fetch_assoc()) {
                                $decisionExperts .= "[";
                                $decisionExperts .= $row['username'];
                                $decisionExperts .= "]";
                                $reason .= "[";
                                $reason .= $row['reason'];
                                $reason .= "]";
                            }
                            echo ("/breakEquivalent api<br/>");
                            echo ("user: ''<br/>");
                            echo ("classIRI1: ");
                            echo ($termIRI);
                            echo ("<br/>");
                            echo ("classIRI2: ");
                            echo ($notStrongOptions[$i]['caseIRI']);
                            echo ("<br/>");
                            echo ("ontology: 'carex'<br/>");
                            echo ("decisionExperts: ");
                            echo ($decisionExperts);
                            echo ("<br/>");
                            echo ("decisionDate: ");
                            echo ($currentDate1);
                            echo ("<br/>");
                            echo ("reason: ");
                            echo ($reason);
                            echo ("<br/>");

                            $ch = curl_init();
                            $url = "http://shark.sbs.arizona.edu:8080/breakEquivalent";
                            $fileds = array(
                                "user" => "",
                                "classIRI1" => $termIRI,
                                "classIRI2" => $notStrongOptions[$i]['caseIRI'],
                                "ontology" => "carex",
                                "decisionExperts" => $decisionExperts,
                                "decisionDate" => $currentDate1,
                                "reason" => $reason
                            );
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fileds));
                            $result = curl_exec($ch);
                            echo($result);
                            echo ("<br/>");
                            curl_close($ch);
                        }
                        echo ("this term is solved<br/>");
                        $db->markTermSolved($termId);
                    }
                }
            }
        }
    }

?>