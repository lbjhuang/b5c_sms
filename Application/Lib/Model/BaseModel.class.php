<?php

class BaseModel extends RelationModel
{
    CONST ALLO_STATE_ING = 1; // 流程进行中
    const ALLO_STATE_OFF = 2; // 流程关闭
    public static $conCompanyCd;
    public static $conCompanyCdNew;
    private $_requestData = null;
    private $_responseData = null;
    private $_requestUrl = null;

    public function create($data='',$type='') {
        $data2 = parent::create($data,$type);
        $data3 = array_merge($data,$data2);
        $data = $data3;
        // 验证完成生成数据对象
        if($this->autoCheckFields) { // 开启字段检测 则过滤非法字段数据
            $fields =   $this->getDbFields();
            foreach ($data as $key=>$val){
                if(!in_array($key,$fields)) {
                    unset($data[$key]);
                }elseif(MAGIC_QUOTES_GPC && is_string($val)){
                    $data[$key] =   stripslashes($val);
                }
            }
        }
        return $data;
    }

    // 我方公司
    public static function conCompanyCd()
    {
        if (static::$conCompanyCd) return static::$conCompanyCd;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N00124%'];
        $ret = $model->where($conditions)->select();
        return static::$conCompanyCd = array_column($ret, 'CD_VAL', 'ETC');
    }

    // 我方公司
    public static function conCompanyCdNew()
    {
        if (static::$conCompanyCdNew) return static::$conCompanyCdNew;
        $condtion = array(
            'CD'=>array('like','N00124%'),
            'USE_YN'=> "Y",
            'ETC5'=>array(array('NEQ','1'),array("exp" ,"IS NULL"),'or'),
        );
        $field = "CD,CD_VAL,ETC,ETC2";
        $ret = CodeModel::getCodeAll($condtion,$field);
        return static::$conCompanyCdNew = array_column($ret, 'CD_VAL', 'ETC');
    }

    public static $outCompany;
    /**
     * 我方公司，根据 cd 值取值，不根据 ETC 取值
     *
     */
    public static function ourCompany()
    {
        if (static::$outCompany) return static::$outCompany;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N00124%'];
        $ret = $model->where($conditions)->select();
        return static::$outCompany = array_column($ret, 'CD_VAL', 'CD');
    }

    // 企业类型
    public static function companyTypeCd()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->where('CD like "N00119%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return $ret;
    }

    public static $spYearScaleCds;
    // 供应商年业务规模
    public static function spYearScaleCd()
    {
        if (static::$spYearScaleCds) return static::$spYearScaleCds;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->where('CD like "N00120%"')->select();
        static::$spYearScaleCds = array_column($ret, 'CD_VAL', 'CD');
        return static::$spYearScaleCds;
    }

