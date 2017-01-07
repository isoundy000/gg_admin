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
        require 'ThinkPHP/Library/Think/Dayu/TopSdk.php';
        $c = new \TopClient();
        $c ->appkey = '23594607' ;
        $c ->secretKey = '95436c0d9318109e10dffa7fdaf34914' ;
        $req = new \AlibabaAliqinFcSmsNumSendRequest();
        $req ->setExtend("1234" );
        $req ->setSmsType( "normal" );
        $req ->setSmsFreeSignName( "捷叔叔" );
        $req ->setSmsParam( "{verify_code:'131420'}" );
        $req ->setRecNum( "13512782792" );
        $req ->setSmsTemplateCode('SMS_39325049');
        $resp = $c ->execute( $req );
        echo json_encode($resp);
    }

}