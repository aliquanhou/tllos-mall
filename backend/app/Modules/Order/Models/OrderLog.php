<?php
namespace App\Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model
{
    protected $table = 'order_logs';
    public $timestamps = false;
    protected $fillable = ['order_id', 'order_no', 'action', 'action_name', 'operator_type', 'operator_id', 'remark'];
}
