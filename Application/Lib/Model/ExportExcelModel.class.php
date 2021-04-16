<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2017/10/19
 * Time: 14:10
 * XLS 文档导出
 */

class ExportExcelModel
{
    public $data;
    public $title;
    public $fileName;
    public $phpExcel;
    public $sheetIndex = 0; // 操作的页,实际纸张为1，不可跨界操作
    public $sheetObject; // 页对象，通过设置的 sheetIndex 对象，获取当前页的操作权限
    public $startLine = 2;
    public $columns;
    public $attributes = [
        'A' => ['name' =>'SKU编码', 'field_name' => 'SKU_ID'],
        'B' => ['name' => '条形码', 'field_name' => 'GUDS_OPT_UPC_ID'],
        'C' => ['name' => '商品名称', 'field_name' => 'GUDS_NM'],
        'D' => ['name' => '属性', 'field_name' => 'GUDS_OPT_VAL_MPNG'],
        'E' => ['name' => '仓库', 'field_name' => 'DELIVERY_WAREHOUSE'],
        'F' => ['name' => '批次号', 'field_name' => 'batch_code'],
        'G' => ['name' => '所属公司', 'field_name' => 'our_company'],
        'H' => ['name' => '销售团队', 'field_name' => 'sale_team_code'],
        'I' => ['name' => '采购单号', 'field_name' => 'purchase_order_no'],
        'J' => ['name' => '采购团队', 'field_name' => 'purchase_team_code'],
        'K' => ['name' => '采购时间', 'field_name' => 'procurement_date'],
        'L' => ['name' => '入库时间', 'field_name' => 'ct_time'],
        'M' => ['name' => '在库天数', 'field_name' => 'existed_days'],
        'N' => ['name' => '到期日', 'field_name' => 'pd'],
        'O' => ['name' => '在库库存', 'field_name' => 'total_inventory'],
        'P' => ['name' => '可售', 'field_name' => 'available_for_sale_num'],
        'Q' => ['name' => '占用', 'field_name' => 'sizeAll'],
        'R' => ['name' => '锁定', 'field_name' => 'locksale'],
        'S' => ['name' => '批次单价', 'field_name' => 'unit_price'],
        'T' => ['name' => '批次成本', 'field_name' => 'all_total']
    ];
    private $_error;
    private $_headers;

