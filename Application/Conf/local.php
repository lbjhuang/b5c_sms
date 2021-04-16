<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2016/11/8
 * Time: 13:14
 */
define('APP_STATUS','local');
define('HOST_URL','http://b5caiapi.stage.com/');
define('HOST_S_URL','http://s.stage.com/');
define('API_SEARCH','http://s.stage.com/');
//define('HOST_URL', 'http://172.16.1.217:8090/');
define('PAY_URL_API','http://pay.b5cai.com');
define('HOST_URL_API','http://b5caiapi.stage.com');
define('OLD_HOST_URL_API','http://172.16.222.75:8080');
define('GP_SMS_URL','http://172.16.111.93');
define('GP_SMS_KEY','pm0MLbRZkTG8XMoJHnscdQ07PqhJOx');
//define('HOST_URL_API','172.16.1.217:8090');
define('GO_URL','http://erp.gshopper.stage.com/index.php?m=orders&a=orderdetail_self&ordId=');
define('SMS2_URL','http://erp.gshopper.stage.com/');
define('ERP_URL','http://erp.gshopper.stage.com/');
define('ERP_CRON_TASK_URL','http://erp.gshopper.stage.com/');
define('BI_API_REVEN','http://3rd-biapi.izene.org');
define('THIRD_SHIP_API','http://172.16.1.217:8083/general/op/thirdDeliverGoods');
define('ADDRESS_URL','http://ucenter.stage.com/user/info/data/getAllArea.htm');
define('SYNC_URL','172.16.1.217:8083/general/op/crawlerOrder.ajax');
//define('SALE_CHANNEL','销售渠道');
define('SALE_CHANNEL','SALE CHANNEL');
define('ROLE_ID',14);

/*define('EXCHANGENAME','sms2Test');
define('ROUTEKEY','sms');*/
define('EXCHANGENAME','gshopperExchange');
define('ROUTEKEY','Q-B5C2GS-03-RK-01');
define('INSIGHT','http://insight.gshopper.com');
define('GSHOPPER','http://openapi.stage.co.kr');
define('RECOMMENDKEYWORD_URL','http://openapi.stage.co.kr/recommend/keyWord.json');

define('PLAT_GSHOPPER_KR','N000831400');
define('ATTACHMENT_DIR','/opt/b5c-disk/hr/');
define('ATTACHMENT_DIR_LOGISTIC','/opt/b5c-disk/logistic/');
//define('ATTACHMENT_DIR_LOGISTIC', 'F:\hrrrrrr/');

define('AREA_DATA', 'http://i.b5cai.com/index/area.json');

//internal host
define('PMS_HOST','http://pms.gshopper.stage.com');
define('CMS_HOST','http://cms.gshopper.stage.com');
define('ERP_HOST','http://erp.gshopper.stage.com');

// Prev str SALE CHANNEL
define('SALE_CHANNEL_PREV','N00083');
// SALE CHANNEL ids for b5c customer
define('PLAT_B5C_CUSTOMER','N000831400,N000834100,N000834200,N000834300');

//define('REDIS_HOST', 'redis1.stage.com:6001,redis1.stage.com:6002,redis2.stage.com:7001,redis2.stage.com:7002,redis3.stage.com:8001,redis3.stage.com:8002');
define('REDIS_HOST', '172.16.11.21:6001,172.16.11.21:6002,172.16.11.22:7002,172.16.11.23:8001,172.16.11.23:8002');

// about b2b
define('APP_SEND_MAIL_TEST','');
define('ATTACHMENT_DIR_IMG','/opt/b5c-disk/img/');
define('ATTACHMENT_DIR_EXCEL','/opt/b5c-disk/excel/');

// BI API
define('BIADMIN','http://biadmin.gshopper.cn');

// Electronic order
// define('ELECTRONIC_HOST','http://172.16.13.114');
define('ELECTRONIC_HOST','http://172.16.1.217:8083/general');
define('THIRD_DELIVER_GOODS','http://general.b5cai.stage.com');

//PMS database name
define('PMS_DATABASE','gshopper_pms_stage');
define('B5C_DATABASE','b5c_stage');

// CreditGenerate key
define('GSHOPPRT_KEY','GSHOPPRTSTAGE');

// JAVA API
define('GENERAL_B5C','http://general.b5cai.stage.com');
define('WECHAT_API','https://api-stage.gshopper.com');
