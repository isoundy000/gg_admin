<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/22
 * Time: 9:46
 */
namespace Admin\Controller;
use Common\Controller\BaseController;

class UserController extends BaseController {
    public function usersGet() {
        $this->response($this->_result);
    }

    //用户登录
    public function tokenGet() {
        $search['username'] = I('get.username');
        $search['password'] = MD5(I('get.password'));
        $search['status'] = 1; //已激活用户

        $option['projection'] = array("username"=>1,"status"=>1);

        $admin_user = $this->mongo_db->admin_user;
        $query = $admin_user->findOne($search, $option);
        if (!$query) {
            $this->response($this->_result, 'json', 400, '用户不存在或者密码错误');
        }

        //生成token
        $_SESSION['admin_login'] = $query['_id'];

        $this->_result['data']['user'] = $query;
        $this->_result['data']['url'] = U(MODULE_NAME."/Index/index");
        $this->response($this->_result, 'json', 200);
    }
}