    public function __construct()
    {
        vendor("PHPExcel.PHPExcel");
        ini_set('max_execution_time', 1800);
        $this->phpExcel = new PHPExcel();
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_discISAM;
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod);
        if (!is_object($this->phpExcel)) $this->_error = 100;
        $this->defaultConfig();
        if (!$this->_headers) $this->_headers = new HeaderCollection();
    }

    /**增加一个活动表
     * @param $title
     * @throws PHPExcel_Exception
     */
    public function addSheet($title)
    {
        $this->phpExcel->createSheet(++$this->sheetIndex);
        $this->sheetObject = $this->phpExcel->setActiveSheetIndex($this->sheetIndex);
        $this->sheetObject->setTitle($title);
        $this->startLine = 2;
    }

    /**
     * 首行标题
     *
     */
    public function setMainTitle()
    {
        if ($this->title) {
            $maxColumn = count($this->columnName());
            $this->sheetObject->setCellValue('A1', $this->title);
            $this->sheetObject->mergeCells('A1:'. chr(64 + $maxColumn) .'1');
            $this->phpExcel->getActiveSheet()->getStyle('A1')->applyFromArray($this->setMainTitleStyle());
        }
    }

    /**
     * 标题样式设置
     *
     */
    public function setMainTitleStyle()
    {
        return [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            ]
        ];
    }

    /**
     * 列标题设置
     *
     */
    public function setColumnTitle()
    {
        if ($this->title) {
            $this->startLine = 3;
        }
        $index = $this->startLine - 1;
        foreach ($this->columnName() as $key => $value) {
            $this->sheetObject->setCellValue($key . $index, $value ['name']);
        }
    }

    /**
     * 文件名
     *
     */
    public function setFileName()
    {
        if ($this->fileName) {
            $fileName = iconv('utf-8', 'gb2312', $this->fileName);
            $this->fileName = $fileName;
        } else {
            $this->fileName = date('_YmdHis');
        }
    }

    /**
     * 默认设置
     *
     */
    public function defaultConfig()
    {
        $this->sheetObject = $this->phpExcel->setActiveSheetIndex($this->sheetIndex);
        // 全部设置为文本格式
//        foreach ($this->columnName() as $k => $v) {
//            $this->phpExcel->getActiveSheet()->getStyle($k)->getNumberFormat()
//                ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
//        }
        $this->sheetObject->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    }

    /**
     * 列名，与数据绑定
     * @return 返回与数据相对应的列标题名称
     */
    public function columnName()
    {
        return $this->attributes;
    }

    /**
     * 数据处理，将数据与列名绑定，写入文件
     * $objPHPExcel->getActiveSheet()->getStyle('B'.$j)->getNumberFormat()->setFormatCode("@"); 设置为 String
     */
    public function parseData()
    {
        $width = [];
        if (!$this->data) {
            throw new PHPExcel_Writer_Exception(101);
            return false;
        }
        $columns = $this->columnName();
        foreach ($this->data as $key => $value) {
            $index = 0;
            foreach ($columns as $k => $v) {
                $this->sheetObject->setCellValueExplicit($k . $this->startLine, is_array($value[$v['field_name']])?implode(',', $value[$v['field_name']]):$value[$v['field_name']], PHPExcel_Cell_DataType::TYPE_STRING);
                if (strlen($value[$v['field_name']]) + 5 > $width [$k]) {
                    $width [$k] = strlen($value[$v['field_name']]) + 5;
                    $this->sheetObject->getColumnDimension($k)->setWidth(strlen($value[$v['field_name']]) + 5);
                }
                $index ++;
            }
            $this->startLine ++;
        }
    }

    /**
     * Error map
     *
     */
    private function _errorMap()
    {
        return [
            100 => L('初始化 EXCEL 组件失败'),
            101 => L('未设置数据'),
        ];
    }

    /**
     * Error Message
     *
     */
    public function getError()
    {
        return $this->_errorMap() [$this->_error]?$this->_errorMap() [$this->_error]:$this->_error;
    }

    public function sendHeaders()
    {
        $this->setHeaders();
        if ($this->_headers) {
            $headers = $this->_headers;
            foreach ($headers as $name => $values) {
                $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
                // set replace for first occurrence of header but false afterwards to allow multiple
                $replace = true;
                foreach ($values as $value) {
                    header("$name: $value", $replace);
                    $replace = false;
                }
            }
        }
    }

    public function setHeaders()
    {
        if ($this->_headers) {
            $this->_headers->setDefault('Content-Type', 'applicationnd.ms-excel');
            $this->_headers->setDefault('Content-Disposition', $this->getDispositionHeaderValue('attachment', $this->fileName));
            $this->_headers->setDefault('Cache-Control', 'max-age=0');
        }
    }

    protected function getDispositionHeaderValue($disposition, $attachmentName)
    {
        $dispositionHeader = "{$disposition}; filename=\"{$attachmentName}\".xls";
        return $dispositionHeader;
    }

    public $threshold = 20000;
    /**
     * 生成 EXCEL 文件
     *
     */
    public function export($savePath = 'php://output')
    {
        if (count($this->data) < $this->threshold) {
            try {
                $this->setMainTitle();
                $this->setColumnTitle();
                $this->parseData();
                $this->setFileName();
                // send header
                $this->sendHeaders();
                // send content
                $objWriter = PHPExcel_IOFactory::createWriter($this->phpExcel, 'Excel5');
                $objWriter->save($savePath);
            } catch (PHPExcel_Writer_Exception $e) {
                $this->_error = $e->getMessage();
            }
        } else {
            $this->mosaicData();
            $this->save($savePath);
        }
    }

    public $html;

    /**
     * 数据拼接
     */
    public function mosaicData()
    {
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= $this->setHead();
        $html .= $this->setBody();
        $html .= '</html>';
        $this->html = $html;
    }

    /**
     * 样式设置
     */
    public function setStyle()
    {
        $style = '<style id="Classeurl_16681_Styles"></style>';

        return $style;
    }

    /**
     * 设置
     */
    public function setHead()
    {
        $head = '<head>';
        $head .= $this->setStyle();
        $head .= '</head>';
        return $head;
    }

    /**
     * 标题设置
     * @return null|string
     */
    public function setTh()
    {
        $th = null;
        $th .= '<tr height="35">';
        foreach ($this->columnName() as $key => $value) {
            $th .= '<th style="border-right: 1px solid #515151; border-bottom: 1px solid #515151;">' . $value ['name'] . '</th>';
        }
        $th .= '</tr>';
        return $th;
    }

    /**
     * 内容设置
     * @return null|string
     */
    public function setContent()
    {
        $columns = $this->columnName();
        $content = null;
        foreach ($this->data as $key => $value) {
            $index = 0;
            $content .= '<tr height="28" style="background: #fff;">';
            foreach ($columns as $k => $v) {
                $content .= '<td style="border-right: 1px solid #515151; border-bottom: 1px solid #515151;">';
                $content .= $value [$v ['field_name']];
                $content .= '</td>';
                $index ++;
            }
            $content .= '</tr>';
        }
        unset($this->data);
        return $content;
    }

    /**
     * 设置table
     */
    public function setTable()
    {
        $table = '<table cellspace="1" cellspading="0" border="0" style="background:white">';
        $table .= $this->setTh();
        $table .= $this->setContent();
        $table .= '</table>';

        return $table;
    }

    /**
     * 设置body
     * @return string
     */
    public function setBody()
    {
        $body = '<body>';
        $body .= '<div id="Classeurl_16681" align="center" x:publishsource="Excel">';
        $body .= $this->setTable();
        $body .= '</div>';
        $body .= '</body>';

        return $body;
    }

    /**
     * 写入文件
     * @param string $fName 文件名
     * @return bool
     */
    public function save($fName = 'php://output')
    {
        $this->setFileName();
        $this->sendHeaders();
        if (is_resource($fName)) {
            $handler = $fName;
        } elseif ($fName == '' or $fName == '-') {
            $tmpName = tempnam('/tmp', 'tmp.xls');
            $handler = fopen($tmpName, 'w+b');
        } else
            $handler = fopen($fName, 'wb');
        if (is_resource($handler))
            fwrite($handler, $this->html);
        fclose($handler);
        if (!is_resource($fName)) {
            fclose($handler);
        }
        return true;
    }
}

