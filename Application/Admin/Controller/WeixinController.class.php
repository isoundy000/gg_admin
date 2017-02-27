<?php
/**
 * Created by PhpStorm.
 * User: JSS
 * Date: 2017/1/12
 * Time: 16:56
 */

namespace Admin\Controller;
use Think\Controller\RestController;
use Think\WxCallBack;

class WeixinController extends RestController {
    //服务器身份验证
    public function AuthenticationGet() {
        $wechatObj = new WxCallBack(C('WEIXIN.TOKEN'));
        $wechatObj->valid();
    }

    public function cardGet() {
        layout(false);
        //获取授权code
        $code = I('get.code');
        $mongo_client = new \MongoClient(C('MONGO_SERVER'));
        $db_name = C('MONGO_DB');
        $db = $mongo_client->$db_name;
        //时段是否可以领取房卡
        $admin_card_daily = $db->admin_card_daily;
        $time = time() - strtotime(date("Y-m-d 00:00:00", time()));
        $cursor = $admin_card_daily->find();
        $period = "";
        foreach ($cursor as $item) {
            if ($item['start_time'] <= $time && $item['end_time'] >= $time) {
                $item['code'] = $code;
                $period = $item;
                break;
            }
        }
        $this->assign("amount", $period['amount']);
        $this->assign("code", $period['code']);
        $this->display("Weixin:card");
    }

    //获取房卡
    public function receiveGet() {
        $data['code'] = I('get.code');
        $data['appid'] = C('WEIXIN.APP_ID');
        $data['secret'] = C('WEIXIN.APP_SECRET');
        $data['grant_type'] = 'authorization_code';
        //获取openID
        $api = C('WEIXIN.WX_OPENID_TOKEN_URL') . "?" . http_build_query($data);
        $result = file_get_contents($api);
        $result = json_decode($result, true);
        if ($result['access_token']) {
            $union_data['access_token'] = $result['access_token'];
            $union_data['openid'] = $result['openid'];
            $union_data['lang'] = 'zh_CN';
            $union_api = C('WEIXIN.WX_UNION_ID_URL') . "?" . http_build_query($union_data);
            $union_result = file_get_contents($union_api);
            $union_result = json_decode($union_result, true);
            if ($union_result['unionid']) {
                //用union id 表示openid
                $result['openid'] = $union_result['unionid'];
                //时段是否可以领取房卡
                $mongo_client = new \MongoClient(C('MONGO_SERVER'));
                $db_name = C('MONGO_DB');
                $db = $mongo_client->$db_name;
                $admin_card_daily = $db->admin_card_daily;
                $time = time() - strtotime(date("Y-m-d 00:00:00", time()));
                $cursor = $admin_card_daily->find();
                $period = "";
                foreach ($cursor as $item) {
                    if ($item['start_time'] <= $time && $item['end_time'] >= $time) {
                        $period = $item;
                        break;
                    }
                }
                if ($period) {
                    //完成领取
                    $info['amount'] = $period['amount'];
                    //查询roleid是否存在
                    $role_info = $db->role_info;
                    $role = $role_info->findOne(array("openid" => $result['openid']));
                    if ($role) {
                        $info['roleid'] = $role['roleid'];
                        $info['nickname'] = $role['nickname'];
                        $info['date'] = time();
                        $info['start_time'] = $period['start_time'];
                        $info['end_time'] = $period['end_time'];

                        //查询是否已经领取该时段的奖励
                        $admin_card_receive_daily = $db->admin_card_receive_daily;
                        //查找最近一条记录
                        $award = $admin_card_receive_daily->find(array(
                            'roleid' => $info['roleid'],
                            'start_time' => $info['start_time'],
                            'end_time' => $info['end_time']
                        ))->sort(array("date"=>-1))->limit(1);
                        //如果有记录，判断这条记录是不是今天的
                        $today = date("Y-m-d", time());
                        $day = "";
                        if ($award) {
                            $record = "";
                            foreach($award as $item) {
                                $record = $item;
                            }
                            $day = date("Y-m-d", $record['date']);
                        }
                        if (!$award) {//如果不存在记录
                            $admin_card_receive_daily->insert($info);
                            $admin_card_receive_daily_mmo = $db->admin_card_receive_daily_mmo;
                            $admin_card_receive_daily_mmo->insert($info);
                            $this->_result['data']['info'] = $info;
                            $this->response($this->_result, 'json', 201, '操作成功');
                        } else if ($today!=$day) {//如果已存在但不是今天
                            $admin_card_receive_daily->insert($info);
                            $admin_card_receive_daily_mmo = $db->admin_card_receive_daily_mmo;
                            $admin_card_receive_daily_mmo->insert($info);
                            $this->_result['data']['info'] = $info;
                            $this->response($this->_result, 'json', 201, '操作成功');
                        }
                    } else {
                        $this->_result['data']['info'] = 0;
                        $this->response($this->_result, 'json', 201, '用户未注册');
                    }
                } else {
                    $this->_result['data']['info'] = 1;
                    $this->response($this->_result, 'json', 201, '已过期');
                }
            }
        }
        $this->response($this->_result, 'json', 400, '操作失败');
    }

    //获取TOKEN
    private function tokenGet() {
        $api = C('WEIXIN.WX_ACCESS_TOKEN_URL') . '&' .
            http_build_query(array('appid'=>C('WEIXIN.APP_ID'), 'secret' => C('WEIXIN.APP_SECRET')));
        $result = file_get_contents($api);
        $result = json_decode($result, true);
        if ($result['access_token']) {
            //写入session缓存
            //redis_session_set('wx_access_token', $result['access_token'], 7000);
            return $result['access_token'];
        } else {
            return false;
        }
    }
}