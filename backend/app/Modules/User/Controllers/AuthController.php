<?php
namespace App\Modules\User\Controllers;

use App\Core\Controllers\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends BaseController
{
    public function register(Request $request)
    {
        $request->validate([
            'account' => 'required|string|unique:users,mobile|unique:users,account',
            'password' => 'required|string|min:6',
            'nickname' => 'nullable|string',
            'agreed' => 'required|boolean',
        ]);

        if (!$request->agreed) {
            return $this->error('请阅读并同意用户协议和隐私政策');
        }

        $user = User::create([
            'mobile' => $request->account,
            'account' => $request->account,
            'password' => Hash::make($request->password),
            'nickname' => $request->nickname ?: '用户' . substr($request->account, -4),
            'status' => 1,
        ]);

        // 记录协议签署
        DB::table('user_agreement_logs')->insert([
            'user_id' => $user->id,
            'agreement_id' => 1,
            'agreement_type' => 1,
            'version' => '1.0',
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;
        return $this->success(['token' => $token, 'user' => $this->formatUser($user)], '注册成功');
    }

    public function login(Request $request)
    {
        $request->validate([
            'account' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('mobile', $request->account)
            ->orWhere('email', $request->account)
            ->orWhere('account', $request->account)
            ->orWhere('username', $request->account)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('账号或密码错误', 401);
        }

        if ($user->status != 1) {
            return $this->error('账号已被禁用', 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;
        return $this->success(['token' => $token, 'user' => $this->formatUser($user)], '登录成功');
    }

    public function sendSmsCode(Request $request)
    {
        $request->validate(['mobile' => 'required|string']);
        return $this->success(null, '验证码已发送');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success(null, '退出成功');
    }

    public function info(Request $request)
    {
        return $this->success($this->formatUser($request->user()));
    }

    private function formatUser($user)
    {
        return [
            'id' => $user->id,
            'mobile' => $user->mobile,
            'account' => $user->account ?? $user->mobile,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'balance' => $user->balance ?? 0,
            'points' => $user->points ?? 0,
            'level_id' => $user->level_id ?? 1,
        ];
    }
}
