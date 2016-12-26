<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/22
 * Time: 9:46
 */
namespace Admin\Controller;
use Common\Controller\BaseController;
use MongoDB\BSON\ObjectID;
use Think\Page;

class RoleController extends BaseController {
    public function rolesGet() {
        $admin_role = $this->mongo_db->admin_role;

        if (I('get._id')) {
            $search['_id'] = new ObjectID(I('get._id', null));
            $option['projection'] = array();
            $query = $admin_role->findOne($search, $option);
            $query->permission = $this->handlePermission($query->permission, 'GET');
            $this->_result['data']['roles'] = $query;
            $this->response($this->_result);

        } else {
            $search = array();
            $option['limit'] = intval(I('get.limit', C('PAGE_NUM')));
            $option['skip'] = (intval(I('get.p', 1)) - 1) * $option['limit'];
            filter_array_element($search);
            filter_array_element($option);

            $cursor = $admin_role->find($search, $option);
            $result = array();
            foreach ($cursor as $item) {
                array_push($result, $item);
            }


            $count = $admin_role->count($search);
            $page = new Page($count, C('PAGE_NUM'));
            $page = $page->show();



            $this->assign("page", $page);
            $this->assign("roles", $result);
            $this->_result['data']['html'] = $this->fetch("Role:index");

            $this->_result['data']['count'] = $count;
            $this->_result['data']['page'] = $page;
            $this->_result['data']['roles'] = $result;

            $this->response($this->_result);
        }
    }

    public function permissionGet() {
        $admin_menu = $this->mongo_db->admin_menu;
        //查找parent menu，以树形返回
        $parent_query = $admin_menu->find(array(),array('$sort',array('sort'=>1)));
        $tree_menu = array();
        foreach($parent_query as $item) {
            if ($item->pid == 0) {
                if (isset($tree_menu[$item->_id])) {
                    if(!$item['child']) {
                        $item['child'] = array();
                    }
                    array_push($item['child'], $tree_menu[$item->_id]['child']);
                }
                $tree_menu[(string)$item->_id] = $item;
            } else {
                if (!$tree_menu[$item->pid]['child']) {
                    $tree_menu[$item->pid]['child'] = array();
                }
                array_push($tree_menu[$item->pid]['child'], $item);
            }
        }
        $this->_result['data']['menus'] = $tree_menu;
        $this->response($this->_result);
    }

    public function rolesPut() {

        $search['_id'] = new ObjectID(I('put._id'));
        $data['name'] = I('put.name', null, check_empty_string);
        $data['status'] = I('post.status', 0)=='on' ? 1 : 0;
        $data['permission'] = $this->handlePermission(I('put.permission'));
        merge_params_error($data['name'], 'name', '权限名称不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        filter_array_element($data);

        $update['$set'] = $data;
        $admin_role = $this->mongo_db->admin_role;
        if ($admin_role->findOneAndUpdate($search,$update)) {
            $this->response($this->_result, 'json', 201, '保存成功');
        } else {
            $this->_result['data']['param'] = $data;
            $this->response($this->_result, 'json', 400, '保存失败');
        }

    }

    private function handlePermission($permission, $method='PUT') {
        //取所有$permission._id
        $ids = array();
        if ($method == 'PUT') {
            foreach ($permission as $key => $value) {
                if ($value['state']['selected'] == 'true') {
                    array_push($ids, $value['tags'][0]['$oid']);
                }
                if ($value['nodes']) {
                    foreach ($value['nodes'] as $k => $v) {
                        if ($v['state']['selected'] == 'true') {
                            array_push($ids, $v['tags'][0]['$oid']);
                        }
                    }
                }
            }
        }
        if ($method == 'GET') {
            foreach ($permission as $key => $value) {
                if ($value->selected) {
                    array_push($ids, (string)$value->_id);
                }
                if ($value['child']) {
                    foreach ($value['child'] as $k => $v) {
                        if ($v->selected) {
                            array_push($ids, (string)$v->_id);
                        }
                    }
                }
            }
        }
        $admin_menu = $this->mongo_db->admin_menu;
        //查找parent menu，以树形返回
        $parent_query = $admin_menu->find(array(),array('$sort',array('sort'=>1)));
        $tree_menu = array();
        foreach($parent_query as $item) {
            if(in_array((string)$item['_id'], $ids)) {
                $item['selected'] = 1;
            } else {
                $item['selected'] = 0;
            }
            if ($item->pid == 0) {
                if (isset($tree_menu[$item->_id])) {
                    if(!$item['child']) {
                        $item['child'] = array();
                    }
                    array_push($item['child'], $tree_menu[$item->_id]['child']);
                }
                $tree_menu[(string)$item->_id] = $item;
            } else {
                if (!$tree_menu[$item->pid]['child']) {
                    $tree_menu[$item->pid]['child'] = array();
                }
                array_push($tree_menu[$item->pid]['child'], $item);
            }
        }
        return $tree_menu;
    }

    public function rolesPost() {
        $data['name'] = I('post.name', null, check_empty_string);
        $data['status'] = I('post.status', 0)=='on' ? 1 : 0;
        $data['permission'] = $this->handlePermission(I('post.permission'));
        merge_params_error($data['name'], 'name', '权限名称不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        filter_array_element($data);

        $admin_role = $this->mongo_db->admin_role;
        if ($admin_role->InsertOne($data)) {
            $this->response($this->_result, 'json', 201, '新建成功');
        } else {
            $this->response($this->_result, 'json', 400, '新建失败');
        }
    }

    public function rolesDelete() {
        $search['_id'] = new ObjectID(I('delete._id'));
        $admin_role = $this->mongo_db->admin_role;
        if ($admin_role->deleteOne($search)) {
            $this->response($this->_result, 'json', 204, '删除成功');
        } else {
            $this->response($this->_result, 'json', 400, '删除失败');
        }
    }
}