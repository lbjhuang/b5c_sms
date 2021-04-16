<?php
/**
 * Created by PhpStorm.
 * User: afanti
 * Date: 2017/10/30
 * Time: 18:07
 */
class AreaModel extends Model{
    const COUNTRY = 1;//国家
    const STATE_PROVINCE = 2;//州，省
    const CITY = 3;//市
    const COUNTY = 4;//区，县
    const TOWN = 5;//乡镇
    
    protected $trueTableName = 'tb_ms_user_area';
    protected $schemaFields = 'id,area_no,zh_name,parent_no,area_type,
                                zip_code,zh_spelling,rank';
    
    /**
     * 指定Id读取地址
     * @param $id
     * @return mixed
     */
    function getAreaById($id){
        return $this->where("id={$id}")->find();
    }
    
    /**
     * 根据区域编码读取地址
     * @param $no
     * @return mixed
     */
    public function getAreaByNo($no)
    {
        return $this->where("area_no={$no}")->find();
    }
    
    /**
     * 指定层级类型读取列表
     * @param $type
     * @return mixed
     */
    public function getAreaByType($type){
        $cacheKey = 'Area_Address_'.$type;
        $res = S($cacheKey);
        if (! empty($res)) {
            return $res;
        }
        
        $res = $this->where("area_type={$type}")->order('rank asc')
            ->getField($this->schemaFields, true);
        $cacheRes = S($cacheKey, $res, 2 * 3600);

        return $res;
    }

    /**
     * 读取含洲际国家列表
     * @param $type
     * @return mixed
     */
    public function getInterAreaByType($type)
    {
        $res = $this->field("tb_ms_cmn_cd.CD_VAL,tb_ms_user_area.area_no,
        tb_ms_user_area.parent_no,tb_ms_user_area.zh_name,tb_ms_user_area.area_type,
        tb_ms_user_area.rank,tb_ms_cmn_cd.CD")
        ->join("tb_ms_cmn_cd on tb_ms_cmn_cd.CD=tb_ms_user_area.continent")->
        where("area_type={$type}")->order('tb_ms_user_area.rank asc')->select();
        return $res;

    }
    
    /**
     * @return mixed 安排续读取地址
     */
    public function getCountiesBySort()
    {
        $res = $this->where("area_type=1 AND rank is not null")->order('rank asc')->select();
        return $res;
    }
    
    /**
     * 指定二字码读取单个地址
     * @param $twoChar
     * @return mixed
     */
    public function getAreaByTwoChar($twoChar)
    {
        return $this->where("two_char='{$twoChar}'")->find();
    }
    
    /**
     * 根据国家区域编码读取其从属的州、省级别区域列表
     * @param array $condition 参数数组，必须有['countryNo' => 国家区域编号 area_no 值]
     * @return mixed
     */
    public function getStateAndProvince($condition){
        if (empty($condition['countryNo'])){
            return $this->getAreaByType(self::STATE_PROVINCE);
        }
    
        $result = $this->where('parent_no=' . $condition['countryNo'])->getField($this->schemaFields, true);
        return $result;
    }
    
    /**
     * 根据州或省区域编码读取其从属的城市列表
     * @param array $condition 参数数组，必须有['provinceNo' => 省、州的区域编号 area_no 值]
     * @return mixed
     */
    public function getCities($condition)
    {
        if (empty($condition['provinceNo'])){
            return $this->getAreaByType(self::CITY);
        }
        
        $result = $this->where('parent_no=' . $condition['provinceNo'])->getField($this->schemaFields, true);
        return $result;
    }
    
    /**
     * 根据城市级别域编码读取其从属的区、县列表
     * @param array $condition 参数数组，必须有['cityNo' => 市级别的区域编号 area_no 值]
     * @return mixed
     */
    public function getCounties($condition){
        if (empty($condition['cityNo'])){
            return $this->getAreaByType(self::COUNTY);
        }
    
        $result = $this->where('parent_no=' . $condition['cityNo'])->getField($this->schemaFields, true);
        return $result;
    }
    
    /**
     * 根据区、县区域编码读取其从属的乡镇列表
     * @param array $condition 参数数组，必须有['countyNo' => 区，县级别的区域编号 area_no 值]
     * @return mixed
     */
    public function getTowns($condition){
        if (empty($condition['countyNo'])){
            return $this->getAreaByType(self::COUNTY);
        }
    
        $result = $this->where('parent_no=' . $condition['countyNo'])->getField($this->schemaFields, true);
        return $result;
    }

    public function getChildrenArea($parent_no = 0) {
        $field = 'id, area_no, zh_name, parent_no';
        return $this->field($field)->where(['parent_no'=>$parent_no])->order('rank')->select();
    }

    public function getRegisteredCountry($country_ids)
    {
        $field = 'id, area_no, zh_name, parent_no';
        return $this->field($field)->where(['id'=>['in',$country_ids]])->order('rank')->select();
    }

    // 根据国家id获取名称
    public function getCountryNameByIds($country_ids)
    {
        if (empty($country_ids)) return '';
        $country_arr = explode(",", $country_ids);
        $country_name_arr = $this->where(['id' => ['in', $country_arr]])->getField('zh_name',true);
        return implode(",", $country_name_arr);
    }
}