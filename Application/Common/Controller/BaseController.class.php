<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/22
 * Time: 7:23
 */
namespace Common\Controller;
use MongoDB\Client;
//use MongoDB\Driver\Manager;
use Think\Controller\RestController;

class BaseController extends RestController {
    protected $mongo_client; //mongo 实例
    protected $mongo_db; //mongoDB

    public function _initialize() {
        //连接数据库
        if (!$this->mongo_client) {
            $this->mongo_client = new Client(C('MONGO_SERVER'));
            $db_name = C('MONGO_DB');
            $this->mongo_db = $this->mongo_client->$db_name;
        }
        $action_name = strtolower(CONTROLLER_NAME . "/" . ACTION_NAME);

        //模板布局
        if ($action_name!="index/index") {
            layout(false);
        }

        //不需要权限的控制器
        if ($action_name == 'index/login') {
            return;
        }
        switch(MODULE_NAME) {
            case 'Admin'://管理后台
                //检查用户是否登录
                if ($_SESSION['token']) {
                    //取用户权限信息，判断用户是否有权限请求此接口
                    $admin_role = $this->mongo_db->admin_role->findOne(array("_id"=>$_SESSION['admin']['role_id']));
                    $menu = json_decode(json_encode($admin_role), true);
                    $this->assign("menu", $menu['permission']);
                }
                break;
            case 'Agent'://代理后台
                break;
        }
    }
}