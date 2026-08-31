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

// 营销活动
Route::prefix('admin/marketing')->middleware('auth:sanctum')->group(function () {
    Route::get('/seckill', [App\Modules\Marketing\Controllers\MarketingController::class, 'seckillList']);
    Route::post('/seckill', [App\Modules\Marketing\Controllers\MarketingController::class, 'seckillStore']);
    Route::put('/seckill/{id}', [App\Modules\Marketing\Controllers\MarketingController::class, 'seckillUpdate']);
    Route::delete('/seckill/{id}', [App\Modules\Marketing\Controllers\MarketingController::class, 'seckillDestroy']);
    Route::get('/seckill/{id}/goods', [App\Modules\Marketing\Controllers\MarketingController::class, 'seckillGoods']);
    Route::get('/group', [App\Modules\Marketing\Controllers\MarketingController::class, 'groupList']);
    Route::post('/group', [App\Modules\Marketing\Controllers\MarketingController::class, 'groupStore']);
    Route::put('/group/{id}', [App\Modules\Marketing\Controllers\MarketingController::class, 'groupUpdate']);
    Route::delete('/group/{id}', [App\Modules\Marketing\Controllers\MarketingController::class, 'groupDestroy']);
    Route::get('/discount', [App\Modules\Marketing\Controllers\MarketingController::class, 'discountList']);
    Route::post('/discount', [App\Modules\Marketing\Controllers\MarketingController::class, 'discountStore']);
    Route::put('/discount/{id}', [App\Modules\Marketing\Controllers\MarketingController::class, 'discountUpdate']);
    Route::delete('/discount/{id}', [App\Modules\Marketing\Controllers\MarketingController::class, 'discountDestroy']);
});

// 应用管理
Route::prefix('admin/application')->middleware('auth:sanctum')->group(function () {
    Route::get('/deposit', [App\Modules\Application\Controllers\ApplicationController::class, 'depositList']);
    Route::get('/material', [App\Modules\Application\Controllers\ApplicationController::class, 'materialList']);
    Route::get('/article', [App\Modules\Application\Controllers\ApplicationController::class, 'articleList']);
    Route::post('/article', [App\Modules\Application\Controllers\ApplicationController::class, 'articleStore']);
    Route::put('/article/{id}', [App\Modules\Application\Controllers\ApplicationController::class, 'articleUpdate']);
    Route::delete('/article/{id}', [App\Modules\Application\Controllers\ApplicationController::class, 'articleDestroy']);
    Route::get('/article-categories', [App\Modules\Application\Controllers\ApplicationController::class, 'articleCategories']);
    Route::get('/notice', [App\Modules\Application\Controllers\ApplicationController::class, 'noticeList']);
    Route::post('/notice', [App\Modules\Application\Controllers\ApplicationController::class, 'noticeStore']);
    Route::put('/notice/{id}', [App\Modules\Application\Controllers\ApplicationController::class, 'noticeUpdate']);
    Route::delete('/notice/{id}', [App\Modules\Application\Controllers\ApplicationController::class, 'noticeDestroy']);
    Route::get('/kefu', [App\Modules\Application\Controllers\ApplicationController::class, 'kefuSetting']);
    Route::post('/kefu', [App\Modules\Application\Controllers\ApplicationController::class, 'kefuSave']);
    Route::get('/collect', [App\Modules\Application\Controllers\ApplicationController::class, 'collectList']);
});

// 权限管理
Route::prefix('admin/permission')->middleware('auth:sanctum')->group(function () {
    Route::get('/role', [App\Modules\Permission\Controllers\PermissionController::class, 'roleList']);
    Route::post('/role', [App\Modules\Permission\Controllers\PermissionController::class, 'roleStore']);
    Route::put('/role/{id}', [App\Modules\Permission\Controllers\PermissionController::class, 'roleUpdate']);
    Route::delete('/role/{id}', [App\Modules\Permission\Controllers\PermissionController::class, 'roleDestroy']);
    Route::get('/menu', [App\Modules\Permission\Controllers\PermissionController::class, 'menuList']);
    Route::get('/dept', [App\Modules\Permission\Controllers\PermissionController::class, 'deptList']);
    Route::post('/dept', [App\Modules\Permission\Controllers\PermissionController::class, 'deptStore']);
    Route::put('/dept/{id}', [App\Modules\Permission\Controllers\PermissionController::class, 'deptUpdate']);
    Route::delete('/dept/{id}', [App\Modules\Permission\Controllers\PermissionController::class, 'deptDestroy']);
});

