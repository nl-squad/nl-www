<?php
error_reporting(getenv('DEBUG') ? E_ALL : E_ERROR | E_PARSE);

function getAllCod2Servers() {
    $servers = [];
    for ($i = 1; $address = getenv(sprintf('COD2_SERVER%d_ADDRESS', $i)); $i++) {
        $servers[] = [
            'address' => $address,
            'port' => getenv(sprintf('COD2_SERVER%d_PORT', $i)),
        ];
    }
    return $servers;
}

function getCurrentMicrotime() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function getValueFromCod2ServerResponse($srv_value, $srv_data) {
    if (($index = array_search($srv_value, $srv_data)) !== false) {
        return $srv_data[$index + 1];
    }
    return false;
}

function getServerStatus($address, $port) {
    $cacheDir = "./cache";
    $cacheFile = "$cacheDir/server_status_{$address}_{$port}.cache";

    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }

    if (file_exists($cacheFile) && filemtime($cacheFile) > time() - 10) {
        $serverStatus = unserialize(file_get_contents($cacheFile));
    } else {
        $serverStatus = [
            'timestamp' => time(),
            'status' => fetchServerStatus($address, $port)
        ];

        file_put_contents($cacheFile, serialize($serverStatus));
    }

    return $serverStatus;
}

function fetchServerStatus($address, $port) {

    $socket = stream_socket_client('udp://' . $address . ':' . $port, $errno, $errstr, 1);
    if ($socket === false) {
        return ['isOnline' => false];
    }

    socket_set_timeout($socket, 1);
    $time_begin = getCurrentMicrotime();
    fwrite($socket, "\xFF\xFF\xFF\xFFgetstatus\x00");
    $serverResponse = fread($socket, 2048);
    $time_end = getCurrentMicrotime();
    fclose($socket);

    if (!$serverResponse) {
        return ['isOnline' => false];
    }
    
    $responseParts = explode("\n", $serverResponse);
    if (!isset($responseParts[1])) {
        return ['isOnline' => false];
    }
    $gameInfo = explode("\\", $responseParts[1]);
    if (!is_array($gameInfo)) {
        return ['isOnline' => false];
    }
    $playersInfo = array_slice($responseParts, 2, -1);

    return [
        'isOnline' => true,
        'ping' => round(($time_end - $time_begin) * 1000),
        'hostname' => getValueFromCod2ServerResponse('sv_hostname', $gameInfo),
        'hasPassword' => getValueFromCod2ServerResponse('pswrd', $gameInfo),
        'mapName' => getValueFromCod2ServerResponse('mapname', $gameInfo),
        'nowPlayers' => count($playersInfo) - 1,
        'prvPlayers' => getValueFromCod2ServerResponse('sv_privateClients', $gameInfo),
        'maxPlayers' => getValueFromCod2ServerResponse('sv_maxclients', $gameInfo),
        'version' => getValueFromCod2ServerResponse('shortversion', $gameInfo)
    ];
}

function colorize($s) {
    $patterns = [
        "^0" => '</font><font color="black">',
        "^1" => '</font><font color="red">',
        "^2" => '</font><font color="lime">',
        "^3" => '</font><font color="yellow">',
        "^4" => '</font><font color="blue">',
        "^5" => '</font><font color="aqua">',
        "^6" => '</font><font color="#FF00FF">',
        "^7" => '</font><font color="white">',
        "^8" => '</font><font color="orange">',
        "^9" => '</font><font color="gray">',
    ];
    $s = str_replace(array_keys($patterns), $patterns, htmlspecialchars($s));
    if (($i = strpos($s, '</font>')) !== false) {
        return substr($s, 0, $i) . substr($s, $i + 7) . '</font>';
    }
    return $s;
}

echo colorize('^1Test ^2All ^5Colors ^4NL');

foreach (getAllCod2Servers() as $server) {
    ?>
    <div>
        <p>Address: <?= $server['address']; ?>:<?= $server['port']; ?></p>
        <?php print_r(getServerStatus($server['address'], $server['port'])); ?>
    </div>
    <?php
}