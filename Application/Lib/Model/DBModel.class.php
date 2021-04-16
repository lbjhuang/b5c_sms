<?php
/**
 * User: yangsu
 * Date: 18/8/13
 * Time: 15:31
 */


class DBModel extends RelationModel
{
    /**
     * @var string
     */
    protected $trueTableName = '';
    /**
     * @var array
     */
    public $where = [];
    public $this_page = 0;
    public $page_count = 10;
    /**
     * @var string
     */
    public $where_string = null;
    /**
     * @var string
     */
    public $field = '';

    /**
     * @return mixed
     */
    public function getAll()
    {
        $res = $this
            ->field($this->field)
            ->where($this->where)
            ->select();
        return $res;
    }
}