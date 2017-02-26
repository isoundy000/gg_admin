<?php
return array(
    //计划任务的配置
    //在crontab里配置每日，每月等计划任务
    //示例:
    //#每天计划任务（0点）
    //5 0 * * * /bin/bash /var/www/html/ggmj/Monitor/crontab_per_day.sh
    //$每月计划任务每月第一天
    //30 0 1 * * /bin/bash /var/www/html/ggmj/Monitor/crontab_per_month.sh
    //
    //你需要
    //1. 配置正确的shell执行路径，即当前服务器shell的绝对路径
    //2. Monitor目录下把shell脚本里的域名配置成当前服务器的域名

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
    'APP_KEY' => 'ggmj', //3fa283d936cc83a698bfa14e94eced9b

    //分页
    'PAGE_NUM' => 10,
    'PAGE_CALLBACK' => 'menuClick',

    //微信公众号配置
    'WEIXIN' => array(
        'APP_ID' => 'wx4437df34cce0da84',
        'APP_SECRET' => '777c789ebef9977348aa1d78ec2bf29c',
        'TOKEN' => 'ggmj',
        'WX_ACCESS_TOKEN_URL' => 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential', //获取TOKEN
        'WX_OPENID_TOKEN_URL' => 'https://api.weixin.qq.com/sns/oauth2/access_token', //获取openId的URL
        'WX_UNION_ID_URL' => 'https://api.weixin.qq.com/sns/userinfo', //获取union id
    ),

    //阿里大于，手机短信配置
    'DAYU' => array(
        'APP_ID' => '23576696', //应用ID
        'APP_SECRET' => 'afa62f19770ce40055b49e9837fb759f', //应用secret
        'SIGN_NAME' => '杠杠游戏', //签名 一般为公司名称
        'TEMPLATE_CODE' => 'SMS_39220246', //模板ID，模板可配置变量
    ),

    //业务配置
    'SYSTEM' => array(//业务字段可自定义配置
        'GAME' => array(
            1 => '杠杠麻将',
            2 => '新游戏',
        ),
        'TABLE_TYPE' => array(
            4 => '4局',
            8 => '8局',
            16 => '16局',
            1020 => '20分钟局',
            1040 => '40分钟局',
            1080 => '80分钟局',
            1100 => '100分钟局',
            1120 => '120分钟局',
        ),
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