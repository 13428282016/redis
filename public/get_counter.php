<?php
/**
 * Created by PhpStorm.
 * User: wmj
 * Date: 2016/9/5
 * Time: 19:36
 */

require_once('../Service/redis.php');
require_once('../Service/redis_counter.php');

$redis = new MyRedis();
if ($redis->connect('173.254.246.15', 6379) === false) {

}

$redisCounter = new RedisCounter($redis);

echo "goods:1:hits:count\n";
print_r([
    1 => $redisCounter->getAll('goods:1:hits:count:1'),
    5 => $redisCounter->getAll('goods:1:hits:count:5'),
    10 => $redisCounter->getAll('goods:1:hits:count:10'),
    60 => $redisCounter->getAll('goods:1:hits:count:60'),

 
]);

echo "goods:2:hits:count\n";
print_r([
    1 => $redisCounter->getAll('goods:2:hits:count:1'),
    5 => $redisCounter->getAll('goods:2:hits:count:5'),
    10 => $redisCounter->getAll('goods:2:hits:count:10'),
    60 => $redisCounter->getAll('goods:2:hits:count:60'),

]);
echo "goods:3:hits:count\n";
print_r([
    1 => $redisCounter->getAll('goods:3:hits:count:1'),
    5 => $redisCounter->getAll('goods:3:hits:count:5'),
    10 => $redisCounter->getAll('goods:3:hits:count:10'),
    60 => $redisCounter->getAll('goods:3:hits:count:60'),

]);

echo "goods:hits:count:rank\n";
print_r( [
    5=>$redisCounter->getRangeRankingList('goods:hits:count:5:rank',5,time()-60,null,0,null,'+inf',  '-inf',  true)
]);

