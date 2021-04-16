<?php

/**
 * 通用类目的模型类
 * User: afanti
 * Date: 2017/8/2
 * Time: 17:11
 */
class CategoryModel extends RelationModel
{

    protected $trueTableName = "tb_ms_cmn_cat";

    protected $_map = [
        'id' => 'id',
        'code' => 'CAT_CD',
        'name' => 'CAT_NM',
        'cnName' => 'CAT_CNS_NM',
        'parentCode' => 'PAR_CAT_CD',
        'namePath' => 'CAT_NM_PATH',
        'level' => 'CAT_LEVEL',
        'status' => 'status',
        'disable' => 'DISABLE_YN',
        'updated_time' => 'updated_time',
        'alp' => 'CAT_CD_ALP'

    ];
    protected $schemaTrans = '
                CAT_CD as code,
                id,                 
                CAT_NM as name,
                CAT_CNS_NM as cnName,
                PAR_CAT_CD as parentCode,
                CAT_NM_PATH as namePath,
                CAT_LEVEL as level,
                status,
                DISABLE_YN as disable,
                updated_time,
                CAT_CD_ALP as alp
                ';
    
    /**
     * 读取商品所属的大类的类型标记A-Z
     * 取消了与品牌类目的关联，现在是通用类目直接关联商品
     * @param $gudsId
     * @return array
     */
    public function getCateFlag($gudsId)
    {
        $sql = "SELECT C.CAT_CD_ALP FROM tb_ms_cmn_cat AS C
                LEFT JOIN tb_ms_guds AS G ON C.CAT_CD = G.GUDS_CAT 
                WHERE G.GUDS_ID = {$gudsId} ";
        
        $res = $this->query($sql);
        $data = array_pop($res);
        return $data['CAT_CD_ALP'];
    }

    /**
     * 根据主键id读取类目信息
     * @param $id
     * @return mixed
     */
    public function getCategoryById($id)
    {
        return $this->where(" id={$id}")
            ->field($this->schemaTrans)
            ->find();
    }

    /**
     * 根据类目编码来读取类目信息
     * @param $code
     * @param null $parentCode
     * @return bool
     */
    public function getCategoryByCode($code, $parentCode = null)
    {
        if (empty($code))   {
            return  false;
        }
        
        $where = "CAT_CD= '{$code}'";
        !is_null($parentCode) && $where .= " AND PAR_CAT_CD = '{$parentCode}' ";
        
        $result = $this->where($where)->find();
        
        return $result;
    }
    
    /**
     * 按层级读取类目列表，计算指定层级的所有类目数量。
     * @param int $level
     * @param bool $disable 是否读取禁用项：Y=只读取Y的,N=只读取N的 or false=读取全部的，默认false
     * @return bool
     */
    public function getTotalByLevel($level, $disable=false){
        if (empty($level))  {
            return false;
        }

        $where = '"CAT_LEVEL = {$level}"';
        ($disable !== false) && $where .= " AND `DISABLE_YN`=" . $disable;

        return $this->where($where)->getField('COUNT(id)');
    }

