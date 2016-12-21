<?php
return array(
	//基本配置
    'MODULE_ALLOW_LIST' => array('Admin','Agent'),
    'DEFAULT_MODULE' => 'Admin', //默认模块
    'URL_CASE_INSENSITIVE' => true, //URL不区分大小写
    'URL_MODEL' => 2, //PATH_INFO模式 http://www.example.com/admin/user

    //数据库配置 mongodb
    'MONGO_SERVER' => 'mongodb://127.0.0.1:27017',
    'MONGO_DB' => 'gg_admin',

);