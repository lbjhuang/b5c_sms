<?php
/**
 * 收款流水录入(压缩包导入)
 * User: fuming
 * Date: 2018/12/26
 */

class PackageImportCollectionModel extends BaseImportPackageModel
{
    protected $images = [];
    protected $images_path = [];
    public $error_info;
    public $success_info = ['code' => 2000, 'msg' => '操作成功'];

    public function import() {
        try {
            //解压
            $content = $this->unPack();
        } catch (\Exception $e) {
           $this->setError($e->getMessage(),'3003');
           return false;
        }

        if (!$this->checkUnzipFormat($content)) {
            //判断解压后的格式
            return false;
        }
        $excel_path = $this->getUnPackExcelPath($content);//获取解压后的excel路径
        if(!$excel_path) {
            $this->setError('未找到excel文件',3002);
            return false;
        }
        $this->images = $this->getUnPackVoucher($content);//获取解压后的凭证文件
        $this->loadExcel($this->unpack_path.$excel_path);
        try {
            //获取标题
            $this->getFirstCellData();
            $this->getTitle();
            //数据加载
            $this->getData();

//            v($this->data);
            //数据验证
            $this->processData();
        } catch (\Exception $e) {
            $this->setError('获取Excel表格数据失败','3003');
            return false;
        }

        if ($this->errorinfo) {
            foreach ($this->errorinfo[0] as $k => $v) {
                $this->setError($k.':'.$v, '3003');
                return false;
            }
        } else {
            return $this->writeData();
        }
    }

    /**写入流水表
     * @return bool
     * @throws Exception
     */
    public function writeData() {
        $model = M('fin_account_turnover', 'tb_');
        $db_data = [];
        $currency = array_flip(BaseModel::getCurrency());
        $collection = array_flip(BaseModel::getCollection());
        $company = BaseModel::ourCompany();
        $bank_model = M('fin_account_bank', 'tb_');
        $current_time = date('Y-m-d',time());

        foreach ($this->data as $key => $value) {
            if ($key < 3) {
                continue;
            }
            $add = [];
            foreach ($value as $v) {
                if (!$v['db_field']) {
                    continue;
                }
                if($v['db_field'] == 'account_bank') {
                    $bank = $bank_model->where(['account_bank' => $v['value']])->find();
                    if (!$bank) {
                        $this->setError(L('收款银行账号:'.$v['value'].'不存在'), '3007');
                        return false;
                    }
                    $add['open_bank'] = $bank['open_bank'];
                    $add['company_code'] = $bank['company_code'];
                    $add['company_name'] = $company[$bank['company_code']];
                }

                if($v['db_field'] == 'collection_type') {
                    if (!$collection[$v['value']]) {
                        $this->setError(L('未知收款类型:'.$v['value']), '3006');
                        return false;
                    }
                    $add[$v['db_field']] = $collection[$v['value']];
                }

                if($v['db_field'] == 'transfer_time') {
                    $transfer_time = ($v['value'] - 25569) * 24 * 3600;
//                    $transfer_time = PHPExcel_Shared_Date::ExcelToPHP($v['value']);
                    $date = $this->excelTime($v['value']);
                    $check_date = date('Y-m-d', $transfer_time);
                    if ($check_date != $date) {
                        $this->setError(L('日期格式错误'), 3009);
                        return false;
                    }
//                    $transfer_time = strtotime($date);
                    if ($transfer_time > strtotime($current_time)) {
                        $this->setError(L('收款日期不能晚于当前日期'), 3009);
                        return false;
                    }
                    $add[$v['db_field']] = date('Y-m-d', $transfer_time);
                }

                if ($v['db_field'] == 'original_currency' || $v['db_field'] == 'currency_code'
                    || $v['db_field'] == 'other_currency' || $v['db_field'] == 'remitter_currency') {
                    if (!$currency[$v['value']]) {
                        $this->setError(L('未知币种:'.$v['value']), '3005');
                        return false;
                    }
                    $add[$v['db_field']] = $currency[$v['value']];
                }

                if ($v['db_field'] == 'original_amount' || $v['db_field'] == 'amount_money'
                    || $v['db_field'] == 'other_cost' || $v['db_field'] == 'remitter_cost') {
                    if (!is_numeric($v['value']) || $v['value'] < 0) {
                        $this->setError(L('金额不能小于0'), '3008');
                        return false;
                    }
                    $add[$v['db_field']] = $v['value'];
                }

                if (!isset($add[$v['db_field']])) {
                    $add[$v['db_field']] = $v['value'];
                }
            }
            $transfer_voucher = $this->matchVoucher($key);
            if (!$transfer_voucher) {
                return false;
            }
            $add['transfer_voucher'] = $transfer_voucher;
            $add['account_transfer_no'] = 'LS'. date(Ymd). TbWmsNmIncrementModel::generateNo('LS');
            $add['transfer_type'] = 'N001950200';//B2B收款
            $add['pay_or_rec'] = 1;
            $add['create_time'] = $this->getTime();
            $add['create_user'] = $this->getName();
            $add['package_path'] = $this->package_path;
            $db_data[] = $add;
        }
        $db_data = ZUtils::filterBlank($db_data);
        $model->startTrans();
        if (!$model->addAll($db_data)) {
            $this->setError('写入日记账失败', '3003');
            Log(json_encode($db_data), '收款流水批量导入失败', __CLASS__);
            $model->rollback();
            return false;
        }
        $model->commit();
        return true;
    }

    /**
     * 加载EXCEL，生成excel对象
     *
     */
    public function loadExcel($excel_path)
    {
        $this->excel = new PackageExcelOperationModel($excel_path);
    }

