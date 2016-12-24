<?php
namespace Admin\Controller;
use Common\Controller\BaseController;

class IndexController extends BaseController {
    public function indexGet(){
        if($_SESSION['token']) {//已登陆
            $this->display("index:index");
        } else {//未登陆
            $this->redirect("index/login");
        }
    }

    public function profileGet() {
        $this->_result['data']['html'] = 'Profile';
        $this->response($this->_result, 'json', 200);
    }

    public function loginGet() {
        if($_SESSION['token']) {
            $this->display("index:index");
        } else {
            $this->display("index:login");
        }
    }
}