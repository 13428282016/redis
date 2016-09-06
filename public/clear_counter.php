<?php
/**
 * Created by PhpStorm.
 * User: wmj
 * Date: 2016/9/5
 * Time: 19:49
 */
set_time_limit(0);
require_once('../Service/redis.php');
require_once('../Task/clear_counter.php');

$redis = new MyRedis();
if ($redis->connect('173.254.246.15', 6379) === false) {

}
$clearCounter=new ClearCounter($redis);
$clearCounter->add(1,['key'=>'goods:1:hits:count:1','retainNum'=>8]);
$clearCounter->add(1,['key'=>'goods:1:hits:count:5','retainNum'=>6,'rankKey'=>'goods:hits:count:5:rank']);
$clearCounter->add(2,['key'=>'goods:1:hits:count:10','retainNum'=>4]);
$clearCounter->add(2,['key'=>'goods:1:hits:count:60','retainNum'=>2]);
$clearCounter->add(1,['key'=>'goods:2:hits:count:1','retainNum'=>8]);
$clearCounter->add(1,['key'=>'goods:2:hits:count:5','retainNum'=>6,'rankKey'=>'goods:hits:count:5:rank']);
$clearCounter->add(2,['key'=>'goods:2:hits:count:10','retainNum'=>4]);
$clearCounter->add(2,['key'=>'goods:2:hits:count:60','retainNum'=>2]);
$clearCounter->add(1,['key'=>'goods:3:hits:count:1','retainNum'=>8]);
$clearCounter->add(1,['key'=>'goods:3:hits:count:5','retainNum'=>6,'rankKey'=>'goods:hits:count:5:rank']);
$clearCounter->add(2,['key'=>'goods:3:hits:count:10','retainNum'=>4]);
$clearCounter->add(2,['key'=>'goods:3:hits:count:60','retainNum'=>2]);

$clearCounter->run();

