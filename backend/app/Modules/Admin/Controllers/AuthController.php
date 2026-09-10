<?php
namespace App\Modules\Admin\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Admin\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends BaseController
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('username', $request->username)->first();
        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return $this->error('账号或密码错误', 401);
        }
        if ($admin->status != 1) {
            return $this->error('账号已被禁用', 403);
        }

        $token = $admin->createToken('admin-token')->plainTextToken;

        DB::table('login_logs')->insert([
            'username' => $admin->username,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 1,
            'type' => 'admin',
            'created_at' => now(),
        ]);

        $admin->update(['last_login_at' => now(), 'last_login_ip' => $request->ip()]);

        return $this->success([
            'token' => $token,
            'admin' => [
                'id' => $admin->id,
                'username' => $admin->username,
                'nickname' => $admin->nickname,
                'avatar' => $admin->avatar,
                'role_id' => $admin->role_id,
            ],
        ], '登录成功');
    }

    public function profile(Request $request)
    {
        $admin = $request->user();
        return $this->success([
            'id' => $admin->id,
            'username' => $admin->username,
            'nickname' => $admin->nickname,
            'avatar' => $admin->avatar,
            'mobile' => $admin->mobile,
            'email' => $admin->email,
            'role_id' => $admin->role_id,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success(null, '退出成功');
    }
}
