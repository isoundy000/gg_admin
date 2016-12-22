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
}