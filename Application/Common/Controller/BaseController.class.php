<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/22
 * Time: 7:23
 */
namespace Common\Controller;
use Think\Controller\RestController;
use Think\Exception;

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
            'material/material',
        ))) {
            return;
        }

        //用户表
        switch(MODULE_NAME) {
            case 'Admin':
                $session_user = $this->mongo_db->admin_user;
                break;
            case 'Agent':
                $session_user = $this->mongo_db->admin_agent;
                break;
        }

        //检查用户是否登录
        if ($_SESSION[MODULE_NAME.'_token']) {
            //更新用户$_SESSION
            $user = $session_user->findOne(array("_id" => $_SESSION[MODULE_NAME.'_admin']['_id']),array("password" => 0));
            if($user['stock_amount']) {
                $amount = 0;
                foreach($user['stock_amount'] as $key => $value) {
                    $amount += $value;
                }
                $user['card_amount'] = $amount;
            }
            $_SESSION[MODULE_NAME.'_admin'] = $user;

            //判断用户是否有权限请求此接口
            $menu_id = $_GET['menu_id'];
            if (!$menu_id) {
                $admin_action = $this->mongo_db->admin_menu->findOne(
                    array("action" => $action_name, "http_method" => new \MongoRegex("/$http_method/"), "module_name" => MODULE_NAME)
                );

                //404 not found
                if (!$admin_action) {
                    $this->response($this->_result, 'json', 404);
                }
                $menu_id = $admin_action['_id']->__toString();
            }
            //取用户权限信息
            $admin_role = $this->mongo_db->admin_role->findOne(array("_id"=>$_SESSION[MODULE_NAME.'_admin']['role_id']));
            //403 no permission
            if(!in_array($menu_id, $admin_role['permission'])) {
                $this->response($this->_result, 'json', 403);
            }
            $menu = $this->menu_tree($admin_role['permission'], array("visible"=>1,"module_name"=>MODULE_NAME));
            $this->assign("menu", $menu);
            //生成breadcrumb
            $this->assign("breadcrumb", $this->breadcrumb($menu_id));
        } else {
            $this->redirect(MODULE_NAME."/Index/login");
        }
    }


    protected function getMongoClient($seeds = "", $options = array(), $retry = 3)
    {
        try {
            return new \MongoClient($seeds, $options);
        } catch (Exception $e) {
            /* Log the exception so we can look into why mongod failed later */
        }
        if ($retry > 0) {
            return $this->getMongoClient($seeds, $options, --$retry);
        }
        throw new Exception("I've tried several times getting MongoClient.. Is mongod really running?");
    }


    protected function breadcrumb($menu_id) {
        $bread_crumb = array();
        $current_menu = $this->mongo_db->admin_menu->findOne(array("_id" => new \MongoId($menu_id)));
        if ($current_menu['pid']) {
            $first_menu = $this->mongo_db->admin_menu->findOne(array("_id" => new \MongoId($current_menu['pid'])));
            if ($first_menu && $first_menu['visible']) {
                $first_menu = array('active'=> 0, 'name' => $first_menu['name'], 'action' => U(MODULE_NAME.'/index/home'));
                array_push($bread_crumb, $first_menu);
            }
        }
        $second_menu = array('active' =>1, 'name' => $current_menu['name'], 'action' => U(MODULE_NAME."./".$current_menu['action'] . "/" . http_build_query($_GET,null, '/')));
        array_push($bread_crumb, $second_menu);
        return $bread_crumb;
    }

    /**
     * @desc tree_menu
     * @param array $permission
     * @param array $search
     * @return array
     */
    protected function menu_tree($permission = array(), $search = array()) {
        $admin_menu = $this->mongo_db->admin_menu->find($search)->sort(array("pid" => 1, "module_name"=>1,"sort"=>1));
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