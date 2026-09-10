<?php
namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens;
    protected $table = 'admins';
    protected $fillable = ['username', 'password', 'nickname', 'avatar', 'mobile', 'email', 'role_id', 'status'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['status' => 'integer'];
}
