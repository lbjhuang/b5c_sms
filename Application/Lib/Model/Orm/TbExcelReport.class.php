<?php

/**
 * Created by Mark.Zhong.
 * Date: Mon, 02 Dec 2019 08:10:56 +0000.
 */

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbExcelReport
 * 
 * @property int $id
 * @property string $file_name
 * @property int $type
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_by
 * @property string $deleted_at
 *
 * @package App\Models
 */
class TbExcelReport extends ORM
{
	protected $table = 'tb_excel_report';

	protected $casts = [
		'type' => 'int'
	];

	protected $fillable = [
		'file_name',
		'type',
		'created_by',
		'updated_by',
		'deleted_by'
	];

	const EXISTING_STOCK = 1;

	public function addReportExcel($file_name, $type)
    {
        $this->file_name = $file_name;
        $this->type = $type;
        $this->created_by = 'ERP SYSTEM';
        $this->created_by = 'ERP SYSTEM';
        $this->save();
    }

    public static function getReportExcel($type)
    {
        $list = self::where('type',$type)->get();
        if ($list) {
            return $list->toArray();
        }
    }
}
