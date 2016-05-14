<?php

function sendPhoto($chatID, $textMessage, $token, $notification = true) {
    $url = "https://api.telegram.org/bot" . $token . "/sendPhoto?chat_id=" . $chatID;
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type:multipart/form-data"
    ));
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, array(
        "photo"     => "@/votsonar/". $imageName, 
    )); 
    curl_setopt($ch, CURLOPT_INFILESIZE, filesize("image/". $imageName));
    $output = curl_exec($ch);
}

function sendMessage($chatID, $textMessage, $token, $notification = true) {

    $url = "https://api.telegram.org/bot" . $token . "/sendMessage?chat_id=" . $chatID;
    $url = $url . "&text=" . urlencode($textMessage);
    if (!$notification) {
        $url = $url . "&disable_notification=true";
    }

    $ch = curl_init();
    $optArray = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true
    );
    curl_setopt_array($ch, $optArray);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function bind_vars($format_string, $values) {
    extract($values);
    $result = $format_string;
    eval('$result = "' . $format_string . '";');
    return $result;
}

function midnight() {
        $t=new DateTime();
        $h=$t->format("H");
        return ($h < "09");
}

?>

