
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
Route::get('profile', [\App\Modules\Admin\Controllers\AuthController::class, 'profile']);
Route::post('logout', [\App\Modules\Admin\Controllers\AuthController::class, 'logout']);

    // 工作台统计
    Route::get('dashboard/stats', [App\Modules\Admin\Controllers\DashboardController::class, 'stats']);
    Route::get('dashboard/recent-orders', [App\Modules\Admin\Controllers\DashboardController::class, 'recentOrders']);
    Route::get('dashboard/sales-trend', [App\Modules\Admin\Controllers\DashboardController::class, 'salesTrend']);

    // 品牌管理    Route::get("/brands", [\App\Modules\Product\Controllers\BrandController::class,"index"]);    Route::get("/brands/all", [\App\Modules\Product\Controllers\BrandController::class,"all"]);    Route::post("/brands", [\App\Modules\Product\Controllers\BrandController::class,"store"]);    Route::put("/brands/{id}", [\App\Modules\Product\Controllers\BrandController::class,"update"]);    Route::delete("/brands/{id}", [\App\Modules\Product\Controllers\BrandController::class,"destroy"]);    // 商品类型    Route::get("/product-types", [\App\Modules\Product\Controllers\ProductTypeController::class,"index"]);    Route::post("/product-types", [\App\Modules\Product\Controllers\ProductTypeController::class,"store"]);    Route::put("/product-types/{id}", [\App\Modules\Product\Controllers\ProductTypeController::class,"update"]);    Route::delete("/product-types/{id}", [\App\Modules\Product\Controllers\ProductTypeController::class,"destroy"]);    // 库存预警    Route::get("/stock-warnings", [\App\Modules\Product\Controllers\StockWarningController::class,"index"]);    Route::post("/stock-warnings/setting", [\App\Modules\Product\Controllers\StockWarningController::class,"setting"]);    // 用户积分    Route::get("/user-points", [\App\Modules\UserCenter\Controllers\PointLogController::class,"index"]);    Route::post("/user-points", [\App\Modules\UserCenter\Controllers\PointLogController::class,"store"]);    // 用户收藏    Route::get("/user-favorites", [\App\Modules\UserCenter\Controllers\FavoriteController::class,"index"]);    Route::delete("/user-favorites/{id}", [\App\Modules\UserCenter\Controllers\FavoriteController::class,"destroy"]);    // 砍价活动    Route::get("/bargains", [\App\Modules\Marketing\Controllers\BargainController::class,"index"]);    Route::post("/bargains", [\App\Modules\Marketing\Controllers\BargainController::class,"store"]);    Route::put("/bargains/{id}", [\App\Modules\Marketing\Controllers\BargainController::class,"update"]);    Route::delete("/bargains/{id}", [\App\Modules\Marketing\Controllers\BargainController::class,"destroy"]);    Route::get("/bargains/records", [\App\Modules\Marketing\Controllers\BargainController::class,"records"]);    // 短信配置    Route::get("/sms-configs", [\App\Modules\SystemConfig\Controllers\SmsConfigController::class,"index"]);    Route::post("/sms-configs", [\App\Modules\SystemConfig\Controllers\SmsConfigController::class,"store"]);    Route::put("/sms-configs/{id}", [\App\Modules\SystemConfig\Controllers\SmsConfigController::class,"update"]);    Route::delete("/sms-configs/{id}", [\App\Modules\SystemConfig\Controllers\SmsConfigController::class,"destroy"]);    // 存储配置    Route::get("/storage-configs", [\App\Modules\SystemConfig\Controllers\StorageConfigController::class,"index"]);    Route::post("/storage-configs", [\App\Modules\SystemConfig\Controllers\StorageConfigController::class,"store"]);    Route::put("/storage-configs/{id}", [\App\Modules\SystemConfig\Controllers\StorageConfigController::class,"update"]);    Route::delete("/storage-configs/{id}", [\App\Modules\SystemConfig\Controllers\StorageConfigController::class,"destroy"]);    // 定时任务    Route::get("/crontabs", [\App\Modules\SystemConfig\Controllers\CrontabController::class,"index"]);    Route::post("/crontabs", [\App\Modules\SystemConfig\Controllers\CrontabController::class,"store"]);    Route::put("/crontabs/{id}", [\App\Modules\SystemConfig\Controllers\CrontabController::class,"update"]);    Route::delete("/crontabs/{id}", [\App\Modules\SystemConfig\Controllers\CrontabController::class,"destroy"]);    Route::post("/crontabs/{id}/run", [\App\Modules\SystemConfig\Controllers\CrontabController::class,"run"]);    // 文件管理    Route::get("/file-managers", [\App\Modules\SystemConfig\Controllers\FileManagerController::class,"index"]);    Route::delete("/file-managers/{id}", [\App\Modules\SystemConfig\Controllers\FileManagerController::class,"destroy"]);    // 系统信息    Route::get("/system-info", [\App\Modules\SystemConfig\Controllers\SystemInfoController::class,"index"]);    // 商家等级
    Route::get("/merchant-levels/{id}", [\App\Modules\Merchant\Controllers\MerchantLevelController::class,"show"]);
    Route::get("/merchant-levels", [\App\Modules\Merchant\Controllers\MerchantLevelController::class,"index"]);    Route::post("/merchant-levels", [\App\Modules\Merchant\Controllers\MerchantLevelController::class,"store"]);    Route::put("/merchant-levels/{id}", [\App\Modules\Merchant\Controllers\MerchantLevelController::class,"update"]);    Route::delete("/merchant-levels/{id}", [\App\Modules\Merchant\Controllers\MerchantLevelController::class,"destroy"]);    // 商品采集
    Route::get('/gathers', [\App\Modules\Application\Controllers\GatherController::class,'index']);
    Route::post('/gathers', [\App\Modules\Application\Controllers\GatherController::class,'store']);
    Route::put('/gathers/{id}', [\App\Modules\Application\Controllers\GatherController::class,'update']);
    Route::delete('/gathers/{id}', [\App\Modules\Application\Controllers\GatherController::class,'destroy']);
    Route::post('/gathers/{id}/run', [\App\Modules\Application\Controllers\GatherController::class,'run']);

    // 热门搜索
    Route::get('/hot-searches', [\App\Modules\SystemConfig\Controllers\HotSearchController::class,'index']);
    Route::post('/hot-searches', [\App\Modules\SystemConfig\Controllers\HotSearchController::class,'store']);
    Route::put('/hot-searches/{id}', [\App\Modules\SystemConfig\Controllers\HotSearchController::class,'update']);
    Route::delete('/hot-searches/{id}', [\App\Modules\SystemConfig\Controllers\HotSearchController::class,'destroy']);

    // 代码生成器
    Route::get('/generator/tables', [\App\Modules\Tools\Controllers\GeneratorController::class,'tables']);
    Route::get('/generator/columns/{table}', [\App\Modules\Tools\Controllers\GeneratorController::class,'columns']);
    Route::post('/generator/generate', [\App\Modules\Tools\Controllers\GeneratorController::class,'generate']);

    // 兼容路由（前端复数形式）    Route::get("/announcements", [\App\Modules\Announcement\Controllers\AnnouncementController::class,"index"]);    Route::post("/announcements", [\App\Modules\Announcement\Controllers\AnnouncementController::class,"store"]);    Route::get("/roles", [\App\Modules\Permission\Controllers\AdminRoleController::class,"index"]);    Route::post("/roles", [\App\Modules\Permission\Controllers\AdminRoleController::class,"store"]);    Route::get("/menus", [\App\Modules\Permission\Controllers\PermissionController::class,"index"]);

    // 品牌管理
    Route::get('/brands', [\App\Modules\Product\Controllers\BrandController::class,'index']);
    Route::get('/brands/all', [\App\Modules\Product\Controllers\BrandController::class,'all']);
    Route::post('/brands', [\App\Modules\Product\Controllers\BrandController::class,'store']);
    Route::put('/brands/{id}', [\App\Modules\Product\Controllers\BrandController::class,'update']);
    Route::delete('/brands/{id}', [\App\Modules\Product\Controllers\BrandController::class,'destroy']);

    // 商品类型
    Route::get('/product-types', [\App\Modules\Product\Controllers\ProductTypeController::class,'index']);
    Route::post('/product-types', [\App\Modules\Product\Controllers\ProductTypeController::class,'store']);
    Route::put('/product-types/{id}', [\App\Modules\Product\Controllers\ProductTypeController::class,'update']);
    Route::delete('/product-types/{id}', [\App\Modules\Product\Controllers\ProductTypeController::class,'destroy']);

    // 库存预警
    Route::get('/stock-warnings', [\App\Modules\Product\Controllers\StockWarningController::class,'index']);
    Route::post('/stock-warnings/setting', [\App\Modules\Product\Controllers\StockWarningController::class,'setting']);

    // 用户积分
    Route::get('/user-points/rules', [\App\Modules\UserCenter\Controllers\PointLogController::class,'rules']);
    Route::get('/user-points', [\App\Modules\UserCenter\Controllers\PointLogController::class,'index']);
    Route::post('/user-points', [\App\Modules\UserCenter\Controllers\PointLogController::class,'store']);

    // 用户收藏
    Route::get('/user-favorites', [\App\Modules\UserCenter\Controllers\FavoriteController::class,'index']);
    Route::delete('/user-favorites/{id}', [\App\Modules\UserCenter\Controllers\FavoriteController::class,'destroy']);

    // 砍价活动
    Route::get('/bargains', [\App\Modules\Marketing\Controllers\BargainController::class,'index']);
    Route::post('/bargains', [\App\Modules\Marketing\Controllers\BargainController::class,'store']);
    Route::put('/bargains/{id}', [\App\Modules\Marketing\Controllers\BargainController::class,'update']);
    Route::delete('/bargains/{id}', [\App\Modules\Marketing\Controllers\BargainController::class,'destroy']);
    Route::get('/bargains/records', [\App\Modules\Marketing\Controllers\BargainController::class,'records']);

    // 短信配置
    Route::get('/sms-configs', [\App\Modules\SystemConfig\Controllers\SmsConfigController::class,'index']);
    Route::post('/sms-configs', [\App\Modules\SystemConfig\Controllers\SmsConfigController::class,'store']);
    Route::put('/sms-configs/{id}', [\App\Modules\SystemConfig\Controllers\SmsConfigController::class,'update']);
    Route::delete('/sms-configs/{id}', [\App\Modules\SystemConfig\Controllers\SmsConfigController::class,'destroy']);

    // 存储配置
    Route::get('/storage-configs', [\App\Modules\SystemConfig\Controllers\StorageConfigController::class,'index']);
    Route::post('/storage-configs', [\App\Modules\SystemConfig\Controllers\StorageConfigController::class,'store']);
    Route::put('/storage-configs/{id}', [\App\Modules\SystemConfig\Controllers\StorageConfigController::class,'update']);
    Route::delete('/storage-configs/{id}', [\App\Modules\SystemConfig\Controllers\StorageConfigController::class,'destroy']);

    // 定时任务
    Route::get('/crontabs', [\App\Modules\SystemConfig\Controllers\CrontabController::class,'index']);
    Route::post('/crontabs', [\App\Modules\SystemConfig\Controllers\CrontabController::class,'store']);
    Route::put('/crontabs/{id}', [\App\Modules\SystemConfig\Controllers\CrontabController::class,'update']);
    Route::delete('/crontabs/{id}', [\App\Modules\SystemConfig\Controllers\CrontabController::class,'destroy']);
    Route::post('/crontabs/{id}/run', [\App\Modules\SystemConfig\Controllers\CrontabController::class,'run']);

    // 文件管理
    Route::get('/file-managers', [\App\Modules\SystemConfig\Controllers\FileManagerController::class,'index']);
    Route::delete('/file-managers/{id}', [\App\Modules\SystemConfig\Controllers\FileManagerController::class,'destroy']);

    // 系统信息
    Route::get('/system-info', [\App\Modules\SystemConfig\Controllers\SystemInfoController::class,'index']);

    // 商家等级
    Route::get('/merchant-levels', [\App\Modules\Merchant\Controllers\MerchantLevelController::class,'index']);
    Route::post('/merchant-levels', [\App\Modules\Merchant\Controllers\MerchantLevelController::class,'store']);
    Route::put('/merchant-levels/{id}', [\App\Modules\Merchant\Controllers\MerchantLevelController::class,'update']);
    Route::delete('/merchant-levels/{id}', [\App\Modules\Merchant\Controllers\MerchantLevelController::class,'destroy']);
    // 商家分类
    Route::get("/merchant-categories/tree", [App\Modules\ShopCenter\Controllers\CategoryController::class, "tree"]);
    Route::get("/merchant-categories", [App\Modules\ShopCenter\Controllers\CategoryController::class, "index"]);
    Route::post("/merchant-categories", [App\Modules\ShopCenter\Controllers\CategoryController::class, "store"]);
    Route::put("/merchant-categories/{id}", [App\Modules\ShopCenter\Controllers\CategoryController::class, "update"]);
    Route::delete("/merchant-categories/{id}", [App\Modules\ShopCenter\Controllers\CategoryController::class, "destroy"]);
    // 商家账户日志
    Route::get("/merchant-account-logs/stats", [App\Modules\Finance\Controllers\MerchantAccountLogController::class, "stats"]);
