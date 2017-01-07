<?php
    include "TopSdk.php";
    date_default_timezone_set('Asia/Shanghai'); 

    /*$httpdns = new HttpdnsGetRequest;
    $client = new ClusterTopClient("4272","0ebbcccfee18d7ad1aebc5b135ffa906");
    $client->gatewayUrl = "http://api.daily.taobao.net/router/rest";
    var_dump($client->execute($httpdns,"6100e23657fb0b2d0c78568e55a3031134be9a3a5d4b3a365753805"));*/

    
    $c = new TopClient;
    $c ->appkey = '23594607' ;
    $c ->secretKey = '95436c0d9318109e10dffa7fdaf34914' ;
    $req = new AlibabaAliqinFcSmsNumSendRequest;
    $req ->setExtend( "1234" );
    $req ->setSmsType( "normal" );
    $req ->setSmsFreeSignName( "捷叔叔" );
    $req ->setSmsParam( "{verify_code:'131420'}" );
    $req ->setRecNum( "13512782792" );
    $req ->setSmsTemplateCode( "SMS_39325049" );
    $resp = $c ->execute( $req );


?>