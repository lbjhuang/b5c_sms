<?php 

class TbHrEmplChildModel extends BaseModel
{
    protected $trueTableName = 'tb_hr_empl_child';
    
    protected $_link = [
        'parent' => [
            'mapping_type' => HAS_ONE,
            'class_name' => 'TbHrEmplModel',
            'foreign_key' => 'ID',
            'relation_foreign_key' => 'EMPL_ID',
            'mapping_name' => 'empl_parent',
        ]
    ];

    /*protected $_validate = [
        ['V_STR1','require','可扩展字段1'],//默认情况下用正则进行验证
        ['V_STR2','require','可扩展字段2']//默认情况下用正则进行验证
    ];*/
   /* protected $_auto = [
        ['CREATE_TIME', 'getTime', Model:: MODEL_INSERT, 'callback'],
        ['UPDATE_TIME', 'getTime', Model::MODEL_BOTH, 'callback'],
        ['CREATE_USER_ID', 'getName', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_USER_ID', 'getName', Model::MODEL_BOTH, 'callback']
    ];*/

    /**
     *  list children by ids
     *
     */
    public function listByIds($ids){
        if(is_string($ids)) $ids=explode(',',$ids);
        $ids = is_array($ids)?$ids:array();
        if(empty($ids)){
            return null;
        }
        $order_data = array();
        $order_data = array('ID'=>'ASC');
        $wheres = array();
        $wheres['EMPL_ID'] = array('IN',$ids);
        $list = $this->where($wheres)
            ->order($order_data)
            ->select();
        $list = is_array($list)?$list:array();
        $ret = array();
        foreach($list as $k=>$v){
            $id = $v['EMPL_ID'];
            $ret[$id][] = $v;
        }
        return $ret;
    }

    /**
     *  Get some info of empl by type
     *
     */
    public function gainEmplInfoByType($emplid,$type=null){
        $code = md5(serialize(func_get_args()));
        static $stc_empl_info_rows = array();
        if(isset($stc_empl_info_rows[$code])){
            return $stc_empl_info_rows[$code];
        }

        $emplid = intval($emplid);
        $order_data = array();
        $order_data = array('ID'=>'ASC');
        $wheres = array();
        $wheres['EMPL_ID'] = $emplid;
        if($type!==null){
            $wheres['TYPE'] = intval($type);
        }
        $info_list = $this->where($wheres)
            ->order($order_data)
            ->select();
        $info_list = is_array($info_list)?$info_list:array();

        $stc_empl_info_rows[$code]=$info_list;
        return $info_list;
    }

    /**
     *  Get (filter type) row or rows from array list
     *
     */
    public function takeInfoListByType($childrens, $type=1, $is_all=true){
        $filtered = array_filter($childrens, function($item) use($type){
            $ret = false;
            if(isset($item['TYPE']) and $item['TYPE']==$type){
                $ret = true;
            }
            return $ret;
        });
        // row or rows
        if(!$is_all){
            $filtered = array_shift($filtered);
        }
        return $filtered;
    }

    /**
     *  Format Educational experience
     *   学历情况     V_STR1  学校名称
     *               V_STR2  专业
     *               V_DATE1 开始日期
     *               V_DATE2 结束日期
     *               V_INT1  是否有学位； 0-否;1-是
     *               V_STR3  毕业证书编号
     *               V_STR4  学历性质
     *
     */
    public function formatEdu($row){
        $ret = array();
        if(!empty($row)){
            $ret['school_name'] = $row['V_STR1'];
            $ret['major'] = $row['V_STR2'];
            $ret['graduation_certificate_number'] = $row['V_STR3'];
            $ret['educational_nature'] = $row['V_STR4'];
            $ret['start_date'] = Mainfunc::fmtDate($row['V_DATE1']);
            $ret['end_date'] = Mainfunc::fmtDate($row['V_DATE2']);
            $ret['is_a_degree'] = $row['V_INT1'];
        }
        return $ret;
    }

    /**
     *  
     *   工作经历     V_DATE1 开始时间
     *               V_DATE2 结束时间
     *               V_STR1  公司名称
     *               V_STR2  职位
     *               V_STR3  离职原因
     *
     */
    public function formatWork($row){
        $ret = array();
        if(!empty($row)){
            $ret['V_STR1'] = $row['V_STR1'];
            $ret['V_STR2'] = $row['V_STR2'];
            $ret['V_STR3'] = $row['V_STR3'];
            $ret['V_DATE1'] = Mainfunc::fmtDate($row['V_DATE1']);
            $ret['V_DATE2'] = Mainfunc::fmtDate($row['V_DATE2']);
        }
        return $ret;
    }

    /**
     *
     *   紧急联系人信息    V_STR1  姓名
     *                   V_STR2  电话联系方式
     *                   V_STR3  与自己关系
     *
     */
    public function formatEmer($row){
        $ret = array();
        if(!empty($row)){
            $ret['V_STR1'] = $row['V_STR1'];
            $ret['V_STR2'] = $row['V_STR2'];
            $ret['V_STR3'] = $row['V_STR3'];
        }
        return $ret;
    }

    /**
     *
     *   银行卡信息   V_STR1  账号
     *               V_STR2  中文名拼音
     *               V_STR3  SWIFT COOD
     *               V_STR4  开户行
     *               V_STR5  开会行(英文)
     *
     */
    public function formatBank($row){
        $ret = array();
        if(!empty($row)){
            $ret['V_STR1'] = $row['V_STR1'];
            $ret['V_STR2'] = $row['V_STR2'];
            $ret['V_STR3'] = $row['V_STR3'];
            $ret['V_STR4'] = $row['V_STR4'];
            $ret['V_STR5'] = $row['V_STR5'];
        }
        return $ret;
    }


}
