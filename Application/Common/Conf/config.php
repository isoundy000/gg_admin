<?php
return array(
    //基本配置
    'MODULE_ALLOW_LIST' => array('Admin','Agent'),
    'DEFAULT_MODULE' => 'Admin', //默认模块
    'URL_CASE_INSENSITIVE' => true, //URL不区分大小写
    'URL_MODEL' => 2,

    //REST API
    'REST_ENABLE' => true,
    'REST_GET' => 'Get',
    'REST_POST' => 'Post',
    'REST_PUT' => 'Put',
    'REST_DELETE' => 'Delete',

    //数据库配置 mongodb socketTimeoutMS=-1
    'MONGO_SERVER' => 'mongodb://backdbuser:backdbpwd@115.28.230.12:27017/backDb?authMechanism=SCRAM-SHA-1',
    'MONGO_DB' => 'backDb',

    'LAYOUT_ON' => true,
    'LAYOUT_NAME' => 'layout',

    //相关计划任务KEY
    //配置后到Monitor目录将每个shell脚本中的key参数值替换成该配置中的值
    //shell脚本中的域名地址也应换成对应的服务器域名
    //例如:curl http://www.example.com/index.php/Admin/Monitor/buildIndex/key/3fa283d936cc83a698bfa14e94eced9b >> /home/bash.log;
    //应将www.example.com替换成真实域名，key的值替换成APP_KEY的MD5值
    'APP_KEY' => 'ggmj',

    //分页
    'PAGE_NUM' => 10,
    'PAGE_CALLBACK' => 'menuClick',

    //微信公众号配置
    'WEIXIN' => array(
        'APP_ID' => 'wx4437df34cce0da84',
        'APP_SECRET' => '777c789ebef9977348aa1d78ec2bf29c',
        'TOKEN' => 'ggmj',
    ),

    //阿里大于，手机短信配置
    'DAYU' => array(
        'APP_ID' => '23576696', //应用ID
        'APP_SECRET' => 'afa62f19770ce40055b49e9837fb759f', //应用secret
        'SIGN_NAME' => '杠杠游戏', //签名 一般为公司名称
        'TEMPLATE_CODE' => 'SMS_39220246', //模板ID，模板可配置变量
    ),

    //业务配置
    'SYSTEM' => array(
        'AGENT_TYPE' => array(//代理类型
            1 => '钻石',
            2 => '金牌',
        ),
        'MODULE_LIST' => array(//模块列表
            'Admin' => 'Admin',
            'Agent' => 'Agent',
        ),
        'STOCK_TYPE' => array(//库存（房卡）类型
            1 => '普通',
            2 => '活动',
        ),
        'STOCK_AMOUNT_TYPE' => array(//库存数量
            10000 => 10000,
            20000 => 20000,
            30000 => 30000,
        ),
    ),
);