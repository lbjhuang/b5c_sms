<?php
/**
 * User: yangsu
 * Date: 18/10/17
 * Time: 16:49
 */

class Repository
{
    /**
     * @var Model|string
     */
    public $model = stdClass::class;
    /**
     * @var Model|string
     */
    public $external_model = stdClass::class;

    /**
     * Repository constructor.
     *
     * @param null $external_model
     */
    public function __construct($external_model = null)
    {
        if ($external_model) {
            $this->model = $external_model;
        } else {
            $this->model = new Model();
        }
    }

    /**
     * @param $paginateper_page
     * @param $current_page
     * @param $data_count
     * @param $data
     *
     * @return mixed
     */
    protected function joinPage($paginateper_page, $current_page, $data_count, $data)
    {
        $data['total'] = $data_count;
        $data['current_page'] = $current_page;
        $data['per_page'] = $paginateper_page;
        $data['last_page'] = $data['total'] / $data['per_page'];
        return $data;
    }

    public function objToArray($data)
    {
        if (empty($data) || !is_object($data)) {
            return $data;
        }
        return $data->toArray();
    }
}