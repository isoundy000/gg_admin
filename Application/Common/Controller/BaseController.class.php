<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/22
 * Time: 7:23
 */
namespace Common\Controller;
use Think\Controller\RestController;

class BaseController extends RestController {
    protected $mongo_client; //mongo 实例
    protected $mongo_db; //mongoDB

    public function _initialize() {
        //连接数据库
        if (!$this->mongo_client) {
            $this->mongo_client = new \MongoClient(C('MONGO_SERVER'));
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
                    $menu = $this->menu_tree($admin_role['permission']);
                    $this->assign("menu", $menu);
                }
                break;
            case 'Agent'://代理后台
                break;
        }
    }

    /**
     * @desc tree_menu
     * @param array $permission
     * @param string $collection
     * @return array
     */
    protected function menu_tree($permission = array(), $collection='admin_menu') {
        $admin_menu = $this->mongo_db->$collection->find()->sort(array('sort'=>1));
        $result = array();
        foreach ($admin_menu as $item) {
            //permission
            if(in_array($item['_id']->__toString(), $permission)) {
                $item['selected'] = 1;
            } else {
                $item['selected'] = 0;
            }
            if ($item['pid']) {//child
                if(!isset($result[$item['pid']]['child'])) {
                    $result[$item['pid']]['child'] = array();
                }
                array_push($result[$item['pid']]['child'], $item);
            } else {//parent
                if (isset($result[$item['_id']->__toString()]['child'])) {
                    $item['child'] = $result[$item['_id']->__toString()]['child'];
                }
                $result[$item['_id']->__toString()] = $item;
            }
        }
        return $result;
    }
}