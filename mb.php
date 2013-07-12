#!/usr/bin/php
<?php
/* vim: set expandtab ts=4 sts=4 sw=4 tw=0: */


class MemcacheBench {
    private $memcache01;
    private $scenarios;
    private $loop;
    private $pid;
    private $server;
    function __construct($server,$pcount,$loop) {
        $this->memcache01 = new Memcache();
        $this->memcache01->pconnect($server);
        $this->pid = getmypid();
        $this->loop = $loop;
        $this->server = $server;

        $memcache = $this->memcache01;
        $pid = $this->pid;

        $this->scenarios = array(
            //単純実行
            "normal set" => function () use (&$memcache, $pid, $loop) {
                $lastkey = "endtime".$pid;
                for($i = 1; $i<=$loop;$i++){
                    $memcache->set("hoge".$pid.$i,"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaabbb");
                }
                $memcache->set($lastkey,date("Y-m-d H:i:s").substr(microtime(),1,5));
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
            $lastvalue = $this->memcache01->get($lastkey);
            //echo $this->pid . " get lastkey.... memcache-cli -h $this->server get $lastkey -> ".$lastvalue. "\n";
            if(empty($lastvalue)){
                echo  "$hostname ,".$this->pid .", 最後の値($lastkey)が記録できていないので終了します\n";
                exit (1);
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
    echo "rb.php <server> <fork count> <key count>\n";
    exit;
  default:
    $server = $argv[1];
    $pcount = $argv[2];
    $loop = $argv[3];
    break;
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
        $rb = new MemcacheBench($server,$pcount,$loop);
        $rb->main();
        //echo "Complete No$i\n";
        exit(); //処理が終わったらexitする。
    }
}
//先に処理が進んでしまうので待つ
while( count( $pstack ) > 0 ) {
    unset( $pstack[ pcntl_waitpid( -1, $status, WUNTRACED ) ] );
}





