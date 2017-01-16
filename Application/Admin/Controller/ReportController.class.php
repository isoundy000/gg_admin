<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2017/1/11
 * Time: 21:07
 */

namespace Admin\Controller;

use Common\Controller\BaseController;

class ReportController extends BaseController {

    public function streamGet() {
        $agent_type = C('SYSTEM.AGENT_TYPE');
        $this->assign("agent_type", $agent_type);
        $game_type = C('SYSTEM.GAME');
        $this->assign("game_type", $game_type);

        $type = I('get.type', 'day');
        switch ($type) {
            case 'day':
                break;
            case 'month':
                break;
        }
        $table_name = "admin_report_stream_" . $type;
        $table = $this->mongo_db->$table_name;

        $search = array();
        $limit = intval(I('get.limit', C('PAGE_NUM')));
        $skip = (intval(I('get.p', 1)) - 1) * $limit;
        $search['date'] = I('get.date', null);
        if ($search['date']) {
            $search['date'] = rangeDate($search['date']);
            $search['date'] = array('$gte' => $search['date'][0], '$lte' => $search['date'][1]);
        }
        filter_array_element($search);
        $cursor = $table->find($search)->limit($limit)->skip($skip)->sort(array("date"=>-1));
        $result = array();
        foreach ($cursor as $item) {
            $item['date'] = date("Y-m-d", $item['date']);
            $item['game'] = $game_type[$item['game']];
            foreach ($item['buy_card'] as $k => $v) {
                $item['buy_card'][$k] = array(
                    'name' => $agent_type[$k],
                    'amount' => $v
                );
            }
            array_push($result, $item);
        }
        $this->assign("stream", $result);
        $this->_result['data']['html'] = $this->fetch("Report:stream");
        $this->_result['data']['stream'] = $result;
        $this->response($this->_result);
    }

}