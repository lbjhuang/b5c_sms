<?php

/**
 * User: yangsu
 * Date: 18/12/17
 * Time: 18:04
 */
class WhereModel extends Model
{

    /**
     * @param array $request_data
     * @param array $search_map
     * @param array $where
     * @param array $search_accurate_arr 用于精确匹配，如果搜索字段不在该数组里，默认是模糊匹配
     * @return array
     */
    public static function joinSearchTemp(array $request_data, array $search_map, array $where = [], array $search_accurate_arr = [])
    {
        if ($request_data['search']) {
            foreach ($request_data['search'] as $key => $value) {
                if (((is_string($value) && '' != $value) || (is_array($value) && !empty($value))) && $search_map[$key]) {
                    switch ($key) {
                        case 'created_at':
                        case 'updated_at':
                        case 'verification_at':
                        case 'start_reminding_date_receipt':
                        case 'payable_date_after':
                        case 'end_at':
                            $where = self::getBetweenDate($value['start'], $value['end'], $where, $search_map[$key]);
                            break;
                        default:
                            if (is_array($value)) {
                                $where[$search_map[$key]] = ['IN', $value];
                            } else {
                                if (($search_accurate_arr && in_array($key, $search_accurate_arr)) || isset($search_accurate_arr['all'])) {
                                    $where[$search_map[$key]] = $value;
                                } else {
                                    $where[$search_map[$key]] = ['LIKE', "%{$value}%"];
                                }
                            }
                    }
                }
            }
        }
        $limit = self::joinPage($request_data);
        return [$where, $limit];
    }

    /**
     * @param array $data
     * @return string
     */
    public static function arrayToInString(array $data)
    {
        $temp_data = array_values($data);
        $temp_string = implode("','", $temp_data);
        $temp_string = "'" . trim($temp_string, "','") . "'";
        return $temp_string;
    }

    public static function stringToInArray($string)
    {
        return explode(',', $string);
    }


    /**
     * @param $start_date
     * @param $end_date
     * @param $where
     * @param $date_key
     * @return mixed
     */
    public static function getBetweenDate($start_date, $end_date, $where, $date_key)
    {
        if (!empty($start_date) && !empty($end_date)) {
            $start_date = DateModel::toYmd($start_date) . ' 00:00:00';
            $end_date = DateModel::toYmd($end_date) . ' 23:59:59';
            $where[$date_key] = array('BETWEEN', array($start_date, $end_date));
        } elseif (empty($start_date) && !empty($end_date)) {
            $end_date = DateModel::toYmd($end_date) . ' 23:59:59';
            $where[$date_key] = array('ELT', $end_date);
        } elseif (empty($end_date) && !empty($start_date)) {
            $start_date = DateModel::toYmd($start_date) . ' 00:00:00';
            $where[$date_key] = array('EGT', $start_date);
        }
        return $where;
    }

    /**
     * @param array $request_data
     * @return array
     */
    private static function joinPage(array $request_data)
    {
        if (!$request_data['pages'] && $request_data['page']) {
            $request_data['pages'] = $request_data['page'];
        }
        if ($request_data['pages']) {
            $limit = [($request_data['pages']['current_page'] - 1) * $request_data['pages']['per_page'], $request_data['pages']['per_page']];
        } else {
            $limit = [0, 10];
        }
        return $limit;
    }
    
    public static function joinSearchStr(array $request_data, array $search_map, $where = "", array $search_accurate_arr = [])
    {
        if ($request_data['search']) {
            foreach ($request_data['search'] as $key => $value) {
                if (((is_string($value) && '' != $value) || (is_array($value) && !empty($value))) && $search_map[$key]) {
                    switch ($key) {
                        case 'created_at':
                            $where .= self::getBetweenDateStr($value['start'], $value['end'], '', $search_map[$key]);
                            break;
                        case 'updated_at':
                            $where .= self::getBetweenDateStr($value['start'], $value['end'], '', $search_map[$key]);
                            break;
                        case 'verification_at':
                            $where .= self::getBetweenDateStr($value['start'], $value['end'], '', $search_map[$key]);
                            break;
                        default:
                            if (is_array($value)) {
                                $condition = "";
                                foreach ($value as $handle) {
                                    $condition .= "'{$handle}'".',';
                                }
                                $condition = trim($condition, ',');
                                $where .= $search_map[$key] . ' IN ('.$condition . ') AND ';
                            } else {
                                if (($search_accurate_arr && in_array($key, $search_accurate_arr)) || isset($search_accurate_arr['all'])) {
                                    $where .= $search_map[$key] . ' = \''.$value. '\' AND ';
                                }
                            }
                    }
                }
            }
        }
        $where = trim($where, 'AND ');
        $limit = self::joinPage($request_data);
        return [$where, $limit];
    }
    
    public static function getBetweenDateStr($start_date, $end_date, $where, $date_key)
    {
        if (!empty($start_date) && !empty($end_date)) {
            $start_date .= ' 00:00:00';
            $end_date .= ' 23:59:59';
            $where = $date_key .' BETWEEN '."'$start_date'". ' AND '. "'$end_date'" . ' AND ';
        } elseif (empty($start_date) && !empty($end_date)) {
            $end_date .= ' 23:59:59';
            $where = $date_key .' <= '."'$end_date'" . ' AND ';
        } elseif (empty($end_date) && !empty($start_date)) {
            $start_date .= ' 00:00:00';
            $where = $date_key .' >= '."'$start_date'" . ' AND ';
        }
        return $where;
    }

    public static function arrWhereToStr($where)
    {
        $str_where = '';
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                switch (strtoupper($value[0])) {
                    case 'BETWEEN':
                        $str_where .= "($field $value[0] '{$value[1][0]}' and '{$value[1][1]}') and ";
                        break;
                    default:
                        $str_where .= "$field $value[0] ". " ('" . join("','", array_values($value[0]) ) . "') and ";
                        break;
                }
            } else if ($field == '_string') {
                $str_where .= "$value and ";
            }else {
                $str_where .= "$field = '{$value}' and ";
            }
        }
        return trim($str_where, 'and ');
    }

}
