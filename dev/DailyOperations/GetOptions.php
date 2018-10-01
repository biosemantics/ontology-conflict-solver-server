<?php

require_once '../../includes/DataBaseOperations.php';
$response = array(); 

    if($_SERVER['REQUEST_METHOD'] == 'GET'){
        
        $db = new DataBaseOperations();
        $result = $db->getOptions($_GET['ID']);
		    
        //output data of each row
        while($row = $result->fetch_assoc()){
            $term       = $row['term'];
            $option_    = $row['option_'];
            $definition = $row['definition'];
            $picture    = $row['picture'];
           
            //echo "term: ". $row['term']. "  --->  " . $row['option_'].  " ---> ". $row['definition']. "<br>";
            $data[] = array("term"=>$term, "option_"=>$option_, "definition"=>$definition);
            //$data[] = array("option_"=>$option_, "definition"=>$definition, "picture"=>$picture);
	}
        $response = $data;
    }
    echo json_encode(array("options_data"=>$response));
?>
