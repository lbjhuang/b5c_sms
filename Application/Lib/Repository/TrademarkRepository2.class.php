<?php
/**
 * User: shenmo
 * Date: 19/07/25
 * Time: 17:38
 */

@import("@.Model.StringModel");

class TrademarkRepository extends Repository
{

    /**
     * @var array
     */
    public $exp_cell_name = [
        ['trademark_name', '商标名称'],
        ['trademark_type_val', '商标类型'],
        ['company_name', '注册公司'],
        ['country_name', '注册国家'],
        ['apply_code', '申请编号'],
        ['applied_date', '申请日期'],
        ['international_type', '国际分类'],
        ['goods', '商品/服务'],
        ['initial_review_date', '初审公告日期'],
        ['register_code', '注册编号'],
        ['register_date', '注册日期'],
        ['current_state_name', '当前状态'],
        ['current_step_name', '当前审批节点'],
        ['agent', '代理/办理机构'],
        ['remark', '备注'],
    ];

    /**
     * @var string
     */
    public $exp_title = '商标导出';
    public $img_path = './opt/b5c-disk/img/';

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getTrademarkDetail($id)
    {
        $where['tb_trademark_base.id'] = $id;
        $field                         = [
            'tb_trademark_base.id as trademark_id',
            'tb_trademark_base.trademark_name',
            'tb_trademark_base.trademark_code',
            'tb_trademark_base.img_url',
            "tb_trademark_detail.*",
        ];
        return $this->model->table('tb_trademark_base')
            ->field($field)
            ->join('LEFT JOIN tb_trademark_detail on tb_trademark_detail.trademark_base_id = tb_trademark_base.id')
            ->where($where)
            ->select();
    }


    /**
     * @param $id
     *
     * @return mixed
     */
    public function getTrademarkBaseAndDetail($id)
    {
        $where['id']   = $id;
        $field         = [
            'id',
            'trademark_name',
            'trademark_code',
            'trademark_type',
            'img_url',
        ];
        $trademarkBase = $this->model->table('tb_trademark_base')
            ->field($field)
            ->where($where)
            ->find();

        $img_info                 = json_decode($trademarkBase['img_url'], true);
        if (empty($img_info['save_name'])) {
            $trademarkBase['img_url'] = json_encode('');
        } else {
            $img_info['show_img'] = ERP_URL. $this->img_path . $img_info['save_name'];
            $trademarkBase['img_url'] = json_encode($img_info);
        }

        $trademarkBase['img_resource']    = $this->packStreamFile($trademarkBase['img_url']);
        $whereDetail['trademark_base_id'] = $id;
        $trademarkDetail                  = $this->model->table('tb_trademark_detail')
            ->where($whereDetail)
            ->select();

        foreach ($trademarkDetail as &$item) {
            if ($item['initial_review_date'] == '0000-00-00') $item['initial_review_date'] = '';
            if ($item['register_date'] == '0000-00-00') $item['register_date'] = '';
            if ($item['applied_date'] == '0000-00-00') $item['applied_date'] = '';
            $item['current_step_name'] = cdVal( $item['current_step']);
        }
        return ['trademark_base' => $trademarkBase, 'trademark_detail' => $trademarkDetail];
    }


    /**
     * @param $where
     *
     * @return mixed
     */
    public function getTrademarkBase($where)
    {
        return $this->model->table('tb_trademark_base base')
            ->join('left join tb_trademark_detail detail on base.id = detail.trademark_base_id')
            ->where($where)
            ->find();
    }

    /**
     * 流文件包装
     *
     * @param string $fname 要发送的文件(全路径)
     *
     * @return mixed
     */
    public function packStreamFile($fname)
    {
        $basePath = ATTACHMENT_DIR_IMG;
        $fullPath = $basePath . $fname;
        $response = '';
        if (file_exists($fullPath))
            $response = base64_encode(file_get_contents($fullPath));

        return $response;
    }

