#!/usr/bin/php
<?php
/* vim: set expandtab ts=4 sts=4 sw=4 tw=0: */


class MysqlBench {
    private $scenarios;
    private $loop;
    private $pid;
    private $server;
    private $value;
    private $table;
    function __construct($server,$db,$user,$pass,$table,$loop,$value) {
        mysql_connect($server,$user,$pass);
        mysql_select_db($db);
        $this->pid = getmypid();
        $this->loop = $loop;
        $this->server = $server;
        $this->value = $value;
        $this->table = $table;

        $pid = $this->pid;

        $this->scenarios = array(
            //単純実行
            "normal set" => function () use ($pid, $loop, $value, $table) {
                $lastkey = "endtime".$pid;
                for($i = 1; $i<=$loop;$i++){
                   mysql_query("insert into $table values ('.$pid.$i.','$value')");
                }
                mysql_query("insert into $table values ('$lastkey', '".date("Y-m-d H:i:s").substr(microtime(),1,5)."')");
                return $lastkey;
            } ,
            
        );

    }

    private function bm($funcs){
        $hostname =gethostname();
        foreach($funcs as $fn_name => $fn){
            //echo $this->pid."-- run $fn_name -- ",$this->loop,"件のset\n";
            $start = microtime(true);
            $lastkey = $fn();
            $end = microtime(true);
            $time = $end-$start;
            $la = sys_getloadavg();
            echo "$hostname ,",$this->pid.", {$time}, $la[0], $la[1], $la[2]\n";
            $result = mysql_query("select value from {$this->table} where `key`='$lastkey'");
            if(!$result){
                echo  "$hostname ,".$this->pid .", 最後の値($lastkey)が記録できていないので終了します(select失敗)\n";
                exit (1);
            }
            else{
                $row = mysql_fetch_assoc($result);
                if(empty($row['value'])){
                    echo  "$hostname ,".$this->pid .", 最後の値($lastkey)が記録できていないので終了します\n";
                    exit (1);
                }

            }
            break; //とりあえず1個目で終了
        }
    }


    public function main(){
        $this->bm($this->scenarios);
    }
}


date_default_timezone_set('Asia/Tokyo');
ini_set('memory_limit', '2G');

switch($argc)
{
  case 1:
  case 2:
  case 3:
  case 4:
  case 5:
  case 6:
  case 7:
  case 8:
    echo "mysqlb.php <size> <server> <fork count> <key count> <db> <user> <pass> <table> [client_number]\n";
    exit;
  case 9:
    $num = null;
    break;
  default:
    $num = $argv[9]+1;
    break;
}
$size = $argv[1];
$server = $argv[2];
$pcount = $argv[3];
$loop = $argv[4];
$db = $argv[5];
$user = $argv[6];
$pass = $argv[7];
$table = $argv[8];

if(!is_numeric($size)){
    echo "第一引数は書き込むvalueのサイズを数値で指定してください\n";
    exit;
}
$value = str_repeat('a',$size);

if($num !== null){
    $servers = explode(',',$server);
    $server = $servers[$num % count($servers)];
}


$pstack = array();
for($i=1;$i<=$pcount;$i++){
    $pid = pcntl_fork();
    if( $pid == -1 ) {
        die( 'fork できません' );
    } else if ($pid) {
        // 親プロセスの場合
        $pstack[$pid] = true;
        if( count( $pstack ) >= $pcount ) {
            unset( $pstack[ pcntl_waitpid( -1, $status, WUNTRACED ) ] );
        }
    } else {
        $rb = new MysqlBench($server,$db,$user,$pass,$table,$loop,$value);
        $rb->main();
        //echo "Complete No$i\n";
        exit(); //処理が終わったらexitする。
    }
}
//先に処理が進んでしまうので待つ
while( count( $pstack ) > 0 ) {
    unset( $pstack[ pcntl_waitpid( -1, $status, WUNTRACED ) ] );
}





