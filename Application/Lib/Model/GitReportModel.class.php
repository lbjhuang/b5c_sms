<?php
/**
 * Created by PhpStorm.
 * User: due
 * Date: 2018/8/9
 * Time: 16:35
 */

class GitReportModel extends Model
{
    public static function getData($data)
    {
        $data['git'] = self::gitData();
        $data['report'] = self::reportData($data);
        return $data;
    }

    public static function gitData()
    {
        $sql = "SELECT
                 thu.department,
                 thu.`group`,
                 thu.title position,
                 wus.`name` emp_sc_nm,
                 thu.workcode,
                 wus.location work_place,
                 thg.last_week commit_count,
                 thg. WEEK commit_count2,
                 CONCAT(thu.jobtime, '') jobtime
                FROM
                 tb_hr_git thg
                LEFT JOIN tb_hr_empl the ON the.id = thg.uid
                LEFT JOIN tb_hr_oa thu ON thu.uid = thg.uid
                LEFT JOIN tb_wework_user_source wus ON wus.email = the.SC_EMAIL
                WHERE
                 wus.`status` = 1
                AND wus.`enable` = 1
                AND wus.deleted = 0
                ORDER BY
                 wus.location DESC";
        $time_area = self::getTimeArea();
        $data['start'] = date('Y-m-d', $time_area[0]);
        $data['end'] = date('Y-m-d', $time_area[1]);
        $data['list'] = M()->query($sql);
        return $data;
    }


    public static function alertEmail($content)
    {
        $to = 'due@gshopper.com';
        $title = '员工GIT代码提交次数 & 员工日报缺失汇总';
        $mail = new SMSEmail();
        $mail->sendEmail($to, $title, $content);
    }

    public static function biReportData()
    {
        $time_area = self::getTimeArea();
        $start = date('Ymd', $time_area[0]);
        $end = date('Ymd', $time_area[1]);
        $tmp = json_decode(HttpTool::curlGet("http://3rd-biapi.izene.org/external/getDailyReportInfo?startDate={$start}&endDate={$end}&userId=&empName="), true);
        if (!$tmp) {
            self::alertEmail('bi接口异常');
            throw new Exception('bi接口异常');
        }
        $tmp = $tmp['data'];
        foreach ($tmp as $k => $t) {
            $lizhi = M('hr_card', 'tb_')
                ->where(['EMP_SC_NM' => $t['emp_name']])
                ->getField('STATUS');
            if ($lizhi == '离职') {
                unset($tmp[$k]);
            }
        }
        return $tmp;
    }

    public static function getTimeArea()
    {
        $start_time = time() - 6 * 86400 - date('w') * 86400;
        $end = $start_time + 6 * 86400;
        return [$start_time, $end];
    }

