<?php

namespace App\Modules\User\Controllers;

use App\Core\Controllers\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseController
{
    public function login(Request $request)
    {
        $request->validate([
            'account' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('mobile', $request->account)
            ->orWhere('email', $request->account)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('账号或密码错误', 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'nickname' => $user->nickname ?? $user->name,
                'mobile' => $user->mobile,
                'avatar' => $user->avatar,
            ],
        ], '登录成功');
    }

    public function register(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|unique:users,mobile',
            'password' => 'required|string|min:6',
            'nickname' => 'nullable|string|max:50',
        ]);

        $user = User::create([
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
            'nickname' => $request->nickname ?? '用户' . substr($request->mobile, -4),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'nickname' => $user->nickname,
                'mobile' => $user->mobile,
            ],
        ], '注册成功');
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        return $this->success([
            'id' => $user->id,
            'nickname' => $user->nickname ?? $user->name,
            'mobile' => $user->mobile,
            'email' => $user->email,
            'avatar' => $user->avatar,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success(null, '退出成功');
    }

    public function sendSmsCode(Request $request)
    {
        $request->validate(['mobile' => 'required|string']);
        // TODO: 接入短信服务
        return $this->success(null, '验证码已发送');
    }
}
