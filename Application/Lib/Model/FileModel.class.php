<?php
/**
 * User: yangsu
 * Date: 18/11/5
 * Time: 20:25
 */


class FileModel extends Model
{
    static $BASE_PATH = '/opt/b5c-disk/excel';
    
    public static function excelToArray($file_path, array $receive_map)
    {
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($file_path)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($file_path)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($file_path);
        $sheet = $PHPExcel->getSheet(0);   //获取第一个sheet
        $res = self::dataJoin($PHPExcel, $sheet->getHighestRow(), $receive_map);
        $destination = self::uploadExcelFile($file_path, $res);
        return [$res, $destination];

    }

    public static function dataJoin($PHPExcel, $allRow, $array_key)
    {
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {    //从第二行数据开始读
            foreach ($array_key as $key => $value) {
                $res[$key] = $PHPExcel->getActiveSheet()->getCell($value . $currentRow)->getValue();
                if (is_object($res[$key])) {
                    $res[$key] = (string)$res[$key];
                }
                if (strpos($res[$key], 'VLOOKUP') !== 0) {
                    // $res[$key] = $PHPExcel->getActiveSheet()->getCell($value . $currentRow)->getCalculatedValue();
                }
            }
            $res_arr[] = $res;
        }
        return $res_arr;
    }

    /**
     * @param $file_path
     * @param $excel_count
     */
    private static function uploadExcelFile($file_path, $excel_count, $file_name_prefix)
    {
        $user = session('m_loginname');
        $file_name = $user . '_logisics_' . date("YmdHis", time());
        if (!empty($excel_count)) {
            $base_path = self::$BASE_PATH;
            if (!file_exists($base_path)) {
                mkdir($base_path);
            }
            if (!empty($file_name_prefix)) {
                if (!file_exists($base_path. '/'. $file_name_prefix)) {
                    mkdir($base_path. '/'. $file_name_prefix);
                }
                $name = $user . '_' . $file_name_prefix. '_' . date("YmdHis", time());
                $file_name = $file_name_prefix. '/'. $name;
            }
            $destination = $base_path. '/'. $file_name . '.xlsx';
            move_uploaded_file($file_path, $destination);
        }
        return $destination;
    }
    
    /**
     * 从img目录复制excel文件到excel目录
     * @param string $file_path img目录下的excel路径
     * @param string $file_name  img目录下的excel文件名
     */
    public static function copyExcel($file_path, $file_name) {
        $user_name = session('m_loginname');
        $data = file_get_contents($file_path);
        if (!file_exists(self::$BASE_PATH)) {
            mkdir(self::$BASE_PATH);
        }
        $file_name = self::$BASE_PATH. '/'. $user_name. '_'. $file_name;
        return file_put_contents($file_name, $data);
    }


    /**
     * 从img目录复制excel文件到excel目录
     * @param string $file_path img目录下的excel路径
     * @param string $file_name  img目录下的excel文件名
     */
    public static function copyExcel2($file_path, $file_name) {
        $data = file_get_contents($file_path);
        if (!file_exists(self::$BASE_PATH)) {
            mkdir(self::$BASE_PATH);
        }
        $file_name = self::$BASE_PATH. '/' . $file_name;
        return file_put_contents($file_name, $data);
    }
}