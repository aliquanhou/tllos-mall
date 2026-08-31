<?php
use Illuminate\Support\Facades\Route;

// 公开路由
Route::prefix('auth')->group(function () {
    Route::post('register', [\App\Modules\User\Controllers\AuthController::class, 'register']);
    Route::post('login', [\App\Modules\User\Controllers\AuthController::class, 'login']);
    Route::post('sms-code', [\App\Modules\User\Controllers\AuthController::class, 'sendSmsCode']);
});

// 管理员公开路由
Route::prefix('admin')->group(function () {
    Route::post('login', [\App\Modules\Admin\Controllers\AuthController::class, 'login']);
});

// 首页
Route::get('home', [\App\Modules\Home\Controllers\HomeController::class, 'index']);
Route::get('config', [\App\Modules\Home\Controllers\HomeController::class, 'config']);

// 商品（公开）
Route::prefix('products')->group(function () {
    Route::get('/', [\App\Modules\Product\Controllers\ProductController::class, 'index']);
    Route::get('categories', [\App\Modules\Product\Controllers\ProductController::class, 'categories']);
    Route::get('hot', [\App\Modules\Product\Controllers\ProductController::class, 'hot']);
    Route::get('new', [\App\Modules\Product\Controllers\ProductController::class, 'new']);
    Route::get('{id}', [\App\Modules\Product\Controllers\ProductController::class, 'show']);
});

// 需要用户认证的路由
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('profile', [\App\Modules\User\Controllers\AuthController::class, 'profile']);
        Route::post('logout', [\App\Modules\User\Controllers\AuthController::class, 'logout']);
    });
});

// 需要管理员认证的路由
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('profile', [\App\Modules\Admin\Controllers\AuthController::class, 'profile']);
    Route::post('logout', [\App\Modules\Admin\Controllers\AuthController::class, 'logout']);

    // 商品管理
    Route::prefix('products')->group(function () {
        Route::get('/', [\App\Modules\Product\Controllers\AdminProductController::class, 'index']);
        Route::get('{id}', [\App\Modules\Product\Controllers\AdminProductController::class, 'show']);
        Route::post('/', [\App\Modules\Product\Controllers\AdminProductController::class, 'store']);
        Route::put('{id}', [\App\Modules\Product\Controllers\AdminProductController::class, 'update']);
        Route::delete('{id}', [\App\Modules\Product\Controllers\AdminProductController::class, 'destroy']);
        Route::post('{id}/toggle-status', [\App\Modules\Product\Controllers\AdminProductController::class, 'toggleStatus']);
    });
});

// 购物车（需登录）
Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
    Route::get('/', [\App\Modules\Cart\Controllers\CartController::class, 'index']);
    Route::get('/count', [\App\Modules\Cart\Controllers\CartController::class, 'count']);
    Route::post('/', [\App\Modules\Cart\Controllers\CartController::class, 'store']);
    Route::put('/{id}', [\App\Modules\Cart\Controllers\CartController::class, 'update']);
    Route::delete('/{id}', [\App\Modules\Cart\Controllers\CartController::class, 'destroy']);
    Route::post('/clear', [\App\Modules\Cart\Controllers\CartController::class, 'clear']);
    Route::post('/select-all', [\App\Modules\Cart\Controllers\CartController::class, 'selectAll']);
});

// 订单（需登录）
Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
    Route::get('/', [\App\Modules\Order\Controllers\OrderController::class, 'index']);
    Route::get('/{id}', [\App\Modules\Order\Controllers\OrderController::class, 'show']);
    Route::post('/', [\App\Modules\Order\Controllers\OrderController::class, 'store']);
    Route::post('/preview', [\App\Modules\Order\Controllers\OrderController::class, 'preview']);
    Route::post('/{id}/cancel', [\App\Modules\Order\Controllers\OrderController::class, 'cancel']);
    Route::post('/{id}/confirm', [\App\Modules\Order\Controllers\OrderController::class, 'confirm']);
});

