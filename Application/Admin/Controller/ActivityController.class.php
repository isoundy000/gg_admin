<?php
/**
 * Created by PhpStorm.
 * trotting: Cherish
 * Date: 2016/12/31
 * Time: 15:39
 */
namespace Admin\Controller;
use Common\Controller\BaseController;
use Think\Page;

class ActivityController extends BaseController {

    //走马灯公告
    public function trottingGet() {
        $admin_trotting = $this->mongo_db->admin_trotting;
        $search = array();

        if (I('get._id')) {
            $search['_id'] = new \MongoId(I('get._id'));
            $query = $admin_trotting->findOne($search);
            $query['date_range'] = date("Y/m/d H:i:s", $query['start_date']) . ' - ' . date("Y/m/d H:i:s", $query['end_date']);
            $this->_result['data']['trotting'] = $query;
        } else {
            $limit = intval(I('get.limit', C('PAGE_NUM')));
            $skip = (intval(I('get.p', 1)) - 1) * $limit;
            filter_array_element($search);

            $cursor = $admin_trotting->find($search)->limit($limit)->skip($skip)->sort(array("start_date" => -1));
            $result = array();
            $now = time();
            foreach ($cursor as $item) {
                $item['date'] = date("Y-m-d H:i:s", $item['date']);
                if ($now < $item['start_date']) {
                    $item['expire'] = 0; //未开始
                }
                if ($now >= $item['start_date'] && $now <= $item['end_date']) {
                    $item['expire'] = 1; //播放中
                }
                if ($now > $item['end_date']) {
                    $item['expire'] = 2; //已过期
                }
                $item['start_date'] = date("Y-m-d H:i:s", $item['start_date']);
                $item['end_date'] = date("Y-m-d H:i:s", $item['end_date']);
                array_push($result, $item);
            }

            $count = $admin_trotting->count($search);
            $page = new Page($count, C('PAGE_NUM'));
            $page = $page->show();

            $this->assign("page", $page);
            $this->assign("trotting", $result);
            $this->_result['data']['html'] = $this->fetch("Activity:trotting");

            $this->_result['data']['count'] = $count;
            $this->_result['data']['page'] = $page;
            $this->_result['data']['trotting'] = $result;
        }
        $this->response($this->_result);
    }

