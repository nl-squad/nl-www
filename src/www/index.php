<?
function getAllCod2Servers() {
    $servers = array();
    $i = 1;

    while ($address = getenv('COD2_SERVER' . $i . '_ADDRESS')) {
        $port = getenv('COD2_SERVER' . $i . '_PORT');

        $server = new stdClass();
        $server->address = $address;
        $server->port = $port;

        $servers[] = $server;
        $i++;
    }

    return $servers;
}

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function getValueFromCod2ServerResponse($srv_value, $srv_data)
{
    $srv_value = array_search ($srv_value, $srv_data);
    if ($srv_value === false)
    {
       return false;
    }
    else
    {
       $srv_value = $srv_data[$srv_value+1];
       return $srv_value;
    }
}

function getServerStatus($address, $port) {

    $socket = fsockopen('udp://' . $address, $port, $errno, $errstr, 30);
    $srv['isOnline'] = false;

    if ($socket === false)
        return $srv;

    socket_set_timeout($socket, 1);
    $time_begin = microtime_float();

    fwrite($socket, "\xFF\xFF\xFF\xFFgetstatus\x00");
    $serverResponse = fread($socket, 2048);

    $time_end = microtime_float();
    fclose($socket);

    if (!$serverResponse)
        return $srv;

    $c_info = explode("\n", $serverResponse);
    $g_info = explode("\\", $c_info[1]);

    $index_old = 0;
    $index_new = 0;
    foreach ($c_info as $value)
    {
        if ($index_old >= 2)
        {
            $p_info[$index_new] = $value;
            $index_new++;
        }
        $index_old++;
    }

    $srv['ping'] = round($time_end - $time_begin * 1000);
    $srv['isOnline'] = true;
    $srv['hostname'] = getValueFromCod2ServerResponse('sv_hostname', $g_info);
    $srv['hasPassword'] = getValueFromCod2ServerResponse('pswrd', $g_info);
    $srv['mapName'] = getValueFromCod2ServerResponse('mapname', $g_info);
    $srv['nowPlayers'] = (count($p_info)) - 1;
    $srv['prvPlayers'] = getValueFromCod2ServerResponse('sv_privateClients', $g_info);
    $srv['maxPlayers'] = getValueFromCod2ServerResponse('sv_maxclients', $g_info) - $srv['prvPlayers'];
    $srv['version'] = getValueFromCod2ServerResponse('shortversion', $g_info);
    return $srv;
}

//

foreach (getAllCod2Servers() as $server) {
?>
    <div>
        <p>Address: <? echo $server->address; ?>:<? echo $server->port; ?></p>

        <? print_r(getValueFromCod2ServerResponse($server->address, $server->port)); ?>
    </div>
<?
}
?>