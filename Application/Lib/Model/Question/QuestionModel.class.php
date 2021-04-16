<?php

/**
 * 反馈问题管理
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/7/25
 * Time: 17:32
 */
class QuestionModel extends RelationModel
{
    protected $trueTableName = "tb_ms_question";
    protected $questionDetailTableName = "tb_ms_question_detail";
    protected $nodeTableName = "bbm_node";
    protected $roleTableName = "bbm_role";
    protected $adminTableName = "bbm_admin";

    /**
     * 获取问题列表
     * @param $params
     * @param $type
     * @param $offset
     * @param $limit
     * @param array $order
     * @return mixed
     */
    public function getQuestionListByPage($params, $type, $offset, $limit, $order = array('id' => 'DESC'))
    {
        $where = array();
        $where['is_delete'] = '0';
        //id模糊查询
        if (!empty($params['id'])) {
            $where['id'] = $params['id'];
        }
        //module_name模糊查询
        if (!empty($params['title'])) {
            $where['title'] = ['like', '%' . $params['title'] . '%'];
        }
        if (!empty($params['status'])) {
            $where['status'] = $params['status'];
        }
        if (!empty($params['module'])) {
            $where['module_name'] = $params['module'];
        }
        if (!empty($params['userName'])) { // 发起人
            $QuestionService = new QuestionService();
            $params_map['field'] = 'question_user_id';
            $params_map['handler_id'] = $params['userName'];
            $where['_string'] = $QuestionService->getFindInSetString($params_map);
        }
        if (!empty($params['handler'])) { // 当前应处理人
            $QuestionService = new QuestionService();
            $params_map['field'] = 'opt_user_id';
            $params_map['handler_id'] = $params['handler'];
            if ($where['_string']) {
                $logistic = ' AND '; 
            }
            $where['_string'] .= $logistic . $QuestionService->getFindInSetString($params_map);
            // p($where);die;
        }
        //p($params['hist_handler']);
        if (!empty($params['hist_handler'])) { // 历史应处理人 根据id到log中获取对应的工单id数组
            $QuestionLogModel = new QuestionLogModel();
            $params_map['field'] = 'deal_user_id';
            $params_map['handler_id'] = $params['hist_handler'];
            $id_res = $QuestionLogModel->getIdsByDealId($params_map);
            if ($id_res && count($id_res) != 0) {
                if (!isset($where['id']) || empty($where['id'])){
                    $where['id'] = array('in', $id_res);
                }
            } else {
                if ($type == 'count') {
                    return '0';
                }
                return false;
            }
        }

        //更新日期
        $dbTimeField = 'add_time';
        if (!empty($params['startTime'])) {
            $where[$dbTimeField][] = array('EGT', strtotime($params['startTime']." 00:00:00"));
        }
        if (!empty($params['endTime'])) {
            $where[$dbTimeField][] = array('ELT', strtotime($params['endTime']." 23:59:59"));
        }
        $field = '*';
        if ($type == 'count') {
            return $this->where($where)->count();
        }
        $subQuery = $this->field($field)
            ->where($where)
            ->order($order)
            ->limit($offset, $limit)
            ->buildSql();
        $result = $this->query($subQuery);
        //echo M()->_sql();die;
        return $result;
    }

    /**
     * 获取问题列表所有数据
     * @param $params
     * @param $type
     * @param $offset
     * @param $limit
     * @param array $order
     * @return array
     */
    public function getQuestionList($params, $type, $offset, $limit, $order = array('add_time' => 'DESC'))
    {
        $where = array();
        $where['is_delete'] = '0';
        if (!empty($params['status'])) {
            $where['status'] = $params['status'];
        }

        if (!empty($params['module'])) {
            $where['module_name'] = $params['module'];
        }
        if (!empty($params['userName'])) { // 发起人
            $QuestionService = new QuestionService();
            $params_map['field'] = 'question_user_id';
            $params_map['handler_id'] = $params['userName'];
            $where['_string'] = $QuestionService->getFindInSetString($params_map);
        }
        if (!empty($params['handler'])) { // 当前应处理人
            $QuestionService = new QuestionService();
            $params_map['field'] = 'opt_user_id';
            $params_map['handler_id'] = $params['handler'];
            if ($where['_string']) {
                $logistic = ' AND '; 
            }
            $where['_string'] .= $logistic . $QuestionService->getFindInSetString($params_map);
        }
        if (!empty($params['hist_handler'])) { // 历史应处理人 根据id到log中获取对应的工单id数组
            $QuestionLogModel = new QuestionLogModel();
            $params_map['field'] = 'deal_user_id';
            $params_map['handler_id'] = $params['hist_handler'];
            $id_res = $QuestionLogModel->getIdsByDealId($params_map);
            if (count($id_res) != 0) {
                $where['id'] = array('in', $id_res);
            }
        }

        //更新日期
        $dbTimeField = 'add_time';
        if (!empty($params['startTime'])) {
            $where[$dbTimeField][] = array('EGT', strtotime($params['startTime']." 00:00:00"));
        }
        if (!empty($params['endTime'])) {
            $where[$dbTimeField][] = array('ELT', strtotime($params['endTime']." 23:59:59"));
        }
        $field = '*';
        $subQuery = $this->field($field)
            ->where($where)
            ->order($order)
            ->buildSql();
        $result = $this->query($subQuery);
        return $result;
    }