// 支付（需登录）
Route::middleware('auth:sanctum')->prefix('payment')->group(function () {
    Route::post('/pay', [\App\Modules\Payment\Controllers\PaymentController::class, 'pay']);
    Route::get('/status/{orderId}', [\App\Modules\Payment\Controllers\PaymentController::class, 'status']);
});

// 退款（需登录）
Route::middleware('auth:sanctum')->prefix('refunds')->group(function () {
    Route::get('/', [\App\Modules\Refund\Controllers\RefundController::class, 'index']);
    Route::get('/{id}', [\App\Modules\Refund\Controllers\RefundController::class, 'show']);
    Route::post('/', [\App\Modules\Refund\Controllers\RefundController::class, 'store']);
    Route::post('/{id}/cancel', [\App\Modules\Refund\Controllers\RefundController::class, 'cancel']);
});

// 管理端订单（需管理员登录）
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/orders', [\App\Modules\Order\Controllers\AdminOrderController::class, 'index']);
    Route::get('/orders/{id}', [\App\Modules\Order\Controllers\AdminOrderController::class, 'show']);
    Route::post('/orders/{id}/ship', [\App\Modules\Order\Controllers\AdminOrderController::class, 'ship']);
    Route::post('/orders/{id}/remark', [\App\Modules\Order\Controllers\AdminOrderController::class, 'remark']);
    Route::get('/refunds', [\App\Modules\Order\Controllers\AdminOrderController::class, 'refundList']);
    Route::post('/refunds/{id}/audit', [\App\Modules\Order\Controllers\AdminOrderController::class, 'refundAudit']);
});

// 管理端用户管理
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/users', [\App\Modules\User\Controllers\AdminUserController::class, 'index']);
    Route::get('/users/{id}', [\App\Modules\User\Controllers\AdminUserController::class, 'show']);
    Route::put('/users/{id}', [\App\Modules\User\Controllers\AdminUserController::class, 'update']);
    Route::post('/users/{id}/toggle-status', [\App\Modules\User\Controllers\AdminUserController::class, 'toggleStatus']);
});

// 商品分类管理
Route::prefix('admin/categories')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\Product\Controllers\CategoryController::class, 'index']);
    Route::get('/tree', [App\Modules\Product\Controllers\CategoryController::class, 'tree']);
    Route::post('/', [App\Modules\Product\Controllers\CategoryController::class, 'store']);
    Route::put('/{id}', [App\Modules\Product\Controllers\CategoryController::class, 'update']);
    Route::delete('/{id}', [App\Modules\Product\Controllers\CategoryController::class, 'destroy']);
});

// 商品评价管理
Route::prefix('admin/comments')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\Product\Controllers\CommentController::class, 'index']);
    Route::get('/{id}', [App\Modules\Product\Controllers\CommentController::class, 'show']);
    Route::post('/{id}/reply', [App\Modules\Product\Controllers\CommentController::class, 'reply']);
    Route::post('/{id}/toggle-show', [App\Modules\Product\Controllers\CommentController::class, 'toggleShow']);
    Route::delete('/{id}', [App\Modules\Product\Controllers\CommentController::class, 'destroy']);
});

// 商家管理
Route::prefix('admin/merchants')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\Merchant\Controllers\MerchantController::class, 'index']);
    Route::get('/{id}', [App\Modules\Merchant\Controllers\MerchantController::class, 'show']);
    Route::post('/{id}/audit', [App\Modules\Merchant\Controllers\MerchantController::class, 'audit']);
    Route::post('/{id}/toggle-status', [App\Modules\Merchant\Controllers\MerchantController::class, 'toggleStatus']);
    Route::put('/{id}', [App\Modules\Merchant\Controllers\MerchantController::class, 'update']);
    Route::delete('/{id}', [App\Modules\Merchant\Controllers\MerchantController::class, 'destroy']);
});