class HeaderCollection implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var array the headers in this collection (indexed by the header names)
     */
    private $_headers = [];

    public function getIterator()
    {
        return new ArrayIterator($this->_headers);
    }

    public function count()
    {
        return $this->getCount();
    }

    public function getCount()
    {
        return count($this->_headers);
    }

    public function get($name, $default = null, $first = true)
    {
        $name = strtolower($name);
        if (isset($this->_headers[$name])) {
            return $first ? reset($this->_headers[$name]) : $this->_headers[$name];
        }

        return $default;
    }

    public function set($name, $value = '')
    {
        $name = strtolower($name);
        $this->_headers[$name] = (array) $value;

        return $this;
    }

    public function add($name, $value)
    {
        $name = strtolower($name);
        $this->_headers[$name][] = $value;

        return $this;
    }

    public function setDefault($name, $value)
    {
        $name = strtolower($name);
        if (empty($this->_headers[$name])) {
            $this->_headers[$name][] = $value;
        }

        return $this;
    }

    public function has($name)
    {
        $name = strtolower($name);

        return isset($this->_headers[$name]);
    }

    public function remove($name)
    {
        $name = strtolower($name);
        if (isset($this->_headers[$name])) {
            $value = $this->_headers[$name];
            unset($this->_headers[$name]);
            return $value;
        }

        return null;
    }

    public function removeAll()
    {
        $this->_headers = [];
    }

    public function toArray()
    {
        return $this->_headers;
    }

    public function fromArray(array $array)
    {
        $this->_headers = $array;
    }

    public function offsetExists($name)
    {
        return $this->has($name);
    }

    public function offsetGet($name)
    {
        return $this->get($name);
    }

    public function offsetSet($name, $value)
    {
        $this->set($name, $value);
    }

    public function offsetUnset($name)
    {
        $this->remove($name);
    }
}