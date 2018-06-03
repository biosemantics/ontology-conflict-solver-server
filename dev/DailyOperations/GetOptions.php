<?php
require_once '../../includes/DataBaseOperations.php';

	if($_SERVER['REQUEST_METHOD'] == 'GET'){

			$db = new DataBaseOperations();
			$_GET['term'] = "bristlelike";
		    $result = $db->getOptions($_GET['term']);
		    
		    // output data of each row
		    	while($row = $result->fetch_assoc()) {
		    		echo "term: ". $row['term'].  "--->". $row['option_']."<br>";
		    	}
    }
?>
