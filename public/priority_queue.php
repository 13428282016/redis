<?php
/**
 * Created by PhpStorm.
 * User: wmj
 * Date: 2016/9/8
 * Time: 11:15
 */

class A{
    public  static  function getClassName(){

        return self::class;
    }
    public static  function say($word){
        echo  $word;
    }
}

require_once('../Util/function.php');
require_once('../Service/redis.php');
require_once('../Service/redis_queue.php');

$redis = new MyRedis();
if ($redis->connect('173.254.246.15', 6379) === false) {

}



$redisQueue=new RedisQueue($redis);
$redisQueue->pushPriority('priority_queue','hello world!','print_r',1);
$redisQueue->pushPriority('priority_queue',9,'print_r',9);
$redisQueue->pushPriority('priority_queue',3,'print_r',3);
$redisQueue->pushPriority('priority_queue',6,'print_r',6);
$redisQueue->pushPriority('priority_queue',7,'print_r',7);
$redisQueue->pushPriority('priority_queue',4,'print_r',4);
$redisQueue->pushPriority('priority_queue',8,'print_r',8);
$redisQueue->pushPriority('priority_queue',5,'print_r',5);
$redisQueue->pullPriority('priority_queue');
$redisQueue->run('priority_queue');
