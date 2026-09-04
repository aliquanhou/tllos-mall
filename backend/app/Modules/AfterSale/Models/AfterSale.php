<?php
namespace App\Modules\AfterSale\Models;
use Illuminate\Database\Eloquent\Model;
class AfterSale extends Model {
    protected $table = 'order_after_sales';
    protected $fillable = ['order_id','order_no','order_item_id','user_id','merchant_id','type','reason','description','images','refund_amount','status','audit_remark','audit_at','completed_at'];
    protected $casts = ['status'=>'integer','type'=>'integer'];
}
