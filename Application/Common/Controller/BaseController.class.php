<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/22
 * Time: 7:23
 */
namespace Common\Controller;
use MongoDB\Client;
use Think\Controller\RestController;

class BaseController extends RestController {
    protected $mongo_client; //mongo 实例
    protected $mongo_db; //mongoDB

    public function _initialize() {
        $this->mongo_client = new Client(C('MONGO_SERVER'));
        $this->mongo_db = $this->mongo_client->selectDatabase(C('MONGO_DB'));
    }
}