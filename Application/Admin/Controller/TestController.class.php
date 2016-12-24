<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/23
 * Time: 7:47
 */
namespace Admin\Controller;
use Common\Controller\BaseController;

class TestController extends BaseController {
    public function indexGet() {
        $admin_role = $this->mongo_db->admin_role;
        $cursor = $admin_role->findOne();
        $admin_menu = $this->mongo_db->admin_menu;
        $result = array();
        foreach ($cursor->permission as $item) {
            foreach($item['child'] as $child) {
                $tmp['id'] = $child['id'];
                $tmp['name'] = $child['name'];
                $tmp['action'] = $child['action'];
                $tmp['icon'] = $child['icon'];
                array_push($result, $tmp);
            }
            $tmp['id'] = $item['id'];
            $tmp['name'] = $item['name'];
            $tmp['action'] = $item['action'];
            $tmp['icon'] = $item['icon'];
            array_push($result, $tmp);
        }
        $admin_menu->insertMany($result);
    }

}