    public function fieldMapping($mode = null)
    {
        return [
            'opp_company_name' => ['field_name' => '付款账户名*', 'required' => true],
            'account_bank' => ['field_name' => '收款银行账号*', 'required' => true],
            'transfer_time' => ['field_name' => '收款日期*', 'required' => true],
            'collection_type' => ['field_name' => '收款类型*', 'required' => true],
            'remark' => ['field_name' => '备注', 'required' => false],
            'original_currency' => ['field_name' => '原始金额币种*', 'required' => true],
            'original_amount' => ['field_name' => '原始金额*', 'required' => true],
            'currency_code' => ['field_name' => '我方实收金额币种*', 'required' => true],
            'amount_money' => ['field_name' => '我方实收金额*', 'required' => true],
            'other_currency' => ['field_name' => '其他费用币种*', 'required' => true],
            'other_cost' => ['field_name' => '其他费用金额*', 'required' => true],
            'remitter_currency' => ['field_name' => '汇款人费用币种*', 'required' => true],
            'remitter_cost' => ['field_name' => '汇款人费用金额*', 'required' => true],
        ];
    }

    public function getTitle()
    {
        $fields = $this->fieldMapping();
        foreach ($this->firstCellRowData as $key => $value) {
            foreach ($fields as $k => $v) {
                if ($v ['field_name'] == $value) {
                    $temp [$key] ['db_field'] = $k;
                    $temp [$key] ['required'] = $v['required'];
                    $temp [$key] ['en_name'] = $value;
                }
            }
        }
        $this->title = $temp;
    }

    public function getUnPackExcelPath($content) {
        foreach ($content as $key => $value) {
            if ($value === -1) {
                continue;
            }
            $file_arr = explode('.', $key);
            if (empty($file_arr)) {
                continue;
            }
            $suffix = array_pop($file_arr);
            if (in_array($suffix, ['xlsx', 'xls'])) {
                return $key;
            }
        }
        return null;
    }

    /**获取压缩包里的凭证文件
     * @param $content
     * @return array
     */
    public function getUnPackVoucher($content) {
        $images = [];
        foreach ($content as $key => $value) {
            if ($value === -1) {
                continue;
            }
            $file_arr = explode('.', $key);
            if (empty($file_arr)) {
                continue;
            }
            $suffix = array_pop($file_arr);
            if (in_array($suffix, ['jpeg', 'jpg', 'png', 'pdf'])) {
                $this->images_path[] = $this->unpack_path.$key;
                $images[] = array_pop(explode('/', $key));
            }
        }
        return $images;
    }

    /**获取excel表行号关联的凭证文件
     * @param $line excel行号
     * @return bool|string
     */
    private function matchVoucher($line) {
        if (empty($this->images)) {
            $this->setError(L('请上传凭证文件库'), '3001');
            return false;
        }
        $image_arr = [];
        foreach ($this->images as $key => $image) {
            if (strpos($image, (string)$line) === 0) {
                $image_name = uniqid('receipt_', true) . '.jpg';
                $copy_res = copy($this->images_path[$key], ATTACHMENT_DIR_IMG . $image_name);
                if (!$copy_res) {
                    $this->setError(L('凭证文件复制失败'), '3002');
                    return false;
                }
                $image_arr[] = ['name' => $image, 'savename' => $image_name];
            }
        }
        if (empty($image_arr)) {
            $this->setError(L('第'. $line. '行，请上传凭证文件，或者检查凭证文件格式是否正确，凭证文件格式要求请查看导入说明'), '3002');
            return false;
        }
        return json_encode($image_arr, JSON_UNESCAPED_UNICODE);
    }

    private function checkUnzipFormat($content) {
        if (empty($content) || !is_array($content)) {
            $this->setError('未找到录入数据', '3001');
            return false;
        }

        $excel_count = 0;
        $image_count = 0;
        foreach ($content as $key => $value) {
            if (!$value) {
                continue;
            }
            //判断excel个数是否为1
            $file_arr = explode('.', $key);
            if (empty($file_arr)) {
                continue;
            }
            $suffix = array_pop($file_arr);
            if (in_array($suffix, ['xlsx', 'xls'])) {
                $excel_count++;
            } else if (in_array($suffix, ['png', 'jpg', 'jpeg', 'gif'])) {
                $image_count++;
            }
        }

        if ($excel_count != 1) {
            $this->setError('压缩文件只能包含一个excel文件', '3001');
            return false;
        }
        if ($image_count == 0) {
            $this->setError('压缩文件里未找到凭证图片', '3001');
            return false;
        }
        return true;
    }

    //excel日期转换
    public function excelTime($date, $time = false) {
        if(function_exists('GregorianToJD')){
            if (is_numeric( $date )) {
                $jd = GregorianToJD( 1, 1, 1970 );
                $gregorian = JDToGregorian( $jd + intval ( $date ) - 25569 );
                $date = explode( '/', $gregorian );
                $date_str = str_pad( $date [2], 4, '0', STR_PAD_LEFT )
                    ."-". str_pad( $date [0], 2, '0', STR_PAD_LEFT )
                    ."-". str_pad( $date [1], 2, '0', STR_PAD_LEFT )
                    . ($time ? " 00:00:00" : '');
                return $date_str;
            }
        }else{
            $date=$date>25568?$date:25569;
            $ofs=(70 * 365 + 17+2) * 86400;
            $date = date("Y-m-d",($date * 86400) - $ofs).($time ? " 00:00:00" : '');
        }
        return $date;
    }

    public function setError($msg, $code) {
        $this->error_info = [
            'msg' => $msg,
            'code' => $code,
            'data' => [],
        ];
    }

}