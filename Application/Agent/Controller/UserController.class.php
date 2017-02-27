<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/22
 * Time: 9:46
 */
namespace Agent\Controller;

use Common\Controller\BaseController;
use Think\Page;

class UserController extends BaseController
{
    public function usersGet()
    {
        $user = $_SESSION[MODULE_NAME . '_admin'];
        $agent_type = C('SYSTEM.AGENT_TYPE');
        $user['type_name'] = $agent_type[$user['type']];
        $user['date'] = date("Y-m-d H:i:s", $user['date']);

        $this->assign("user", $user);
        $html = $this->fetch("User:index");
        $this->_result['data']['html'] = $html;
        $this->response($this->_result);
    }

    public function usersPut()
    {
        $admin_agent = $this->mongo_db->admin_agent;
        $search['_id'] = $_SESSION[MODULE_NAME . '_admin']['_id'];
        $data['name'] = I('put.name', null, check_empty_string);
        $data['bank_name'] = I('put.bank_name', null, check_empty_string);
        $data['bank_card'] = I('put.bank_card', null, check_empty_string);
        $data['real_name'] = I('put.real_name', null, check_empty_string);
        $data['wechat'] = I('put.wechat', null, check_empty_string);
        $data['password'] = I('put.password', null);
        $data['repeat_password'] = I('put.repeat_password', null);
        $data['old_password'] = I('put.old_password', null);

        $data['status'] = intval(I('put.status', $_SESSION[MODULE_NAME . '_admin']['status']));
        merge_params_error($data['name'], 'name', '名字不能为空', $this->_result['error'], false);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }

        if ($data['old_password']) {
            $user = $admin_agent->findOne(array("username" => $_SESSION[MODULE_NAME . '_admin']['username']));
            if (!$user || $user['password'] != md5($data['old_password'])) {
                $this->response($this->_result, 'json', 400, '旧密码错误');
            }
        }

        if ($data['password'] && $data['repeat_password']) {
            if ($data['password'] != $data['repeat_password']) {
                $this->response($this->_result, 'json', 400, '两次输入的密码不一致');
            }

            if (checkTextLength6($data['password']) || checkTextLength6($data['repeat_password'])) {
                $this->response($this->_result, 'json', 400, '密码至少6个字符');
            }
            unset($data['repeat_password']);
            $data['password'] = md5($data['password']);
        }
        if (isset($data['password']) && !$data['password']) {
            unset($data['password']);
        }
        if (isset($data['repeat_password']) && !$data['repeat_password']) {
            unset($data['repeat_password']);
        }
        if (isset($data['old_password']) && !$data['old_password']) {
            unset($data['old_password']);
        }

        filter_array_element($data);

