<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/22
 * Time: 9:46
 */
namespace Agent\Controller;
use Common\Controller\BaseController;
use Think\Page;

class AgentController extends BaseController
{

    public function cardsGet() {
        $admin_card = $this->mongo_db->admin_card;
        $search = array();
        $cursor = $admin_card->find($search);
        $result = iterator_to_array($cursor);
        $this->assign("cards", $result);
        $this->_result['data']['html'] = $this->fetch("Card:purchase");
        $this->_result['data']['cards'] = $result;
        $this->response($this->_result);
    }

    public function agentsPut()
    {
        $admin_agent = $this->mongo_db->admin_agent;
        $search['_id'] = $_SESSION[MODULE_NAME . '_admin']['_id'];
        $data['name'] = I('put.name', null, check_empty_string);
        $data['bank_name'] = I('put.bank_name', null, check_empty_string);
        $data['bank_card'] = I('put.bank_card', null, check_empty_string);
        $data['real_name'] = I('put.real_name', null, check_empty_string);
        $data['wechat'] = I('put.wechat', null, check_empty_string);
        $data['password'] = I('put.password', null);
        $data['repeat_password'] = I('put.repeat_password', null);
        $data['old_password'] = I('put.old_password', null);

        $data['status'] = intval(I('put.status', $_SESSION[MODULE_NAME . '_admin']['status']));
        merge_params_error($data['name'], 'name', '名字不能为空', $this->_result['error'], false);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }

        if ($data['old_password']) {
            $user = $admin_agent->findOne(array("username" => $_SESSION[MODULE_NAME . '_admin']['username']));
            if (!$user || $user['password'] != md5($data['old_password'])) {
                $this->response($this->_result, 'json', 400, '旧密码错误');
            }
        }

        if ($data['password'] && $data['repeat_password']) {
            if ($data['password'] != $data['repeat_password']) {
                $this->response($this->_result, 'json', 400, '两次输入的密码不一致');
            }

            if (checkTextLength6($data['password']) || checkTextLength6($data['repeat_password'])) {
                $this->response($this->_result, 'json', 400, '密码至少6个字符');
            }
            unset($data['repeat_password']);
            $data['password'] = md5($data['password']);
        }
        if (isset($data['password']) && !$data['password']) {
            unset($data['password']);
        }
        if (isset($data['repeat_password']) && !$data['repeat_password']) {
            unset($data['repeat_password']);
        }
        if (isset($data['old_password']) && !$data['old_password']) {
            unset($data['old_password']);
        }

        filter_array_element($data);

        $update['$set'] = $data;
        if ($agent = $admin_agent->findAndModify($search, $update, null, array('new' => true))) {
            unset($agent['password']);
            $agent['date'] = date("Y-m-d H:i:s", $agent['date']);
            $_SESSION[MODULE_NAME . '_admin'] = $agent;
            $this->response($this->_result, 'json', 201, '保存成功');
        } else {
            $this->_result['data']['param'] = $data;
            $this->response($this->_result, 'json', 400, '保存失败');
        }

    }
}