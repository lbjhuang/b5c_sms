<?php
/*
 *
 */

class HelpAction extends Action
{
    public function halfYearLater()
    {
        $date = $_POST['date'];
        $half_year_later = null;
        if ($date) {
            $put_off_month = 6;
            $half_year_later = date("Y-m-d", strtotime("+$put_off_month month -1 day", strtotime($date)));
        }
        $this->assign('half_year_later', $half_year_later);
        $this->assign('date', $date);
        $this->display('half_year_later');
    }

    public function calculateMinute()
    {
        $deduciton = 0;
        $act = $_POST['act'];
        $end = $_POST['end'];
        if ($act && $end) {
            $default = date('Y-m-d');
            $request_act = strtotime($default . ' ' . $act);
            $request_end = strtotime($default . ' ' . $end);
            $rest_act = strtotime($default . ' 11:30');
            $rest_end = strtotime($default . ' 13:00');

            $morning_duration = ($rest_act - $request_act) / 60;
            $afternoon_duration = ($request_end - $rest_end) / 60;
            $duration = $morning_duration + $afternoon_duration;
            $rest_minute = 1.5 * 60;
            if ($morning_duration >= 0 && $afternoon_duration >= 0) {
            } elseif ($morning_duration < 0 || $afternoon_duration < 0) {
                $duration += $rest_minute;
            } else {
                $duration -= $rest_minute;
            }
        }
        $this->assign('act', $act);
        $this->assign('end', $end);
        $this->assign('duration', $duration);
        $this->display('calculate_minute');
    }

    public function showDoc()
    {
        $this->display('show_doc');
    }
}