<?php
import('ORG.Util.Date');// 导入日期类
/**
 * 报价方案信息模型
 * @author Redbo He
 * @date 2020-11-03 11:25
 */
class QuotationSchemeModel extends BaseModel
{
    // 数据表名（不包含表前缀）
    protected $trueTableName = 'tb_quotation_scheme';

    protected $fields = [
        "object_name","object_id","scheme_type","audit_status",
        "creator_id", "operator_id", "created_by","updated_by",
        "created_at","updated_at",
    ];

    protected $_validate = [
//        ['object_name', 'require', '数据类型不能为空！', 1, 'regex', 3],
//        ['object_id', 'require', '数据对象ID不能为空！', 1, 'regex', 3],
        ['scheme_type', 'require', '方案类型不能为空！', 1, 'regex', 3],
        ['scheme_type', [
             self::SCHEME_TYPE_ZG, self::SCHEME_TYPE_SG, self::SCHEME_TYPE_ZG_SG,
             self::SCHEME_TYPE_HZG, self::SCHEME_TYPE_BHZG,
            ],'报价类型值的范围不正确',Model::MUST_VALIDATE,'in'], // 当值不为空的时候判断是否在一个范围内


    ];

    # 方案类型 1：整柜，2：散柜，3：整柜+散柜，4：含整柜，5：不含整柜
    const SCHEME_TYPE_ZG    = 1;
    const SCHEME_TYPE_SG    = 2;
    const SCHEME_TYPE_ZG_SG = 3;
    const SCHEME_TYPE_HZG   = 4;
    const SCHEME_TYPE_BHZG  = 5;


    public static $scheme_type_str_map = [
        self::SCHEME_TYPE_ZG => '整柜',
        self::SCHEME_TYPE_SG => '散柜',
        self::SCHEME_TYPE_ZG_SG => '整柜+散柜',
        self::SCHEME_TYPE_HZG => '含整柜',
        self::SCHEME_TYPE_BHZG => '不含整柜',
    ];

    public static $show_scheme_name_types = [
        self::SCHEME_TYPE_ZG_SG, self::SCHEME_TYPE_HZG
    ];
    # 对象类型，quote_lcl：拼柜，quotation：报价单',
    const OBJECT_NAME_QUOTE_LCL = 'quote_lcl';
    const OBJECT_NAME_QUOTATION = 'quotation';

    # '审核状态 0：未审核，1：审核失败，2:审核通过',
    const AUDIT_STATUS_UNCHECKED = 0; # 未审核
    const AUDIT_STATUS_FAIL      = 1; # 审核失败
    const AUDIT_STATUS_SUCCESS   = 2; # 审核通过


    /**
     * 添加前置函數
     * @param array $data
     * @param $option
     * @author Redbo He
     * @date 2020/11/5  14:35
     */
    protected function _before_insert(&$data, $option)
    {
        $date = new Date();
        # 报价单状态
        $params =  json_decode(file_get_contents('php://input'),1);
        $data['object_name'] = $params['object_name'];
        $data['object_id']   = $params['object_id'];

        $data['audit_status']= self::AUDIT_STATUS_UNCHECKED; # 初始未审核

        $data['creator_id'] = session('user_id');
        $data['created_by'] = session('m_loginname');
        $data['created_at'] = $date->format();
        $data['updated_at'] = $date->format();
    }

    /**
     * 插入后置函数 将方案数据保存入库 
     * @param  array $data
     * @param   $option
     * @return mixed
     */
    protected function _after_insert($data,$option)
    {
        ### 保存方案详情数据
        $scheme_detail = $data['scheme_detail'];
        $scheme_detail_data = [];
        $quotation_scheme_detail_model = D("Quote/QuotationSchemeDetail");
        foreach ($scheme_detail as $item)
        {
            $item['quotation_scheme_id'] = $data['id'];
            $scheme_detail_data[] = $item;
        }
        $res = $quotation_scheme_detail_model->addAll($scheme_detail_data);
        if(!$res) {
            throw_exception("方案数据插入失败");
        }
    }

    /**
     * 修改前置操作
     * @param $data
     * @param $option
     * @author Redbo He
     * @date 2020/11/5  19:37
     */
    protected function _before_update(&$data, $option)
    {
        $date = new Date();
        $data['operator_id'] = session('user_id');
        $data['updated_by'] = session('m_loginname');
        $data['updated_at'] = $date->format();
        unset($data['scheme_name']);
    }


    protected function _after_update($data, $options)
    {
        $scheme_detail = $data['scheme_detail'];
        $scheme_detail_data = [];
        $quotation_scheme_detail_model = D("Quote/QuotationSchemeDetail");
        foreach ($scheme_detail as $item)
        {
            $item['quotation_scheme_id'] = $data['id'];
            if(isset($item['id']))
            {
                $quotation_scheme_detail_model->data($item)->save();
            }
            else
            {
                $quotation_scheme_detail_model->_before_insert($item);
                $scheme_detail_data[] = $item;
            }
        }

        if($scheme_detail_data)
        {
            $res = $quotation_scheme_detail_model->addAll($scheme_detail_data);
            if(!$res) {
                throw_exception("方案数据插入失败");
            }
        }

    }


}