    /**
     * @param $wheres
     * @param $limit
     * @param $is_excel
     *
     * @return mixed
     */
    public function getTrademarkList($wheres, $limit, $is_excel = null)
    {

        $temp_model = $this->model->table('tb_trademark_base')
            ->field('
                tb_trademark_base.id,
                tb_trademark_base.trademark_name,
                tb_trademark_base.trademark_code,
                tb_trademark_detail.country_code,
                tb_trademark_detail.company_code,
                tb_trademark_detail.register_code,
                tb_trademark_detail.register_date,
                tb_trademark_detail.international_type,
                tb_trademark_detail.goods,
                tb_trademark_detail.agent,
                tb_trademark_detail.applied_date,
                 tb_trademark_detail.apply_code,
                tb_trademark_detail.current_state,
                tb_trademark_detail.current_step,
                tb_trademark_detail.initial_review_date,
                tb_trademark_detail.inter_register_date,
                tb_trademark_detail.register_date,
                tb_ms_user_area.zh_name as country_name,
                tb_ms_cmn_cd.CD_VAL as company_name,
                cd1.CD_VAL as current_state_name,
                cd2.CD_VAL as current_step_name,
                tb_trademark_base.trademark_type,
                tb_trademark_detail.remark
            ')
            ->join('left join tb_trademark_detail on tb_trademark_base.id = tb_trademark_detail.trademark_base_id')
            ->join('left join tb_ms_user_area on tb_ms_user_area.id = tb_trademark_detail.country_code')
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = tb_trademark_detail.company_code')
            ->join('left join tb_ms_cmn_cd cd1 on cd1.CD = tb_trademark_detail.current_state')
            ->join('left join tb_ms_cmn_cd cd2 on cd2.CD = tb_trademark_detail.current_step')
            ->where($wheres)
            ->order('tb_trademark_base.id,tb_trademark_detail.id desc');

        if ($is_excel) {
            return [$this->joinData($temp_model), []];
        }

        list($pages, $res_db) = $this->joinDataPage($limit, $temp_model);
        return [$res_db, $pages];
    }

    /**
     * @param $wheres
     * @param $limit
     * @param $is_excel
     *
     * @return mixed
     */
    public function getTrademarkListNew($wheres,$limit)
    {

        $temp_model = $this->model->table('tb_trademark_base')
            ->field('
                tb_trademark_base.id,
                tb_trademark_base.trademark_name,
                tb_trademark_base.trademark_code,
                tb_trademark_detail.country_code,
                tb_trademark_detail.company_code,
                tb_trademark_detail.register_code,
                tb_trademark_detail.international_type,
                tb_trademark_detail.goods,
                tb_trademark_detail.applied_date,
                tb_trademark_detail.current_state,
                tb_trademark_detail.current_step,
                tb_ms_user_area.zh_name as country_name,
                tb_ms_cmn_cd.CD_VAL as company_name,
                cd1.CD_VAL as current_state_name,
                cd2.CD_VAL as current_step_name,
                cd3.CD_VAL as trademark_type_val,
                tb_trademark_detail.remark
            ')
            ->join('left join tb_trademark_detail on tb_trademark_base.id = tb_trademark_detail.trademark_base_id')
            ->join('left join tb_ms_user_area on tb_ms_user_area.id = tb_trademark_detail.country_code')
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = tb_trademark_detail.company_code')
            ->join('left join tb_ms_cmn_cd cd1 on cd1.CD = tb_trademark_detail.current_state')
            ->join('left join tb_ms_cmn_cd cd2 on cd2.CD = tb_trademark_detail.current_step')
            ->join('left join tb_ms_cmn_cd cd3 on cd3.CD = tb_trademark_base.trademark_type')

            ->where($wheres)
            ->limit($limit[1])
            ->order('tb_trademark_base.id desc,tb_trademark_detail.id desc');

        return $this->joinData($temp_model);
    }

    /**
     * @param $wheres
     * @param $limit
     * @param $is_excel
     *
     * @return mixed
     */
    public function getTrademarkBaseList($wheres, $limit, $is_excel = null)
    {

        $temp_model = $this->model->table('tb_trademark_base')
            ->field('
                tb_trademark_base.id,
                tb_trademark_base.trademark_name,
                tb_trademark_base.trademark_code
            ')
            ->where($wheres)
            ->order('tb_trademark_base.id desc');

        if ($is_excel) {
            return [$this->joinData($temp_model), []];
        }

        list($pages, $res_db) = $this->joinDataPage($limit, $temp_model);
        return [$res_db, $pages];
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function createTrademarkAndDetailExport($params)
    {
        //没有商标则新增
//        $where['trademark_name'] = $params['trademark_base']['trademark_name'];
//        if ($trademark = $this->getTrademarkBase($where)) {
//            $res = $trademark['id'];
//        } else {
//            $res = $this->createTrademarkBase($params['trademark_base']);
//        }
        $res = $this->createTrademarkBase($params['trademark_base']);

        if (!$res) {
            throw new Exception(L('商标基础信息新增失败'));
        }
        $trademark_detail                      = $params['trademark_detail'];
        $trademark_detail['trademark_base_id'] = $res;
        $res                                   = $this->createTrademarkDetailExport($trademark_detail);
        if (!$res) {
            throw new Exception(L('商标详情信息新增失败'));
        }
        return true;
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function createTrademarkBase($params)
    {
        $data['trademark_name'] = $params['trademark_name'];
        $data['img_url']        = json_encode($params['img_url'], JSON_UNESCAPED_UNICODE);
        $data['trademark_type'] = $params['trademark_type'];
        $data['created_by']     = DataModel::userNamePinyin();
        $data['updated_by']     = DataModel::userNamePinyin();
        $model                  = new TbTrademarkModel();
        return $model->createTrademark($data);
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function createTrademarkDetail($params)
    {
        $data = $params;
        return TbTrademarkDetailModel::createTrademarkDetail($data);
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function createTrademarkDetailExport($params)
    {
        $data = $params;
        return TbTrademarkDetailModel::createTrademarkDetailExport($data);
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function updateTrademarkAndDetail($params)
    {
        $res = $this->updateTrademarkBase($params['trademark_base']);
        if ($res === false) {
            throw new Exception(L('商标基础信息编辑失败'));
        }
        //编辑调整-原因：商标国家记录数量存在变化（增删改）-先删除旧商标国家信息再重新生成新商标国家信息
        $res = $this->updateTrademarkDetailNew($params['trademark_detail'], $params['trademark_base']['id']);
        if (!$res) {
            throw new Exception(L('商标详情信息编辑失败'));
        }
        return true;
    }

    public function updateTrademarkBase($params)
    {
        $data['id']             = $params['id'];
        $data['trademark_name'] = $params['trademark_name'];
        $data['img_url']        = json_encode($params['img_url'],JSON_UNESCAPED_UNICODE);
        $data['trademark_type'] = $params['trademark_type'];
        $model                  = new TbTrademarkModel();
        return $model->updateTrademark($data);
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function updateTrademarkDetail($params, $trademark_base_id)
    {
        $return = true;
        $data   = $params;
        $detail_model = new TbTrademarkDetailModel();
        foreach ($data as $val) {
            //判断新增的是处于审批流的哪一步，申请注册中需要传入商标有效期，待提交需要传入申请价格和周期等
            $detail = $detail_model->where(['trademark_base_id' => $trademark_base_id])->find();
            $step = $detail['current_step'];
            if(empty($step)){
                throw new Exception(L('请传入审批流程节点'));
            }
            if($step == TbTrademarkDetailModel::$step['waiting_commit_step']){
                if(empty($val['apply_price']) || empty($val['apply_period'])){
                    throw new Exception(L('请传入商标申请价格和申请周期'));
                }
            }

            if($step == TbTrademarkDetailModel::$step['apply_registering_step']){
                //与前端墨尘约定只传递中文字段 保证中文字段与英文字段的一致性 2019-07-31 shenmo
                $val['current_state_en']  = $val['current_state'];
                if(empty($val['effective_start']) || empty($val['effective_end']) || empty($val['apply_price']) || empty($val['apply_period'])){
                    throw new Exception(L('请传入商标的有效期日期区间和申请价格及周期'));
                }
            }

            $res                      = TbTrademarkDetailModel::updateTrademarkDetail($val, $trademark_base_id);
            if ($res === false) {
                $return = false;
                break;
            }

        }
        return $return;
    }

    /**
     * @param $params
     * @param $trademark_base_id
     *
     * @return mixed
     */
    public function updateTrademarkDetailNew($params, $trademark_base_id)
    {
        $where['trademark_base_id'] = $trademark_base_id;
//        $res = TbTrademarkDetailModel::removeTrademarkDetail($where);
        return $this->updateTrademarkDetail($params, $trademark_base_id);
    }

    /**
     * @param $limit
     * @param $temp_model
     * @param $pages
     * @return array
     */
    private function joinDataPage($limit, $temp_model)
    {
        $search_model          = clone $temp_model;
        $pages['total']        = $temp_model->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page']     = $limit[1];
        $res_db                = $search_model->limit($limit[0], $limit[1])->select();
        return array($pages, $res_db);
    }

    /**
     * @param $temp_model
     * @return array
     */
    private function joinData($temp_model)
    {
        $res_db = $temp_model->select();
        return $res_db;
    }

    public function isUniqueTrademark()
    {
        $arg                                       = func_get_args();
        $where['tb_trademark_base.trademark_name'] = $arg[0];
        $where['tb_trademark_base.trademark_type'] = $arg[1];
        $where['tb_trademark_detail.country_code'] = $arg[2];
        $where['tb_trademark_detail.company_code'] = $arg[3];
        $count                                     = $this->model->table('tb_trademark_base')
            ->join('LEFT JOIN tb_trademark_detail on tb_trademark_detail.trademark_base_id = tb_trademark_base.id')
            ->where($where)
            ->count();
        return $count ? false : true;
    }
}