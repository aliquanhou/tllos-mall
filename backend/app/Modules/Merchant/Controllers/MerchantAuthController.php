<?php
namespace App\Modules\Merchant\Controllers;

use App\Core\Controllers\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MerchantAuthController extends BaseController
{
    public function login(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        $shop = DB::table('shops')->where('username', $username)->first();
        if (!$shop || !Hash::check($password, $shop->password)) {
            return $this->error('账号或密码错误');
        }
        if ($shop->status != 1) {
            return $this->error('店铺已被禁用');
        }

        // 查找或创建关联用户
        $user = null;
        if ($shop->user_id) {
            $user = User::where('id', $shop->user_id)->first();
        }

        if (!$user) {
            // 尝试通过手机号查找已存在的用户
            $mobile = $shop->contact_phone ?: $shop->contact_mobile ?: 'merchant_' . $shop->id;
            $user = User::where('mobile', $mobile)->first();

            if (!$user) {
                try {
                    $user = User::create([
                        'mobile' => $mobile,
                        'nickname' => $shop->name,
                        'password' => Hash::make($password),
                        'status' => 1,
                    ]);
                } catch (\Exception $e) {
                    // 如果创建失败，用兜底手机号
                    $user = User::create([
                        'mobile' => 'merchant_' . $shop->id . '_' . time(),
                        'nickname' => $shop->name,
                        'password' => Hash::make($password),
                        'status' => 1,
                    ]);
                }
            }

            DB::table('shops')->where('id', $shop->id)->update(['user_id' => $user->id]);
        }

        $token = $user->createToken('merchant')->plainTextToken;

        return $this->success([
            'token' => $token,
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'logo' => $shop->logo,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success(null, '退出成功');
    }

    public function info(Request $request)
    {
        $shop = DB::table('shops')->where('user_id', $request->user()->id)->first();
        return $this->success($shop);
    }
}
