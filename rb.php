#!/usr/local/bin/php
<?php
/* vim: set expandtab ts=4 sts=4 sw=4 tw=0: */


$pcount = 300;
$loop =   211500000/ $pcount;
//$loop = 7050000/ $pcount;

class RedisBench {
    private $redis01;
    private $redis02;
    private $redis03;

    private $scenarios;
    private $pid;
    function __construct() {
        $this->redis01 = new Redis();
        $this->redis02 = new Redis();
        $this->redis03 = new Redis();
        //$this->redis01->pconnect('/tmp/redis.sock');
        $this->redis01->pconnect('CMN_REDIS01');
        $this->redis02->pconnect('CMN_REDIS02');
        $this->redis03->pconnect('CMN_REDIS03');
        $this->pid = getmypid();

        $redis = $this->redis01;
        $pid = $this->pid;

        $this->scenarios = array(

            //パイプライン
            "pipe line" => function () use (&$redis,$pid) {
                $lastkey = "endtime".$pid;
                $start = microtime(true);
                $pipe = $redis->multi(Redis::PIPELINE);
                for($i = 1; $i<$loop;$i++){
                    $pipe->set("hoge".$pid.$i,"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaabbb");
                }
                $pipe->set($lastkey,date("Y-m-d H:i:s").substr(microtime(),1,5));
                echo "$pid rap1:".(microtime(true) - $start)."\n";
                $start = microtime(true);
                $pipe->exec();
                echo "$pid rap2:".(microtime(true) - $start)."\n";
                return $lastkey;
            } ,
            //単純実行
            "normal set" => function () use (&$redis, $pid) {
                $lastkey = "endtime".$pid;
                for($i = 1; $i<=$loop;$i++){
                    //$redis01->set("hoge".$i,date("Y-m-d H:i:s").substr(microtime(),1,5));
                    $redis->set("hoge".$pid.$i,$i);
                }
                $redis->set($lastkey,date("Y-m-d H:i:s").substr(microtime(),1,5));
                return $lastkey;
            } ,
        );

    }

    private function bm($funcs){
        foreach($funcs as $fn_name => $fn){
            echo $this->pid."-- run $fn_name -- ",$loop,"件のset\n";
            $start = microtime(true);
            $lastkey = $fn();
            $end = microtime(true);
            $time = $end-$start;
            echo $this->pid." time:{$time}秒\n";
            $lastvalue = $this->redis01->get($lastkey);
            echo $this->pid . " get lastkey.... redis-cli -h CMN_REDIS01 get $lastkey -> ".$lastvalue. "\n";
            if(empty($lastvalue)){
                echo  $this->pid ." 最後の値($lastkey)が記録できていないので終了します\n";
                exit (1);
            }
            echo  $this->pid ." polling........ redis-cli -h CMN_REDIS02 get $lastkey\n";
            echo  $this->pid ." polling........ redis-cli -h CMN_REDIS03 get $lastkey\n";
            $lag02 = 0;
            $lag03 = 0;
            while(true){
                if($lag02===0){
                    if($lastvalue === $this->redis02->get($lastkey)){
                        $redis02end = microtime(true);
                        $lag02 = $redis02end - $end;
                        echo $this->pid ." get lastkey........ redis-cli -h CMN_REDIS02 get $lastkey -> $lastvalue\n";
                        echo $this->pid." CMN_REDIS02 lag:{$lag02}秒\n";
                    }
                }
                if($lag03===0){
                    if($lastvalue === $this->redis03->get($lastkey)){
                        $redis03end = microtime(true);
                        $lag03 = $redis03end - $end;
                        echo $this->pid ." get lastkey........ redis-cli -h CMN_REDIS03 get $lastkey -> $lastvalue\n";
                        echo $this->pid." CMN_REDIS03 lag:{$lag03}秒\n";
                    }
                }
                if(($lag02 !== 0)&&($lag03 !== 0)){
                    break;
                }
                usleep(10);
            }
            break; // とりあえず最初のシナリオで終わる
        }
    }


    public function main(){
        $this->bm($this->scenarios);
    }
}


date_default_timezone_set('Asia/Tokyo');
ini_set('memory_limit', '2G');

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
        $rb = new RedisBench();
        $rb->main();
        echo "Complete No$i\n";
        exit(); //処理が終わったらexitする。
    }
}
//先に処理が進んでしまうので待つ
while( count( $pstack ) > 0 ) {
    unset( $pstack[ pcntl_waitpid( -1, $status, WUNTRACED ) ] );
}





