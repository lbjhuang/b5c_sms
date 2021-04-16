<?php

/**
 * User: yangsu
 * Date: Thu, 23 Aug 2018 07:00:46 +0000.
 */

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbOpOrder
 * 
 * @property int $ID
 * @property string $ORDER_ID
 * @property string $PLAT_CD
 * @property string $PLAT_NAME
 * @property string $SHOP_ID
 * @property \Carbon\Carbon $ORDER_UPDATE_TIME
 * @property \Carbon\Carbon $ORDER_TIME
 * @property \Carbon\Carbon $ORDER_PAY_TIME
 * @property \Carbon\Carbon $ORDER_CREATE_TIME
 * @property string $USER_ID
 * @property string $USER_NAME
 * @property string $USER_EMAIL
 * @property string $ADDRESS_USER_NAME
 * @property string $ADDRESS_USER_PHONE
 * @property string $ADDRESS_USER_COUNTRY
 * @property string $ADDRESS_USER_COUNTRY_ID
 * @property string $ADDRESS_USER_COUNTRY_CODE
 * @property string $ADDRESS_USER_COUNTRY_EDIT
 * @property string $ADDRESS_USER_CITY
 * @property string $ADDRESS_USER_PROVINCES
 * @property string $ADDRESS_USER_REGION
 * @property string $ADDRESS_USER_ADDRESS1
 * @property string $ADDRESS_USER_ADDRESS2
 * @property string $ADDRESS_USER_ADDRESS3
 * @property string $ADDRESS_USER_ADDRESS4
 * @property string $ADDRESS_USER_ADDRESS5
 * @property string $ADDRESS_USER_POST_CODE
 * @property string $PAY_CURRENCY
 * @property float $PAY_ITEM_PRICE
 * @property float $PAY_TOTAL_PRICE
 * @property float $PAY_SHIPING_PRICE
 * @property float $PAY_VOUCHER_AMOUNT
 * @property float $PAY_PRICE
 * @property string $PAY_METHOD
 * @property string $PAY_TRANSACTION_ID
 * @property string $SHIPPING_TYPE
 * @property string $SHIPPING_DELIVERY_COMPANY
 * @property string $SHIPPING_DELIVERY_COMPANY_CD
 * @property string $SHIPPING_TRACKING_CODE
 * @property string $SHIPPING_MSG
 * @property \Carbon\Carbon $CRAWLER_DATE
 * @property \Carbon\Carbon $UPDATE_TIME
 * @property string $ORDER_STATUS
 * @property string $ORDER_NUMBER
 * @property string $SITE
 * @property string $ADDRESS_USER_ADDRESS_MSG
 * @property string $BWC_ORDER_STATUS
 * @property float $PAY_SETTLE_PRICE
 * @property string $BWC_USER_ID
 * @property string $PACK_NO
 * @property string $PACKING_NO
 * @property string $RELATED_ORDER
 * @property string $SELLER_DELIVERY_NO
 * @property string $B5C_ORDER_NO
 * @property string $B5C_ACCOUNT_ID
 * @property int $SHORT_SUPPLY
 * @property int $B5C_ORDER_DES_COUNT
 * @property \Carbon\Carbon $SHIPPING_TIME
 * @property string $REFUND_STAT_CD
 * @property string $RECEIVER_TEL
 * @property string $BUYER_TEL
 * @property string $BUYER_MOBILE
 * @property int $REMARK_STAT_CD
 * @property string $ORDER_STATUS1
 * @property bool $FAIL_TIMES
 * @property string $REMARK_MSG
 * @property string $CREATE_USER
 * @property string $FILE_NAME
 * @property float $TARIFF
 * @property bool $THIRD_DELIVER_STATUS
 * @property int $STORE_ID
 * @property string $SHIPPING_NUMBER
 * @property string $ORDER_SEQUENCE
 * @property string $ORDER_NO
 * @property string $SHOP_STORE_MAPPING
 * @property string $b5c_logistics_cd
 * @property string $UPDATE_USER_LAST
 * @property string $SEND_ORD_STATUS
 * @property string $WAREHOUSE
 * @property string $SEND_ORD_MSG
 * @property \Carbon\Carbon $SEND_ORD_TIME
 * @property string $SELLER_ID
 * @property string $MPS
 * @property string $SOURCE
 * @property string $FIND_ORDER_JSON
 * @property string $FIND_ORDER_ERROR_TYPE
 * @property int $FIND_ORDER_INVOKE_TIMES
 * @property string $FIND_ORDER_FAIL_MSG
 * @property \Carbon\Carbon $FIND_ORDER_TIME
 * @property string $PARENT_ORDER_ID
 * @property string $CHILD_ORDER_ID
 * @property string $COUPONS_ID
 * @property int $CANAL_BATCH_VAL
 * @property int $receiver_cust_id
 * @property string $logistic_cd
 * @property int $logistic_model_id
 * @property string $SURFACE_WAY_GET_CD
 * @property float $PAY_TOTAL_PRICE_DOLLAR
 * @property float $SEND_FREIGHT
 * @property int $SEND_ORD_TYPE
 * @property string $ADVANCE_ORD_TYPE_CD
 * @property string $ADVANCE_ORD_MSG
 * @property \Carbon\Carbon $ADVANCE_ORD_TIME
 * @property string $ADVANCE_ORD_USER
 * @property string $SEND_ORD_TYPE_CD
 * @property string $SEND_ORD_USER
 * @property string $LOGISTICS_SINGLE_STATU_CD
 * @property \Carbon\Carbon $LOGISTICS_SINGLE_UP_TIME
 * @property string $LOGISTICS_SINGLE_ERROR_MSG
 * @property string $SEND_ORD_ERROR_STATUS_CD
 * @property string $voucher_code
 * @property string $ADDRESS_USER_IDENTITY_CARD
 * @property int $SEARCH_BACK
 * @property int $has_default_warehouse
 * @property string $default_warehouse_logistics
 * @property int $is_mark
 * @property string $freight_type
 * @property string $amount_freight
 *
 * @package App\Models
 */
