<?php
/* vim: set expandtab ts=4 sts=4 sw=4 tw=0: */

declare(ticks = 1);

$default_param = array(
    "class"          => "Memcache",  // Memcache,MemcacheEx,Redis,Mysql
    "connect_method" => "addServer", // connect,pconnect,addServer,nonPaddServer
    "server"         => "localhost", // 接続サーバー localhost:11211 等のポート番号をつけることもできる
    "close"          => "false",     // closeメソッドを呼ぶかどうか
    "pretty"         => "false",     // 結果をpretty print
    "dummy_loop"     => "0",          // 空のコネクション数
);
$option_params = (array) json_decode($_SERVER['argv'][1]);
$param = (object) array_merge($default_param, $option_params);

$cache = new Cache($param);
$cache->connect();

pcntl_signal(SIGTERM, array($cache, 'close'));
pcntl_signal(SIGINT,  array($cache, 'close'));

while(TRUE);

class Cache
{
    private $param;
    private $caches = array();

    function __construct($param)
    {
        $this->param = $param;
        for ($i = 0; $i < intval($this->param->dummy_loop); $i++)
        {
            $this->caches[] = new $this->param->class();
        }
    }

    function connect()
    {
        $results = array();
        foreach ($this->caches as $key => $cache)
        {
            $results[] = $cache->{$this->param->connect_method}($this->param->server);
        }
        if (in_array(FALSE, $results, TRUE)) {
            die("Connect Error\n");
        }
        echo "Connect Success\n";
    }

    function close()
    {
        $results = array();
        foreach ($this->caches as $cache)
        {
            $results[] = $cache->close();
        }
        if (in_array(FALSE, $results, TRUE)) {
            die("Close Error\n");
        }
        echo "Close Success\n";
        exit;
    }
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

