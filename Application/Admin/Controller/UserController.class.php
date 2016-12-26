<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/22
 * Time: 9:46
 */
namespace Admin\Controller;
use Common\Controller\BaseController;
use MongoDB\BSON\ObjectID;
use Think\Page;

class UserController extends BaseController
{
    public function usersGet() {
        $admin_user = $this->mongo_db->admin_user;

        if (I('get._id')) {
            $search['_id'] = new ObjectID(I('get._id', null));
            $option['projection'] = array();
            $query = $admin_user->findOne($search, $option);
            $this->_result['data']['users'] = $query;
            $this->response($this->_result);

        } else {
            $search = array();
            $option['limit'] = intval(I('get.limit', C('PAGE_NUM')));
            $option['skip'] = (intval(I('get.p', 1)) - 1) * $option['limit'];
            filter_array_element($search);
            filter_array_element($option);

            $cursor = $admin_user->find($search, $option);
            $result = array();
            foreach ($cursor as $item) {
                array_push($result, $item);
            }


            $count = $admin_user->count($search);
            $page = new Page($count, C('PAGE_NUM'));
            $page = $page->show();

            $this->assign("page", $page);
            $this->assign("users", $result);
            $this->_result['data']['html'] = $this->fetch("User:index");

            $this->_result['data']['count'] = $count;
            $this->_result['data']['page'] = $page;
            $this->_result['data']['users'] = $result;

            $this->response($this->_result);
        }
    }

    public function usersPut() {

        $search['_id'] = new ObjectID(I('put._id'));
        $data['sort'] = intval(I('put.sort'));
        $data['name'] = I('put.name', null, check_empty_string);
        $data['action'] = I('put.action', 'javascript:void(0)');
        $data['icon'] = I('put.icon', 'fa-circle');
        $data['pid'] = I('put.pid', '0');

        merge_params_error($data['name'], 'name', '名称不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        filter_array_element($data);

        $update['$set'] = $data;
        $admin_user = $this->mongo_db->admin_user;
        if ($admin_user->findOneAndUpdate($search,$update)) {
            $this->response($this->_result, 'json', 201, '保存成功');
        } else {
            $this->_result['data']['param'] = $data;
            $this->response($this->_result, 'json', 400, '保存失败');
        }

    }

    public function usersPost() {
        $data['sort'] = intval(I('post.sort'));
        $data['name'] = I('post.name', null, check_empty_string);
        $data['action'] = I('post.action', 'javascript:void(0)');
        $data['icon'] = I('post.icon', 'fa-circle');
        $data['pid'] = I('post.pid', '0');
        merge_params_error($data['name'], 'name', '名称不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        filter_array_element($data);

        $admin_user = $this->mongo_db->admin_user;
        if ($admin_user->InsertOne($data)) {
            $this->response($this->_result, 'json', 201, '新建成功');
        } else {
            $this->response($this->_result, 'json', 400, '新建失败');
        }
    }

    public function usersDelete() {
        $search['_id'] = new ObjectID(I('delete._id'));
        $admin_user = $this->mongo_db->admin_user;
        if ($admin_user->deleteOne($search)) {
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
        $search['status'] = 1; //已激活用户

        $option['projection'] = array("username"=>1,
            "name"=>1,
            "date"=>1,
            "role_id"=>1,
            "status"=>1
        );

        $admin_user = $this->mongo_db->admin_user;
        $query = $admin_user->findOne($search, $option);
        if (!$query) {
            $this->response($this->_result, 'json', 400, '用户不存在或者密码错误');
        }
        //保存用户会话信息
        $_SESSION['admin'] = $query;
        //生成token
        $_SESSION['token'] = $query['_id'];

        $this->_result['data']['user'] = $query;
        $this->_result['data']['url'] = U(MODULE_NAME . "/Index/index");
        $this->response($this->_result, 'json', 200);
    }

    //用户注销
    public function tokenDelete() {
        unset($_SESSION['admin'], $_SESSION['token']);
        $this->_result['data']['url'] = U(MODULE_NAME . "/Index/login");
        $this->response(null, 'json', 204);
    }
}