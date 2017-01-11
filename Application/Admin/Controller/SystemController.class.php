<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/23
 * Time: 7:47
 */
namespace Admin\Controller;
use Common\Controller\BaseController;
use Think\Page;

class SystemController extends BaseController {

    //限时免房卡
    public function limitationGet() {
        $admin_limitation = $this->mongo_db->admin_limitation;
        $search = array();

        if (I('get._id')) {
            $search['_id'] = new \MongoId(I('get._id'));
            $query = $admin_limitation->findOne($search);
            $query['date_range'] = date("Y/m/d H:i:s", $query['start_date']) . ' - ' . date("Y/m/d H:i:s", $query['end_date']);
            $query['start_time'] = convertToTime($query['start_time']);
            $query['end_time'] = convertToTime($query['end_time']);
            $this->_result['data']['limitation'] = $query;
        } else {
            $limit = intval(I('get.limit', C('PAGE_NUM')));
            $skip = (intval(I('get.p', 1)) - 1) * $limit;
            filter_array_element($search);

            $cursor = $admin_limitation->find($search)->limit($limit)->skip($skip)->sort(array("start_date" => -1));
            $result = array();
            foreach ($cursor as $item) {
                $item['date'] = date("Y-m-d H:i:s", $item['date']);
                $item['start_date'] = date("Y-m-d H:i:s", $item['start_date']);
                $item['end_date'] = date("Y-m-d H:i:s", $item['end_date']);
                $item['start_time'] = convertToTime($item['start_time']);
                $item['end_time'] = convertToTime($item['end_time']);
                array_push($result, $item);
            }

            $count = $admin_limitation->count($search);
            $page = new Page($count, C('PAGE_NUM'));
            $page = $page->show();

            $this->assign("page", $page);
            $this->assign("limitation", $result);
            $this->_result['data']['html'] = $this->fetch("System:limitation");

            $this->_result['data']['count'] = $count;
            $this->_result['data']['page'] = $page;
            $this->_result['data']['limitation'] = $result;
        }
        $this->response($this->_result);
    }

    public function limitationPut() {
        $search['_id'] = new \MongoId(I('put._id'));
        $data['start_time'] = I('put.start_time', null, check_empty_string);
        $data['end_time'] = I('put.end_time', null, check_empty_string);
        $data['status'] = intval(I('put.status', 0));
        $data['admin'] = $_SESSION[MODULE_NAME.'_admin']['username'];
        merge_params_error($data['start_time'], 'start_time', '开始时间不能为空', $this->_result['error']);
        merge_params_error($data['end_time'], 'end_time', '结束时间不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }

        $date_range = I('put.date_range');
        $date_range = explode('-', $date_range);
        if (!$date_range) {
            $this->response($this->_result, 'json', 400, "时间格式不正确");
        }
        $data['start_date'] = strtotime(trim($date_range[0]));
        $data['end_date'] = strtotime(trim($date_range[1]));
        $data['start_time'] = convertToSeconds($data['start_time']);
        $data['end_time'] = convertToSeconds($data['end_time']);

        filter_array_element($data);
        $update['$set'] = $data;
        $admin_limitation = $this->mongo_db->admin_limitation;
        if ($admin_limitation->update($search, $update)) {
            $this->response($this->_result, 'json', 201, '保存成功');
        } else {
            $this->response($this->_result, 'json', 400, '保存失败');
        }
    }

    public function limitationPost() {
        $data['start_time'] = I('post.start_time', null, check_empty_string);
        $data['end_time'] = I('post.end_time', null, check_empty_string);
        $data['date'] = time();
        $data['status'] = intval(I('post.status', 0));
        $data['admin'] = $_SESSION[MODULE_NAME.'_admin']['username'];
        merge_params_error($data['start_time'], 'start_time', '开始时间不能为空', $this->_result['error']);
        merge_params_error($data['end_time'], 'end_time', '结束时间不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }

        $date_range = I('post.date_range');
        $date_range = explode('-', $date_range);
        if (!$date_range) {
            $this->response($this->_result, 'json', 400, "时间格式不正确");
        }
        $data['start_date'] = strtotime(trim($date_range[0]));
        $data['end_date'] = strtotime(trim($date_range[1]));
        $data['start_time'] = convertToSeconds($data['start_time']);
        $data['end_time'] = convertToSeconds($data['end_time']);

        filter_array_element($data);
        $admin_limitation = $this->mongo_db->admin_limitation;
        if ($admin_limitation->insert($data)) {
            $this->response($this->_result, 'json', 201, '新建成功');
        } else {
            $this->response($this->_result, 'json', 400, '新建失败');
        }
    }

    public function limitationDelete() {
        $search['_id'] = new \MongoId(I('delete._id'));
        $admin_limitation = $this->mongo_db->admin_limitation;
        if ($admin_limitation->remove($search)) {
            $this->response($this->_result, 'json', 204, '删除成功');
        } else {
            $this->response($this->_result, 'json', 400, '删除失败');
        }
    }
}