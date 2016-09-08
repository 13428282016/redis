<?php

/**
 * Created by PhpStorm.
 * User: wmj
 * Date: 2016/9/7
 * Time: 19:45
 */
class RedisQueue {


    private $redis;
    protected $callbackName = 'callback';
    protected $callbackArgName = 'args';
    protected $queueStatusKey = 'queue_status';
    protected $sortQueuePrefix = 'sort_';
    protected $execTimeName = 'exec_at';
    protected $taskIdName = 'id';
    protected $queuePullStatusKey = 'queue_pull_status';
    const QUEUE_RUNNING = 1;
    const QUEUE_STOP = 0;
    const QUEUE_PULLING = 1;
    const QUEUE_STOP_PULLING = 0;


    public function __construct($redis) {

        $this->redis = $redis;
    }

    public function  getTaskIdName() {
        return $this->taskIdName;
    }

    public function  getCallbackName() {

        return $this->callbackName;
    }

    public function  getCallbackArgName() {
        return $this->callbackArgName;
    }

    public function  getQueueStatusKey() {
        return $this->queueStatusKey;
    }

    public function  getQueuePullStatusKey() {
        return $this->queuePullStatusKey;
    }

    public function getQueueStatus($key) {
        return $this->redis->hGet($this->getQueueStatusKey(), $key);
    }

    public function  setQueueStatus($key, $value) {
        return $this->redis->hSet($this->getQueueStatusKey(), $key, $value);
    }

    public function getQueuePullStatus($key) {
        return $this->redis->hGet($this->getQueuePullStatusKey(), $key);
    }

    public function  setQueuePullStatus($key, $value) {
        return $this->redis->hSet($this->getQueuePullStatusKey(), $key, $value);
    }

    public function getExecTimeName() {
        return $this->execTimeName;
    }

    public function getSortQueueKey($key) {
        return $this->sortQueuePrefix . $key;
    }

    public function  push($key, $args, $callback) {
        $data = [
            $this->getCallbackArgName() => $args,
            $this->getCallbackName() => $callback
        ];
        return $this->redis->rPush($key, json_encode($data));


    }

    public function pushDelay($key, $args, $callback, $delay = 0) {
        $execAt = $delay + time();
        $this->pushDelayAt($key, $args, $callback, $execAt);
    }

    public function pushDelayAt($key, $args, $callback, $execAt = 0) {
        if ($execAt <= time()) {
            return $this->push($key, $args, $callback);
        }
        $data = [
            $this->getTaskIdName() => guid(),//保证任务唯一性，防止添加冲突
            $this->getCallbackArgName() => $args,
            $this->getCallbackName() => $callback
        ];
        return $this->redis->zAdd($this->getSortQueueKey($key), $execAt, json_encode($data));
    }

    public function pushPriority($key, $args, $callback, $priority) {
        $data = [
            $this->getTaskIdName() => guid(),//保证任务唯一性，防止添加冲突
            $this->getCallbackArgName() => $args,
            $this->getCallbackName() => $callback
        ];
        return $this->redis->zAdd($this->getSortQueueKey($key), $priority, json_encode($data));
    }

    public function pullDelay($key) {
        $sortKey = $this->getSortQueueKey($key);
        $lock = $this->redis->lock($sortKey);
        if (!$lock) {
            return false;
        }
        try {
            if ($this->getQueuePullStatus($key) == static::QUEUE_PULLING) {
                return -1;
            }
            $this->setQueuePullStatus($key, static::QUEUE_PULLING);

            while ($this->getQueuePullStatus($key) == static::QUEUE_PULLING) {

                $item = $this->redis->zRangeByScore($sortKey, '-inf', '+inf', ['withscores' => true,'limit'=>[0,1]]);
                if (empty($item) ) {
                    usleep(10);
                    continue;
                }
                $delayAt=array_values($item)[0];
                if ($delayAt > time()) {
                    usleep(10);
                    continue;
                }

                $value=array_keys($item)[0];

                if ($this->redis->zRem($sortKey, $value)) {
                    $value=json_decode($value,true);
                    $this->push($key, $value[$this->getCallbackArgName()],$value[$this->getCallbackName()]);
                }
            }
        } finally {
            $this->setQueuePullStatus($key, static::QUEUE_STOP);
            $this->redis->unlock($sortKey, $lock);
        }


    }

    public function pullPriority($key) {
        $sortKey = $this->getSortQueueKey($key);
        $lock = $this->redis->lock($sortKey);
        if (!$lock) {
            return false;
        }
        try {
            if ($this->getQueuePullStatus($key) == static::QUEUE_PULLING) {
                return -1;
            }
            $this->setQueuePullStatus($key, static::QUEUE_PULLING);

            while ($this->getQueuePullStatus($key) == static::QUEUE_PULLING) {

                $item = $this->redis->zRange($sortKey,0,0);
                if (empty($item) ) {
                    usleep(10);
                    continue;
                }
                $value=$item[0];

                if ($this->redis->zRem($sortKey, $value)) {
                    $value=json_decode($value,true);
                    $this->push($key, $value[$this->getCallbackArgName()],$value[$this->getCallbackName()]);
                }
            }
        } finally {
            $this->setQueuePullStatus($key, static::QUEUE_STOP);
            $this->redis->unlock($sortKey, $lock);
        }
    }

    public function stopPull($key) {
        return $this->setQueuePullStatus($key,static::QUEUE_STOP_PULLING);
    }

    public function pop($key) {
        return $this->redis->lPop($key);

    }

    public function bPop() {

        return call_user_func_array([$this->redis, 'blPop'], func_get_args());

    }


    public function  run($key, $popTimeout = 10) {

        $lock = $this->redis->lock($key);
        if (!$lock) {
            return false;
        }
        try {
            if ($this->getQueueStatus($key) == static::QUEUE_RUNNING) {
                return -1;
            }
            $this->setQueueStatus($key, static::QUEUE_RUNNING);
            $this->redis->unlock($key, $lock);
            while ($this->getQueueStatus($key) == static::QUEUE_RUNNING) {
                $pack = $this->bPop($key, $popTimeout);    //  当队列是空的时候，可以减少请求次数,可以同时监听多个队列，只要有一个队列有数据就可以出队
                if (!$pack) {
                    continue;
                }
                $item = json_decode($pack[1], true);
                $args = $item[$this->getCallbackArgName()];
                $callback = $item[$this->getCallbackName()];
                call_user_func_array($callback, is_array($args) ? $args : [$args]);

            }
        } finally {
            $this->setQueueStatus($key, static::QUEUE_STOP);
            $this->redis->unlock($key, $lock);

        }


    }

    public function stop($key) {
        return $this->setQueueStatus($key, static::QUEUE_STOP);

    }


}