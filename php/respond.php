<?php

function respondPollingResult($chat_id, $questionObj){
    foreach($GLOBALS['ANSWER_KEYBOARD']['Q4'] as $districtKey => $district){
        $resultArray = getDistrictResult($districtKey);
        $partyArray = $GLOBALS['ANSWER_KEYBOARD']['Q7'][$districtKey];
        respondOnePollingResult($chat_id, $resultArray, $district, $partyArray);
    }
    $resultArray = getSuperDistrictResult();
    $partyArray = $GLOBALS['ANSWER_KEYBOARD']['Q9'];
    respondOnePollingResult($chat_id, $resultArray, $GLOBALS['ANSWER_KEYBOARD']['PARTY_SUPER'], $partyArray);

}

function respondOnePollingResult($chat_id, $resultArray, $district, $partyArray){
    $total = array_sum($resultArray);

    array_push($partyArray, $GLOBALS['ANSWER_KEYBOARD']['PARTY_NOT_YET_DECIDE'], $GLOBALS['ANSWER_KEYBOARD']['PARTY_NO_SECOND_CHOICE']);
    
    $res = sprintf($GLOBALS['WORD']['SURVEY_RESULT'], $district, $total);
    
    arsort($resultArray);
    $row = 0;
    foreach($resultArray as $key => $val) {
        $res .= $partyArray[$key].": $val\n";
        $count = ($val/$total * 10);
        
        for($i=0; $i < $count; $i++){
            $res .= '✅';
        }
        $res .= ' *'.floor($count * 10)."%*\n";
        $row++;
        if($row == 10){
            //res .= $GLOBALS['WORD']['SURVEY_RESULT_MORE'];
            break;
        }
    }
    //$res .= $GLOBALS['WORD']['SURVEY_RESULT_LINK'];
    $res .= $GLOBALS['WORD']['SURVEY_RESULT_RESTART_INSTRUCTION'];
    respondWithMessage($chat_id, $res);
}

function respondWithMessage($chat_id, $message){
    apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => $message, 'parse_mode' => 'Markdown', 'reply_markup' => array('hide_keyboard' => true)));
    print "API: $message.<BR>\n";
}

function respondInvalidRequest($chat_id, $message_id){
    respondWithQuote($chat_id, $message_id, 'Cool.  But I do not understand.');
}

