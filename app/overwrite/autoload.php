<?php

$bool = spl_autoload_register(function ($cls) {
    $map = [
        'Hanson\Vbot\Support\Http' => __DIR__  . '/Http.php',
        'Hanson\Vbot\Message\Traits\SendAble' => __DIR__  . '/SendAble.php',
        'Hanson\Vbot\Message\Traits\Multimedia' => __DIR__  . '/Multimedia.php',
    ];

    if (isset($map[$cls])) {
        echo $cls . ' loaded' . PHP_EOL;
        include $map[$cls];
        return true;
    }

}, true, true);

