<?php
/**
 * Created by PhpStorm.
 * User: afanti
 * Date: 2018/1/12
 * Time: 10:18
 */
class AppVersionModel extends Model{

    protected $trueTableName = 'tb_ms_app_version';

    /**
     * 添加App版本配置
     * @param array $data 属性数据
     * @param array $options 可选项，扩展。
     * @return bool|mixed
     */
    public function addVersion($data, $options=[])
    {
        if (empty($data))   return  false;

        if (empty($data['name']) || empty($data['system']) || empty($data['version'])){
            return false;
        }

        $this->startTrans();
        $res = $this->query('SELECT MAX(id) as maxId FROM ' . $this->trueTableName . ' FOR UPDATE');
        $newId = $res[0]['maxId'] + 1;
        $save['type'] = addslashes($data['name']);
        $save['system'] = $data['system'];
        $save['version'] = $data['version'];
        !empty($data['platform']) && $save['platform'] = $data['platform'];
        isset($data['is_force']) && $save['is_force'] = strtoupper($data['is_force']);
        !empty($data['download']) && $save['download'] = $data['download'] . '&id=' . $newId;
        !empty($data['suitable']) && $save['suitable'] = $data['suitable'];
        !empty($data['update_desc']) && $save['update_desc'] = addslashes($data['update_desc']);
        !empty($data['fileName']) && $save['file_name'] = addslashes($data['fileName']);

        $time = date('Y-m-d H:i:s', time());
        $save['create_time'] = $time;
        $save['updated_time'] = $time;
        $result = $this->add($save);

        if ($result){
            $this->commit();
            return $result;
        } else {
            $this->rollback();
            return false;
        }
    }

    /**
     * 是否重复存在了。
     * @param $data
     * @return bool
     */
    public function checkExist($data)
    {
        $res = $this->table($this->trueTableName)
            ->where("type='{$data['name']}' AND system='{$data['system']}' and version='{$data['version']}'")
            ->find();

        $isExist = !empty($res) ? true : false;
        return $isExist;
    }

    /**
     * @param $condition
     * @return mixed
     */
    public function getVersionList($condition)
    {
        $where = '1';
        !empty($condition['type']) && $where .= " AND type='{$condition['type']}'";
        !empty($condition['system']) && $where .= " AND system='{$condition['system']}'";
        !empty($condition['version']) && $where .= " AND FIND_IN_SET('{$condition['version']}',suitable)";
        !empty($condition['platform']) && $where .= "platform = '{$condition['platform']}'";

        return $this->where($where)->order('id DESC')->select();
    }

    public function getVersionById($id)
    {
        if (empty($id)) return false;

        return $this->where('id='.$id)->find();
    }


    /**
     * 读取指定条件的数据
     * @param $condition
     * @return bool
     */
    public function getNewVersion($condition)
    {
        $where = '1';
        !empty($condition['type']) && $where .= " AND type='{$condition['type']}'";
        !empty($condition['system']) && $where .= " AND system='{$condition['system']}'";
        !empty($condition['version']) && $where .= " AND FIND_IN_SET('{$condition['version']}',suitable)";
        !empty($condition['platform']) && $where .= "platform = '{$condition['platform']}'";

        return $this->where($where)->order('id DESC')->limit(1)->select();
    }

    /**
     * 更新版本信息
     * @param $data
     * @param $condition
     * @return bool
     */
    public function updateVersion($data, $condition)
    {
        if (empty($data) || empty($condition['id'])){
            return false;
        }

        !empty($data['platform']) && $update['platform'] = addslashes($data['platform']);
        !empty($data['is_force']) && $update['is_force'] = $data['is_force'];
        !empty($data['download']) && $update['download'] = $data['download'] . '&id=' . $condition['id'];
        !empty($data['suitable']) && $update['suitable'] = $data['suitable'];
        !empty($data['update_desc']) && $update['update_desc'] = addslashes($data['update_desc']);
        !empty($data['fileName']) && $update['file_name'] = addslashes($data['fileName']);
        $update['update_time'] = date("Y-d-m H:i:s", time());

        return $this->where("id={$condition['id']}")->save($update);
    }


}