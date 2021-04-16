<?php
/**
 * User: tianrui
 * Date: 19/03/07
 * Time: 15:08
 */
@import("@.Model.Orm.TbMsCmnCdType");
@import("@.Model.Orm.TbMsCmnCd");


class CodeRepository extends Repository
{
    public function getCodeKeyVal($cd_type)
    {
        return CodeModel::getCodeKeyValArr([$cd_type], '', 'Y'); // 不要缓存，因为要根据最新的数据来判断值是否已经存在
    }

    public function getCodeValKey($cd_type)
    {
        return CodeModel::getCodeValKeyArr([$cd_type], '', 'Y');
    }
    // 获取所有code type的键值对数组
    public function getCodeTypeKeyVal($status = '0, 1')
    {
        $where['status'] = ['in', $status];
        $res_db = $this->model->table('tb_ms_cmn_cd_type')
            ->field('cd_type, cd_type_name')
            ->where($where)
            ->select();
        $res = array_column($res_db, 'cd_type_name', 'cd_type');
        return $res;
    }

    public function getCodeTypeList($where = [])
    {
        $res_db = TbMsCmnCdType::select(
                    'id', 
                    'cd_type', 
                    'cd_type_key', 
                    'cd_type_name', 
                    'status'
                );
        if ($where) {
            $res_db = $res_db->where($where[0], $where[1]);
        }
        $res = $res_db->get();
        if ($res) {
            $res = $res->toArray();
        }
        return $res;
    }

    public function getLastCodeTypeValue($value = 'cd_type_key')
    {
        return TbMsCmnCdType::max($value);
    }

    public function createBatchCode($save)
    {
        return TbMsCmnCdType::insert($save);
    }

    public function updateCodeTypeStatus($where, $save)
    {
        $save['updated_by'] = DataModel::userNamePinyin() ? DataModel::userNamePinyin() : 'system';
        return TbMsCmnCdType::where($where)->update($save);
    }

    public function createCodeType($addData)
    {
        $addData['created_by'] = DataModel::userNamePinyin();
        return TbMsCmnCdType::insertGetId($addData);   
    }

    public function checkCodeTypeNameExist($where = [])
    {
        return TbMsCmnCdType::where($where[0], $where[1])->exists();
    }

    public function checkCodeTypeStatus($cd_type)
    {
        return TbMsCmnCdType::where('cd_type', $cd_type)->where('status', 1)->exists();
    }

    public function getCodeTypeByGroup()
    {
        return $this->model->table('tb_ms_cmn_cd')->field("CD, CD_NM")->group("CD_NM")->select();
    }

    public function getLastCodeValue($where = [], $value = 'CD')
    {       
        return $this->model->table('tb_ms_cmn_cd')->where($where)->max($value);
    }

    public function updateCode($where, $save)
    {
        $save['updated_by'] = DataModel::userNamePinyin() ? DataModel::userNamePinyin() : 'system';
        $save['updated_at'] = DateModel::now(); 
        return $this->model->table('tb_ms_cmn_cd')->where($where)->save($save);
    }

    public function createCode($save)
    {
        $save['created_by'] = DataModel::userNamePinyin();
        $save['created_at'] = DateModel::now();
        return $this->model->table('tb_ms_cmn_cd')->add($save);
    }

    public function getOneCode($where)
    {
        return $this->model->table('tb_ms_cmn_cd')->where($where)->find();
    }

    public function modifyCodeData()
    {
        $str = <<<ET
            SELECT
             *
            FROM
             (
              SELECT
               COUNT(CD_6) AS cd_6_11,
               CONCAT(
                "'",
                REPLACE (cd_arr, ',', "','"),
                "'"
               ) AS cd_arr,
               CD_6,
               CD,
               CD_NM
              FROM
               (
                SELECT
                 SUBSTR(CD, 1, 6) AS CD_6,
                 GROUP_CONCAT(CD) AS cd_arr,
                 CD,
                 CD_NM
                FROM
                 `tb_ms_cmn_cd`
                GROUP BY
                 CD_6,
                 CD_NM
               ) AS t1
              GROUP BY
               CD_NM
              HAVING
               cd_6_11 > 1
             ) AS t11,
             tb_ms_cmn_cd
            WHERE
             tb_ms_cmn_cd.CD = (t11.cd)
ET;
        $res = M()->query($str);
        return $res;
    }

}