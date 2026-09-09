<?php
namespace App\Modules\Cart\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Cart\Models\Cart;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductSku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends BaseController
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $carts = Cart::with(['product:id,name,main_image,price,market_price,stock,status', 'sku:id,sku_no,spec_text,price,stock,image'])
            ->where('user_id', $userId)->orderBy('id', 'desc')->get();

        $list = $carts->map(function($item) {
            $product = $item->product;
            $sku = $item->sku;
            $price = $sku ? $sku->price : ($product ? $product->price : 0);
            $stock = $sku ? $sku->stock : ($product ? $product->stock : 0);
            $available = $product && $product->status == 1 && $stock > 0;
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'sku_id' => $item->sku_id,
                'name' => $product ? $product->name : '商品已下架',
                'image' => $sku->image ?? ($product->main_image ?? ''),
                'spec_text' => $sku->spec_text ?? '',
                'price' => $price,
                'stock' => $stock,
                'quantity' => $item->quantity,
                'selected' => $item->selected,
                'available' => $available,
                'subtotal' => bcmul($price, $item->quantity, 2),
            ];
        });

        $selectedItems = $list->where('selected', 1)->where('available', true);
        $totalAmount = $selectedItems->sum('subtotal');
        $totalCount = $selectedItems->sum('quantity');

        return $this->success([
            'list' => $list->values(),
            'selected_count' => $totalCount,
            'total_amount' => $totalAmount,
            'all_selected' => $list->count() > 0 && $list->every(fn($i) => $i['selected'] == 1),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'sku_id' => 'nullable|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        $userId = $request->user()->id;
        $product = Product::find($request->product_id);
        if (!$product || $product->status != 1) return $this->error('商品不存在或已下架');

        $sku = null;
        $stock = $product->stock;
        if ($request->sku_id) {
            $sku = ProductSku::where('id', $request->sku_id)->where('product_id', $request->product_id)->first();
            if (!$sku) return $this->error('规格不存在');
            $stock = $sku->stock;
        }

        $cart = Cart::where('user_id', $userId)->where('product_id', $request->product_id)
            ->where('sku_id', $request->sku_id ?: 0)->first();

        if ($cart) {
            $newQty = $cart->quantity + $request->quantity;
            if ($newQty > $stock) return $this->error('库存不足');
            $cart->update(['quantity' => $newQty]);
        } else {
            if ($request->quantity > $stock) return $this->error('库存不足');
            Cart::create([
                'user_id' => $userId,
                'product_id' => $request->product_id,
                'sku_id' => $request->sku_id ?: 0,
                'quantity' => $request->quantity,
                'selected' => 1,
            ]);
        }

        $count = Cart::where('user_id', $userId)->sum('quantity');
        return $this->success(['cart_count' => $count], '已加入购物车');
    }

    public function update(Request $request, $id)
    {
        $userId = $request->user()->id;
        $cart = Cart::where('id', $id)->where('user_id', $userId)->first();
        if (!$cart) return $this->error('购物车项不存在', 404);

        if ($request->has('quantity')) {
            $product = Product::find($cart->product_id);
            $stock = $product ? $product->stock : 0;
            if ($cart->sku_id) {
                $sku = ProductSku::find($cart->sku_id);
                $stock = $sku ? $sku->stock : 0;
            }
            if ($request->quantity > $stock) return $this->error('库存不足');
            if ($request->quantity < 1) return $this->error('数量不能小于1');
            $cart->update(['quantity' => $request->quantity]);
        }

        if ($request->has('selected')) {
            $cart->update(['selected' => $request->selected ? 1 : 0]);
        }

        return $this->success(null, '更新成功');
    }

    public function destroy($id, Request $request)
    {
        $userId = $request->user()->id;
        $cart = Cart::where('id', $id)->where('user_id', $userId)->first();
        if (!$cart) return $this->error('购物车项不存在', 404);
        $cart->delete();
        return $this->success(null, '删除成功');
    }

    public function clear(Request $request)
    {
        Cart::where('user_id', $request->user()->id)->delete();
        return $this->success(null, '购物车已清空');
    }

    public function selectAll(Request $request)
    {
        $selected = $request->input('selected', 1) ? 1 : 0;
        Cart::where('user_id', $request->user()->id)->update(['selected' => $selected]);
        return $this->success(null, '操作成功');
    }

    public function count(Request $request)
    {
        $count = Cart::where('user_id', $request->user()->id)->sum('quantity');
        return $this->success(['count' => $count]);
    }
}