    /**
     * 按层级查询类目列表
     * @param int $level
     * @param int $start
     * @param int $limit
     * @param bool $disable 是否读取禁用项：Y=只读取Y的,N=只读取N的 or false=读取全部的，默认false
     * @return array | bool on error
     */
    public function getCateGoryByLevel($level, $start=0, $limit=50, $disable=false)
    {
        if (empty($level))  {
            return false;
        }

        $where = "CAT_LEVEL = {$level}";
        ($disable !== false) && $where .= " AND `DISABLE_YN`='{$disable}'";

        return $this->where($where)->limit($start, $limit)
            ->getField('
                CAT_CD as code,
                id,
                CAT_NM as name,
                CAT_CNS_NM as cnName,
                PAR_CAT_CD as parentCode, 
                CAT_NM_PATH as namePath,
                CAT_LEVEL as level,
                status,
                DISABLE_YN as disable,
                updated_time,
                CAT_CD_ALP as alp
                ');
    }
    
    
    /**
     * 根据父类CODE和等级读取类目列表。
     * 用户查询品牌类目的绑定关系列表以及其他需要查询类似数据的页面。
     * 
     * @param $parentCode
     * @param null $level
     * @return bool
     */
    public function getCategoryByParent($parentCode, $level = null)
    {
        if (empty($parentCode)){
            
            return false;
        }
        
        $condition = "PAR_CAT_CD = '{$parentCode}' ";
        if (!empty($level))
        {
            $condition = $condition . " AND CAT_LEVEL = {$level}";
        }
        
        
        $cateList = $this->where($condition)->getField($this->schemaTrans);
        return $cateList;
    }
    

    /**
     * 查询指定类目的子类目
     * @param string $code code码
     * @param int $level 层级
     * @return bool | array
     */
    public function getSubcategory($code, $level=null)
    {
        if (empty($code))   {
            return false;
        }
        
        //判定等级情况，实际上等级条件没有实际用处，这里只是留作扩展，万一绑定类目等级有异常或者业务有改动留余地。
        $where = " PAR_CAT_CD = '{$code}' ";
        if (!empty($level)){
            $where .= " AND CAT_LEVEL={$level}";
        }
        return $this->where($where)->field($this->schemaTrans)->select();
    }

    /**
     * 根据CODE码读取其关联的所有类目。
     * 按照前缀读取出所有其一级类目的子类，然后筛选。
     * @param $code
     * @return array
     */
    public function getRelateCateByCode($code)
    {
        //根据CODE读取其子类目和父类目，构成类目树
        $prefix = substr($code, 0, 3);
        $res = $this->where("LEFT(CAT_CD,3)='{$prefix}' OR CAT_LEVEL=1")
            ->field($this->schemaTrans)
            ->order('CAT_LEVEL ASC')
            ->select();

        $format = [];
        foreach ($res as $key => $cat){
            $format[$cat['code']] = $cat;
        }

        return $format;
    }

    /**
     * 类目列表按照层级拆分三个数组。
     * @param array $cateMap 关联的类目CODE列表
     * @param string $currentCat
     * @return array|bool
     */
    public function buildCateByLevel($cateMap, $currentCat)
    {
//        var_dump($cateMap);exit;
        if (empty($cateMap)) return false;
        $data = [];
        foreach ($cateMap as $key => $cate) {
            $cate['allVal'] = $cate['cnName'];
            $catId = $cate['code'];
            $cate = $this->parseFieldsMap($cate);

            $level1 = substr($catId, 0,3);
            $level2 = substr($catId, 3,3);
            $level3 = substr($catId, 6,4);
            $keyL1 = $level1 . '0000000';
            if ($level1 && $level2 == '000' && $level3 == '0000'){
                $data['cateStru'][$keyL1]['val'] = $cate;
            } elseif ($level1 && $level2 != '000' && $level3 == '0000'){
                $data['cateStru'][$keyL1]['sec'][$catId]['val'] = $cate;
            } elseif($level1 && $level2 != '000' && $level3 != '0000'){
                $keyL2 = $level1 . $level2 . '0000';
                $data['cateStru'][$keyL1]['sec'][$keyL2]['thr'][$catId]['val'] = $cate;
            }

            //TODO 多语言的内容读取和匹配，这里需要移植到元数据的读取的方法中.但是现在没有多语言内容
            $data['list'][$catId] = $cate;
        }

        return $data;
    }

    /**
     * 根据等级和类目编码搜索类目。
     * @param array $conditions ['levels'，'catCode']
     * @param int $start page limit start
     * @param int $limit page limit.
     * 
     * @return bool | array
     */
    public function search($conditions = array(), $start, $limit)
    {
        $where = " 1 ";
        if (!empty($conditions['CAT_CD'])) {
            $where .= " AND CAT_CD LIKE '{$conditions['CAT_CD']}%' ";
        }
    
        if (!empty($conditions['CAT_LEVEL'])){
            $where .= " AND CAT_LEVEL IN ({$conditions['CAT_LEVEL']}) ";
        }
    
        $result = $this->where($where)
            ->field($this->schemaTrans)
            ->limit($start, $limit)
            ->select();

        return $result;
    }

    /**
     * 查询符合搜索条件的数据数量
     * @param $conditions
     * @return bool
     */
    public function getSearchCount($conditions = null)
    {
        $where = " 1 ";
        if (!empty($conditions['CAT_CD'])) {
            $where .= " AND CAT_CD LIKE '{$conditions['CAT_CD']}%' ";
        }
        
        if (!empty($conditions['CAT_LEVEL'])){
            $where .= " AND CAT_LEVEL IN ({$conditions['CAT_LEVEL']}) ";
        }
        
        $count = $this->where($where)->getField("COUNT(id)");
        return $count;
    }

    /**
     * 添加通用类目功能
     * @param array $data
     * @return int|bool
     */
    public function addCategory($data)
    {
        if (empty($data) || !is_array($data))
        {
            return false;
        }
        
        $first = $second = array();
        if (!empty($data['levelFirst'])){
            $first = $this->getCategoryByCode($data['levelFirst']);
            
            if (!empty($data['levelSecond'])){
                $second = $this->getCategoryByCode($data['levelSecond'], $data['levelFirst']);
            }
        }

        $parentCode = $catCode = $catPath = null;
        switch ($data['catLevel']){
            case 1:
                $parentCode = null;
                $catPath = $data['catCnName'];
                $maxCodeCate = $this->getMaxCatCode($data['catLevel']);
                $maxCode = $maxCodeCate['CAT_CD'];
                $catAlp = chr(ord($maxCodeCate['CAT_CD_ALP']) + 1);
                $subCode = (int)substr($maxCode, 1, 2) + 1;
                $catCode = 'C' . sprintf('%02d', $subCode) . '0000000';
                break;
            case 2:
                $parentCode = $data['levelFirst'];
                $catPath = $first['CAT_NM_PATH'] . '>' . $data['catCnName'];
                $maxCodeCate = $this->getMaxCatCode($data['catLevel'], $parentCode);
                $catAlp = $first['CAT_CD_ALP'];//取一级级类目的Code标识。
                if(!empty($maxCodeCate['CAT_CD'])){
                    $subCode = (int)substr($maxCodeCate['CAT_CD'], 3, 3) + 1;
                    $catCode = substr($maxCodeCate['CAT_CD'], 0, 3) . sprintf('%03d', $subCode);
                    $catCode .= substr($maxCodeCate['CAT_CD'], 6, 4);
                } else {
                    $catCode = substr($data['levelFirst'], 0, 3) . '0010000';
                }
                break;
            case 3:
                $parentCode = $data['levelSecond'];
                $catPath = $second['CAT_NM_PATH'] . '>' . $data['catCnName'];
                $maxCodeCate = $this->getMaxCatCode($data['catLevel'], $parentCode);
                $catAlp = $first['CAT_CD_ALP'];//取一级级类目的Code标识。
                if(!empty($maxCodeCate['CAT_CD'])){
                    $subCode = (int)substr($maxCodeCate['CAT_CD'], 6, 4) + 1;
                    $catCode = substr($maxCodeCate['CAT_CD'], 0, 6) . sprintf('%04d', $subCode);
                } else {
                    $catCode = substr($data['levelSecond'], 0, 6) . '0001';
                }
                break;
            default: //如果不指定添加的类目等级，直接返回false。
                return false;
        }

       $catData['CAT_CD'] = $catCode;
       $catData['CAT_NM'] = $data['catName'];
       $catData['CAT_CNS_NM'] = $data['catCnName'];
       $catData['CAT_SORT'] = !empty($data['sort']) ? $data['sort'] : 0;
       $catData['PAR_CAT_CD'] = $parentCode;
       $catData['CAT_NM_PATH'] = $catPath;
       $catData['ALIAS'] = !empty($data['aliasName']) ? $data['aliasName'] : null;
       $catData['CAT_LEVEL'] = $data['catLevel'];
       $catData['status'] = 1;
       $catData['DISABLE_YN'] = 'N';
       $catData['CAT_CD_ALP'] = $catAlp;
       
       $res =  $this->add($catData);
       return $res;
    }

    /**
     * 找出同层级最大的 CODE值，计算新增的CODE值。
     * @param int $level 3 =< 类目层级 >= 1
     * @param string $parentCode 父类目CODE
     * @return mixed
     */
    public function getMaxCatCode($level, $parentCode = null)
    {
        
        $where = "CAT_LEVEL={$level} ";
        if (!empty($parentCode)) {
            $where .= " AND PAR_CAT_CD='{$parentCode}'";
        }
    
        $res = $this->where($where)
            ->order("CAT_CD DESC")
            ->limit(1)
            ->find();
        //echo $this->getLastSql();
        return $res;
    }

    /**
     * 更新类目
     *
     * @param array $data 要更新的最新数据
     * @param array $condition 更新条件，必须包含 id属性
     * @return bool
     */
    public function update($data, $condition)
    {
        if (empty($data))   return false;

        $res =  $this->where(" id = '{$condition['id']}' ")
            ->save($data);

        return $res;
    }
    
    /**
     * 验证是否有重复名称
     * 
     * @param array $data
     * @param array $condition
     * @return bool | array false 表示没有有重复，数组表示重复的数据
     */
    public function checkDuplicateName($data, $condition)
    {
        if (empty($data))   return false;
        
        $duplicate = $this->where("CAT_NM ='{$data['CAT_NM']}' OR CAT_CNS_NM='{$data['CAT_CNS_NM']}'")
            ->getField("id,CAT_CD");
    
        //从重复名称的数据中去掉 id为当前要更新的数据，
        //因为如果查出来的不是要修改的本身，清理后数组肯定不空，如果就只有当前条自己数组就空了，防止没有修改名称的情况下更新数据
        if (isset($condition['id'])) {
            unset($duplicate[$condition['id']]);
        }
        
        if (!empty($duplicate)){
            return $duplicate;
        }
        
        return false;
    }

}