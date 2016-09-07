<?php
/**
 * Created by PhpStorm.
 * User: wmj
 * Date: 2016/9/6
 * Time: 16:56
 */
//如果有序集合的分值相等，将会按值得字典序进行排序。
//查找带有abc前缀得单词，实际上就是查找介于abbz...，abd之间得字符串。{在z元素之后，'{'不会出现在内容中，所有相当于查找 abb{...到abc{...之间得字符串
class RedisAutoFill{

    private $conn;

    public function __construct($conn) {

        $this->conn = $conn;
    }

    public function add($key,$value){

      return  $this->conn->zAdd($key,0,$value);

    }
    public function  get($key,$prefix,$offset=0,$len=null){
        $range=$this->getPrefixRange($prefix);

        $rand=rand(0,10000000);
        $range['start'].=$rand;
        $range['end'].=$rand;
        $this->conn->zAdd($key,0,$range['start'],0,$range['end']);
        while(true){
                $this->conn->watch($key);
                $start=$this->conn->zRank($key,$range['start']);
                $end=$this->conn->zRank($key,$range['end']);

                $end-=2;//去掉2个元素后的排名
                $start+=$offset;
                if($len){
                    $end=min($end,$start+$len-1);
                }
                $this->conn->multi();
                $this->conn->zRem($key,$range['start'],$range['end']);
                $this->conn->zRange($key,$start,$end);
                $ret=$this->conn->exec();
                if($ret===false){
                    continue;
                }
                return $ret[1];
        }





    }
    public function getPrefixRange($prefix){
        $end="$prefix{";
        $len=strlen($prefix);
        $start=substr($prefix,0,$len-1).chr(ord($prefix[$len-1])-1)."{";
        return ['start'=>$start,'end'=>$end];
    }

}