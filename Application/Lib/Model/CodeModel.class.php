<?php
/**
 * User: yangsu
 * Date: 18/7/9
 * Time: 19:37
 */


class CodeModel extends Model
{
    public static $stock_cd = 'N002440100';
    public static $change_attribution_store_cd = 'N002990001';
    public static $change_sales_team_cd = 'N002990002';
    public static $change_purchasing_team_cd = 'N002990003';
    public static $change_small_sales_team_cd = 'N002990005';



    /**
     * @return array
     */
    public static function getAllCodeArrKeyVal()
    {
        $Model = M();
        $temp_res = $Model->table('tb_ms_cmn_cd')
            ->field('CD,CD_VAL,ETC')
            ->order('SORT_NO asc')
            ->cache(true, 10)
            ->select();
        $res = array_column($temp_res, 'CD_VAL', 'CD');
        return $res;
    }

    /**
     * Get code array
     *
     * @param array $cd_arr cd arr
     * @param string $use_yn is show
     * @param string $nocache default need cache
     * @return array
     */
    public static function getCodeArr($cd_arr = [], $use_yn = 'Y', $nocache = '')
    {
        if (empty($cd_arr)) {
            return [];
        }
        $Model = M();
        $where_str = ' 1!=1 ';
        foreach ($cd_arr as $value) {
            $where_str .= "OR CD like '" . $value . "%'";
        }
        if ($use_yn) {
            $where['USE_YN'] = $use_yn;
        }
        if ($nocache) {
            $res = $Model->table('tb_ms_cmn_cd')
                ->field('CD,CD_VAL,ETC')
                ->where($where)
                ->where($where_str)
                ->order('SORT_NO asc,CD asc')
                ->select();
        } else {
            $res = $Model->table('tb_ms_cmn_cd')
                ->field('CD,CD_VAL,ETC')
                ->where($where)
                ->where($where_str)
                ->order('SORT_NO asc,CD asc')
                ->cache(true, 3)
                ->select();
	   }        
	   return $res;
    }

    /**
     * Get code array key and val
     *
     * @param array $cd_arr cd arr
     * @param string $use_yn is show
     *
     * @return array
     */
    public static function getCodeKeyValArr($cd_arr = [], $use_yn = 'Y', $nocache = '')
    {
        $temp_res = self::getCodeArr($cd_arr, $use_yn, $nocache);
        $res = array_column($temp_res, 'CD_VAL', 'CD');
        return $res;
    }

    /**
     * @param array $cd_arr
     * @param string $use_yn
     *
     * @return array
     */
    public static function getCodeValKeyArr($cd_arr = [], $use_yn = 'Y', $nocache = '')
    {
        $temp_res = self::getCodeArr($cd_arr, $use_yn, $nocache);
        $res = array_column($temp_res, 'CD', 'CD_VAL');
        return $res;
    }

    /**
     * @param        $cd
     * @param bool $USE_YN
     * @param null $params
     * @param string $nm
     *
     * @return array
     */
    public static function getCodeLang($cd, $USE_YN = true, $params = null, $nm = 'CD')
    {
        $cd_arr = B2bModel::get_code(null, 0, 0, $nm, $USE_YN, $cd . '%', $params);
        foreach ($cd_arr as &$v) {
            $v['CD_VAL'] = L($v['CD_VAL']);
        }
        return $cd_arr;
    }

    /**
     * @param array $datas
     * @param array $toValArr
     * @param string $type
     *
     * @return array
     */
    public static function autoCodeOneVal(array $datas, array $toValArr, $type = 'enable')
    {
        foreach ($toValArr as $value) {
            if ($datas[$value]) {
                $code_arr[] = substr($datas[$value], 0, 6);
            }
        }
        if (empty($code_arr)) {
            return $datas;
        }
        switch ($type) {
            case 'enbale':
                $use_yn = 'y';
                break;
            case 'all':
                $use_yn = null;
                break;
        }
        $code_arr = array_unique($code_arr);
        $code_val_arr = self::getCodeKeyValArr($code_arr, $use_yn);
        foreach ($datas as $key => $value) {
            if (in_array($key, $toValArr)) {
                $datas[$key . '_val'] = $code_val_arr[$value];
            }
        }
        return $datas;
    }