    /**
     * add data
     * @param $params
     * @return mixed
     */
    public function saveQuestionData($params)
    {
        $data['title'] = $params['title'];
        $data['desc'] = $params['desc'];
        $data['type'] = $params['type'];
        $data['validity'] = $params['validity'];
        $data['project_type'] = $params['projectType'];
        $data['project_no'] = $params['projectNo'];
        $data['module_name'] = $params['moduleName'];
        $data['file_name'] = $params['fileName'];
        $data['page_url'] = $params['pageUrl'];
        $data['status'] = $params['status'];
        $data['question_user_id'] = $_SESSION['user_id'];
        $data['question_user_name'] = $_SESSION['m_loginname'];
        $data['question_email'] = $params['questionEmail'];
        $data['opt_user_id'] = $params['opt_user_id'];
        $data['opt_user_name'] = $params['opt_user_name'];
        $data['add_time'] = $params['time'];
        $data['opt_time'] = 0;
        $data['finish_time'] = 0;
        return $this->add($data);
    }

    /**
     * update data
     * @param $params
     * @return mixed
     */
    public function updatQuestionData($params)
    {
        $where['id'] = $params['id'];
        !empty($params['pageUrl']) && $data['page_url'] = $params['pageUrl'];
        !empty($params['status']) && $data['status'] = $params['status'];
        !empty($params['question_user_id']) && $data['question_user_id'] = $params['question_user_id'];
        !empty($params['question_user_name']) && $data['question_user_name'] = $params['question_user_name'];
        !empty($params['project_no']) && $data['project_no'] = $params['project_no'];
        !empty($params['project_type']) && $data['project_type'] = $params['project_type'];
        !empty($params['validity']) && $data['validity'] = $params['validity'];
        !empty($params['opt_time']) && $data['opt_time'] = $params['opt_time'];
        !empty($params['finish_time']) && $data['finish_time'] = $params['finish_time'];

        $data['opt_user_id'] = $params['opt_user_id']; // 允许为空 当问题关闭时，清空当前处理人
        $data['opt_user_name'] = $params['opt_user_name'];
        return $this->where($where)->save($data);
    }

    /**
     * 获取模块信息
     * @return mixed
     */
    public function getNodeList()
    {
        $where[]['LEVEL'] = array('eq', '1');
        $where[]['PID'] = array('eq', '0');
        $res = $this->table($this->nodeTableName)->where($where)->getField('ID, NAME');
        return $res;
        /*$where[]['LEVEL'] = 2;
        $where[]['TITLE'] = array('not in', '日志管理,系统权限,null,在线反馈');
        $sql = $this->table($this->nodeTableName)->field('ID')->where('LEVEL = 1')->buildSql();
        $where[]['PID'] = ['exp', 'IN ' . $sql];
        return $this->table($this->nodeTableName)->field('TITLE,CTL,PID')->where($where)->group('PID')->select();*/
    }

    /**
     * 获取在线反馈对应的ID
     * @param string $name
     * @return mixed
     */
    public function getNodeId($ctl = 'Question',$act = 'detail')
    {
        $where['CTL'] = $ctl;
        $where['ACT'] = $act;
        return M('node','bbm_')->field('ID')->where($where)->find();
    }
    /**
     * 获取权限ID通过节点ID
     * @param string $name
     * @return mixed
     */
    public function getQuestionRights($roleId)
    {
        $where['ROLE_ID'] = is_array($roleId) ? ['in', $roleId] : $roleId;
        return M('role','bbm_')->field('ROLE_ACTLIST')->where($where)->find();
    }

    /**
     * 获取在线反馈对应的ID
     * @param string $name
     * @return mixed
     */
    public function getQuestionDealWithUser($rightIds = [])
    {
        $where[]['ROLE_ID'] = ['exp', "in ('" . implode(',', $rightIds) . "')"];
        return $this->table($this->adminTableName)->field('M_ID as id,M_NAME as name')->where($where)->select();
    }
    public function getAdminList()
    {
        return $this->table($this->adminTableName)->field('M_ID as id,M_NAME as name')->select();
    }


    /**
     * 获取工单详情
     * @param $id
     * @return mixed
     */
    public function getQuestionDetail($id,$userId,$field = '*')
    {
        $where['id'] = $id;
        if(!empty($userId)){$where['question_user_id'] = $userId;}
        return $this->field($field)->where($where)->find();

    }
}