<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/22
 * Time: 9:46
 */
namespace Agent\Controller;
use Common\Controller\BaseController;

class UserController extends BaseController
{
    public function usersGet() {
        $this->assign("user", $_SESSION[MODULE_NAME.'_admin']);
        $html = $this->fetch("User:index");
        $this->_result['data']['html'] = $html;
        $this->response($this->_result);
    }

    public function usersPut() {
        $admin_agent = $this->mongo_db->admin_agent;
        $search['_id'] = $_SESSION[MODULE_NAME.'_admin']['_id'];
        $data['name'] = I('put.name', null, check_empty_string);
        $data['password'] = I('put.password', null);
        $data['repeat_password'] = I('put.repeat_password', null);
        $data['old_password'] = I('put.old_password', null);

        $data['status'] = intval(I('put.status', $_SESSION[MODULE_NAME.'_admin']['status']));
        merge_params_error($data['name'], 'name', '名字不能为空', $this->_result['error'],false);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }

        if ($data['old_password']) {
            $user = $admin_agent->findOne(array("username"=>$_SESSION[MODULE_NAME.'_admin']['username']));
            if (!$user || $user['password']!=md5($data['old_password'])) {
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
        if ($admin_agent->update($search,$update)) {
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

        if(!check_verify($code)) {
            $this->response($this->_result, 'json', 400, '验证码错误');
        }

        $admin_agent = $this->mongo_db->admin_agent;
        $query = $admin_agent->findOne($search, $option);
        if (!$query) {
            $this->response($this->_result, 'json', 400, '用户不存在或者密码错误');
        }
        if (!$query['status']) {
            $this->response($this->_result, 'json', 400, '该账户已被禁用');
        }
        //附加字段说明
        $type_list = C('SYSTEM.AGENT_TYPE');
        $query['type_name'] = $type_list[$query['type']];
        //保存用户会话信息
        $_SESSION[MODULE_NAME.'_admin'] = $query;
        //生成token
        $_SESSION[MODULE_NAME.'_token'] = $query['_id'];

        $this->_result['data']['user'] = $query;
        $this->_result['data']['url'] = U(MODULE_NAME . "/Index/index");
        $this->response($this->_result, 'json', 200);
    }

    //用户注销
    public function tokenDelete() {
        unset($_SESSION[MODULE_NAME.'_admin'], $_SESSION[MODULE_NAME.'_token']);
        $this->_result['data']['url'] = U(MODULE_NAME . "/Index/login");
        $this->response(null, 'json', 204);
    }
}