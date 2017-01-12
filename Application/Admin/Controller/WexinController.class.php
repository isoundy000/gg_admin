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

class WexinController extends RestController {
    //服务器身份验证
    public function AuthenticationGet() {
        $wechatObj = new WxCallBack(C('WEIXIN.TOKEN'));
        $wechatObj->valid();
    }
    public function tokenGet() {

    }
}