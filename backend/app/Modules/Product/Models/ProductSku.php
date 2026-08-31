<?php
namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSku extends Model
{
    protected $table = 'product_skus';
    protected $fillable = ['product_id', 'sku_no', 'specs', 'spec_text', 'price', 'market_price', 'cost_price', 'stock', 'sales', 'image', 'status'];
    protected $casts = ['specs' => 'array', 'price' => 'decimal:2'];
}
