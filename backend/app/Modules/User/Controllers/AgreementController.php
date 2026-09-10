<?php
namespace App\Modules\User\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AgreementController extends BaseController
{
    // 协议类型映射：字符串标识 => 整数类型
    const TYPE_MAP = [
        'user' => 1,
        'privacy' => 2,
        'refund' => 3,
        'payment' => 4,
        'member' => 5,
    ];

    public function index(Request $request)
    {
        $query = DB::table('agreements')->orderBy('id', 'desc');
        if ($request->type) {
            $type = $this->resolveType($request->type);
            $query->where('type', $type);
        }
        $list = $query->get();
        return $this->success(['list' => $list, 'total' => count($list)]);
    }

    public function show($type)
    {
        $typeId = $this->resolveType($type);
        $agreement = DB::table('agreements')->where('type', $typeId)->where('status', 1)->orderBy('id', 'desc')->first();
        if (!$agreement) {
            // 如果数据库中没有协议，返回默认内容
            $default = $this->getDefaultAgreement($typeId);
            if ($default) return $this->success($default);
            return $this->error('协议不存在', 404);
        }
        return $this->success($agreement);
    }

    private function resolveType($type)
    {
        if (is_numeric($type)) return (int)$type;
        $key = strtolower(trim($type));
        return self::TYPE_MAP[$key] ?? 0;
    }

    private function getDefaultAgreement($typeId)
    {
        $defaults = [
            1 => [
                'id' => 0, 'type' => 1, 'title' => '用户服务协议',
                'content' => '<h2>用户服务协议</h2><p>欢迎使用TLLOS商城。本协议是您与本平台之间关于使用本平台服务的协议。</p><p>一、服务内容<br>本平台为用户提供商品浏览、购买、支付、售后等电商服务。</p><p>二、用户账号<br>用户需注册账号并妥善保管密码，对账号下的所有行为负责。</p><p>三、交易规则<br>用户下单后应及时支付，平台按订单约定发货。</p><p>四、隐私保护<br>平台尊重并保护用户隐私，具体见隐私政策。</p><p>五、协议变更<br>平台有权根据需要修改本协议，修改后将在平台公示。</p>',
                'version' => '1.0', 'status' => 1,
            ],
            2 => [
                'id' => 0, 'type' => 2, 'title' => '隐私政策',
                'content' => '<h2>隐私政策</h2><p>TLLOS商城重视用户隐私保护。本政策说明我们如何收集、使用和保护您的个人信息。</p><p>一、信息收集<br>我们收集您在注册、下单、支付过程中提供的必要信息。</p><p>二、信息使用<br>您的信息仅用于提供商品和服务、处理订单、改善用户体验。</p><p>三、信息保护<br>我们采取加密存储、访问控制等措施保护您的信息安全。</p><p>四、信息共享<br>未经您同意，我们不会向第三方共享您的个人信息。</p><p>五、您的权利<br>您有权查询、更正、删除您的个人信息。</p>',
                'version' => '1.0', 'status' => 1,
            ],
        ];
        return $defaults[$typeId] ?? null;
    }

    public function store(Request $request)
    {
        $request->validate(['type' => 'required|integer', 'title' => 'required|string', 'content' => 'required|string', 'version' => 'nullable|string']);
        $id = DB::table('agreements')->insertGetId([
            'type' => $request->type, 'title' => $request->title, 'content' => $request->content,
            'version' => $request->version ?? '1.0', 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $this->success(['id' => $id], '创建成功');
    }

    public function update(Request $request, $id)
    {
        $agreement = DB::table('agreements')->where('id', $id)->first();
        if (!$agreement) return $this->error('协议不存在', 404);
        $data = $request->only(['title', 'content', 'version', 'status']);
        $data['updated_at'] = now();
        DB::table('agreements')->where('id', $id)->update($data);
        return $this->success(null, '更新成功');
    }

    public function sign(Request $request)
    {
        $request->validate(['agreement_id' => 'required|integer', 'agreed' => 'required|boolean']);
        if (!$request->agreed) return $this->error('请同意协议');
        $agreement = DB::table('agreements')->where('id', $request->agreement_id)->first();
        if (!$agreement) return $this->error('协议不存在', 404);
        $userId = $request->user()->id;
        $exists = DB::table('user_agreement_logs')->where('user_id', $userId)->where('agreement_id', $request->agreement_id)->first();
        if (!$exists) {
            DB::table('user_agreement_logs')->insert([
                'user_id' => $userId, 'agreement_id' => $agreement->id, 'agreement_type' => $agreement->type,
                'version' => $agreement->version, 'ip' => $request->ip(), 'created_at' => now(),
            ]);
        }
        return $this->success(null, '签署成功');
    }
}
