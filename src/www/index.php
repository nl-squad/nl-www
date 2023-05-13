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
?>