    /**
     * @param array $datas
     * @param array $toValArr
     * @param string $type
     *
     * @return array
     */
    public static function autoCodeTwoVal(array $datas, array $toValArr, $type = 'enable')
    {
        foreach ($toValArr as $value) {
            $code_data = array_filter(array_column($datas,$value));
            foreach ($code_data as $item) {
                if (false !== strpos($item, 'N00')) {
                    if($item == 'customs_clear'){
                    }
                    $cd_string = $item;
                    break;
                }
            }
//            $cd_string = array_shift(array_filter(array_column($datas,$value)));
            if ($cd_string) {
                $code_arr[] = substr($cd_string, 0, 6);
            }
        }
        
        if (empty($code_arr)) {
            return $datas;
        }
        $code_arr = array_unique($code_arr);
        switch ($type) {
            case 'enbale':
                $use_yn = 'y';
                break;
            case 'all':
                $use_yn = null;
                break;
        }
        $code_val_arr = self::getCodeKeyValArr($code_arr, $use_yn);
        
        foreach ($datas as $k => $data) {
            foreach ($data as $key => $value) {
                if (in_array($key, $toValArr)) {
                    $datas[$k][$key . '_val'] = $code_val_arr[$value];
                }
            }
        }
        return $datas;
    }
    public static function autoCodeTwoVal1(array $datas, array $toValArr, $type = 'enable')
    {
        foreach ($toValArr as $value) {

            $code_data = array_filter(array_column($datas, $value));
            foreach ($code_data as $item) {
               
                if (false !== strpos($item, 'N00')) {
                   
                    $cd_string = $item;
                    break;
                }
            }
            //            $cd_string = array_shift(array_filter(array_column($datas,$value)));
            if ($cd_string) {
                $code_arr[] = substr($cd_string, 0, 6);
            }
        }

        if (empty($code_arr)) {
            return $datas;
        }
        
        $code_arr = array_unique($code_arr);
        switch ($type) {
            case 'enbale':
                $use_yn = 'y';
                break;
            case 'all':
                $use_yn = null;
                break;
        }
        $code_val_arr = self::getCodeKeyValArr($code_arr, $use_yn);
       
        foreach ($datas as $k => $data) {
            foreach ($data as $key => $value) {
                if (in_array($key, $toValArr)) {
                    $datas[$k][$key] = $code_val_arr[$value];
                }
            }
        }
        
        return $datas;
    }
    /**
     * @param $code_val
     *
     * @return mixed
     */
    public static function getInfoFromCodeVal($code_val)
    {
        $Model = M();
        $where['CD_VAL'] = $code_val;
        $where['CD'] = ['LIKE', 'N00124%'];
        return $Model->table('tb_ms_cmn_cd')
            ->field('CD,CD_VAL,ETC,ETC3')
            ->where($where)
            ->find();
    }

    /**
     * gshopper code
     *
     * @return array
     */
    public static function getLegalAuditStatus()
    {
        $Model = M();
        $where['CD'] = ['LIKE', 'N00366%'];
        $res = $Model->table('tb_ms_cmn_cd')
            ->field('CD,CD_VAL')
            ->where($where)
            ->cache(true, 3)
            ->select();
        $legalArr = array_column($res, 'CD_VAL', 'CD');
        return (array)$legalArr;
    }


    public static function getGpPlatCds()
    {
        $Model = M();
        $where['CD'] = ['LIKE', 'N00083%'];
        $where['ETC3'] = 'N002620800';
        $res = $Model->table('tb_ms_cmn_cd')
            ->field('CD,CD_VAL,ETC,ETC3')
            ->where($where)
            ->cache(true, 3)
            ->select();
        $plat_cds = array_column($res, 'CD');
        return (array)$plat_cds;
    }

    public function getSiteCodeArr($plat_cd = '')
    {
        $Model = M();
        $where['CD'] = ['LIKE', 'N00083%'];
        if ($plat_cd) {
            $where['ETC3'] = ['IN', $plat_cd];
        }
        $res = $Model->table('tb_ms_cmn_cd')
            ->field('CD,CD_VAL,ETC,ETC3,SORT_NO')
            ->where($where)
            ->cache(true, 3)
            ->order('SORT_NO ASC,CD ASC')
            ->select();
        return $res;
    }

    public static function getTeamBy($cd, $field = 'ETC')
    {
        $Model = M();
        $where['CD'] = $cd;
        $res = $Model->table('tb_ms_cmn_cd')
            ->where($where)
            ->cache(true, 3)
            ->getField($field);
        return $res;
    }

    public static function getEtcKeyValue($codeType)
    {
        $Model = M();
        $whereCmn['CD'] = array('like', $codeType . '%');
        $whereCmn['USE_YN'] = array('eq', 'Y');
        $res = $Model->table('tb_ms_cmn_cd')
                        ->field('CD_VAL,ETC')
                        ->where($whereCmn)
                        ->select();
        return array_column($res, 'ETC', 'CD_VAL');
    }

