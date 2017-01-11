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
            $this->_result['data']['limitation'] = $query;
        } else {
            $limit = intval(I('get.limit', C('PAGE_NUM')));
            $skip = (intval(I('get.p', 1)) - 1) * $limit;
            filter_array_element($search);

            $cursor = $admin_limitation->find($search)->limit($limit)->skip($skip)->sort(array("start_date" => -1));
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
}