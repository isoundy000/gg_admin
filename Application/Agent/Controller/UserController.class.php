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
            $_SESSION[MODULE_NAME.'_admin'] = $agent;
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
        $search['pid'] = $_SESSION[MODULE_NAME.'_admin']['_id']->__toString();
        $search['date'] = I('get.date', null);
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
            array_push($result, $item);
        }

        $count = $admin_agent->count($search);
        $page = new Page($count, C('PAGE_NUM'));
        $page = $page->show();

        $this->assign("page", $page);
        $this->assign("agents", $result);
        $this->assign("agent_type", $agent_type);
        $this->_result['data']['html'] = $this->fetch("User:agent");

        $this->_result['data']['count'] = $count;
        $this->_result['data']['page'] = $page;
        $this->_result['data']['agents'] = $result;

        $this->response($this->_result);
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
        $data['pid'] = $_SESSION[MODULE_NAME.'_admin']['_id']->__toString();
        //$data['role_id'] = $_SESSION[MODULE_NAME.'_admin']['role_id'];
        //金牌代理组权限
        $admin_role = $this->mongo_db->admin_role;
        $role = $admin_role->findOne(array(
            '_id' => array('$ne' => $_SESSION[MODULE_NAME.'_admin']['role_id']),
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

        if (checkTextLength6($data['password'])||checkTextLength6($data['repeat_password'])) {
            $this->response($this->_result, 'json', 400, '密码至少6个字符');
        }

        if (findRecord('username', $data['username'], $admin_agent)) {
            $this->response($this->_result, 'json', 400, '用户名已经存在');
        }

        if (!session($data['cellphone'])) {
            $this->response($this->_result, 'json', 400, '验证码已过期');
        }

        if (session($data['cellphone']) != $data['verify_code']) {
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
            $this->_result['data']['url'] = U(MODULE_NAME.'/user/agents');
            $this->response($this->_result, 'json', 201, '新建成功');
        } else {
            $this->response($this->_result, 'json', 400, '新建失败');
        }
    }

    public function verifyCodeGet() {
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
            session($cellphone, $random_code);
            $this->response($this->_result, 'json', 200, '发送成功');
        } else {
            $this->response($this->_result, 'json', 400, '发送失败，请稍后重试');
        }
    }
}