<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2017/1/11
 * Time: 21:07
 */

namespace Admin\Controller;

use Common\Controller\BaseController;
use Think\Page;

class ReportController extends BaseController {

    public function streamGet() {
        $agent_type = C('SYSTEM.AGENT_TYPE');
        $this->assign("agent_type", $agent_type);
        $game_type = C('SYSTEM.GAME');
        $this->assign("game_type", $game_type);

        $type = I('get.type', 'day');
        switch ($type) {
            case 'day':
                break;
            case 'month':
                break;
        }
        $table_name = "admin_report_stream_" . $type;
        $table = $this->mongo_db->$table_name;

        $search = array();
        $limit = intval(I('get.limit', C('PAGE_NUM')));
        $skip = (intval(I('get.p', 1)) - 1) * $limit;
        $search['date'] = I('get.date', null);

        if ($search['date']) {
            $search['date'] = rangeDate($search['date']);
            $search['date'] = array('$gte' => $search['date'][0], '$lte' => $search['date'][1]);
        }
        filter_array_element($search);
        $cursor = $table->find($search)->limit($limit)->skip($skip)->sort(array("date"=>-1));
        $result = array();
        $total = array(
            'expense' => 0,
            'stream' => 0,
        );
        foreach ($agent_type as $k => $v) {
            $total['buy_card'][$k] = array(
                'name' => $agent_type[$k],
                'amount' => 0
            );
        }
        foreach ($cursor as $item) {
            $match = $type=='day' ? 'Y-m-d' : 'Y-m';
            $item['date'] = date($match, $item['date']);
            $item['game'] = $game_type[$item['game']];
            foreach ($item['buy_card'] as $k => $v) {
                $item['buy_card'][$k] = array(
                    'name' => $agent_type[$k],
                    'amount' => $v
                );

                $total['buy_card'][$k]['amount'] += $v;
            }
            if ($type == 'retail') {
                $total['card'] += $item['buy_card'];
            }
            $total['expense'] += $item['expense'];
            $total['stream'] += $item['stream'];
            array_push($result, $item);
        }

        $count = $table->count($search);
        $page = new Page($count, C('PAGE_NUM'));
        $page = $page->show();

        $this->assign("page", $page);

        $this->assign("stream", $result);
        $this->assign("total", $total);
        $this->assign("type", $type);
        $this->_result['data']['html'] = $this->fetch("Report:stream");
        $this->_result['data']['total'] = $total;
        $this->_result['data']['page'] = $page;
        $this->_result['data']['stream'] = $result;
        $this->response($this->_result);
    }

    public function streamExcelGet() {
        $agent_type = C('SYSTEM.AGENT_TYPE');
        $this->assign("agent_type", $agent_type);
        $game_type = C('SYSTEM.GAME');
        $this->assign("game_type", $game_type);

        $type = I('get.type', 'day');
        switch ($type) {
            case 'day':
                break;
            case 'month':
                break;
        }
        $table_name = "admin_report_stream_" . $type;
        $table = $this->mongo_db->$table_name;

        $search = array();
        //$limit = intval(I('get.limit', C('PAGE_NUM')));
        //$skip = (intval(I('get.p', 1)) - 1) * $limit;
        $search['date'] = I('get.date', null);

        if ($search['date']) {
            $search['date'] = rangeDate($search['date']);
            $search['date'] = array('$gte' => $search['date'][0], '$lte' => $search['date'][1]);
        }
        filter_array_element($search);
        $cursor = $table->find($search)->sort(array("date"=>-1));
        //$result = array();
        $total = array(
            'expense' => 0,
            'stream' => 0,
        );
        foreach ($agent_type as $k => $v) {
            $total['buy_card'][$k] = array(
                'name' => $agent_type[$k],
                'amount' => 0
            );
        }

        $filename = $type=='day' ? '日' : '月';

        $option['filename'] = "流水{$filename}报表" . date("Y-m-d") . ".xls";
        $option['author'] = '杠杠麻将';

        $option['header'] = array('日期', '游戏', '购卡量', '', '流水');
        $option['merge'] = array('C1:D1');
        $option['data'] = array();

        foreach ($cursor as $item) {
            $match = $type=='day' ? 'Y-m-d' : 'Y-m';
            $item['date'] = date($match, $item['date']);
            $item['game'] = $game_type[$item['game']];
           /* foreach ($item['buy_card'] as $k => $v) {
                $item['buy_card'][$k] = array(
                    'name' => $agent_type[$k],
                    'amount' => $v
                );

                $total['buy_card'][$k]['amount'] += $v;
            }*/
            if ($type == 'retail') {
                $total['card'] += $item['buy_card'];
            }
            $total['expense'] += $item['expense'];
            $total['stream'] += $item['stream'];
            /*array_push($result, $item);*/
            $current = array($item['date'], $item['game'], $item['buy_card'][1], $item['buy_card'][2], $item['stream']);
            array_push($option['data'], $current);
        }

        excelExport($option);
    }

