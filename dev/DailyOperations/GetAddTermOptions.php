<?php

require_once '../../includes/DataBaseOperations.php';
$sentences = array(); 

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

        $solutionResult = $db->getAddTermSolutionByTermId($_GET['termId']);
        $characterCount = 0;
        $structureCount = 0;
        $characterSubpartData = array();
        $subpartData = array();
        $superpartData = array();
        $alwaysHasPartData = array();
        $alwaysPartOfData = array();
        $maybePartOfData = array();
        $subclassOfData = array();
        $synonyms = array();
        $comments = array();
        $curComment = "";
        $curCharacter = "";
        $curSubclassOf = "";
        $curType = "";
        $curAlwaysPartOf = array();
        $curAlwaysHasPart = array();
        $curMaybePartOf = array();
        $curSubPart = array();
        $curSuperPart = array();
        while($row = $solutionResult->fetch_assoc()) {
            if ($row['type'] == 'Character') {
                $charactersubpart = $row['subpart'];
                if ($charactersubpart && $charactersubpart != '') {
                    if ($row['expertId'] != $_GET['expertId']) {
                        $characterCount ++;
                        $characterpiece = explode(",", $charactersubpart);
                        for ($i = 0; $i < count($characterpiece); $i ++) {
                            for ($j = 0; $j < count($characterSubpartData); $j ++) {
                                if ($characterSubpartData[$j]['name'] == $characterpiece[$i]) {
                                    break;
                                }
                            }
                            if ($j == count($characterSubpartData)) {
                                $characterSubpartData[] = array("name"=>$characterpiece[$i],
                                                        "count"=>1);
                            } else {
                                $characterSubpartData[$j]['count'] ++;
                            }
                        }
                    } else {
                        $curCharacter = $charactersubpart;
                        $curType = "Character";
                    }
                }
            } else {
                if ($row['expertId'] != $_GET['expertId']) {
                    $structureCount ++;
                }
                $subpart = $row['subpart'];
                if ($subpart && $subpart != '') {
                    $subpartpiece = explode(",", $subpart);
                    for ($i = 0; $i < count($subpartpiece); $i ++) {
                        if ($row['expertId'] != $_GET['expertId']) {
                            for ($j = 0; $j < count($subpartData); $j ++) {
                                if ($subpartData[$j]['name'] == $subpartpiece[$i]) {
                                    break;
                                }
                            }
                            if ($j == count($subpartData)) {
                                $subpartData[] = array("name"=>$subpartpiece[$i],
                                                        "count"=>1);
                            } else {
                                $subpartData[$j]['count'] ++;
                            }
                        } else {
                            array_push($curSubPart, $subpartpiece[$i]);
                        }
                    }
                }

                $superpart = $row['superpart'];
                if ($superpart && $superpart != '') {
                    $superpartpiece = explode(",", $superpart);
                    for ($i = 0; $i < count($superpartpiece); $i ++) {
                        if ($row['expertId'] != $_GET['expertId']) {
                            for ($j = 0; $j < count($superpartData); $j ++) {
                                if ($superpartData[$j]['name'] == $superpartpiece[$i]) {
                                    break;
                                }
                            }
                            if ($j == count($superpartData)) {
                                $superpartData[] = array("name"=>$superpartpiece[$i],
                                                        "count"=>1);
                            } else {
                                $superpartData[$j]['count'] ++;
                            }
                        } else {
                            array_push($curSuperPart, $superpartpiece[$i]);
                        }
                    }
                }

                $alwaysHasPart = $row['alwaysHasPart'];
                if ($alwaysHasPart && $alwaysHasPart != '') {
                    $alwaysHasPartpiece = explode(",", $alwaysHasPart);
                    for ($i = 0; $i < count($alwaysHasPartpiece); $i ++) {
                        if ($row['expertId'] != $_GET['expertId']) {
                            for ($j = 0; $j < count($alwaysHasPartData); $j ++) {
                                if ($alwaysHasPartData[$j]['name'] == $alwaysHasPartpiece[$i]) {
                                    break;
                                }
                            }
                            if ($j == count($alwaysHasPartData)) {
                                $alwaysHasPartData[] = array("name"=>$alwaysHasPartpiece[$i],
                                                        "count"=>1);
                            } else {
                                $alwaysHasPartData[$j]['count'] ++;
                            }
                        } else {
                            array_push($curAlwaysHasPart, $alwaysHasPartpiece[$i]);
                        }
                    }
                }

                $alwaysPartOf = $row['alwaysPartOf'];
                if ($alwaysPartOf && $alwaysPartOf != '') {
                    $alwaysPartOfpiece = explode(",", $alwaysPartOf);
                    for ($i = 0; $i < count($alwaysPartOfpiece); $i ++) {
                        if ($row['expertId'] != $_GET['expertId']) {
                            for ($j = 0; $j < count($alwaysPartOfData); $j ++) {
                                if ($alwaysPartOfData[$j]['name'] == $alwaysPartOfpiece[$i]) {
                                    break;
                                }
                            }
                            if ($j == count($alwaysPartOfData)) {
                                $alwaysPartOfData[] = array("name"=>$alwaysPartOfpiece[$i],
                                                        "count"=>1);
                            } else {
                                $alwaysPartOfData[$j]['count'] ++;
                            }
                        } else {
                            array_push($curAlwaysPartOf, $alwaysPartOfpiece[$i]);
                        }
                    }
                }

                $maybePartOf = $row['maybePartOf'];
                if ($maybePartOf && $maybePartOf != '') {
                    $maybePartOfpiece = explode(",", $maybePartOf);
                    for ($i = 0; $i < count($maybePartOfpiece); $i ++) {
                        if ($row['expertId'] != $_GET['expertId']) {
                            for ($j = 0; $j < count($maybePartOfData); $j ++) {
                                if ($maybePartOfData[$j]['name'] == $maybePartOfpiece[$i]) {
                                    break;
                                }
                            }
                            if ($j == count($maybePartOfData)) {
                                $maybePartOfData[] = array("name"=>$maybePartOfpiece[$i],
                                                        "count"=>1);
                            } else {
                                $maybePartOfData[$j]['count'] ++;
                            }
                        } else {
                            array_push($curMaybePartOf, $maybePartOfpiece[$i]);
                        }
                    }
                }

                $subclassOf = $row['subclassOf'];
                if ($subclassOf && $subclassOf != '') {
                    if ($row['expertId'] != $_GET['expertId']) {
                        $subclassOfpiece = explode(",", $subclassOf);
                        for ($i = 0; $i < count($subclassOfpiece); $i ++) {
                            for ($j = 0; $j < count($subclassOfData); $j ++) {
                                if ($subclassOfData[$j]['name'] == $subclassOfpiece[$i]) {
                                    break;
                                }
                            }
                            if ($j == count($subclassOfData)) {
                                $subclassOfData[] = array("name"=>$subclassOfpiece[$i],
                                                        "count"=>1);
                            } else {
                                $subclassOfData[$j]['count'] ++;
                            }
                        }
                    } else {
                        $curSubclassOf = $subclassOf;
                        $curType = "Structure";
                    }
                }
            }
            
        }

        $sysnonymsResult = $db->getSynonymsByTermId($_GET['termId']);
        while ($row = $sysnonymsResult->fetch_assoc()) {
            $synonyms[] = array("expertId"=>$row['expertId'],
                                "synonym"=>$row['synonym']);
        }

        $commentsResult = $db->getAddTermCommentsByTermId($_GET['termId']);
        while ($row = $commentsResult->fetch_assoc()) {
            if ($row['comment'] && $row['comment'] != '') {
                if ($row['expertId'] != $_GET['expertId']) {
                    $comments[] = array("expertId"=>$row['expertId'],
                                        "comment"=>$row['comment']);
                } else {
                    $curComment = $row['comment'];
                }
            }
        }

        $termDeclined = $db->isTermDeclinedByExpert($_GET['termId'], $_GET['expertId']);

    }
    echo json_encode(array("sentence"=>$sentences,
                            "characterCount"=>$characterCount,
                            "structureCount"=>$structureCount,
                            "characterSubpartData"=>$characterSubpartData,
                            "subpartData"=>$subpartData,
                            "superpartData"=>$superpartData,
                            "alwaysHasPartData"=>$alwaysHasPartData,
                            "alwaysPartOfData"=>$alwaysPartOfData,
                            "maybePartOfData"=>$maybePartOfData,
                            "subclassOfData"=>$subclassOfData,
                            "synonyms"=>$synonyms,
                            "comments"=>$comments,
                            "termDeclined"=>$termDeclined,
                            "curComment"=>$curComment,
                            "curCharacter"=>$curCharacter,
                            "curSubclassOf"=>$curSubclassOf,
                            "curType"=>$curType,
                            "curAlwaysPartOf"=>$curAlwaysPartOf,
                            "curAlwaysHasPart"=>$curAlwaysHasPart,
                            "curMaybePartOf"=>$curMaybePartOf,
                            "curSubPart"=>$curSubPart,
                            "curSuperPart"=>$curSuperPart
                        ));
?>
