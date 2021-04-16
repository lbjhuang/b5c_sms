<?php

class RealLgtCompanyService extends Service
{

    public $user_name;
    public $model;
    public $area_table;

    public function __construct()
    {
        $this->user_name = DataModel::userNamePinyin();
        $this->model = new Model();
        $this->area_table = M('lgt_real_company', 'tb_');
        $this->area_table2 = M('lgt_real_company_details', 'tb_');
    }

    // 获取有详情信息的实际公司名称
    public function getComNameKeyValue()
    {
        // 只获取目前展示含详情的公司名称
        $query = $this->area_table
            ->alias('rc')
            ->field("rc.logistics_name,rc.rc_id")
            ->join('right join tb_lgt_real_company_details as rcd on rcd.rc_id = rc.rc_id')
            ->select();

        $real_query = [];
        if ($query) {
            // 去重
            $query = array_unique($query, SORT_REGULAR);
            $rc_id_arr = array_column($query, 'rc_id');
            $logistics_name_arr = array_column($query, 'logistics_name');
            $real_query = array_combine($rc_id_arr, $logistics_name_arr);
        }

        return $real_query;

    }

    public function saveLgtRealCompany($request_data) {
        #将去除字符中全部的空格改为去除两侧空格
        $request_data['logistics_name'] = trim($request_data['logistics_name']);
        $result = $this->isUniqueRealLgtCompany($request_data);
        if (200 != $result['code']) {
            throw new \Exception(L($result['msg']));
        }
        if ($request_data['lgt_track_platform_cd'] !== 'N002850003') { // (方便JAVA获取)除伙伴数据外，其他物流轨迹平台，简拼直接同步服务代码的值
            $request_data['com_sort_name'] = $request_data['service_code'];
        }
        $this->model->startTrans();
        //主表新增记录
        $rc_id = $result['data'];
        if (!$rc_id) { // 表明该公司名称在表里不存在，需要在主表新增公司
            $add_main_data = [
                'logistics_name' => $request_data['logistics_name'],
                'created_by' => $this->user_name,
            ];

            $rc_id = $this->area_table->add($add_main_data);
            if (!$rc_id) {
                Logs(json_encode($add_main_data), __FUNCTION__.' fail', __CLASS__);
                $this->model->rollback();
                throw new \Exception(L('实际物流公司主表新增失败'));
            }
        }

        if ($request_data['rcd_id']) { // 编辑
            $save_data = [
                'rcd_id' => $request_data['rcd_id'],
                'rc_id' => $rc_id,
                'com_en_name' => $request_data['com_en_name'],
                'service_code' => $request_data['service_code'],
                'lgt_track_platform_cd' => $request_data['lgt_track_platform_cd'],
                'level' => $request_data['level'],
                'com_sort_name' => $request_data['com_sort_name'],
                'check_logistics' => $request_data['check_logistics'],
                'logistics_cd' => $request_data['logistics_cd'],
                'store_cd' => $request_data['store_cd'],
                'optional' => $request_data['optional'],
                'updated_by' => $this->user_name,
            ];
            if (false === $this->area_table2->save($save_data)) {
                Logs(json_encode($save_data), __FUNCTION__.' fail', __CLASS__);
                $this->model->rollback();
                throw new \Exception(L('编辑失败'));
            }
        } else { // 新增
            
            //详情表新增记录
            $add_data = [
                'rc_id' => $rc_id,
                'com_en_name' => $request_data['com_en_name'],
                'service_code' => $request_data['service_code'],
                'lgt_track_platform_cd' => $request_data['lgt_track_platform_cd'],
                'level' => $request_data['level'],
                'com_sort_name' => $request_data['com_sort_name'],
                'check_logistics' => $request_data['check_logistics'],
                'logistics_cd' => $request_data['logistics_cd'],
                'store_cd' => $request_data['store_cd'],
                'optional' => $request_data['optional'],
                'created_by' => $this->user_name,
            ];
            
            if (!$this->area_table2->add($add_data)) {
                Logs(json_encode($add_data), __FUNCTION__.' fail', __CLASS__);
                $this->model->rollback();
                throw new \Exception(L('实际物流公司操作失败'));
            }
        }
        $this->model->commit();
    }