class TbOpOrder extends ORM
{
	protected $table = 'tb_op_order';
	protected $primaryKey = 'ID';
	public $timestamps = false;

	protected $casts = [
		'PAY_ITEM_PRICE' => 'float',
		'PAY_TOTAL_PRICE' => 'float',
		'PAY_SHIPING_PRICE' => 'float',
		'PAY_VOUCHER_AMOUNT' => 'float',
		'PAY_PRICE' => 'float',
		'PAY_SETTLE_PRICE' => 'float',
		'SHORT_SUPPLY' => 'int',
		'B5C_ORDER_DES_COUNT' => 'int',
		'REMARK_STAT_CD' => 'int',
		'FAIL_TIMES' => 'bool',
		'TARIFF' => 'float',
		'THIRD_DELIVER_STATUS' => 'bool',
		'STORE_ID' => 'int',
		'FIND_ORDER_INVOKE_TIMES' => 'int',
		'CANAL_BATCH_VAL' => 'int',
		'receiver_cust_id' => 'int',
		'logistic_model_id' => 'int',
		'PAY_TOTAL_PRICE_DOLLAR' => 'float',
		'SEND_FREIGHT' => 'float',
		'SEND_ORD_TYPE' => 'int',
		'SEARCH_BACK' => 'int',
		'has_default_warehouse' => 'int',
		'is_mark' => 'int'
	];

	protected $dates = [
		'ORDER_UPDATE_TIME',
		'ORDER_TIME',
		'ORDER_PAY_TIME',
		'ORDER_CREATE_TIME',
		'CRAWLER_DATE',
		'UPDATE_TIME',
		'SHIPPING_TIME',
		'SEND_ORD_TIME',
		'FIND_ORDER_TIME',
		'ADVANCE_ORD_TIME',
		'LOGISTICS_SINGLE_UP_TIME'
	];