    private static function reportData($data)
    {
        $start = $data['start'] ?: date('Y-m-d', strtotime('-2 monday'));
        $end = $data['end'] ?: date('Y-m-d');
        $page = $data['page'] ?: 1;
        $emp_sc_nm = $data['emp_sc_nm'] ?: '';
        $page_size = $data['page_size'] ?: 10;
        $offset = ($page - 1) * $page_size;
        $where = ['hdr.delete' => 0,
            'wus.status' => 1,
            'wus.enable' => 1,
            'wus.deleted' => 0,
            'the.STATUS' => '在职',
            'hdr.drdate' => [['egt', $start], ['elt', $end]]];
        if (!empty($emp_sc_nm)) {
            $where['the.EMP_SC_NM'] = ['like', '%' . $emp_sc_nm . '%'];
        }
        $model = M('hr_dr_report', 'tb_');
        $query = $model->alias('hdr')
            ->field('hdr.id,hdr.uid,hed.ID1,hed.PERCENT,card.JOB_CD,hd.DEPT_NM,hd.DEPT_NM,hdp.DEPT_NM as PAR_DEPT_NM, card.DEPT_GROUP, wus.email, thu.title position, the.EMP_SC_NM emp_sc_nm, thu.workcode, thu.base work_place, card.WORK_NUM, card.WORK_PALCE, hdr.drdate, hdr.remark')
            ->join('LEFT JOIN tb_hr_empl the ON the.ID = hdr.uid')
            ->join('LEFT JOIN tb_hr_oa thu ON thu.uid = hdr.uid')
            ->join('LEFT JOIN tb_hr_card card ON card.EMPL_ID = hdr.uid')
            ->join('LEFT JOIN tb_hr_empl_dept hed ON hed.ID2 = hdr.uid')
            ->join('LEFT JOIN tb_hr_dept hd ON hd.ID = hed.ID1')
            ->join('LEFT JOIN tb_hr_dept hdp ON hdp.ID = hd.PAR_DEPT_ID')
            ->join('LEFT JOIN tb_wework_user_source wus ON wus.email = the.SC_EMAIL')
            ->where($where)
            ->order('hdr.drdate DESC');
        $result['start'] = $start;
        $result['end'] = $end;
        $q = clone $query;
        $result['total'] = $q->count();
        $result['list'] = $query->limit("{$offset},{$page_size}")->select();

        $department_list = D('TbHrDept')->dept_info();

        foreach ($result['list'] as $k => &$v) {

            $result['list'][$k] = array_merge($v,$department_list[$v['ID1']]);
        }


        return $result;
    }

    public static function getUserInfo($nm)
    {
        $model = M('hr_card', 'tb_');
        $info = $model->field('EMP_SC_NM,WORK_NUM,DEPT_NAME,JOB_CD,DEPT_GROUP,WORK_PALCE')
            ->where(['EMP_SC_NM' => $nm])
            ->find();
        return $info;
    }

    public static function updateReport($data)
    {
        $delete = $data['delete'];
        $update = $data['update'];
        $model = M('hr_dr_report', 'tb_');
        if ($delete) {
            return $model->where(['id' => ['in', $delete]])->save(['delete' => 1]) !== false;
        } else {
            $model->startTrans();
            try {
                foreach ($update as $v) {
                    if ($model->where(['id' => $v['id']])->save(['remark' => $v['remark']]) === false) {
                        throw new \Exception('修改失败');
                    }
                }
                $model->commit();
                return true;
            } catch (\Exception $e) {
                $model->rollback();
                return false;
            }
        }
    }

    public static function genReport($bi_report)
    {
        $report = [];
        foreach ($bi_report as $v) {
            $tmp = [];
            $info = self::getUserInfo($v['emp_name']);
            $tmp['DEPT_NAME'] = $v['dept_name'];
            $tmp['DEPT_GROUP'] = $info['DEPT_GROUP'];
            $tmp['JOB_CD'] = $v['title'];
            $tmp['EMP_SC_NM'] = $v['emp_name'];
            $tmp['WORK_NUM'] = $v['user_id'];
            $tmp['WORK_PALCE'] = $info['WORK_PALCE'];
            $tmp['UNREPORTED'] = $v['unreport_dt'] ? substr_count($v['unreport_dt'], ',') + 1 : 0;
            $tmp['CHECKED_UNREPORTED'] = '';
            $tmp['UNREPORTED_AT'] = $v['unreport_dt'];
            $tmp['REASON'] = '';
            $tmp['RESULT'] = '';
            $report[] = $tmp;
        }
        return $report;
    }

    /**处理日报记录，用于发送邮件
     *
     * @param $report
     *
     * @return array
     */
    public static function handleReport($report)
    {
        $loss = [];
        $loss_count = 0;
        $err = [];
        $err_count = 0;
        foreach ($report as $v) {
            if ($v['CHECKED_UNREPORTED'] > 0) {
                $loss[] = $v;
                $loss_count++;
            } else {
                $err[] = $v;
                $err_count++;
            }
        }
        return ['loss' => $loss, 'loss_count' => $loss_count, 'err' => $err, 'err_count' => $err_count];
    }
}