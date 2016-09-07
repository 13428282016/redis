<?php
/**
 * Created by PhpStorm.
 * User: wmj
 * Date: 2016/9/5
 * Time: 15:09
 */

class MyRedis extends  \Redis{


    protected  $lockNamePrefix='lock_';
    protected $semaphoreNamePrefix='semaphore_';
    protected $semaphoreCounterNamePrefix='semaphore_counter_';
    protected $semaphoreTimeNamePrefix='semaphore_time_';
    public function __construct(){
        parent::__construct();
    }

    public function lock($key,$requireTimeout=10,$lockTimeout=10) {
        $lock = uniqid();    //  防止被其他客户端释放锁
        $now = time();
        $lockName=$this->getLockName($key);
        while (time() < $now + $requireTimeout) {
            if ($this->setnx($lockName, $lock)) {
                $this->expire($lockName,$lockTimeout);//防止释放锁失败，或者程序崩毁没有释放锁，导致锁一直被占用的问题
                return $lock;
            }
            if($this->ttl($lockName)==-1){
                $this->expire($lockName,$lockTimeout);//处理key没有正确的设置过期时间的情况
            }
            usleep(10);//睡眠10毫秒再试（锁已经被占用，不需要立即就重试）
        }
    }

    public function  getLockName($key){
        return $this->lockNamePrefix.$key;
    }

    public function  unlock($key,$lock){

        $lockName=$this->getLockName($key);
        while(true){

            try {
                $this->watch($lockName);
                $realId = $this->get($lockName);
                if ($realId === false) {
                    return -1;
                }
                if ($realId === $lock) {//  防止被其他客户端释放锁
                    $this->multi();
                    $this->del($lockName);
                    return $this->exec();
                }
                $this->unwatch();
                break;
            }catch (Exception $e){
                //重试
                $this->discard();
            }
        }
        return false;
    }
    public function  getSemaphoreName($key){
        return $this->semaphoreNamePrefix.$key;
    }
    /*
     * 如果排名低于可获取的信号量总数，那么表示进程成功获取了信号量。获取信号量和获取锁不同的是，获取信号量在失败的时候认为资源紧缺，不需要重试。
     *时间较慢的会偷走时间较快的系统的信号量，因为时间慢的排名在时间快得前面。
     */
    public function acquireUnjustSemaphore($key,$limit,$timeout=10){
        $semaphore=uniqid();
        $now=time();
        $semaphoreName=$this->getSemaphoreName($key);
        $lock=$this->lock($key);//防止竞争操作，例如一直被zAdd而没有执行zRank，这样会导致信号量随着时间流逝一添加就过期了
        if(!$lock){
            return false;
        }
        try{
            $this->multi();
            $this->zRemRangeByScore($semaphoreName,'-inf',$now-$timeout);//清除过期信号量，因为这样是集合，如果用expire自动清除的话会把所有信号量清除。或者程序A，B同时zrank操作获取排名，然后程序A删除了一个信号量，这是时候B的rank还是原来的，出现不一致性，
            $this->zAdd($semaphoreName,$now,$semaphore);//尝试获取信号量
            $this->zRank($semaphoreName,$semaphore);
            $ret=$this->exec();
            if($ret[2]<$limit){//如果排名低于可获取的信号量总数，那么表示进程成功获取了信号量。
                return $semaphore;
            }
            $this->zRem($semaphoreName,$semaphore);
            return false;

        }finally{
            $this->unlock($key,$lock);
        }


    }

    public function refreshUnjustSemaphore($key,$semaphore){
        $semaphoreName=$this->getSemaphoreName($key);
        if($this->zAdd($semaphoreName,time(),$semaphore)){//添加成功说明客户端已经失去了信号量
            $this->refreshUnjustSemaphore($key,$semaphore);// 取消刚才的操作
            return false;
        }
        return true;
    }
    public function releaseUnjustSemaphore($key,$semaphore){

        $semaphoreName=$this->getSemaphoreName($key);
        return $this->zRem($semaphoreName,$semaphore);
    }

    public function  getSemaphoreCounterName($key){

        return $this->semaphoreCounterNamePrefix.$key;
    }

    public  function  getSemaphoreTimeName($key){
        return $this->semaphoreTimeNamePrefix.$key;
    }
    /*
     *
     * 公平信号量采用计算器作为排名，先自增的排名越前，消除了不同系统时间不一致导致的不公平竞争。由于判断超时时，使用的是各自系统的时间，所以超时处理不会受不同系统时间不一致影响
     *
     *
     */
    public function  acquireSemaphore($key,$limit,$timeout=10){
        $semaphore=uniqid();
        $now=time();
        $semaphoreName=$this->getSemaphoreName($key);
        $semaphoreTimeName=$this->getSemaphoreTimeName($key);
        $semaphoreCounterName=$this->getSemaphoreCounterName($key);
        $lock=$this->lock($key);
        if(!$lock){
            return false;
        }
        try{
            $this->multi();
            $this->zRemRangeByScore($semaphoreTimeName,'-inf',$now-$timeout);//删除过期的信号量
            $this->zInter($semaphoreName,[$semaphoreName,$semaphoreTimeName],[1,0]);//删除过期的信号量
            $this->incr($semaphoreCounterName);
            $ret=$this->exec();
            $count=$ret[2];
            $this->multi();
            $this->zAdd($semaphoreTimeName,$now,$semaphore);
            $this->zAdd($semaphoreName,$count,$semaphore);
            $this->zRank($semaphoreName,$semaphore);
            $ret=$this->exec();
            $rank=$ret[2];
            if($rank<$limit){
                return $semaphore;
            }
            $this->multi();
            $this->zRem($semaphoreTimeName,$semaphore);
            $this->zRem($semaphoreName,$semaphore);
            return $this->exec();
        }finally{
            $this->unlock($key,$lock);
        }



    }
    public function releaseSemaphore($key,$semaphore){
        $semaphoreName=$this->getSemaphoreName($key);
        $semaphoreTimeName=$this->getSemaphoreTimeName($key);
        $this->multi();
        $this->zRem($semaphoreTimeName,$semaphore);
        $this->zRem($semaphoreName,$semaphore);
        return $this->exec();
    }


    public function refreshSemaphore($key,$semaphore){
        $semaphoreTimeName=$this->getSemaphoreTimeName($key);
        if($this->zAdd($semaphoreTimeName,time(),$semaphore)){//添加成功说明客户端已经失去了信号量
            $this->refreshSemaphore($key,$semaphore);// 取消刚才的操作
            return false;
        }
        return true;
    }








}