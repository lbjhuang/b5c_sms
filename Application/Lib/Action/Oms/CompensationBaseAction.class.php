<?php

class CompensationBaseAction extends BaseAction
{

    public function __construct()
    {
        parent::__construct();

    }

    public function getException($e, $model = null)
    {
        if ($model) $model->rollback();
        $this->ajaxError([], $e->getMessage());
    }


}