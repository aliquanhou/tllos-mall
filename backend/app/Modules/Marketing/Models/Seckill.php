<?php
namespace App\Modules\Marketing\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Seckill extends Model
{
    use SoftDeletes;
    protected $table = 'seckills';
    protected $fillable = ['*'];
    protected $guarded = [];
}
