<?php
    class DataBaseOperations{

    	private $con;
    	function __construct(){

    		require_once dirname(__FILE__). '/DataBaseConnect.php';
    		$db = new DataBaseConnect();
            $this->con = $db->connect();
            set_time_limit(300);
    	}

        /****************************************************************************
        *
        * Functions for aurthor Table
        *
        * 1) createAuthor($username, $pass, $firstname, $lastname, $email)
        * 2) authorLogin($username, $pass)
        * 3) isAuthorExist($username, $email)
        * 4) getAuthorByUsername($username)
        *
        *****************************************************************************/

        public function createAuthor($username, $pass, $firstname, $lastname, $email){
            
            if($this->isAuthorExist($username,$email)){

                return 0;

            }else{
                $password = md5($pass);
                $stmt = $this->con->prepare("INSERT INTO `author` (`authorId`,`username`,`password`,`firstname`,`lastname`,`email`) VALUES (NULL, ?, ?, ?, ?, ?);");
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
            $stmt = $this->con->prepare("SELECT authorId FROM author WHERE username = ? AND password = ?");
            $stmt->bind_param("ss",$username,$password);
            $stmt->execute();
            $stmt->store_result(); 
            return $stmt->num_rows > 0; 
        }

        private function isAuthorExist($username, $email){
            $stmt = $this->con->prepare("SELECT authorId FROM author WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute(); 
            $stmt->store_result(); 
            return $stmt->num_rows > 0; 
        }
        
        public function getAuthorByUsername($username){
            $stmt = $this->con->prepare("SELECT * FROM author WHERE username = ?");
            $stmt->bind_param("s",$username);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function getAuthorByEmail($email){
            $stmt = $this->con->prepare("SELECT * FROM author WHERE email = ?");
            $stmt->bind_param("s",$email);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        /****************************************************************************
        *
        * Functions for expert Table
        *
        * 1) createExpert($username, $pass, $firstname, $lastname, $email)
        * 2) expertLogin($username, $pass)
        * 3) isExpertExist($username, $email)
        * 4) getExpertByUsername($username)
        * 5) getExpertUsernameById($expertId)
        * 6) getExpertsByConflict($termId, $expertId)
        * 7) function setTasksToExpert()
        *
        *****************************************************************************/

    	public function createExpert($username, $pass, $firstname, $lastname, $email){
            if($this->isExpertExist($username,$email)){
                return 0;
            }else{
                $password = md5($pass);
                $stmt = $this->con->prepare("INSERT INTO `expert` (`expertId`,`username`,`password`,`firstname`,`lastname`,`email`) VALUES (NULL, ?, ?, ?, ?, ?);");
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
            $stmt = $this->con->prepare("SELECT expertId FROM expert WHERE username = '$username' AND password = '$password'");
            if (!$stmt)
            {
                echo "SELECT expertId FROM expert WHERE username = '$username' AND password = '$password'";
                return 0;
            }
            else{
                $stmt->execute();
                $stmt->store_result(); 
            }
            return $stmt->num_rows > 0; 
        }

        private function isExpertExist($username, $email){
            $stmt = $this->con->prepare("SELECT expertId FROM expert WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute(); 
            $stmt->store_result(); 
            return $stmt->num_rows > 0; 
        }
        
        public function getExpertByUsername($username){
            $stmt = $this->con->prepare("SELECT * FROM expert WHERE username = ?");
            $stmt->bind_param("s",$username);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function getExpertUsernameById($expertId){
            $stmt = $this->con->prepare("SELECT username FROM expert WHERE expertId = ?");
            $stmt->bind_param("s",$expertId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        // public function getExpertsTokenByConflict($termId, $expertId){
        //     $stmt = $this->con->prepare("
        //         SELECT
        //             expert.token as token 
        //         FROM expert 
        //         JOIN J_Conflict_Expert on J_Conflict_Expert.expertId = expert.expertId 
        //         WHERE J_Conflict_Expert.termId = ? AND expert.expertId != ?
        //     ");
        //     $stmt->bind_param("ss",$termId, $expertId);
        //     $stmt->execute();
        //     return $stmt->get_result();
        // }

        public function getTokenByExpertId($expertId){
            $stmt = $this->con->prepare("
                SELECT
                    expert.token as username,
                    expert.token as token 
                FROM expert 
                WHERE expert.expertId != ?
            ");
            $stmt->bind_param("ss",$termId, $expertId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function setTasksToExpert(){
            $expertId = mysqli_insert_id($this->con);

            $stmt = $this->con->prepare("
                SELECT
                    confusingterm.termId as termId 
                FROM confusingterm 
            ");
            $stmt->execute();
            $results = $stmt->get_result();

            while( $termId = $results->fetch_assoc()) {
                $stmt = $this->con->prepare("
                    INSERT INTO `categorysolution` VALUES (NULL, ?, ?, 0, '', '', '')
                ");
                $stmt->bind_param("ss", $expertId, $termId['termId']);
                $stmt->execute();
            }
            return $expertId; 
        }

        /****************************************************************************
        *
        * Functions for the other database operations
        *
        *  1) getTermByConflict($termId)
        *  2) getOptions($termId){
        *  3) getOptionImages($termId)
        *  4) getSolvedTasks($expertId)
        *  5) getUnsolvedTasks($expertId)
        *  6) countSolvedConflicts($termId)
        *  7) countUnsolvedConflictsByExpert($expertId)
        *  8) submitDecision($choice, $writtenComment, $voiceComment)
        *  9) isExpertRegistered($expertId)
        * 10) registerToken($expertId, $token)
        * 11) populate_J_Conflict_Expert_Choice($termId, $expertId)
        * 12) populate_J_Conflict_Expert($termId, $expertId)
        * 13) sendNotification($tokens, $message)
        *
        *****************************************************************************/
        public function getTermByConflict($termId){

            $stmt = $this->con->prepare("
                SELECT   
                    confusingterm.term as term
                FROM  confusingterm.termId = ?");
            $stmt->bind_param("s",$termId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function getOptions($termId){
            $stmt = $this->con->prepare("
                SELECT   
                    confusingterm.term as term,
                    option_.option_ as option_,
                    option_.definition as definition,
                    option_.image_link as image_link,
                    option_.IRI as IRI
                FROM  j_confusingterm_option 
                JOIN  confusingterm on j_confusingterm_option.termId = confusingterm.termId
                JOIN  option_       on j_confusingterm_option.optionId = option_.optionId
                WHERE confusingterm.termId = ?");
            $stmt->bind_param("s",$termId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getExactOptions($termId) {
            $stmt = $this->con->prepare("
                SELECT
                    exactcases.id as id,
                    exactcases.label as label, 
                    exactcases.iri as iri,
                    exactcases.elucidations as elucidations,
                    exactcases.sentences as sentences,
                    exactcases.definition as definition
                FROM exactcases
                WHERE exactcases.termId = ?
            ");
            $stmt->bind_param("s", $termId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getExpertCountOnExact($termId) {
            $stmt = $this->con->prepare("
                SELECT COUNT(DISTINCT expertId) as count
                FROM exactcasesolution
                WHERE termId = ?
            ");
            $stmt->bind_param("i", $termId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function getExpertCountOnCase($termId, $caseId) {
            $stmt = $this->con->prepare("
                SELECT COUNT(DISTINCT expertId) as count
                FROM exactcasesolution
                WHERE termId = ? and caseId = ? and selected = 1
            ");
            $stmt->bind_param("ii", $termId, $caseId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function getExpertDataOnExactSolution($termId, $caseId) {
            $stmt = $this->con->prepare("
                SELECT expert.username as username, exactcasesolution.reason as reason
                FROM expert, exactcasesolution
                WHERE expert.expertId = exactcasesolution.expertId and exactcasesolution.termId = ? and exactcasesolution.caseId = ?
            ");
            $stmt->bind_param("ii", $termId, $caseId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getSolutionOptions($termId) {
            $stmt = $this->con->prepare("
                SELECT choice
                FROM categorysolution
                WHERE termId = ?
                GROUP BY choice
            ");
            $stmt->bind_param("i", $termId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getNewCategories($termId) {
            $stmt = $this->con->prepare("
                SELECT DISTINCT choice
                FROM categorysolution
                WHERE termId = ? and choice not in
                (
                    SELECT DISTINCT option_.option_ as option_
                    FROM j_confusingterm_option
                    JOIN  confusingterm on j_confusingterm_option.termId = confusingterm.termId
                    JOIN  option_       on j_confusingterm_option.optionId = option_.optionId
                    WHERE confusingterm.termId = ?
                )
            ");
            $stmt->bind_param("ss",$termId, $termId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getSentences($termId) {
            $stmt = $this->con->prepare("
                SELECT id, sentence
                FROM  sentence
                WHERE termId = ?");
            $stmt->bind_param("s",$termId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getCountSynonymSolutionExpert($definitionId, $sentenceId) {
            $stmt = $this->con->prepare("
                SELECT expertId 
                FROM synonymsolution 
                WHERE definitionId = ? and sentenceId = ?
            ");
            $stmt->bind_param("ii", $definitionId, $sentenceId);
            $stmt->execute(); 
            $stmt->store_result(); 
            return $stmt->num_rows;
        }

        public function getSentenceIdByDefinitionId($definitionId) {
            $stmt = $this->con->prepare("
                SELECT sentenceId
                FROM synonymsolution
                WHERE definitionId = ?
            ");
            $stmt->bind_param("s", $definitionId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getSentenceBySentenceId($sentenceId) {
            $stmt = $this->con->prepare("
                SELECT sentence
                FROM sentence
                WHERE id = ?
            ");
            $stmt->bind_param("s", $sentenceId);
            $stmt->execute();
            return $stmt->get_result();
        }
        
        public function getDefinitions($termId, $expertId) {
            $stmt = $this->con->prepare("
                SELECT id, definition, expertId
                FROM  definition
                WHERE termId = ? ");
            $stmt->bind_param("s",$termId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getDefinitionsByTermId($termId) {
            $stmt = $this->con->prepare("
                SELECT id, definition, expertId
                FROM  definition
                WHERE termId = ? ");
            $stmt->bind_param("s",$termId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getDefinitionByDefinitionID($definitionId) {
            $stmt = $this->con->prepare("
                SELECT definition
                FROM definition
                WHERE id = ?
            ");
            $stmt->bind_param("s", $definitionId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getCountDefinitionsByTermId($termId) {
            $stmt = $this->con->prepare("
                SELECT COUNT(id) as count
                FROM definition
                WHERE termId = ? 
            ");
            $stmt->bind_param("i", $termId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function getSolutionSentencesExperts($definitionId) {
            $stmt = $this->con->prepare("
                SELECT username, expert.expertId as expertId
                FROM synonymsolution, sentence, expert
                WHERE definitionId = ?
                AND sentence.id = synonymsolution.sentenceId
                AND expert.expertId = synonymsolution.expertId
                GROUP BY expert.expertId
            ");
            $stmt->bind_param("i", $definitionId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getSolutionSentencesExpertsBySentence($definitionId, $sentenceId) {
            $stmt = $this->con->prepare("
                SELECT username, expert.expertId as expertId
                FROM synonymsolution, sentence, expert
                WHERE definitionId = ? AND sentenceId = ?
                AND sentence.id = synonymsolution.sentenceId
                AND expert.expertId = synonymsolution.expertId
                GROUP BY expert.expertId
            ");
            $stmt->bind_param("ii", $definitionId, $sentenceId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function isSynonymSolutionExist($sentenceId, $definitionId, $expertId) {
            $stmt = $this->con->prepare("
                SELECT id
                FROM synonymsolution
                WHERE sentenceId = ? and definitionId = ? and expertId = ?
            ");
            $stmt->bind_param("iii", $sentenceId, $definitionId, $expertId);
            $stmt->execute();
            return $stmt->get_result()->num_rows > 0;
        }

        public function getApproveData($termId, $expertId) {
            $stmt = $this->con->prepare("
                SELECT DISTINCT synonymsolution.sentenceId, synonymsolution.definitionId
                FROM  sentence, synonymsolution
                WHERE sentence.termId = ? and synonymsolution.expertId = ?");
            $stmt->bind_param("ss",$termId, $expertId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getApproveDataByTermID($termId) {
            $stmt = $this->con->prepare("
                SELECT DISTINCT synonymsolution.definitionId, synonymsolution.expertId
                FROM  sentence, synonymsolution
                WHERE sentence.termId = ? and sentence.id = synonymsolution.sentenceId");
            $stmt->bind_param("s",$termId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function isApproveExist($definitionId, $sentenceId, $expertId) {
            $stmt = $this->con->prepare("
                SELECT *
                FROM synonymsolution
                WHERE sentenceId = ? and definitionId = ? and expertId = ?
            ");
            $stmt->bind_param("iii", $definitionId, $sentenceId, $expertId);
            $stmt->execute();
            return $stmt->get_result()->num_rows > 0;
        }

        public function setApproveData($expertId, $sentenceIds, $definitionIds, $comment) {

            for( $i = 0 ; $i < count($sentenceIds) ; $i ++ ) {
                if (!$this->isApproveExist($sentenceIds[$i], $definitionIds[$i], $expertId)) {
                    $stmt = $this->con->prepare("
                        INSERT INTO synonymsolution
                        VALUES(NULL, ?, ?, ?, ?);
                    ");
                    $stmt->bind_param("ssss", $sentenceIds[$i], $definitionIds[$i], $expertId, $comment);
                    $stmt->execute();
                }
            }
            return true;
        }

        public function addDefinition($termId, $expertId, $definition){
            $stmt = $this->con->prepare("
                INSERT INTO definition
                VALUES(NULL, ?, ?, ?);
            ");
            $stmt->bind_param("sss",$definition, $termId, $expertId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function removeDefinition($id){
            $stmt = $this->con->prepare("
                DELETE FROM definition
                WHERE id=?
            ");
            $stmt->bind_param("i",$id);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getOptionImages($termId){
            $stmt = $this->con->prepare("
                SELECT   
                    option_.picture as photo
                FROM  j_confusingterm_option 
                JOIN  confusingterm on j_confusingterm_option.termId = confusingterm.termId
                JOIN  option_       on j_confusingterm_option.optionId = option_.optionId                
                WHERE confusingterm.termId = ?");
            $stmt->bind_param("s",$termId);
            $stmt->execute();
            return $stmt->get_result();
        }        

        public function getSolvedCategoryTask($expertId){
            $stmt = $this->con->prepare("
                SELECT DISTINCT 
                confusingterm.termId as termId,
                confusingterm.term as term,
                confusingterm.data as data,
                confusingterm.status as status
            FROM confusingterm
            WHERE confusingterm.type='category'
                    and (confusingterm.termId in (select termId from categorysolution where expertId = ?) or confusingterm.termId in (select termId from declinedterm where expertId = ?))
            ORDER BY term ASC
            ;");
            $stmt->bind_param("ss",$expertId,$expertId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getUnsolvedCategoryTask($expertId){
            $stmt = $this->con->prepare("
                SELECT DISTINCT 
                    confusingterm.termId as termId,
                    confusingterm.term as term,
                    confusingterm.data as data,
                    confusingterm.status as status
                FROM confusingterm
                WHERE confusingterm.type='category' and confusingterm.termId not in
                    (
                        SELECT DISTINCT
                            categorysolution.termId
                        FROM categorysolution
                        WHERE categorysolution.expertId = ?
                    )
                    and confusingterm.termId not in
                    (
                        SELECT DISTINCT
                            declinedterm.termId
                        FROM declinedterm
                        WHERE declinedterm.expertId = ?
                    )
                ORDER BY term ASC
            ;");
            $stmt->bind_param("ss",$expertId, $expertId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getSolvedExactTasks($expertId){
            $stmt = $this->con->prepare("
            SELECT DISTINCT 
                confusingterm.termId as termId,
                confusingterm.term as term,
                confusingterm.data as data,
                confusingterm.status as status
            FROM confusingterm
            WHERE confusingterm.type='exact'
                    and confusingterm.termId in (select termId from exactcasesolution where expertId = ?)
            ORDER BY term ASC
            ;");
            $stmt->bind_param("s",$expertId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getUnsolvedExactTask($expertId){
            $stmt = $this->con->prepare("
            SELECT DISTINCT 
                confusingterm.termId as termId,
                confusingterm.term as term,
                confusingterm.data as data,
                confusingterm.status as status
            FROM confusingterm
            WHERE confusingterm.type='exact'
                    and confusingterm.termId not in (select termId from exactcasesolution where expertId = ?)
            ORDER BY term ASC
            ;");
            $stmt->bind_param("s",$expertId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getSolvedEquivTasks($expertId){
            $stmt = $this->con->prepare("
            SELECT DISTINCT 
                confusingterm.termId as termId,
                confusingterm.term as term,
                confusingterm.data as data,
                confusingterm.status as status
            FROM confusingterm
            WHERE confusingterm.type='equiv'
                    and confusingterm.termId in (select termId from exactcasesolution where expertId = ?)
            ORDER BY term ASC
            ;");
            $stmt->bind_param("s",$expertId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getUnsolvedEquivTask($expertId){
            $stmt = $this->con->prepare("
                SELECT DISTINCT 
                    confusingterm.termId as termId,
                    confusingterm.term as term,
                    confusingterm.data as data,
                    confusingterm.status as status
                FROM confusingterm
                WHERE confusingterm.type='equiv' and confusingterm.termId not in
                    (
                        SELECT DISTINCT
                            exactcasesolution.termId
                        FROM exactcasesolution
                        WHERE exactcasesolution.expertId = ?
                    )
                ORDER BY term ASC
            ;");
            $stmt->bind_param("s",$expertId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getApproveTask($expertId){
            $stmt = $this->con->prepare("
                SELECT DISTINCT 
                    confusingterm.term as term, 
                    confusingterm.termId as termId,
                    confusingterm.data as data,
                    confusingterm.status as status,
                    Count(DISTINCT sentence.id) as sentenceCount,
                    SUM(CASE WHEN synonymsolution.expertId=? THEN 1 ELSE 0 END) != 0 OR SUM(CASE WHEN declinedterm.expertId=? THEN 1 ELSE 0 END) != 0 as isSolved
                FROM confusingterm
                    INNER JOIN sentence on confusingterm.termId = sentence.termId
                    LEFT JOIN synonymsolution on sentence.id = synonymsolution.sentenceId
                    LEFT JOIN declinedterm on declinedterm.termId = confusingterm.termId
                where confusingterm.type='synonym'
                GROUP BY termId
                ORDER BY term ASC
            ;");
            $stmt->bind_param("ss",$expertId,$expertId);
            $stmt->execute();
            return $stmt->get_result();
        }
        
        public function setAddTermSolution($termId, $expertId, $termType, $subpart, $superpart, $alwaysHasPart, $alwaysPartOf, $maybePartOf, $subclassOf, $comment) {
            $stmt = $this->con->prepare("
                DELETE FROM addtermsolution
                WHERE termId = ? and expertId = ? 
            ");
            $stmt->bind_param("ii", $termId, $expertId);
            $stmt->execute();

            $stmt = $this->con->prepare("
                INSERT INTO addtermsolution
                VALUES( NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
            ;");
            $stmt->bind_param("ssssssssss", $termId, $expertId, $termType, $subpart, $superpart, $alwaysHasPart, $alwaysPartOf, $maybePartOf, $subclassOf, $comment);
            return $stmt->execute();
        }

        public function declineTerm($termId, $expertId, $reason, $alternativeTerm) {
            $stmt = $this->con->prepare("
                DELETE FROM categorysolution
                WHERE termId = ? and expertId = ?
            ");
            $stmt->bind_param("ii", $termId, $expertId);
            $stmt->execute();

            $resDefinition = $this->getDefinitions($termId, $expertId);

            while ($row = $resDefinition->fetch_assoc()) {
                $definitionId = $row['id'];
                $this->deleteApproveDecision($definitionId, $expertId);
            }

            $stmt = $this->con->prepare("
                DELETE FROM addtermsolution
                WHERE termId = ? and expertId = ? 
            ");
            $stmt->bind_param("ii", $termId, $expertId);
            $stmt->execute();

            $stmt = $this->con->prepare("
                DELETE FROM addtermsynonyms
                WHERE termId = ? and expertId = ? 
            ");
            $stmt->bind_param("ii", $termId, $expertId);
            $stmt->execute();
            
            $stmt = $this->con->prepare("
                INSERT INTO declinedterm
                VALUES( NULL, ?, ?, ?, ?);
            ");
            $stmt->bind_param("iiss", $termId, $expertId, $reason, $alternativeTerm);
            return $stmt->execute();
        }

        public function addSynonyms($termId, $expertId, $experts, $synonyms) {
            $stmt = $this->con->prepare("
                DELETE FROM addtermsynonyms
                WHERE termId = ? 
            ");
            $stmt->bind_param("i", $termId);
            $stmt->execute();

            for ($i = 0; $i < count($experts); $i ++) {
                $stmt = $this->con->prepare("
                    INSERT INTO addtermsynonyms
                    VALUES( NULL, ?, ?, ?);
                ;");
                $stmt->bind_param("sss", $termId, $experts[$i], $synonyms[$i]);
                if (!$stmt->execute()) {
                    return false;
                }
            }
            return true;
        }
        
        public function getAddTermTasks($expertId){
            $stmt = $this->con->prepare("
                SELECT DISTINCT 
                    confusingterm.term as term, 
                    confusingterm.termId as termId,
                    confusingterm.data as data,
                    confusingterm.status as status,
                    SUM(CASE WHEN addtermsolution.expertId=? THEN 1 ELSE 0 END) != 0 OR SUM(CASE WHEN declinedterm.expertId=? THEN 1 ELSE 0 END) != 0 as isSolved
                FROM confusingterm
                    LEFT JOIN addtermsolution on addtermsolution.termId = confusingterm.termId
                    LEFT JOIN declinedterm on declinedterm.termId = confusingterm.termId
                where confusingterm.type='addTerm'
                GROUP BY termId
                ORDER BY term ASC
            ;");
            $stmt->bind_param("ss",$expertId, $expertId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function countSolvedConflicts($termId){
            $stmt = $this->con->prepare("
                SELECT COUNT(DISTINCT expertId) as count
                FROM categorysolution
                WHERE termId = ?
            ;");
            $stmt->bind_param("s",$termId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function countExactSolvedConflicts($termId){
            $stmt = $this->con->prepare("
                SELECT COUNT(DISTINCT expertId) as count
                FROM exactcasesolution
                WHERE termId = ?
            ;");
            $stmt->bind_param("s",$termId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function getSolvedConflictsByIDAndOption($termId, $choice) {
            $stmt = $this->con->prepare("
                SELECT DISTINCT username
                FROM categorysolution, expert
                WHERE termId = ? and categorysolution.expertId = expert.expertId
                    and expert.expertId not in (
                        SELECT DISTINCT expertId
                        FROM categorysolution
                        WHERE termId = ? and choice = ?
                    )
            ;");
            $stmt->bind_param("iis",$termId, $termId, $choice);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getSolvedConflictsNewByIDAndOption($termId, $choice) {
            $stmt = $this->con->prepare("
                SELECT DISTINCT username
                FROM categorysolution, expert
                WHERE termId = ? and categorysolution.expertId = expert.expertId
                    and expert.expertId in (
                        SELECT DISTINCT expertId
                        FROM categorysolution
                        WHERE termId = ? and choice = ?
                    )
            ;");
            $stmt->bind_param("iis",$termId, $termId, $choice);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function countDeclinedTerm($termId){
            $stmt = $this->con->prepare("
                SELECT COUNT(expertId)
                FROM declinedterm
                WHERE termId = ?
            ;");
            $stmt->bind_param("s",$termId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function countExpertAgreedOption($termId, $option) {
            $stmt = $this->con->prepare("
                SELECT COUNT(DISTINCT expertId) as count
                FROM categorysolution
                WHERE termId = ? and choice = ?
            ;");
            $stmt->bind_param("is", $termId, $option);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function countSolvedApproveConflicts($termId){
            $stmt = $this->con->prepare("
                SELECT DISTINCT
                    synonymsolution.expertId
                FROM confusingterm
                    INNER JOIN sentence on confusingterm.termId = sentence.termId
                    INNER JOIN synonymsolution on sentence.id = synonymsolution.sentenceId
                WHERE sentence.termId = $termId
                GROUP BY synonymsolution.expertId
            ;");
            $stmt->execute();
            return $stmt->get_result()->num_rows;
        }

        public function getSolvedApproveConflictsExperts($termId){
            $stmt = $this->con->prepare("
                SELECT DISTINCT
                    synonymsolution.expertId as expertId
                FROM confusingterm
                    INNER JOIN sentence on confusingterm.termId = sentence.termId
                    INNER JOIN synonymsolution on sentence.id = synonymsolution.sentenceId
                WHERE sentence.termId = $termId
                GROUP BY synonymsolution.expertId
            ;");
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getSynonymSolutionsTermIdExpertId($definitionId, $expertId) {
            $stmt = $this->con->prepare("
                SELECT sentenceId
                FROM synonymsolution
                WHERE definitionId = ? and expertId = ?
                ORDER BY sentenceId ASC
            ");
            $stmt->bind_param("ii", $definitionId, $expertId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function countSolvedAddTermConflicts($termId){
            $stmt = $this->con->prepare("
                SELECT COUNT(expertId)
                FROM addtermsolution
                WHERE termId = ?
            ;");
            $stmt->bind_param("s",$termId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function getAddTermSolutionByTermId($termId) {
            $stmt = $this->con->prepare("
                SELECT expert.expertId as expertId, 
                        type, 
                        subpart, 
                        superpart, 
                        username, 
                        alwaysHasPart, 
                        alwaysPartOf, 
                        maybePartOf, 
                        subclassOf, 
                        comment
                FROM addtermsolution, expert
                WHERE termId = ? and expert.expertId = addtermsolution.expertId
            ");
            $stmt->bind_param("i", $termId);
            $stmt->execute();
            return $stmt->get_result();
        }

         public function countUnsolvedConflictsByExpert($expertId){
            $stmt = $this->con->prepare("
                SELECT COUNT(termId)
                FROM confusingterm
                WHERE termId not in (
                    SELECT termId
                    FROM categorysolution
                    WHERE expertId = ?
                ) and termId not in (
                    SELECT DISTINCT termId
                    FROM sentence, synonymsolution
                    WHERE sentence.id = synonymsolution.sentenceId and synonymsolution.expertId = ?
                ) and termId not in (
                    SELECT DISTINCT termId
                    FROM addtermsolution
                    WHERE expertId = ?
                ) and termId not in (
                    SELECT DISTINCT termId
                    FROM declinedterm
                    WHERE expertId = ?
                )
            ;");
            $stmt->bind_param("ssss", $expertId, $expertId, $expertId, $expertId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function countCompletedConflictsByExpert($expertId){
            $stmt = $this->con->prepare("
                SELECT COUNT(termId)
                FROM confusingterm
                WHERE termId in (
                    SELECT DISTINCT termId
                    FROM categorysolution
                    WHERE expertId = ?
                ) or termId in (
                    SELECT DISTINCT termId
                    FROM sentence, synonymsolution
                    WHERE sentence.id = synonymsolution.sentenceId and synonymsolution.expertId = ?
                ) or termId in (
                    SELECT DISTINCT termId
                    FROM exactcasesolution
                    WHERE expertId = ?
                ) or termId in (
                    SELECT DISTINCT termId
                    FROM addtermsolution
                    WHERE expertId = ?
                ) or termId in (
                    SELECT DISTINCT termId
                    FROM declinedterm
                    WHERE expertId = ?
                )
            ;");
            $stmt->bind_param("sssss", $expertId, $expertId, $expertId, $expertId, $expertId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function submitDecision($termId, $expertId, $choice, $writtenComment, $voiceComment){
            $stmt = $this->con->prepare("
                INSERT INTO `categorysolution` VALUES(NULL, ?, ?, ?, ?, ?);
            ");
            $stmt->bind_param("sssss", $expertId, $termId, $choice, $writtenComment, $voiceComment);

            if($stmt->execute()){
                return 1;
            }
            return 2;
        }

        public function submitExactDecision($termId, $expertId, $caseId, $selected, $reason){
            $stmt = $this->con->prepare("
                INSERT INTO `exactcasesolution` VALUES(NULL, ?, ?, ?, ?, ?);
            ");
            $stmt->bind_param("sssss", $expertId, $termId, $caseId, $selected, $reason);

            if($stmt->execute()){
                return 1;
            }
            return 2;
        }

        public function isExpertRegistered($expertId){
            $stmt = $this->con->prepare("SELECT token FROM expert WHERE expertId = ?");
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
            $stmt = $this->con->prepare("UPDATE `expert` SET token = ? WHERE expertId = ?");
            $stmt->bind_param("ss",$token,$expertId);
            
            if($stmt->execute()){
                return 1;
            }else{
                return 2;
            }
        }

        // public function populate_J_Conflict_Expert_Choice($termId, $expertId){
        //     $choiceId = mysqli_insert_id($this->con);
        //     $stmt = $this->con->prepare("INSERT INTO `J_Conflict_Expert_Choice` (`termId`,`expertId`,`choiceId`) VALUES (?, ?, ?);");
        //     $stmt->bind_param("sss",$termId,$expertId,$choiceId);
            
        //     if($stmt->execute()){
        //         return $choiceId;
        //     }else{
        //         return -1;
        //     }
        // }

        // public function populate_J_Conflict_Expert($termId, $expertId){
                     
        //     $isSolved = 1;
        //     $stmt = $this->con->prepare("
        //                                  UPDATE J_Conflict_Expert 
        //                                  SET isSolved = ? 
        //                                  WHERE termId = ? AND expertId = ?");
        //      $stmt->bind_param("sss",$isSolved,$termId,$expertId);
            
        //     if($stmt->execute()){
        //         return 1;
        //     }else{
        //         return 2;
        //     }
        // }

        public function getAllTokens(){
                     
            $stmt = $this->con->prepare("SELECT token FROM expert");
             $stmt->execute();
            return $stmt->get_result();
        }


        public function sendNotification($tokens, $message){
            $url = 'https://fcm.googleapis.com/fcm/send';
            $fields = array('registration_ids' => $tokens,
                        'data' => $message );
            $headers = array('Authorization:key = AAAAYXS_iEo:APA91bGZ50RhB0sZIBf6vmXohxOd_wJsDVCQPJCMeqtujIfG9JhLPUpA5C4Q_OFW-nacNXHfoSJPjJKMagr54b9i4JUFpcXocf2oAGzrVaTMsKpBNufnNAGRRQrO-CHGJ3eSdjnv9twF','Content-Type: application/json');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);  
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
             $result = curl_exec($ch);
            
            if ($result === FALSE) {
                die('Curl failed: ' . curl_error($ch));
            }
            curl_close($ch);
            return $result;
        }

        private function isTermExist($iri, $type) {
            $stmt = $this->con->prepare("SELECT * FROM confusingterm WHERE IRI = ? AND type = ?");
            $stmt->bind_param("ss", $iri, $type);
            $stmt->execute();
            $stmt->store_result();
            return $stmt->num_rows > 0;
        }

        private function getConfusingTermByIRI($iri, $type) {
            $stmt = $this->con->prepare("SELECT * FROM confusingterm WHERE IRI = ? AND type = ?");
            $stmt->bind_param("ss", $iri, $type);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function getConfusingTermByID($termId) {
            $stmt = $this->con->prepare("SELECT * FROM confusingterm WHERE termId = ?");
            $stmt->bind_param("i", $termId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function insertTermToConfusing($iri, $term, $data, $type, $authorId) {
            if(!$this->isTermExist($iri, $type)) {
                $stmt = $this->con->prepare("INSERT INTO `confusingterm` (`termId`,`IRI`,`term`,`data`,`type`,`authorId`) VALUES (NULL, ?, ?, ?, ?, ?);");
                $stmt->bind_param("ssssi", $iri, $term, $data, $type, $authorId);
                $stmt->execute();
                $term = $this->getConfusingTermByIRI($iri, $type);
                return $term['termId'];
            }
            return "Exist";
        }

        public function insertEquivTermToConfusing($iri, $term, $data, $type, $authorId, $elucidations, $sentences, $definition) {
            if(!$this->isTermExist($iri, $type)) {
                $stmt = $this->con->prepare("INSERT INTO `confusingterm` (`termId`,`IRI`,`term`,`data`,`type`,`authorId`, `elucidations`, `sentences`, `definition`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?);");
                $stmt->bind_param("ssssisss", $iri, $term, $data, $type, $authorId, $elucidations, $sentences, $definition);
                $stmt->execute();
                $term = $this->getConfusingTermByIRI($iri, $type);
                return $term['termId'];
            }
            return "Exist";
        }

        private function isOptionExist($iri) {
            $stmt = $this->con->prepare("SELECT * FROM option_ WHERE IRI = ?");
            $stmt->bind_param("s", $iri);
            $stmt->execute();
            $stmt->store_result();
            return $stmt->num_rows > 0;
        }

        private function getOptionByIRI($iri) {
            $stmt = $this->con->prepare("SELECT * FROM option_ WHERE IRI = ?");
            $stmt->bind_param("s", $iri);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function insertOption($iri, $option, $definition, $image) {
            if(!$this->isOptionExist($iri)) {
                $stmt = $this->con->prepare("INSERT INTO `option_` (`optionId`,`IRI`,`option_`,`definition`,`image_link`) VALUES (NULL, ?, ?, ?, ?);");
                $stmt->bind_param("ssss", $iri, $option, $definition, $image);
                $stmt->execute();
            }
            $option = $this->getOptionByIRI($iri);
            return $option['optionId'];
        }

        public function relateTermAndOption($termId, $optionId) {
            $stmt = $this->con->prepare("INSERT INTO `j_confusingterm_option` (`id`,`termId`,`optionId`) VALUES (NULL, ?, ?);");
            $stmt->bind_param("ii", $termId, $optionId);
            $stmt->execute();
        }

        public function insertSentence($sentence, $termId) {
            $stmt = $this->con->prepare("INSERT INTO `sentence` (`id`,`sentence`,`termId`) VALUES (NULL, ?, ?);");
            $stmt->bind_param("si", $sentence, $termId);
            $stmt->execute();
        }

        public function insertDefinition($definition, $termId) {
            $stmt = $this->con->prepare("INSERT INTO `definition` (`id`,`definition`,`termId`,`expertId`) VALUES (NULL, ?, ?, NULL);");
            $stmt->bind_param("si", $definition, $termId);
            $stmt->execute();
        }

        public function getAuthorId($username) {
            $email = str_replace(" ", ".", $username);
            $email .= "@email.arizona.edu";
            $pass = "aaaaaa";
            $firstname = $username;
            $lastname = $username;
            if(!$this->isAuthorExist($username, $email)) {
                $stmt = $this->con->prepare("INSERT INTO `author` (`authorId`,`username`,`password`,`firstname`,`lastname`,`email`) VALUES (NULL, ?, ?, ?, ?, ?);");
                $stmt->bind_param("sssss", $username, $pass, $firstname, $lastname, $email);
                $stmt->execute();
            }
            $author = $this->getAuthorByEmail($email);
            return $author['authorId'];
        }

        public function getAllTasks() {
            $stmt = $this->con->prepare("SELECT * from confusingterm;");
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getAllConflictsTasks() {
            $stmt = $this->con->prepare("SELECT * from confusingterm where status != 'solved' and status != 'declined';");
            $stmt->execute();
            return $stmt->get_result();
        }

        public function markTermTough($termId) {
            $stmt = $this->con->prepare("UPDATE confusingterm SET status = 'tough' WHERE confusingterm.termId = ?;");
            $stmt->bind_param("i", $termId);
            $stmt->execute();            
        }

        public function markTermSolved($termId) {
            $stmt = $this->con->prepare("UPDATE confusingterm SET status = 'solved' WHERE confusingterm.termId = ?;");
            $stmt->bind_param("i", $termId);
            $stmt->execute();            
        }

        public function markTermDeclined($termId) {
            $stmt = $this->con->prepare("UPDATE confusingterm SET status = 'declined' WHERE confusingterm.termId = ?;");
            $stmt->bind_param("i", $termId);
            $stmt->execute();            
        }

        public function getDeclinedDataByTermId($termId) {
            $stmt = $this->con->prepare("
                SELECT reason, alternativeTerm, username
                FROM declinedterm, expert
                WHERE termId = ? and declinedterm.expertId = expert.expertId
            ");
            $stmt->bind_param("i", $termId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getComments($termId) {
            $stmt = $this->con->prepare("
                SELECT DISTINCT username, writtenComment, expert.expertId as expertId
                FROM categorysolution, expert
                WHERE termId = ? and categorysolution.expertId = expert.expertId
            ");
            $stmt->bind_param("i", $termId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getReasons($termId) {
            $stmt = $this->con->prepare("
                SELECT DISTINCT username, reason, expert.expertId as expertId
                FROM exactcasesolution, expert
                WHERE termId = ? and exactcasesolution.expertId = expert.expertId
            ");
            $stmt->bind_param("i", $termId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getSynonyms($termId, $type) {
            $stmt = $this->con->prepare("
                SELECT DISTINCT username, synonym
                FROM addtermsynonyms, expert, addtermsolution
                WHERE addtermsynonyms.termId = ? and addtermsolution.termId = ? and  type = ? and addtermsynonyms.expertId = addtermsolution.expertId and expert.expertId = addtermsynonyms.expertId
            ");
            $stmt->bind_param("iis", $termId, $termId, $type);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getSynonymsByTermIdExpertId($termId, $expertId) {
            $stmt = $this->con->prepare("
                SELECT DISTINCT synonym, username
                FROM addtermsynonyms, expert
                WHERE termId = ? and expert.expertId = ? and expert.expertId = addtermsynonyms.expertId
            ");
            $stmt->bind_param("ii", $termId, $expertId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getSynonymsByTermId($termId) {
            $stmt = $this->con->prepare("
                SELECT DISTINCT expertId, synonym
                FROM addtermsynonyms
                WHERE termId = ?
            ");
            $stmt->bind_param("i", $termId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getAddTermCommentsByTermId($termId) {
            $stmt = $this->con->prepare("
                SELECT DISTINCT expert.expertId as expertId, comment, username
                FROM addtermsolution, expert
                WHERE termId = ? and expert.expertId = addtermsolution.expertId
            ");
            $stmt->bind_param("i", $termId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getApproveTermCommentsByTermId($termId) {
            $stmt = $this->con->prepare("
            SELECT DISTINCT
                synonymsolution.comment as comment, synonymsolution.expertId as expertId
            FROM confusingterm
                INNER JOIN sentence on confusingterm.termId = sentence.termId
                INNER JOIN synonymsolution on sentence.id = synonymsolution.sentenceId
            WHERE sentence.termId = ?
            ");
            $stmt->bind_param("i", $termId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getExpertDecisionOnCategory($termId, $expertId) {
            $stmt = $this->con->prepare("
                SELECT DISTINCT choice
                FROM categorysolution
                WHERE termId = ? and expertId = ?
            ");
            $stmt->bind_param("ii", $termId, $expertId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getCountExpertDecisionOnExact($termId, $expertId) {
            $stmt = $this->con->prepare("
                SELECT COUNT(id) as count
                FROM exactcasesolution
                WHERE termId = ? and expertId = ?
            ");
            $stmt->bind_param("ii", $termId, $expertId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function getExpertsOnExact($termId) {
            $stmt = $this->con->prepare("
                SELECT DISTINCT expertId
                FROM exactcasesolution
                WHERE termId = ?
            ");
            $stmt->bind_param("i", $termId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getExpertDecisionOnExact($termId, $expertId) {
            $stmt = $this->con->prepare("
                SELECT DISTINCT caseId
                FROM exactcasesolution
                WHERE termId = ? and expertId = ? and selected = 1
            ");
            $stmt->bind_param("ii", $termId, $expertId);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getCountOnExact($termId, $expertId) {
            $stmt = $this->con->prepare("
                SELECT count(id) as count
                FROM exactcasesolution
                WHERE termId = ? and expertId = ? and selected = 1
            ");
            $stmt->bind_param("ii", $termId, $expertId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function isTermDeclinedByExpert($termId, $expertId){
            $stmt = $this->con->prepare("
                SELECT ID 
                FROM declinedterm 
                WHERE termId = ? and expertId = ?
            ");
            $stmt->bind_param("ii", $termId, $expertId);
            $stmt->execute(); 
            $stmt->store_result(); 
            return $stmt->num_rows > 0; 
        }

        public function deleteDeclinedDecision($termId, $expertId) {
            $stmt = $this->con->prepare("
                DELETE FROM declinedterm
                WHERE termId = ? and expertId = ?
            ");
            $stmt->bind_param("ii", $termId, $expertId);
            $stmt->execute();
        }

        public function deleteCurrentDecisions($termId, $expertId) {
            $stmt = $this->con->prepare("
                DELETE FROM categorysolution
                WHERE termId = ? and expertId = ?
            ");
            $stmt->bind_param("ii", $termId, $expertId);
            $stmt->execute();
        }

        public function deleteCurrentExactDecisions($termId, $expertId) {
            $stmt = $this->con->prepare("
                DELETE FROM exactcasesolution
                WHERE termId = ? and expertId = ?
            ");
            $stmt->bind_param("ii", $termId, $expertId);
            $stmt->execute();
        }

        public function deleteApproveDecision($definitionId, $expertId) {
            $stmt = $this->con->prepare("
                DELETE FROM synonymsolution
                WHERE definitionId = ? and expertId = ?
            ");
            $stmt->bind_param("ss",$definitionId, $expertId);
            $stmt->execute();
        }

        public function getCategorySolutionCount($termId, $choice, $expertId) {
            $stmt = $this->con->prepare("
                SELECT COUNT(id) as count
                FROM categorysolution
                WHERE termId = ? and expertId != ? and choice = ?
            ");
            $stmt->bind_param("sss", $termId, $expertId, $choice);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function getExactSolutionCount($termId, $caseId, $expertId) {
            $stmt = $this->con->prepare("
                SELECT COUNT(id) as count
                FROM exactcasesolution
                WHERE termId = ? and expertId != ? and caseId = ? and selected = 1
            ");
            $stmt->bind_param("sss", $termId, $expertId, $caseId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function insertExactCase($termId, $iri, $elucidations, $sentences, $definition, $label, $termCreator) {
            $stmt = $this->con->prepare("INSERT INTO `exactcases` (`id`,`termId`,`iri`,`elucidations`,`sentences`,`definition`,`label`,`termCreator`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?);");
            $stmt->bind_param("issssss", $termId, $iri, $elucidations, $sentences, $definition, $label, $termCreator);
            $stmt->execute();
        }
    }
?>