    public function agentStreamGet() {
        $agent_type = C('SYSTEM.AGENT_TYPE');
        $this->assign("agent_type", $agent_type);
        $game_type = C('SYSTEM.GAME');
        $this->assign("game_type", $game_type);

        $type = I('get.type', 'day');
        switch ($type) {
            case 'day':
                break;
            case 'month':
                break;
        }
        $table_name = "admin_report_agent_stream_" . $type;
        $table = $this->mongo_db->$table_name;

        $search = array();
        $limit = intval(I('get.limit', C('PAGE_NUM')));
        $skip = (intval(I('get.p', 1)) - 1) * $limit;
        $search['date'] = I('get.date', null);
        $search['username'] = I('get.username', null);
        $search['type'] = I('get.agent_type', null);
        if ($search['date']) {
            $search['date'] = rangeDate($search['date']);
            $search['date'] = array('$gte' => $search['date'][0], '$lte' => $search['date'][1]);
        } else {
            //上月数据
            $search['date'] = strtotime(date("Y-m-01", strtotime("-1 month")));
        }
        $search['type'] && $search['type'] = intval($search['type']);
        $show_child = I('get.show_child');//是否显示账号下的子账号
        if ($show_child) {
            //父账号_id
            $parent_user = $this->mongo_db->admin_agent->findOne(['username' => $search['username']]);
            $parent_user_id = $parent_user['_id']->__toString();
            $child_list = $this->mongo_db->admin_agent->find(['pid' => $parent_user_id])->field();
            $child_username_list = array();
            foreach ($child_list as $item) {
                array_push($child_username_list, $item['username']);
            }
            //TODO
            if ($child_username_list) {
                $search['username'] = ['$in', implode(',', $child_list)];
            }
        }
        filter_array_element($search);
        $cursor = $table->find($search)->limit($limit)->skip($skip)->sort(array("username" => 1));
        $result = array();
        $total = array(
            'pay_back' => 0,
            'expense' => 0,
            'purchase' => 0,
        );
        foreach ($cursor as $item) {
            $item['date'] = date("Y-m", $item['date']);
            $total['pay_back'] += $item['pay_back'];
            $total['expense'] += $item['expense'];
            $total['purchase'] += $item['purchase'];
            $item['type_name'] = $agent_type[$item['type']];
            array_push($result, $item);
        }

        $count = $table->count($search);
        $page = new Page($count, C('PAGE_NUM'));
        $page = $page->show();

        $this->assign("page", $page);

        $this->assign("stream", $result);
        $this->assign("total", $total);
        $this->assign("type", $type);
        $this->_result['data']['html'] = $this->fetch("Report:agent_stream");
        $this->_result['data']['total'] = $total;
        $this->_result['data']['stream'] = $result;
        $this->_result['data']['page'] = $page;
        $this->response($this->_result);
    }

    public function rechargeGet() {
        $agent_type = C('SYSTEM.AGENT_TYPE');
        $this->assign("agent_type", $agent_type);
        $game_type = C('SYSTEM.GAME');
        $this->assign("game_type", $game_type);

        $table_name = "agent_recharge_order";
        $table = $this->mongo_db->$table_name;

        $search = array();
        $limit = intval(I('get.limit', C('PAGE_NUM')));
        $skip = (intval(I('get.p', 1)) - 1) * $limit;
        $search['date'] = I('get.date', null);
        $search['username'] = I('get.username', null);
        $search['status'] = 1; //支付已成功
        if ($search['date']) {
            $search['date'] = rangeDate($search['date']);
            $search['date'] = array('$gte' => $search['date'][0], '$lte' => $search['date'][1]);
        }
        filter_array_element($search);
        $cursor = $table->find($search)->limit($limit)->skip($skip)->sort(array("date" => -1));
        $result = array();
        foreach ($cursor as $item) {
            $item['date'] = date("Y-m-d H:i:s", $item['date']);
            $item['type'] = $agent_type[$item['type']];
            array_push($result, $item);
        }

        $count = $table->count($search);
        $page = new Page($count, C('PAGE_NUM'));
        $page = $page->show();

        $this->assign("page", $page);

        $this->assign("recharge", $result);
        $this->_result['data']['html'] = $this->fetch("Report:recharge");
        $this->_result['data']['recharge'] = $result;
        $this->_result['data']['page'] = $page;
        $this->response($this->_result);
    }
}