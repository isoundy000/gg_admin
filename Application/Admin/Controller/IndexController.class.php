<?php
namespace Admin\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
        $MongoClient = new \MongoDB\Client(C('MONGO_SERVER'));
        $MongoClient->selectDatabase(C('MONGO_DB'));
        $user = $MongoClient->gg_admin->user;
        $result = $user->findOne();
        var_dump($result);
    }
}