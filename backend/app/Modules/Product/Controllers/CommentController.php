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

    // 用户端：获取商品评价列表（公开，只显示已审核）
    public function productList(Request $request)
    {
        $request->validate(['product_id' => 'required|integer']);
        $query = DB::table('product_comments as c')
            ->leftJoin('users as u', 'c.user_id', '=', 'u.id')
            ->select('c.id', 'c.product_id', 'c.content', 'c.rating', 'c.images', 'c.reply', 'c.reply_at', 'c.created_at', 'u.nickname', 'u.avatar')
            ->where('c.product_id', $request->product_id)
            ->where('c.is_show', 1);

        $total = $query->count();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $list = $query->orderBy('c.id', 'desc')->offset(($page - 1) * $limit)->limit($limit)->get();

        $stats = [
            'total' => $total,
            'avg_rating' => round(DB::table('product_comments')->where('product_id', $request->product_id)->where('is_show', 1)->avg('rating'), 1),
            'rating_5' => DB::table('product_comments')->where('product_id', $request->product_id)->where('is_show', 1)->where('rating', 5)->count(),
            'rating_4' => DB::table('product_comments')->where('product_id', $request->product_id)->where('is_show', 1)->where('rating', 4)->count(),
            'rating_3' => DB::table('product_comments')->where('product_id', $request->product_id)->where('is_show', 1)->where('rating', 3)->count(),
            'rating_2' => DB::table('product_comments')->where('product_id', $request->product_id)->where('is_show', 1)->where('rating', 2)->count(),
            'rating_1' => DB::table('product_comments')->where('product_id', $request->product_id)->where('is_show', 1)->where('rating', 1)->count(),
        ];

        return $this->success(['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit, 'stats' => $stats]);
    }

    // 用户端：提交评价（需登录）
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'order_id' => 'nullable|integer',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string|max:500',
            'images' => 'nullable|string',
        ]);

        $userId = $request->user()->id;

        // 检查是否已评价
        $exists = DB::table('product_comments')->where('user_id', $userId)->where('product_id', $validated['product_id'])->first();
        if ($exists) return $this->error('您已评价过该商品');

        $id = DB::table('product_comments')->insertGetId([
            'user_id' => $userId,
            'product_id' => $validated['product_id'],
            'order_id' => $validated['order_id'] ?? null,
            'rating' => $validated['rating'],
            'content' => $validated['content'],
            'images' => $validated['images'] ?? null,
            'is_show' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['id' => $id], '评价成功');
    }

    // 用户端：我的评价列表（需登录）
    public function myList(Request $request)
    {
        $userId = $request->user()->id;
        $query = DB::table('product_comments as c')
            ->leftJoin('products as p', 'c.product_id', '=', 'p.id')
            ->select('c.*', 'p.name as product_name', 'p.main_image')
            ->where('c.user_id', $userId);

        $total = $query->count();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $list = $query->orderBy('c.id', 'desc')->offset(($page - 1) * $limit)->limit($limit)->get();

        return $this->success(['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }
}
