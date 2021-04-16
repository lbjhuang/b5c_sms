<?php

/**
 * User: shenmo
 * Date: 19/07/29
 * Time: 10:46
 **/

/**
 * Class TbTrademarkDetailModel
 *
 * @property int $id
 * @property int $trademark_base_id
 * @property string $img_url
 * @property string $country_code
 * @property string $company_code
 * @property string $register_code
 * @property string $international_type
 * @property string $goods
 * @property string $goods_en
 * @property int $is_delete_state
 * @property string $applied_date
 * @property string $applicant_name
 * @property string $applicant_name_en
 * @property string $applicant_address
 * @property string $applicant_address_en
 * @property string $initial_review_date
 * @property string $register_date
 * @property string $trademark_type
 * @property string $exclusive_period
 * @property string $inter_register_date
 * @property string $late_specified_date
 * @property string $priority_date
 * @property string $agent
 * @property string $agent_en
 * @property string $current_state
 * @property string $current_state_en
 * @property string $remark
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class TbTrademarkDetailModel extends BaseModel
{

	public function __construct($name = '')
	{
		parent::__construct($name);
	}

	public $params;
	protected $trueTableName = 'tb_trademark_detail';
	protected $_auto = [
		['create_at', 'getTime', Model::MODEL_INSERT, 'callback'],
		['create_by', 'getName', Model::MODEL_INSERT, 'callback'],
		['update_at', 'getTime', Model::MODEL_UPDATE, 'callback'],
		['update_by', 'getName', Model::MODEL_UPDATE, 'callback'],
	];

	const IS_DELETE_STATE_NO = 0; //正常
	const IS_DELETE_STATE_YES = 1; //删除

	/**
	 * ODM-新增商标国家详情信息
	 *
	 * @param $params
	 *
	 * @return array
	 */
	public static function createTrademarkDetail($params)
	{
		$trademarkDetailModel = M('_trademark_detail', 'tb_');
		return $trademarkDetailModel->addAll($params);
	}

	public static function createTrademarkDetailExport($params)
	{
        $params['created_by']     = DataModel::userNamePinyin();
        $params['updated_by']     = DataModel::userNamePinyin();

		$trademarkDetailModel = M('_trademark_detail', 'tb_');
		return $trademarkDetailModel->add($params);
	}

	/**
	 * ODM-编辑商标国家详情信息
	 *
	 * @param $params
	 *
	 * @return array
	 */
	public static function updateTrademarkDetail($params, $trademark_base_id)
	{
		$trademarkDetailModel = M('_trademark_detail', 'tb_');
		return $trademarkDetailModel->where(['trademark_base_id' => $trademark_base_id])->save($params);
	}

	/**
	 * ODM-删除商标国家详情信息
	 *
	 * @param $where
	 *
	 * @return array
	 */
	public static function removeTrademarkDetail($where)
	{
		$trademarkDetailModel = M('_trademark_detail', 'tb_');
		return $trademarkDetailModel->where($where)->delete();
	}
}
