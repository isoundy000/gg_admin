<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/23
 * Time: 7:47
 */
namespace Admin\Controller;
use Think\Controller\RestController;

class TestController extends RestController {
    public function indexGet() {
        //连接数据库
        //$mongo_client = new \MongoClient(C('MONGO_SERVER'));
        //$db_name = C('MONGO_DB');
        unset($_SESSION);

    }

}