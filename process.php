<?php

function processUser($message, $user){
    $chat_id = $message['chat']['id'];
    $userId = $message['from']['id'];
    $firstName = isset($message['from']['first_name'])?$message['from']['first_name']:'';
    $lastName = isset($message['from']['last_name'])?$message['from']['last_name']:'';
    $userName = isset($message['from']['username'])?$message['from']['username']:'';
    
    if(null == $user){
        $user = new User();
        $user->user_id = $userId;
        $user->user_name = $userName;
        $user->first_name = $firstName;
        $user->last_name = $lastName;
        $user->chat_id = $chat_id;
        $user->authorized = 'N';
        $user->member_type = MemberType::L4;
        $user->stage = Stage::UNAUTHORIZED;
        $user->lang = 'tc';
    }
    else{
        $user->user_name = $userName;
        $user->first_name = $firstName;
        $user->last_name = $lastName;
        $user->chat_id = $chat_id;
    }
    
    return $user;
}

function processLang($lang){
    global $aryQ1;
    global $aryQ1Tc;
    global $aryQ1En;
    global $aryQ2;
    global $aryQ2Tc;
    global $aryQ2En;
    global $aryParty;
    global $aryPartyTc;
    global $aryPartyEn;
    global $Q1Agree;
    global $Q1AgreeTc;
    global $Q1AgreeEn;
    global $Q1Disagree;
    global $Q1DisagreeTc;
    global $Q1DisagreeEn;
    
    if('en' === $lang ){
        $aryQ1 = $aryQ1En;
        $aryQ2 = $aryQ2En;
        $aryParty = $aryPartyEn;
        $Q1Agree = $Q1AgreeEn;
        $Q1Disagree = $Q1DisagreeEn;
        $GLOBALS['WORD'] = $GLOBALS['WORD_EN'];
    }
    else{
        $aryQ1 = $aryQ1Tc;
        $aryQ2 = $aryQ2Tc;
        $aryParty = $aryPartyTc;
        $Q1Agree = $Q1AgreeTc;
        $Q1Disagree = $Q1DisagreeTc;
        $GLOBALS['WORD'] = $GLOBALS['WORD_TC'];
    }
}

function processMessage($message) {
    // process incoming message
    $message_id = $message['message_id'];
    $chat_id = $message['chat']['id'];
    
    $userId = $message['from']['id'];
    
    global $aryQ1;
    global $aryQ2;
    
    
    if (isset($message['text'])) {
        // incoming text message
        $text = $message['text'];
        logDebug("Text is: $text\n");
        
        
        $user = UserDao::get($userId);
        
        if(null == $user){
            $question = null;
        }
        else{
            $question = QuestionDao::get($userId);
        }
        
        $user = processUser($message, $user);
        $questionService = new QuestionService($user, $question);
        
        processLang($user->lang);
        
        switch($user->stage){
            case Stage::UNAUTHORIZED:
                handleStageUnauthorized($user, $text);
                break;
            case Stage::AUTHORIZED:
                handleStageAuthorized($user, $questionService, $text);
                break;
            case Stage::Q1:
                handleStageQ1($user, $questionService, $text);
                break;
            case Stage::Q2:
                handleStageQ2($user, $questionService, $text, $message_id);
                break;
            case Stage::Q2_CONFIRM:
                handleStageQ2Confirm($user, $questionService, $text, $message_id);
                break;
            case Stage::Q3:
                handleStageQ3($user, $questionService, $text);
                break;
            case Stage::RESTART:
                handleStageRestart($user, $questionService, $text, $message_id);
                break;
            case Stage::DELETED:
                handleStageDeleted($user, $questionService, $text);
                break;
            default:
                break;
        }
    } else {
        apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'I understand only text messages'));
    }
    
}

function respondPollingResult($chat_id, $q2Index){
    global $aryQ2;
    
    $result = getResult($q2Index);
    
    $q2Key = array_keys($aryQ2);
    $q2Array = $aryQ2[$q2Key[$q2Index]];
    
    $total = array_sum($result);
    
    $res = sprintf($GLOBALS['WORD']['SURVEY_RESULT'], $q2Key[$q2Index], $total);
    
    arsort($result);
    $row = 0;
    foreach($result as $key => $val) {
        $res .= $q2Array[$key].": $val\n";
        $count = ($val/$total * 10);
        
        for($i=0; $i < $count; $i++){
            $res .= '✅';
        }
        $res .= ' *'.floor($count * 10)."%*\n\n";
        $row++;
        if($row == 5){
            $res .= $GLOBALS['WORD']['SURVEY_RESULT_MORE'];
            break;
        }
    }
    $res .= $GLOBALS['WORD']['SURVEY_RESULT_LINK'];
    $res .= $GLOBALS['WORD']['SURVEY_RESULT_RESTART_INSTRUCTION'];
    respondWithMessage($chat_id, $res);
}

function respondWithMessage($chat_id, $message){
    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => $message, 'parse_mode' => 'Markdown'));
    print "API: $message.<BR>\n";
}

