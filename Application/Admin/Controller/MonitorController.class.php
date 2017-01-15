<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/23
 * Time: 7:47
 */
namespace Admin\Controller;
use Think\Controller\RestController;

class MonitorController extends RestController {

    /**
     * 建立索引
     */
    public function buildIndexGet() {
        //校验KEY
        $key = C('APP_KEY');
        if(I('get.key') != md5($key)) {
            echo "app_key is wrong\n";
            return;
        }
        $mongo_client = new \MongoClient(C('MONGO_SERVER'));
        $db_name = C('MONGO_DB');
        $db = $mongo_client->$db_name;

        //admin_user
        $c = new \MongoCollection($db, 'admin_user');
        $c->deleteIndexes();
        $c->createIndex(array('username' => 1), array());
        $c->createIndex(array('name' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_agent
        $c = new \MongoCollection($db, 'admin_agent');
        $c->deleteIndexes();
        $c->createIndex(array('username' => 1), array());
        $c->createIndex(array('cellphone' => 1), array());
        $c->createIndex(array('wechat' => 1), array());
        $c->createIndex(array('name' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_card
        $c = new \MongoCollection($db, 'admin_card');
        $c->deleteIndexes();
        $c->createIndex(array('name' => 1), array());
        $c->createIndex(array('admin' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_mail
        $c = new \MongoCollection($db, 'admin_mail');
        $c->deleteIndexes();
        $c->createIndex(array('title' => 1), array());
        $c->createIndex(array('admin' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_user
        $c = new \MongoCollection($db, 'admin_user');
        $c->deleteIndexes();
        $c->createIndex(array('username' => 1), array());
        $c->createIndex(array('name' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_menu
        $c = new \MongoCollection($db, 'admin_menu');
        $c->deleteIndexes();
        $c->createIndex(array('name' => 1), array());
        $c->createIndex(array('action' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_notice
        $c = new \MongoCollection($db, 'admin_notice');
        $c->deleteIndexes();
        $c->createIndex(array('title' => 1), array());
        $c->createIndex(array('admin' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_popup
        $c = new \MongoCollection($db, 'admin_popup');
        $c->deleteIndexes();
        $c->createIndex(array('admin' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_role
        $c = new \MongoCollection($db, 'admin_role');
        $c->deleteIndexes();
        $c->createIndex(array('name' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_stock
        $c = new \MongoCollection($db, 'admin_stock');
        $c->deleteIndexes();
        $c->createIndex(array('remark' => 1), array());
        $c->createIndex(array('apply_user' => 1), array());
        $c->createIndex(array('audit_user' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_stock_grant_record
        $c = new \MongoCollection($db, 'admin_stock_grant_record');
        $c->deleteIndexes();
        $c->createIndex(array('from_user' => 1), array());
        $c->createIndex(array('to_user' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_trotting
        $c = new \MongoCollection($db, 'admin_trotting');
        $c->deleteIndexes();
        $c->createIndex(array('admin' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //agent_stock_grant_record
        $c = new \MongoCollection($db, 'agent_stock_grant_record');
        $c->deleteIndexes();
        $c->createIndex(array('nickname' => 1), array());
        $c->createIndex(array('from_user' => 1), array());
        $c->createIndex(array('to_user' => 1), array());
        $c->createIndex(array('date' => 1), array());

        echo "create mongodb index at " . date("Y-m-d H:i:s", time()) . "\n";
    }


    /**
     * @desc 游戏日，月消耗
     */
    public function streamGet() {
        //校验KEY
        $key = C('APP_KEY');
        if(I('get.key') != md5($key)) {
            echo "app_key is wrong\n";
            return;
        }

        // day 日
        // month 月
        $type = I('get.type');
        $end_date = strtotime(date("Y-m-d 00:00:00", time()));
        $start_date = $end_date - 86400;
        var_dump($end_date, $start_date);
        $mongo_client = new \MongoClient(C('MONGO_SERVER'));
        $db_name = C('MONGO_DB');
        $db = $mongo_client->$db_name;
        $ticket_use_record = $db->ticket_use_record;

        $cursor = $ticket_use_record->group(
            array('roleid' => 1),
            array('usedCnt' => 0),
            "function (obj, prev) {
                 prev.usedCnt += obj.usedCnt;
             }",
            array(
                'condition' => array(
                    'usedTime' => array(
                        '$gte' => $start_date,
                        '$lt' => $end_date,
                    )
                )
            )
        );
        $result = array();
        foreach($cursor['retval'] as $item) {
            var_dump($item);
        }
    }
}