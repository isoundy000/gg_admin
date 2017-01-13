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

class PlayerController extends BaseController {
    public function playersGet() {
        $search = array();
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
            if (I('get.fields')) {
                $search['roleid'] = intval(I('get.fields'));
            }
            $limit = intval(I('get.limit', C('PAGE_NUM')));
            $skip = (intval(I('get.p', 1)) - 1) * $limit;
            filter_array_element($search);
            filter_array_element($option);

            $cursor = $admin_client->find($search)->limit($limit)->skip($skip);
            $result = array();
            foreach ($cursor as $item) {
                $item['match_count'] = $item['totalWinCi'] + $item['totalLoseCi'] + $item['totalPingCi'];
                array_push($result, $item);
            }

            $count = $admin_client->count($search);
            $page = new Page($count, C('PAGE_NUM'));
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
}