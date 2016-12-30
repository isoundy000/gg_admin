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

class StockController extends BaseController
{
    public function stocksGet()
    {
        $admin_user = $this->mongo_db->admin_user;

        $stock_type = C('SYSTEM.STOCK_TYPE');
        $stock_amount_type = C('SYSTEM.STOCK_AMOUNT_TYPE');
        $stock_amount = $admin_user->findOne(array("_id"=>$_SESSION[MODULE_NAME.'_admin']['_id']));
        $stock_amount = $stock_amount['stock_amount'];
        foreach ($stock_amount as $key => $value) {
            $stock_amount[$key] = array(
                'name' => $stock_type[$key],
                'amount' => $value
            );
        }
        $search = array();
        $tab = I('get.tab') ? I('get.tab') : 'edit';
        switch ($tab) {
            case 'edit'://申请页面
                $this->assign("stock_type", $stock_type);
                $this->assign("stock_amount", $stock_amount);
                $this->assign("stock_amount_type", $stock_amount_type);
                $html = $this->fetch("Stock:edit");
                $this->_result['data']['html'] = $html;
                $this->response($this->_result);
                break;
            case 'verify'://申请记录页面
                $search['verify'] = 0;
                $this->assign("query", "verify");
                $this->assign("title", "申请记录");
                break;
            case 'status'://审核结果页面
                $this->assign("title", "审核结果");
                $this->assign("query", "status");
                $search['verify'] = 1;
                break;
        }

        $admin_stock = $this->mongo_db->admin_stock;
        $limit = intval(I('get.limit', C('PAGE_NUM')));
        $skip = (intval(I('get.p', 1)) - 1) * $limit;
        filter_array_element($search);

        $cursor = $admin_stock->find($search)->limit($limit)->skip($skip)->sort(array("date" => -1));
        $result = array();
        foreach ($cursor as $item) {
            $item['type'] = $stock_type[$item['type']];
            $item['date'] = date("Y-m-d H:i:s", $item['date']);
            array_push($result, $item);
        }

        $count = $admin_stock->count($search);
        $page = new Page($count, C('PAGE_NUM'));
        $page = $page->show();

        $this->assign("page", $page);
        $this->assign("stocks", $result);
        $this->_result['data']['html'] = $this->fetch("Stock:index");

        $this->_result['data']['count'] = $count;
        $this->_result['data']['page'] = $page;
        $this->_result['data']['stocks'] = $result;
        $this->response($this->_result);
    }

    public function stocksPut()
    {
        $search['_id'] = new \MongoId(I('put._id'));
        $data['status'] = intval(I('put.status', null, check_empty_string));
        $data['verify'] = 1;//已审核
        $data['audit_user'] = $_SESSION[MODULE_NAME.'_admin']['username'];
        $data['audit_time'] = time();

        $update['$set'] = $data;
        $admin_stock = $this->mongo_db->admin_stock;
        $admin_user = $this->mongo_db->admin_user;

        if ($modify = $admin_stock->findAndModify($search, $update)) {
            //审核通过则，给apply_user记录生成的数量
            if ($data['status']) {
                $admin_user->update(array("username" => $modify['apply_user']),
                    array('$inc' =>
                        array(
                            "stock_amount.{$modify['type']}" => $modify['amount']
                        )
                    )
                );
            }
            $this->response($this->_result, 'json', 201, '保存成功');
        } else {
            $this->_result['data']['param'] = $data;
            $this->response($this->_result, 'json', 400, '保存失败');
        }

    }

    public function stocksPost()
    {
        $data['type'] = I('post.type', null, check_empty_string);
        $data['amount'] = I('post.amount', null, check_empty_string);
        $data['remark'] = I('post.remark', '');
        merge_params_error($data['type'], 'type', '类型不能为空', $this->_result['error']);
        merge_params_error($data['amount'], 'amount', '数量不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        filter_array_element($data);
        $data['type'] = intval($data['type']);
        $data['amount'] = intval($data['amount']);
        $data['apply_user'] = $_SESSION[MODULE_NAME.'_admin']['username'];
        $data['verify'] = 0;
        $data['status'] = 0;
        $data['date'] = time();

        $admin_stock = $this->mongo_db->admin_stock;
        if ($admin_stock->insert($data)) {
            $this->response($this->_result, 'json', 201, '新建成功');
        } else {
            $this->response($this->_result, 'json', 400, '新建失败');
        }
    }

    public function stocksDelete()
    {
        $search['_id'] = new \MongoId(I('delete._id'));
        $admin_stock = $this->mongo_db->admin_stock;
        if ($admin_stock->remove($search)) {
            $this->response($this->_result, 'json', 204, '删除成功');
        } else {
            $this->response($this->_result, 'json', 400, '删除失败');
        }
    }
}