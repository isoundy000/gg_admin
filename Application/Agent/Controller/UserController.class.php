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
    public function usersGet() {
        $admin_agent = $this->mongo_db->admin_agent;
        $admin_role = $this->mongo_db->admin_role;
        if (I('get._id')) {
            $search['_id'] = new \MongoId(I('get._id', null));
            $option = array('password' => 0);
            $query = $admin_agent->findOne($search, $option);
            $this->_result['data']['users'] = $query;

        } else {
            $search = array();
            $limit = intval(I('get.limit', C('PAGE_NUM')));
            $skip = (intval(I('get.p', 1)) - 1) * $limit;
            filter_array_element($search);
            filter_array_element($option);

            $cursor = $admin_agent->find($search)->limit($limit)->skip($skip);
            $result = array();
            foreach ($cursor as $item) {
                $role = $admin_role->findOne(array('_id' => $item['role_id']),array('name'=>1));
                $item['role_name'] = $role['name'];
                array_push($result, $item);
            }

            $count = $admin_agent->count($search);
            $page = new Page($count, C('PAGE_NUM'));
            $page = $page->show();

            //role list
            $role_cursor = $admin_role->find();
            $roles = array();
            foreach($role_cursor as $item) {
                array_push($roles, $item);
            }

            $this->assign("roles", $roles);
            $this->assign("page", $page);
            $this->assign("users", $result);
            $this->_result['data']['html'] = $this->fetch("User:index");

            $this->_result['data']['count'] = $count;
            $this->_result['data']['page'] = $page;
            $this->_result['data']['users'] = $result;
            $this->_result['data']['roles'] = $roles;
        }
        $this->response($this->_result);
    }

    public function usersPut() {

        $search['_id'] = new \MongoId(I('put._id'));
        $data['name'] = I('put.name', null, check_empty_string);
        $data['role_id'] = I('put.role_id', null, check_empty_string);
        $data['password'] = I('put.password', null);
        $data['repeat_password'] = I('put.repeat_password', null);
        $data['status'] = intval(I('put.status'));
        merge_params_error($data['name'], 'name', '名字不能为空', $this->_result['error'],false);
        merge_params_error($data['role_id'], 'role_id', '权限组不能为空', $this->_result['error']);
        merge_params_error($data['password'], 'password', '密码不能为空', $this->_result['error'], false);
        merge_params_error($data['repeat_password'], 'repeat_password', '密码不能为空', $this->_result['error'], false);
        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }

        if (checkTextLength6($data['name'])) {
            $this->response($this->_result, 'json', 400, '用户名至少6个字符');
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
        $data['role_id'] = new \MongoId($data['role_id']);
        filter_array_element($data);

        $update['$set'] = $data;
        $admin_agent = $this->mongo_db->admin_agent;
        if ($admin_agent->update($search,$update)) {
            $this->response($this->_result, 'json', 201, '保存成功');
        } else {
            $this->_result['data']['param'] = $data;
            $this->response($this->_result, 'json', 400, '保存失败');
        }

    }

    public function usersPost() {
        $admin_agent = $this->mongo_db->admin_agent;

        $data['username'] = I('post.username');
        $data['name'] = I('post.name', null, check_empty_string);
        $data['password'] = I('post.password', null, check_empty_string);
        $data['repeat_password'] = I('post.repeat_password', null, check_empty_string);
        $data['role_id'] = I('post.role_id', null, check_empty_string);
        $data['status'] = intval(I('post.status'));
        $data['date'] = date("Y-m-d H:i:s", time());
        merge_params_error($data['name'], 'name', '名字不能为空', $this->_result['error']);
        merge_params_error($data['password'], 'password', '密码不能为空', $this->_result['error']);
        merge_params_error($data['repeat_password'], '密码不能为空', 'repeat_password', $this->_result['error']);
        merge_params_error($data['role_id'], 'role_id', '请选择权限组', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }

        if (checkTextLength6($data['username'])) {
            $this->response($this->_result, 'json', 400, 'username length less 6');
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

        filter_array_element($data);

        $data['role_id'] = new \MongoId($data['role_id']);
        $data['password'] = md5($data['password']);
        unset($data['repeat_password']);

        if ($admin_agent->insert($data)) {
            $this->_result['data']['url'] = U(MODULE_NAME.'/user/users');
            $this->response($this->_result, 'json', 201, '新建成功');
        } else {
            $this->response($this->_result, 'json', 400, '新建失败');
        }
    }

    public function usersDelete() {
        $search['_id'] = new \MongoId(I('delete._id'));
        $admin_agent = $this->mongo_db->admin_agent;
        if ($admin_agent->remove($search)) {
            $this->response($this->_result, 'json', 204, '删除成功');
        } else {
            $this->response($this->_result, 'json', 400, '删除失败');
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