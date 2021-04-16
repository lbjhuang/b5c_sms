<?php
/**
 * User: yuanshixiao
 * Date: 2018/4/26
 * Time: 17:41
 */

class ToolAction extends Action
{
    /**
     * 导出表信息到csv，表名用tables传参，多个表格用','分割
     */
    public function export_table() {
        $tables = explode(',',I('request.tables'));
        $fileName = 'table.csv';
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        print(chr(0xEF).chr(0xBB).chr(0xBF));
        $fp = fopen('php://output', 'a');
        foreach ($tables as $v) {
            fputcsv($fp, [$v]);
            $sql = 'SHOW FULL COLUMNS FROM '.$v;
            $res = M()->query($sql);
            foreach ($res as $val) {
                $row = [];
                $row[] = $val['Field'];
                $row[] = $val['Type'];
                $row[] = $val['Key']?'YES':'NO';
                $row[] = $val['Null'];
                $row[] = $val['Default'];
                $row[] = $val['Comment'];
                fputcsv($fp, $row);
            }
            fputcsv($fp, []);
        }
    }

    /**
     * 导出表信息为文档格式，表名用tables传参，多个表格用','分割
     */
    public function export_table_doc() {
        $tables = explode(',',I('request.tables'));
        foreach ($tables as $v) {
            $sql = 'SHOW FULL COLUMNS FROM '.$v;
            $res = M()->query($sql);
            echo "$v\n";
            echo "-  表说明\n\n";
            echo "|字段|类型|空|默认|key|注释|\n";
            echo "|:----    |:-------    |:---|:--- |-- -|------      |\n";
            foreach ($res as $val) {
                echo "|{$val['Field']}    |{$val['Type']}    |{$val['Null']}    |{$val['Default']}    |".($val['Key']?'YES':'NO')."   |{$val['Comment']}    |\n";
            }
            echo "\n-  备注：无\n\n";
        }
    }


    /**
     * 导出表信息为文档格式，表名用tables传参，多个表格用','分割
     */
    public function export_table_explain_doc() {
        $tables = explode(',',I('request.tables'));
        foreach ($tables as $v) {
            $sql = 'SHOW FULL COLUMNS FROM '.$v;
            $res = M()->query($sql);
            echo "**{$v}字段参数说明**\n\n";
            echo "|参数名|类型|说明|\n";
            echo "|:----    |-- -|------      |\n";
            foreach ($res as $val) {
                echo "|{$val['Field']}    |string    |{$val['Comment']}    |\n";
            }
            echo "\n";
        }
    }

    /**
     * 导出表信息为json格式，表名用tables传参，多个表格用','分割
     */
    public function export_table_json() {
        $tables = explode(',',I('request.tables'));
        foreach ($tables as $v) {
            $columns = [];
            $sql = 'SHOW FULL COLUMNS FROM '.$v;
            $res = M()->query($sql);
            echo "$v\n";
            echo "-  表转json\n\n";
            foreach ($res as $val) {
                $columns[$val['Field']] = "value";
            }
            echo json_encode($columns,JSON_PRETTY_PRINT);
        }
    }
}