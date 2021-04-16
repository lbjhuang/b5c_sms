<?php

/**
 * User: yuanshixiao
 * Date: 2017/6/27
 * Time: 13:28
 */
class CommonFileAction extends BaseAction
{
    public function download()
    {
        $name = I('get.name');
        import('ORG.Net.Http');
        $filename = substr(T($name),0,-5);
        Http::download($filename, $filename);
    }
}