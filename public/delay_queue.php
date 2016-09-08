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
$redisQueue->pushDelay('delay_queue','hello world!','print_r',10);
$redisQueue->pushDelay('delay_queue',1,'print_r',15);
$redisQueue->pushDelay('delay_queue',2,'print_r',20);
$redisQueue->pushDelay('delay_queue',3,'print_r',25);
$redisQueue->pushDelay('delay_queue',4,'print_r',30);
$redisQueue->pushDelay('delay_queue',5,'print_r',35);
$redisQueue->pushDelay('delay_queue',6,'print_r',40);
$redisQueue->pushDelay('delay_queue',7,'print_r',45);
$redisQueue->pushDelay('delay_queue',null,['A','getClassName'],50);
$redisQueue->pushDelay('delay_queue','word',['A','say'],55);
$redisQueue->pullDelay('delay_queue');
$redisQueue->run('delay_queue');
