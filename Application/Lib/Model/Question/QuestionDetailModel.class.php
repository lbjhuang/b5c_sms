<?php

/**
 * 反馈问题管理
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/7/25
 * Time: 17:32
 */
class QuestionDetailModel extends RelationModel
{
    protected $trueTableName = "tb_ms_question_detail";


    /**
     * 获取详情列表
     * @param $id
     * @return mixed
     */
    public function getQuestionDetailList($id)
    {
        $where['question_id'] = $id;
        return $this->where($where)->select();
    }

    /**
     * 获取详情测通过状态
     * @param $id
     * @return mixed
     */
    public function getQuestionDetailByStatus($id, $status, $field = 'status,question_desc')
    {
        $where['question_id'] = $id;
        $where = !empty($status) ? array_merge($where,['status'=>$status]) : $where;
        return $this->field($field)->where($where)->select();
    }

    /**
     * 保存问题详情数据
     * @param $params
     * @return mixed
     */
    public function saveQuestionDetailData($params)
    {
        $data['question_id'] = $params['questionId'];
        $data['opt_user_id'] = $params['optUserId'];
        $data['opt_user_name'] = $params['optUserName'];
        $data['status'] = $params['status'];
        $data['question_desc'] = $params['questionDesc'];
        $data['add_time'] = $params['add_time'];
        $data['img_json'] = $params['img_json'];
        $data['demo_remark'] = $params['demo_remark'];
        return $this->add($data);
    }

    public function updateQuestionDetailData($params)
    {
        $data = [];
        $this->save($data);
    }

}