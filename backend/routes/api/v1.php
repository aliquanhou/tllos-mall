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
