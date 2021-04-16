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
        ['agent', '代理/办理机构'],
        ['remark', '备注'],
    ];

    /**
     * @var string
     */
    public $exp_title = '商标导出';
    public $img_path = './opt/b5c-disk/img/';

    //申请记录状态[注册的，使用的都统一用这几个状态值]
    const VERIFY_WAITING= 1;  //待审核
    const VERIFY_PASS = 2;    //审核通过
    const VERIFY_REJECT = 3;  //审核驳回
    const VERIFY_CANCEL = 4;  //审核取消
    const VERIFY_ROLE_ID = 93;  //法务的角色id
    static $register_status = [self::VERIFY_WAITING => '待审核', self::VERIFY_PASS => '已通过', self::VERIFY_REJECT => '已驳回', self::VERIFY_CANCEL => '已取消'];

    //商标的外部注册状态
    const HAVE_REGISTER = 'N002980004';  //已成功注册商标
    static $use_type = ['1'=>'贴牌使用', '2'=>'店铺申请使用', '3'=>'授权使用'];

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
    public function getTrademarkBaseAndDetail($id, $trademark_no)
    {
        if(empty($id)){
            $where['trademark_no']   = $trademark_no;
        }else{
            $where['id']   = $id;
        }

        $field         = [
            'id',
            'trademark_name',
            'trademark_code',
            'trademark_type',
            'img_url',
            'register_apply_no',
            'trademark_no',
            'protect_period',
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
        if(!empty($id)){
            $whereDetail['trademark_base_id'] = $id;
        }else{
            $whereDetail['trademark_base_id'] = $trademarkBase['id'];
        }

        $trademarkDetail                  = $this->model->table('tb_trademark_detail')
            ->where($whereDetail)
            ->select();
        foreach ($trademarkDetail as &$item) {
            if ($item['initial_review_date'] == '0000-00-00') $item['initial_review_date'] = '';
            if ($item['register_date'] == '0000-00-00') $item['register_date'] = '';
            if ($item['applied_date'] == '0000-00-00') $item['applied_date'] = '';
        }
        //查找关联的合同编号，用于使用商标那里作展示。
        $trademarkBase['contract_nos'] = $this->getContractNo($trademark_no);

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
                tb_trademark_detail.initial_review_date,
                tb_trademark_detail.inter_register_date,
                tb_trademark_detail.register_date,
                tb_ms_user_area.zh_name as country_name,
                tb_ms_cmn_cd.CD_VAL as company_name,
                cd1.CD_VAL as current_state_name,
                tb_trademark_base.trademark_type,
                tb_trademark_detail.remark
            ')
            ->join('left join tb_trademark_detail on tb_trademark_base.id = tb_trademark_detail.trademark_base_id')
            ->join('left join tb_ms_user_area on tb_ms_user_area.id = tb_trademark_detail.country_code')
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = tb_trademark_detail.company_code')
            ->join('left join tb_ms_cmn_cd cd1 on cd1.CD = tb_trademark_detail.current_state')
            ->where($wheres)
            ->order('tb_trademark_base.id desc,tb_trademark_detail.id desc');

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
    public function getTrademarkListNew($params)
    {
        $page_params = $params['page'];
        $search_params = $params['search'];

        $page_size = $page_params['per_page'] ? $page_params['per_page'] : 20;
        $page = $page_params['current_page'] ? $page_params['current_page'] : 1;

        if(!empty($search_params['country_code'])){
            $where['tb_trademark_detail.country_code'] = ['in', $search_params['country_code']];
        }
        if(!empty($search_params['company_code'])){
            $where['tb_trademark_detail.company_code'] = ['in', $search_params['company_code']];
        }

        if(!empty($search_params['current_state'])){
            $where['tb_trademark_detail.current_state'] = ['in', $search_params['current_state']];
        }

        if(!empty($search_params['register_code'])){
            $where['tb_trademark_detail.register_code'] = trim($search_params['register_code']);
        }

        if(!empty($search_params['current_step'])){
            $where['tb_trademark_detail.current_step'] = trim($search_params['current_step']);
        }

        if(!empty($search_params['international_type'])){
            $where['tb_trademark_detail.international_type'] = trim($search_params['international_type']);
        }

        if(!empty($search_params['trademark_name'])){
            $where['tb_trademark_base.trademark_name'] = trim($search_params['trademark_name']);
        }

        if(!empty($search_params['register_code'])){
            $where['tb_trademark_base.register_code'] = trim($search_params['register_code']);
        }

        if(!empty($search_params['created_by'])){
            $where['tb_trademark_base.created_by'] = trim($search_params['created_by']);
        }

        if(!empty($search_params['trademark_no'])){
            $where['tb_trademark_base.trademark_no'] = trim($search_params['trademark_no']);
        }

        $count =  $this->model->table('tb_trademark_base')
            ->field('
                tb_trademark_base.id,
                tb_trademark_base.register_apply_no,
                tb_trademark_base.trademark_name,
                tb_trademark_base.trademark_no,
                tb_trademark_base.trademark_code,
                tb_trademark_detail.country_code,
                tb_trademark_detail.country_code as country_id,
                tb_trademark_detail.company_code,
                tb_trademark_detail.register_code,
                tb_trademark_detail.international_type,
                tb_trademark_detail.goods,
                tb_trademark_detail.applied_date,
                tb_trademark_detail.current_state,
                tb_ms_user_area.zh_name as country_name,
                tb_ms_cmn_cd.CD_VAL as company_name,
                cd1.CD_VAL as current_state_name,
                cd2.CD_VAL as trademark_type_val,
                tb_trademark_detail.remark
            ')
            ->join('left join tb_trademark_detail on tb_trademark_base.id = tb_trademark_detail.trademark_base_id')
            ->join('left join tb_ms_user_area on tb_ms_user_area.id = tb_trademark_detail.country_code')
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = tb_trademark_detail.company_code')
            ->join('left join tb_ms_cmn_cd cd1 on cd1.CD = tb_trademark_detail.current_state')
            ->join('left join tb_ms_cmn_cd cd2 on cd2.CD = tb_trademark_base.trademark_type')
            ->where($where)
            ->count();
        $start = ($page-1) * $page_size;
        $list = $this->model->table('tb_trademark_base')
            ->field('
                tb_trademark_base.id,
                tb_trademark_base.register_apply_no,
                tb_trademark_base.trademark_name,
                tb_trademark_base.trademark_no,
                tb_trademark_base.trademark_code,
                tb_trademark_detail.country_code,
                tb_trademark_detail.country_code as country_id,
                tb_trademark_detail.company_code,
                tb_trademark_detail.register_code,
                tb_trademark_detail.international_type,
                tb_trademark_detail.goods,
                tb_trademark_detail.applied_date,
                tb_trademark_detail.current_state,
                tb_ms_user_area.zh_name as country_name,
                tb_ms_cmn_cd.CD_VAL as company_name,
                cd1.CD_VAL as current_state_name,
                cd2.CD_VAL as trademark_type_val,
                tb_trademark_detail.remark
            ')
            ->join('left join tb_trademark_detail on tb_trademark_base.id = tb_trademark_detail.trademark_base_id')
            ->join('left join tb_ms_user_area on tb_ms_user_area.id = tb_trademark_detail.country_code')
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = tb_trademark_detail.company_code')
            ->join('left join tb_ms_cmn_cd cd1 on cd1.CD = tb_trademark_detail.current_state')
            ->join('left join tb_ms_cmn_cd cd2 on cd2.CD = tb_trademark_base.trademark_type')
            ->where($where)
            ->limit($start, $page_size)
            ->order('tb_trademark_base.id desc,tb_trademark_detail.id desc')->select();
        $pages = ['total'=>$count, 'current_page'=>$page_params['current_page'], 'per_page'=>$page_params['per_page']];
        return ['data'=>$list,'pages'=>$pages];
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


    //ODM商标信息提交
    public function createTrademarkAndDetail($params)
    {
        $trademark_detail = $params['trademark_detail'];
        $trademark_base = $params['trademark_base'];
        $register_id = $params['register_id'];
        M()->startTrans();
        $save_base_data = $this->makeBaseData($trademark_base);
        $save_base_data['created_by'] = userName();
        $save_base_data['created_at'] = date("Y-m-d H:i:s",time());
        $save_base_data['trademark_no'] =  date("Ymd",time()).TbWmsNmIncrementModel::generateNo('TN');//生成注册编号;

        $trademark_base_id = M('trademark_base','tb_')->add($save_base_data);
        if(!$trademark_base_id){
            M()->rollback();
            return ['data'=>'','info'=>'商标基础信息新增失败', 'status'=>0];
        }
        $save_detail_data = $this->makeDetailData($trademark_detail);
        $save_detail_data['trademark_base_id'] = $trademark_base_id;
        $save_detail_data['trademark_type_en'] = $trademark_base['trademark_type'];
        $save_detail_data['created_by'] = userName();
        $save_detail_data['created_at'] = date("Y-m-d H:i:s",time());

        $detail_res = M('trademark_detail','tb_')->add($save_detail_data);
        if (!$detail_res) {
            M()->rollback();
            return ['data'=>'','info'=>'商标详情信息新增失败', 'status'=>0];
        }else{
            $save_related_no['related_trademark_no'] = $save_base_data['trademark_no'];
            $register_res = M('register_trademark','tb_')->where(['id'=>$register_id])->save($save_related_no);
            if(!$register_res){
                M()->rollback();
            }else{
                M()->commit();
            }
        }
        return ['trademark_base_id'=>$trademark_base_id];
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
        $data['register_apply_no'] = $params['register_apply_no'];  //关联的注册单号
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
    public function createTrademarkDetailExport($params)
    {
        $data = $params;
        return TbTrademarkDetailModel::createTrademarkDetailExport($data);
    }


    //修改ODM数据
    public function updateTrademarkAndDetail($params)
    {
        $trademark_base_id = $params['trademark_base']['id'];
        $trademark_no = $params['trademark_base']['trademark_no'];
        $detail_model = M('trademark_detail','tb_');
        $base_model = M('trademark_base','tb_');
        $trademark_base = $params['trademark_base'];
        $update_base_data = $this->makeBaseData($trademark_base);
        $base_res = $base_model->where(['id'=>$trademark_base_id])->save($update_base_data);

        $trademark_detail = $params['trademark_detail'];
        $update_detail_data = $this->makeDetailData($trademark_detail);
        $update_detail_data['trademark_type_en'] = $trademark_base['trademark_type'];
        $detail_res = $detail_model->where(['trademark_base_id'=>$trademark_base_id])->save($update_detail_data);

        //如果注册状态修改了且是【已申请，初审公告，已注册】，则需要通知商标注册申请人
        if(in_array($trademark_detail['current_state'], ['N002980002','N002980003','N002980004'])) {
            $register_info = M('register_trademark','tb_')->field('create_user_id,trademark_name,created_at,created_by')->where('related_trademark_no = '.$trademark_no)->find();
            $state_name = cdVal($trademark_detail['current_state']);
            $create_user_id = $register_info['create_user_id'];
            //发消息
            $content = ">**通知事项** 
你发起的新增注册商标申请，注册状态已更新
> 发起人：<font color=info >created_by</font>
> 申请发起时间：<font color=info >created_at</font>
> 商标注册状态：<font color=info >status_desc</font>
>如需查看详情，请点击： [查看详情](detail_url)";

            $tab_data = [
                'url' => urldecode('/index.php?' . http_build_query(['m' => 'legal', 'a' => 'odm'])),
                'name' => 'ODM 商标管理'
            ];
            $replace_data = [
                'created_by' => $register_info['created_by'],
                'created_at' => $register_info['created_at'],
                'status_desc' => $state_name,
                'detail_url' => ERP_HOST . '/index.php?' . http_build_query(['tab_data' => $tab_data]),
            ];
            $this->sendMessage($create_user_id,'M_ID', $content, $replace_data);
        }
        return true;
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


    //添加注册申请数据
    public function addRegisterTrademark($params) {
        $insert['trademark_name'] = $params['trademark_name'];
        $insert['country_id'] = $params['country_id'];
        $insert['image_urls'] = $this->formatImage($params['image_urls']);
        $insert['remark'] = $params['remark'];
        $insert['status'] = self::VERIFY_WAITING;
        $insert['created_at'] = date("Y-m-d H:i:s", time());
        $insert['updated_at'] = date("Y-m-d H:i:s", time());
        $insert['created_by'] = $_SESSION['m_loginname'];
        $insert['updated_by'] = $_SESSION['m_loginname'];
        $insert['create_user_id'] = $_SESSION['userId'];
        $insert['register_no'] ='ZC' . date('Ymd') . TbWmsNmIncrementModel::generateNo('ZC');//生成流水号
        $res = M('register_trademark','tb_')->add($insert);
        if($res > 0){
            $content = ">**待操作事项** 
{$_SESSION['m_loginname']}发起了新增注册商标申请，请知悉
> 发起人：<font color=info >created_by</font>
> 申请发起时间：<font color=info >created_at</font>
>请尽快处理。如需查看详情，请点击： [查看详情](detail_url)";

            $tab_data = [
                'url' => urldecode('/index.php?' . http_build_query(['m' => 'legal', 'a' => 'registered_trademark'])),
                'name' => '注册商品'
            ];
            $replace_data = [
                'created_by' => $insert['created_by'],
                'created_at' => $insert['created_at'],
                'detail_url' => ERP_HOST . '/index.php?' . http_build_query(['tab_data' => $tab_data]),
            ];
            $this->sendMessage(self::VERIFY_ROLE_ID,'ROLE_ID', $content, $replace_data);
        }
        return $res;
    }






    //注册申请信息列表数据筛选
    public function registerList($params){
        $page_size = $params['page_size'] ? $params['page_size'] : 20;
        $page = $params['page'] ? $params['page'] : 1;

        if(!empty($params['country_id'])){
            $where['a.country_id'] = $params['country_id'];
        }
        if(!empty($params['trademark_name'])){
            $where['a.trademark_name'] = trim($params['trademark_name']);
        }
        if(!empty($params['status'])){
            $where['a.status'] = ['in',$params['status']];
        }else{
            $where['a.status'] = ['in', [1,2,3]];
        }

        if(!empty($params['created_by'])){
            $where['a.created_by'] = trim($params['created_by']);
        }

        if(!empty($params['created_at'])){
            $where['a.created_at'] = $params['created_at'];
        }

        $register_model = M('register_trademark','tb_');
        $count =  $register_model->alias('a')->where($where)->count();
        $start = ($page-1) * $page_size;
        $list = $register_model->alias('a')->field('a.*,b.zh_name as country_name')->join('left join  tb_ms_user_area b on a.country_id = b.id')->where($where)->order('a.id desc' )->limit($start, $page_size)->select();
        foreach($list as $k=>$v){
            $list[$k]['status_name'] = static::$register_status[$v['status']];
            $images = json_decode($v['image_urls'],true);
            foreach($images as $ik=>$iv){
                $images[$ik]['show_img'] = ERP_HOST.$iv['save_path'].$iv['save_name'];
            }
            $list[$k]['image_urls'] = $images;
        }
        return ['list'=>$list,'total'=>$count];
    }


    //注册申请列表修改数据展示
    public function editRegisterTrademarkShow($params) {
        $id = $params['id'];
        $res = M('register_trademark','tb_')->where("id = $id")->find();
        $images = json_decode($res['image_urls'],true);
        foreach($images as $ik=>$iv){
            $images[$ik]['show_img'] = ERP_HOST.$iv['save_path'].$iv['save_name'];
        }
        $res['image_urls'] = $images;
        return $res;
    }


    //注册申请列表修改提交
    public function editRegisterTrademark($params) {
        $id = $params['id'];
        $update['trademark_name'] = $params['trademark_name'];
        $update['country_id'] = $params['country_id'];
        $update['image_urls'] = $this->formatImage($params['image_urls']);
        $update['remark'] = $params['remark'];
        $update['updated_at'] = date("Y-m-d H:i:s", time());
        $update['updated_by'] = $_SESSION['m_loginname'];
        $update['status'] = self::VERIFY_WAITING;
        $res = M('register_trademark','tb_')->where("id = $id")->save($update);
        if($res > 0){
            $content = ">**待操作事项** 
{$_SESSION['m_loginname']}发起了新增注册商标申请，请知悉
> 发起人：<font color=info >created_by</font>
> 申请发起时间：<font color=info >created_at</font>
>请尽快处理。如需查看详情，请点击： [查看详情](detail_url)";

            $tab_data = [
                'url' => urldecode('/index.php?' . http_build_query(['m' => 'legal', 'a' => 'registered_trademark'])),
                'name' => '注册商品'
            ];
            $replace_data = [
                'created_by' => $update['updated_by'],
                'created_at' => $update['updated_at'],
                'detail_url' => ERP_HOST . '/index.php?' . http_build_query(['tab_data' => $tab_data]),
            ];
            $this->sendMessage(self::VERIFY_ROLE_ID,'ROLE_ID', $content, $replace_data);
        }
        return $res;
    }


    //注册申请审批通过或者驳回和取消
    public function changeStatus($params, $register_info){
        $status =  $params['status'];
        $update['status'] = $params['status'];
        $register_model = M('register_trademark','tb_');
        if($params['status'] != self::VERIFY_CANCEL){
            $id_arr = array_column($register_info, 'id');
        }else{
            $id_arr = $register_info;
        }
        $success = [];
        foreach ($id_arr as $ik=>$iv){
            $res = $register_model->where("id = $iv")->save($update);
            if($res > 0) {
                $success[] = $iv;
            }
        }
        return $success;
    }





    //同名商标名称检测
    public function checkSameName($name){
        $res = M('register_trademark','tb_')->where("trademark_name = '{$name}'")->find();
        return $res;
    }


    //某个状态的触发判断【用于新建商标和修改商标的判断】
    public function canOperate($id, $status){
        $res = M('register_trademark','tb_')->where("id = $id and status = $status")->find();
        return $res;
    }

    //判断是否能使用商标
    public function canUseTrademarkOperate($id, $status){
        $res = M('trademark_use','tb_')->where("id = $id and status = $status")->find();
        return $res;
    }


    //全部都是待审批的才可以审批通过
    public function checkAllStatus($params, $status){
        $info = M('register_trademark','tb_')->where(['id'=>['in',$params['ids']]])->select();
        $register_info = [];
        foreach ($info as $k=>$v){
            if($v['status'] != self::VERIFY_WAITING){
                return $v;
            }
            $register_info[$v['id']] = $v;
        }
        return ['register_info'=>$register_info];
    }


    //图片参数处理
    public function formatImage($image_urls){
        $image_arr = [];
        foreach($image_urls as $ik=>$iv){
            $image_arr[$ik]['save_name'] = $iv['save_name'];
            $image_arr[$ik]['original_name'] = $iv['original_name'];
            $image_arr[$ik]['save_path'] = $iv['save_path'];
        }
        return json_encode($image_arr);
    }


    //ODM商标基础表数据处理
    public function makeBaseData($trademark_base){
        $base_data['trademark_name'] = $trademark_base['trademark_name'];  //商标名字
        $base_data['protect_period'] = $trademark_base['protect_period'];  //商标保护期限
        $base_data['register_apply_no'] = $trademark_base['register_apply_no'];  //关联的注册单号
        $base_data['img_url']        = json_encode($trademark_base['img_url'],JSON_UNESCAPED_UNICODE); //图片url
        $base_data['trademark_type'] = $trademark_base['trademark_type']; //商标类型
        $base_data['updated_by'] = userName();
        $base_data['updated_at'] = date("Y-m-d H:i:s",time());
        return $base_data;
    }



    //ODM商标详情表数据处理
    public function makeDetailData($trademark_detail) {
        $detail_data['country_code'] = $trademark_detail['country_code'];  //国家
        $detail_data['company_code'] = $trademark_detail['company_code'];  //公司
        $detail_data['register_code'] = $trademark_detail['register_code'];  //公司
        $detail_data['current_state'] = $trademark_detail['current_state'];  //注册状态
        $detail_data['apply_code'] = $trademark_detail['apply_code'];  //公司
        $detail_data['initial_review_date'] = $trademark_detail['initial_review_date'];  //公司
        $detail_data['applied_date'] = $trademark_detail['applied_date'];  //公司
        $detail_data['current_state'] = $trademark_detail['current_state'];  //注册状态
        $detail_data['international_type'] = $trademark_detail['international_type'];  //国际分类
        $detail_data['apply_currency'] = $trademark_detail['apply_currency'];  //申请币种
        $detail_data['apply_price'] = $trademark_detail['apply_price'];  //申请价格
        $detail_data['apply_period'] = $trademark_detail['apply_period'];  //申请周期
        $detail_data['goods'] = $trademark_detail['goods'];  //商品或服务
        $detail_data['effective_start'] = $trademark_detail['effective_start'];  //生效开始时间
        $detail_data['effective_end'] = $trademark_detail['effective_end'];  //生效结束时间
        $detail_data['updated_by'] = userName();
        $detail_data['updated_at'] = date("Y-m-d H:i:s",time());
        $detail_data['agent'] = $trademark_detail['agent'];
        $detail_data['remark'] = $trademark_detail['remark'];
        $detail_data['register_date'] = $trademark_detail['register_date'];
        return $detail_data;
    }


    //ODM列表的批量删除
    public function deleteODMData($ids){
        $update['is_delete_state'] = 1;
        $res = M('trademark_base','tb_')->where("id in ($ids)")->save($update);
        return $res;
    }


    //该商标是否能使用的判断
    public function canUse($id, $status){
        $res = M('register_trademark_detail','tb_')->where("trademark_base_id = $id and current_state = $status")->find();
        return $res;
    }


    //使用商标新建
    public function addTrademarkUse($params){
        $insert['trademark_no'] = $params['trademark_no'];
        $insert['use_type'] = $params['use_type'];
        $insert['status'] = self::VERIFY_WAITING;
        $insert['created_at'] = date("Y-m-d H:i:s", time());
        $insert['updated_at'] = date("Y-m-d H:i:s", time());
        $insert['created_by'] = $_SESSION['m_loginname'];
        $insert['updated_by'] = $_SESSION['m_loginname'];
        $insert['use_no'] ='US' . date('Ymd') . TbWmsNmIncrementModel::generateNo('US');//生成流水号
        $res = M('trademark_use','tb_')->add($insert);
        $id = M('trademark_use','tb_')->getLastInsID();
        if($res > 0){
            //发消息
            $content = ">**待操作事项** 
{$_SESSION['m_loginname']}发起了商标使用申请，请知悉
> 发起人：<font color=info >created_by</font>
> 申请发起时间：<font color=info >created_at</font>
>请尽快处理。如需查看详情，请点击： [查看详情](detail_url)";

            $tab_data = [
                'url' => urldecode('/index.php?' . http_build_query(['m' => 'legal', 'a' => 'use_trademark'])),
                'name' => '使用商标'
            ];
            $replace_data = [
                'created_by' => $insert['created_by'],
                'created_at' => $insert['created_at'],
                'detail_url' => ERP_HOST . '/index.php?' . http_build_query(['tab_data' => $tab_data]),
            ];
            $this->sendMessage(self::VERIFY_ROLE_ID,'ROLE_ID', $content, $replace_data);
        }
        return $res;
    }


    //使用商标编辑
    public function editTrademarkUse($params){
        $id = $params['id'];
        $update['use_type'] = $params['use_type'];
        $update['status'] = self::VERIFY_WAITING;
        $update['updated_at'] = date("Y-m-d H:i:s", time());
        $update['updated_by'] = $_SESSION['m_loginname'];
        $res = M('trademark_use','tb_')->where("id = $id")->save($update);
        if($res > 0){
            //发消息
            $content = ">**待操作事项** 
{$_SESSION['m_loginname']}发起了商标使用申请，请知悉
> 发起人：<font color=info >created_by</font>
> 申请发起时间：<font color=info >created_at</font>
>请尽快处理。如需查看详情，请点击： [查看详情](detail_url)";

            $tab_data = [
                'url' => urldecode('/index.php?' . http_build_query(['m' => 'legal', 'a' => 'use_trademark'])),
                'name' => '使用列表'
            ];
            $replace_data = [
                'created_by' => $update['created_by'],
                'created_at' => $update['created_at'],
                'detail_url' => ERP_HOST . '/index.php?' . http_build_query(['tab_data' => $tab_data]),
            ];
            $this->sendMessage(self::VERIFY_ROLE_ID,'ROLE_ID', $content, $replace_data);
        }
        return $res;
    }



    //全部都是待审批的才可以审批通过
    public function checkAllUseStatus($params, $status){
        $info = M('trademark_use','tb_')->where(['id'=>['in',$params['ids']]])->select();
        $use_info = [];
        if($status!= self::VERIFY_WAITING && $status!= self::VERIFY_CANCEL){
            foreach ($info as $k=>$v){
                if($v['status'] != self::VERIFY_WAITING){
                    return $v;
                }
                $use_info[$v['id']] = $v;
            }
        }
        return ['use_info'=>$use_info];
    }


    //使用商标申请的审批通过和驳回
    public function changeAllUseStatus($params){
        $update['status'] = $params['status'];
        $res = M('trademark_use','tb_')->where("id in ({$params['ids']})")->save($update);
        return $res;
    }


    //单个的使用申请记录
    public function getTradeMarkUseData($params) {
        $id = $params['id'];
        $res = M('trademark_use','tb_')->where("id = $id")->find();
        return $res;
    }

    public function useTrademarkList($params){
        $page_size = $params['page_size'] ? $params['page_size'] : 20;
        $page = $params['page'] ? $params['page'] : 1;
        $where['a.status'] = ['in',[1,2,3]];
        if(!empty($params['country_code'])){
            $where['c.country_code'] = $params['country_code'];
        }
        if(!empty($params['trademark_name'])){
            $where['b.trademark_name'] = trim($params['trademark_name']);
        }
        if(!empty($params['international_type'])){
            $where['c.international_type'] = trim($params['international_type']);
        }
        if(!empty($params['status'])){
            $where['a.status'] = ['in',$params['status']];
        }
        if(!empty($params['created_by'])){
            $where['a.created_by'] = trim($params['created_by']);
        }

        if(!empty($params['id'])){
            $where['a.id'] = trim($params['id']);
        }

        $use_model = M('trademark_use','tb_');
        $count = $use_model->alias('a')
            ->join('left join tb_trademark_base b on a.trademark_no = b.id')
            ->join('left join tb_trademark_detail c on b.id = c.trademark_base_id')
            ->join('left join tb_ms_user_area d on c.country_code = d.id')
            ->where($where)->field('a.*,b.trademark_code,b.trademark_name,c.international_type,c.country_code,d.NAME')->count();

        $start = ($page-1) * $page_size;
        $list =  $use_model->alias('a')
            ->join('left join tb_trademark_base b on a.trademark_no = b.trademark_no')
            ->join('left join tb_trademark_detail c on b.id = c.trademark_base_id')
            ->join('left join tb_ms_user_area d on c.country_code = d.id')
            ->where($where)->field('a.*,b.trademark_code,b.trademark_name,c.international_type,c.country_code,d.zh_name as country_name')->order('a.id desc' )->limit($start, $page_size)->select();


        foreach($list as $k=>$v){
            $list[$k]['use_type_name'] = self::$use_type[$v['use_type']];
            $list[$k]['status_name'] = self::$register_status[$v['status']];
        }
        return ['list'=>$list, 'total'=>$count];
    }


    //新增使用记录
    public function useRecordAdd($params){
        $info = $params['info'];
        //更新之前先查出所有的现存记录id
        $model =  M('trademark_record','tb_');
        $old_record_ids =  $model->field('id')->where(['trademark_no'=>$params['trademark_no']])->select();
        $old_record_ids = array_column($old_record_ids,'id');
        //传过来的id，即是更新的数据，排除，剩下的需要删除他
        $update_ids  = array_column($info,'id');
        $delete_ids = array_values(array_diff($old_record_ids, $update_ids));
        $model->where(['id'=>['in',$delete_ids]])->delete();
        foreach($info as $ik=>$iv){
            $use_type = $iv['use_type'];
            $operate_info['use_type'] = $iv['use_type'];
            $operate_info['use_no'] = $iv['use_no'];
            $operate_info['user_name'] = $iv['user_name'];
            $operate_info['contract_code'] = $iv['contract_code'];
            $operate_info['use_type'] = $iv['use_type'];
            $operate_info['trademark_no'] = $params['trademark_no'];
            $operate_info['use_evidence_file'] = json_encode($iv['use_evidence_file'], JSON_UNESCAPED_UNICODE);
            if($use_type == 1){
                $this->doOperate($iv, $operate_info);
            }
            if($use_type == 2){
                $operate_info['shop_name'] = $iv['shop_name'];
                $this->doOperate($iv, $operate_info);
            }
            if($use_type == 3){
                $operate_info['authorize_range'] = $iv['authorize_range'];
                $operate_info['authorize_period_start'] = $iv['authorize_period_start'];
                $operate_info['authorize_period_end'] = $iv['authorize_period_end'];
                $operate_info['authorize_body'] = $iv['authorize_body'];
                $operate_info['related_suppliers'] = $iv['related_suppliers'];
                $this->doOperate($iv, $operate_info);
            }
        }

        return true;
    }


    //具体更新还是新增
    public function doOperate($item, $operate_info){
        $model = M('trademark_record','tb_');
        if(isset($item['id'])){
            //更新
            $operate_info['updated_by'] = userName();
            $operate_info['updated_at'] = date("Y-m-d H:i:s");
            $model->where("id = {$item['id']}")->save($operate_info);
        }else{
            //插入
            $operate_info['created_by'] = userName();
            $operate_info['created_at'] = date("Y-m-d H:i:s");
            $operate_info['updated_by'] = userName();
            $operate_info['updated_at'] = date("Y-m-d H:i:s");
            $model->add($operate_info);
        }
    }


    //获取一个odm的所有记录
    public function getAllUseRecord($params){
        $all_record = M('trademark_record','tb_')->where(['trademark_no'=>$params['trademark_no']])->select();
        $search_ids = "";
        foreach ($all_record as $k=>$v){
            $all_record[$k]['use_evidence_file'] = json_decode($v['use_evidence_file'], true);
            $supplier_ids[$v['id']] = explode(',', $v['related_suppliers']);
            $search_ids .=  ','.$v['related_suppliers'];
        }
        $search_ids = explode(',', trim($search_ids,','));
        $all_suppliers_info = M('crm_sp_supplier','tb_')->field('ID as id,SP_NAME as supplier_name')->where(['ID'=>['in', $search_ids]])->select();
        foreach($all_record as $ak=>$av){
            foreach ($all_suppliers_info as $sk=>$sv){
                if(in_array($sv['id'], $supplier_ids[$av['id']])){
                    $all_record[$ak]['supplier_info'][] = $sv;
                }
            }
        }
        return $all_record;
    }


    //获取供应商对应的商标使用记录
    public function getSupplierUseRecord($params){
        $id = $params['id'];
        $sql = "select trademark_no,contract_code from tb_trademark_record where find_in_set($id, related_suppliers)";
        $records = M()->query($sql);
        return $records;
    }



    public function getAUthFile($params){
        $word = new MyPhpWordModel();
        $word->exportWord($params);
    }


    public function getTrademarkInfo($params){
        $base_model = M('trademark_base','tb_');
        $field = 'a.trademark_type,a.trademark_no,a.trademark_name,b.international_type,b.international_type,
                  b.goods,b.country_code,b.register_code,c.zh_name as country_name,b.current_state,
                  d.CD_VAL as state_name,e.CD_VAL as trademark_type_name,f.CD_VAL as company_name';
        $trademark_nos = explode(',',$params['trademark_no']);
        $where['a.trademark_no'] = ['in', $trademark_nos];
        $info = $base_model->alias('a')
            ->join('left join tb_trademark_detail b on a.id = b.trademark_base_id')
            ->join('left join tb_ms_user_area c on b.country_code = c.id')
            ->join('left join tb_ms_cmn_cd d on b.current_state = d.CD')
            ->join('left join tb_ms_cmn_cd f on b.company_code = f.CD')
            ->join('left join tb_ms_cmn_cd e on a.trademark_type = e.CD')
            ->where($where)
            ->field($field)->select();
        return $info;
    }


    //合同编号查找
    public function getContractNo($trademark_no){
        $sql = "select CON_NO from tb_crm_contract where find_in_set('$trademark_no', trademark_no)";
        $records = M()->query($sql);
        $contract = implode(array_column($records,'CON_NO'),',');
        return $contract;
    }


    //需要发消息的人
    public function sendMessage($user_id, $column, $content, $replace_data){
        if($column = 'Role_ID'){
            $wx_ids = M()->table('bbm_admin_role a')
                ->field('c.wid')
                ->join('left join bbm_admin b on a.M_ID = b.M_ID')
                ->join('left join tb_hr_empl_wx c on b.empl_id = c.uid')
                ->where(["a.$column" => $user_id])
                ->select();
        }else{
            $wx_ids = M()->table('bbm_admin a')
                ->field('b.wid')
                ->join('left join tb_hr_empl_wx b on a.empl_id = b.uid')
                ->where(["a.$column" => $user_id])
                ->select();
        }

        $wx_ids = trim(implode(array_column($wx_ids, 'wid'), '|'),'|');
        $data = (new InventoryNotifyService())->replace_template_var($content, $replace_data);
        ApiModel::WorkWxSendMarkdownMessage($wx_ids, $data);
    }


    public function isRealSupplier($id){
        $supplier = M('crm_sp_supplier','tb_')->field('ID as id,SP_NAME as supplier_name')->where('ID = '.$id)->find();
        return $supplier;
    }


    //查看是否有提交过商标信息，有则需要提示不能再提交商标信息了
    public function existOdm($id){
        $exist_odm = true;
        $has_trademark_no =  M('register_trademark','tb_')->field('related_trademark_no')->where(['id'=>$id])->find();
        if(!empty($has_trademark_no['related_trademark_no'])){
            $exist_odm = false;
        }
        return $exist_odm;
    }


}