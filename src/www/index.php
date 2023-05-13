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

    if (file_exists($cacheFile) && filemtime($cacheFile) > time() - 1) {
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

function compare_scores($a, $b) {
    return $b['score'] - $a['score'];
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

    $parsedPlayers = array();
    foreach($playersInfo as $player) {
        $parts = explode(" ", $player);
        
        $name = trim(implode(' ', array_slice($parts, 2)), '\"');
        $parsedPlayers[] = array(
            'score' => (int)$parts[0],
            'rank' => $parts[1],
            'name' => $name
        );
    }
    usort($parsedPlayers, 'compare_scores');

    return [
        'isOnline' => true,
        'ping' => round(($time_end - $time_begin) * 1000),
        'hostname' => getValueFromCod2ServerResponse('sv_hostname', $gameInfo),
        'hasPassword' => getValueFromCod2ServerResponse('pswrd', $gameInfo),
        'mapName' => getValueFromCod2ServerResponse('mapname', $gameInfo),
        'nowPlayers' => count($playersInfo),
        'prvPlayers' => getValueFromCod2ServerResponse('sv_privateClients', $gameInfo),
        'maxPlayers' => getValueFromCod2ServerResponse('sv_maxclients', $gameInfo),
        'version' => getValueFromCod2ServerResponse('shortversion', $gameInfo),
        'players' => $parsedPlayers,
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
        "^9" => '</font><font color="#e3e3e3">',
    ];
    $s = str_replace(array_keys($patterns), $patterns, htmlspecialchars($s));
    if (($i = strpos($s, '</font>')) !== false) {
        return substr($s, 0, $i) . substr($s, $i + 7) . '</font>';
    }
    return $s;
}

function monotone($s) {
    $patterns = [
        "^^00" => '',
        "^^11" => '',
        "^^22" => '',
        "^^33" => '',
        "^^44" => '',
        "^^55" => '',
        "^^66" => '',
        "^^77" => '',
        "^^88" => '',
        "^^99" => '',
        "^^00" => '',
        "^1" => '',
        "^2" => '',
        "^3" => '',
        "^4" => '',
        "^5" => '',
        "^6" => '',
        "^7" => '',
        "^8" => '',
        "^9" => '',
    ];
    return str_replace(array_keys($patterns), $patterns, htmlspecialchars($s));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>No Limits</title>
    <link rel="icon" type="image/x-icon" href="nolimitsicon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter&family=Poppins:wght@900&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-image: url("nolimitsbackground.png");
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: 'Inter', sans-serif;
            color: white;
        }
        .logo {
            max-width: 300px;
            margin: 20px 0 60px 0;
        }
        .container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 14px;
        }
        .cod2-widget {
            width: 400px;
            height: 300px;
            background-color: rgb(32, 34, 37);
            color: white;
            border-radius: 5px;
            margin: 20px 0 0 0;
        }
        .header {
            background-color: rgb(88, 101, 242);
            border-radius: 5px 5px 0 0;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-title {
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
        }
        .content {
            padding: 20px;
            overflow-y: scroll;
            height: 150px;
        }
        .map-name {
            font-size: 16px;
            margin-bottom: 12px;
        }
        .player-list {
            margin-left: 10px;
            color: rgb(138, 142, 148);
        }
        .player {
            line-height: 16px;
        }
        .footer {
            padding: 6px;
            display: flex;
            justify-content: flex-end;
            box-shadow: 0 -1px 18px rgba(0,0,0,.2), 0 -1px 0 rgba(0,0,0,.2);
        }
        .button {
            border: 1px solid #212325;
            background-color: hsla(0,0%,100%,.1);
            cursor: pointer;
            transition: opacity .25s ease-out;
            width: 120px;
            height: 28px;
            border-radius: 4px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: inset 0 1px 0 hsla(0,0%,100%,.04);
        }
        .button:hover {
            opacity: .6;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="nolimitslogo.png" class="logo">
        <iframe src="https://discord.com/widget?id=1090555635239751703&theme=dark" width="400" height="300" allowtransparency="true" frameborder="0" sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"></iframe>
<?
        foreach (getAllCod2Servers() as $server) {
            $status = getServerStatus($server['address'], $server['port'])['status'];

            if (!$status['isOnline'])
                continue;
?>
        <div class="cod2-widget">
            <div class="header">
                <div class="header-title">
                    <?= colorize($status['hostname']) ?>
                </div>
                <div class="header-status">
                    <b><?= $status['nowPlayers'] ?></b> Players Online
                </div>
            </div>
            <div class="content">
                <div class="map-name">
                    Current map is <b><?= $status['mapName'] ?></b>
                </div>
                <div class="player-list">
<?
                foreach ($status['players'] as $player) {
?>
                    <div class="player">
                        <img src="https://avatars.dicebear.com/api/open-peeps/<?= preg_replace("/[^a-zA-Z0-9]/", "", monotone($player['name'])) ?>.svg" width="16" height="16" />
                        <?= monotone($player['name']) ?>
                    </div>
<?
                }
?>
                </div>
            </div>
            <div class="footer">
                <div class="button">Join server</div>
            </div>
        </div>
<?
        }
?>
    </div>
</body>
</html>