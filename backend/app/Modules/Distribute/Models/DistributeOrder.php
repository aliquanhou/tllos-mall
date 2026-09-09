<?php
namespace App\Modules\Distribute\Models;
use Illuminate\Database\Eloquent\Model;
class DistributeOrder extends Model {
    protected $table = 'distribute_orders';
    protected $fillable = ['order_id','order_no','user_id','agent_id','level_id','goods_amount','commission_rate','commission_amount','status','settled_at'];
    protected $casts = ['status'=>'integer'];
}
