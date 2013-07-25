<?php
/* vim: set expandtab ts=4 sts=4 sw=4 tw=0: */

$default_param = array(
    "class"          => "Memcache",  // Memcache or Redis
    "connect_method" => "connect",   // Memcache:connect,pconnect,addServer  Redis:connect,pconnect
    "server"         => "localhost", // 接続サーバー localhost:11211 等のポート番号をつけることもできる
    "loop"           => 1,           // 繰り返し回数
    "size"           => 8,           // 書き込みサイズ(byte)
    "close"          => "false",     // closeメソッドを呼ぶかどうか
    "pretty"         => "false",     // 結果をpretty print
);
$param = (object) array_merge($default_param, $_GET);

$cache = new $param->class();
$cache->{$param->connect_method}($param->server);

$hostname = gethostname();
$pid = getmypid();
$value = str_repeat('a',$param->size);

$start = microtime(true);
for($i = 1; $i<=$param->loop; $i++){
    $cache->set("hoge".$pid.$i,$value);
}
for($i = 1; $i<=$param->loop; $i++){
    if($cache->get("hoge".$pid.$i) !== $value){
        echo  "$hostname ,".$pid .", setした値が記録できていないので終了します\n";
        exit;
    }
}
if(strcasecmp($param->close,"true")===0){
    $cache->close();
}
$end = microtime(true);

$result = array(
    "hostname"  => $hostname,
    "pid"       => $pid,
    "time"      => $end-$start,
    "la"        => implode(",",sys_getloadavg()),
);

echo json_encode( 
        array("param"=>(array)$param, "result"=>$result),
        (strcasecmp($param->pretty,"true")===0)? JSON_PRETTY_PRINT:0
     ), 
     PHP_EOL;