function respondInvalidRequest($chat_id, $message_id){
    respondWithQuote($chat_id, $message_id, 'Cool.  But I do not understand.');
}

function respondWithQuote($chat_id, $message_id, $message){
    apiRequestJson("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => $message));
}

function respondWithKeyboard($chat_id, $message, $keyboardOptions){
    print "API: $message.<BR>\n";
    apiRequestJson("sendMessage", 
                array('chat_id' => $chat_id, 
                "text" => $message, 
                'parse_mode' => 'Markdown', 
                'reply_markup' => array('keyboard' => $keyboardOptions, 
                                        'one_time_keyboard' => true, 
                                        'resize_keyboard' => true))
                      );
}

function logDebug($msg) {
    if (DEBUG) {
        file_put_contents(DEBUG_FILE_NAME, $msg."\n", FILE_APPEND | LOCK_EX);
    }
}



function formatInvitationMessage($invitation){
    $url = INVITATION_LINK_PREFIX.$invitation->link;
    
    return sprintf($GLOBALS['WORD']['INVITATION_LINK'], $url);
}

function respondWelcomeMessage($chat_id){
    global $aryQ1;
    respondWithMessage($chat_id, $GLOBALS['WORD']['WELCOME']);
    respondWithMessage($chat_id, $GLOBALS['WORD']['WELCOME_TERMS']);
    respondWithKeyboard($chat_id, $GLOBALS['WORD']['WELCOME_TERMS_AGREE'], array(array_values($aryQ1)));
}

function respondNotAuthorized($chat_id){
    respondWithMessage($chat_id, 'Not authorized');
}

function respondQ1($chat_id){
    global $aryQ2;
    //which district?
    respondWithKeyboard($chat_id, $GLOBALS['WORD']['SURVEY_Q1'], array_chunk(array_keys($aryQ2), 3));
}

function respondQ2($chat_id, $question){
    global $aryQ2;
    
    $q2Key = array_keys($aryQ2);
    $district = $q2Key[$question->q2];
    
    $option = $aryQ2[$district];
    shuffle($option);
    respondWithKeyboard($chat_id, sprintf($GLOBALS['WORD']['SURVEY_Q2'], $district), array_chunk($option, 3));
}

function respondQ2Confirm($chat_id, $choice){
    $confirmArray = array( 'yes', 'no');
    respondWithKeyboard($chat_id, sprintf($GLOBALS['WORD']['SURVEY_Q2_CONFIRM'], $choice), array($confirmArray));
}

function handleStageUnauthorized($user, $text){
    //authorize the user
    $args = explode(' ', $text);
    if(strpos($text, "/start") !== 0){
        respondNotAuthorized($user->chat_id);
    }
    else{
        if (count($args) > 1){
            $invitation = InvitationDao::getByLink($args[1]);
            if(null != $invitation){
                $invitationUser = $invitation->useQuota($user);
                
                print_r($invitationUser);
                UserDao::save($user);
                InvitationDao::save($invitation);
                InvitationUserDao::save($invitationUser);
                
                respondWelcomeMessage($user->chat_id);
            }
            else{
                respondNotAuthorized($user->chat_id);
            }
        }
        else{
            respondNotAuthorized($user->chat_id);
        }
    }
}

function handleStageAuthorized($user, $questionService, $text){
    global $Q1Agree;
    global $Q1Disagree;
    
    $aryAgreeText = array('agree', 'ok', 'yes', $Q1Agree);
    $aryDisagreeText = array('not agree', 'no', 'nope', $Q1Disagree);
    logDebug("In array? ".in_array($text, $aryAgreeText));
    
    if(in_array($text, $aryAgreeText)){
        if($user->changeStageToQ1()){
            if($questionService->addQ1(ANSWER_YES)){
                respondQ1($user->chat_id);
                UserDao::save($user);
            }
        }
    }
    else if(in_array($text, $aryDisagreeText)){
        if($questionService->addQ1(ANSWER_NO)){
            //tell them not agree can't do anything
            respondWithMessage($user->chat_id, $GLOBALS['WORD']['SURVEY_Q1_NOT_AGREE']);
            respondWelcomeMessage($user->chat_id);   
        }
    }
    else{
        respondWithMessage($user->chat_id, $GLOBALS['WORD_TC']['INVALID_INPUT']);
        respondWelcomeMessage($user->chat_id);   
    }
}

function handleStageQ1($user, $questionService, $text){
    global $aryQ2;
    
    if (array_key_exists($text, $aryQ2)){
        if($user->changeStageToQ2()){
            if($questionService->addQ2($text)){
                respondQ2($user->chat_id, $questionService->question);
                
                UserDao::save($user);
            }
        }
    }
    else{
        respondWithMessage($user->chat_id, $GLOBALS['WORD_TC']['INVALID_INPUT']);
        respondQ1($user->chat_id);
    }
}

function handleStageQ2($user, $questionService, $text, $message_id){
    global $aryQ2;
    $q2 = $questionService->question->q2;
    $q2Key = array_keys($aryQ2);
    
    if(in_array($text, $aryQ2[$q2Key[$q2]])){
        if($user->changeStageToQ2Confirm()){
            if($questionService->addQ3(array_search($text, $aryQ2[$q2Key[$q2]]))){
                respondQ2Confirm($user->chat_id, $text);
                UserDao::save($user);
            }
        }
    }
    else{
        respondWithMessage($user->chat_id, $GLOBALS['WORD_TC']['INVALID_INPUT']);
        respondQ2($user->chat_id, $questionService->question);
    }
}

function handleStageQ2Confirm($user, $questionService, $text, $message_id){
    $aryAgreeText = array('confirm', 'ok', 'yes');
    $aryDisagreeText = array('no', 'nope');
    
    if(in_array($text, $aryAgreeText)){
        if($user->changeStageToQ3()){
            respondWithQuote($user->chat_id, $message_id, $GLOBALS['WORD']['SURVEY_THANKS']);
            respondPollingResult($user->chat_id, $questionService->question->q2);
            respondWithMessage($user->chat_id, $GLOBALS['WORD']['SURVEY_THANKS_REMIND']);
            

            $invitationService = new InvitationService($user);
            
            if(!$invitationService->hasGenerated() && $invitationService->canGenerate()){
                $invitation = $invitationService->getInvitation();
                respondWithMessage($user->chat_id, sprintf($GLOBALS['WORD']['INVITATION_MSG'], $invitation->quota));
                respondWithMessage($user->chat_id, formatInvitationMessage($invitation));
            }
            UserDao::save($user);
        }
    }
    else if(in_array($text, $aryDisagreeText)){
        if($user->changeStageToQ2()){
            respondQ2($user->chat_id, $questionService->question);
            UserDao::save($user);
        }
    }
    else{
        respondWithMessage($user->chat_id, $GLOBALS['WORD_TC']['INVALID_INPUT']);
        respondQ2Confirm($user->chat_id);
    }
}
function handleStageQ3($user, $questionService, $text){
    $ary = array('/vote');
    if(in_array($text, $ary)){
        if($user->changeStageToRestart()){
            respondQ2($user->chat_id, $questionService->question);
            UserDao::save($user);
        }
    }
    else if(handleShowResult($user, $questionService->question, $text)){
        
    }
    else if(handleInvite($user, $text)){
        
    }
    else{
        
        respondWithMessage($user->chat_id, "You have already voted.");
    }
}

function handleStageRestart($user, $questionService, $text, $message_id){
    handleStageQ2($user, $questionService, $text, $message_id);
}

function handleStageDeleted($user, $questionService, $text){
}

function handleShowResult($user, $question, $text){
    $aryResult = array('/result', 'show result');
    if(in_array($text, $aryResult)){
        if(null === $question->q2){
            respondWithMessage($user->chat_id, $GLOBALS['WORD']['SURVEY_NOT_START']);
        }
        else{
            respondPollingResult($user->chat_id, $question->q2);
        }
        return true;
    }
    return false;
}

function handleInvite($user , $text){
    $ret = false;
    if (strpos($text, "/invite new") === 0 && MemberType::canCreateMutli($user->member_type)) {
        $invitationService = new InvitationService($user);
        if($invitationService->canGenerate()){
            $invitationService->createInvitation($user->member_type);
            $invitation = $invitationService->getInvitation();
            respondWithMessage($user->chat_id, formatInvitationMessage($invitation));
        }
        else{
            respondWithMessage($user->chat_id, $GLOBALS['WORD']['INVITE_NO_PRIVILEGE']);
        }
        $ret = true;
    } else if (strpos($text, "/invite c") === 0 && MemberType::canCreateMutli($user->member_type)) {
        $invitationService = new InvitationService($user);
        if($invitationService->canGenerate()){
            $invitationService->createInvitation(MemberType::CELEBRITIES);
            $invitation = $invitationService->getInvitation();
            respondWithMessage($user->chat_id, formatInvitationMessage($invitation));
        }
        else{
            respondWithMessage($user->chat_id, $GLOBALS['WORD']['INVITE_NO_PRIVILEGE']);
        }
        $ret = true;
    } else if (strpos($text, "/invite") === 0) {
        $invitationService = new InvitationService($user);
        
        
        if($invitationService->hasGenerated()){
            $invitation = $invitationService->getInvitation();
            respondWithMessage($user->chat_id, $GLOBALS['WORD']['INVITE_ALREAY_GENERATED'].formatInvitationMessage($invitation));
        }
        else{
            if($invitationService->canGenerate()){
                $invitation = $invitationService->getInvitation();
                respondWithMessage($user->chat_id, sprintf($GLOBALS['WORD']['INVITATION_MSG'], $invitation->quota));
                respondWithMessage($user->chat_id, formatInvitationMessage($invitation));
            }
            else{
                respondWithMessage($user->chat_id, $GLOBALS['WORD']['INVITE_NO_PRIVILEGE']);
            }
        }
        $ret = true;
    } 
    return $ret;
}


?>