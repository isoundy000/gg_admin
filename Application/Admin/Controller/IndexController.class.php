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

    public function loginGet() {
        if($_SESSION['token']) {
            $this->display("index:index");
        } else {
            layout(false);
            $this->display("index:login");
        }
    }
}