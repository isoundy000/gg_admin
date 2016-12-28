<?php
namespace Agent\Controller;
use Common\Controller\BaseController;
class IndexController extends BaseController {
    public function indexGet(){
        if($_SESSION[MODULE_NAME.'_token']) {//已登陆
            $this->display("Index:index");
        } else {//未登陆
            $this->redirect(MODULE_NAME."/Index/login");
        }
    }

    public function profileGet() {
        $this->_result['data']['html'] = 'Profile';
        $this->response($this->_result, 'json', 200);
    }

    public function loginGet() {
        if($_SESSION[MODULE_NAME.'_token']) {
            $this->display("Index:index");
        } else {
            $this->display("Index:login");
        }
    }
}