// 订单售后
Route::prefix('admin/after-sale')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\AfterSale\Controllers\AfterSaleController::class, 'index']);
    Route::get('/{id}', [App\Modules\AfterSale\Controllers\AfterSaleController::class, 'show']);
    Route::post('/{id}/audit', [App\Modules\AfterSale\Controllers\AfterSaleController::class, 'audit']);
    Route::post('/{id}/complete', [App\Modules\AfterSale\Controllers\AfterSaleController::class, 'complete']);
});

// 用户中心
Route::prefix('admin/user-center')->middleware('auth:sanctum')->group(function () {
    Route::get('/levels', [App\Modules\UserCenter\Controllers\UserCenterController::class, 'levels']);
    Route::post('/levels', [App\Modules\UserCenter\Controllers\UserCenterController::class, 'levelStore']);
    Route::put('/levels/{id}', [App\Modules\UserCenter\Controllers\UserCenterController::class, 'levelUpdate']);
    Route::delete('/levels/{id}', [App\Modules\UserCenter\Controllers\UserCenterController::class, 'levelDestroy']);
    Route::get('/recharges', [App\Modules\UserCenter\Controllers\UserCenterController::class, 'recharges']);
    Route::get('/withdraws', [App\Modules\UserCenter\Controllers\UserCenterController::class, 'withdraws']);
    Route::post('/withdraws/{id}/audit', [App\Modules\UserCenter\Controllers\UserCenterController::class, 'withdrawAudit']);
    Route::post('/withdraws/{id}/pay', [App\Modules\UserCenter\Controllers\UserCenterController::class, 'withdrawPay']);
    Route::get('/addresses', [App\Modules\UserCenter\Controllers\UserCenterController::class, 'addresses']);
    Route::get('/account-logs', [App\Modules\UserCenter\Controllers\UserCenterController::class, 'accountLogs']);
});

// 商家中心
Route::prefix('admin/shop-center')->middleware('auth:sanctum')->group(function () {
    Route::get('/categories', [App\Modules\ShopCenter\Controllers\ShopCenterController::class, 'categories']);
    Route::post('/categories', [App\Modules\ShopCenter\Controllers\ShopCenterController::class, 'categoryStore']);
    Route::put('/categories/{id}', [App\Modules\ShopCenter\Controllers\ShopCenterController::class, 'categoryUpdate']);
    Route::delete('/categories/{id}', [App\Modules\ShopCenter\Controllers\ShopCenterController::class, 'categoryDestroy']);
    Route::get('/banks/{shop_id}', [App\Modules\ShopCenter\Controllers\ShopCenterController::class, 'banks']);
    Route::get('/account-logs', [App\Modules\ShopCenter\Controllers\ShopCenterController::class, 'accountLogs']);
});

// 分销申请
Route::prefix('admin/distribute/apply')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\Distribute\Controllers\DistributionApplyController::class, 'index']);
    Route::post('/{id}/audit', [App\Modules\Distribute\Controllers\DistributionApplyController::class, 'audit']);
});

// 商城公告
Route::prefix('admin/announcement')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\Announcement\Controllers\AnnouncementController::class, 'index']);
    Route::post('/', [App\Modules\Announcement\Controllers\AnnouncementController::class, 'store']);
    Route::put('/{id}', [App\Modules\Announcement\Controllers\AnnouncementController::class, 'update']);
    Route::delete('/{id}', [App\Modules\Announcement\Controllers\AnnouncementController::class, 'destroy']);
});

