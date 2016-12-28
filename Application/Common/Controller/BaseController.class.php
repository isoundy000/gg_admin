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
        }
        $db_name = C('MONGO_DB');
        $this->mongo_db = $this->mongo_client->$db_name;
        $action_name = strtolower(CONTROLLER_NAME . "/" . ACTION_NAME);
        $http_method = strtoupper($_SERVER['REQUEST_METHOD']);
        //模板布局
        if ($action_name!="index/index") {
            layout(false);
        }

        //不需要权限的控制器
        if (in_array($action_name, array(
            'index/login',
            'user/token',
            'index/verify',
        ))) {
            return;
        }

        //检查用户是否登录
        if ($_SESSION[MODULE_NAME.'_token']) {
            //判断用户是否有权限请求此接口
            $admin_action = $this->mongo_db->admin_menu->findOne(
                array("action"=>$action_name,"http_method"=>new \MongoRegex("/$http_method/"),"module_name"=>MODULE_NAME)
            );

            //404 not found
            if(!$admin_action) {
                $this->response($this->_result, 'json', 404);
            }
            $admin_action = $admin_action['_id']->__toString();
            //取用户权限信息
            $admin_role = $this->mongo_db->admin_role->findOne(array("_id"=>$_SESSION[MODULE_NAME.'_admin']['role_id']));
            //403 no permission
            if(!in_array($admin_action, $admin_role['permission'])) {
                $this->response($this->_result, 'json', 403);
            }
            $menu = $this->menu_tree($admin_role['permission'], array("visible"=>1,"module_name"=>MODULE_NAME));
            $this->assign("menu", $menu);
        } else {
            $this->redirect(MODULE_NAME."/Index/login");
        }
    }

    /**
     * @desc tree_menu
     * @param array $permission
     * @param array $search
     * @return array
     */
    protected function menu_tree($permission = array(), $search = array()) {
        $admin_menu = $this->mongo_db->admin_menu->find($search)->sort(array("module_name"=>1,"sort"=>1));
        $result = array();
        foreach ($admin_menu as $item) {
            $item['http_method'] = explode(',', $item['http_method']);
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