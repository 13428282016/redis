<?php
/**
 * Created by PhpStorm.
 * User: wmj
 * Date: 2016/9/5
 * Time: 15:12
 */
/*
为了防止内存不足，应该定时清理计数器，清理计数器的频率（定时任务实现）应有程序根据实际情况确定
*/

class RedisCounter {


    private $conn;


    public function __construct($conn) {

        $this->conn = $conn;
    }

    public function  getAll($key) {

        $items=$this->conn->hGetAll($key);
        ksort( $items);
        return $items;

    }

    public function  getLast($key, $precision) {
        $slice = intval(time() / $precision) * $precision;
        return intval($this->conn->hGet($key, $slice));
    }


    public function getRangeRankingList( $rankKey,$precision,$startAt,$endAt=null ,$offset = 0, $len = null, $start = '+inf', $end = '-inf', $withScores = false) {

         $startAt=intval($startAt/$precision)*$precision;
        $endAt||$endAt=time();
        $endAt=intval($endAt/$precision)*$precision;
        $slices=[];
        for($i=$startAt;$i<=$endAt;$i+=$precision){
            $slices[]=$i;
        }
        $this->conn->multi(Redis::PIPELINE);
        $keys = [];
        foreach ($slices as $slice) {

            $keys[] = $key = $this->getRankKeyBySlice($rankKey, $slice);
            $this->conn->zRevRangeByScore($key, $start, $end, ['limit' => [$offset, $len], 'withscores' => $withScores]);
        }
        $ranks = $this->conn->exec();
        return array_combine($keys, $ranks);
    }

    public function  getLastRankingList(  $rankKey,$precision, $offset = 0, $len = null, $start = '+inf', $end = '-inf', $withScores = false) {
        $slice = intval(time() / $precision) * $precision;
        $key = $this->getRankKeyBySlice($rankKey, $slice);
        $rank = $this->conn->zRevRangeByScore($key, $start, $end, ['limit' => [$offset, $len], 'withscores' => $withScores]);
        return $rank;
    }


    public function  update($counters, $count = 1, $time = null) {


        $time || $time = time();
        $this->conn->multi();
        foreach ($counters as $counter) {
            $slice = intval($time / $counter['precision']) * $counter['precision'];//获取当前时间所处的分片
            $this->conn->hIncrBy($counter['key'], $slice, $count);
            echo "{$counter['key']},$slice";
            if (isset($counter['rankKey'])) {

                $this->conn->zIncrBy($this->getRankKeyBySlice($counter['rankKey'], $slice), $count, $counter['id']);

            }
        }
        return $this->conn->exec();
    }

    public function  getRankKeyBySlice($rankKey, $slice) {
        return "{$rankKey}:slice:{$slice}";
    }


}