// 装修管理
Route::prefix('admin/decorate')->middleware('auth:sanctum')->group(function () {
    Route::get('/pages', [App\Modules\Decorate\Controllers\DecorateController::class, 'pages']);
    Route::put('/pages/{id}', [App\Modules\Decorate\Controllers\DecorateController::class, 'pageSave']);
    Route::get('/tabbars', [App\Modules\Decorate\Controllers\DecorateController::class, 'tabbars']);
    Route::post('/tabbars', [App\Modules\Decorate\Controllers\DecorateController::class, 'tabbarStore']);
    Route::put('/tabbars/{id}', [App\Modules\Decorate\Controllers\DecorateController::class, 'tabbarUpdate']);
    Route::delete('/tabbars/{id}', [App\Modules\Decorate\Controllers\DecorateController::class, 'tabbarDestroy']);
    Route::get('/category-ads', [App\Modules\Decorate\Controllers\DecorateController::class, 'categoryAds']);
});

// 支付场景
Route::prefix('admin/pay-scene')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\Pay\Controllers\PaySceneController::class, 'index']);
    Route::post('/', [App\Modules\Pay\Controllers\PaySceneController::class, 'store']);
    Route::put('/{id}', [App\Modules\Pay\Controllers\PaySceneController::class, 'update']);
    Route::delete('/{id}', [App\Modules\Pay\Controllers\PaySceneController::class, 'destroy']);
});

// 结算记录
Route::prefix('admin/settlement-record')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\Finance\Controllers\SettlementRecordController::class, 'index']);
});

// 系统配置
Route::prefix('admin/system-config')->middleware('auth:sanctum')->group(function () {
    Route::get('/dict-types', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'dictTypes']);
    Route::post('/dict-types', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'dictTypeStore']);
    Route::put('/dict-types/{id}', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'dictTypeUpdate']);
    Route::delete('/dict-types/{id}', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'dictTypeDestroy']);
    Route::get('/dict-datas', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'dictDatas']);
    Route::post('/dict-datas', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'dictDataStore']);
    Route::put('/dict-datas/{id}', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'dictDataUpdate']);
    Route::delete('/dict-datas/{id}', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'dictDataDestroy']);
    Route::get('/hot-searches', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'hotSearches']);
    Route::post('/hot-searches', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'hotSearchStore']);
    Route::put('/hot-searches/{id}', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'hotSearchUpdate']);
    Route::delete('/hot-searches/{id}', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'hotSearchDestroy']);
    Route::get('/crontabs', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'crontabs']);
    Route::post('/crontabs/{id}/toggle', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'crontabToggle']);
    Route::get('/areas', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'areas']);
    Route::get('/express-templates', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'expressTemplates']);
    Route::post('/express-templates', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'expressTemplateStore']);
    Route::put('/express-templates/{id}', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'expressTemplateUpdate']);
    Route::delete('/express-templates/{id}', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'expressTemplateDestroy']);
    Route::get('/files', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'files']);
    Route::get('/file-categories', [App\Modules\SystemConfig\Controllers\SystemConfigController::class, 'fileCategories']);
});

// 管理员管理
Route::prefix('admin/admin-manage')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\AdminManage\Controllers\AdminManageController::class, 'index']);
    Route::post('/', [App\Modules\AdminManage\Controllers\AdminManageController::class, 'store']);
    Route::put('/{id}', [App\Modules\AdminManage\Controllers\AdminManageController::class, 'update']);
    Route::delete('/{id}', [App\Modules\AdminManage\Controllers\AdminManageController::class, 'destroy']);
});

// 岗位管理
Route::prefix('admin/jobs')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\AdminManage\Controllers\JobController::class, 'index']);
    Route::get('/all', [App\Modules\AdminManage\Controllers\JobController::class, 'all']);
    Route::post('/', [App\Modules\AdminManage\Controllers\JobController::class, 'store']);
    Route::put('/{id}', [App\Modules\AdminManage\Controllers\JobController::class, 'update']);
    Route::delete('/{id}', [App\Modules\AdminManage\Controllers\JobController::class, 'destroy']);
});