    public static $spTeamCds;
    // 采购团队
    public static function spTeamCd()
    {
        if (static::$spTeamCds) return static::$spTeamCds;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->where('CD like "N00129%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        static::$spTeamCds = $ret;
        return static::$spTeamCds;
    }

    // 采购团队
    public static function spTeamCdExtend()
    {
        if (static::$spTeamCds) return static::$spTeamCds;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->where('CD like "N00129%" and USE_YN = "Y"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        static::$spTeamCds = $ret;
        return static::$spTeamCds;
    }

    public static $saleTeamCd;
    // 销售
    public static function saleTeamCd()
    {
        if (static::$saleTeamCd) return static::$saleTeamCd;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->where('CD like "N00128%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        static::$saleTeamCd = $ret;
        return static::$saleTeamCd;
    }
    public static $smallTeamCd;
    public static function samllTeamCd()
    {
        if (static::$smallTeamCd) return static::$smallTeamCd;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->where('CD like "N00323%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        static::$smallTeamCd = $ret;
        return static::$smallTeamCd;
    }
    // 销售
    public static function saleTeamCdCur()
    {
        if (static::$saleTeamCd) return static::$saleTeamCd;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->where('CD like "N00128%" and USE_YN="Y"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        static::$saleTeamCd = $ret;
        return static::$saleTeamCd;
    }


    public static $teamCdExtend;
    // 销售
    public static function saleTeamCdExtend()
    {
        if (static::$teamCdExtend) return static::$teamCdExtend;
        $model = M('_ms_cmn_cd', 'tb_');
        static::$teamCdExtend = $model->where('CD like "N00128%" and USE_YN = "Y"')->getField('CD, CD_VAL, ETC');

        return static::$teamCdExtend;
    }

    public static $person;
    // 负责人
    public static function personLiable()
    {
        if (static::$person) return static::$person;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->where('CD like "N000910%"')->getField('CD, CD_VAL, ETC');
        return static::$person = $ret;
    }

    public static $spJsTeamCds;
    // 介绍团队
    public static function spJsTeamCd()
    {
        if (static::$spJsTeamCds) return static::$spJsTeamCds;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->where('CD like "N00130%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        static::$spJsTeamCds = $ret;
        return static::$spJsTeamCds;
    }

    public static function spJsTeamCdExtend()
    {
        if (static::$spJsTeamCds) return static::$spJsTeamCds;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->where('CD like "N00130%" and USE_YN = "Y"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        static::$spJsTeamCds = $ret;
        return static::$spJsTeamCds;
    }

    public static $conType;
    // 合作类型
    public static function conType()
    {
        if (static::$conType) return static::$conType;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N00123%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->where($conditions)->select();
        return static::$conType = array_column($ret, 'CD_VAL', 'ETC');
    }

    public static $warehouse;
    public static function get_all_warehouse()
    {
        if (static::$warehouse) return static::$warehouse;
        $Warehouse = M('warehouse', 'tb_wms_');
        return static::$warehouse = $Warehouse->getField('CD,company_id,warehouse');
    }

    // 是否自动续约
    public static function isAutoRenew()
    {
        return [
            '0' => '自动续约',
            '1' => '不自动续约'
        ];
    }

    // 合同日期类型（年度合同 0、长期合同1）
    public static function conDateType()
    {
        return [
            '0' => '年度合同',
            '1' => '长期合同',
            '2' => '一次性合同',
            '3' => '保密合同',
            '4' => '平台合同',
            '5' => '外部融资合同',
            '6' => '一件代发合同',
            '7' => '仓储合同',
            '8' => '运输合同',
            '9' => '货运代理合同',
            '10' => '推广合同',
            '11' => '加工承揽合同',
            '12' => '保证合同',
            '13' => '借款合同',
            '14' => '供用电、水、气、热力合同',
            '15' => '建设工程合同',
            '16' => '技术合同',
            '17' => '委托代理合同',
            '18' => '行纪合同',
            '19' => '中介合同',
        ];
    }

    

    // 条件筛选，国家
    public static $country;
    public static function getCountries()
    {
        if (static::$country) return static::$country;
        $model = M('_crm_site', 'tb_');
        $ret = $model->field('ID, CONCAT(RES_NAME, NAME) AS NAME')->where('PARENT_ID = 0')->order('sort asc')->select();

        if ($ret) {
            $ret = array_column($ret, 'NAME', 'ID');
            static::$country = $ret;
            return static::$country;
        }
    }

    /**
     * @param $area_no
     * @return mixed
     */
    public static function join_country($area_no){
        $model = M('user_area', 'tb_ms_');
        $field = ['area_no','zh_name','en_name','two_char','three_char'];
        return $model->field($field)->where('area_type = 1 AND area_no = '.$area_no)->order('id asc')->find();

    }

    /**
     * get user area code
     * @param null $code
     * @return mixed
     */
    public static function getAreaCode($code = null)
    {
        $model = M('user_area', 'tb_ms_');
        $field = ['id','zh_name AS NAME', 'area_type','area_no'];
        if($code){
            $field[] = 'zh_name AS CN_NAME';
        }
        return $model->field($field)->where('area_type = 1 AND parent_no = 0 AND rank IS NOT NULL ')->order('rank asc')->select();
    }

    /**
     * get user area code by query
     * @param array $where
     * @return mixed
     */
    public static function getAreaByWhere($where = [])
    {
        $model = M('user_area', 'tb_ms_');
        $field = ['id','area_no','zh_name','en_name','two_char','three_char'];
        $query = $model->field($field);
        $condition['area_type'] = '1';
        $condition['parent_no'] = '0';
        $condition['rank'] = ['exp', 'is not null'];
        if($where){
            $string = '';
            if (!empty($where['en_name'])) {
                //多个用英文逗号分隔
                if (strpos($where['en_name'], ',') !== false) {
                    $condition['en_name'] = ['in', explode(',', $where['en_name'])];
                } else {
                    $condition['en_name'] = ['like', '%' . $where['en_name'] . '%'];
                }
            }
            if (!empty($where['zh_name'])) {
                //多个用英文逗号分隔
                if (strpos($where['zh_name'], ',') !== false) {
                    $condition['zh_name'] = ['in', explode(',', $where['zh_name'])];
                } else {
                    $condition['zh_name'] = ['like', '%' . $where['zh_name'] . '%'];
                }
            }
            if (!empty($where['two_char'])) {
                //多个用英文逗号分隔
                if (strpos($where['two_char'], ',') !== false) {
                    $condition['two_char'] = ['in', explode(',', $where['two_char'])];
                } else {
                    $condition['two_char'] = ['like', '%' . $where['two_char'] . '%'];
                }
            }
            if (!empty($string)) {
                $string = rtrim($string, ' AND ');
                $condition['_string'] = $string;
            }
        }
        $query->where($condition);
        return $query->order('rank asc')->select();
    }

    /**
     * 获得国家Code，映射国家编码
     * @return mixed
     */
    public static function getCountryCode($code = null)
    {
        $model = M('_crm_site', 'tb_');
        $field = ['ID', 'CONCAT(RES_NAME, NAME) AS NAME','RES_NAME', 'NAME AS ZH_NAME', 'PARENT_ID'];
        if($code){
            $field[] = 'NAME AS CN_NAME';
        }
        return $model->field($field)->where('PARENT_ID = 0')->order('sort asc')->select();
    }

    // 获得国家
    public static function getCountry()
    {
        $model = M('_crm_site', 'tb_');
        return $model->field('ID, CONCAT(RES_NAME, NAME) AS NAME, PARENT_ID')->where('PARENT_ID = 0')->order('sort asc')->select();
    }

    // 获得省
    public static function getProvince($parent_id)
    {
        $model = M('_crm_site', 'tb_');
        return $model->field('NAME, PARENT_ID')->where('PARENT_ID = 1')->select();
    }

    // 获得市
    public static function getCity($parent_id)
    {
        $model = M('_crm_site', 'tb_');
        $ret = $model->field('NAME, ID')->where('PARENT_IDS = "' . $parent_id . '"')->select();
        return $ret;
    }

    // 获得县
    public static function getCounty($parent_id)
    {
        $model = M('_crm_site', 'tb_');
        return $model->field('NAME, ID')->where('PARENT_IDS = "' . $parent_id . '"')->select();
    }

    // 区域
    public static function getArea($parent_id)
    {
        $model = M('_crm_site', 'tb_');
        return $model->field('NAME, ID')->where('PARENT_ID = %d', [$parent_id])->select();
    }

    public static $localName;
    /**
     * 根据ID返回城市的名字
     *
     */
    public static function getLocalName()
    {
        if (static::$localName) return static::$localName;
        $model = M('_crm_site', 'tb_');
        $result = $model->field('ID, NAME')->select();
        return static::$localName = array_column($result, 'NAME', 'ID');
    }

    public function getName()
    {
        return $_SESSION['userId'];
    }

    public function getLoginName()
    {
        return $_SESSION['m_loginname'];
    }

    public function getTime()
    {
        return date('Y-m-d H:i:s');
    }

    public function upload() {
        import('ORG.Net.UploadFile');
        $upload = new UploadFile();// 实例化上传类
        $upload->maxSize  = 3145728 ;// 设置附件上传大小
        $upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg', 'apk');// 设置附件上传类型
        $upload->savePath =  './Public/Uploads/';// 设置附件上传目录
        if(!$upload->upload()) {// 上传错误提示错误信息
            $this->error($upload->getErrorMsg());
        }else{// 上传成功
            $this->success('上传成功！');
        }
    }
    // 获取所有的用户信息
    public static $allUserInfo;

    public static function getAdmin()
    {
        if (static::$allUserInfo) return static::$allUserInfo;

        $model = M('_admin', 'bbm_');
        $ret = $model->field('M_ID, M_NAME')->select();

        return static::$allUserInfo = array_column($ret, 'M_NAME', 'M_ID');
    }

    // 获取所有的国别信息
    public static $allCountryInfo;

    public static function getCountryInfo()
    {
        if (static::$allCountryInfo) return static::$allCountryInfo;

        $model = M('_crm_site', 'tb_');
        $ret = $model->field('ID, NAME')->where('PARENT_ID = 0')->select();

        return static::$allCountryInfo = array_column($ret, 'NAME', 'ID');
    }

    // 商品类目
    public static $cmnCat;

    public static function getCmnCat()
    {
        if (static::$cmnCat) return static::$cmnCat;

        $model = M('ms_cmn_cat', 'tb_');
        $ret = $model->field('CAT_CNS_NM, CAT_CD')->where('CAT_LEVEL = 1')->select();
        $s = array_column($ret, 'CAT_CNS_NM', 'CAT_CD');
        $other = static ::addOtherType();

        return static::$cmnCat = array_merge($s, $other);
    }

    /**
     * 新增其他类别到商品类目中去
     *
     */
    public static function addOtherType()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD = "N001510100"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');

        return $ret;
    }

    public static $currency;

    // 币种
    public static function getCurrency()
    {
        if (static::$currency) return static::$currency;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00059%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return static::$currency = $ret;
    }

    public static $currenyFlip;
    // 币种
    public static function getCurrencyFlip()
    {
        if (static::$currenyFlip) return static::$currenyFlip;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00059%"')->select();
        $ret = array_column($ret, 'CD', 'CD_VAL');
        return static::$currenyFlip = $ret;
    }

    public static $purInvoiceTaxRate;

    /**
     * 采购发票税率
     * @return array
     */
    public static function purInvoiceTaxRate()
    {
        if (static::$purInvoiceTaxRate) return static::$purInvoiceTaxRate;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00134%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return static::$purInvoiceTaxRate = $ret;
    }

    // 币种
    public static function getCurrencyExtend()
    {
        if (static::$currency) return static::$currency;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00059%" and USE_YN = "Y"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return static::$currency = $ret;
    }

    public static $chinaMainlandAndHMT;

    /**
     * 供应商地区分类
     * 1、为中国大陆
     * 2、港澳台
     * 3、海外区域
     */
    public static function regionalClassification()
    {
        if (static::$chinaMainlandAndHMT) return static::$chinaMainlandAndHMT;
        $model = M('_crm_site', 'tb_');
        // 取得中国大陆与港澳台数据
        $ret = $model->field('ID')->where('PARENT_ID = ' . 1)->select();
        $ret = array_column($ret, 'ID');
        // 港澳台、HongKong、Macao、Taiwan
        $hmt = $model->field('ID')->where("NAME IN ('香港', '澳门', '台湾')")->select();
        $hmt = array_column($hmt, 'ID');
        // 求差集，获取到中国大陆数据，并缓存起来
        $ret = array_diff($ret, $hmt);
        return static::$chinaMainlandAndHMT = [
            2 => $hmt,
            1 => $ret,
        ];
    }

    /**
     * 付款方式
     *
     */
    public static function paymentMode()
    {
        return [
            '1' => '1期付清',
            '2' => '2期付清',
            '3' => '3期付清',
            '0' => '未约定',
        ];
    }

    /**
     * 期数
     *
     */
    public static function periods()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N001390%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return $ret;
    }

    public static $channels;

    /**
     * 渠道
     *
     */
    public static function getChannels()
    {
        if (static::$channels) return static::$channels;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00083%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return static::$channels = $ret;
    }

    /**
     * 天数
     *
     */
    public static function day()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00142%"')->order('SORT_NO asc')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return $ret;
    }

    /**
     * 付款百分比
     *
     */
    public static function percentage()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00141%"')->order('SORT_NO asc')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return $ret;
    }

    /**
     * 审核状态
     *
     */
    public static function auditState()
    {
        return [
            1 => '未审核',
            2 => '已审核',
            3 => '无需审核', // 该选项目前用于供应商列表搜索，需要
        ];
    }

    /**
     * 风险评级
     *
     */
    public static function riskRating()
    {
        return [
            1 => '低风险',
            2 => '中等风险',
            3 => '重大风险'
        ];
    }

    /**
     * 检查是否需要审核
     * update 2017/6/26 所有供应商客户均需要发送法务邮件审核 by benyin
     */
    public function checkNeedAudit($addr)
    {
        return true;
        //foreach (static::regionalClassification() as $key => $value) {
//            if (array_search($addr, $value) !== false and $key == 1) {
//                return true;
//            }
//        }
//
//        return false;
    }

    /**
     * 审核评级标准
     *
     */
    public static function auditGradeStandard()
    {
        return [
            4 => '中国大陆公司:<br />',
            3 => '高：  资金履行或货物供应能力有较严重问题。  <br />
                    如以下情况之一：（1）认缴资本100万以内（含），连续2次工商异常；（2）主要股东或企业被列为过失信人；（3）3年内2次应货款或货物问题被列为被告；（4）严重的行政处罚、产品曝光，包括对其声誉有较大影响。<br />
                    <br />',
            2 => '中： 资金履行或货物供应能力可能发生不太严重的问题。<br /> 
                    如以下情况都满足：（1）认缴资本在100万以上，工商异常很少；（2）虽有过民事案件纠纷被告，但不严重也没有列为失信人； （3）很少发生作为被告的案件； （4）虽有处罚，但不严重，产品也没有曝光有什么声誉影响。<br />
                    <br />',
            1 => '低： 查无行政处罚、最近2年无司法案件，且资本额在500万以上。<br /><br />',
            0=> '大陆以外公司：以专业报告中的信用评分和信用评级为主要评级依据。',
        ];
    }

    /**
     * 审核评级标准纯文本
     *
     */
    public static function auditGradeStandardText()
    {
        return [
            3 => '高：资金履行或货物供应能力有较严重问题。如以下情况之一：（1）认缴资本100万以内（含），连续2次工商异常；（2）主要股东或企业被列为过失信人；（3）3年内2次应货款或货物问题被列为被告；（4）严重的行政处罚、产品曝光，包括对其声誉有较大影响。',
            2 => '中： 资金履行或货物供应能力可能发生不太严重的问题。如以下情况都满足：（1）认缴资本在100万以上，工商异常很少；（2）虽有过民事案件纠纷被告，但不严重也没有列为失信人； （3）很少发生作为被告的案件； （4）虽有处罚，但不严重，产品也没有曝光有什么声誉影响。',
            1 => '低： 查无行政处罚、最近2年无司法案件，且资本额在500万以上。'
        ];
    }

    /**
     * 邮件发送、供应商
     *
     */
    public function supplierSendMail($data)
    {
        $address = C('supplier_customer_forensic_email_address');
        $web_site = C('redirect_audit_addr');
        if (!$this->checkNeedAudit($data['SP_ADDR3'])) return;
        $data['web_site'] = $web_site;
        $template = new SendMailMessageTemplateModel();

        $sps = explode(',', $data['SP_TEAM_CD']);
        $str = '';
        if (count($sps) > 1) {
            foreach ($sps as $key => $value) {
                $str .= static::spTeamCd()[$value] . ',';
            }
            $str = rtrim($str, ',');
        } else {
            $str = static::spTeamCd()[$data['SP_TEAM_CD']];
        }
        $content = sprintf($template->firstTrial(), $data['SP_NAME'], static::getLocalName()[$data['SP_ADDR1']], static::getLocalName()[$data['SP_ADDR3']], static::getLocalName()[$data['SP_ADDR4']], static::getAdmin()[$data['CREATE_USER_ID']], $str, $data['CREATE_TIME'], $data['web_site']);
        $mail = new ExtendSMSEmail();
        $mail->sendEmail($address, sprintf($template->supplierTitle(), $data ['SP_NAME']), $content);
    }

    /**
     * 邮件发送、供应商年度审核
     * 需添加上次审核人
     *
     */
    public function supplierYearSendMail($data, $audit)
    {
        $address = C('supplier_customer_forensic_email_address');
        $web_site = C('redirect_audit_addr');
        if (!$this->checkNeedAudit($data['SP_ADDR3'])) return;
        $data['web_site'] = $web_site;
        $template = new SendMailMessageTemplateModel();
        $sps = explode(',', $data['SALE_TEAM']);
        $str = '';
        if (count($sps) > 1) {
            foreach ($sps as $key => $value) {
                $str .= static::spTeamCd()[$value] . ',';
            }
            $str = rtrim($str, ',');
        } else {
            $str = static::spTeamCd()[$data['SP_TEAM_CD']];
        }
        $content = sprintf($template->supplierYearExamine(), $data['SP_NAME'], static::getLocalName()[$data['SP_ADDR1']], static::getLocalName()[$data['SP_ADDR3']], static::getLocalName()[$data['SP_ADDR4']], static::getAdmin()[$data['CREATE_USER_ID']], $str, $data['CREATE_TIME'], static::getAdmin()[$audit['REVIEWER']], $audit['REV_TIME'] ,$data['web_site']);
        $mail = new ExtendSMSEmail();
        if (C('is_start_cc')) $mail->cAddr = static::getAdmin()[$v['REVIEWER']].'@gshopper.com';
        $mail->sendEmail($address, sprintf($template->supplierYearTitle(), $data ['SP_NAME']), $content);
    }

    /**
     * 邮件发送、客户管理
     *
     */
    public function customerSendMail($data)
    {
        $address = C('supplier_customer_forensic_email_address');
        $web_site = C('redirect_audit_addr');
        if (!$this->checkNeedAudit($data['SP_ADDR3'])) return;
        $data['web_site'] = $web_site;
        $template = new SendMailMessageTemplateModel();

        $sps = explode(',', $data['SALE_TEAM']);
        $str = '';
        if (count($sps) > 1) {
            foreach ($sps as $key => $value) {
                $str .= static::saleTeamCd()[$value] . ',';
            }
            $str = rtrim($str, ',');
        } else {
            $str = static::saleTeamCd()[$data['SALE_TEAM']];
        }
        $content = sprintf($template->customerFirstTrial(), $data['SP_NAME'], static::getLocalName()[$data['SP_ADDR1']], static::getLocalName()[$data['SP_ADDR3']], static::getLocalName()[$data['SP_ADDR4']], static::getAdmin()[$data['CREATE_USER_ID']], $str, $data['CREATE_TIME'], $data['web_site']);
        $mail = new ExtendSMSEmail();
        $mail->sendEmail($address, sprintf($template->customerTitle(), $data ['SP_NAME']), $content);
    }

    /**
     * 邮件发送、客户年度审核
     *
     */
    public function customerYearSendMail($data, $audit)
    {
        $address = C('supplier_customer_forensic_email_address');
        $web_site = C('redirect_audit_addr');
        if (!$this->checkNeedAudit($data['SP_ADDR3'])) return;
        $data['web_site'] = $web_site;
        $template = new SendMailMessageTemplateModel();

        $sps = explode(',', $data['SALE_TEAM']);
        $str = '';
        if (count($sps) > 1) {
            foreach ($sps as $key => $value) {
                $str .= static::saleTeamCd()[$value] . ',';
            }
            $str = rtrim($str, ',');
        } else {
            $str = static::saleTeamCd()[$data['SALE_TEAM']];
        }
        $content = sprintf($template->customerYearExamine(), $data['SP_NAME'], static::getLocalName()[$data['SP_ADDR1']], static::getLocalName()[$data['SP_ADDR3']], static::getLocalName()[$data['SP_ADDR4']], static::getAdmin()[$data['CREATE_USER_ID']], $str, $data['CREATE_TIME'], static::getAdmin()[$audit['REVIEWER']], $audit['REV_TIME'],$data['web_site']);
        $mail = new ExtendSMSEmail();
        if (C('is_start_cc')) $mail->cAddr = static::getAdmin()[$v['REVIEWER']].'@gshopper.com';
        $mail->sendEmail($address, sprintf($template->customerYearTitle(), $data ['SP_NAME']), $content);
    }

    public static $allPlat;
    /**
     * 获取所有的平台
     *
     */
    public static function getPlat()
    {
        if (static::$allPlat) {
            return static::$allPlat;
        }
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00083%"')->select();   //获取平台数据WORK_NUM
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return static::$allPlat = $ret;
    }

    public static $creditGrade;

    /**
     * 信用评级
     *
     */
    public static function getCreditGrade()
    {
        if (static::$creditGrade) return static::$creditGrade;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00137%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return static::$creditGrade = $ret;
    }

    public static $nagetiveOptions;

    /**
     * 负面信息项
     *
     */
    public static function getNagetiveOptions()
    {
        if (static::$nagetiveOptions) return static::$nagetiveOptions;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00138%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return static::$nagetiveOptions = $ret;
    }

    public static $btbtcCustomerSotre;
    /**
     * B2B2C客户管理，店铺获取
     * 根据配置文件的Available状态判定是否屏蔽某个店铺
     *
     */
    public static function getBtbtcCustomerStore()
    {
        if (static::$btbtcCustomerSotre) return static::$btbtcCustomerSotre;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL, USE_YN')->where('CD like "N00083%"')->select();
        $temp [-1] = 'ALL';
        foreach ($ret as $key => $value) {
            if ($value ['CD'] == 'N000831400') continue;
            if ($value ['USE_YN'] == 'N') continue;
            $temp [$value ['CD']] = $value ['CD_VAL'];
        }
        return static::$btbtcCustomerSotre = $temp;
    }



    /**
     * 是否有负面信息
     *
     */
    public static function isHaveNagetive()
    {
        return [
            1 => '有',
            0 => '无',
            2 => '未知'
        ];
    }

    /**
     * 自然日工作日
     *
     */
    public static function workday()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N001400%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return $ret;
    }

    /**
     * 日志写入
     *
     */
    public function insertLog($ORD_ID = '',$ORD_STAT_CD = '',$content = '')
    {
        $log = A('Home/Log');
        if ($log->index($ORD_ID, $ORD_STAT_CD, $content)) {
            return true;
        }
        return $log->errorMsg;
    }

    /**
     * 合同状态
     *
     */
    public static function contractState()
    {
        return [
            1 => '有效合同',
            2 => '合同已到期',
            3 => '合同作废',
            4 => '保密合同',
            5 => '平台合同',
            6 => '外部融资合同',
            7 => '一件代发合同',
            8 => '仓储合同',
            9 => '运输合同',
            10 => '货运代理合同',
            11 => '推广合同',
            12 => '加工承揽合同',
            13 => '保证合同',
            14 => '借款合同',
            15 => '供用电、水、气、热力合同',
            16 => '建设工程合同',
            17 => '技术合同',
            18 => '委托代理合同',
            19 => '行纪合同',
            20 => '中介合同',
        ];
    }


    /**
     * 是否含税
     *
     */
    public static function hasTax()
    {
        return [
            0 => '不含税',
            1 => '含税',
        ];
    }

    /**
     * 合同类型
     *
     */
    public static function contractType()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00180%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return $ret;
    }

    /**
     * 发票类型
     *
     */
    public static function invoiceType()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00135%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return $ret;
    }

