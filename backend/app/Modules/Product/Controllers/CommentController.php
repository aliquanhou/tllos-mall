<?php
namespace App\Modules\Product\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentController extends BaseController
{
    public function index(Request $request)
    {
        $query = DB::table('product_comments as c')
            ->leftJoin('products as p', 'c.product_id', '=', 'p.id')
            ->leftJoin('users as u', 'c.user_id', '=', 'u.id')
            ->select('c.*', 'p.name as product_name', 'p.main_image', 'u.nickname', 'u.avatar as user_avatar');

        if ($request->filled('keyword')) {
            $query->where(function($q) use ($request) {
                $q->where('c.content', 'like', '%' . $request->keyword . '%')
                  ->orWhere('p.name', 'like', '%' . $request->keyword . '%')
                  ->orWhere('u.nickname', 'like', '%' . $request->keyword . '%');
            });
        }
        if ($request->filled('product_id')) $query->where('c.product_id', $request->product_id);
        if ($request->filled('rating')) $query->where('c.rating', $request->rating);
        if ($request->filled('is_show')) $query->where('c.is_show', $request->is_show);
        if ($request->filled('has_reply')) {
            if ($request->has_reply == 1) $query->whereNotNull('c.reply');
            else $query->whereNull('c.reply');
        }

        $total = $query->count();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $list = $query->orderBy('c.id', 'desc')->offset(($page - 1) * $limit)->limit($limit)->get();

        $stats = [
            'total' => DB::table('product_comments')->count(),
            'today' => DB::table('product_comments')->whereDate('created_at', today())->count(),
            'avg_rating' => round(DB::table('product_comments')->avg('rating'), 1),
            'hidden' => DB::table('product_comments')->where('is_show', 0)->count(),
        ];

        return $this->success(['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit, 'stats' => $stats]);
    }

    public function show($id)
    {
        $comment = DB::table('product_comments as c')
            ->leftJoin('products as p', 'c.product_id', '=', 'p.id')
            ->leftJoin('users as u', 'c.user_id', '=', 'u.id')
            ->select('c.*', 'p.name as product_name', 'p.main_image', 'u.nickname', 'u.mobile')
            ->where('c.id', $id)->first();
        if (!$comment) return $this->error('评价不存在');
        return $this->success($comment);
    }

    public function reply(Request $request, $id)
    {
        $validated = $request->validate(['reply' => 'required|string|max:500']);
        DB::table('product_comments')->where('id', $id)->update([
            'reply' => $validated['reply'], 'reply_at' => now(), 'updated_at' => now(),
        ]);
        return $this->success(null, '回复成功');
    }

    public function toggleShow($id)
    {
        $comment = DB::table('product_comments')->where('id', $id)->first();
        if (!$comment) return $this->error('评价不存在');
        $newStatus = $comment->is_show == 1 ? 0 : 1;
        DB::table('product_comments')->where('id', $id)->update(['is_show' => $newStatus, 'updated_at' => now()]);
        return $this->success(['is_show' => $newStatus], $newStatus == 1 ? '已显示' : '已隐藏');
    }

    public function destroy($id)
    {
        DB::table('product_comments')->where('id', $id)->delete();
        return $this->success(null, '删除成功');
    }
}