// 配送方式
Route::prefix('admin/delivery-type')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\SystemConfig\Controllers\DeliveryTypeController::class, 'index']);
    Route::post('/', [App\Modules\SystemConfig\Controllers\DeliveryTypeController::class, 'store']);
    Route::put('/{id}', [App\Modules\SystemConfig\Controllers\DeliveryTypeController::class, 'update']);
    Route::delete('/{id}', [App\Modules\SystemConfig\Controllers\DeliveryTypeController::class, 'destroy']);
});

// 订单设置
Route::prefix('admin/order-setting')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\SystemConfig\Controllers\OrderSettingController::class, 'getConfig']);
    Route::post('/', [App\Modules\SystemConfig\Controllers\OrderSettingController::class, 'saveConfig']);
});

// 拼团开团
Route::prefix('admin/pt-open')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\Marketing\Controllers\PtOpenController::class, 'index']);
});

// 商家菜单
Route::prefix('admin/shop-menu')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\ShopCenter\Controllers\ShopMenuController::class, 'index']);
    Route::post('/', [App\Modules\ShopCenter\Controllers\ShopMenuController::class, 'store']);
    Route::put('/{id}', [App\Modules\ShopCenter\Controllers\ShopMenuController::class, 'update']);
    Route::delete('/{id}', [App\Modules\ShopCenter\Controllers\ShopMenuController::class, 'destroy']);
});

// 通知设置
Route::prefix('admin/notice-setting')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\SystemConfig\Controllers\NoticeSettingController::class, 'index']);
    Route::put('/{id}', [App\Modules\SystemConfig\Controllers\NoticeSettingController::class, 'update']);
});

// 短信配置
Route::prefix('admin/sms-config')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\SystemConfig\Controllers\SmsConfigController::class, 'getConfig']);
    Route::post('/', [App\Modules\SystemConfig\Controllers\SmsConfigController::class, 'setConfig']);
});

// 存储设置
Route::prefix('admin/storage')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\SystemConfig\Controllers\StorageController::class, 'detail']);
    Route::post('/', [App\Modules\SystemConfig\Controllers\StorageController::class, 'setup']);
    Route::post('/change', [App\Modules\SystemConfig\Controllers\StorageController::class, 'change']);
});

// 系统缓存
Route::prefix('admin/cache')->middleware('auth:sanctum')->group(function () {
    Route::post('/clear', [App\Modules\SystemConfig\Controllers\CacheController::class, 'clear']);
});

// 交易设置
Route::prefix('admin/transaction-setting')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\SystemConfig\Controllers\TransactionSettingController::class, 'getConfig']);
    Route::post('/', [App\Modules\SystemConfig\Controllers\TransactionSettingController::class, 'setConfig']);
});

// 用户设置
Route::prefix('admin/user-setting')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Modules\SystemConfig\Controllers\UserSettingController::class, 'getConfig']);
    Route::post('/', [App\Modules\SystemConfig\Controllers\UserSettingController::class, 'setConfig']);
    Route::get('/register', [App\Modules\SystemConfig\Controllers\UserSettingController::class, 'getRegisterConfig']);
    Route::post('/register', [App\Modules\SystemConfig\Controllers\UserSettingController::class, 'setRegisterConfig']);
});

// 网站设置
Route::prefix('admin/web-setting')->middleware('auth:sanctum')->group(function () {
    Route::get('/website', [App\Modules\SystemConfig\Controllers\WebSettingController::class, 'getWebsite']);
    Route::post('/website', [App\Modules\SystemConfig\Controllers\WebSettingController::class, 'setWebsite']);
    Route::get('/agreement', [App\Modules\SystemConfig\Controllers\WebSettingController::class, 'getAgreement']);
    Route::post('/agreement', [App\Modules\SystemConfig\Controllers\WebSettingController::class, 'setAgreement']);
    Route::get('/copyright', [App\Modules\SystemConfig\Controllers\WebSettingController::class, 'getCopyright']);
    Route::post('/copyright', [App\Modules\SystemConfig\Controllers\WebSettingController::class, 'setCopyright']);
});
