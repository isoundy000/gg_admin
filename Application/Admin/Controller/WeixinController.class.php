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
        echo "领取房卡";
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