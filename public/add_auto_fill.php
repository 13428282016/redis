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
$redisAutoFill->add('contacts','wangmingjie');
$redisAutoFill->add('contacts','liangxiaojun');
$redisAutoFill->add('contacts','lishilin');
$redisAutoFill->add('contacts','yeguopeng');
$redisAutoFill->add('contacts','zhangyuejian');
$redisAutoFill->add('contacts','shuaijun');
$redisAutoFill->add('contacts','shuaitao');
$redisAutoFill->add('contacts','luxianfan');
$redisAutoFill->add('contacts','tanda');
$redisAutoFill->add('contacts','xiedangan');
$redisAutoFill->add('contacts','linjingguang');
$redisAutoFill->add('contacts','13428282016');
$redisAutoFill->add('contacts','13428282017');
$redisAutoFill->add('contacts','13535234854');
$redisAutoFill->add('contacts','13580698819');
$redisAutoFill->add('contacts','662016');
$redisAutoFill->add('contacts','3952699');
$redisAutoFill->add('contacts','3955033');
$redisAutoFill->add('contacts','3951672');
$redisAutoFill->add('contacts','13824590677');
