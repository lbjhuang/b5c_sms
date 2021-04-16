<?php

class  CrmAction extends BaseAction
{
    //菜单列表
    public  function  CrmList()
    {
        $this->display();
    }
    //详情
    public  function  CrmDetail()
    {
        $this->display();
    }
    //日志
    public  function  crmloglist()
    {
        $this->display();
    }
     /**
     * 下载模板文件
     */
    public function downloadTemplate()
    {
        $name = 'crm_template.xlsx';
        import('ORG.Net.Http');
        $filename = APP_PATH . 'Tpl/Crm/Crm/' . $name;
        Http::download($filename, $filename);
    }
}