    /**
     * 管理员、联系人
     *
     */
    public static function warehouseContacts()
    {
        $model = M('_admin', 'bbm_');
        $ret = $model->field('M_ID, M_NAME')->select();
        $ret = array_column($ret, 'M_NAME', 'M_ID');

        return [
            0 => '张三',
            1 => '李四',
            2 => '王五'
        ];
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00142%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');

        return $ret;
    }

    /**
     * 天数
     *
     */
    public static function getPayDays()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00142%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return $ret;
    }

    /**
     * 根据传递的cd开头值获取数据字典表配置的参数值
     * @param $cd_prefix
     * @return Array
     */
    public static function getCd($cd_prefix)
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "'. $cd_prefix .'%" and USE_YN = "Y"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return $ret;
    }

    public function setRequestData($requestData)
    {
        $this->_requestData = $requestData;
    }

    public function setResponseData($responseData)
    {
        $this->_responseData = $responseData;
    }

    public function setRequestUrl($url)
    {
        $this->_requestUrl = $url;
    }

    public function getRequestData()
    {
        return $this->_requestData;
    }

    public function getResponseData()
    {
        return $this->_responseData;
    }

    public function getRequestUrl()
    {
        return $this->_requestUrl;
    }

    public static $gudsOpt;
    public static function getGudsOpt()
    {
        if (static::$gudsOpt) return static::$gudsOpt;
        $model = M('ms_opt', 'tb_');
        $ret = $model->cache(true, 300)
            ->join('left join tb_ms_opt_val on tb_ms_opt_val.OPT_ID = tb_ms_opt.OPT_ID')
            ->field('tb_ms_opt.OPT_ID, tb_ms_opt_val.OPT_VAL_ID,tb_ms_opt.OPT_CNS_NM,tb_ms_opt_val.OPT_VAL_CNS_NM,tb_ms_opt.OPT_ID')
            ->select();
        foreach ($ret as $key => $value) {
            $tmp [$value['OPT_ID'] . ':' . $value ['OPT_VAL_ID']] = $value;
        }
        $tmp [''] = [
            'OPT_CNS_NM' => '',
            'OPT_VAL_CNS_NM' => '标配'
        ];
        $tmp ['8000:800000'] = [
            'OPT_CNS_NM' => '',
            'OPT_VAL_CNS_NM' => '标配'
        ];
        static::$gudsOpt = $tmp;
        return $tmp;
    }

    public static function getRuleStorage()
    {
        return [
            0 => '虚拟入库(直接发给客户)',
            1 => '实际入库',
            2 => '默认规则(先进先出/效期敏感商品将以效期优先)',
            3 => '指定采购批次出库',
            5 => '虚拟出库'
        ];
    }

    /**
     * 管理员、联系人
     *
     */
    public static function warehouseContactsExtends()
    {
        $model = M('_admin', 'bbm_');
        $ret = $model->field('M_ID, M_NAME')->select();
        $ret = array_column($ret, 'M_NAME', 'M_ID');
        $data = [];
        foreach ($ret as $k => $v) {
            $tmp = null;
            $tmp ['label'] = $v;
            $tmp ['value'] = $k;
            $data [] = $tmp;
        }
        return $data;
    }

    /**
     * 产品与模块的对应值
     *
     */
    public static function getPMName()
    {
        return [
            'stock' => [
                'name' => '飞松',
                'email' => 'feisong@gshopper.com'
            ],
            0 => [
                'name' => '华黎',
                'email' => 'huali@gshopper.com'
            ]
        ];
    }

    /*
     * 店铺信息
     * @param $plat_code 平台code，参数plat_code有值，则根据plat_code获取到平台下所有的店铺
     */
    public static function getStoreInfo($plat_code = false)
    {
        $model = M('ms_store', 'tb_');
        if (!$plat_code) {
            $ret = $model->field('DISTINCT PLAT_CD, PLAT_NAME')->where('')->select();
            $ret = array_column($ret, 'PLAT_NAME', 'PLAT_CD');
            return $ret;
        } else {
            $ret = $model->field('STORE_NAME, ID')->where('PLAT_CD = "' . $plat_code . '"')->select();
            $ret = array_column($ret, 'STORE_NAME', 'ID');
            return $ret;
        }
    }

    public static $sotres;
    /**
     * 获取所有的店铺
     *
     */
    public static function getStores()
    {
        if (static::$sotres) return static::$stores;
        $model = M('ms_store', 'tb_');
        $ret = $model->where('')->getField('ID, PLAT_CD, PLAT_NAME');

        return static::$sotres = $ret;
    }

    public static $storesName;
    /**
     * @var
     */
    public static function getStoreName()
    {
        if (static::$storesName) return static::$storesName;
        $model = M('ms_store', 'tb_');
        $ret = $model->field('ID, STORE_NAME')->where('')->select();

        return static::$storesName = $ret;
    }

    public static $unit;
    /**
     * 计量单位
     *
     */
    public static function getUnit()
    {
        if (static::$unit) return static::$unit;
        $Cmn_cd = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = 'VALUATION_UNIT';
        return static::$unit = $Cmn_cd->where($where)->getField('CD,CD_VAL,ETc');
    }

    public static $languages;
    /**
     * 语种设置
     *
     */
    public static function languages()
    {
        if (static::$languages) return static::$languages;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->where('CD like "N000920%" and USE_YN = "Y"')->getField('ETC2, ETC, CD, CD_VAL');
        return static::$languages = $ret;
    }

    /**
     * @return array
     * 到期日
     */
    public static function expireTime()
    {
        return [
            1 => L('最近6个月'),
            2 => L('最近3个月'),
            3 => L('最近1个月'),
            5 => L('无到期日')
        ];
    }

    /**
     * 是否滞销
     *
     */
    public static function unsalable()
    {
        return [
            1 => L('是'),
            2 => L('否')
        ];
    }

    /**
     * 合同文件类型
     *
     */
    public static function contractAgreement()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N001810%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return $ret;
    }

    /**
     * 合同时间类型
     *
     */
    public static function contractTimeType()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N001800%" and USE_YN = "Y"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return $ret;
    }

    /**
     * 仓库
     * @var
     */
    public static $warehouseList;

    /**
     * 取得所有的仓库，不包含已禁用的仓库
     * @param bool $originalFieldName
     */
    public static function warehouseList($originalFieldName = false, $useyn = false)
    {
        if (static::$warehouseList) return static::$warehouseList;
        if ($originalFieldName)
            $field = 'CD, CD_VAL, CD, USE_YN';
        else
            $field = 'CD as cd, CD_VAL as cdVal, CD as cd, USE_YN as useYn';
        $model = new Model();
        $where = ['CD' => ['like', 'N00068%']];
        $useyn and $where['USE_YN'] = 'Y';
        static::$warehouseList = $model->table('tb_ms_cmn_cd')
            ->where($where)
            ->getField($field);

        return static::$warehouseList;
    }

    /**
     * 销售团队
     */
    public static $saleTeamList;

    /**
     * 销售团队
     * @param bool $originalFieldName 是否使用原始名称
     * @param bool $isUse 是否启用
     */
    public static function saleTeamList($originalFieldName = false, $isUse = false)
    {
        if (static::$saleTeamList) return static::$saleTeamList;
        if ($originalFieldName)
            $field = 'CD, CD_VAL, CD, USE_YN';
        else
            $field = 'CD as cd, CD_VAL as cdVal, CD as cd, USE_YN as useYn';

        $conditions ['CD'] = ['like', 'N00128%'];
        if ($isUse)
            $conditions ['USE_YN'] = ['eq', 'Y'];

        $model = new Model();
        static::$saleTeamList = $model->table('tb_ms_cmn_cd')
            ->where($conditions)
            ->getField($field);

        return static::$saleTeamList;
    }

    /**
     * 获取仓库
     */
    public static $warehouses;
    public static function getWarehouseId()
    {
        if (static::$warehouse) return static::$warehouse;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00068%" and USE_YN = "Y"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return static::$warehouse = $ret;
    }

    /**
     * 合作评级
     *
     */
    public static function getCooperativeRating()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N001880%" and USE_YN = "Y"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        if ($ret) return $ret;
        else {
            return [
                1 => 'A',
                2 => 'B',
                3 => 'C',
                4 => 'D',
                5 => 'E',
            ];
        }
    }

    /**
     * 合作评级
     *
     */
    public static function getCooperativeRatingForFilter()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N001880%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        if ($ret) return $ret;
        else {
            return [
                1 => 'A',
                2 => 'B',
                3 => 'C',
                4 => 'D',
                5 => 'E',
            ];
        }
    }

    /**
     * 获取所有仓库
     *
     */
    public static function getAllDeliveryWarehouse()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model
            ->join("tb_wms_warehouse ON tb_ms_cmn_cd.CD = tb_wms_warehouse.CD ")
            ->where('tb_ms_cmn_cd.CD like "N00068%" and tb_ms_cmn_cd.USE_YN = "Y"')
            ->group('tb_ms_cmn_cd.CD')
            ->getField("tb_ms_cmn_cd.CD, tb_ms_cmn_cd.CD, tb_ms_cmn_cd.CD_VAL, GROUP_CONCAT(tb_wms_warehouse.place,'-',tb_wms_warehouse.address) as place_address");
        return $ret;
    }

    /**
     * 获取所有退货仓库
     * @param $where
     *
     */
    public static function getReturnDeliveryWarehouse($where = [])
    {
        $model = M('_ms_cmn_cd', 'tb_');
        if ($where['warehouse']) $condition['tb_ms_cmn_cd.CD_VAL'] = $where['warehouse'];
        $condition['tb_ms_cmn_cd.CD'] = ['like', 'N00068%'];
        $condition['tb_ms_cmn_cd.USE_YN'] = 'Y';
        $condition['tb_lgt_third_warehouse_code.TYPE'] = '3'; //退货仓
        $ret = $model
            ->join("tb_lgt_third_warehouse_code ON tb_lgt_third_warehouse_code.WAREHOUSE_CD = tb_ms_cmn_cd.CD ")
            ->where($condition)
            ->group('tb_ms_cmn_cd.CD')
            ->field("tb_ms_cmn_cd.CD, tb_ms_cmn_cd.CD_VAL, tb_lgt_third_warehouse_code.THIRD_CD")
        ->select();
        return $ret;
    }

    /**
     * 锁定库存仓库获取
     *
     */
    public static function getAllDeliveryWarehouseLock()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->where('CD like "N00068%"')->getField('CD, CD, CD_VAL');
        return $ret;
    }

    /**
     * 发货方
     *
     */
    public static function manage()
    {
        return [
            1 => L('我方发货'),
            2 => L('外部发货')
        ];
    }

    /**
     * 系统对接
     *
     */
    public static function systemDocking()
    {
        return [
            1 => L('已对接'),
            2 => L('未对接')
        ];
    }

    public static $senderSystem;
    /**
     * 发货系统
     */
    public static function senderSystem()
    {
        if (static::$senderSystem) return static::$senderSystem;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00204%" and USE_YN = "Y"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return static::$senderSystem = $ret;
    }

    public static $jobContent;

    /**
     * 作业内容
     */
    public static function jobContent()
    {
        if (static::$jobContent) return static::$jobContent;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N002060%" and USE_YN = "Y"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return static::$jobContent = $ret;
    }

    /**
     * 系统来源
     *
     */
    public static function systemSource()
    {
        $code_arr = CodeModel::getCodeKeyValArr(['N00238']);
        $code_arr['N001950500'] = 'ERP';
        return $code_arr;
    }

    /**
     * 日志类型
     *
     */
    public static function logType()
    {
        return [
            'N001940200' => L('业务'),
            'N001790100' => L('接口')
        ];
    }

    /**
     * es-search conf
     *
     */
    public static function esSearchConf($source = 'N001950500')
    {
        $base = [
            'N001950500' => [
                'index' => 'gs_log',
                'type' => 'gs_log'
            ],
            'es_order' => [
                'index' => 'es_order',
                'type'  => 'es_order'
            ]
        ];
        return $base [$source];
    }

    /**
     * 调拨页选择状态
     *
     */
    public static function selectedState()
    {
        return [
            1 => L('未选择'),
            2 => L('已选择')
        ];
    }

    /**
     * 调拨类型
     *
     */
    public static function alloType()
    {
        return [
            1 => L('普通调拨'),
            2 => L('非审核调拨')
        ];
    }

    /**
     * 调拨页状态选择
     *
     */
    public static function auditAlloState()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->where('CD like "N00197%"')->order('SORT_NO ASC')->getField('CD, CD, CD_VAL');
        return $ret;
    }

    public static function humpToLine($str){
        $str = preg_replace_callback('/([A-Z]{1})/',function($matches){
            return '_'.strtolower($matches[0]);
        },$str);
        return $str;
    }

    public static function hump($str)
    {
        $result = [];
        if (is_array($str)) {
            foreach ($str as $key => $item) {
                if (is_array($item)) {
                    $result [$key] = self::hump($item);
                } else {
                    $result [self::humpToLine($key)] = $item;
                }
            }
            return $result;
        } else {
            return self::humpToLine($str);
        }
    }

    public static function convertUnderline($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i',function($matches){
            return strtoupper($matches[2]);
        },$str);
        return $str;
    }

    public static function convertUnder($str)
    {
        $result = [];
        if (is_array($str)) {
            foreach ($str as $key => $item) {
                if (is_array($item)) {
                    $result [$key] = self::convertUnder($item);
                } else {
                    $result [self::convertUnderline($key)] = $item;
                }
            }
            return $result;
        } else {
            return self::convertUnderline($str);
        }
    }

    const CURRENT_CURRENCY_LOCAL = 'cny';//当前所保存的本地币种（人民币）
    const CURRENT_CURRENCY_USD   = 'usd';//当前锁保存的对外币种（美元）

    /**
     * 获取各币种汇率
     * @param $current 币种CODE
     * @param $time    交易时间
     * @return array 返回RMB单价，USD单价
     */
    public static function exchangeRate($current, $time)
    {
        $time = date('Y-m-d', strtotime($time));
        $r = [];
        $currentValue = static::getCurrency();

        if (!isset($currentValue [$current])) {
            return false;
        }

        $requestUrl = 'http://3rd-biapi.izene.org/external/exchangeRate?';
        $queryDataToCny = [
            'date' => $time,
            'src_currency' => strtoupper($currentValue [$current]),
            'dst_currency' => 'CNY'
        ];
        $queryCny      = http_build_query($queryDataToCny);
        $responseCny = curl_get_json_get($requestUrl . $queryCny);
        $queryDataToUsd = [
            'date' => $time,
            'src_currency' => strtoupper($currentValue [$current]),
            'dst_currency' => 'USD'
        ];
        $queryUsd      = http_build_query($queryDataToUsd);
        $responseUsd = curl_get_json_get($requestUrl . $queryUsd);

        $r [static::CURRENT_CURRENCY_LOCAL] = json_decode($responseCny, true) ['data'][0]['rate'];
        $r [static::CURRENT_CURRENCY_USD]   = json_decode($responseUsd, true) ['data'][0]['rate'];
        return $r;
    }

    public static $inStorageType;

    /**
     * 入库类型
     */
    public function inStorageType()
    {
        if (static::$inStorageType) return static::$inStorageType;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N000940%" and USE_YN = "Y"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return static::$inStorageType = $ret;
    }

    public static $outStorageType;

    /**
     * 入库类型
     */
    public function outStorageType()
    {
        if (static::$outStorageType) return static::$outStorageType;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N000950%" and USE_YN = "Y"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return static::$outStorageType = $ret;
    }

    /**
     * 收支方向
     */
    public static $transferTypeData;

    public function transferType()
    {
        if (static::$transferTypeData)
            return static::$transferTypeData;

        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N001950%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');

        return static::$transferTypeData = $ret;
    }

    /**
     * 记录接口日志
     */
    public function _catchMe()
    {
        $filePath = '/opt/logs/logstash/';
        $fileName = 'logstash_' . date('Ymd') . '_erp_json.log';
        $a = parse_url($_SERVER["REQUEST_URI"]);
        parse_str($a["query"], $s);
        $a = $s['a'];
        $m1 =  $s['m'];
        $m = M('');
        //获取操作日志
        $res = $m->query("SELECT CONCAT(bbm_node.TITLE,bbm_node.NAME) as opt from bbm_node where lower(bbm_node.CTL)='$m1' AND lower(bbm_node.ACT)='$a' ");
        $action = $s ['a'];
        $tlog = D('TbMsUserOperationLog');
        $data ['uId']           = create_guid();
        $data ['noteType']      = 'N001940200';
        $data ['source']        = 'N001950500';
        $data ['ip']            = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']);
        $data ['space']         = null;
        $data ['cTime']         = date('Y-m-d H:i:s');
        $data ['cTimeStamp']    = time();
        $data ['action']        = $s ['a'];
        $data ['model']         = $s ['m'];
        $data ['msg']           = json_encode([
            'model' => MODULE_NAME,
            'msg'   => [
                'GET' => $_GET,
                'POST'=> $_POST,
                'action' => $s ['m'],
                'operation' => $res[0]['opt'],
                'uri' => $_SERVER["REQUEST_URI"],
                'request_data' => $this->getRequestData(),
                'response_data' => $this->getResponseData(),
                'request_url' => $this->getRequestUrl(),
            ]
        ]);
        $data ['user'] = $_SESSION['m_loginname'];
        $tlog->add($data);
        $data ['id'] = $tlog->getLastInsID();
        $data ['msg'] = json_decode($data['msg']);
        $txt = json_encode($data);
        $file = $filePath.$fileName;
        fclose(fopen($file, 'a+'));
        $_fo = fopen($file, 'rb');
        fclose($_fo);
        file_put_contents($file, $txt . "\n", FILE_APPEND);
    }

    public static $PAYMENT_TYPE = [
            'N001950100',//采购付款
            'N001950500',//采购付款手续费
            'N001950300',//划转转出
        ];
     public static $COLLECTION_TYPE = 2;//收款
    /**
     * 判断是收款还是付款
     * @param type $type code
     * @return string
     */
    public static function checkTransactionType($type) {
        if (empty($type)) {
            return;
        }
        return M('cmn_cd', 'tb_ms_')->where(['CD' => $type])->getField('ETC');
    }

    public static function isPaymentTransactionType($type) {
        if (empty($type)) {
            return false;
        }
        return M('cmn_cd', 'tb_ms_')->where(['CD' => $type])->getField('ETC') == '付款' ? true : false;
    }

    public static function getTransactionTypeCds($name = '付款') {
        return M('cmn_cd', 'tb_ms_')->where(['ETC' => $name])->getField('CD', true);
    }

    public static $collection;
    //收款类型
    public static function getCollection()
    {
        if (static::$collection) return static::$collection;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00252%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return static::$collection = $ret;
    }

    public static $dimension;
    //佣金配置-维度
    public static function dimension()
    {
        if (static::$dimension) return static::$dimension;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00260%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return static::$dimension = $ret;
    }

    public static $calculate;
    //佣金配置-计算
    public static function judge()
    {
        if (static::$calculate) return static::$calculate;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->where('CD like "N00261%"')->select();
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return static::$calculate = $ret;
    }

    public function isPositiveNumber($number) {
        return (is_numeric($number) && $number > 0) ? true : false;
    }

    /**
    更新
     *
     * @param array $datas 需要更新的数据集合
     * @param object $model 模型
     * @param string $pk 主键
     *
     * @return string $sql$sql
     */
    public static function saveMult($datas, $model, $pk = '')
    {
        $sql = '';
        $lists = [];
        isset($pk) or $pk = 'id';
        foreach ($datas as $data) {
            foreach ($data as $key => $value) {
                if ($pk === $key) {
                    $ids[] = $value;
                } else {
                    $lists[$key] .= sprintf("WHEN %u THEN '%s' ", $data[$pk], $value);
                }
            }
        }
        foreach ($lists as $key => $value) {
            $sql .= sprintf("`%s` = CASE `%s` %s END,", $key, $pk, $value);
        }
        $sql = sprintf('UPDATE %s SET %s WHERE %s IN ( %s )', strtolower($model), rtrim($sql, ','), $pk, implode(',', $ids));
        if (empty($Models)) {
            $Models = M();
        }
        return $Models->execute($sql);
    }

    //时效列表筛选下拉框相关数据
    public static function getListCondition(){
        $out_warehouse_cd = AllocationExtendNewRepository::$warehouse;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['in', $out_warehouse_cd];
        $out_warehouse = $model->field('CD,CD_VAL')->where($conditions)->select();
        $sell_small_team = $list = CodeModel::getSellSmallTeamCodeArr('N001282800');

        $logistics_way = $list = CodeModel::getCodeAll(['CD'=>['like','%N002820%']],'CD,CD_VAL');
        $in_warehouse = $list = CodeModel::getCodeAll(['CD'=>['like','%N00068%']],'CD,CD_VAL');
        $allo_class = new AllocationExtendNewRepository();
        $logistics_company = $allo_class->getTransportCompany();
        $res['out_warehouse'] = $out_warehouse;
        $res['in_warehouse'] = $in_warehouse;
        $res['sell_small_team'] = $sell_small_team;
        $res['logistics_way'] = $logistics_way;
        $res['logistics_company'] = $logistics_company;
        return $res;
    }

}

