<?php
namespace App\Modules\Distribute\Models;
use Illuminate\Database\Eloquent\Model;
class DistributeGoods extends Model {
    protected $table = 'distribute_goods';
    protected $fillable = ['product_id','commission_type','commission_rate','commission_amount','is_distribute'];
    protected $casts = ['is_distribute'=>'integer'];
}
