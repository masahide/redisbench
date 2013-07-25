<?php
/* vim: set expandtab ts=4 sts=4 sw=4 tw=0: */

$default_param = array(
    "class"          => "Mysql",  // Memcache,Redis,Mysql
    "connect_method" => "connect",   // connect,pconnect,addServer
    "server"         => "localhost", // 接続サーバー localhost:11211 等のポート番号をつけることもできる
    "loop"           => 100,         // 繰り返し回数
    "size"           => 8,           // 書き込みサイズ(byte)
    "close"          => "false",     // closeメソッドを呼ぶかどうか
    "pretty"         => "false",     // 結果をpretty print
);
$param = (object) array_merge($default_param, $_GET);

$cache = new $param->class();
$cache->{$param->connect_method}($param->server);

$hostname = gethostname();
$pid = getmypid();
$key = uniqid($pid);
$value = str_repeat('a',$param->size);

$start = microtime(true);
for($i = 1; $i<=$param->loop; $i++){
    $cache->set("{$key}-{$i}",$value);
}
for($i = 1; $i<=$param->loop; $i++){
    if($cache->get("{$key}-{$i}") !== $value){
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



class Mysql {
    private $db = "test";
    private $user = "bmg";
    private $pass = "hoge";
    private $table = "test_innodb256";
    
    public function connect($server){
        mysql_connect($server,$this->user,$this->pass);
        mysql_select_db($this->db);
    }
    public function pconnect(){
        mysql_pconnect($server,$this->user,$this->pass);
        mysql_select_db($this->db);
    }
    public function close(){
        mysql_close();
    }
    public function set($key,$value){
        mysql_query("insert into {$this->table} values ('$key','$value')");
    }
    public function get($key){
        $result = mysql_query("select value from {$this->table} where `key`='$key'");
        if(!$result){
            return NULL;
        }
        else{
            $row = (object) mysql_fetch_assoc($result);
            return property_exists($row,'value')? $row->value : NULL;
        }
    }
}

