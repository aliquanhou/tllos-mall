<?php
namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $table = 'products';
    protected $fillable = [
        'merchant_id', 'category_id', 'brand_id', 'name', 'subtitle',
        'main_image', 'images', 'description', 'price', 'market_price',
        'cost_price', 'stock', 'sales', 'views', 'favorites', 'is_sku',
        'unit', 'weight', 'is_free_shipping', 'shipping_fee',
        'is_new', 'is_hot', 'is_recommend', 'status', 'on_sale_at',
    ];
    protected $casts = [
        'images' => 'array',
        'price' => 'decimal:2',
        'status' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(\App\Modules\Product\Models\ProductCategory::class, 'category_id');
    }

    public function skus()
    {
        return $this->hasMany(\App\Modules\Product\Models\ProductSku::class, 'product_id');
    }
}
