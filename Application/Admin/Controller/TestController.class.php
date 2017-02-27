<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/23
 * Time: 7:47
 */
namespace Admin\Controller;
use Think\Controller\RestController;

class TestController extends RestController {
    public function indexGet() {
        //连接数据库
        $mongo_client = new \MongoClient(C('MONGO_SERVER'));
        $db = C('MONGO_DB');
        $db = $mongo_client->$db;
        //查询是否已经领取该时段的奖励
        $admin_card_receive_daily = $db->admin_card_receive_daily;
        //查找最近一条记录
        $award = $admin_card_receive_daily->find(array(
            'roleid' => 1005202,
            'start_time' => 0,
            'end_time' => 67500
        ))->sort(array("date"=>-1))->limit(1);
        //如果有记录，判断这条记录是不是今天的
        $today = date("Y-m-d", time());
        $day = "";
        var_dump($award);
        if ($award) {
            $record = "";
            foreach($award as $item) {
                $record = $item;
            }
            $day = date("Y-m-d", $record['date']);
        }
        var_dump($day);
    }

}