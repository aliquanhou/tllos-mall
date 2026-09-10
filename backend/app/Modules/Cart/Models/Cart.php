<?php
namespace App\Modules\Cart\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $table = 'carts';
    protected $fillable = ['user_id', 'product_id', 'sku_id', 'quantity', 'selected'];
    protected $casts = ['selected' => 'integer'];

    public function product()
    {
        return $this->belongsTo(\App\Modules\Product\Models\Product::class, 'product_id');
    }

    public function sku()
    {
        return $this->belongsTo(\App\Modules\Product\Models\ProductSku::class, 'sku_id');
    }
}
