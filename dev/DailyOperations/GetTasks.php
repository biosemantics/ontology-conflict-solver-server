<?php

require_once '../../includes/DataBaseOperations.php';

$response = array(); 
$err = array();

  if($_SERVER['REQUEST_METHOD'] == 'GET'){

      $db = new DataBaseOperations();
      $result = $db->getTasks();
      
      while( $row = $result->fetch_assoc() ) {

          $termId = $row['termId'];
          $term = $row['term'];
          $conflictId = $row['conflictId'];
          $username = $row['username'];

          $data[] = array("termId"=>$termId, "term"=>$term, "conflictId"=>$conflictId, "username"=>$username);
      	  //echo "termId: ". $row['termId']. "term: ". $row['term']. "conflictId: ". $row['conflictId']. "username: ". $row['username']."<br>";
      }

      $response = $data;
  }
  echo json_encode(array("task_data"=>$response));
?>
