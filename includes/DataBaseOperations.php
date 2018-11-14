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

        /****************************************************************************
        *
        * Functions for the other database operations
        *
        * 1) getOptions
        * 2) getOptionImages
        * 3) getTasks
        * 4) submitDecision
        * 5) registerToken
        * 6) populate_J_Conflict_Expert_Choice
        * 7) populate_Conflict
        *
        *****************************************************************************/
        public function getOptions($termId){
            $stmt = $this->con->prepare("
                SELECT   
                    ConfusingTerm.term as term,
                    Option_.option_ as option_,
                    Option_.definition as definition,
                    Option_.image_link as image_link
                FROM  J_ConfusingTerm_Option 
                JOIN  ConfusingTerm on J_ConfusingTerm_Option.termId = ConfusingTerm.termId
                JOIN  Option_       on J_ConfusingTerm_Option.optionId = Option_.optionId
                WHERE ConfusingTerm.termId = ?");
            $stmt->bind_param("s",$termId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getOptionImages($termId){
            $stmt = $this->con->prepare("
                SELECT   
                    Option_.picture as photo
                FROM  J_ConfusingTerm_Option 
                JOIN  ConfusingTerm on J_ConfusingTerm_Option.termId = ConfusingTerm.termId
                JOIN  Option_       on J_ConfusingTerm_Option.optionId = Option_.optionId                
                WHERE ConfusingTerm.termId = ?");
            $stmt->bind_param("s",$termId);
            $stmt->execute();
            return $stmt->get_result();
        }        

        public function getTasksSolved($expertId){
            $stmt = $this->con->prepare("
                SELECT DISTINCT 
                    ConfusingTerm.term as term, 
                    ConfusingTerm.termId as termId,
                    ConfusingTerm.sentence as sentence,
                    Author.username as username,
                    Conflict.conflictId as conflictId
                FROM Conflict
                JOIN Author                    on Conflict.authorId    = Author.authorId
                JOIN J_Conflict_Expert         on Conflict.conflictId != J_Conflict_Expert.conflictId
                JOIN J_Conflict_ConfusingTerm  on Conflict.conflictId  = J_Conflict_ConfusingTerm.conflictId
                JOIN ConfusingTerm             on J_Conflict_ConfusingTerm.termId = ConfusingTerm.termId
                WHERE J_Conflict_Expert.expertId = ? AND J_Conflict_Expert.isSolved = 1
                ORDER BY term ASC
            ;");
            $stmt->bind_param("s",$expertId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getTasksUnsolved($expertId){
            $stmt = $this->con->prepare("
                SELECT DISTINCT 
                    ConfusingTerm.term as term, 
                    ConfusingTerm.termId as termId,
                    ConfusingTerm.sentence as sentence,
                    Author.username as username,
                    Conflict.conflictId as conflictId
                FROM Conflict
                JOIN Author                    on Conflict.authorId    = Author.authorId
                JOIN J_Conflict_Expert         on Conflict.conflictId  = J_Conflict_Expert.conflictId
                JOIN J_Conflict_ConfusingTerm  on Conflict.conflictId  = J_Conflict_ConfusingTerm.conflictId
                JOIN ConfusingTerm             on J_Conflict_ConfusingTerm.termId = ConfusingTerm.termId
                WHERE J_Conflict_Expert.expertId = ? AND J_Conflict_Expert.isSolved = 0
                ORDER BY term ASC
            ;");
            $stmt->bind_param("s",$expertId);        
            $stmt->execute();
            return $stmt->get_result();
        }

        public function submitDecision($choice, $writtenComment, $voiceComment){
            
            $stmt = $this->con->prepare("INSERT INTO `Choice` (`choiceId`,`choice`,`writtenComment`,`voiceComment`) VALUES (NULL, ?, ?, ?);");
            $stmt->bind_param("sss",$choice,$writtenComment,$voiceComment);
            
            if($stmt->execute()){
                return 1;
            }else{
                return 2;
            }
        }

        public function isExpertRegistered($expertId){

            $stmt = $this->con->prepare("SELECT token FROM Expert WHERE expertId = ?");
            $stmt->bind_param("s",$expertId);

            $stmt->execute();

            $result = $stmt->get_result();

            $row = $result->fetch_assoc();

            if( is_null( $row["token"] ) ){
                return 1;
            }else{
                return 2;
            }
        }

        public function registerToken($expertId, $token){
            
            $stmt = $this->con->prepare("UPDATE `Expert` SET token = ? WHERE expertId = ?");
            $stmt->bind_param("ss",$token,$expertId);
            
            if($stmt->execute()){
                return 1;
            }else{
                return 2;
            }
        }

        public function populate_J_Conflict_Expert_Choice($conflictId, $expertId){
            
            $choiceId = mysqli_insert_id($this->con);

            $stmt = $this->con->prepare("INSERT INTO `J_Conflict_Expert_Choice` (`conflictId`,`expertId`,`choiceId`) VALUES (?, ?, ?);");
            $stmt->bind_param("sss",$conflictId,$expertId,$choiceId);
            
            if($stmt->execute()){
                return $choiceId;
            }else{
                return -1;
            }
        }

        public function populate_J_Conflict_Expert($conflictId,$expertId){
                     
            $isSolved = 1;
            $stmt = $this->con->prepare("
                                         UPDATE J_Conflict_Expert 
                                         SET isSolved = ? 
                                         WHERE conflictId = ? AND expertId = ?");
             $stmt->bind_param("sss",$isSolved,$conflictId,$expertId);
            
            if($stmt->execute()){
                return 1;
            }else{
                return 2;
            }
        }


        public function getAllTokens(){
                     
            $stmt = $this->con->prepare("SELECT token FROM Expert");

            $stmt->execute();
            return $stmt->get_result();
        }

        public function getExpertsGivenConflict($conflictId, $expertId){
            $stmt = $this->con->prepare("
                SELECT 
                       Expert.expertId as expertId,
                       Expert.username as username,
                       Expert.token as token 
                FROM Expert 
                JOIN J_Conflict_Expert_Choice on J_Conflict_Expert_Choice.expertId = Expert.expertId 
                WHERE J_Conflict_Expert_Choice.conflictId = ? AND Expert.expertId != ?");
            $stmt->bind_param("ss",$conflictId, $expertId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getRelatedTokens(){

            $stmt = $this->con->prepare("
                SELECT   
                    ConfusingTerm.term as term,
                    Option_.option_ as option_,
                    Option_.definition as definition,
                    Option_.image_link as image_link
                FROM  J_ConfusingTerm_Option 
                JOIN  ConfusingTerm on J_ConfusingTerm_Option.termId = ConfusingTerm.termId
                JOIN  Option_       on J_ConfusingTerm_Option.optionId = Option_.optionId
                WHERE ConfusingTerm.termId = ?");
            $stmt->bind_param("s",$termId);
            $stmt->execute();
            return $stmt->get_result();

        }

    }
?>