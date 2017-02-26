<?php
/**
 * Created by PhpStorm.
 * User: JSS
 * Date: 2017/1/13
 * Time: 14:19
 */
namespace Admin\Controller;

use Common\Controller\BaseController;
use Think\Page;

class PlayerController extends BaseController
{
    public function playersGet()
    {
        $search = array();
        $search['roleid'] = I('get.roleid', null);
        $search['nickname'] = I('get.nickname', null);

        $admin_client = $this->mongo_db->role_info;
        $stock_type = C('SYSTEM.STOCK_TYPE');
        $this->assign("stock_type", $stock_type);

        if (I('get._id')) {
            $search['_id'] = new \MongoId(I('get._id', null));
            $option = array();
            $query = $admin_client->findOne($search, $option);
            $this->_result['data']['clients'] = $query;
            if (I('get.tab') == 'card') {//充卡页面
                $stock_amount_type = C('SYSTEM.STOCK_AMOUNT_TYPE');
                $query['type_name'] = $stock_type[$query['type']];
                $stock_amount = $query['stock_amount'];
                foreach ($stock_amount as $key => $value) {
                    $stock_amount[$key] = array(
                        'name' => $stock_type[$key],
                        'amount' => $value
                    );
                }
                $this->assign("clients", $query);
                $this->assign("stock_amount", $stock_amount);
                $this->assign("stock_amount_type", $stock_amount_type);
                $html = $this->fetch("Client:card");
                $this->_result['data']['html'] = $html;
                $this->response($this->_result);
            }
        } else {
            $search['roleid'] && $search['roleid'] = intval($search['roleid']);
            $search['nickname'] && $search['nickname'] = new \MongoRegex("/{$search['nickname']}/");
            $limit = intval(I('get.limit', C('PAGE_NUM')));
            $skip = (intval(I('get.p', 1)) - 1) * $limit;
            $search['regTime'] = I('get.regTime', null);

            if ($search['regTime']) {
                $search['regTime'] = rangeDate($search['regTime']);
                $search['regTime'] = array('$gte' => $search['regTime'][0], '$lte' => $search['regTime'][1]);
            }
            filter_array_element($search);
            filter_array_element($option);

            $cursor = $admin_client->find($search)->sort(array("roleid" => -1))->limit($limit)->skip($skip);
            $result = array();
            foreach ($cursor as $item) {
                $item['match_count'] = $item['totalWinCi'] + $item['totalLoseCi'] + $item['totalPingCi'];
                $item['date'] = date("Y-m-d H:i:s", $item['regTime']);
                array_push($result, $item);
            }

            $count = $admin_client->count($search);
            $page = new Page($count, $limit);
            $page = $page->show();

            $this->assign("page", $page);
            $this->assign("clients", $result);
            $this->_result['data']['html'] = $this->fetch("Player:index");

            $this->_result['data']['count'] = $count;
            $this->_result['data']['page'] = $page;
            $this->_result['data']['clients'] = $result;
        }
        $this->response($this->_result);
    }

    public function playersExcelPost()
    {
        $search = array();
        $admin_client = $this->mongo_db->role_info;
        $search['roleid'] && $search['roleid'] = intval($search['roleid']);
        $search['nickname'] && $search['nickname'] = new \MongoRegex("/{$search['nickname']}/");
        /*$limit = intval(I('get.limit', C('PAGE_NUM')));
        $skip = $_SESSION['skip'];
        $skip = ($skip - 1) * $limit;*/
        $search['regTime'] = I('get.regTime', null);

        if ($search['regTime']) {
            $search['regTime'] = rangeDate($search['regTime']);
            $search['regTime'] = array('$gte' => $search['regTime'][0], '$lte' => $search['regTime'][1]);
        }
        filter_array_element($search);
        filter_array_element($option);

        $cursor = $admin_client->find($search)->sort(array("roleid" => -1));//->limit($limit)->skip($skip);
        $option['filename'] = "玩家列表报表" . date("Y-m-d") . ".xlsx";
        $option['author'] = '杠杠麻将';
        $option['header'] = array('玩家ID', '注册时间', '昵称', '房卡剩余', '累计局数', '赢分');
        $option['data'] = array();
        foreach ($cursor as $item) {
            $item['date'] = date("Y-m-d H:i:s", $item['regTime']);
            $item['match_count'] = $item['totalWinCi'] + $item['totalLoseCi'] + $item['totalPingCi'];
            //$charset = mb_detect_encoding($item['nickname']);
            //$item['nickname'] = iconv($charset, 'utf-8', $item['nickname']);
            array_push($option['data'], array($item['roleid'], $item['date'],
                $item['nickname'], intval($item['stock_amount'][1] + $item['stock_amount'][2]),
                $item['match_count'], $item['totalWinFen']));
        }
        excelExport($option, '2007');
    }

    //TODO
    public function forbiddenGet()
    {
        $this->_result['data']['html'] = $this->fetch("Player:forbidden");
        $this->response($this->_result);
    }

    //TODO
    public function forbiddenPut()
    {

    }
}