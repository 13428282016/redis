<?php
/**
 * Created by PhpStorm.
 * User: wmj
 * Date: 2016/9/7
 * Time: 11:11
 */


require_once('../Service/redis.php');

$redis = new MyRedis();
if ($redis->pconnect('173.254.246.15', 6379) === false) {

}

function incr($conn, $key, $count = 1, $timeout = 5) {


    $start = time();
    while (time() <= $timeout + $start) {
        try {
            $conn->watch($key);
            $old = $conn->get($key);
            $conn->multi();
            $new = $old + $count;
            $conn->set($key, $new);
            if (rand(0, 1)) {
                throw new RedisException;
            }
            return $conn->exec();
        } catch (RedisException $e) {
            echo 'exception';
            $conn->discard();
        }

    }

}

function incrA($conn, $key, $count = 1) {

    $lock = $conn->lock($key);
    if (!$lock) {
        return false;
    }

    try{
        $old = $conn->get($key);
        $new = $old + $count;
        $conn->multi();
        $conn->set($key, $new);
        return $conn->exec();
    }finally{
        $conn->unlock($key,$lock);
    }



}

print_r(incr($redis, 'count'));
print_r(incrA($redis, 'count'));