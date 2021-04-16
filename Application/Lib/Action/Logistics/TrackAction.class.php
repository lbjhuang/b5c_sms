<?php
/**
 * Created by PhpStorm.
 * User: afanti
 * Date: 2017/10/13
 * Time: 09:40
 */
class TrackAction extends BaseAction{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 物流接口日志列表页页面配置
     */
    public function track_list()
    {
        $this->display();
    }

    /**
     * 物流信息详情页面配置
     */
    public function track_detail()
    {
        $id = $_REQUEST['Id'];
        if ($id) {
            $this->assign('id',$id);
        }
        $this->display();
    }
   }