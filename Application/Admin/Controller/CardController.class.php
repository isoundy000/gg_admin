<?php
/**
 * Created by PhpStorm.
 * card: Cherish
 * Date: 2016/12/22
 * Time: 9:46
 */
namespace Admin\Controller;
use Common\Controller\BaseController;
use Think\Page;

class CardController extends BaseController
{
    public function cardsGet() {
        $admin_card = $this->mongo_db->admin_card;

        $card_type = C('SYSTEM.STOCK_TYPE');
        $this->assign("stock_type", $card_type);
        $this->_result['data']['stock_type'] = $card_type;

        if (I('get._id')) {
            $search['_id'] = new \MongoId(I('get._id', null));
            $option = array();
            $query = $admin_card->findOne($search, $option);
            $query['date'] = date("Y-m-d H:i:s", $query['date']);
            $query['config'] = array_values($query['config']);
            $this->_result['data']['cards'] = $query;
        } else {
            $search = array();
            $limit = intval(I('get.limit', C('PAGE_NUM')));
            $skip = (intval(I('get.p', 1)) - 1) * $limit;
            filter_array_element($search);
            filter_array_element($option);

            $cursor = $admin_card->find($search)->limit($limit)->skip($skip);
            $result = array();
            foreach ($cursor as $item) {
                $item['type_name'] = $card_type[$item['type']];
                $item['date'] = date("Y-m-d H:i:s", $item['date']);
                array_push($result, $item);
            }

            $count = $admin_card->count($search);
            $page = new Page($count, $limit);
            $page = $page->show();

            $this->assign("page", $page);
            $this->assign("cards", $result);
            $this->assign("card_type", $card_type);
            $this->_result['data']['html'] = $this->fetch("Card:index");

            $this->_result['data']['count'] = $count;
            $this->_result['data']['page'] = $page;
            $this->_result['data']['cards'] = $result;
        }
        $this->response($this->_result);
    }

    public function cardsPut() {

        $search['_id'] = new \MongoId(I('put._id'));
        $data['desc'] = I('put.desc', null, check_empty_string);
        $data['name'] = I('put.name', null, check_empty_string);
        $data['price'] = intval(I('put.price', 0));
        $data['status'] = intval(I('put.status', 0));
        $data['admin'] = $_SESSION[MODULE_NAME.'_admin']['username'];
        merge_params_error($data['desc'], 'desc', '请填写描述信息', $this->_result['error']);
        merge_params_error($data['name'], 'name', '名字不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }

        if ($data['price'] <= 0) {
            $this->response($this->_result, 'json', 400, '价格必须大于0');
        }

        //检查房卡配置
        $data['config'] = array();
        $config = I('put.config');
        $count = 0;
        foreach ($config as $key => $value) {
            $num = intval($value);
            $count += $num;
            $data['config'][$key] = $num;
        }
        if ($count <= 0) {
            $this->response($this->_result, 'json', 400, '配置数量必须大于0');
        }
        filter_array_element($data);

        $update['$set'] = $data;
        $admin_card = $this->mongo_db->admin_card;
        if ($admin_card->update($search,$update)) {
            $this->response($this->_result, 'json', 201, '保存成功');
        } else {
            $this->_result['data']['param'] = $data;
            $this->response($this->_result, 'json', 400, '保存失败');
        }

    }

    public function cardsPost() {
        $admin_card = $this->mongo_db->admin_card;
        $data['desc'] = I('post.desc', null, check_empty_string);
        $data['name'] = I('post.name', null, check_empty_string);
        $data['price'] = intval(I('post.price', 0));
        $data['status'] = intval(I('post.status', 0));
        $data['admin'] = $_SESSION[MODULE_NAME.'_admin']['username'];
        $data['date'] = time();
        merge_params_error($data['desc'], 'desc', '请填写描述信息', $this->_result['error']);
        merge_params_error($data['name'], 'name', '名字不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }

        if ($data['price'] <= 0) {
            $this->response($this->_result, 'json', 400, '价格必须大于0');
        }

        //检查房卡配置
        $data['config'] = array();
        $config = I('post.config');
        $count = 0;
        foreach ($config as $key => $value) {
            $num = intval($value);
            $count += $num;
            $data['config'][$key] = $num;
        }
        if ($count <= 0) {
            $this->response($this->_result, 'json', 400, '配置数量必须大于0');
        }
        filter_array_element($data);
        if ($admin_card->insert($data)) {
            $this->_result['data']['url'] = U(MODULE_NAME.'/card/cards');
            $this->response($this->_result, 'json', 201, '新建成功');
        } else {
            $this->response($this->_result, 'json', 400, '新建失败');
        }
    }

    public function cardsDelete() {
        $search['_id'] = new \MongoId(I('delete._id'));
        $admin_card = $this->mongo_db->admin_card;
        if ($admin_card->remove($search)) {
            $this->response($this->_result, 'json', 204, '删除成功');
        } else {
            $this->response($this->_result, 'json', 400, '删除失败');
        }
    }
}