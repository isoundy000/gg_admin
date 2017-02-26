<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/22
 * Time: 9:46
 */
namespace Admin\Controller;
use Common\Controller\BaseController;
use Think\Page;

class RoleController extends BaseController {
    public function rolesGet() {
        $admin_role = $this->mongo_db->admin_role;

        if (I('get._id')) {
            $search['_id'] = new \MongoId(I('get._id', null));
            $query = $admin_role->findOne($search);
            $query['permission'] = $this->menu_tree($query['permission'],
                array("module_name"=>I('get.module_name')?I('get.module_name'):MODULE_NAME));
            $this->_result['data']['roles'] = $query;
        } else {
            $search = array();
            $limit = intval(I('get.limit', C('PAGE_NUM')));
            $skip = (intval(I('get.p', 1)) - 1) * $limit;
            filter_array_element($search);
            filter_array_element($option);

            $cursor = $admin_role->find($search)->limit($limit)->skip($skip);
            $result = iterator_to_array($cursor);

            $count = $admin_role->count($search);
            $page = new Page($count, $limit);
            $page = $page->show();

            $this->_result['data']['menus'] = $this->menu_tree(array(),
                array("module_name"=>I('get.module_name')?I('get.module_name'):MODULE_NAME));
            $this->assign("permission", $this->_result['data']['menus']);
            $this->assign("page", $page);
            $this->assign("roles", $result);
            $this->_result['data']['html'] = $this->fetch("Role:index");

            $this->_result['data']['count'] = $count;
            $this->_result['data']['page'] = $page;
            $this->_result['data']['roles'] = $result;
        }
        $this->response($this->_result);
    }

    public function permissionGet() {
        $this->_result['data']['menus'] = $this->menu_tree(array(),
            array("module_name"=>I('get.module_name')?I('get.module_name'):MODULE_NAME));
        $this->response($this->_result);
    }

    public function rolesPut() {
        $search['_id'] = new \MongoId(I('put._id'));
        $data['name'] = I('put.name', null, check_empty_string);
        $data['status'] = intval(I('put.status'));
        $data['module_name'] = I('put.module_name') ? I('put.module_name') : 'Admin';
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
        if ($admin_role->update($search, $update)) {
            $this->response($this->_result, 'json', 201, '保存成功');
        } else {
            $this->_result['data']['param'] = $data;
            $this->response($this->_result, 'json', 400, '保存失败');
        }

    }

    private function handlePermission($permission)
    {
        //取所有$permission._id
        $ids = array();
        foreach ($permission as $key => $value) {
            if ($value['state']['selected'] == 'true') {
                if (!in_array($value['tags'][0]['$id'], $ids)) {
                    array_push($ids, $value['tags'][0]['$id']);
                }
            }
            if ($value['nodes']) {
                foreach ($value['nodes'] as $k => $v) {
                    if ($v['state']['selected'] == 'true') {
                        if(!in_array($v['tags'][0]['$id'], $ids)) {
                            array_push($ids, $v['tags'][0]['$id']);
                        }
                    }
                }
            }
        }
        return $ids;
    }

    public function rolesPost() {
        $data['name'] = I('post.name', null, check_empty_string);
        $data['status'] = intval(I('post.status'));
        $data['module_name'] = I('post.module_name') ? I('post.module_name') : 'Admin';
        $data['permission'] = $this->handlePermission(I('post.permission'));
        $data['date'] = time();
        merge_params_error($data['name'], 'name', '权限名称不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        filter_array_element($data);

        $admin_role = $this->mongo_db->admin_role;
        if ($admin_role->insert($data)) {
            $this->response($this->_result, 'json', 201, '新建成功');
        } else {
            $this->response($this->_result, 'json', 400, '新建失败');
        }
    }

    public function rolesDelete() {
        $search['_id'] = new \MongoId(I('delete._id'));
        $admin_role = $this->mongo_db->admin_role;
        if ($admin_role->remove($search)) {
            $this->response($this->_result, 'json', 204, '删除成功');
        } else {
            $this->response($this->_result, 'json', 400, '删除失败');
        }
    }
}