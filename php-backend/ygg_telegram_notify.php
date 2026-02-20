<?PHP

function tlgNotify($msg, $url, $description) {

    // --- These parameters need to be changed if you want YOUR bot to write to YOU in the chat
    $bot_url = 'https://api.telegram.org/botXXXXXXXXX:ABCDEFGH_iJklmnopQrstuvWxYz12345678/sendMessage';//YOUR BOT
    $chat_id = '-1';//YOUR CHAT (for example: 123456789)
    // --- 

    if ($chat_id == '-1')
        return;

    $dataToSend = json_encode(array (
        'chat_id' => "$chat_id",
        'disable_web_page_preview' => '1',
        //"parse_mode" => "Markdown",
        "text" => "$msg\r\n$url\r\n$description\r\n"
    ));
    
    $curlInit = curl_init();
    curl_setopt_array($curlInit, [
        CURLOPT_URL => "$bot_url",
        CURLOPT_CONNECTTIMEOUT => 10,                      
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true ,
        // <--- through tor
        //CURLOPT_PROXYTYPE => 7, //CURLPROXY_SOCKS5_HOSTNAME
        //CURLOPT_PROXY => '192.168.1.2:9051',
        // ---> through tor
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Content-Length: ' . strlen($dataToSend)],
        CURLOPT_POSTFIELDS=> $dataToSend,
    ]);
    
    try {
        $response = curl_exec($curlInit);
    } catch (Exception $e) {
        //
    }
    curl_close($curlInit);
}

?>
