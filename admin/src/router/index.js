import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/login', name: 'Login', component: () => import('@/views/login/index.vue'), meta: { public: true } },
  {
    path: '/', component: () => import('@/layouts/AdminLayout.vue'), redirect: '/dashboard',
    children: [
      // 工作台
      { path: 'dashboard', name: 'Dashboard', component: () => import('@/views/dashboard/index.vue'), meta: { title: '工作台' } },
      // 商品管理
      { path: 'product/list', name: 'ProductList', component: () => import('@/views/product/list.vue'), meta: { title: '商品列表' } },
      { path: 'product/category', name: 'ProductCategory', component: () => import('@/views/product/category.vue'), meta: { title: '商品分类' } },
      { path: 'product/comment', name: 'ProductComment', component: () => import('@/views/product/comment.vue'), meta: { title: '商品评价' } },
      { path: 'product/sku', name: 'ProductSku', component: () => import('@/views/product/sku.vue'), meta: { title: '商品SKU' } },
      { path: 'product/brand', name: 'ProductBrand', component: () => import('@/views/product/brand.vue'), meta: { title: '品牌管理' } },
      { path: 'product/type', name: 'ProductType', component: () => import('@/views/product/type.vue'), meta: { title: '商品类型' } },
      { path: 'product/stock-warning', name: 'StockWarning', component: () => import('@/views/product/stock-warning.vue'), meta: { title: '库存预警' } },
      // 订单管理
      { path: 'order/list', name: 'OrderList', component: () => import('@/views/order/list.vue'), meta: { title: '订单列表' } },
      { path: 'order/after-sale', name: 'OrderAfterSale', component: () => import('@/views/order/after-sale.vue'), meta: { title: '订单售后' } },
      { path: 'order/log', name: 'OrderLog', component: () => import('@/views/order/log.vue'), meta: { title: '订单日志' } },
      // 商家管理
      { path: 'merchant/list', name: 'MerchantList', component: () => import('@/views/merchant/list.vue'), meta: { title: '商家列表' } },
      { path: 'merchant/audit', name: 'MerchantAudit', component: () => import('@/views/merchant/audit.vue'), meta: { title: '入驻审核' } },
      { path: 'merchant/category', name: 'MerchantCategory', component: () => import('@/views/merchant/category.vue'), meta: { title: '商家分类' } },
      { path: 'merchant/account-log', name: 'MerchantAccountLog', component: () => import('@/views/merchant/account-log.vue'), meta: { title: '账户日志' } },
      { path: 'merchant/menu', name: 'MerchantMenu', component: () => import('@/views/merchant/menu.vue'), meta: { title: '商家菜单' } },
      { path: 'merchant/permission', name: 'MerchantPermission', component: () => import('@/views/merchant/permission.vue'), meta: { title: '商家权限' } },
      { path: 'merchant/level', name: 'MerchantLevel', component: () => import('@/views/merchant/level.vue'), meta: { title: '商家等级' } },
      // 用户管理
      { path: 'user/list', name: 'UserList', component: () => import('@/views/user/list.vue'), meta: { title: '用户列表' } },
      { path: 'user/level', name: 'UserLevel', component: () => import('@/views/user/level.vue'), meta: { title: '用户等级' } },
      { path: 'user/recharge', name: 'UserRecharge', component: () => import('@/views/user/recharge.vue'), meta: { title: '充值记录' } },
      { path: 'user/withdraw', name: 'UserWithdraw', component: () => import('@/views/user/withdraw.vue'), meta: { title: '提现管理' } },
      { path: 'user/address', name: 'UserAddress', component: () => import('@/views/user/address.vue'), meta: { title: '收货地址' } },
      { path: 'user/account-log', name: 'UserAccountLog', component: () => import('@/views/user/account-log.vue'), meta: { title: '账户日志' } },
      { path: 'user/auth', name: 'UserAuth', component: () => import('@/views/user/auth.vue'), meta: { title: '实名认证' } },
      { path: 'user-center/point', name: 'UserPoint', component: () => import('@/views/user-center/point.vue'), meta: { title: '用户积分' } },
      { path: 'user-center/favorite', name: 'UserFavorite', component: () => import('@/views/user-center/favorite.vue'), meta: { title: '用户收藏' } },
      // 分销管理
      { path: 'distribute/overview', name: 'DistributeOverview', component: () => import('@/views/distribute/overview.vue'), meta: { title: '分销概览' } },
      { path: 'distribute/goods', name: 'DistributeGoods', component: () => import('@/views/distribute/goods.vue'), meta: { title: '分销商品' } },
      { path: 'distribute/order', name: 'DistributeOrder', component: () => import('@/views/distribute/order.vue'), meta: { title: '分销订单' } },
      { path: 'distribute/level', name: 'DistributeLevel', component: () => import('@/views/distribute/level.vue'), meta: { title: '分销等级' } },
      { path: 'distribute/agent', name: 'DistributeAgent', component: () => import('@/views/distribute/agent.vue'), meta: { title: '分销商' } },
      { path: 'distribute/apply', name: 'DistributeApply', component: () => import('@/views/distribute/apply.vue'), meta: { title: '分销申请' } },
      { path: 'distribute/setting', name: 'DistributeSetting', component: () => import('@/views/distribute/setting.vue'), meta: { title: '分销设置' } },
      // 营销管理
      { path: 'marketing/coupon', name: 'MarketingCoupon', component: () => import('@/views/marketing/coupon.vue'), meta: { title: '优惠券' } },
      { path: 'marketing/member-discount', name: 'MemberDiscount', component: () => import('@/views/marketing/member-discount.vue'), meta: { title: '会员折扣' } },
      { path: 'marketing/seckill', name: 'MarketingSeckill', component: () => import('@/views/marketing/seckill.vue'), meta: { title: '限时秒杀' } },
      { path: 'marketing/group', name: 'MarketingGroup', component: () => import('@/views/marketing/group.vue'), meta: { title: '拼团活动' } },
      { path: 'marketing/pt-open', name: 'MarketingPtOpen', component: () => import('@/views/marketing/pt-open.vue'), meta: { title: '拼团开团' } },
      // 应用管理
      { path: 'application/deposit', name: 'AppDeposit', component: () => import('@/views/application/deposit.vue'), meta: { title: '充值管理' } },
      { path: 'application/material', name: 'AppMaterial', component: () => import('@/views/application/material.vue'), meta: { title: '素材管理' } },
      { path: 'application/article', name: 'AppArticle', component: () => import('@/views/application/article.vue'), meta: { title: '文章资讯' } },
      { path: 'application/notice', name: 'AppNotice', component: () => import('@/views/application/notice.vue'), meta: { title: '消息管理' } },
      { path: 'application/announcement', name: 'AppAnnouncement', component: () => import('@/views/application/announcement.vue'), meta: { title: '商城公告' } },
      { path: 'application/collect', name: 'AppCollect', component: () => import('@/views/application/collect.vue'), meta: { title: '商品采集' } },
      { path: 'application/kefu', name: 'AppKefu', component: () => import('@/views/application/kefu.vue'), meta: { title: '客服设置' } },
      // 装修管理
      { path: 'decoration/index', name: 'Decoration', component: () => import('@/views/decoration/index.vue'), meta: { title: '页面装修' } },
      { path: 'decoration/editor/:id', name: 'DecorationEditor', component: () => import('@/views/decoration/editor.vue'), meta: { title: '可视化装修' } },
      { path: 'decoration/template', name: 'DecorationTemplate', component: () => import('@/views/decoration/template.vue'), meta: { title: '模板管理' } },
      { path: 'decoration/banner', name: 'DecorationBanner', component: () => import('@/views/decoration/banner.vue'), meta: { title: '轮播图管理' } },
      { path: 'decoration/navigation', name: 'DecorationNavigation', component: () => import('@/views/decoration/navigation.vue'), meta: { title: '导航管理' } },
      { path: 'decoration/page', name: 'DecorationPage', component: () => import('@/views/decoration/page.vue'), meta: { title: '装修页面' } },
      { path: 'decoration/tabbar', name: 'DecorationTabbar', component: () => import('@/views/decoration/tabbar.vue'), meta: { title: '底部导航' } },
      // 财务管理
      { path: 'finance/income', name: 'FinanceIncome', component: () => import('@/views/finance/income.vue'), meta: { title: '订单收款' } },
      { path: 'finance/refund', name: 'FinanceRefund', component: () => import('@/views/finance/refund.vue'), meta: { title: '退款记录' } },
      { path: 'finance/withdraw', name: 'FinanceWithdraw', component: () => import('@/views/finance/withdraw.vue'), meta: { title: '提现管理' } },
      { path: 'finance/settlement', name: 'FinanceSettlement', component: () => import('@/views/finance/settlement.vue'), meta: { title: '商家结算' } },
      { path: 'finance/settlement-record', name: 'FinanceSettlementRecord', component: () => import('@/views/finance/settlement-record.vue'), meta: { title: '结算记录' } },
      // 渠道设置
      { path: 'channel/index', name: 'ChannelIndex', component: () => import('@/views/channel/index.vue'), meta: { title: '渠道列表' } },
      { path: 'channel/setting', name: 'ChannelSetting', component: () => import('@/views/channel/setting.vue'), meta: { title: '渠道配置' } },
      { path: 'channel/oa-menu', name: 'ChannelOaMenu', component: () => import('@/views/channel/oa-menu.vue'), meta: { title: '公众号菜单' } },
      { path: 'channel/oa-reply', name: 'ChannelOaReply', component: () => import('@/views/channel/oa-reply.vue'), meta: { title: '公众号回复' } },
      // 组织管理
      { path: 'org/index', name: 'Org', component: () => import('@/views/org/index.vue'), meta: { title: '组织架构' } },
      // 权限管理
      { path: 'permission/role', name: 'PermissionRole', component: () => import('@/views/permission/role.vue'), meta: { title: '角色管理' } },
      { path: 'permission/menu', name: 'PermissionMenu', component: () => import('@/views/permission/menu.vue'), meta: { title: '菜单管理' } },
      { path: 'permission/admin', name: 'PermissionAdmin', component: () => import('@/views/permission/admin.vue'), meta: { title: '管理员' } },
      { path: 'permission/job', name: 'PermissionJob', component: () => import('@/views/permission/job.vue'), meta: { title: '岗位管理' } },
      { path: 'permission/dept', name: 'PermissionDept', component: () => import('@/views/permission/dept.vue'), meta: { title: '部门管理' } },
      // 系统设置
      { path: 'system/config', name: 'SystemConfig', component: () => import('@/views/system/config.vue'), meta: { title: '基础配置' } },
      { path: 'system/payment', name: 'SystemPayment', component: () => import('@/views/system/payment.vue'), meta: { title: '支付配置' } },
      { path: 'system/express', name: 'SystemExpress', component: () => import('@/views/system/express.vue'), meta: { title: '物流配置' } },
      { path: 'system/delivery-type', name: 'SystemDeliveryType', component: () => import('@/views/system/delivery-type.vue'), meta: { title: '配送方式' } },
      { path: 'system/order-setting', name: 'SystemOrderSetting', component: () => import('@/views/system/order-setting.vue'), meta: { title: '订单设置' } },
      { path: 'system/transaction-setting', name: 'SystemTransactionSetting', component: () => import('@/views/system/transaction-setting.vue'), meta: { title: '交易设置' } },
      { path: 'system/user-setting', name: 'SystemUserSetting', component: () => import('@/views/system/user-setting.vue'), meta: { title: '用户设置' } },
      { path: 'system/web-setting', name: 'SystemWebSetting', component: () => import('@/views/system/web-setting.vue'), meta: { title: '网站设置' } },
      { path: 'system/notice-setting', name: 'SystemNoticeSetting', component: () => import('@/views/system/notice-setting.vue'), meta: { title: '通知设置' } },
      { path: 'system/sms-config', name: 'SystemSmsConfig', component: () => import('@/views/system/sms-config.vue'), meta: { title: '短信配置' } },
      { path: 'system/storage', name: 'SystemStorage', component: () => import('@/views/system/storage.vue'), meta: { title: '存储设置' } },
      { path: 'system/storage-config', name: 'SystemStorageConfig', component: () => import('@/views/system/storage-config.vue'), meta: { title: '存储配置' } },
      { path: 'system/pay-scene', name: 'SystemPayScene', component: () => import('@/views/system/pay-scene.vue'), meta: { title: '支付场景' } },
      { path: 'system/dict', name: 'SystemDict', component: () => import('@/views/system/dict.vue'), meta: { title: '数据字典' } },
      { path: 'system/hot-search', name: 'SystemHotSearch', component: () => import('@/views/system/hot-search.vue'), meta: { title: '热门搜索' } },
      { path: 'system/crontab', name: 'SystemCrontab', component: () => import('@/views/system/crontab.vue'), meta: { title: '定时任务' } },
      { path: 'system/area', name: 'SystemArea', component: () => import('@/views/system/area.vue'), meta: { title: '地区管理' } },
      { path: 'system/express-template', name: 'SystemExpressTemplate', component: () => import('@/views/system/express-template.vue'), meta: { title: '快递模板' } },
      { path: 'system/file', name: 'SystemFile', component: () => import('@/views/system/file.vue'), meta: { title: '文件管理' } },
      { path: 'system/cache', name: 'SystemCache', component: () => import('@/views/system/cache.vue'), meta: { title: '系统缓存' } },
      { path: 'system/info', name: 'SystemInfo', component: () => import('@/views/system/info.vue'), meta: { title: '系统信息' } },
      { path: 'system/upgrade', name: 'SystemUpgrade', component: () => import('@/views/system/upgrade.vue'), meta: { title: '系统升级' } },
      { path: 'system/log', name: 'SystemLog', component: () => import('@/views/system/log.vue'), meta: { title: '操作日志' } },
      // 开发工具
      { path: 'tools/generator', name: 'ToolsGenerator', component: () => import('@/views/tools/generator.vue'), meta: { title: '代码生成器' } },
      { path: 'tools/export', name: 'ToolsExport', component: () => import('@/views/tools/export.vue'), meta: { title: '数据导出' } },
    ]
  }
]

const router = createRouter({ history: createWebHistory(import.meta.env.BASE_URL), routes })
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('tllos_admin_token')
  if (to.meta.public) return next()
  if (!token) return next('/login')
  next()
})
export default router
