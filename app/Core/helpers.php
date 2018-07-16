<?php

if (!function_exists('str_random')) {
    function str_random($length = 16)
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }
}

function config ($key = null, $default = null)
{
    static $conf = [];

    // 默认获取所有配置
    if (is_null($key)) return $conf;
    // 添加配置
    if (is_array($key)) {
        $conf = array_merge_recursive($conf, $key);
        return;
    }

    // 获取一项配置
    $array = $conf;
    if (strpos($key, '.') === false) {
        return $array[$key] ?? $default;
    }
    foreach (explode('.', $key) as $segment) {
        if (is_array($array) && key_exists($segment, $array)) {
            $array = $array[$segment];
        } else {
            return $default;
        }
    }
    return $array;
}


function readStreamWithCoroutine($stream)
{
    stream_set_blocking($stream, 0);
    $result = '';
    $co_id = swoole_coroutine::getuid();
    swoole_event_add($stream, function ($stream) use (&$result, $co_id) {
        $time = microtime(1);
        $data = fread($stream, 1024);
        dump(microtime(1) - $time);
        if (empty($data) && feof($stream)) {
            swoole_event_del($stream);
            swoole_coroutine::resume($co_id);
            return;
        }
        $result .= $data;
    }, null, SWOOLE_EVENT_READ);
    swoole_coroutine::suspend();
    dump('data finish');
    return $result;
}

function println($msg)
{
    echo  '---------' . posix_getpid() . '---------' . PHP_EOL . $msg . PHP_EOL;
}

