<?php
/**
 * Created by PhpStorm.
 * User: afanti
 * Date: 2017/8/3
 * Time: 11:18
 */
class OptionValueModel extends RelationModel{
    protected $trueTableName = "tb_ms_opt_val";


    public function getValueById(){

    }

    /**
     * 按照指定的SKU属性名的ID，读取关联的所有属性值列表
     * @param $condition
     * @return  array
     */
    public function getValuesByNameId($condition){
        if (empty($condition))  return  [];

        $res = $this->where("OPT_ID='{$condition['optNameCode']}'")->select();
        return $res;
    }

    /**
     * 检查要添加的SKU属性值，是否已经存在了。
     * @param $nameCode
     * @param $values
     * @return bool
     */
    public function checkExist($nameCode, $values){
        $valueList = $this->getValuesByNameId(['optNameCode' => $nameCode]);
        if (empty($valueList))  return false;//没有重复存在的

        //任意一种语言内容不空，且相同的就认为是重复存在的
        foreach ($valueList as $key => $optVal){
            foreach ($values as $vKey => $newVal){
                if (!empty($optVal['OPT_VAL_NM']) && $optVal['OPT_VAL_NM'] == $newVal['KR']){
                    return true;
                }
                if (!empty($optVal['OPT_VAL_CNS_NM']) && $optVal['OPT_VAL_CNS_NM'] == $newVal['CN']){
                    return true;
                }
                if (!empty($optVal['OPT_VAL_ENG_NM']) && $optVal['OPT_VAL_ENG_NM'] == $newVal['EN']){
                    return true;
                }

                if (!empty($optVal['OPT_VAL_JPA_NM']) && $optVal['OPT_VAL_JPA_NM'] == $newVal['JP']){
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 创建多种语言版本的SKU属性值，目前支持 韩语和中文
     * @param string $optNameId SKU属性名对应的CODE编码，或者叫做ID
     * @param array $values 多语言的SKU属性值，数组，每种语言一个元素。
     * @return array | bool
     */
    public function createOptionValues($optNameId, $values){
        if (empty($optNameId) || empty($values)){
            return false;
        }

        $data = "";
        $lastValues = array();
        $this->startTrans();
        // 找出指定属性名下属性值的最大ID，生成新的ID是使用，注意：这里OPT_VAL_ID是字符串，MAX会不是预期结果所以 +0 转换成int。
        $getSql = "SELECT MAX(OPT_VAL_ID+0) as maxId FROM {$this->trueTableName} WHERE OPT_ID='{$optNameId}' FOR UPDATE;";
        $maxRes = $this->query($getSql);
        $optMaxId = $maxRes[0]['maxId'];

        if(strlen($optMaxId) <= 3 || $optMaxId <= 999){
            //针对异常数据处理或者新Name增加值，ValueId小于三位数的，查出来+1。
            $newValueId = $optMaxId + 1;
        } elseif (strpos($optMaxId, $optNameId) !== false){
            //如果查询出来的最大ValueId包含NameId，说明ValueId还在 99 个范围内。
            $newValueId = substr($optMaxId, strlen($optNameId)) + 1;
        } else {
            //ValueId溢出的情况,去掉前四位，剩余的部分 + 1，然后拼接NameId。
            $newValueId = substr($optMaxId, 4) + 1;
        }

        foreach ($values as $key => $item){
            //从@2018-01-26开始，统一格式化为4位，避免冲突，NameId + 4位数或以上(从0001开始+1，超过4位数几位就保留几位)
            $optValId = $optNameId . sprintf('%04d',$newValueId + $key);
            $data .= "({$optNameId},{$optValId},'{$item['KR']}','{$item['CN']}','Y','{$item['EN']}','{$item['JP']}'),";

            //构建新的数据，替换原来的数字索引为 valueId，用来构建多语言内容。
            $lastValues[$optValId] = $item;
        }
        $data = trim($data, ',');
        $sql = "INSERT INTO tb_ms_opt_val 
                (OPT_ID,OPT_VAL_ID,OPT_VAL_NM,OPT_VAL_CNS_NM,OPT_VAL_USE_YN,OPT_VAL_ENG_NM,OPT_VAL_JPA_NM)
                VALUES {$data};";

        $res = $this->execute($sql);
        if ($res==false){
            $this->rollback();
            return false;
        } else {
            $this->commit();
            return array($res, $lastValues);
        }
    }

    public function deleteValuesByNameId(){

    }

    public function deleteValueById($options = array())
    {
        
    }

    /**
     * 生成新的值ID编码。
     * @param $maxId
     * @return mixed
     */
    public function getNewValueId($maxId)
    {
        return $maxId + 1;
    }
    
}