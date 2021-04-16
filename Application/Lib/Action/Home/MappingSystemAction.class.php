<?php

/**
 * User: yangsu
 * Date: 19/1/4
 * Time: 13:33
 */


class MappingSystemAction extends BaseAction
{
    private $internal = [
        'ERP',
        'PMS',
        'CMS',
    ];

    public function __construct()
    {
        parse_str($_SERVER['QUERY_STRING'], $result);
        $BbmNode = new BbmNodeModel();
        $node_info = $BbmNode->getNodeInfo(DataModel::camelize($result['m']), $result['a']);
        $host = null;
        if (in_array($node_info['mapping_system'], $this->internal)) {
            $host = constant($node_info['mapping_system'] . "_HOST");
        }
        return $this->interpretationReturnType($node_info, $host);
        die('Incorrect request');
    }

    private function interpretationReturnType($node_info, $host = null)
    {
        $url = $this->assemblyUrl($node_info, $host);
        switch ($node_info['TYPE']) {
            case 0:
                header("Location:$url");
                /*$html_cont = file_get_contents($url);
                $html_cont = str_replace('<html>', '<html style="height: 100%; overflow: auto;">', $html_cont);
                echo $html_cont;*/
                die();
                break;
            case 1:
                return file_get_contents($url);
                break;
        }
    }

    /**
     * @param $node_info
     * @param $host
     * @return string
     */
    private function assemblyUrl($node_info, $host)
    {
        $url = "{$host}{$node_info['mapping_url']}";
        switch ($node_info['ACT']) {
            case 'chandaoTool':
                $parameter = '/user/' . DataModel::userHuaMing();
                break;
            default:
                $parameter = null;
        }
        return $url . $parameter;
    }

}

