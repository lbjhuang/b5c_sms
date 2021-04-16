<?php


/**
 * User: yangsu
 * Date: 18/12/14
 * Time: 13:34
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbFinClaim
 *
 * @property int $id
 * @property int $account_turnover_id
 * @property string $order_type
 * @property int $order_id
 * @property string $order_no
 * @property string $child_order_no
 * @property string $sale_teams
 * @property float $claim_amount
 * @property float $summary_amount
 * @property float $current_remaining_receivable
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class TbFinClaim extends ORM
{
    protected $table = 'tb_fin_claim';

    protected $casts = [
        'account_turnover_id' => 'int',
        'order_id' => 'int',
        'claim_amount' => 'float',
        'summary_amount' => 'float',
        'current_remaining_receivable' => 'float'
    ];

    protected $fillable = [
        'account_turnover_id',
        'order_type',
        'order_id',
        'order_no',
        'child_order_no',
        'sale_teams',
        'claim_amount',
        'summary_amount',
        'current_remaining_receivable',
        'created_by',
        'updated_by'
    ];
}
