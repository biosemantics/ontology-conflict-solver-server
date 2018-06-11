<?php

require_once '../../includes/DataBaseOperations.php';

$response = array(); 

	if($_SERVER['REQUEST_METHOD'] == 'GET'){

		$db = new DataBaseOperations();
	    $result = $db->getTasks();
        
	  	while( $array = $result->fetch_row() ) {
	  		$reponse[] = $array;
            printf ("%s ---> %s\n", $array[0], $array[1]);
            echo "<br>";
		}

		echo '<pre>'; print_r($reponse); echo '</pre>';

    } else {
    	$response['error'] = true;
    }

#echo json_encode($response);

?>
