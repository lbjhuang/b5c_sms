<?php

/**
 * User: yangsu
 * Date: 19/2/18
 * Time: 13:34
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbMsUserArea
 * 
 * @property int $id
 * @property string $area_no
 * @property string $zh_name
 * @property string $parent_no
 * @property int $area_type
 * @property string $zip_code
 * @property string $zh_spelling
 * @property int $rank
 * @property string $two_char
 * @property string $three_char
 * @property string $en_name
 * @property string $continent
 *
 * @package App\Models
 */
class TbMsUserArea extends ORM
{
	protected $table = 'tb_ms_user_area';
	public $timestamps = false;

	protected $casts = [
		'area_type' => 'int',
		'rank' => 'int'
	];

	protected $fillable = [
		'area_no',
		'zh_name',
		'parent_no',
		'area_type',
		'zip_code',
		'zh_spelling',
		'rank',
		'two_char',
		'three_char',
		'en_name',
		'continent'
	];
}
