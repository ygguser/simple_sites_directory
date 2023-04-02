<?PHP

function tlgNotify($msg, $url, $description) {

    // --- These parameters need to be changed if you want YOUR bot to write to YOU in the chat
    $bot_url = 'https://api.telegram.org/botXXXXXXXXX:ABCDEFGH_iJklmnopQrstuvWxYz12345678/sendMessage';//YOUR BOT
    $chat_id = '-1';//YOUR CHAT (for example: 123456789)
    // --- 

    if ($chat_id == '-1')
        return;

    $curlInit = curl_init();
    curl_setopt($curlInit, CURLOPT_URL, "$bot_url");
    curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curlInit, CURLOPT_TIMEOUT, 20);
    curl_setopt($curlInit, CURLOPT_HEADER, false);
    curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curlInit, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curlInit, CURLOPT_FOLLOWLOCATION, true);
    // --- through tor
    //curl_setopt($curlInit, CURLOPT_PROXYTYPE, 7);//CURLPROXY_SOCKS5_HOSTNAME
    //curl_setopt($curlInit, CURLOPT_PROXY, '192.168.1.2:9051');
    // ---
    curl_setopt($curlInit, CURLOPT_POST, true);
    $dataToSend = array (
        'chat_id' => "$chat_id",
        'disable_web_page_preview' => '1',
        //"parse_mode" => "Markdown",
        "text" => "$msg\r\n$url\r\n$description\r\n"
    );
    curl_setopt($curlInit, CURLOPT_POSTFIELDS, $dataToSend);
    try {
        $response = curl_exec($curlInit);
    } catch (Exception $e) {
        //
    }
    curl_close($curlInit);
}

?>
