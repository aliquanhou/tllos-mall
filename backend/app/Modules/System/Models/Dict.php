<?php
namespace App\Modules\System\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Dict extends Model
{
    use SoftDeletes;
    protected $table = 'dicts';
    protected $fillable = ['*'];
    protected $guarded = [];
}
