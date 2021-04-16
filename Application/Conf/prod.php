<?php
/**
 * User: yangsu
 * Date: 2018/05/16
 * Time: 13:14
 */
define('APP_STATUS','prod');
define('HOST_URL','http://b5caiapi.prod.com/');
define('HOST_S_URL','http://s.prod.com/');
define('API_SEARCH','http://s.prod.com/');
//define('HOST_URL', 'http://172.16.1.217:8090/');
define('PAY_URL_API','http://pay.b5cai.com');
define('HOST_URL_API','http://b5caiapi.prod.com');
define('OLD_HOST_URL_API','http://172.16.222.75:8080');
//define('HOST_URL_API','172.16.1.217:8090');
define('GO_URL','http://erp.gshopper.prod.com/index.php?m=orders&a=orderdetail_self&ordId=');
define('SMS2_URL','http://erp.gshopper.prod.com/');
define('ERP_URL','http://erp.gshopper.prod.com/');
define('ERP_CRON_TASK_URL','http://erp.gshopper.prod.com/');
define('BI_API_REVEN','http://3rd-biapi.izene.org');
define('THIRD_SHIP_API','http://172.16.1.217:8083/general/op/thirdDeliverGoods');
define('ADDRESS_URL','http://ucenter.prod.com/user/info/data/getAllArea.htm');
define('SYNC_URL','172.16.1.217:8083/general/op/crawlerOrder.ajax');
//define('SALE_CHANNEL','销售渠道');
define('SALE_CHANNEL','SALE CHANNEL');
define('ROLE_ID',14);

/*define('EXCHANGENAME','sms2Test');
define('ROUTEKEY','sms');*/
define('EXCHANGENAME','gshopperExchange');
define('ROUTEKEY','Q-B5C2GS-03-RK-01');
define('INSIGHT','http://insight.gshopper.com');
define('GSHOPPER','http://openapi.prod.co.kr');
define('RECOMMENDKEYWORD_URL','http://openapi.prod.co.kr/recommend/keyWord.json');

define('PLAT_GSHOPPER_KR','N000831400');
define('ATTACHMENT_DIR','/opt/b5c-disk/hr/');
define('ATTACHMENT_DIR_LOGISTIC','/opt/b5c-disk/logistic/');
//define('ATTACHMENT_DIR_LOGISTIC', 'F:\hrrrrrr/');

define('AREA_DATA', 'http://i.b5cai.com/index/area.json');

//internal host
define('PMS_HOST','http://pms.gshopper.prod.com');
define('CMS_HOST','http://cms.gshopper.prod.com');
define('ERP_HOST','http://erp.gshopper.prod.com');

// Prev str SALE CHANNEL
define('SALE_CHANNEL_PREV','N00083');
// SALE CHANNEL ids for b5c customer
define('PLAT_B5C_CUSTOMER','N000831400,N000834100,N000834200,N000834300');

define('REDIS_HOST', '172.16.111.18:6001,172.16.111.18:6002,172.16.111.18:6003');

// about b2b
define('APP_SEND_MAIL_TEST','');
define('ATTACHMENT_DIR_IMG','/opt/b5c-disk/img/');

// BI API
define('BIADMIN','http://biadmin.gshopper.cn');

// Electronic order
// define('ELECTRONIC_HOST','http://172.16.13.114');
define('ELECTRONIC_HOST','http://172.16.1.217:8083/general');
define('THIRD_DELIVER_GOODS','http://general.b5cai.prod.com');

//PMS database name
define('PMS_DATABASE','gshopper_pms_prod');
define('B5C_DATABASE','b5c_prod');

// CreditGenerate key
define('GSHOPPRT_KEY','GSHOPPRTprod');

// JAVA API
define('GENERAL_B5C','http://general.b5cai.prod.com');
define('WECHAT_API','https://api-prod.gshopper.com');
