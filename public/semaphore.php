<?php
/**
 * Created by PhpStorm.
 * User: wmj
 * Date: 2016/9/7
 * Time: 16:14
 */

require_once('../Service/redis.php');

$redis = new MyRedis();
if ($redis->pconnect('173.254.246.15', 6379) === false) {

}

function play(MyRedis $conn,$key,$limit){


    $semaphore=$conn->acquireUnjustSemaphore($key,$limit);
    if(!$semaphore){
        echo "服务器忙!";
        return false;
    }
    try{
        echo "成功进入游戏";
        sleep(rand(10,20));
    }finally{
        $conn->releaseUnjustSemaphore($key,$semaphore);
    }



}

function playA(MyRedis $conn,$key,$limit){


    $semaphore=$conn->acquireSemaphore($key,$limit);
    if(!$semaphore){
        echo "服务器忙!";
        return false;
    }
    try{
        echo "成功进入游戏";
        sleep(rand(10,20));
    }finally{
        $conn->releaseSemaphore($key,$semaphore);
    }



}
//play($redis,'play',2);

playA($redis,'play',2);