<?php
namespace App\Modules\Product\Controllers;

use App\Core\Controllers\BaseController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CeoEditController extends BaseController
{
    // CEO编辑密码（明文：TllosCEO2026!）
    const CEO_PASSWORD = 'TllosCEO2026!';

    /**
     * 验证CEO密码
     */
    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $inputPassword = $request->input('password');

        // 从system_configs读取加密密码
        $config = DB::table('system_configs')
            ->where('key', 'ceo_edit_password')
            ->first();

        if (!$config) {
            // 首次使用，初始化密码
            $hashed = Hash::make(self::CEO_PASSWORD);
            DB::table('system_configs')->insert([
                'group' => 'security',
                'key' => 'ceo_edit_password',
                'name' => 'CEO编辑密码',
                'value' => $hashed,
                'type' => 'password',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $config = (object)['value' => $hashed];
        }

        if (Hash::check($inputPassword, $config->value)) {
            // 生成临时token（1小时有效）
            $token = bin2hex(random_bytes(32));
            DB::table('ceo_edit_sessions')->updateOrInsert(
                ['token' => $token],
                [
                    'token' => $token,
                    'expires_at' => now()->addHour(),
                    'created_at' => now(),
                ]
            );
            return $this->success(['token' => $token], '验证成功');
        }

        return $this->error('密码错误', 401);
    }

    /**
     * CEO更新商品
     */
    public function updateProduct(Request $request, $id)
    {
        // 验证CEO token
        $token = $request->header('X-CEO-Token');
        if (!$token) {
            return $this->error('未授权', 401);
        }

        $session = DB::table('ceo_edit_sessions')
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$session) {
            return $this->error('授权已过期，请重新验证', 401);
        }

        $product = Product::find($id);
        if (!$product) {
            return $this->error('商品不存在', 404);
        }

        // 验证输入
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'description' => 'sometimes|string',
            'main_image' => 'sometimes|string|max:500',
        ]);

        // XSS过滤富文本
        if (isset($data['description'])) {
            $data['description'] = $this->sanitizeHtml($data['description']);
        }

        // 记录操作日志
        Log::info('CEO编辑商品', [
            'product_id' => $id,
            'changes' => array_keys($data),
            'ip' => $request->ip(),
            'time' => now()->toDateTimeString(),
        ]);

        $product->update($data);

        return $this->success($product, '更新成功');
    }

    /**
     * XSS过滤
     */
    private function sanitizeHtml($html)
    {
        // 移除script标签
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        // 移除on事件属性
        $html = preg_replace('/\son\w+="[^"]*"/i', '', $html);
        $html = preg_replace("/\son\w+='[^']*'/i", '', $html);
        // 移除javascript:协议
        $html = preg_replace('/javascript:/i', '', $html);
        // 移除iframe
        $html = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $html);
        return $html;
    }
}
