<?php
class QuestionService{
    private $user;
    public $question;
    
    function __construct($user, $question){
        $this->user = $user;
        $this->question = $question;
    }
    
    public function addQ1($text){
        $result = false;
        
        if(null != $this->user){
            global $aryQ1;
            
            $answer = array_search($text, $aryQ1);
            
            if(null != $this->question){
                $this->question = QuestionDao::updateSingle($this->question, 1, $answer);
            }
            else{
                $this->question = new Question();
                $this->question->user_id = $this->user->user_id;
                $this->question->q1 = $answer;
                $this->question = QuestionDao::save($this->question);
            }
            
            $result = true;
        }
        return $result;
    }

    public function addQ2($text){
        $result = false;
        
        global $aryQ2;
        $answer = array_search($text, array_keys($aryQ2));
        
        if(null !== $this->question){
            QuestionDao::updateSingle($this->question, 2, $answer);
            $result = true;
        }
        return $result;
    }

    public function addQ3($text){
        $result = false;
        
        if(null != $this->user){
            $answer = $text;
            
            if(null != $this->question){
                QuestionDao::updateSingle($this->question, 3, $answer);
                $result = true;
            }
        }
        return $result;
    }
}
?>