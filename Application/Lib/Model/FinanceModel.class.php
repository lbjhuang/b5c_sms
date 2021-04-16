<?php

/**

 */
class FinanceModel extends Model
{

    public static function getRateList($params)
    {
        $where = ['ar.area_type' => 1];
        empty($params['page']) and $params['page'] = 1;
        empty($params['page_size']) and $params['page_size'] = 20;
        empty($params['country']) or $where['zh_name'] = ['like', '%' . $params['country'] . '%'];
        $offset = ($params['page'] - 1) * $params['page_size'];
        $query = M('tax_rate', 'tb_fin_')->alias('tr')
            ->join('right join tb_ms_user_area ar on ar.id=tr.area_id')
            ->field([
                'tr.*',
                'ar.zh_name',
                'ar.id as area_id'
            ])
            ->where($where);
        $query1 = clone $query;
        $list = $query->order('tr.rate desc')->limit($offset . ',' . $params['page_size'])->select();
        $total = $query1->count();
        return ['code' => 2000, 'msg' => '查询成功', 'data' => [
            'list' => $list,
            'total' => (int) $total,
            'page' => $params['page'],
            'page_size' => $params['page_size'],
        ]];
    }

    public static function rateEdit($params)
    {
        foreach ($params as $v) {
            if (empty($v['area_id']) || $v['rate'] == '' || $v['rate'] < 0 ) {
                return ['code' => 3000, 'msg' => '税率填写错误', 'data' => []];
            }
        }
        M()->startTrans();
        try {
            if (!RedisLock::lock('fin_tax_rate_edit'))
                throw new \Exception('请求异常');
            foreach ($params as $v) {
                $isExisted = M('tax_rate', 'tb_fin_')->where(['area_id' => $v['area_id']])->getField('rate');
                if ($isExisted) {
                    $ret = M('tax_rate', 'tb_fin_')->where(['area_id' => $v['area_id']])->save(['rate' => $v['rate'], 'updated_by' => $_SESSION['m_loginname']]);
                } else {
                    $ret = M('tax_rate', 'tb_fin_')->add(['area_id' => $v['area_id'], 'rate' => $v['rate'], 'created_by' => $_SESSION['m_loginname'], 'updated_by' => $_SESSION['m_loginname']]);
                }
                if ($ret !== false) {
                    $start = M('tax_rate_part', 'tb_fin_')
                        ->field('date_format(start,"%Y%m%d") as start')
                        ->where(['area_id' => $v['area_id'], '_string' => 'end is null'])
                        ->find();
                    if ($start['start'] == date('Ymd')) {//当天修改不新增历史记录
                        $ret2 = M('tax_rate_part', 'tb_fin_')->where(['area_id' => $v['area_id'], '_string' => 'end is null'])
                            ->save(['rate' => $v['rate'], 'updated_by' => $_SESSION['m_loginname'], 'updated_at' => date('Y-m-d H:i:s')]);
                        if (!$ret2) {
                            throw new \Exception('历史表更新失败');
                        }
                    } else {
                        M('tax_rate_part', 'tb_fin_')->where(['area_id' => $v['area_id'], '_string' => 'end is null'])
                            ->save(['end' => date('Y-m-d H:i:s'), 'updated_by' => $_SESSION['m_loginname']]);
                        if (!M('tax_rate_part', 'tb_fin_')->add(['area_id' => $v['area_id'], 'rate' => $v['rate'], 'created_by' => $_SESSION['m_loginname'], 'updated_by' => $_SESSION['m_loginname']])) {
                            throw new \Exception('历史表保存失败');
                        }
                    }

                } else {
                    throw new \Exception('税率保存失败');
                }
            }
            M()->commit();
            $res['code'] = 2000;
            $res['msg'] = L('保存成功');
            $res['data'] = [];
        } catch (\Exception $e) {
            M()->rollback();
            $res['code'] = 3000;
            $res['msg'] = L($e->getMessage());
            $res['data'] = [];
        }
        RedisLock::unlock();
        return $res;
    }


}
