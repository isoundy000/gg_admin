<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/22
 * Time: 9:46
 */
namespace Admin\Controller;
use Common\Controller\BaseController;

class UserController extends BaseController
{
    public function usersGet()
    {
        $search['_id'] = I('get._id');
        $option = array();
        filter_array_element($search);
        $admin_user = $this->mongo_db->admin_user;
        $query = $admin_user->findOne($search, $option);
        $this->_result['data']['user'] = $query;
        $this->_result['data']['html'] = 'Hello User';
        $this->response($this->_result);
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
        unset($query['_id']);

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