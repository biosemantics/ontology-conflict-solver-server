<?php
    class DataBaseOperations{

    	private $con;

    	function __construct(){

    		require_once dirname(__FILE__). '/DataBaseConnect.php';
    		$db = new DataBaseConnect();
    		$this->con = $db->connect();
    	}

        /****************************************************************************
        *
        * Functions for Author Table
        *
        * 1) createAuthor
        * 2) authorLogin
        * 3) isAuthorExist
        * 4) getAuthorByUsername
        *
        *****************************************************************************/

        public function createAuthor($username, $pass, $firstname, $lastname, $email){
            
            if($this->isAuthorExist($username,$email)){

                return 0;

            }else{
                $password = md5($pass);
                $stmt = $this->con->prepare("INSERT INTO `Author` (`authorId`,`username`,`password`,`firstname`,`lastname`,`email`) VALUES (NULL, ?, ?, ?, ?, ?);");
                $stmt->bind_param("sssss",$username,$password,$firstname,$lastname,$email);
            
                if($stmt->execute()){
                    return 1;
                }else{
                    return 2;
                }
            }
        }

        public function authorLogin($username, $pass){
            $password = md5($pass);
            $stmt = $this->con->prepare("SELECT authorId FROM Author WHERE username = ? AND password = ?");
            $stmt->bind_param("ss",$username,$password);
            $stmt->execute();
            $stmt->store_result(); 
            return $stmt->num_rows > 0; 
        }

        private function isAuthorExist($username, $email){
            $stmt = $this->con->prepare("SELECT authorId FROM Author WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute(); 
            $stmt->store_result(); 
            return $stmt->num_rows > 0; 
        }
        
        public function getAuthorByUsername($username){
            $stmt = $this->con->prepare("SELECT * FROM Author WHERE username = ?");
            $stmt->bind_param("s",$username);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        /****************************************************************************
        *
        * Functions for Expert Table
        *
        * 1) createExpert
        * 2) expertLogin
        * 3) isExpertExist
        * 4) getExpertByUsername
        *
        *****************************************************************************/


    	public function createExpert($username, $pass, $firstname, $lastname, $email){
            
            if($this->isExpertExist($username,$email)){

                return 0;

            }else{
                $password = md5($pass);
                $stmt = $this->con->prepare("INSERT INTO `Expert` (`expertId`,`username`,`password`,`firstname`,`lastname`,`email`) VALUES (NULL, ?, ?, ?, ?, ?);");
    		    $stmt->bind_param("sssss",$username,$password,$firstname,$lastname,$email);
    		
                if($stmt->execute()){
    			    return 1;
    		    }else{
                    return 2;
    		    }
    	    }
        }

        public function expertLogin($username, $pass){
            $password = md5($pass);
            $stmt = $this->con->prepare("SELECT expertId FROM Expert WHERE username = ? AND password = ?");
            $stmt->bind_param("ss",$username,$password);
            $stmt->execute();
            $stmt->store_result(); 
            return $stmt->num_rows > 0; 
        }

        private function isExpertExist($username, $email){
            $stmt = $this->con->prepare("SELECT expertId FROM Expert WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute(); 
            $stmt->store_result(); 
            return $stmt->num_rows > 0; 
        }
        
        public function getExpertByUsername($username){
            $stmt = $this->con->prepare("SELECT * FROM Expert WHERE username = ?");
            $stmt->bind_param("s",$username);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        /*public function getOptions($term){
            $stmt = $this->con->prepare("SELECT DISTINCT ConfusingTerm.term as term, Option_.term as option_ FROM J_ConfusingTerm_Option
                JOIN ConfusingTerm on J_ConfusingTerm_Option.termId = ConfusingTerm.termId
                JOIN Option_ on J_ConfusingTerm_Option.optionId = Option_.optionId  
                WHERE ConfusingTerm.term = ?
                ORDER BY term ASC;");
            $stmt->bind_param("s",$term);
            $stmt->execute();
            return $stmt->get_result();
        }*/

        public function getOptions($termId){
            $stmt = $this->con->prepare("
                SELECT   
                    ConfusingTerm.term as term,
                    Option_.term as option_,
                    Option_.definition as definition,
                    Option_.picture as picture
                FROM  J_ConfusingTerm_Option 
                JOIN  ConfusingTerm on J_ConfusingTerm_Option.termId = ConfusingTerm.termId
                JOIN  Option_       on J_ConfusingTerm_Option.optionId = Option_.optionId
                WHERE ConfusingTerm.termId = ?");
            $stmt->bind_param("s",$termId);
            $stmt->execute();
            return $stmt->get_result();
        }
        public function getTasks(){
            $stmt = $this->con->prepare("
                SELECT DISTINCT 
                    ConfusingTerm.term as term, 
                    ConfusingTerm.termId as termId,
                    ConfusingTerm.sentence as sentence,
                    Author.username as username,
                    Conflict.conflictId as conflictId
                FROM J_Conflict_ConfusingTerm
                JOIN ConfusingTerm on J_Conflict_ConfusingTerm.termId = ConfusingTerm.termId
                JOIN Conflict      on J_Conflict_ConfusingTerm.conflictId = Conflict.conflictId
                JOIN Author        on Author.authorId = Conflict.authorId
                ORDER BY term ASC;");
            $stmt->execute();
            return $stmt->get_result();
        }
    }
?>