        $update['$set'] = $data;
        if ($agent = $admin_agent->findAndModify($search, $update, null, array('new' => true))) {
            unset($agent['password']);
            $agent['date'] = date("Y-m-d H:i:s", $agent['date']);
            $_SESSION[MODULE_NAME . '_admin'] = $agent;
            $this->response($this->_result, 'json', 201, '保存成功');
        } else {
            $this->_result['data']['param'] = $data;
            $this->response($this->_result, 'json', 400, '保存失败');
        }

    }


    //用户登录
    public function tokenGet()
    {
        $search['username'] = I('get.username');
        $search['password'] = MD5(I('get.password'));
        $code = I('get.verify');

        $option = array(
            'username' => 1,
            'name' => 1,
            'status' => 1,
            'level' => 1,
            'type' => 1,
            'role_id' => 1,
            'date' => 1,
        );

        if (!check_verify($code)) {
            $this->response($this->_result, 'json', 400, '验证码错误');
        }

        $admin_agent = $this->mongo_db->admin_agent;
        $query = $admin_agent->findOne($search, $option);
        if (!$query) {
            $this->response($this->_result, 'json', 400, '用户不存在或者密码错误');
        }
        if (!$query['status']) {
            $this->response($this->_result, 'json', 400, '你的账号未认证或者已被禁用，请联系管理员开通');
        }
        //附加字段说明
        $type_list = C('SYSTEM.AGENT_TYPE');
        $query['type_name'] = $type_list[$query['type']];
        //保存用户会话信息
        $_SESSION[MODULE_NAME . '_admin'] = $query;
        //生成token
        $_SESSION[MODULE_NAME . '_token'] = $query['_id'];

        $this->_result['data']['user'] = $query;
        $this->_result['data']['url'] = U(MODULE_NAME . "/Index/index");
        $this->response($this->_result, 'json', 200);
    }

    //用户注销
    public function tokenDelete()
    {
        unset($_SESSION[MODULE_NAME . '_admin'], $_SESSION[MODULE_NAME . '_token']);
        $this->_result['data']['url'] = U(MODULE_NAME . "/Index/login");
        $this->response(null, 'json', 204);
    }

    //二级代理
    public function agentsGet()
    {
        $admin_agent = $this->mongo_db->admin_agent;

        $agent_type = C('SYSTEM.AGENT_TYPE');
        $stock_type = C('SYSTEM.STOCK_TYPE');
        $this->_result['data']['agent_type'] = $agent_type;
        $this->assign("stock_type", $stock_type);


        $search = array();
        $search['username'] = I('get.username', null);
        $search['pid'] = $_SESSION[MODULE_NAME . '_admin']['_id']->__toString();
        $search['date'] = I('get.date', null);
        $search['status'] = array('$ne' => 2);
        if ($search['date']) {
            $search['date'] = rangeDate($search['date']);
            $search['date'] = array('$gte' => $search['date'][0], '$lte' => $search['date'][1]);
        }

        $limit = intval(I('get.limit', C('PAGE_NUM')));
        $skip = (intval(I('get.p', 1)) - 1) * $limit;
        filter_array_element($search);
        filter_array_element($option);

        $cursor = $admin_agent->find($search)->sort(array('date' => -1))->limit($limit)->skip($skip);
        $result = array();
        foreach ($cursor as $item) {
            $item['type_name'] = $agent_type[$item['type']];
            $item['date'] = date('Y-m-d H:i:s', $item['date']);
            $item['amount'] = array_reduce(array_values($item['stock_amount']), function($a, $b) {
                return $a + $b;
            }, 0);
            array_push($result, $item);
        }

        $count = $admin_agent->count($search);
        $page = new Page($count, $limit);
        $page = $page->show();

        $this->assign("page", $page);

        $this->assign("agent_type", $agent_type);
        if (I('get.tab') == 'card') {//代理充卡页面
            $this->assign("agent", $result[0]);
            $this->_result['data']['html'] = $this->fetch("User:card");
        } else {
            $this->assign("agents", $result);
            $this->_result['data']['html'] = $this->fetch("User:agent");
        }

        $this->_result['data']['count'] = $count;
        $this->_result['data']['page'] = $page;
        $this->_result['data']['agents'] = $result;

        $this->response($this->_result);
    }

    //售卡情况
    public function sellGet()
    {
        //$admin_agent = $this->mongo_db->admin_agent;
        $agent = $_SESSION[MODULE_NAME . '_admin'];
        $stock_amount = $agent['card_amount'];
        $total_amount = 0;
        $total_sell = 0;
        $this_month_buy = 0;
        $this_month_sell = 0;
        $this_month_back = 0;
        $last_month_buy = 0;
        $last_month_sell = 0;
        $last_month_back = 0;
        foreach ($agent['total_amount'] as $item) {
            $total_amount += $item;
        }
        foreach ($agent['total_sell'] as $item) {
            $total_sell += $item;
        }

        //当月数据
        $agent_stock_grant_record = $this->mongo_db->agent_stock_grant_record;//花费
        $start_date = strtotime(date("Y-m-01", time()));
        $end_date = time();
        $cursor = $agent_stock_grant_record->find(array(
                'date' => array(
                    '$gte' => $start_date,
                    '$lt' => $end_date,
                ),
                'from_user' => $agent['username']
            )
        );
        foreach ($cursor as $item) {
            $this_month_sell += $item['amount'];
        }

        $admin_stock_grant_record = $this->mongo_db->admin_stock_grant_record;//购买
        $cursor = $admin_stock_grant_record->find(
            array(
                'date' => array(
                    '$gte' => $start_date,
                    '$lt' => $end_date,
                ),
                'to_user' => $agent['username']
            )
        );
        foreach ($cursor as $item) {
            $this_month_buy += $item['amount'];
        }

        //上月数据
        $last_month = strtotime(date("Y-m-01", strtotime("-1 month")));
        $stream = $this->mongo_db->admin_report_agent_stream_month->findOne(array('date' => $last_month, 'username' => $agent['username']));
        if ($stream) {
            $last_month_buy = $stream['purchase'];
            $last_month_back = $stream['pay_back'];
            $last_month_sell = $stream['expense'];
        }
        $data = array(
            'stock_amount' => $stock_amount,
            'total_amount' => $total_amount,
            'total_sell' => $total_sell,
            'this_month_buy' => $this_month_buy,
            'this_month_sell' => $this_month_sell,
            'this_month_back' => $this_month_back,
            'last_month_buy' => $last_month_buy,
            'last_month_sell' => $last_month_sell,
            'last_month_back' => $last_month_back,
        );
        $this->_result['data']['sell'] = $data;
        $this->assign("data", $data);
        $this->_result['data']['html'] = $this->fetch("User:sell");
        $this->assign("html", $this->_result['data']['html']);
        $this->response($this->_result);
    }

    //二级代理售卡情况，即取月统计
    public function agentSellGet() {
        $search = array();
        $admin_agent = $this->mongo_db->admin_agent;
        $agents = $admin_agent->find(array('pid' => $_SESSION[MODULE_NAME . '_admin']['_id']->__toString()));
        $agent_list = array();
        foreach ($agents as $item) {
            array_push($agent_list, $item['username']);
        }
        $search['username'] = array('$in' => $agent_list);
        $_GET['username'] && $search['username'] = I('get.username');

        $agent_type = C('SYSTEM.AGENT_TYPE');
        $this->assign("agent_type", $agent_type);
        $game_type = C('SYSTEM.GAME');
        $this->assign("game_type", $game_type);

        $type = I('get.type', 'month');
        switch ($type) {
            case 'day':
                break;
            case 'month':
                break;
        }
        $table_name = "admin_report_agent_stream_" . $type;
        $table = $this->mongo_db->$table_name;

        $limit = intval(I('get.limit', C('PAGE_NUM')));
        $skip = (intval(I('get.p', 1)) - 1) * $limit;
        $search['date'] = I('get.date', null);
        $search['type'] = I('get.agent_type', null);
        if ($search['date']) {
            $search['date'] = rangeDate($search['date']);
            $search['date'] = array('$gte' => $search['date'][0], '$lte' => $search['date'][1]);
        }/* else {
            //上月数据
            $search['date'] = strtotime(date("Y-m-01", strtotime("-1 month")));
        }*/
        $search['type'] && $search['type'] = intval($search['type']);
        filter_array_element($search);
        $cursor = $table->find($search)->limit($limit)->skip($skip)->sort(array("username" => 1));
        $result = array();
        $total = array(
            'pay_back' => 0,
            'expense' => 0,
            'purchase' => 0,
        );
        foreach ($cursor as $item) {
            if ($item['expense'] >= 2000) {
                $item['pay_back'] = intval($item['expense'] * 0.5);
            } else {
                $item['pay_back'] = intval($item['expense'] * 0.7);
            }
            $item['date'] = date("Y-m", $item['date']);
            $total['pay_back'] += $item['pay_back'];
            $total['expense'] += $item['expense'];
            $total['purchase'] += $item['purchase'];
            $item['type_name'] = $agent_type[$item['type']];
            array_push($result, $item);
        }

        $count = $table->count($search);
        $page = new Page($count, $limit);
        $page = $page->show();

        $this->assign("page", $page);
        $this->assign("stream", $result);
        $this->assign("total", $total);
        $this->assign("type", $type);
        $this->_result['data']['html'] = $this->fetch("User:agent_stream");
        $this->_result['data']['total'] = $total;
        $this->_result['data']['stream'] = $result;
        $this->_result['data']['page'] = $page;
        $this->response($this->_result);
    }

    public function agentSellExcelPost() {
        $search = array();
        $admin_agent = $this->mongo_db->admin_agent;
        $agents = $admin_agent->find(array('pid' => $_SESSION[MODULE_NAME . '_admin']['_id']->__toString()));
        $agent_list = array();
        foreach ($agents as $item) {
            array_push($agent_list, $item['username']);
        }
        $search['username'] = array('$in' => $agent_list);
        $_GET['username'] && $search['username'] = I('get.username');

        $agent_type = C('SYSTEM.AGENT_TYPE');
        $this->assign("agent_type", $agent_type);
        $game_type = C('SYSTEM.GAME');
        $this->assign("game_type", $game_type);

        $type = I('get.type', 'month');
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
        }/* else {
            //上月数据
            $search['date'] = strtotime(date("Y-m-01", strtotime("-1 month")));
        }*/
        if ($search['username'] || $search['type']) {
            $limit = null;
            $skip = null;
        }
        $search['type'] && $search['type'] = intval($search['type']);
        filter_array_element($search);
        $cursor = $table->find($search)->limit($limit)->skip($skip)->sort(array("username" => 1));
        $total = array(
            'pay_back' => 0,
            'expense' => 0,
            'purchase' => 0,
        );
        $option['filename'] = "金牌代理月报表" . date("Y-m") . ".xls";
        $option['author'] = '杠杠麻将';
        $option['header'] = array('时间', '账号', '昵称', '购买', '售出', '返还');
        $option['data'] = array();
        foreach ($cursor as $item) {
            $item['date'] = date("Y-m", $item['date']);
            $total['pay_back'] += $item['pay_back'];
            $total['expense'] += $item['expense'];
            $total['purchase'] += $item['purchase'];
            $item['type_name'] = $agent_type[$item['type']];
            array_push($option['data'], array($item['date'], $item['username'], $item['name'],
                 $item['purchase'], $item['expense'], $item['pay_back']));
        }
        array_push($option['data'], array('总计', '', '', $total['purchase'], $total['expense'], $total['pay_back']));
        excelExport($option);
    }

    public function agentsPut() {
        $search['_id'] = new \MongoId(I('put._id'));
        $data['wechat'] = I('put.wechat', null, check_empty_string);
        $data['name'] = I('put.name', null, check_empty_string);
        merge_params_error($data['wechat'], 'wechat', '微信号不能为空', $this->_result['error'], false);
        merge_params_error($data['name'], 'name', '昵称不能为空', $this->_result['error'], false);
        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        filter_array_element($data);

        $data && $update['$set'] = $data; //data不能为空
        $admin_agent = $this->mongo_db->admin_agent;
        $agent_stock_grant_record = $this->mongo_db->agent_stock_grant_record;
        $stock_type = 2; //优化发放活动房卡
        //充卡
        $amount = intval(I('put.amount'));
        if ($amount>0) {
            if (!check_nature_integer($amount)) {
                $this->response($this->_result, 'json', 400, '房卡数量必须为正整数');
            } else {
                //库存是否充足
                $user = $admin_agent->findOne(array("_id" => $_SESSION[MODULE_NAME . '_admin']['_id']));
                if ($user['stock_amount'][$stock_type] < $amount) {
                    $stock_type = 1;//选择普通房卡
                    if ($user['stock_amount'][$stock_type] < $amount) {
                        $this->response($this->_result, 'json', 400, '房卡库存不足');
                    }
                }
                $update['$inc'] = array("stock_amount.{$stock_type}" => $amount,
                                        "total_amount.{$stock_type}" => $amount);
            }
            $client = $admin_agent->findOne($search);
        }
        if ($admin_agent->update($search,$update)) {
            if ($amount > 0) {
                //给代理充卡后要扣除代理相应的库存卡数量
                $admin_agent->update(array("_id" => $_SESSION[MODULE_NAME . '_admin']['_id']),
                    array('$inc' => array("stock_amount.{$stock_type}" => -$amount,
                        "total_sell.{$stock_type}" => $amount))
                );
                //充卡记录，代理后台使用
                $agent_stock_grant_record->insert(
                    array(
                        'from_user' => $_SESSION[MODULE_NAME . '_admin']['username'],
                        'to_user' => $client['username'],
                        'nickname' => $client['name'],
                        'type' => $stock_type,
                        'amount' => $amount,
                        'date' => time(),
                    )
                );
            }
            $this->response($this->_result, 'json', 201, '保存成功');
        } else {
            $this->_result['data']['param'] = $data;
            $this->response($this->_result, 'json', 400, '保存失败');
        }
    }

    public function agentsPost()
    {
        $admin_agent = $this->mongo_db->admin_agent;
        $data['cellphone'] = I('post.cellphone', null, check_empty_string);
        $data['username'] = $data['cellphone'];
        $data['wechat'] = I('post.wechat', null, check_empty_string);
        $data['name'] = I('post.name', null, check_empty_string);
        $data['password'] = I('post.password', null, check_empty_string);
        $data['repeat_password'] = I('post.repeat_password', null, check_empty_string);
        $data['type'] = 2; //只能添加金牌代理
        $data['verify_code'] = I('post.verify_code', null, check_empty_string);
        $data['status'] = 0; //二级代理需要认证
        $data['pid'] = $_SESSION[MODULE_NAME . '_admin']['_id']->__toString();
        //$data['role_id'] = $_SESSION[MODULE_NAME.'_admin']['role_id'];
        //金牌代理组权限
        $admin_role = $this->mongo_db->admin_role;
        $role = $admin_role->findOne(array(
            '_id' => array('$ne' => $_SESSION[MODULE_NAME . '_admin']['role_id']),
            'module_name' => 'Agent',
        ));
        $data['role_id'] = $role['_id'];
        $data['date'] = time();
        merge_params_error($data['cellphone'], 'cellphone', '手机号码不能为空', $this->_result['error']);
        merge_params_error($data['wechat'], 'wechat', '微信号不能为空', $this->_result['error']);
        merge_params_error($data['name'], 'name', '昵称不能为空', $this->_result['error']);
        merge_params_error($data['verify_code'], 'verify_code', '验证码不能为空', $this->_result['error']);
        merge_params_error($data['password'], 'password', '密码不能为空', $this->_result['error']);
        merge_params_error($data['repeat_password'], 'repeat_password', '密码不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }

        if (checkTextLength6($data['username'])) {
            $this->response($this->_result, 'json', 400, '用户名至少6个字符');
        }

        if ($data['password'] != $data['repeat_password']) {
            $this->response($this->_result, 'json', 400, '两次输入的密码不一致');
        }

        if (checkTextLength6($data['password']) || checkTextLength6($data['repeat_password'])) {
            $this->response($this->_result, 'json', 400, '密码至少6个字符');
        }

        if (findRecord('username', $data['username'], $admin_agent)) {
            $this->response($this->_result, 'json', 400, '用户名已经存在');
        }

        if (!isset($_SESSION['phone'.$data['cellphone']])) {
            $this->response($this->_result, 'json', 400, '验证码已过期');
        }

        if ($_SESSION['phone'.$data['cellphone']] != $data['verify_code']) {
            $this->response($this->_result, 'json', 400, '验证码不正确');
        }

        if (findRecord('cellphone', $data['cellphone'], $admin_agent)) {
            $this->response($this->_result, 'json', 400, '手机号码已经存在');
        }

        filter_array_element($data);

        $data['password'] = md5($data['password']);
        unset($data['repeat_password']);
        unset($data['verify_code']);

        if ($admin_agent->insert($data)) {
            $this->_result['data']['url'] = U(MODULE_NAME . '/user/agents');
            unset($_SESSION['phone'.$data['cellphone']]);
            $this->response($this->_result, 'json', 201, '新建成功');
        } else {
            $this->response($this->_result, 'json', 400, '新建失败');
        }
    }

    //删除
    public function agentDelete() {
        $this->response($this->_result, 'json', 204);
    }

    public function verifyCodeGet()
    {
        $cellphone = I('get.cellphone');
        if (!check_cellphone_format($cellphone)) {
            $this->response($this->_result, 'json', 400, '手机号码格式错误');
        }
        $random_code = buildRandomCode();
        require 'ThinkPHP/Library/Think/Dayu/TopSdk.php';
        $c = new \TopClient();
        $c->appkey = C('DAYU.APP_ID');
        $c->secretKey = C('DAYU.APP_SECRET');
        $req = new \AlibabaAliqinFcSmsNumSendRequest();
        $req->setExtend("ggmj");
        $req->setSmsType("normal");
        $req->setSmsFreeSignName(C('DAYU.SIGN_NAME'));
        $param = "{\"verify_code\":\"{$random_code}\"}";
        $req->setSmsParam($param);
        $req->setRecNum($cellphone);
        $req->setSmsTemplateCode(C("DAYU.TEMPLATE_CODE"));
        $resp = $c->execute($req);
        if ($resp->result && $resp->result->err_code == "0") {
            //设置$_SESSION
            $_SESSION['phone'.$cellphone] = $random_code;
            //session($cellphone, $random_code);
            $this->response($this->_result, 'json', 200, '发送成功');
        } else {
            $this->response($this->_result, 'json', 400, '发送失败，请稍后重试');
        }
    }
}