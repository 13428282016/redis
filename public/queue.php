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


require_once('../Service/redis.php');
require_once('../Service/redis_queue.php');

$redis = new MyRedis();
if ($redis->connect('173.254.246.15', 6379) === false) {

}



$redisQueue=new RedisQueue($redis);

$redisQueue->push('queue','hello world!','print_r');
$redisQueue->push('queue',1,'print_r');
$redisQueue->push('queue',2,'print_r');
$redisQueue->push('queue',3,'print_r');
$redisQueue->push('queue',4,'print_r');
$redisQueue->push('queue',5,'print_r');
$redisQueue->push('queue',6,'print_r');
$redisQueue->push('queue',7,'print_r');
$redisQueue->push('queue',null,['A','getClassName']);
$redisQueue->push('queue','word',['A','say']);
$redisQueue->run('queue');
$redisQueue->push('queueA','hello world!','print_r');
$redisQueue->push('queueA',1,'print_r');
$redisQueue->push('queueA',2,'print_r');
$redisQueue->push('queueA',3,'print_r');
$redisQueue->push('queueA',4,'print_r');
$redisQueue->push('queueA',5,'print_r');
$redisQueue->push('queueA',6,'print_r');
$redisQueue->push('queueA',7,'print_r');
$redisQueue->push('queueA',null,['A','getClassName']);
$redisQueue->push('queueA','word',['A','say']);
$redisQueue->run('queueA');