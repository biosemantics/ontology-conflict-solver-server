<?php
require_once '../../includes/DataBaseOperations.php';

    if($_SERVER['REQUEST_METHOD'] == 'GET') {

        $db = new DataBaseOperations();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://shark.sbs.arizona.edu:8080/carex/getClassesWMSuperclasses");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // echo(curl_exec($ch));

        $categoryTasksResponse = curl_exec($ch);
        // echo($categoryTasksResponse);
        $categoryTasks = json_decode($categoryTasksResponse);
        foreach($categoryTasks->{'terms'} as $termKey => $indTerm) {
            // echo($indTerm->{'iri'});
            // echo('<br/>');
            // echo($indTerm->{'label'});
            // echo('<br/>');
            $dataString = '';
            foreach($indTerm->{'sentences'} as $sentenceKey => $sentence) {
                // echo($sentence);
                $dataString .= $sentence;
                // echo('<br/>');
            }
            // echo($dataString);
            // echo('<br/>');

            $authorId = $db->getAuthorId($indTerm->{'termCreator'});
            // echo($authorId);
            // echo('<br/>');
            
            $termId = $db->insertTermToConfusing($indTerm->{'iri'}, $indTerm->{'label'}, $dataString, "category", $authorId);
            echo($termId);
            echo('<br/>');

            if ($termId != "Exist") {
                foreach($indTerm->{'categories'} as $cateogryKey => $indCategory) {
                    // echo($indCategory->{'iri'});
                    // echo('<br/>');
                    $image = '';
                    foreach($indCategory->{'elucidation'} as $imageKey => $imageLink) {
                        $image .= $imageLink;
                        $image .= " ";
                        // echo($imageLink);
                        // echo('<br/>');
                    }
                    
                    // echo($indCategory->{'name'});
                    // echo('<br/>');
                    $definition = '';
                    foreach($indCategory->{'definition'} as $definitionKey => $indDefinition) {
                        $definition .= $indDefinition;
                        $definition .= " ";
                        // echo($definition);
                        // echo('<br/>');
                    }
                    $optionId = $db->insertOption($indCategory->{'iri'}, $indCategory->{'name'}, $definition, $image);
                    echo($optionId);
                    echo('<br/>');

                    $db->relateTermAndOption($termId, $optionId);
                }
            }
        }

        curl_setopt($ch, CURLOPT_URL, "http://shark.sbs.arizona.edu:8080/carex/getClassesWMZdefinitions");
        // echo(curl_exec($ch));

        $synonymTasksResponse = curl_exec($ch);
        $sysnonymTasks = json_decode($synonymTasksResponse);
        foreach($sysnonymTasks->{'terms'} as $termKey => $indTerm) {
            // echo($indTerm->{'iri'});
            // echo('<br/>');
            // echo($indTerm->{'label'});
            // echo('<br/>');
            $superClassLabel = '';
            foreach($indTerm->{'superclass label'} as $superKey => $superLabel) {
                // echo($superLabel);
                // echo('<br/>');
                $superClassLabel .= $superLabel;
                $superClassLabel .= ' ';
            }
            $authorId = $db->getAuthorId($indTerm->{'termCreator'});

            $termId = $db->insertTermToConfusing($indTerm->{'iri'}, $indTerm->{'label'}, $superClassLabel, 'synonym', $authorId);
            echo($termId);
            echo('<br/>');
            
            if ($termId != "Exist") {
                foreach($indTerm->{'sentences'} as $sentenceKey => $sentence) {
                    // echo($sentence);
                    // echo('<br/>');
                    $db->insertSentence($sentence, $termId);
                }
                foreach($indTerm->{'definitions'} as $definitionKey => $definition) {
                    // echo($definition);
                    // echo('<br/>');
                    $db->insertDefinition($definition, $termId);
                }
                // foreach($indTerm->{'elucidations'} as $elucidationKey => $elucidation) {
                //     echo($elucidation);
                //     echo('<br/>');
                // }
            }
        }

        curl_setopt($ch, CURLOPT_URL, "http://shark.sbs.arizona.edu:8080/carex/getToreviewClasses");
        // echo(curl_exec($ch));

        $addTermTasksResponse = curl_exec($ch);
        $addtermTasks = json_decode($addTermTasksResponse);
        foreach($addtermTasks->{'terms'} as $termKey => $indTerm) {
            // echo($indTerm->{'iri'});
            // echo('<br/>');
            // echo($indTerm->{'label'});
            // echo('<br/>');
           
            $definitions = $indTerm->{'definition'};

            $authorId = $db->getAuthorId($indTerm->{'termCreator'});

            $termId = $db->insertTermToConfusing($indTerm->{'iri'}, $indTerm->{'label'}, $definitions, 'addTerm', $authorId);
            echo($termId);
            echo('<br/>');
            
            if ($termId != "Exist") {
                foreach($indTerm->{'sentences'} as $sentenceKey => $sentence) {
                    // echo($sentence);
                    // echo('<br/>');
                    $db->insertSentence($sentence, $termId);
                }
                // foreach($indTerm->{'elucidations'} as $elucidationKey => $elucidation) {
                //     echo($elucidation);
                //     echo('<br/>');
                // }
            }
        }


        curl_setopt($ch, CURLOPT_URL, "http://shark.sbs.arizona.edu:8080/carex/getSynonymConflicts");
        
        $equivalentTasksResponse = curl_exec($ch);
        $equivalentTasks = json_decode($equivalentTasksResponse);
        foreach ($equivalentTasks->{'synonym conflicts'} as $termKey => $indTerm) {
            $termType = 0;
            $termLabel = "";
            $termIRI = "";
            foreach ($indTerm as $indKey => $indVal) {
                if (strpos($indKey, 'exact synonym') !== false) {
                    $termType = 1;
                    $termLabel = $indVal;
                } else if (strpos($indKey, 'equ class') !== false) {
                    $termType = 2;
                    $termIRI = $indVal;
                }
            }
            echo ($termType);
            echo (" ");
            if ($termType == 1) {
                echo ("Exact Synonym: ");
                echo ($termLabel);
                $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                $url .= $termLabel;
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
                $searchResult = json_decode($response);
                foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                    if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                        $termIRI = $indAnnotation->{'value'};
                        break;
                    }
                }
                if ($termIRI == "") {
                    $ch = curl_init();
                    $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                    $url .= str_replace("_", "%20", $termLabel);
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    $searchResult = json_decode($response);
                    foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                        if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                            $termIRI = $indAnnotation->{'value'};
                            break;
                        }
                    }
                }
                if ($termIRI == "") {
                    $ch = curl_init();
                    $url = "http://shark.sbs.arizona.edu:8080/carex/search?term=";
                    $url .= str_replace(" ", "_", $termLabel);
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    $searchResult = json_decode($response);
                    foreach ($searchResult->{'entries'}[0]->resultAnnotations as $indAnnotation) {
                        if ($indAnnotation->{'property'} == 'http://www.geneontology.org/formats/oboInOwl#id') {
                            $termIRI = $indAnnotation->{'value'};
                            break;
                        }
                    }
                }
                echo (", IRI:");
                // $termIRI = 'http://biosemantics.arizona.edu/ontologies/carex#' . $termLabel;
                echo ($termIRI);
                echo ("<br/>");

                $termId = $db->insertTermToConfusing($termIRI, $termLabel, "", "exact", 9);
                echo ($termId);
                echo ("<br/>");

                if ($termId != 'Exist') {
                    foreach ($indTerm->{'classes'} as $indClass) {
                        $elucidation = "";
                        $sentence = "";
                        echo ("iri:");
                        echo ($indClass->{'iri'});
                        echo (", elucidation:");
                        $flg = 0;
                        foreach ($indClass->{'elucidations'} as $eachElucidation) {
                            if ($flg != 0) {
                                $elucidation .= ", ";
                            }
                            $flg = 1;
                            $elucidation .= $eachElucidation;
                        }
                        $flg = 0;
                        foreach ($indClass->{'sentences'} as $eachSentence) {
                            if ($flg != 0) {
                                $sentence .= ", ";
                            }
                            $flg = 1;
                            $sentence .= $eachSentence;
                        }
                        echo ($elucidation);
                        echo (", sentence:");
                        echo ($sentence);
                        echo (", definition:");
                        echo ($indClass->{'definition'});
                        echo (", label:");
                        echo ($indClass->{'label'});
                        echo (", termCreator:");
                        echo ($indClass->{'termCreator'});
                        echo ("<br/>");
                        $db->insertExactCase($termId, $indClass->{'iri'}, $elucidation, $sentence, $indClass->{'definition'}, $indClass->{'label'}, $indClass->{'termCreator'});
                    }
                }
            } else if ($termType == 2) {
                $termLabel = $indTerm->{'label'};
                echo ("Equ class: ");
                echo ($termLabel);
                echo (", IRI: ");
                echo ($termIRI);
                echo ("<br/>");
                $termElucidation = "";
                $termSentence = "";
                $flg = 0;
                foreach ($indTerm->{'elucidations'} as $eachElucidation) {
                    if ($flg != 0) {
                        $termElucidation .= ", ";
                    }
                    $flg = 1;
                    $termElucidation .= $eachElucidation;
                }
                $flg = 0;
                foreach ($indTerm->{'sentences'} as $eachSentence) {
                    if ($flg != 0) {
                        $termSentence .= ", ";
                    }
                    $flg = 1;
                    $termSentence .= $eachSentence;
                }
                echo ("elucidation: ");
                echo ($termElucidation);
                echo (", sentence:");
                echo ($termSentence);
                echo (", definition:");
                echo ($indTerm->{'definition'});
                echo (", termCreator:");
                echo ($indTerm->{'termCreator'});
                echo ("<br/>");

                $authorId = $db->getAuthorId($indTerm->{'termCreator'});
                
                $termId = $db->insertEquivTermToConfusing($termIRI, $termLabel, "", "equiv", $authorId, $termElucidation, $termSentence, $indTerm->{'definition'});

                echo ($termId);
                echo ("<br/>");

                if ($termId != 'Exist') {
                    foreach ($indTerm->{'equivalent classes'} as $indClass) {
                        $elucidation = "";
                        $sentence = "";
                        echo ("iri:");
                        echo ($indClass->{'iri'});
                        echo (", elucidation:");
                        $flg = 0;
                        foreach ($indClass->{'elucidations'} as $eachElucidation) {
                            if ($flg != 0) {
                                $elucidation .= ", ";
                            }
                            $flg = 1;
                            $elucidation .= $eachElucidation;
                        }
                        $flg = 0;
                        foreach ($indClass->{'sentences'} as $eachSentence) {
                            if ($flg != 0) {
                                $sentence .= ", ";
                            }
                            $flg = 1;
                            $sentence .= $eachSentence;
                        }
                        echo ($elucidation);
                        echo (", sentence:");
                        echo ($sentence);
                        echo (", definition:");
                        echo ($indClass->{'definition'});
                        echo (", label:");
                        echo ($indClass->{'label'});
                        echo (", termCreator:");
                        echo ($indClass->{'termCreator'});
                        echo ("<br/>");
                        $db->insertExactCase($termId, $indClass->{'iri'}, $elucidation, $sentence, $indClass->{'definition'}, $indClass->{'label'}, $indClass->{'termCreator'});
                    }
                }
            }            
        }

        curl_close($ch);
    }
?>