<?php
/* vim: set expandtab ts=4 sts=4 sw=4 tw=0: */

$default_param = array(
    "class"          => "Memcache",  // Memcache,MemcacheEx,Redis,Mysql
    "connect_method" => "addServer", // connect,pconnect,addServer,nonPaddServer
    "server"         => "localhost", // 接続サーバー localhost:11211 等のポート番号をつけることもできる
    "loop"           => 100,         // 繰り返し回数
    "size"           => 8,           // 書き込みサイズ(byte)
    "close"          => "false",     // closeメソッドを呼ぶかどうか
    "pretty"         => "false",     // 結果をpretty print
);
$param = (object) array_merge($default_param, $_GET);

$cache = new $param->class();
if($cache->{$param->connect_method}($param->server) === false){
        header('HTTP', true, 500); exit;
}


$hostname = gethostname();
$pid = getmypid();
$key = uniqid($hostname . $pid);
$value = str_repeat('a',$param->size);

$start = microtime(true);
for($i = 1; $i<=$param->loop; $i++){
    if($cache->set("{$key}-{$i}",$value) === false){
        header('HTTP', true, 501); exit;
    }
    usleep(10);
}
for($i = 1; $i<=$param->loop; $i++){
    if($cache->get("{$key}-{$i}") !== $value){
        //echo  "$hostname ,".$pid .", setした値が記録できていないので終了します\n";
        header('HTTP', true, 502); exit;
    }
    usleep(10);
}
if(strcasecmp($param->close,"true")===0){
    if($cache->close() === false){
        header('HTTP', true, 503); exit;
    }
}
$end = microtime(true);

$result = array(
    "hostname"  => $hostname,
    "pid"       => sprintf('%011d', $pid),
    "time"      => sprintf('%.17f', $end-$start),
    "la"        => implode(",", array_map('to_f', sys_getloadavg())),
);

echo json_encode( 
        array("param"=>(array)$param, "result"=>$result),
        (strcasecmp($param->pretty,"true")===0)? JSON_PRETTY_PRINT:0
     ), 
     PHP_EOL;

function to_f($val)
{
    return sprintf('%.2f', $val);
}

class MemcacheEx extends Memcache {
    public function nonPaddServer($server){
        $s = explode(":",$server);
        $port = array_key_exists(2,$s)? $s[2]: 11211; 
        return $this->addServer($server,$port,FALSE);
    }
}


class Mysql {
    private $db = "test";
    private $user = "bmg";
    private $pass = "hoge";
    private $table = "test_innodb256";
    
    public function connect($server){
        if( (mysql_connect($server,$this->user,$this->pass) === false) ||
            (mysql_select_db($this->db) === false)
          ){
            return false;
        }
    }
    public function pconnect(){
        if( (mysql_pconnect($server,$this->user,$this->pass) === false) ||
            (mysql_select_db($this->db) === false)
          ){
            return false;
        }
    }
    public function close(){
        return mysql_close();
    }
    public function set($key,$value){
        return mysql_query("insert into {$this->table} values ('$key','$value')");
    }
    public function get($key){
        $result = mysql_query("select value from {$this->table} where `key`='$key'");
        if(!$result){
            return $result;
        }
        else{
            $row = (object) mysql_fetch_assoc($result);
            return property_exists($row,'value')? $row->value : NULL;
        }
    }
}

