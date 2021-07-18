<?PHP

function base32_encode($data, $padRight = false) {
    $BITS_5_RIGHT = 31;
    $CHARS = 'abcdefghijklmnopqrstuvwxyz234567';

	$dataSize = strlen($data);
	$res = '';
	$remainder = 0;
	$remainderSize = 0;
		
	for ($i = 0; $i < $dataSize; $i++) {
		$b = ord($data[$i]);
		$remainder = ($remainder << 8) | $b;
		$remainderSize += 8;
		while ($remainderSize > 4) {
			$remainderSize -= 5;
			$c = $remainder & ($BITS_5_RIGHT << $remainderSize);
			$c >>= $remainderSize;
			$res .= $CHARS[$c];
		}
	}
	if ($remainderSize > 0) {
		// remainderSize < 5:
		$remainder <<= (5 - $remainderSize);
		$c = $remainder & $BITS_5_RIGHT;
		$res .= $CHARS[$c];
	}
	if ($padRight) {
		$padSize = (8 - ceil(($dataSize % 5) * 8 / 5)) % 8;
		$res .= str_repeat('=', $padSize);
	}
	
	return $res;
}

?>
