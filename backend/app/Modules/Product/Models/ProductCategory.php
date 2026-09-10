<?php
namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $table = 'product_categories';
    protected $fillable = ['parent_id', 'name', 'icon', 'image', 'description', 'level', 'sort', 'status'];

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
