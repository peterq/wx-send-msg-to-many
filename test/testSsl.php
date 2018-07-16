<?php

function get($domain, $path = '/', $port = 443) {
    $stream_context = stream_context_create([ 'ssl1' => [
        // 'SNI_enabled' => true,
        // 'SNI_server_name' => $domain,
        'verify_peer_name' => false,
        'verify_peer' => false,
    ]]);

    $fp = stream_socket_client("tcp://$domain:$port",
        $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $stream_context);
    stream_set_blocking($fp, 0);
    $id = swoole_event_add($fp, function ($stream) use (&$id) {
        $data = fread($stream, 8192);
        if (empty($data)) {
            swoole_event_del($id);
        }
        echo $data;
        echo PHP_EOL;
        sleep(1);
    });
    fwrite($fp, "GET $path HTTP/1.0\r\nHost: $domain\r\nAccept: */*\r\n\r\n");
}

get('api.jiebei.local', '/sleep.php', 80);
swoole_timer_tick(1000, function () {var_dump('hello');});
