<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    // 路径前缀到权限标识的映射
    private $pathPermissionMap = [
        'dashboard' => 'dashboard.view',
        'products' => 'product.manage',
        'categories' => 'product.manage',
        'brands' => 'product.manage',
        'product-types' => 'product.manage',
        'goods-sku' => 'product.manage',
        'stock-warnings' => 'product.manage',
        'orders' => 'order.manage',
        'refunds' => 'order.manage',
        'order-log' => 'order.manage',
        'order-setting' => 'order.manage',
        'after-sale' => 'after_sales.manage',
        'decorate' => 'decorate.manage',
        'finance' => 'finance.manage',
        'settlement-record' => 'finance.manage',
        'pay-configs' => 'finance.manage',
        'pay-scene' => 'finance.manage',
        'transaction-setting' => 'finance.manage',
        'users' => 'user.manage',
        'user-points' => 'user.manage',
        'user-favorites' => 'user.manage',
        'user-auth' => 'user.manage',
        'user-center' => 'user.manage',
        'user-setting' => 'user.manage',
        'roles' => 'permission.manage',
        'menus' => 'permission.manage',
        'admin-manage' => 'permission.manage',
        'admin-dept' => 'permission.manage',
        'permission' => 'permission.manage',
        'settings' => 'system.manage',
        'system' => 'system.manage',
        'system-config' => 'system.manage',
        'system-info' => 'system.manage',
        'crontabs' => 'system.manage',
        'storage-configs' => 'system.manage',
        'sms-configs' => 'system.manage',
        'sms-config' => 'system.manage',
        'file-managers' => 'system.manage',
        'operation-logs' => 'system.manage',
        'cache' => 'system.manage',
        'dicts' => 'system.manage',
        'storage' => 'system.manage',
        'distribute' => 'distribute.manage',
        'coupons' => 'marketing.manage',
        'seckills' => 'marketing.manage',
        'bargains' => 'marketing.manage',
        'marketing' => 'marketing.manage',
        'hot-searches' => 'marketing.manage',
        'agreements' => 'application.manage',
        'announcements' => 'application.manage',
        'announcement' => 'application.manage',
        'application' => 'application.manage',
        'gathers' => 'application.manage',
        'articles' => 'application.manage',
        'help' => 'application.manage',
        'docs' => 'application.manage',
        'merchants' => 'merchant.manage',
        'merchant-levels' => 'merchant.manage',
        'merchant-categories' => 'merchant.manage',
        'merchant-account-logs' => 'merchant.manage',
        'shop-center' => 'merchant.manage',
        'express-templates' => 'logistics.manage',
        'delivery-type' => 'logistics.manage',
        'logistics-configs' => 'logistics.manage',
        'areas' => 'logistics.manage',
        'comments' => 'content.manage',
        'sensitive-words' => 'content.manage',
        'notices' => 'content.manage',
        'notice-setting' => 'content.manage',
        'invoices' => 'invoice.manage',
        'channel' => 'channel.manage',
        'organization' => 'organization.manage',
        'generator' => 'developer.manage',
        'upgrade' => 'developer.manage',
        'export' => 'developer.manage',
        'jobs' => 'developer.manage',
        'groups' => 'developer.manage',
        'pt-open' => 'developer.manage',
        'web-setting' => 'system.manage',
    ];

    public function handle(Request $request, Closure $next, $permission = null)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['code' => 401, 'message' => '未登录或登录已过期'], 401);
        }

        // 超级管理员（role_id=1）跳过权限检查
        if ($user->role_id == 1) {
            return $next($request);
        }

        // 如果指定了具体权限，使用指定的；否则根据路径自动映射
        if ($permission && $permission !== 'auto') {
            $requiredPermission = $permission;
        } else {
            // 根据请求路径自动映射权限
            $path = $request->path(); // 如 api/v1/admin/products
            $segments = explode('/', $path);

            // 找到admin后面的第一个路径段
            $adminIndex = array_search('admin', $segments);
            if ($adminIndex !== false && isset($segments[$adminIndex + 1])) {
                $module = $segments[$adminIndex + 1];
                $requiredPermission = $this->pathPermissionMap[$module] ?? null;
            } else {
                $requiredPermission = null;
            }

            // profile和logout是所有登录用户都能访问的
            if (in_array($module ?? '', ['profile', 'logout'])) {
                return $next($request);
            }
        }

        // 如果没有映射到权限，默认拒绝（安全优先）
        if (!$requiredPermission) {
            return response()->json([
                'code' => 403,
                'message' => '无权限访问该资源（未定义权限规则）'
            ], 403);
        }

        // 获取角色的权限列表
        $role = \DB::table('admin_roles')->where('id', $user->role_id)->first();

        if (!$role) {
            return response()->json(['code' => 403, 'message' => '角色不存在，无权限访问'], 403);
        }

        $permissions = json_decode($role->permissions, true);

        if (!is_array($permissions)) {
            $permissions = [];
        }

        // 检查是否有权限
        if (!in_array($requiredPermission, $permissions)) {
            return response()->json([
                'code' => 403,
                'message' => '无权限访问该资源（需要权限：' . $requiredPermission . '）'
            ], 403);
        }

        return $next($request);
    }
}
