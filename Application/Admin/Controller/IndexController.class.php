<?php
namespace Admin\Controller;
use Common\Controller\BaseController;

class IndexController extends BaseController {
    public function indexGet(){
        if($_SESSION['token']) {//已登陆
            $this->display("Index:index");
        } else {//未登陆
            $this->redirect("Index/login");
        }
    }

    public function profileGet() {
        $this->_result['data']['html'] = 'Profile';
        $this->response($this->_result, 'json', 200);
    }

    public function loginGet() {
        if($_SESSION['token']) {
            $this->display("Index:index");
        } else {
            $this->display("Index:login");
        }
    }
}