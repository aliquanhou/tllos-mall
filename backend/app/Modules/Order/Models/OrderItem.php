<?php
namespace App\Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';
    protected $fillable = [
        'order_id', 'order_no', 'product_id', 'sku_id', 'product_name',
        'product_image', 'sku_text', 'price', 'market_price', 'cost_price',
        'quantity', 'total_amount', 'discount_amount', 'pay_amount',
        'is_commented', 'is_refunded',
    ];
}
