<?php
/**
 * Created by PhpStorm.
 * User: due
 * Date: 2019/3/28
 * Time: 13:43
 */



class VideoModel
{
    const SAVE_PATH = '/opt/b5c-disk/mv/';

    public static function deleteVideo($params)
    {
        $data = ['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s'), 'updated_by' => $_SESSION['m_loginname'], 'deleted_by' => $_SESSION['m_loginname']];
        $ret = M('video', 'tb_sys_')->where(['id' => $params['id']])->save($data);
        if ($ret) {
            $saveName = M('video', 'tb_sys_')->where(['id' => $params['id']])->getField('save_name');
            unlink(self::SAVE_PATH . $saveName);
            return ['code' => 2000, 'msg' => 'success', 'data' => ''];
        } else {
            return ['code' => 3000, 'msg' => L(M()->getError()), 'data' => ''];
        }
    }

    public static function listData($params)
    {
        $list = M('video', 'tb_sys_')->where(['is_deleted' => 0])
            ->order('id desc')
            ->limit($params['page_size'])
            ->page($params['page'])
            ->select();
        $total = M('video', 'tb_sys_')->where(['is_deleted' => 0])->count();
        return ['code' => 2000, 'msg' => 'success', 'data' => ['list' => $list, 'total' => $total]];
    }

    public static function getBaseInfo($params)
    {
        return M('video', 'tb_sys_')->where(['is_deleted' => 0, 'id' => $params['video_id']])->find();
    }

    public function getDetail($params)
    {
        $user_id = DataModel::userId();
        if (!$params['video_id'] || !$user_id) {
            return false;
        }
        $list = []; $scoreModel = M('video_score', 'tb_sys_');
        // 基本信息
        $list['base_info'] = self::getBaseInfo($params);
        if ($list['base_info'] && $list['base_info']['id']) {
            // 该视频的所有评分信息
            $list['all_score_info'] = $scoreModel->where(['video_id' => $params['video_id']])->select();
            // 我的评分信息
            $list['mine_score_info'] = [];
            foreach ($list['all_score_info'] as $key => $value) {
                if ($value['user_id'] == $user_id) {
                    $list['mine_score_info'] = $value;
                }
            }
        }
        
        return $list;
    }

    public static function upload($params)
    {
        $code = 3000;
        $msg = 'error';
        import('ORG.Net.UploadFile');
        $upload = new UploadFile();             // 实例化上传类
        $upload->maxSize    = 600 << 20;          // 设置附件上传大小
        $upload->allowExts  = ['mp4', 'mov', 'wmv', 'mkv', 'rmvb', 'wma', 'rm', 'avi', 'flv', 'mpeg', 'wms'];  // 设置附件上传类型
        $upload->savePath   = self::SAVE_PATH;  // 设置附件上传目录
        if (!$upload->upload()) {
            $msg = L('文件上传失败') . "：" .$upload->getErrorMsg();
        } else {
            $fileInfo = $upload->getUploadFileInfo()[0];
            $name = substr($fileInfo['name'], 0, strlen($fileInfo['name']) - strlen($fileInfo['extension']) - 1);
            $url = 'http://qn.izenecdn.com/mv/' . $fileInfo['savename'];
            $data = ['name' => $name, 'url' => $url, 'created_by' => $_SESSION['m_loginname'], 'updated_by' => $_SESSION['m_loginname'], 'save_name' => $fileInfo['savename']];
            $ret = M('video', 'tb_sys_')->add($data);
            if ($ret) {
                $code = 2000;
                $msg = 'success';
            } else {
                $msg = '保存失败';
            }
        }
        return ['code' => $code, 'msg' => L($msg), 'data' => ['']];
    }

}