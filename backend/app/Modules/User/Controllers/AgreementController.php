<?php
namespace App\Modules\User\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AgreementController extends BaseController
{
    public function index(Request $request)
    {
        $query = DB::table('agreements')->orderBy('id', 'desc');
        if ($request->type) $query->where('type', $request->type);
        $list = $query->get();
        return $this->success(['list' => $list, 'total' => count($list)]);
    }

    public function show($type)
    {
        $agreement = DB::table('agreements')->where('type', $type)->where('status', 1)->orderBy('id', 'desc')->first();
        if (!$agreement) return $this->error('协议不存在', 404);
        return $this->success($agreement);
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
