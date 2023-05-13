<?
error_reporting(E_ERROR | E_PARSE);
if (getenv('DEBUG'))
    error_reporting(E_ALL);

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

function colorize($s)
{
    $pattern[0]="^^00";	$replacement[0]='</font><font color="black">';
    $pattern[1]="^^11";	$replacement[1]='</font><font color="red">';
    $pattern[2]="^^22";	$replacement[2]='</font><font color="lime">';
    $pattern[3]="^^33";	$replacement[3]='</font><font color="yellow">';
    $pattern[4]="^^44";	$replacement[4]='</font><font color="blue">';
    $pattern[5]="^^55";	$replacement[5]='</font><font color="aqua">';
    $pattern[6]="^^66";	$replacement[6]='</font><font color="#FF00FF">';
    $pattern[7]="^^77";	$replacement[7]='</font><font color="white">';
    $pattern[8]="^^88";	$replacement[8]='</font><font color="orange">';
    $pattern[9]="^^99";	$replacement[9]='</font><font color="gray">';
    $pattern[10]="^0";	$replacement[10]='</font><font color="black">';
    $pattern[11]="^1";	$replacement[11]='</font><font color="red">';
    $pattern[12]="^2";	$replacement[12]='</font><font color="lime">';
    $pattern[13]="^3";	$replacement[13]='</font><font color="yellow">';
    $pattern[14]="^4";	$replacement[14]='</font><font color="blue">';
    $pattern[15]="^5";	$replacement[15]='</font><font color="aqua">';
    $pattern[16]="^6";	$replacement[16]='</font><font color="#FF00FF">';
    $pattern[17]="^7";	$replacement[17]='</font><font color="white">';
    $pattern[18]="^8";	$replacement[18]='</font><font color="orange">';
    $pattern[19]="^9";	$replacement[19]='</font><font color="gray">';
    $pattern[20]="ˇ!ˇ";	$replacement[20]='<span style="background-color: yellow; color: black">&nbsp;</span>';

    $s = str_replace($pattern, $replacement, htmlspecialchars($s));
    $i = strpos($s, '</font>');
    if ($i !== false)
	   {return '<font color="white">' . substr($s, 0, $i) . substr($s, $i+7, strlen($s)) . '</font>' . '</font>';}
    else
	   {return '<font color="white">' . $s . '</font>';}
}

//

foreach (getAllCod2Servers() as $server) {
?>
    <div>
        <p>Address: <? echo $server->address; ?>:<? echo $server->port; ?></p>

        <? echo colorize('^1Test ^2All ^3Colors ^4NL'); ?>
        <? print_r(getValueFromCod2ServerResponse($server->address, $server->port)); ?>
    </div>
<?
}
?>