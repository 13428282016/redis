<?php

/**
 * Created by PhpStorm.
 * User: wmj
 * Date: 2016/9/5
 * Time: 16:18
 */

//还没完成，这里清理使用scan去遍历吧。
class ClearCounter {


    protected $items = [


    ];

    protected $conn;

    protected $retainNum = 1000;


    public function  __construct($conn) {

        $this->conn = $conn;
    }

    public function  add($frequency, $item) {

        if (!isset($this->items[$frequency])) {
            $this->items[$frequency] = [];
        }
        array_push($this->items[$frequency], $item);
        return true;
    }

    public function  run() {
        $passedMinute = 0;
//        while (true) {
        while ($this->conn->exists('is_clearing_counter')) {
            $startAt = time();
            foreach ($this->items as $frequency => $counters) {
                if ($passedMinute % $frequency) {
                    continue;
                }
                foreach ($counters as $counter) {
                    try {
                        $this->conn->watch($counter['key']);
                        $len = $this->conn->hLen($counter['key']);


                        $retainNum = $counter['retainNum'] ? $counter['retainNum'] : $this->retainNum;
                        if ($len > $retainNum) {
                            $slices = $this->conn->hKeys($counter['key']);
                            sort($slices);
                            $slices = array_slice($slices, 0, -$retainNum);
                            if ($counter['rankKey']) {
                                $rankKeys = [];
                                foreach ($slices as $slice) {
                                    $rankKeys[] = "{$counter['rankKey']}:slice:{$slice}";
                                }

                            }

                            $this->conn->multi();
                            array_unshift($slices, $counter['key']);
                            call_user_func_array([$this->conn, 'hDel'], $slices);
                            if(!empty($rankKeys)){
                               $this->conn->del($rankKeys);
                            }
                            $this->conn->exec();
                        } else {
                            $this->conn->unwatch();
                        }
                    } catch (\Exception $e) {
                        //记录错误
                    }
                }

            }
            $passedMinute += 1;
            $duration = time() - $startAt;
            if ($duration > 60) {
                //警告一分钟内还没执行完清理操作；
            }
            sleep(max(60 - $duration, 1));
        }
    }


}