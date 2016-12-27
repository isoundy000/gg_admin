<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/22
 * Time: 9:47
 */
namespace Admin\Controller;
use Common\Controller\BaseController;
use Think\Page;

class MenuController extends BaseController {
    public function menusGet() {
        $admin_menu = $this->mongo_db->admin_menu;

        if (I('get._id')) {
            $search['_id'] = new \MongoId(I('get._id', null));
            $option = array();
            $query = $admin_menu->findOne($search, $option);
            $this->_result['data']['menus'] = $query;
        } else {
            $search = array();
            $limit = intval(I('get.limit', C('PAGE_NUM')));
            $skip = (intval(I('get.p', 1)) - 1) * $limit;
            filter_array_element($search);
            filter_array_element($option);

            $cursor = $admin_menu->find($search)->limit($limit)->skip($skip)->sort(array("sort"=>1));
            $result = array();
            foreach ($cursor as $item) {
                if($item['pid']) {
                    $parent_id = new \MongoId($item['pid']);
                    $parent = $admin_menu->findOne(array('_id'=>$parent_id), array('name' => 1));
                    $item['parent_name'] = $parent ? $parent['name'] : "";
                } else {
                    $item['parent_name'] = "";
                }
                array_push($result, $item);
            }

            //查找parent menu
            $parent_query = $admin_menu->find(array("pid"=>'0'));
            $parent_result = iterator_to_array($parent_query);

            $count = $admin_menu->count($search);
            $page = new Page($count, C('PAGE_NUM'));
            $page = $page->show();

            $this->assign("page", $page);
            $this->assign("menus", $result);
            $this->assign("parent_menu", $parent_result);
            $this->_result['data']['html'] = $this->fetch("Menu:index");

            $this->_result['data']['count'] = $count;
            $this->_result['data']['page'] = $page;
            $this->_result['data']['menus'] = $result;
            $this->_result['data']['parent_menu'] = $parent_result;
        }
        $this->response($this->_result);
    }

    public function menusPut() {

        $search['_id'] = new \MongoId(I('put._id'));
        $data['sort'] = intval(I('put.sort'));
        $data['name'] = I('put.name', null, check_empty_string);
        $data['action'] = I('put.action', 'javascript:void(0)');
        $data['module_name'] = I('put.module_name', null, check_empty_string);
        $data['http_method'] = strtoupper(I('put.http_method', null, check_empty_string));
        $data['icon'] = I('put.icon', 'fa-circle');
        $data['pid'] = I('put.pid', '0');
        $data['visible'] = I('put.visible') ? intval(I('put.visible')) : 0;

        merge_params_error($data['name'], 'name', '名称不能为空', $this->_result['error']);
        merge_params_error($data['module_name'], 'module_name', '模块名不能为空', $this->_result['error']);
        merge_params_error($data['http_method'], 'http_method', 'HTTP方法不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        filter_array_element($data);
        if(!strstr("GET,PUT,POST,DELETE", $data['http_method'])) {
            $this->response($this->_result, 'json', 400, 'http方法必须为:GET,PUT,POST,DELETE');
        }

        if(!strstr("Admin,Agent", $data['module_name'])) {
            $this->response($this->_result, 'json', 400, '模块名必须为:Admin,Agent中的一种');
        }

        $update['$set'] = $data;
        $admin_menu = $this->mongo_db->admin_menu;
        if ($admin_menu->update($search,$update)) {
            $this->response($this->_result, 'json', 201, '保存成功');
        } else {
            $this->_result['data']['param'] = $data;
            $this->response($this->_result, 'json', 400, '保存失败');
        }

    }

    public function menusPost() {
        $data['sort'] = intval(I('post.sort'));
        $data['name'] = I('post.name', null, check_empty_string);
        $data['action'] = I('post.action', 'javascript:void(0)');
        $data['module_name'] = I('post.module_name', null, check_empty_string);
        $data['http_method'] = strtoupper(I('post.http_method', null, check_empty_string));
        $data['icon'] = I('post.icon', 'fa-circle');
        $data['pid'] = I('post.pid', '0');
        $data['visible'] = I('post.visible') ? intval(I('post.visible')) : 0;
        merge_params_error($data['name'], 'name', '名称不能为空', $this->_result['error']);
        merge_params_error($data['module_name'], 'module_name', '模块名不能为空', $this->_result['error']);
        merge_params_error($data['http_method'], 'http_method', 'HTTP方法不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        filter_array_element($data);

        if(!strstr("GET,PUT,POST,DELETE", $data['http_method'])) {
            $this->response($this->_result, 'json', 400, 'http方法必须为:GET,PUT,POST,DELETE');
        }

        if(!strstr("Admin,Agent", $data['module_name'])) {
            $this->response($this->_result, 'json', 400, '模块名必须为:Admin,Agent中的一种');
        }

        $admin_menu = $this->mongo_db->admin_menu;
        if ($admin_menu->insert($data)) {
            $this->response($this->_result, 'json', 201, '新建成功');
        } else {
            $this->response($this->_result, 'json', 400, '新建失败');
        }
    }

    public function menusDelete() {
        $search['_id'] = new \MongoId(I('delete._id'));
        $admin_menu = $this->mongo_db->admin_menu;
        if ($admin_menu->remove($search)) {
            $this->response($this->_result, 'json', 204, '删除成功');
        } else {
            $this->response($this->_result, 'json', 400, '删除失败');
        }
    }
}