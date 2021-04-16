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
    public $detail_model;
    public $base_model;

    const LEGAL_ROLE_ID = '135';  //法务角色id

	public function __construct($name = '')
	{
		parent::__construct($name);
		$this->detail_model = M('_trademark_detail', 'tb_');
		$this->base_model = M('_trademark_base', 'tb_');
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

    //申请审批流步骤
    static $step = [
        'waiting_commit_step'     => 'N003710001',//待提交申请
        'leader_approve_step'    => 'N003710002',//待领导审批
        'apply_registering_step' => 'N003710003',//申请注册中
        'turned_down_step'         => 'N003710004',//驳回
        'cancel_apply_step'   => 'N003710005',//取消流程
    ];

    static $operate_type = ['approve','turned_down','back_to_waiting_commit'];



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


    //保存或提交一个不存在的商标申请
    public function createTrademarkAndDetail($params)
    {
        $trademark_detail = $params['trademark_detail'];
        $trademark_base = $params['trademark_base'];
        $this->startTrans();
        $save_base_data = $this->makeBaseData($trademark_base);
        $save_base_data['created_by'] = userName();
        $save_base_data['created_at'] = date("Y-m-d H:i:s",time());
        
        $trademark_base_id = $this->base_model->add($save_base_data);
        if(!$trademark_base_id){
            $this->rollback();
            return ['data'=>'','info'=>'商标基础信息新增失败', 'status'=>0];
        }
        $save_detail_data = $this->makeDetailData($trademark_detail);
        $save_detail_data['trademark_base_id'] = $trademark_base_id;
        $save_detail_data['trademark_type_en'] = $trademark_base['trademark_type'];
        $save_detail_data['created_by'] = userName();
        $save_detail_data['created_at'] = date("Y-m-d H:i:s",time());

        $detail_res = $this->detail_model->add($save_detail_data);
        if (!$detail_res) {
            $this->rollback();
            return ['data'=>'','info'=>'商标详情信息新增失败', 'status'=>0];
        }else{
            $this->commit();
            if($trademark_detail['submit_to_next'] == '1') {
                $this->sendToLeader($save_detail_data, $trademark_base_id);
            }
        }
        return ['data'=>['trademark_base_id'=>$trademark_base_id],'info'=>'操作成功', 'status'=>1];
    }


	//取消申请
    public function cancelApply($id) {
        $result = ['data'=>'','info'=>'操作失败','status'=>0];
        $detail = $this->detail_model->where(['trademark_base_id'=>$id])->field('current_step')->find();
        $current_step = $detail['current_step'];
        if(!in_array($current_step, [self::$step['waiting_commit_step']])){
            $result = ['data'=>'','info'=>'流程节点有变动，暂时不能取消流程','status'=>0];
        }else{
            $res = $this->rejectApply($id, 'cancel_apply_step');
            if($res > 0) {
                $result = ['data'=>'','info'=>'操作成功','status'=>1];
            }
        }
        return $result;
    }


	//保存或提交一个已存在的申请
    public function submitExistApply($params) {
        $trademark_base_id = $params['trademark_base']['id'];
        $detail = $this->detail_model->where(['trademark_base_id'=>$trademark_base_id])->find();
        if($detail['current_step'] != self::$step['waiting_commit_step']){
            return ['data'=>'','info'=>'操作失败，申请已经提交上去了','status'=>0];
        }

        $trademark_base = $params['trademark_base'];
        $update_base_data = $this->makeBaseData($trademark_base);
        $base_res = $this->base_model->where(['id'=>$trademark_base_id])->save($update_base_data);

        $trademark_detail = $params['trademark_detail'];
        $update_detail_data = $this->makeDetailData($trademark_detail);
        $update_detail_data['trademark_type_en'] = $trademark_base['trademark_type'];

        $detail_res = $this->detail_model->where(['trademark_base_id'=>$trademark_base_id])->save($update_detail_data);
        if($trademark_detail['submit_to_next'] == '1') {
            $this->sendToLeader($update_detail_data, $trademark_base_id);
        }
        return  ['data'=>'','info'=>'操作成功', 'status'=>1];
    }


    //领导审核
    public function leaderOperation($id, $operate) {
        if(!in_array($operate, self::$operate_type)){
            return ['data'=>'', 'info'=>'请传入正确的操作方式', 'status'=>0];
        }
        $result = ['data'=>'', 'info'=>'操作失败', 'status'=>0];
        $detail = $this->detail_model->alias('a')->where(['a.trademark_base_id'=>$id])->join('tb_trademark_base b on a.trademark_base_id = b.id')->field('b.trademark_name,a.country_code,a.company_code,a.current_step,a.register_code,a.created_by')->find();
        $current_step = $detail['current_step'];
        if(in_array($current_step, [self::$step['leader_approve_step']])){
            //领导退回
            if($operate == 'back_to_waiting_commit'){
                $res = $this->approveOperate($id, $current_step,'prev');
                if($res) {
                    return ['data'=>'', 'info'=>'操作成功', 'status'=>1];
                }
            }

            //领导通过，发消息给法务
            if($operate == 'approve'){
                $res = $this->approveOperate($id, $current_step,'next');
                if($res) {
                    $content = "<div class='normal' style='size: 20px'>{$detail['created_by']} 发起了新建商标申请，请知悉</div>"
                        ."<div class='normal' >发起人: {$detail['created_by']}</div>"
                        ."<div class='normal'>申请时间: {$detail['applied_date']}</div>"
                        ."<div class='normal'>如需查看详情，请点击</div>";

                    $trademark_detail_url =  ERP_URL ."index.php?m=index&a=index&source=email&actionType=odmView&id=$id";
                    $see ="<a href='$trademark_detail_url'>【查看详情】</a>";
                    $content .=  $see;
                    //给法务发消息
                    $legal_person = $this->getLegalName();
                    $this->sendWx($legal_person, $content);
                    $result = ['data'=>'', 'info'=>'操作成功', 'status'=>1];
                }
            }

            //领导驳回
            if($operate == 'turned_down'){
               $res = $this->rejectApply($id, 'turned_down_step');
               if($res > 0){
                   $result = ['data'=>'', 'info'=>'操作成功', 'status'=>1];
               }
            }
        }else{
            $result = ['data'=>'', 'info'=>'流程节点位置有变动，请刷新页面', 'status'=>0];
        }
        return $result;
    }


    //是否通过到上/下一个节点
    public function approveOperate($id, $current_step, $operate_type){
        $step_arr = array_flip(TbTrademarkDetailModel::$step);
        while (key($step_arr)!= $current_step) next($step_arr);
        $operate_step = $operate_type($step_arr);
        $update['current_step'] = TbTrademarkDetailModel::$step[$operate_step];
        $res  =  $this->detail_model->where(['trademark_base_id'=>$id])->save($update);
        return $res > 0 ? true : false;
    }


    //驳回/取消流程
    public function rejectApply($id, $set_step){
        $update['current_step'] = TbTrademarkDetailModel::$step[$set_step];
        $res  =  $this->detail_model->where(['trademark_base_id'=>$id])->save($update);
        return $res > 0 ? true : false;
    }


    //获取法务名字
    public function getLegalName(){
        $legal_people = M('admin_role','bbm_')->alias('a')->field('c.wid')
            ->join('bbm_admin b on a.M_ID=b.M_ID')
            ->join('tb_hr_empl_wx c on b.empl_id=c.uid')
            ->where(['a.ROLE_ID' => ['in',self::LEGAL_ROLE_ID]])->select();
        return $legal_people;
    }


    //法务编辑提交的商标申请的信息
    public function legalUpdateTrademarkDetail($params){
        $result = ['data'=>'','info'=>'操作失败','status'=>0];
        $trademark_base_id = $params['trademark_base']['id'];
        $detail = $this->detail_model->where(['trademark_base_id'=>$trademark_base_id])->find();
        if(!empty($detail)){
            $trademark_base = $params['trademark_base'];
            $update_base_data = $this->makeBaseData($trademark_base);
            $base_res = $this->base_model->where(['id'=>$trademark_base_id])->save($update_base_data);

            $trademark_detail = $params['trademark_detail'];
            $update_detail_data = $this->makeDetailData($trademark_detail);
            unset($update_detail_data['current_step']);
            $update_detail_data['trademark_type'] = $trademark_base['trademark_type']; //商标code
            $update_detail_data['trademark_type_en'] = $trademark_base['trademark_type']; //商标code en
            $update_detail_data['apply_code'] = $trademark_detail['apply_code']; //申请编号
            $update_detail_data['register_code'] = $trademark_detail['register_code']; //注册编号
            $update_detail_data['initial_review_date'] = $trademark_detail['initial_review_date']; //初审公告日期
            $update_detail_data['applied_date'] = $trademark_detail['applied_date'];  //申请日期
            $update_detail_data['register_date'] = $trademark_detail['register_date']; //注册日期
            $update_detail_data['effective_start'] = $trademark_detail['effective_start']; //有效期开始
            $update_detail_data['effective_end'] = $trademark_detail['effective_end'];  //有效期结束
            $update_detail_data['current_state'] = $trademark_detail['current_state'];  //注册状态
            $update_detail_data['attachment'] = $trademark_detail['attachment'];  //附件
            $update_detail_data['agent'] = $trademark_detail['agent'];  //代理
            $update_detail_data['remark'] = $trademark_detail['remark'];  //备注
            $detail_res = $this->detail_model->where(['trademark_base_id'=>$trademark_base_id])->save($update_detail_data);
            if($detail_res > 0 ) {
                $result = ['data'=>'','info'=>'操作成功','status'=>1];
            }
        }else{
            return ['data'=>'','info'=>'商标注册详情信息不存在','status'=>0];
        }
        return  $result;
    }


    //消息发送
    public function sendWx($wx_data, $content){
        $wx_ids = implode('|', array_column($wx_data,'wid'));
        $send_res = WxAboutModel::sendWxMsg($wx_ids, $content);
        Logs(json_encode($send_res), __FUNCTION__ . '----send res', 'TrademarkSubmitSendWx');
    }


    //发消息给领导
    public function sendToLeader($trademark_detail, $trademark_base_id) {
        $hr_model = D('TbHrDept');
        $dept_id = M('hr_card','tb_')
            ->field('a.ID1')
            ->join('tb_hr_empl_dept a on a.ID2=tb_hr_card.EMPL_ID')
            ->where(['tb_hr_card.STATUS' => '在职', 'tb_hr_card.ERP_ACT' => $trademark_detail['created_by']])
            ->select();

        if(count($dept_id) > 1){
            $send_people = $trademark_detail['created_by'];
        }else{
            $send_people = $hr_model->getDeptLeader($dept_id[0]['ID1']);
        }

        $content = "<div class='normal' style='size: 20px'>{$trademark_detail['created_by']} 发起了新建商标申请，请尽快审批</div>"
            ."<div class='normal' >发起人: {$trademark_detail['created_by']}</div>"
            ."<div class='normal'>申请时间: {$trademark_detail['created_at']}</div>"
            ."<div class='normal'>如需查看详情，请点击</div>";

        $trademark_detail_url =  ERP_URL ."index.php?m=index&a=index&source=email&actionType=odmView&id=$trademark_base_id";
        $see ="<a href='$trademark_detail_url'>【查看详情】</a>";
        $content .=  $see;
        //给领导发消息
        $wx_data = M()->table('bbm_admin a')->field('b.wid')->join('left join tb_hr_empl_wx b on a.empl_id = b.uid')->where(['a.M_NAME' => ['in', [$send_people]]])->select();
        $this->sendWx($wx_data, $content);
    }


    //基础表数据处理
    public function makeBaseData($trademark_base){
        $base_data['trademark_name'] = $trademark_base['trademark_name'];  //商标名字
        $base_data['img_url']        = json_encode($trademark_base['img_url'],JSON_UNESCAPED_UNICODE); //图片url
        $base_data['trademark_type'] = $trademark_base['trademark_type']; //商标类型
        $base_data['updated_by'] = userName();
        $base_data['updated_at'] = date("Y-m-d H:i:s",time());
        return $base_data;
    }


    //详情表数据处理
    public function makeDetailData($trademark_detail) {
        $detail_data['country_code'] = $trademark_detail['country_code'];  //国家
        $detail_data['company_code'] = $trademark_detail['company_code'];  //公司
        $detail_data['international_type'] = $trademark_detail['international_type'];  //国际分类
        $detail_data['apply_currency'] = $trademark_detail['apply_currency'];  //申请币种
        $detail_data['apply_price'] = $trademark_detail['apply_price'];  //申请价格
        $detail_data['apply_period'] = $trademark_detail['apply_period'];  //申请周期
        $detail_data['goods'] = $trademark_detail['goods'];  //商品或服务

        if($trademark_detail['submit_to_next'] == '1') {
            $detail_data['current_step'] = TbTrademarkDetailModel::$step['leader_approve_step'];
        }else{
            $detail_data['current_step'] =  TbTrademarkDetailModel::$step['waiting_commit_step'];
        }
        $detail_data['updated_by'] = userName();
        $detail_data['updated_at'] = date("Y-m-d H:i:s",time());
        return $detail_data;
    }


}
