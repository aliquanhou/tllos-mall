<?php
namespace App\Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;
    protected $table = 'orders';
    protected $fillable = [
        'order_no', 'user_id', 'merchant_id', 'total_amount', 'shipping_fee',
        'discount_amount', 'coupon_amount', 'points_amount', 'pay_amount',
        'cost_amount', 'commission', 'merchant_amount', 'pay_type', 'pay_no',
        'pay_time', 'order_type', 'status', 'receiver_name', 'receiver_mobile',
        'province_id', 'city_id', 'district_id', 'province_name', 'city_name',
        'district_name', 'receiver_address', 'express_company', 'express_no',
        'ship_time', 'confirm_time', 'user_remark', 'admin_remark', 'coupon_id',
        'use_points', 'earn_points', 'auto_cancel_at', 'auto_confirm_at',
    ];
    protected $casts = ['status' => 'integer', 'pay_type' => 'integer'];

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function logs()
    {
        return $this->hasMany(OrderLog::class, 'order_id');
    }
}