Route::get("/merchant-account-logs/{id}", [App\Modules\Finance\Controllers\MerchantAccountLogController::class, "show"]);
Route::get("/merchant-account-logs", [App\Modules\Finance\Controllers\MerchantAccountLogController::class, "index"]);

    // 兼容路由
    Route::get('/announcements', [\App\Modules\Announcement\Controllers\AnnouncementController::class,'index']);
    Route::post('/announcements', [\App\Modules\Announcement\Controllers\AnnouncementController::class,'store']);
    Route::get('/roles', [\App\Modules\Permission\Controllers\AdminRoleController::class,'index']);
    Route::post('/roles', [\App\Modules\Permission\Controllers\AdminRoleController::class,'store']);
    Route::get('/menus', [\App\Modules\Permission\Controllers\PermissionController::class,'index']);});



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

    // 工作台统计
    Route::get('dashboard/stats', [App\Modules\Admin\Controllers\DashboardController::class, 'stats']);
    Route::get('dashboard/recent-orders', [App\Modules\Admin\Controllers\DashboardController::class, 'recentOrders']);
    Route::get('dashboard/sales-trend', [App\Modules\Admin\Controllers\DashboardController::class, 'salesTrend']);




    // 限时秒杀
    Route::prefix('seckills')->group(function () {
        Route::get('/', [\App\Modules\Marketing\Controllers\AdminSeckillController::class, 'index']);
        Route::get('{id}', [\App\Modules\Marketing\Controllers\AdminSeckillController::class, 'show']);
        Route::post('/', [\App\Modules\Marketing\Controllers\AdminSeckillController::class, 'store']);
        Route::put('{id}', [\App\Modules\Marketing\Controllers\AdminSeckillController::class, 'update']);
        Route::delete('{id}', [\App\Modules\Marketing\Controllers\AdminSeckillController::class, 'destroy']);
    });
    // 拼团活动
    Route::prefix('groups')->group(function () {
        Route::get('/', [\App\Modules\Marketing\Controllers\AdminGroupController::class, 'index']);
        Route::get('{id}', [\App\Modules\Marketing\Controllers\AdminGroupController::class, 'show']);
        Route::post('/', [\App\Modules\Marketing\Controllers\AdminGroupController::class, 'store']);
        Route::put('{id}', [\App\Modules\Marketing\Controllers\AdminGroupController::class, 'update']);
        Route::delete('{id}', [\App\Modules\Marketing\Controllers\AdminGroupController::class, 'destroy']);
    });
    // 文章资讯
    Route::prefix('articles')->group(function () {
        Route::get('/', [\App\Modules\Application\Controllers\AdminArticleController::class, 'index']);
        Route::get('{id}', [\App\Modules\Application\Controllers\AdminArticleController::class, 'show']);
        Route::post('/', [\App\Modules\Application\Controllers\AdminArticleController::class, 'store']);
        Route::put('{id}', [\App\Modules\Application\Controllers\AdminArticleController::class, 'update']);
        Route::delete('{id}', [\App\Modules\Application\Controllers\AdminArticleController::class, 'destroy']);
    });
    // 消息管理
    Route::prefix('notices')->group(function () {
        Route::get('/', [\App\Modules\Application\Controllers\AdminNoticeController::class, 'index']);
        Route::get('{id}', [\App\Modules\Application\Controllers\AdminNoticeController::class, 'show']);
        Route::post('/', [\App\Modules\Application\Controllers\AdminNoticeController::class, 'store']);
        Route::put('{id}', [\App\Modules\Application\Controllers\AdminNoticeController::class, 'update']);
        Route::delete('{id}', [\App\Modules\Application\Controllers\AdminNoticeController::class, 'destroy']);
    });
    // 角色管理
    Route::prefix('roles')->group(function () {
        Route::get('/', [\App\Modules\Permission\Controllers\AdminRoleController::class, 'index']);
        Route::get('{id}', [\App\Modules\Permission\Controllers\AdminRoleController::class, 'show']);
        Route::post('/', [\App\Modules\Permission\Controllers\AdminRoleController::class, 'store']);
        Route::put('{id}', [\App\Modules\Permission\Controllers\AdminRoleController::class, 'update']);
        Route::delete('{id}', [\App\Modules\Permission\Controllers\AdminRoleController::class, 'destroy']);
    });
    // 系统设置
    Route::prefix('settings')->group(function () {
        Route::get('/', [\App\Modules\System\Controllers\AdminSettingController::class, 'index']);
        Route::get('{id}', [\App\Modules\System\Controllers\AdminSettingController::class, 'show']);
        Route::post('/', [\App\Modules\System\Controllers\AdminSettingController::class, 'store']);
        Route::put('{id}', [\App\Modules\System\Controllers\AdminSettingController::class, 'update']);
        Route::delete('{id}', [\App\Modules\System\Controllers\AdminSettingController::class, 'destroy']);
    });
    // 数据字典
    Route::prefix('dicts')->group(function () {
        Route::get('/', [\App\Modules\System\Controllers\AdminDictController::class, 'index']);
        Route::get('{id}', [\App\Modules\System\Controllers\AdminDictController::class, 'show']);
        Route::post('/', [\App\Modules\System\Controllers\AdminDictController::class, 'store']);
        Route::put('{id}', [\App\Modules\System\Controllers\AdminDictController::class, 'update']);
        Route::delete('{id}', [\App\Modules\System\Controllers\AdminDictController::class, 'destroy']);
    });
    // 地区管理
    Route::prefix('areas')->group(function () {
        Route::get('/', [\App\Modules\System\Controllers\AdminAreaController::class, 'index']);
        Route::get('{id}', [\App\Modules\System\Controllers\AdminAreaController::class, 'show']);
        Route::post('/', [\App\Modules\System\Controllers\AdminAreaController::class, 'store']);
        Route::put('{id}', [\App\Modules\System\Controllers\AdminAreaController::class, 'update']);
        Route::delete('{id}', [\App\Modules\System\Controllers\AdminAreaController::class, 'destroy']);
    });

    // 商品管理

    Route::prefix('products')->group(function () {

        // 商品批量操作（必须在{id}路由之前）
        Route::put('/batch', [\App\Modules\Product\Controllers\AdminProductController::class, 'batchUpdate']);
        Route::delete('/batch', [\App\Modules\Product\Controllers\AdminProductController::class, 'batchDelete']);

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
    Route::post('/', [App\Modules\Merchant\Controllers\MerchantController::class, 'store']);
    Route::post('/draft', [App\Modules\Merchant\Controllers\MerchantController::class, 'draft']);
    Route::get('/reject-templates', [App\Modules\Merchant\Controllers\MerchantController::class, 'rejectTemplates']);
    Route::get('/audit-stats', [App\Modules\Merchant\Controllers\MerchantController::class, 'auditStats']);
    Route::get('/{id}', [App\Modules\Merchant\Controllers\MerchantController::class, 'show']);
    Route::post('/{id}/audit', [App\Modules\Merchant\Controllers\MerchantController::class, 'audit']);
    Route::post('/{id}/resubmit', [App\Modules\Merchant\Controllers\MerchantController::class, 'resubmit']);
    Route::post('/{id}/blacklist', [App\Modules\Merchant\Controllers\MerchantController::class, 'blacklist']);
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
    Route::post('/{id}/receive', [App\Modules\AfterSale\Controllers\AfterSaleController::class, 'receive']);

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

    // 装修页面
    Route::get('/pages', [App\Modules\Decorate\Controllers\PageController::class, 'index']);
    Route::get('/pages/{id}', [App\Modules\Decorate\Controllers\PageController::class, 'show']);
    Route::post('/pages', [App\Modules\Decorate\Controllers\PageController::class, 'store']);
    Route::put('/pages/{id}', [App\Modules\Decorate\Controllers\PageController::class, 'update']);
    Route::delete('/pages/{id}', [App\Modules\Decorate\Controllers\PageController::class, 'destroy']);
    Route::post('/pages/{id}/components', [App\Modules\Decorate\Controllers\PageController::class, 'saveComponents']);
    Route::post('/pages/{id}/apply-template', [App\Modules\Decorate\Controllers\PageController::class, 'applyTemplate']);

    // 装修模板
    Route::get('/templates', [App\Modules\Decorate\Controllers\TemplateController::class, 'index']);
    Route::get('/templates/{id}', [App\Modules\Decorate\Controllers\TemplateController::class, 'show']);
    Route::post('/templates', [App\Modules\Decorate\Controllers\TemplateController::class, 'store']);
    Route::put('/templates/{id}', [App\Modules\Decorate\Controllers\TemplateController::class, 'update']);
    Route::delete('/templates/{id}', [App\Modules\Decorate\Controllers\TemplateController::class, 'destroy']);

    // 轮播图
    Route::get('/banners', [App\Modules\Decorate\Controllers\BannerController::class, 'index']);
    Route::post('/banners', [App\Modules\Decorate\Controllers\BannerController::class, 'store']);
    Route::put('/banners/{id}', [App\Modules\Decorate\Controllers\BannerController::class, 'update']);
    Route::delete('/banners/{id}', [App\Modules\Decorate\Controllers\BannerController::class, 'destroy']);

    // 导航
    Route::get('/navigations', [App\Modules\Decorate\Controllers\NavigationController::class, 'index']);
    Route::post('/navigations', [App\Modules\Decorate\Controllers\NavigationController::class, 'store']);
    Route::put('/navigations/{id}', [App\Modules\Decorate\Controllers\NavigationController::class, 'update']);
    Route::delete('/navigations/{id}', [App\Modules\Decorate\Controllers\NavigationController::class, 'destroy']);

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

    Route::get('/tree', [App\Modules\ShopCenter\Controllers\ShopMenuController::class, 'tree']);
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



// 渠道配置

Route::prefix('admin/channel')->middleware('auth:sanctum')->group(function () {

    Route::get('/{channel}/config', [App\Modules\Channel\Controllers\ChannelSettingController::class, 'getConfig']);

    Route::post('/{channel}/config', [App\Modules\Channel\Controllers\ChannelSettingController::class, 'setConfig']);

    Route::get('/oa/menu', [App\Modules\Channel\Controllers\OfficialAccountMenuController::class, 'detail']);

    Route::post('/oa/menu', [App\Modules\Channel\Controllers\OfficialAccountMenuController::class, 'save']);

    Route::post('/oa/menu/publish', [App\Modules\Channel\Controllers\OfficialAccountMenuController::class, 'saveAndPublish']);

    Route::get('/oa/reply', [App\Modules\Channel\Controllers\OfficialAccountReplyController::class, 'lists']);

    Route::post('/oa/reply', [App\Modules\Channel\Controllers\OfficialAccountReplyController::class, 'add']);

    Route::put('/oa/reply/{id}', [App\Modules\Channel\Controllers\OfficialAccountReplyController::class, 'edit']);

    Route::delete('/oa/reply/{id}', [App\Modules\Channel\Controllers\OfficialAccountReplyController::class, 'delete']);

});



// 系统信息

Route::prefix('admin/system-info')->middleware('auth:sanctum')->group(function () {

    Route::get('/', [App\Modules\SystemConfig\Controllers\SystemInfoController::class, 'info']);

});



// 系统升级

Route::prefix('admin/upgrade')->middleware('auth:sanctum')->group(function () {

    Route::get('/', [App\Modules\SystemConfig\Controllers\UpgradeController::class, 'lists']);

    Route::post('/download', [App\Modules\SystemConfig\Controllers\UpgradeController::class, 'downloadPkg']);

    Route::post('/upgrade', [App\Modules\SystemConfig\Controllers\UpgradeController::class, 'upgrade']);

});



// 代码生成器

Route::prefix('admin/generator')->middleware('auth:sanctum')->group(function () {

    Route::get('/tables', [App\Modules\Tools\Controllers\GeneratorController::class, 'getModels']);

    Route::get('/table', [App\Modules\Tools\Controllers\GeneratorController::class, 'selectTable']);

    Route::get('/data', [App\Modules\Tools\Controllers\GeneratorController::class, 'dataTable']);

    Route::post('/generate', [App\Modules\Tools\Controllers\GeneratorController::class, 'generate']);

    Route::get('/preview', [App\Modules\Tools\Controllers\GeneratorController::class, 'preview']);

    Route::get('/download', [App\Modules\Tools\Controllers\GeneratorController::class, 'download']);

});



// 数据导出

Route::prefix('admin/export')->middleware('auth:sanctum')->group(function () {

    Route::post('/', [App\Modules\Tools\Controllers\DownloadController::class, 'export']);

});



// 商品SKU

Route::prefix('admin/goods-sku')->middleware('auth:sanctum')->group(function () {

    Route::get('/', [App\Modules\Product\Controllers\GoodsSkuController::class, 'index']);

    Route::post('/', [App\Modules\Product\Controllers\GoodsSkuController::class, 'store']);

    Route::put('/{id}', [App\Modules\Product\Controllers\GoodsSkuController::class, 'update']);

    Route::delete('/{id}', [App\Modules\Product\Controllers\GoodsSkuController::class, 'destroy']);

});



// 订单日志

Route::prefix('admin/order-log')->middleware('auth:sanctum')->group(function () {

    Route::get('/', [App\Modules\Order\Controllers\OrderLogController::class, 'index']);

});



// 用户认证

Route::prefix('admin/user-auth')->middleware('auth:sanctum')->group(function () {

    Route::get('/', [App\Modules\User\Controllers\UserAuthController::class, 'index']);

    Route::get('/{id}', [App\Modules\User\Controllers\UserAuthController::class, 'show']);

    Route::post('/{id}/audit', [App\Modules\User\Controllers\UserAuthController::class, 'audit']);

});



// 商家端权限

Route::prefix('admin/shop-permission')->middleware('auth:sanctum')->group(function () {
    Route::get('/admins', [App\Modules\ShopCenter\Controllers\ShopPermissionController::class, 'adminList']);
    Route::post('/admins', [App\Modules\ShopCenter\Controllers\ShopPermissionController::class, 'adminStore']);
    Route::put('/admins/{id}', [App\Modules\ShopCenter\Controllers\ShopPermissionController::class, 'adminUpdate']);
    Route::delete('/admins/{id}', [App\Modules\ShopCenter\Controllers\ShopPermissionController::class, 'adminDestroy']);
    Route::get('/roles', [App\Modules\ShopCenter\Controllers\ShopPermissionController::class, 'roleList']);
    Route::post('/roles', [App\Modules\ShopCenter\Controllers\ShopPermissionController::class, 'roleStore']);
    Route::put('/roles/{id}', [App\Modules\ShopCenter\Controllers\ShopPermissionController::class, 'roleUpdate']);
    Route::delete('/roles/{id}', [App\Modules\ShopCenter\Controllers\ShopPermissionController::class, 'roleDestroy']);
    Route::get('/depts', [App\Modules\ShopCenter\Controllers\ShopPermissionController::class, 'deptList']);
    Route::post('/depts', [App\Modules\ShopCenter\Controllers\ShopPermissionController::class, 'deptStore']);
    Route::put('/depts/{id}', [App\Modules\ShopCenter\Controllers\ShopPermissionController::class, 'deptUpdate']);
    Route::delete('/depts/{id}', [App\Modules\ShopCenter\Controllers\ShopPermissionController::class, 'deptDestroy']);
    Route::get('/jobs', [App\Modules\ShopCenter\Controllers\ShopPermissionController::class, 'jobList']);
    Route::post('/jobs', [App\Modules\ShopCenter\Controllers\ShopPermissionController::class, 'jobStore']);
    Route::put('/jobs/{id}', [App\Modules\ShopCenter\Controllers\ShopPermissionController::class, 'jobUpdate']);
    Route::delete('/jobs/{id}', [App\Modules\ShopCenter\Controllers\ShopPermissionController::class, 'jobDestroy']);
});



// 管理员部门

Route::prefix('admin/admin-dept')->middleware('auth:sanctum')->group(function () {

    Route::get('/', function() {

        $list = \Illuminate\Support\Facades\DB::table('admin_depts')->orderBy('sort','asc')->get();

        return response()->json(['code'=>200,'message'=>'success','data'=>['list'=>$list,'total'=>count($list)]]);

    });

});



// 用户端 - 地址管理

Route::middleware('auth:sanctum')->group(function() {

    Route::get('/user/addresses', [\App\Modules\UserCenter\Controllers\AddressController::class,'lists']);

    Route::post('/user/addresses', [\App\Modules\UserCenter\Controllers\AddressController::class,'add']);

    Route::put('/user/addresses/{id}', [\App\Modules\UserCenter\Controllers\AddressController::class,'edit']);

    Route::delete('/user/addresses/{id}', [\App\Modules\UserCenter\Controllers\AddressController::class,'delete']);

    Route::get('/user/addresses/{id}', [\App\Modules\UserCenter\Controllers\AddressController::class,'detail']);

    // 收藏

    Route::get('/user/collects', [\App\Modules\UserCenter\Controllers\CollectController::class,'lists']);

    Route::post('/user/collects', [\App\Modules\UserCenter\Controllers\CollectController::class,'add']);

    Route::post('/user/collects/cancel', [\App\Modules\UserCenter\Controllers\CollectController::class,'cancel']);

    Route::delete('/user/collects/{id}', [\App\Modules\UserCenter\Controllers\CollectController::class,'delete']);

    // 优惠券

    Route::get('/user/coupons', [\App\Modules\UserCenter\Controllers\UserCouponController::class,'lists']);

    Route::post('/user/coupons/receive', [\App\Modules\UserCenter\Controllers\UserCouponController::class,'receive']);

    // 会员等级
    Route::get('/user/levels', [\App\Modules\UserCenter\Controllers\LevelController::class,'index']);
    Route::get('/user/level-progress', [\App\Modules\UserCenter\Controllers\LevelController::class,'progress']);

    // 用户通知
    Route::get('/user/notifications', [\App\Modules\UserCenter\Controllers\LevelController::class,'notifications']);
    Route::put('/user/notifications/{id}/read', [\App\Modules\UserCenter\Controllers\LevelController::class,'readNotification']);
    Route::post('/user/notifications/read-all', [\App\Modules\UserCenter\Controllers\LevelController::class,'readAllNotifications']);

    // 用户中心

    Route::get('/user/center', [\App\Modules\UserCenter\Controllers\UserCenterController::class,'center']);

    Route::get('/user/info', [\App\Modules\UserCenter\Controllers\UserCenterController::class,'info']);

    Route::put('/user/info', [\App\Modules\UserCenter\Controllers\UserCenterController::class,'updateInfo']);

    // 售后

    Route::get('/user/after-sale', [\App\Modules\AfterSale\Controllers\UserAfterSaleController::class,'lists']);

    Route::post('/user/after-sale', [\App\Modules\AfterSale\Controllers\UserAfterSaleController::class,'add']);

    Route::get('/user/after-sale/{id}', [\App\Modules\AfterSale\Controllers\UserAfterSaleController::class,'detail']);

    Route::post('/user/after-sale/{id}/cancel', [\App\Modules\AfterSale\Controllers\UserAfterSaleController::class,'cancel']);
    Route::post('/user/after-sale/{id}/return-ship', [App\Modules\AfterSale\Controllers\UserAfterSaleController::class,'returnShip']);
    Route::get('/user/after-sale/reasons/list', [App\Modules\AfterSale\Controllers\UserAfterSaleController::class,'reasons']);

});



// 商家端API

Route::prefix('merchant')->group(function() {

    Route::post('/login', [\App\Modules\Merchant\Controllers\MerchantAuthController::class,'login']);

    Route::middleware('auth:sanctum')->group(function() {

        Route::post('/logout', [\App\Modules\Merchant\Controllers\MerchantAuthController::class,'logout']);

        Route::get('/info', [\App\Modules\Merchant\Controllers\MerchantAuthController::class,'info']);

        Route::get('/workbench', [\App\Modules\Merchant\Controllers\MerchantWorkbenchController::class,'index']);

        // 财务管理
        Route::get('/finance', [\App\Modules\Merchant\Controllers\MerchantFinanceController::class,'index']);
        Route::post('/finance/withdraw', [\App\Modules\Merchant\Controllers\MerchantFinanceController::class,'withdraw']);

        // 优惠券
        Route::get('/coupons', [\App\Modules\Merchant\Controllers\MerchantCouponController::class,'index']);
        Route::post('/coupons', [\App\Modules\Merchant\Controllers\MerchantCouponController::class,'store']);

        // 商品管理

        Route::get('/goods', [\App\Modules\Merchant\Controllers\MerchantGoodsController::class,'lists']);

        Route::post('/goods', [\App\Modules\Merchant\Controllers\MerchantGoodsController::class,'add']);

        Route::put('/goods/{id}', [\App\Modules\Merchant\Controllers\MerchantGoodsController::class,'edit']);

        Route::delete('/goods/{id}', [\App\Modules\Merchant\Controllers\MerchantGoodsController::class,'delete']);
        Route::post('/goods/batch-status', [\App\Modules\Merchant\Controllers\MerchantGoodsController::class,'batchUpdateStatus']);
        Route::post('/goods/batch-delete', [\App\Modules\Merchant\Controllers\MerchantGoodsController::class,'batchDelete']);

        Route::get('/goods/{id}', [\App\Modules\Merchant\Controllers\MerchantGoodsController::class,'detail']);

        // 订单管理

        Route::get('/orders', [\App\Modules\Merchant\Controllers\MerchantOrderController::class,'lists']);

        Route::get('/orders/{id}', [\App\Modules\Merchant\Controllers\MerchantOrderController::class,'detail']);

        Route::post('/orders/{id}/ship', [\App\Modules\Merchant\Controllers\MerchantOrderController::class,'ship']);

        // 店铺管理

        Route::get('/shop', [\App\Modules\Merchant\Controllers\MerchantShopController::class,'detail']);

        Route::put('/shop', [\App\Modules\Merchant\Controllers\MerchantShopController::class,'edit']);

    });

});





// 文件上传

Route::post('/upload', [\App\Modules\Core\Controllers\UploadController::class, 'upload']);

Route::post('/upload/image', [\App\Modules\Core\Controllers\UploadController::class, 'uploadImage']);

Route::post('/upload/video', [\App\Modules\Core\Controllers\UploadController::class, 'uploadVideo']);


// 补充缺失的系统设置路由
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    Route::get('/pay-configs', [App\Modules\SystemConfig\Controllers\PayConfigController::class, 'index']);
    Route::post('/pay-configs', [App\Modules\SystemConfig\Controllers\PayConfigController::class, 'store']);
    Route::put('/pay-configs/{id}', [App\Modules\SystemConfig\Controllers\PayConfigController::class, 'update']);
    Route::delete('/pay-configs/{id}', [App\Modules\SystemConfig\Controllers\PayConfigController::class, 'destroy']);

    Route::get('/logistics-configs', [App\Modules\SystemConfig\Controllers\LogisticsConfigController::class, 'index']);
    Route::post('/logistics-configs', [App\Modules\SystemConfig\Controllers\LogisticsConfigController::class, 'store']);

    Route::get('/pay-scenes', [App\Modules\SystemConfig\Controllers\PaySceneController::class, 'index']);
    Route::post('/pay-scenes', [App\Modules\SystemConfig\Controllers\PaySceneController::class, 'store']);

    Route::get('/web-setting', [App\Modules\SystemConfig\Controllers\WebSettingController::class, 'index']);
    Route::post('/web-setting', [App\Modules\SystemConfig\Controllers\WebSettingController::class, 'save']);

    Route::get('/express-templates', [App\Modules\SystemConfig\Controllers\ExpressTemplateController::class, 'index']);
    Route::post('/express-templates', [App\Modules\SystemConfig\Controllers\ExpressTemplateController::class, 'store']);
    Route::put('/express-templates/{id}', [App\Modules\SystemConfig\Controllers\ExpressTemplateController::class, 'update']);
    Route::delete('/express-templates/{id}', [App\Modules\SystemConfig\Controllers\ExpressTemplateController::class, 'destroy']);

    Route::get('/organization', [App\Modules\Permission\Controllers\OrganizationController::class, 'index']);
    Route::post('/organization', [App\Modules\Permission\Controllers\OrganizationController::class, 'store']);

    Route::get('/operation-logs', [App\Modules\System\Controllers\OperationLogController::class, 'index']);
});

// 技术文档API
Route::prefix('admin/docs')->middleware('auth:sanctum')->group(function () {
    Route::get('/{module}', [App\Modules\System\Controllers\DocController::class, 'show']);
    Route::get('/{module}/all-list', [App\Modules\System\Controllers\DocController::class, 'list']);
    Route::get('/{module}/{page}', [App\Modules\System\Controllers\DocController::class, 'show']);
});

// 帮助文档路由
Route::prefix('admin/help')->middleware('auth:sanctum')->group(function () {
    Route::get('/{module}', [App\Modules\System\Controllers\HelpController::class, 'show']);
    Route::get('/{module}/all-list', [App\Modules\System\Controllers\HelpController::class, 'list']);
    Route::get('/{module}/{page}', [App\Modules\System\Controllers\HelpController::class, 'show']);
    Route::put('/{module}/{page}', [App\Modules\System\Controllers\HelpController::class, 'update']);
});
