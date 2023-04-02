<?PHP

function html_entity_encode_all($s) {
    $out = '';
    for ($i = 0; isset($s[$i]); $i++) {
        // read UTF-8 bytes and decode to a Unicode codepoint value:
        $x = ord($s[$i]);
        if ($x < 0x80) {
            // single byte codepoints
            $codepoint = $x;
        } else {
            // multibyte codepoints
            if ($x >= 0xC2 && $x <= 0xDF) {
                $codepoint = $x & 0x1F;
                $length = 2;
            } else if ($x >= 0xE0 && $x <= 0xEF) {
                $codepoint = $x & 0x0F;
                $length = 3;
            } else if ($x >= 0xF0 && $x <= 0xF4) {
                $codepoint = $x & 0x07;
                $length = 4;
            } else {
                // invalid byte
                $codepoint = 0xFFFD;
                $length = 1;
            }
            // read continuation bytes of multibyte sequences:
            for ($j = 1; $j < $length; $j++, $i++) {
                if (!isset($s[$i + 1])) {
                    // invalid: string truncated in middle of multibyte sequence
                    $codepoint = 0xFFFD;
                    break;
                }
                $x = ord($s[$i + 1]);
                if (($x & 0xC0) != 0x80) {
                    // invalid: not a continuation byte
                    $codepoint = 0xFFFD;
                    break;
                }
                $codepoint = ($codepoint << 6) | ($x & 0x3F);
            }
            if (($codepoint > 0x10FFFF) ||
                ($length == 2 && $codepoint < 0x80) ||
                ($length == 3 && $codepoint < 0x800) ||
                ($length == 4 && $codepoint < 0x10000)) {
                // invalid: overlong encoding or out of range
                $codepoint = 0xFFFD;
            }
        }

        // have codepoint, now output:
        if (($codepoint >= 48 && $codepoint <= 57) ||
            ($codepoint >= 65 && $codepoint <= 90) ||
            ($codepoint >= 97 && $codepoint <= 122) ||
            ($codepoint == 32)) {
            // leave plain 0-9, A-Z, a-z, and space unencoded
            $out .= $s[$i];
        } else {
            // all others as numeric entities
            $out .= '&#' . $codepoint . ';';
        }
    }
    return $out;
}

function mtrxNotify($msg, $url, $description) {

    // --- These parameters need to be changed if you want YOUR bot to send to YOU in the chat
    $room_id = '';//YOUR ROOM ID (for example: !bEAGLEonJOwaLxcSXip:matrix.org)
    $token = '';
    // --- 

    if ($room_id == '' || $token == '')
        return;

    $curlInit = curl_init();
    curl_setopt($curlInit, CURLOPT_URL, "https://matrix.org/_matrix/client/r0/rooms/$room_id/send/m.room.message?access_token=$token");
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
        'msgtype' => "m.notice",
        'format' => 'org.matrix.custom.html',
        'body' => '',
        'formatted_body' => "<font color=\"#0000ff\">Web Directory: </font>$msg <a target=\"_blank\" href=\"$url\">$url</a><br>" . html_entity_encode_all($description));
    curl_setopt($curlInit, CURLOPT_POSTFIELDS, json_encode($dataToSend));
    try {
        $response = curl_exec($curlInit);
        error_log("mtrx: $response");
    } catch (Exception $e) {
        error_log("mtrx_err: $e");
    }
    curl_close($curlInit);
}

?>