    public function trottingPost() {
        $data['interval'] = I('post.interval', null, check_positive_integer);
        $data['content'] = I('post.content', null, check_empty_string);
        $data['date'] = time();
        $data['admin'] = $_SESSION[MODULE_NAME.'_admin']['username'];
        merge_params_error($data['interval'], 'interval', '间隔为正整数', $this->_result['error']);
        merge_params_error($data['content'], 'content', '消息内容不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }

        $data['interval'] = intval($data['interval']);
        $date_range = I('post.date_range');
        $date_range = explode('-', $date_range);
        if (!$date_range) {
            $this->response($this->_result, 'json', 400, "时间格式不正确");
        }
        $data['start_date'] = strtotime(trim($date_range[0]));
        $data['end_date'] = strtotime(trim($date_range[1]));
        //$data['content'] = strip_tags($data['content'], '<a>');
        if ($data['interval'] > ($data['end_date'] - $data['start_date'])) {
            $this->response($this->_result, 'json', 400, '时间间隔不能大于起始时间差');
        }

        filter_array_element($data);
        $admin_trotting = $this->mongo_db->admin_trotting;
        if ($admin_trotting->insert($data)) {
            $this->response($this->_result, 'json', 201, '新建成功');
        } else {
            $this->response($this->_result, 'json', 400, '新建失败');
        }
    }
    
    public function trottingPut() {
        $search['_id'] = new \MongoId(I('put._id'));
        $data['interval'] = I('put.interval', null, check_positive_integer);
        $data['content'] = I('put.content', null, check_empty_string);

        merge_params_error($data['interval'], 'interval', '时间应为正整数', $this->_result['error']);
        merge_params_error($data['content'], 'content', '消息内容不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        $data['interval'] = intval($data['interval']);
        $date_range = I('put.date_range');
        $date_range = explode('-', $date_range);
        if (!$date_range) {
            $this->response($this->_result, 'json', 400, "时间格式不正确");
        }
        $data['start_date'] = strtotime(trim($date_range[0]));
        $data['end_date'] = strtotime(trim($date_range[1]));
        $data['admin'] = $_SESSION[MODULE_NAME.'_admin']['username'];
        //$data['content'] = strip_tags($data['content'], '<a>');
        if ($data['interval'] > ($data['end_date'] - $data['start_date'])) {
            $this->response($this->_result, 'json', 400, '时间间隔不能大于起始时间差');
        }

        filter_array_element($data);
        $update['$set'] = $data;
        $admin_trotting = $this->mongo_db->admin_trotting;
        if ($admin_trotting->update($search,$update)) {
            $this->response($this->_result, 'json', 201, '保存成功');
        } else {
            $this->_result['data']['param'] = $data;
            $this->response($this->_result, 'json', 400, '保存失败');
        }
    }
    
    public function trottingDelete() {
        $search['_id'] = new \MongoId(I('delete._id'));
        $admin_trotting = $this->mongo_db->admin_trotting;
        if ($admin_trotting->remove($search)) {
            $this->response($this->_result, 'json', 204, '删除成功');
        } else {
            $this->response($this->_result, 'json', 400, '删除失败');
        }
    }
    
    //邮件
    public function mailGet() {
        $admin_mail = $this->mongo_db->admin_mail;
        $search = array();

        if (I('get._id')) {
            $search['_id'] = new \MongoId(I('get._id'));
            $query = $admin_mail->findOne($search);
            $this->_result['data']['mail'] = $query;
        } else {
            $limit = intval(I('get.limit', C('PAGE_NUM')));
            $skip = (intval(I('get.p', 1)) - 1) * $limit;
            filter_array_element($search);

            $cursor = $admin_mail->find($search)->limit($limit)->skip($skip)->sort(array("date" => -1));
            $result = array();
            foreach ($cursor as $item) {
                $item['date'] = date("Y-m-d H:i:s", $item['date']);
                array_push($result, $item);
            }

            $count = $admin_mail->count($search);
            $page = new Page($count, C('PAGE_NUM'));
            $page = $page->show();

            $this->assign("page", $page);
            $this->assign("mail", $result);
            $this->_result['data']['html'] = $this->fetch("Activity:mail");

            $this->_result['data']['count'] = $count;
            $this->_result['data']['page'] = $page;
            $this->_result['data']['mail'] = $result;
        }
        $this->response($this->_result);
    }

    public function mailPost() {
        $data['scope'] = intval(I('post.scope', 0, check_numeric));//0 全服 1 多人
        $role_list = I('post.role_list', null);
        $data['title'] = I('post.title', null, check_empty_string);
        $data['content'] = I('post.content', null, check_empty_string);
        $data['date'] = time();
        $data['admin'] = $_SESSION[MODULE_NAME.'_admin']['username'];
        merge_params_error($data['title'], 'title', '标题不能为空', $this->_result['error']);
        merge_params_error($data['content'], 'content', '邮件内容不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }

        if ($data['scope'] && $role_list) {
            $role_list = explode(',', $role_list);
            $role_list = array_map("intval", $role_list);
            $check_list = array_map(array($this, "checkRoleId"), $role_list);
            $check_list = array_filter($check_list);
            if ($check_list) {
                $this->response($this->_result, 'json', 400, "以下玩家ID不存在: " . implode(", ", $check_list));
            }
            $data['role_list'] = $role_list;
        }

        $data['title'] = strip_tags($data['title']);
        //$data['content'] = strip_tags($data['content'], '<a>');

        filter_array_element($data);
        $admin_mail = $this->mongo_db->admin_mail;
        if ($admin_mail->insert($data)) {
            $this->response($this->_result, 'json', 201, '新建成功');
        } else {
            $this->response($this->_result, 'json', 400, '新建失败');
        }
    }

    //公告
    public function noticeGet() {
        $admin_notice = $this->mongo_db->admin_notice;
        $search = array();
        if (I('get._id')) {
            $search['_id'] = new \MongoId(I('get._id'));
            $query = $admin_notice->findOne($search);
            $query['date'] = date("Y/m/d H:i:s", $query['date']);
            $this->_result['data']['notice'] = $query;
        } else {
            $limit = intval(I('get.limit', C('PAGE_NUM')));
            $skip = (intval(I('get.p', 1)) - 1) * $limit;
            filter_array_element($search);

            $cursor = $admin_notice->find($search)->limit($limit)->skip($skip)->sort(array("date" => -1));
            $result = array();
            foreach ($cursor as $item) {
                $item['type_name'] = $item['type'] ? "后台" : "游戏";
                $item['date'] = date("Y-m-d H:i:s", $item['date']);
                array_push($result, $item);
            }

            $count = $admin_notice->count($search);
            $page = new Page($count, C('PAGE_NUM'));
            $page = $page->show();

            $this->assign("page", $page);
            $this->assign("notice", $result);
            $this->_result['data']['html'] = $this->fetch("Activity:notice");

            $this->_result['data']['count'] = $count;
            $this->_result['data']['page'] = $page;
            $this->_result['data']['notice'] = $result;
        }
        $this->response($this->_result);
    }

    public function noticePost() {
        $data['type'] = intval(I('post.type', 0));
        $data['title'] = I('post.title', null, check_empty_string);
        $data['content'] = I('post.content', null, check_empty_string);
        $data['date'] = time();
        $data['admin'] = $_SESSION[MODULE_NAME.'_admin']['username'];
        merge_params_error($data['title'], 'title', '标题不能为空', $this->_result['error']);
        merge_params_error($data['content'], 'content', '内容不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        $data['title'] = strip_tags($data['title']);
        //$data['content'] = strip_tags($data['content'], '<a>');
        $admin_notice = $this->mongo_db->admin_notice;
        if ($admin_notice->insert($data)) {
            $this->response($this->_result, 'json', 201, '新建成功');
        } else {
            $this->response($this->_result, 'json', 400, '新建失败');
        }
    }

    public function noticePut() {
        $search['_id'] = new \MongoId(I('put._id'));
        $data['type'] = intval(I('put.type', 0));
        $data['title'] = I('put.title', null, check_empty_string);
        $data['content'] = I('put.content', null, check_empty_string);
        $data['admin'] = $_SESSION[MODULE_NAME.'_admin']['username'];
        merge_params_error($data['title'], 'title', '标题不能为空', $this->_result['error']);
        merge_params_error($data['content'], 'content', '内容不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        $data['title'] = strip_tags($data['title']);
        //$data['content'] = strip_tags($data['content'], '<a>');
        $update['$set'] = $data;
        $admin_notice = $this->mongo_db->admin_notice;
        if ($admin_notice->update($search,$update)) {
            $this->response($this->_result, 'json', 201, '保存成功');
        } else {
            $this->_result['data']['param'] = $data;
            $this->response($this->_result, 'json', 400, '保存失败');
        }
    }

    public function noticeDelete() {
        $search['_id'] = new \MongoId(I('delete._id'));
        $admin_notice = $this->mongo_db->admin_notice;
        if ($admin_notice->remove($search)) {
            $this->response($this->_result, 'json', 204, '删除成功');
        } else {
            $this->response($this->_result, 'json', 400, '删除失败');
        }
    }

    //弹窗
    public function popupGet() {
        $admin_popup = $this->mongo_db->admin_popup;
        $search = array();

        if (I('get._id')) {
            $search['_id'] = new \MongoId(I('get._id'));
            $query = $admin_popup->findOne($search);
            $query['date_range'] = date("Y/m/d H:i:s", $query['start_date']) . ' - ' . date("Y/m/d H:i:s", $query['end_date']);
            $this->_result['data']['popup'] = $query;
        } else {
            $limit = intval(I('get.limit', C('PAGE_NUM')));
            $skip = (intval(I('get.p', 1)) - 1) * $limit;
            filter_array_element($search);

            $cursor = $admin_popup->find($search)->limit($limit)->skip($skip)->sort(array("start_date" => -1));
            $result = array();
            foreach ($cursor as $item) {
                $item['date'] = date("Y-m-d H:i:s", $item['date']);
                $item['start_date'] = date("Y-m-d H:i:s", $item['start_date']);
                $item['end_date'] = date("Y-m-d H:i:s", $item['end_date']);
                array_push($result, $item);
            }

            $count = $admin_popup->count($search);
            $page = new Page($count, C('PAGE_NUM'));
            $page = $page->show();

            $this->assign("page", $page);
            $this->assign("popup", $result);
            $this->_result['data']['html'] = $this->fetch("Activity:popup");

            $this->_result['data']['count'] = $count;
            $this->_result['data']['page'] = $page;
            $this->_result['data']['popup'] = $result;
        }
        $this->response($this->_result);
    }

    public function popupPost()
    {
        $data['visible'] = I('post.visible', 0); //0失效 1显示
        $data['image'] = I('post.image', null, check_empty_string);
        $data['date'] = time();
        $data['admin'] = $_SESSION[MODULE_NAME . '_admin']['username'];
        merge_params_error($data['image'], 'image', '请上传图片', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }

        $data['visible'] = intval($data['visible']);
        $date_range = I('post.date_range');
        $date_range = explode('-', $date_range);
        if (!$date_range) {
            $this->response($this->_result, 'json', 400, "时间格式不正确");
        }
        $data['start_date'] = strtotime(trim($date_range[0]));
        $data['end_date'] = strtotime(trim($date_range[1]));
        $data['image'] = substr($data['image'], 1);
        filter_array_element($data);
        $admin_popup = $this->mongo_db->admin_popup;
        if ($admin_popup->insert($data)) {
            $this->response($this->_result, 'json', 201, '新建成功');
        } else {
            $this->response($this->_result, 'json', 400, '新建失败');
        }
    }

    public function popupPut() {
        $search['_id'] = new \MongoId(I('put._id'));
        $data['visible'] = I('put.visible', 0);
        $data['image'] = I('put.image', null, check_empty_string);
        merge_params_error($data['image'], 'image', '请上传图片', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        $data['visible'] = intval($data['visible']);
        $date_range = I('put.date_range');
        $date_range = explode('-', $date_range);
        if (!$date_range) {
            $this->response($this->_result, 'json', 400, "时间格式不正确");
        }
        $data['start_date'] = strtotime(trim($date_range[0]));
        $data['end_date'] = strtotime(trim($date_range[1]));
        $data['admin'] = $_SESSION[MODULE_NAME.'_admin']['username'];
        if (strpos($data['image'], '.') == 0) {
            $data['image'] = substr($data['image'], 1);
        }
        filter_array_element($data);
        $update['$set'] = $data;
        $admin_popup = $this->mongo_db->admin_popup;
        if ($admin_popup->update($search,$update)) {
            $this->response($this->_result, 'json', 201, '保存成功');
        } else {
            $this->_result['data']['param'] = $data;
            $this->response($this->_result, 'json', 400, '保存失败');
        }
    }

    public function popupDelete() {
        $search['_id'] = new \MongoId(I('delete._id'));
        $admin_popup = $this->mongo_db->admin_popup;
        if ($admin_popup->remove($search)) {
            $this->response($this->_result, 'json', 204, '删除成功');
        } else {
            $this->response($this->_result, 'json', 400, '删除失败');
        }
    }

    //检查玩家ID是否存在
    private function checkRoleId($role_id) {
        $role_info = $this->mongo_db->role_info;
        if (!$role_info->findOne(array("roleid" => $role_id))) {
            return $role_id;
        } else {
            return null;
        }
    }
}