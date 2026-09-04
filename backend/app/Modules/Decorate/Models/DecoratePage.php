<?php
namespace App\Modules\Decorate\Models;
use Illuminate\Database\Eloquent\Model;
class DecoratePage extends Model {
    protected $table = 'decorate_pages';
    protected $fillable = ['name','page_type','content','is_default','status'];
    protected $casts = ['is_default'=>'integer','status'=>'integer'];
}
