<?php
namespace App\Modules\System\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Area extends Model
{
    use SoftDeletes;
    protected $table = 'areas';
    protected $fillable = ['*'];
    protected $guarded = [];
}