    public static function getAlloOutStatusCode()
    {
        return [
            '0' => [
                'CD' => '0',
                'CD_VAL' => '未完成'
            ],
            '1' => [
                'CD' => '1',
                'CD_VAL' => '已完成'
            ],
        ];
    }

    public static function getAlloInStatusCode()
    {
        return [
            '0' => [
                'CD' => '0',
                'CD_VAL' => '未完成'
            ],
//            '1' => [
//                'CD' => '1',
//                'CD_VAL' => '已完成'
//            ],
        ];
    }

    public static function getSendNetCode()
    {
        return [
            '0' => '否',
            '1' => '是',
            '2' => '不对接发网（erp内部调整）',
        ];
    }

    public static function getTransferUseTypeCode()
    {
        return [
            '0' => '销售',
            '1' => '非销售',
        ];
    }

    public static function getGoodsTypeCode()
    {
        return [
            [
                'cd' => 'N002440100',
                'cdVal' => '正品',
            ],
            [
                'cd' => 'N002440400',
                'cdVal' => '残次品',
            ],
        ];
    }

    public static function getPositive()
    {
        return [
            '正品' => 'N002440100',
            '残次品' => 'N002440400',
        ];
    }

    public static function getValue($code)
    {
        $Model = M();
        $where['CD'] = $code;
        return $Model->table('tb_ms_cmn_cd')
            ->field('CD,CD_VAL,ETC,ETC3,ETC4,ETC5')
            ->where($where)
            ->find();
    }


    /**
    * 获取销售销售小团队
    * @param string $plat_cd
    * @return mixed
    */
   public static function getSellSmallTeamCodeArr($code = '')
   {
       $Model = M();
       $where['CD'] = ['LIKE', 'N00323%'];
       $where['USE_YN'] = array('eq', 'Y');
       if ($code) {
           $where['ETC'] = ['IN', $code];
       }
       $res = $Model->table('tb_ms_cmn_cd')
           ->field('CD,CD_VAL,ETC,ETC2,ETC3')
           ->where($where)
           ->cache(true, 3)
           ->order('SORT_NO ASC,CD ASC')
           ->select();
       return $res;
   }
    // 根据Etc获取对应的CD值
    public static function getCdByEtc($where = [])
    {
        $Model = M();
        $res = $Model->table('tb_ms_cmn_cd')
            ->field('CD')
            ->where($where)
            ->select();
        return array_column($res, 'CD');
    }

    public static function getCodeAll($where,$field){
        $Model = M();
        $res = $Model->table('tb_ms_cmn_cd')
            ->field($field)
            ->where($where)
            ->select();
        return $res;
    }
    public static function getCodeFind($where,$field){
        $Model = M();
        $res = $Model->table('tb_ms_cmn_cd')
            ->field($field)
            ->where($where)
            ->find();
        return $res;
    }


    /**
     * 数据code 自动转换称val 支持一个字段包含多个code 使用 ',' 隔开
     * @param array $data
     * @param array $fields
     * @author Redbo He
     * @date 2021/4/2 14
     */
    public  static function  autoCodes2Val(array $data, array $fields )
    {
        if(empty($data) || empty($fields)) return $data;
        $codes = [];
        foreach ($fields as $field)
        {
            $field_codes = array_unique(array_column($data,$field));
            if($field_codes)
            {
                foreach ($field_codes as $k => $val)
                {
                    if($val) {
                        $val_arr = explode(',',$val);
                        $codes = array_filter( array_unique(array_merge($codes, $val_arr)));
                    }
                }
            }
        }
        $code_val_arr = self::getCodeKeyValArr($codes, null);
        foreach ($data as $kk =>  $val)
        {
            foreach ($fields as $field)
            {
                if(isset($val[$field]))
                {
                    $index = $field . '_val';
                    $val_string = '';
                    $field_val = $val[$field];
                    if(strpos($field_val,',') !== false)
                    {
                        $field_val_arr = array_filter(array_unique( explode(',',$field_val)));
                        if($field_val_arr)
                        {
                            $val_arr = array_map(function($v) use ($code_val_arr){
                                return isset($code_val_arr[$v]) ? $code_val_arr[$v] : '';
                            }, $field_val_arr);
                            $val_string = implode(',',$val_arr);
                        }
                    }
                    else
                    {
                        $val_string = $code_val_arr[$field_val] ? $code_val_arr[$field_val] : '';
                    }
                    $data[$kk][$index] = $val_string;
                }
            }
        }
        return $data;
    }

}