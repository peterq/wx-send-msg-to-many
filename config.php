<?php

return [
    'app-name' => 'wx-send-many',
    'rpc' => [
        'app-name' => 'wx-send-many',
        'ws-server' => [
            // 'worker_num' => swoole_cpu_num() * 4,
            'worker_num' => 4,
        ]
    ],
    'accounts' => [
        'peterq' => 'leo520',
        'leo'    => 'lovepeterq',
        'test'   => 'peterq011425'
    ],
    'wx' =>
        function ($id = 'default') {
            $path = __DIR__ . '/temp/' . $id . '/';
            is_dir($path) or mkdir($path, 0777, true);
            return [
                'path' => $path,
                'cookie_file' => $path . 'cookie.json',
                'session_key' => 'session_' . $id,

                /*
                 * 下载配置项
                 */
                'download' => [
                    'image' => false,
                    'voice' => false,
                    'video' => false,
                    'emoticon' => false,
                    'file' => false,
                    'emoticon_path' => $path . 'emoticons', // 表情库路径（PS：表情库为过滤后不重复的表情文件夹）
                ],
                /*
                 * 输出配置项
                 */
                'console' => [
                    'output' => false, // 是否输出
                    'message' => false, // 是否输出接收消息 （若上面为 false 此处无效）
                ],
                /*
                 * 日志配置项
                 */
                'log' => [
                    'level' => 'error',
                    'permission' => 0777,
                    'system' => $path . 'log', // 系统报错日志
                    'message' => $path . 'log', // 消息日志
                ],
                /*
                 * 缓存配置项
                 */
                'cache' => [
                    'default' => 'file', // 缓存设置 （支持 redis 或 file）
                    'stores' => [
                        'file' => [
                            'driver' => 'file',
                            'path' => $path . 'cache',
                        ],
                        'redis' => [
                            'driver' => 'redis',
                            'connection' => 'default',
                        ],
                    ],
                ],
            ];
        }
];
