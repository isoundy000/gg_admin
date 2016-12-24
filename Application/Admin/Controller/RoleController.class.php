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
            $this->_result['data']['html'] = $this->fetch("role:index");

            $this->_result['data']['count'] = $count;
            $this->_result['data']['page'] = $page;
            $this->_result['data']['roles'] = $result;

            $this->response($this->_result);
        }
    }

    public function rolesPut() {

        $search['_id'] = new ObjectID(I('put._id'));
        $data['name'] = I('put.name', null, check_empty_string);
        $data['status'] = I('post.status', 0)=='on' ? 1 : 0;

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

    public function rolesPost() {
        $data['name'] = I('post.name', null, check_empty_string);
        $data['status'] = I('post.status', 0)=='on' ? 1 : 0;
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