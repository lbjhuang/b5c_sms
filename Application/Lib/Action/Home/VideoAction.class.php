<?php

/**
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/11/9
 * Time: 15:05
 */
class VideoAction extends BaseAction
{

    public function index()
    {
        $this->display('videoList');
    }

    public function videoList()
    {
        $params = $this->getParams();
        $ret = VideoModel::listData($params);
        $this->ajaxReturn($ret);
    }

    public function videoDetails()
    {
        $params = $this->getParams();
        $ret = VideoModel::getDetail($params);
        $res = DataModel::$success_return;
        $res['data'] = $ret;
        if (!$ret) {
            $res = DataModel::$error_return;
        } 
        $this->ajaxReturn($res);

    }

    public function videoScoreSubmit()
    {
        $params = $this->getParams();
        try {
            $this->checkData($params);
            $res = DataModel::$success_return;
            $model = new Model();
            $model->startTrans();
            $result = (new VideoService())->videoScoreSubmit($params, $model);
            if (false === $result) {
                $model->rollback();
            }
            $model->commit();
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function checkData($params)
    {
        if (!$params['score']) {
            throw new \Exception('缺失分数参数~');
        }
        if (!$params['remark']) {
            throw new \Exception('缺失评价参数~');
        }
        if (!DataModel::userId()) {
            throw new \Exception('缺失用户参数，请重新登录~');
        }
        if (!in_array($params['score'], [1,2,3,4,5])) {
            throw new \Exception('分数值不在允许值范围内，请确认~');
        }
        return false;
    }

    public function deleteVideo()
    {
        $params = $this->getParams();
        $ret = VideoModel::deleteVideo($params);
        $this->ajaxReturn($ret);
    }

    public function upload()
    {
        $params = $this->getParams();
        $ret = VideoModel::upload($params);
        $this->ajaxReturn($ret);
    }

}