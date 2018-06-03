<?php
require_once '../../includes/DataBaseOperations.php';

	if($_SERVER['REQUEST_METHOD'] == 'GET'){

			$db = new DataBaseOperations();
		    $result = $db->getTasks();
		    
		    // output data of each row
		    	while($row = $result->fetch_assoc()) {
		    		echo "A conflict ". $row['term'].  "  from  ". $row['username']. "<br>";
		    	}
    }
?>