function respondWithQuote($chat_id, $message_id, $message){
    apiRequestJson("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => $message));
}

function respondWithKeyboard($chat_id, $message, $keyboardOptions){
    print "API Keyboard: $message.<BR>\n";
    print_r($keyboardOptions);
    apiRequestJson("sendMessage", 
                array('chat_id' => $chat_id, 
                "text" => $message, 
                'parse_mode' => 'Markdown', 
                'reply_markup' => array('keyboard' => $keyboardOptions, 
                                        'one_time_keyboard' => true, 
                                        'resize_keyboard' => true))
                      );
}

function respondWelcomeMessage($chat_id){
    respondWithMessage($chat_id, $GLOBALS['WORD']['WELCOME']);
    respondWithKeyboard($chat_id, $GLOBALS['WORD']['WELCOME_CHOOSE_LANGUAGE'], array_chunk($GLOBALS['ANSWER_KEYBOARD']['WELCOME_LANGUAGE'], 2));
}

function respondTermsAgree($chat_id){
    respondWithMessage($chat_id, $GLOBALS['WORD']['WELCOME_TERMS']);
    respondWithKeyboard($chat_id, $GLOBALS['WORD']['WELCOME_TERMS_AGREE'], array(array_values($GLOBALS['ANSWER_KEYBOARD']['Q1'])));
}

function respondNotAuthorized($chat_id){
    respondWithMessage($chat_id, $GLOBALS['WORD']['NOT_AUTHORIZED']);
}

function respondLinkQuotaUsedUp($chat_id){
    respondWithMessage($chat_id, $GLOBALS['WORD']['LINK_QUOTA_USED_UP']);
}

function respondQuotaLeft($chat_id, $invitation){
    if(null != $invitation){
        respondWithMessage($chat_id, sprintf($GLOBALS['WORD']['INVITATION_QUOTA'], $invitation->quota));
    }
    else{
        respondWithMessage($chat_id, $GLOBALS['WORD']['INVITATION_NO_LINK']);
    }
}

function respondQ2($chat_id){
    respondWithKeyboard($chat_id, $GLOBALS['WORD']['SURVEY_Q2'], array_chunk($GLOBALS['ANSWER_KEYBOARD']['Q2'], 1));
}


function respondQ3($chat_id){
    respondWithKeyboard($chat_id, $GLOBALS['WORD']['SURVEY_Q3'], array_chunk($GLOBALS['ANSWER_KEYBOARD']['Q3'], 3));
}


function respondQ4($chat_id){
    respondWithKeyboard($chat_id, $GLOBALS['WORD']['SURVEY_Q4'], array_chunk($GLOBALS['ANSWER_KEYBOARD']['Q4'], 3));
}

function respondQ5($chat_id, $questionObj){
    //which party?
    $district = $GLOBALS['ANSWER_KEYBOARD']['Q4'][$questionObj->q4];
    
    $option = $GLOBALS['ANSWER_KEYBOARD']['Q5'][$questionObj->q4];
    shuffle($option);
    array_push($option, $GLOBALS['ANSWER_KEYBOARD']['PARTY_NOT_YET_DECIDE']);
    respondWithKeyboard($chat_id, sprintf($GLOBALS['WORD']['SURVEY_Q5'], $district), array_chunk($option, 1));
}

function respondQ3Confirm($chat_id, $choice){
    respondWithKeyboard($chat_id, sprintf($GLOBALS['WORD']['SURVEY_Q2_CONFIRM'], $choice), array($GLOBALS['ANSWER_KEYBOARD']['Q2_CONFIRM']));
}

function respondQ6($chat_id, $questionObj){
    $name = $GLOBALS['ANSWER_KEYBOARD']['Q5'][$questionObj->q4][$questionObj->q5];

    $question = sprintf($GLOBALS['WORD']['SURVEY_Q6'], $name);
    $keyboard = $GLOBALS['ANSWER_KEYBOARD']['Q6'];

    respondWithKeyboard($chat_id, $question, array_chunk($keyboard, 2));
}

function respondQ7($chat_id, $questionObj){
    $question = $GLOBALS['WORD']['SURVEY_Q7'];
    $keyboard = $GLOBALS['ANSWER_KEYBOARD']['Q7'][$questionObj->q4];

    shuffle($keyboard);
    array_push($keyboard, $GLOBALS['ANSWER_KEYBOARD']['PARTY_NOT_YET_DECIDE'], $GLOBALS['ANSWER_KEYBOARD']['PARTY_NO_SECOND_CHOICE']);
    respondWithKeyboard($chat_id, $question, array_chunk($keyboard, 1));
}


function respondQ8($chat_id, $questionObj){
    $districtKey = $questionObj->q4;
    $partyOption = $GLOBALS['ANSWER_KEYBOARD']['Q5'][$districtKey];
    array_push($partyOption, $GLOBALS['ANSWER_KEYBOARD']['PARTY_NOT_YET_DECIDE'], $GLOBALS['ANSWER_KEYBOARD']['PARTY_NO_SECOND_CHOICE']);

    $q2answer = $GLOBALS['ANSWER_KEYBOARD']['Q2'][$questionObj->q2];
    $q3answer = $partyOption[$questionObj->q5];
    $q4answer = $GLOBALS['ANSWER_KEYBOARD']['Q6'][$questionObj->q6];
    $q5answer = $partyOption[$questionObj->q7];

    $question = sprintf($GLOBALS['WORD']['SURVEY_Q8'], $q2answer, $q3answer, $q4answer, $q5answer);
    $keyboard = $GLOBALS['ANSWER_KEYBOARD']['Q8'];

    respondWithKeyboard($chat_id, $question, array_chunk($keyboard, 2));
}


function respondQ9($chat_id, $questionObj){
    $question = $GLOBALS['WORD']['SURVEY_Q9'];
    $partyOption = $GLOBALS['ANSWER_KEYBOARD']['Q9'];

    shuffle($partyOption);
    array_push($partyOption, $GLOBALS['ANSWER_KEYBOARD']['PARTY_NOT_YET_DECIDE']);

    respondWithKeyboard($chat_id, $question, array_chunk($partyOption, 1));
}


function respondQ10($chat_id, $questionObj){
    $name = $GLOBALS['ANSWER_KEYBOARD']['Q9'][$questionObj->q9];

    $question = sprintf($GLOBALS['WORD']['SURVEY_Q10'], $name);
    $keyboard = $GLOBALS['ANSWER_KEYBOARD']['Q10'];

    respondWithKeyboard($chat_id, $question, array_chunk($keyboard, 2));
}


function respondQ11($chat_id, $questionObj){
    $question = $GLOBALS['WORD']['SURVEY_Q11'];
    $partyOption = $GLOBALS['ANSWER_KEYBOARD']['Q11'];

    shuffle($partyOption);
    array_push($partyOption, $GLOBALS['ANSWER_KEYBOARD']['PARTY_NOT_YET_DECIDE'], $GLOBALS['ANSWER_KEYBOARD']['PARTY_NO_SECOND_CHOICE']);

    respondWithKeyboard($chat_id, $question, array_chunk($partyOption, 1));
}


function respondQ12($chat_id, $questionObj){
    $partyOption = $GLOBALS['ANSWER_KEYBOARD']['Q9'];
    array_push($partyOption, $GLOBALS['ANSWER_KEYBOARD']['PARTY_NOT_YET_DECIDE'], $GLOBALS['ANSWER_KEYBOARD']['PARTY_NO_SECOND_CHOICE']);
    
    $q7answer = $partyOption[$questionObj->q9];
    $q8answer = $GLOBALS['ANSWER_KEYBOARD']['Q10'][$questionObj->q10];
    $q9answer = $partyOption[$questionObj->q11];

    $question = sprintf($GLOBALS['WORD']['SURVEY_Q12'], $q7answer, $q8answer, $q9answer);
    $keyboard = $GLOBALS['ANSWER_KEYBOARD']['Q12'];

    respondWithKeyboard($chat_id, $question, array_chunk($keyboard, 2));
}

function respondQ13($chat_id, $questionObj){
    $question = $GLOBALS['WORD']['SURVEY_Q13'];
    $keyboard = $GLOBALS['ANSWER_KEYBOARD']['Q13'];

    respondWithKeyboard($chat_id, $question, array_chunk($keyboard, 3));
}

function respondQ14($chat_id, $questionObj){
    $question = $GLOBALS['WORD']['SURVEY_Q14'];
    $keyboard = $GLOBALS['ANSWER_KEYBOARD']['Q14'];

    respondWithKeyboard($chat_id, $question, array_chunk($keyboard, 1));
}


function respondQ15($chat_id, $questionObj){
    $question = $GLOBALS['WORD']['SURVEY_Q15'];
    $keyboard = $GLOBALS['ANSWER_KEYBOARD']['Q15'];

    respondWithKeyboard($chat_id, $question, array_chunk($keyboard, 1));
}

function respondSurveyEnded($chat_id){
    respondWithMessage($chat_id, $GLOBALS['WORD']['SURVEY_ENDED']);
}
?>