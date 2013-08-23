<?php
/* vim: set expandtab ts=4 sts=4 sw=4 tw=0: */

declare(ticks = 1);

$default_param = array(
    "class"          => "Memcache",  // Memcache,MemcacheEx,Redis,Mysql
    "connect_method" => "addServer", // connect,pconnect,addServer,nonPaddServer
    "server"         => "localhost", // 接続サーバー localhost:11211 等のポート番号をつけることもできる
    "port"           => "80",        // 接続ポート
    "close"          => "false",     // closeメソッドを呼ぶかどうか
    "pretty"         => "false",     // 結果をpretty print
    "dummy_loop"     => "0",         // 空のコネクション数
    "timeout"        => 60 * 60 * 24 // タイムアウト秒数
);

$option_params = (array) json_decode($_SERVER['argv'][1]);
$param = (object) array_merge($default_param, $option_params);
$res = array();
for($i = 1; $i <= $param->dummy_loop; $i++){
    $res[] = fsockopen($param->server, $param->port);
    usleep(10);
}
sleep($param->timeout);

