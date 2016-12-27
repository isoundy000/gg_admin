<?php
namespace Agent\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function indexGet(){
        $this->display("Index:index");
    }
}