    private function isUniqueRealLgtCompany($data) {

        $whereMap['lgt_track_platform_cd'] = $data['lgt_track_platform_cd'];
        if ($data['lgt_track_platform_cd'] !== 'N002850003') { // 除伙伴数据外，其他物流轨迹平台，服务代码需要校验
            $whereMap['service_code'] = $data['service_code'];
        } else { // 伙伴数据需要校验简拼
            $whereMap['com_sort_name'] = $data['com_sort_name'];
        }

        // 判断服务代码 && 物流轨迹平台CD 是否已经存在
        if ($data['rcd_id']) {
            $whereMap['rcd_id'] = ['neq', $data['rcd_id']];
            $whereMap['rc_id'] = $data['rc_id'];
            unset($whereMap['service_code']);
            unset($whereMap['com_sort_name']);
        }
        $result = $this->area_table2->where($whereMap)->find();

        if ($result) {
            $msg = '该物流轨迹平台已存在此（服务代码或拼音代码），请更换（服务代码或拼音代码）';
            if ($data['rcd_id']) {
                $msg = '该物流公司已经有此物流轨迹平台，无需重复新增';
            }
            return ['code' => 300, 'msg' => $msg];
        }

        // 编辑时，可以修改公司名称
        // 查询公司名称的id
        // 判断实际物流公司是否已存在，根据公司名称去主表查
        $resultSec = $this->area_table->where([
                'logistics_name' => $data['logistics_name'],
        ])->find();        


        if ($data['rcd_id'] && $data['rc_id'] == $resultSec['rc_id'] && !empty($resultSec['rc_id'])) { // 编辑 公司名称没有更改
            $rc_id = $data['rc_id'];
        } else { // 新增 和编辑（更改了公司名称）            
            if ($resultSec) {
                // 根据实际物流公司id 和 物流轨迹平台 查询
                $resultThr = $this->area_table2->where([
                        'rc_id'=>$resultSec['rc_id'],
                        'lgt_track_platform_cd' => $data['lgt_track_platform_cd']
                ])->find();
                if ($resultThr) {
                    // 该公司物流轨迹平台已存在，无法重复新增，请前往编辑
                    return ['code' => 301, 'msg' => '该公司物流轨迹平台已存在，无需重复新增，请前往编辑'];
                }
                $rc_id = $resultSec['rc_id'];
            }
        }

        // 检查判断该优先级是否存在，（根据实际物流公司id 和该优先级数字）
        if ($rc_id) {
            $whereMap = [
                'rc_id' => $resultSec['rc_id'],
                'level' => $data['level']
            ];
            if ($data['rcd_id']) { // 编辑
                $whereMap['rcd_id'] = ['neq', $data['rcd_id']];
            }
            $resultFour = $this->area_table2->where($whereMap)->find();
            if ($resultFour) {
               return ['code' => 302, 'msg' => '该物流公司的优先级数字已经存在，请更换其他数字']; 
            }
        }

        return ['code' => 200, 'msg' => 'success', 'data' => $resultSec['rc_id']];
    }

    // 获取单个实际物流公司的信息
    public function getRealComInfo($request_data) {
        if (empty($request_data['rcd_id'])) {
            throw new \Exception(L('参数错误'));
        }
        return $this->area_table->find($request_data['rcd_id']);
    }

    /**
     * @param $request_data
     * @param bool $is_excel
     * @return array
     */
    public function realCompanyInfoList($request_data) {
        $search_map = [
            'service_code' => 'service_code',
            'logistics_name' => 'logistics_name',
            'com_en_name' => 'com_en_name',
        ];
        $search_type = [];
        $request_data['search'] = $request_data['data'];
        unset($request_data['data']);
        if (!$request_data['pages'] && $request_data['page']) {
            $request_data['pages'] = $request_data['page'];
            unset($request_data['page']);
        }
        $request_data['pages']['per_page'] = $request_data['pages']['page_count'];
        $request_data['pages']['current_page'] = $request_data['pages']['this_page'];
        list($where, $limit) = WhereModel::joinSearchTemp($request_data, $search_map, "", $search_type);
        list($res_db, $pages) = $this->getRealCompanyInfoList($where, $limit);
        return [
            'data' => $res_db,
            'page' => $pages
        ];
    }

    public function getRealCompanyInfoList($where, $limit) {
        $field = "tb_lgt_real_company_details.*, rc.logistics_name, cmn.CD_VAL as lgt_track_platform_name";
        $query = $this->area_table2
            ->field($field)
            ->join('left join tb_lgt_real_company as rc on tb_lgt_real_company_details.rc_id = rc.rc_id')
            ->join('left join tb_ms_cmn_cd as cmn on cmn.CD = tb_lgt_real_company_details.lgt_track_platform_cd')
            ->where($where);
        $query_copy = clone $query;

        $pages['count'] = $query->count();
        $pages['page_count'] = $limit[1] ? $limit[1] : 10;
        $query_copy->limit($limit[0], $limit[1]);

        $db_res = $query_copy->order('updated_at desc')->select();

        return [$db_res, $pages];
    }

    public function deleteRealCompanyInfo($request_data) {
        $this->model->startTrans();
        if (empty($request_data['rcd_id'])) {
            throw new \Exception(L('参数错误'));
        }
        $where = ['rcd_id' => $request_data['rcd_id']];
        $save_data = [
            'deleted_by' => $this->user_name,
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->user_name,
        ];
        if (false === $this->area_table2->where($where)->save($save_data)) {
            $this->model->rollback();
            throw new \Exception('记录删除人失败');
        }
        if (!$this->area_table2->where($where)->delete()) {
            $this->model->rollback();
            throw new \Exception(L('删除实际物流公司信息失败'));
        }
        $this->model->commit();
    }

}