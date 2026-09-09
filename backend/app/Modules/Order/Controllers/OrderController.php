<?php
namespace App\Modules\Order\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\OrderLog;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductSku;
use App\Modules\Cart\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class OrderController extends BaseController
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $query = Order::with(['items:id,order_id,product_id,product_name,product_image,sku_text,price,quantity,pay_amount,is_commented'])
            ->where('user_id', $userId);

        if ($request->status !== null && $request->status !== '') {
            if ($request->status == 0) $query->where('status', 0);
            elseif ($request->status == 1) $query->whereIn('status', [1, 2]);
            elseif ($request->status == 3) $query->where('status', 3);
            elseif ($request->status == 4) $query->whereIn('status', [4, 5, 6]);
        }

        $list = $query->orderBy('id', 'desc')->paginate($request->limit ?: 10);
        return $this->success(['list' => $list->items(), 'total' => $list->total(), 'page' => $list->currentPage()]);
    }

    public function show($id, Request $request)
    {
        $order = Order::with(['items', 'logs' => function($q) { $q->orderBy('id', 'asc'); }])
            ->where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$order) return $this->error('订单不存在', 404);
        return $this->success($order);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'receiver_name' => 'required|string',
            'receiver_mobile' => 'required|string',
            'province_id' => 'required|integer',
            'city_id' => 'required|integer',
            'district_id' => 'required|integer',
            'province_name' => 'required|string',
            'city_name' => 'required|string',
            'district_name' => 'required|string',
            'receiver_address' => 'required|string',
        ]);

        $userId = $request->user()->id;
        $orderNo = 'TLL' . date('YmdHis') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $orderItems = [];
            $cartIds = [];

            foreach ($request->items as $item) {
                $product = Product::where('id', $item['product_id'])->where('status', 1)->lockForUpdate()->first();
                if (!$product) throw new \Exception('商品已下架');

                $sku = null;
                $price = $product->price;
                $stock = $product->stock;
                $skuText = '';
                $skuId = 0;

                if (!empty($item['sku_id'])) {
                    $sku = ProductSku::where('id', $item['sku_id'])->where('product_id', $product->id)->lockForUpdate()->first();
                    if (!$sku) throw new \Exception('规格不存在');
                    $price = $sku->price;
                    $stock = $sku->stock;
                    $skuText = $sku->spec_text;
                    $skuId = $sku->id;
                }

                if ($item['quantity'] > $stock) throw new \Exception("{$product->name} 库存不足");

                $subtotal = bcmul($price, $item['quantity'], 2);
                $totalAmount = bcadd($totalAmount, $subtotal, 2);

                if ($sku) $sku->decrement('stock', $item['quantity']);
                else $product->decrement('stock', $item['quantity']);
                $product->increment('sales', $item['quantity']);

                $orderItems[] = [
                    'product_id' => $product->id,
                    'sku_id' => $skuId,
                    'product_name' => $product->name,
                    'product_image' => $sku->image ?? $product->main_image,
                    'sku_text' => $skuText,
                    'price' => $price,
                    'market_price' => $product->market_price,
                    'cost_price' => $sku->cost_price ?? $product->cost_price,
                    'quantity' => $item['quantity'],
                    'total_amount' => $subtotal,
                    'pay_amount' => $subtotal,
                ];

                if (!empty($item['cart_id'])) $cartIds[] = $item['cart_id'];
            }

            $shippingFee = $request->shipping_fee ?? 0;
            $payAmount = bcadd($totalAmount, $shippingFee, 2);

            $order = Order::create([
                'order_no' => $orderNo,
                'user_id' => $userId,
                'merchant_id' => 0,
                'total_amount' => $totalAmount,
                'shipping_fee' => $shippingFee,
                'discount_amount' => 0,
                'coupon_amount' => 0,
                'points_amount' => 0,
                'pay_amount' => $payAmount,
                'cost_amount' => 0,
                'commission' => 0,
                'merchant_amount' => $payAmount,
                'pay_type' => 0,
                'order_type' => 1,
                'status' => 0,
                'receiver_name' => $request->receiver_name,
                'receiver_mobile' => $request->receiver_mobile,
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'province_name' => $request->province_name,
                'city_name' => $request->city_name,
                'district_name' => $request->district_name,
                'receiver_address' => $request->receiver_address,
                'user_remark' => $request->user_remark ?? '',
                'auto_cancel_at' => Carbon::now()->addMinutes(30),
            ]);

            foreach ($orderItems as &$item) {
                $item['order_id'] = $order->id;
                $item['order_no'] = $orderNo;
                $item['created_at'] = Carbon::now();
                $item['updated_at'] = Carbon::now();
            }
            OrderItem::insert($orderItems);

            OrderLog::create([
                'order_id' => $order->id, 'order_no' => $orderNo,
                'action' => 1, 'action_name' => '创建订单',
                'operator_type' => 'user', 'operator_id' => $userId,
                'remark' => '用户提交订单',
            ]);

            if (!empty($cartIds)) Cart::whereIn('id', $cartIds)->where('user_id', $userId)->delete();

            DB::commit();
            return $this->success(['order_id' => $order->id, 'order_no' => $orderNo, 'pay_amount' => $payAmount], '订单创建成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

    public function cancel($id, Request $request)
    {
        $order = Order::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$order) return $this->error('订单不存在', 404);
        if ($order->status != 0) return $this->error('当前状态不能取消');

        DB::beginTransaction();
        try {
            $order->update(['status' => 4]);
            foreach ($order->items as $item) {
                if ($item->sku_id) ProductSku::where('id', $item->sku_id)->increment('stock', $item->quantity);
                else Product::where('id', $item->product_id)->increment('stock', $item->quantity);
            }
            OrderLog::create([
                'order_id' => $order->id, 'order_no' => $order->order_no,
                'action' => 5, 'action_name' => '取消订单',
                'operator_type' => 'user', 'operator_id' => $request->user()->id,
                'remark' => $request->reason ?? '用户取消',
            ]);
            DB::commit();
            return $this->success(null, '订单已取消');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('取消失败');
        }
    }

    public function confirm($id, Request $request)
    {
        $order = Order::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$order) return $this->error('订单不存在', 404);
        if ($order->status != 2) return $this->error('当前状态不能确认收货');

        $order->update(['status' => 3, 'confirm_time' => Carbon::now()]);
        OrderLog::create([
            'order_id' => $order->id, 'order_no' => $order->order_no,
            'action' => 4, 'action_name' => '确认收货',
            'operator_type' => 'user', 'operator_id' => $request->user()->id,
        ]);
        return $this->success(null, '已确认收货');
    }

    public function preview(Request $request)
    {
        $request->validate(['items' => 'required|array']);
        $userId = $request->user()->id;
        $totalAmount = 0;
        $list = [];

        foreach ($request->items as $item) {
            $product = Product::where('id', $item['product_id'])->where('status', 1)->first();
            if (!$product) continue;
            $price = $product->price;
            $skuText = '';
            if (!empty($item['sku_id'])) {
                $sku = ProductSku::where('id', $item['sku_id'])->where('product_id', $product->id)->first();
                if ($sku) { $price = $sku->price; $skuText = $sku->spec_text; }
            }
            $subtotal = bcmul($price, $item['quantity'], 2);
            $totalAmount = bcadd($totalAmount, $subtotal, 2);
            $list[] = [
                'product_id' => $product->id, 'name' => $product->name,
                'image' => $product->main_image, 'sku_text' => $skuText,
                'price' => $price, 'quantity' => $item['quantity'], 'subtotal' => $subtotal,
            ];
        }

        $addresses = \DB::table('user_addresses')->where('user_id', $userId)->orderBy('is_default', 'desc')->get();

        return $this->success([
            'items' => $list,
            'total_amount' => $totalAmount,
            'shipping_fee' => 0,
            'pay_amount' => $totalAmount,
            'addresses' => $addresses,
        ]);
    }
}
