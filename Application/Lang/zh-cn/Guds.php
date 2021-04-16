<?php
/**
 * Guds分组的语言包文件
 * User: afanti
 * Date: 2017/8/10
 * Time: 16:07
 */

return array(
    'INVALID_PARAMS' => '无效的参数，请检查参数正确性。',
    'SYSTEM_ERROR' =>'系统内部错误，无法成功处理您的请求。',
    'NOT_EXIST' => '抱歉，没有查询到您想要的记录，无法处理您的请求。',
    'DELETE_LAST_PRICE' => '当前SKU只有一个价格，删除后等同于SKU下架，前端不再展示。',
    'SKU_PRICE_DELETED' => '指定的价格信息已删除。',
    'CAN_NOT_MORE_SKU' => '指定商品已存在SKU，不可以再添加了。',
    'CAN_NOT_MODIFY_SKU_OPTION' => 'SKU选项和选项值不可以修改和添加。',
    'DUPLICATE_NAME' => '有另外一个重名的数据，请核实。',
    'LESS_FRONTEND_PUSH' => '抱歉，前端发布或者SKU销售状态时，务必填写全部信息。',
    'NEED_SALE_STATE' => '抱歉，前端发布时，务必填写SKU销售状态',
    'FRONTEND_NEED_DETAIL' => '前端发布必须填写商品详情信息',
    'MUST_FULFILL_IMAGE' => '缺少商品图片信息！',
    'GUDS_NEED_ORIGIN' => '请您必须为商品指定原产国！',
    'GUDS_NEED_CURRENCY' => '请为商品指定币种!',
    'NEED_CHINESE_CONTENT' => '请您务必添写中文版本商品信息内容！',
    'NEED_ENGLISH_CONTENT' => '请您务必添写英文版本商品信息内容！',
    'NEED_KOREAN_CONTENT' => '请您务必为产地为韩国的商品填写韩文内容！',
    'NEED_JAPANESE_CONTENT' => '请您务必为产地为日本的商品填写日文内容!',
    'NEED_WEIGHT' => '请您务必为SKU填写重量属性！',
    'NEED_PURCHASE_PRICE' => '请您务必为SKU填写采购价!',
    'OPTION_CODE_DUPLICATE' => '存在重复的SKU自编码，请核实是否为同一个物理商品！',
    'BACKEND_CATEGORY_NEED_BIND' => '后端类目需要先正确绑定前段类目，否则自编码无法生成!',
    'BAR_CODE_NEED_NUMBER' => '商品条形码必须是 8位以上数字格式，不允许有其他字符！',
    'OPTION_CODE_INVALID' => '商品SKU自编码格式错误，请确认格式是否正确!',
    'NEED_EXPRESS_CONFIG' => '请填写海关报关信息和物流派单信息！',
    'REVIEW_ONLY_TOBE_PENDING' => '审核状态只能从草稿提交审核变更为待审核状态。',
    'REVIEW_ONLY_TOBE_DONE' => '审核状态只能从待审核改为审核通过或者驳回。',
    'REVIEW_PROCESSED' => '审核请求已经处理完毕(可能被其他人处理)，请刷新确认审核情况。',
);