// 优惠券管理
Route::prefix('admin/coupons')->middleware('auth:sanctum')->group(function () {
    Route::get('/records', [App\Modules\Marketing\Controllers\CouponController::class, 'records']);
    Route::get('/', [App\Modules\Marketing\Controllers\CouponController::class, 'index']);
    Route::get('/{id}', [App\Modules\Marketing\Controllers\CouponController::class, 'show']);
    Route::post('/', [App\Modules\Marketing\Controllers\CouponController::class, 'store']);
    Route::put('/{id}', [App\Modules\Marketing\Controllers\CouponController::class, 'update']);
    Route::post('/{id}/toggle-status', [App\Modules\Marketing\Controllers\CouponController::class, 'toggleStatus']);
    Route::delete('/{id}', [App\Modules\Marketing\Controllers\CouponController::class, 'destroy']);
});

// 财务管理
Route::prefix('admin/finance')->middleware('auth:sanctum')->group(function () {
    Route::get('/income', [App\Modules\Finance\Controllers\FinanceController::class, 'income']);
    Route::get('/refund', [App\Modules\Finance\Controllers\FinanceController::class, 'refund']);
    Route::get('/withdraw', [App\Modules\Finance\Controllers\FinanceController::class, 'withdraw']);
    Route::post('/withdraw/{id}/audit', [App\Modules\Finance\Controllers\FinanceController::class, 'withdrawAudit']);
    Route::post('/withdraw/{id}/pay', [App\Modules\Finance\Controllers\FinanceController::class, 'withdrawPay']);
    Route::get('/settlement', [App\Modules\Finance\Controllers\FinanceController::class, 'settlement']);
    Route::post('/settlement/{id}/confirm', [App\Modules\Finance\Controllers\FinanceController::class, 'settlementConfirm']);
});

// 系统设置
Route::prefix('admin/system')->middleware('auth:sanctum')->group(function () {
    Route::get('/config', [App\Modules\System\Controllers\SystemController::class, 'getConfig']);
    Route::post('/config', [App\Modules\System\Controllers\SystemController::class, 'saveConfig']);
    Route::get('/express', [App\Modules\System\Controllers\SystemController::class, 'expressList']);
    Route::post('/express', [App\Modules\System\Controllers\SystemController::class, 'expressStore']);
    Route::put('/express/{id}', [App\Modules\System\Controllers\SystemController::class, 'expressUpdate']);
    Route::delete('/express/{id}', [App\Modules\System\Controllers\SystemController::class, 'expressDestroy']);
    Route::get('/logs', [App\Modules\System\Controllers\SystemController::class, 'logList']);
});

// 分销管理
Route::prefix('admin/distribute')->middleware('auth:sanctum')->group(function () {
    Route::get('/overview', [App\Modules\Distribute\Controllers\DistributeController::class, 'overview']);
    Route::get('/agents', [App\Modules\Distribute\Controllers\DistributeController::class, 'agents']);
    Route::post('/agents/{id}/audit', [App\Modules\Distribute\Controllers\DistributeController::class, 'agentAudit']);
    Route::get('/levels', [App\Modules\Distribute\Controllers\DistributeController::class, 'levels']);
    Route::post('/levels', [App\Modules\Distribute\Controllers\DistributeController::class, 'levelStore']);
    Route::put('/levels/{id}', [App\Modules\Distribute\Controllers\DistributeController::class, 'levelUpdate']);
    Route::get('/orders', [App\Modules\Distribute\Controllers\DistributeController::class, 'orders']);
    Route::get('/goods', [App\Modules\Distribute\Controllers\DistributeController::class, 'goods']);
    Route::post('/goods/{id}/toggle', [App\Modules\Distribute\Controllers\DistributeController::class, 'goodsToggle']);
    Route::get('/settings', [App\Modules\Distribute\Controllers\DistributeController::class, 'getSettings']);
    Route::post('/settings', [App\Modules\Distribute\Controllers\DistributeController::class, 'saveSettings']);
});
