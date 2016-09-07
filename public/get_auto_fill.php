<?php
/**
 * Created by PhpStorm.
 * User: wmj
 * Date: 2016/9/6
 * Time: 17:51
 */

require_once('../Service/redis.php');
require_once('../Service/redis_auto_fill.php');


$redis = new MyRedis();
if ($redis->connect('173.254.246.15', 6379) === false) {

}
$redisAutoFill=new RedisAutoFill($redis);
print_r($redisAutoFill->get('contacts',$_REQUEST['prefix'],intval($_REQUEST['offset']),intval($_REQUEST['rows'])));