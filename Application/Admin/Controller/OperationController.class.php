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

class OperationController extends BaseController
{
    public function cardGet()
    {
        $admin_card_daily = $this->mongo_db->admin_card_daily;
        $search = array();

        if (I('get._id')) {
            $search['_id'] = new \MongoId(I('get._id'));
            $query = $admin_card_daily->findOne($search);
            $query['start_time'] = convertToTime($query['start_time']);
            $query['end_time'] = convertToTime($query['end_time']);
            $this->_result['data']['card_daily'] = $query;
        } else {
            $limit = intval(I('get.limit', C('PAGE_NUM')));
            $skip = (intval(I('get.p', 1)) - 1) * $limit;
            filter_array_element($search);

            $cursor = $admin_card_daily->find($search)->limit($limit)->skip($skip)->sort(array("start_date" => -1));
            $result = array();
            foreach ($cursor as $item) {
                $item['start_time'] = convertToTime($item['start_time']);
                $item['end_time'] = convertToTime($item['end_time']);
                array_push($result, $item);
            }

            $count = $admin_card_daily->count($search);
            $page = new Page($count, C('PAGE_NUM'));
            $page = $page->show();

            $this->assign("page", $page);
            $this->assign("card_daily", $result);
            $this->_result['data']['html'] = $this->fetch("Operation:card");

            $this->_result['data']['count'] = $count;
            $this->_result['data']['page'] = $page;
            $this->_result['data']['card_daily'] = $result;
        }
        $this->response($this->_result);
    }

    public function cardPut()
    {
        $search['_id'] = new \MongoId(I('put._id'));
        $data['start_time'] = I('put.start_time', null, check_empty_string);
        $data['end_time'] = I('put.end_time', null, check_empty_string);
        $data['amount'] = intval(I('put.amount', 0));
        merge_params_error($data['start_time'], 'start_time', '开始时间不能为空', $this->_result['error']);
        merge_params_error($data['end_time'], 'end_time', '结束时间不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        $data['start_time'] = convertToSeconds($data['start_time']);
        $data['end_time'] = convertToSeconds($data['end_time']);
        if($data['amount'] <= 0) {
            $this->response($this->_result, 'json', 400, '数量必须为正整数');
        }
        filter_array_element($data);
        $update['$set'] = $data;
        $admin_card_daily = $this->mongo_db->admin_card_daily;
        if ($admin_card_daily->update($search, $update)) {
            $this->response($this->_result, 'json', 201, '保存成功');
        } else {
            $this->response($this->_result, 'json', 400, '保存失败');
        }
    }

    public function cardPost()
    {
        $data['start_time'] = I('post.start_time', null, check_empty_string);
        $data['end_time'] = I('post.end_time', null, check_empty_string);
        $data['date'] = time();
        $data['amount'] = intval(I('post.amount', 0));
        $data['admin'] = $_SESSION[MODULE_NAME.'_admin']['username'];
        merge_params_error($data['start_time'], 'start_time', '开始时间不能为空', $this->_result['error']);
        merge_params_error($data['end_time'], 'end_time', '结束时间不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        $data['start_time'] = convertToSeconds($data['start_time']);
        $data['end_time'] = convertToSeconds($data['end_time']);

        if($data['amount'] <= 0) {
            $this->response($this->_result, 'json', 400, '数量必须为正整数');
        }

        $admin_card_daily = $this->mongo_db->admin_card_daily;
        filter_array_element($data);
        if ($admin_card_daily->insert($data)) {
            $this->response($this->_result, 'json', 201, '新建成功');
        } else {
            $this->response($this->_result, 'json', 400, '新建失败');
        }
    }

    public function cardDelete()
    {
        $search['_id'] = new \MongoId(I('delete._id'));
        $admin_card_daily = $this->mongo_db->admin_card_daily;
        if ($admin_card_daily->remove($search)) {
            $this->response($this->_result, 'json', 204, '删除成功');
        } else {
            $this->response($this->_result, 'json', 400, '删除失败');
        }
    }

    /**
     * @desc 领取记录
     */
    public function recordGet() {
        $search['roleid'] = I('get.fields', null);
        $admin_card_receive_daily = $this->mongo_db->admin_card_receive_daily;
        $limit = intval(I('get.limit', C('PAGE_NUM')));
        $skip = (intval(I('get.p', 1)) - 1) * $limit;
        $search['roleid'] && $search['roleid'] = intval($search['roleid']);
        filter_array_element($search);
        $cursor = $admin_card_receive_daily->find($search)->limit($limit)->skip($skip)->sort(array("date" => -1));
        $result = array();
        foreach ($cursor as $item) {
            $item['date'] = date("Y-m-d H:i:s", $item['date']);
            array_push($result, $item);
        }

        $count = $admin_card_receive_daily->count($search);
        $page = new Page($count, C('PAGE_NUM'));
        $page = $page->show();

        $this->assign("page", $page);
        $this->assign("record", $result);
        $this->_result['data']['html'] = $this->fetch("Operation:record");

        $this->_result['data']['count'] = $count;
        $this->_result['data']['page'] = $page;
        $this->_result['data']['record'] = $result;
        $this->response($this->_result, 'json', 200);
    }

    /**
     * @desc 牌型分数记录
     */
    public function scoreGet() {
        $search = array();
        //$search['roleid'] = I('get.fields', null);
        $admin_card_receive_daily = $this->mongo_db->admin_card_receive_daily;
        $limit = intval(I('get.limit', C('PAGE_NUM')));
        $skip = (intval(I('get.p', 1)) - 1) * $limit;
        //$search['roleid'] && $search['roleid'] = intval($search['roleid']);
        filter_array_element($search);
        $cursor = $admin_card_receive_daily->find($search)->limit($limit)->skip($skip)->sort(array("date" => -1));
        $result = array();
        foreach ($cursor as $item) {
            $item['date'] = date("Y-m-d H:i:s", $item['date']);
            array_push($result, $item);
        }

        $count = $admin_card_receive_daily->count($search);
        $page = new Page($count, C('PAGE_NUM'));
        $page = $page->show();

        $this->assign("page", $page);
        $this->assign("record", $result);
        $this->_result['data']['html'] = $this->fetch("Operation:record");

        $this->_result['data']['count'] = $count;
        $this->_result['data']['page'] = $page;
        $this->_result['data']['record'] = $result;
        $this->response($this->_result, 'json', 200);
    }
}