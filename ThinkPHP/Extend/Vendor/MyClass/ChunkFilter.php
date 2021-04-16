<?php
/**
 * Created by PhpStorm.
 * User: afanti
 * Date: 2017/9/5
 * Time: 16:49
 */
/**
 * 分块读取类
 *
 */
class ChunkFilter implements PHPExcel_Reader_IReadFilter
{
    private $_startRow = 0;     // 开始行
    private $_endRow = 0;       // 结束行
    public function __construct($startRow, $chunkSize) {    // 我们需要传递：开始行号&行跨度(来计算结束行号)
        $this->_startRow = $startRow;
        $this->_endRow       = $startRow + $chunkSize;
    }
    
    public function setRows($startRow, $chunkSize) {
        $this->_startRow = $startRow;
        $this->_endRow       = $startRow + $chunkSize;
    }
    
    public function getEndRow()
    {
        return $this->_endRow;
    }
    
    public function getStartRow()
    {
        return $this->_startRow;
    }
    
    public function readCell($column, $row, $worksheetName = '') {
        if (($row == 1) || ($row >= $this->_startRow && $row < $this->_endRow)) {
            return true;
        }
        return false;
    }
}