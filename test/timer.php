<?php
// 测试结果 内存占用一开始2分多钟的样子增长1M, 增速逐渐放缓, 最终停留在10.9M,初始运行是7M
echo swoole_version();
swoole_timer_tick(1, function () {
    static $i = 0;
    $i++;
});