	protected $fillable = [
		'ORDER_ID',
		'PLAT_CD',
		'PLAT_NAME',
		'SHOP_ID',
		'ORDER_UPDATE_TIME',
		'ORDER_TIME',
		'ORDER_PAY_TIME',
		'ORDER_CREATE_TIME',
		'USER_ID',
		'USER_NAME',
		'USER_EMAIL',
		'ADDRESS_USER_NAME',
		'ADDRESS_USER_PHONE',
		'ADDRESS_USER_COUNTRY',
		'ADDRESS_USER_COUNTRY_ID',
		'ADDRESS_USER_COUNTRY_CODE',
		'ADDRESS_USER_COUNTRY_EDIT',
		'ADDRESS_USER_CITY',
		'ADDRESS_USER_PROVINCES',
		'ADDRESS_USER_REGION',
		'ADDRESS_USER_ADDRESS1',
		'ADDRESS_USER_ADDRESS2',
		'ADDRESS_USER_ADDRESS3',
		'ADDRESS_USER_ADDRESS4',
		'ADDRESS_USER_ADDRESS5',
		'ADDRESS_USER_POST_CODE',
		'PAY_CURRENCY',
		'PAY_ITEM_PRICE',
		'PAY_TOTAL_PRICE',
		'PAY_SHIPING_PRICE',
		'PAY_VOUCHER_AMOUNT',
		'PAY_PRICE',
		'PAY_METHOD',
		'PAY_TRANSACTION_ID',
		'SHIPPING_TYPE',
		'SHIPPING_DELIVERY_COMPANY',
		'SHIPPING_DELIVERY_COMPANY_CD',
		'SHIPPING_TRACKING_CODE',
		'SHIPPING_MSG',
		'CRAWLER_DATE',
		'UPDATE_TIME',
		'ORDER_STATUS',
		'ORDER_NUMBER',
		'SITE',
		'ADDRESS_USER_ADDRESS_MSG',
		'BWC_ORDER_STATUS',
		'PAY_SETTLE_PRICE',
		'BWC_USER_ID',
		'PACK_NO',
		'PACKING_NO',
		'RELATED_ORDER',
		'SELLER_DELIVERY_NO',
		'B5C_ORDER_NO',
		'B5C_ACCOUNT_ID',
		'SHORT_SUPPLY',
		'B5C_ORDER_DES_COUNT',
		'SHIPPING_TIME',
		'REFUND_STAT_CD',
		'RECEIVER_TEL',
		'BUYER_TEL',
		'BUYER_MOBILE',
		'REMARK_STAT_CD',
		'ORDER_STATUS1',
		'FAIL_TIMES',
		'REMARK_MSG',
		'CREATE_USER',
		'FILE_NAME',
		'TARIFF',
		'THIRD_DELIVER_STATUS',
		'STORE_ID',
		'SHIPPING_NUMBER',
		'ORDER_SEQUENCE',
		'ORDER_NO',
		'SHOP_STORE_MAPPING',
		'b5c_logistics_cd',
		'UPDATE_USER_LAST',
		'SEND_ORD_STATUS',
		'WAREHOUSE',
		'SEND_ORD_MSG',
		'SEND_ORD_TIME',
		'SELLER_ID',
		'MPS',
		'SOURCE',
		'FIND_ORDER_JSON',
		'FIND_ORDER_ERROR_TYPE',
		'FIND_ORDER_INVOKE_TIMES',
		'FIND_ORDER_FAIL_MSG',
		'FIND_ORDER_TIME',
		'PARENT_ORDER_ID',
		'CHILD_ORDER_ID',
		'COUPONS_ID',
		'CANAL_BATCH_VAL',
		'receiver_cust_id',
		'logistic_cd',
		'logistic_model_id',
		'SURFACE_WAY_GET_CD',
		'PAY_TOTAL_PRICE_DOLLAR',
		'SEND_FREIGHT',
		'SEND_ORD_TYPE',
		'ADVANCE_ORD_TYPE_CD',
		'ADVANCE_ORD_MSG',
		'ADVANCE_ORD_TIME',
		'ADVANCE_ORD_USER',
		'SEND_ORD_TYPE_CD',
		'SEND_ORD_USER',
		'LOGISTICS_SINGLE_STATU_CD',
		'LOGISTICS_SINGLE_UP_TIME',
		'LOGISTICS_SINGLE_ERROR_MSG',
		'SEND_ORD_ERROR_STATUS_CD',
		'voucher_code',
		'ADDRESS_USER_IDENTITY_CARD',
		'SEARCH_BACK',
		'has_default_warehouse',
		'default_warehouse_logistics',
		'is_mark',
		'freight_type',
		'amount_freight'
	];
}
