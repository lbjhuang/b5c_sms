<?php

class QuestionLogModel extends RelationModel
{
    protected $trueTableName = "tb_ms_question_log";

    protected $table_sec = 'tb_ms_question_log_detail';

    public function getLogListData($param = [])
    {
        return $this->getActionName(CodeModel::autoCodeTwoVal($this->where($param)->select(), ['deal_status_cd']));
    }

    // 获取截图数据
    public function getLogImgListData($list = [])
    {
        if(!$list) return false;
        foreach ($list as $key => $value) {
            $img = '';
            $where[]['question_log_id'] = $value['id'];
            $img = $this->table($this->table_sec)->where($where)->getField('img');
            $list[$key]['img'] = $img;
            unset($where);
        }
        return $list;
    }



    // 通过应操作人id获取工单id
    public function getIdsByDealId($param = [])
    {
        $QuestionService = new QuestionService();
        $maps['_string'] = $QuestionService->getFindInSetString($param);
        $res = $this->field('question_id')->where($maps)->select();
        if (count($res) != 0) {
            $res = array_column($res, 'question_id');
        }
        return $res;
        
    }



    // 获取补充备注信息（可多条）
    // $need_img 是否需要返回截图
    public function getAddRemark($data = [])
    {
        $temp = [];
        foreach ($data as $key => $value) {
            if ($value['action'] == '3') {
                $temp[$key]['img'] = $value['img'];
                $temp[$key]['remark'] = $value['remark'];
            }
        }
        return $temp;
    }


    // 获取组装后的数据
    public function getActionName($list = [])
    {
        if (count($list) != 0) {
            $QuestionService = new QuestionService();
            foreach ($list as $key => &$value) {
                $tempData = []; $temp = [];
                $value['action_name'] = $QuestionService::$_MODULE_INFO[$value['action']]['name'];
                $tempData['opt_user_ids'] = $value['deal_user_id']; 
                $temp['opt_user_ids'] = $value['user_id']; 
                $value['deal_user_id'] = $this->getAppointUser($tempData, true, true); // 操作人
                $value['user_id'] = $this->getAppointUser($temp, true, true); // 创建人
            }
        }
        return $list;
    }
    
    public function saveLogData($data = [])
    {
        $addData['user_id'] = $_SESSION['user_id'];
        $addData['action'] = $data['type'];
        $addData['deal_user_id'] = $data['deal_user_id'];
        $addData['deal_status_cd'] = $data['deal_status_cd'];
        $addData['question_id'] = $data['id'];
        $addData['status_cd'] = $data['info']['status'];
        $addData['created_by'] = $_SESSION['m_loginname'];
        $addData['created_at'] = dateTime();
        $addData['remark'] = $data['content'];
		$add_res = $this->add($addData);
		if (false === $add_res) {
            $addData['lastsql'] = M()->_sql();
            Logs($addData, __CLASS__, __FUNCTION__);
			throw new Exception("更新操作日志失败");
		}

        if ($data['img']) {
            $detail_data = [];
            $detail_data['question_log_id'] = $add_res;
            $detail_data['img'] = $data['img'];
            $add_detail_res = $this->table($this->table_sec)->add($detail_data);
            if (false === $add_detail_res) {
                $add_detail_res['lastsql'] = M()->_sql();
                Logs($addData, __CLASS__, __FUNCTION__);
                throw new Exception("更新操作日志失败（截图保存失败）");
            }
        }


        return $add_res;
    }

    // 获取全部历史操作处理人（去重，求并集）
    // $include_creator 是否包含工单创建者
    public function getAllHistUser($data = [], $include_creator = false)
    {

        $user_arr = []; $user_send_arr = []; $user_send_str = '';
        // 先获取所有该工单id下的处理人
        $map['question_id&deal_user_id'] =array($data['id'],array('neq',''),'_multi'=>true);
        $user_arr = $this->where($map)->select();
        if ($user_arr) {
            foreach ($user_arr as $key => $value) {
                $user_send_str .= ',' . $value['deal_user_id']; // 拼接
            }
            if ($include_creator) {
                $user_send_str .= ',' . $data['info']['question_user_id'];
            }
            $user_send_arr = explode(',', $user_send_str); // 去重
            $user_send_arr = array_unique(array_filter($user_send_arr));
            $user_send_arr = $this->getOprName($user_send_arr);
        }
        return $user_send_arr;
    }

    // 获取当前处理人格式处理
    // 是否需要返回id对应的名称
    public function getDealUser($user_str = '', $need_name = true)
    {
        $user_name_arr = []; $user = [];
        if (!strstr($user_str, ',')) {
            $user_name_arr[] = $user_str;
        } else {
            $user = explode(',', $user_str);
            $user_name_arr = array_unique(array_filter($user));
        }

        if ($need_name) {
            $user_name_arr = $this->getOprName($user_name_arr);
        }
        return $user_name_arr;
    }

    // 获取指派人员
    public function getAppointUser($data, $need_name, $need_str)
    {
        $user_arr = []; $user_str = '';
        $opt_user_ids = $data['opt_user_ids'];
        /*if ($data['info']['opt_user_id']) { // 指派人员不需要保留原来的应处理人，而是替换即可
            $opt_user_ids = $data['info']['opt_user_id'] . ',' . $data['opt_user_ids'];
        }*/
        $user_arr = $this->getDealUser($opt_user_ids, $need_name);
        if ($need_str) { // 需要转化为字符串
            if (count($user_arr) > 1) { 
                $user_arr = implode(",",$user_arr);
            } else {
                $user_arr = $user_arr[0];
            }
        }
        return $user_arr;
    }

    // 根据id获取操作人名称
    public function getOprName($user = [])
    {
        if (empty($user)) return [];
        $user_name_arr = [];
        $admin_model = D('admin');
        foreach ($user as $key => $value) {
            $name = '';
            $map['M_ID'] = array('eq', $value);
            $name = $admin_model->where($map)->getField('M_NAME');
            if ($name) {
                $user_name_arr[] = $name;
            }
        }
        return $user_name_arr;
    }

    /**
     * 获取详情列表
     * @param $questionIds
     * @return mixed
     */
    public function getQuestionLogByQuestionIds($questionIds)
    {
        $where_str = 'question_id in (' . implode(',', $questionIds) . ') and (remark is not null and remark <> "")';
        return $this->where($where_str)->select();
    }
}