<?php
namespace Admin\Controller;
use Common\Controller\BaseController;

class IndexController extends BaseController {

    public function index(){
        $result = $this->mongo_db->admin_user->findOne();
        $this->display("index");
    }
}