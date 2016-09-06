<?php
/**
 * Created by PhpStorm.
 * User: wmj
 * Date: 2015/11/11
 * Time: 11:17
 */

require_once('../Service/redis.php');
require_once('../Service/redis_counter.php');

$redis = new MyRedis();
if ($redis->connect('173.254.246.15', 6379) === false) {

}

$redisCounter = new RedisCounter($redis);
$time=time();
$redisCounter->update([
    [
        'key' => 'goods:1:hits:count:1',
        'precision' => 1,
    ],

    [
        'key' => 'goods:1:hits:count:5',
        'precision' => 5,
        'id'=>1,
        'rankKey' => 'goods:hits:count:5:rank'

    ],
    [
        'key' => 'goods:1:hits:count:10',
        'precision' => 5,
    ],
    [
        'key' => 'goods:1:hits:count:60',
        'precision' => 60
    ],
], rand(1, 10),$time);

$redisCounter->update([
    [
        'key' => 'goods:2:hits:count:1',
        'precision' => 1,
    ],

    [
        'key' => 'goods:2:hits:count:5',
        'precision' => 5,
        'id'=>2,
        'rankKey' => 'goods:hits:count:5:rank'

    ],
    [
        'key' => 'goods:2:hits:count:10',
        'precision' => 5,
    ],
    [
        'key' => 'goods:2:hits:count:60',
        'precision' => 60
    ],
], rand(1, 10),$time);

$redisCounter->update([
    [
        'key' => 'goods:3:hits:count:1',
        'precision' => 1,
    ],

    [
        'key' => 'goods:3:hits:count:5',
        'precision' => 5,
        'id'=>3,
        'rankKey' => 'goods:hits:count:5:rank'

    ],
    [
        'key' => 'goods:3:hits:count:10',
        'precision' => 5,
    ],
    [
        'key' => 'goods:3:hits:count:60',
        'precision' => 60
    ],
], rand(1, 10),$time);



