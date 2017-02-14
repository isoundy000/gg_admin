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
            $match = $type=='month' ? 'Y-m' : 'Y-m-d';
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

    public function streamExcelPost() {
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
        $skip = $_SESSION['skip'];
        $skip = ($skip - 1) * $limit;
        $search['date'] = I('get.date', null);

        if ($search['date']) {
            $search['date'] = rangeDate($search['date']);
            $search['date'] = array('$gte' => $search['date'][0], '$lte' => $search['date'][1]);
            $limit = null;
            $skip = null;
        }
        filter_array_element($search);
        $cursor = $table->find($search)->limit($limit)->skip($skip)->sort(array("date"=>-1));
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


        if ($type == 'retail') {
            $option['header'] = array('日期', '游戏', '购卡量', '流水');
        } else {
            $option['header'] = array('日期', '游戏', '购卡量', '', '消耗', '流水');
            $option['merge'] = array('C1:D1');//购卡量与''合并
        }

        $option['data'] = array();

        foreach ($cursor as $item) {
            $match = $type=='month' ? 'Y-m' : 'Y-m-d';
            $item['date'] = date($match, $item['date']);
            $item['game'] = $game_type[$item['game']];
            if ($type == 'retail') {
                $total['card'] += $item['buy_card'];
            }
            foreach ($item['buy_card'] as $k => $v) {
                $total['buy_card'][$k]['amount'] += $v;
            }
            $total['expense'] += $item['expense'];
            $total['stream'] += $item['stream'];
            $current = array($item['date'], $item['game'], $item['buy_card'][1], $item['buy_card'][2], $item['expense'], $item['stream']);
            if ($type == 'retail') {
                $current = array($item['date'], $item['game'], $total['card'], $item['stream']);
            }
            array_push($option['data'], $current);
        }
        //总计
        if ($type == 'retail') {
            array_push($option['data'], array('总计', '', $total['card'], $total['stream']));
        } else {
            array_push($option['data'], array('总计', '', $total['buy_card'][1]['amount'],
                $total['buy_card'][2]['amount'], $total['expense'], $total['stream']));
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
            $parent_user = $this->mongo_db->admin_agent->findOne(array('username' => $search['username']));
            $parent_user_id = $parent_user['_id']->__toString();
            $child_list = $this->mongo_db->admin_agent->find(array('pid' => $parent_user_id));
            $child_username_list = array();
            foreach ($child_list as $item) {
                array_push($child_username_list, $item['username']);
            }
            $child_username_list = array_values($child_username_list);
            $search['username'] = array('$in' => $child_username_list);
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
            $show_child && $item['parent'] = I('get.username');
            array_push($result, $item);
        }

        $count = $table->count($search);
        $page = new Page($count, C('PAGE_NUM'));
        $page = $page->show();

        $this->assign("page", $page);

        $this->assign("stream", $result);
        if ($show_child) {
            $this->assign("child_stream", $result);
            $this->_result['data']['child_html'] = $this->fetch("Report:agent_child_stream");
        }
        $this->assign("total", $total);
        $this->assign("type", $type);
        $this->_result['data']['html'] = $this->fetch("Report:agent_stream");
        $this->_result['data']['total'] = $total;
        $this->_result['data']['stream'] = $result;
        $this->_result['data']['page'] = $page;
        $this->response($this->_result);
    }

    public function agentStreamExcelPost() {
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
        $skip = $_SESSION['skip'];
        $skip = ($skip - 1) * $limit;
        $search['date'] = I('get.date', null);
        $search['username'] = I('get.username', null);
        $search['type'] = I('get.agent_type', null);
        if ($search['date']) {
            $search['date'] = rangeDate($search['date']);
            $search['date'] = array('$gte' => $search['date'][0], '$lte' => $search['date'][1]);
            $limit = null;
            $skip = null;
        } else {
            //上月数据
            $search['date'] = strtotime(date("Y-m-01", strtotime("-1 month")));
        }
        if ($search['username'] || $search['type']) {
            $limit = null;
            $skip = null;
        }
        $search['type'] && $search['type'] = intval($search['type']);
        $show_child = I('get.show_child');//是否显示账号下的子账号
        if ($show_child) {
            //父账号_id
            $parent_user = $this->mongo_db->admin_agent->findOne(array('username' => $search['username']));
            $parent_user_id = $parent_user['_id']->__toString();
            $child_list = $this->mongo_db->admin_agent->find(array('pid' => $parent_user_id));
            $child_username_list = array();
            foreach ($child_list as $item) {
                array_push($child_username_list, $item['username']);
            }
            $child_username_list = array_values($child_username_list);
            $search['username'] = array('$in' => $child_username_list);
        }
        filter_array_element($search);
        $cursor = $table->find($search)->limit($limit)->skip($skip)->sort(array("username" => 1));
        $total = array(
            'pay_back' => 0,
            'expense' => 0,
            'purchase' => 0,
        );
        $option['filename'] = "代理月报表" . date("Y-m") . ".xls";
        $option['author'] = '杠杠麻将';
        $option['header'] = array('时间', '账号', '微信', '级别', '返还', '购买', '消耗');
        $option['data'] = array();
        foreach ($cursor as $item) {
            $item['date'] = date("Y-m", $item['date']);
            $total['pay_back'] += $item['pay_back'];
            $total['expense'] += $item['expense'];
            $total['purchase'] += $item['purchase'];
            $item['type_name'] = $agent_type[$item['type']];
            $show_child && $item['parent'] = I('get.username');
            array_push($option['data'], array($item['date'], $item['username'], $item['wechat'], $item['type_name'],
            $item['pay_back'], $item['purchase'], $item['expense']));
        }
        array_push($option['data'], array('总计', '', '', '', $total['pay_back'], $total['purchase'], $total['expense']));
        excelExport($option);
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

    public function rechargeExcelPost() {
        $agent_type = C('SYSTEM.AGENT_TYPE');
        $this->assign("agent_type", $agent_type);
        $game_type = C('SYSTEM.GAME');
        $this->assign("game_type", $game_type);

        $table_name = "agent_recharge_order";
        $table = $this->mongo_db->$table_name;

        $search = array();
        $limit = intval(I('get.limit', C('PAGE_NUM')));
        $skip = $_SESSION['skip'];
        $skip = ($skip - 1) * $limit;
        $search['date'] = I('get.date', null);
        $search['username'] = I('get.username', null);
        $search['status'] = 1; //支付已成功
        if ($search['date']) {
            $search['date'] = rangeDate($search['date']);
            $search['date'] = array('$gte' => $search['date'][0], '$lte' => $search['date'][1]);
            $skip = null;
            $limit = null;
        }
        if ($search['username']) {
            $skip = null;
            $limit = null;
        }
        filter_array_element($search);
        $cursor = $table->find($search)->limit($limit)->skip($skip)->sort(array("date" => -1));
        $option['filename'] = "充值记录报表" . date("Y-m-d") . ".xls";
        $option['author'] = '杠杠麻将';
        $option['header'] = array('充值时间', '订单号', '账号', '微信', '级别', '购买数量');
        $option['data'] = array();
        foreach ($cursor as $item) {
            $item['date'] = date("Y-m-d H:i:s", $item['date']);
            $item['type'] = $agent_type[$item['type']];
            array_push($option['data'], array($item['date'], $item['sys_order_id'], $item['username'],
                $item['wechat'], $item['type'], $item['card_amount']));
        }
        excelExport($option);
    }
}