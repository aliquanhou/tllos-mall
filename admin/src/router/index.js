import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/login', name: 'Login', component: () => import('@/views/login/index.vue'), meta: { public: true } },
  {
    path: '/', component: () => import('@/layouts/AdminLayout.vue'), redirect: '/dashboard',
    children: [
      { path: 'dashboard', name: 'Dashboard', component: () => import('@/views/dashboard/index.vue'), meta: { title: '工作台' } },
      { path: 'product/list', name: 'ProductList', component: () => import('@/views/product/list.vue'), meta: { title: '商品列表' } },
      { path: 'product/category', name: 'ProductCategory', component: () => import('@/views/product/category.vue'), meta: { title: '商品分类' } },
      { path: 'product/comment', name: 'ProductComment', component: () => import('@/views/product/comment.vue'), meta: { title: '商品评价' } },
      { path: 'order/list', name: 'OrderList', component: () => import('@/views/order/list.vue'), meta: { title: '订单列表' } },
      { path: 'merchant/list', name: 'MerchantList', component: () => import('@/views/merchant/list.vue'), meta: { title: '商家列表' } },
      { path: 'merchant/audit', name: 'MerchantAudit', component: () => import('@/views/merchant/audit.vue'), meta: { title: '入驻审核' } },
      { path: 'user/list', name: 'UserList', component: () => import('@/views/user/list.vue'), meta: { title: '用户列表' } },
      { path: 'distribute/overview', name: 'DistributeOverview', component: () => import('@/views/distribute/overview.vue'), meta: { title: '分销概览' } },
      { path: 'distribute/goods', name: 'DistributeGoods', component: () => import('@/views/distribute/goods.vue'), meta: { title: '分销商品' } },
      { path: 'distribute/order', name: 'DistributeOrder', component: () => import('@/views/distribute/order.vue'), meta: { title: '分销订单' } },
      { path: 'distribute/level', name: 'DistributeLevel', component: () => import('@/views/distribute/level.vue'), meta: { title: '分销等级' } },
      { path: 'distribute/agent', name: 'DistributeAgent', component: () => import('@/views/distribute/agent.vue'), meta: { title: '分销商' } },
      { path: 'distribute/setting', name: 'DistributeSetting', component: () => import('@/views/distribute/setting.vue'), meta: { title: '分销设置' } },
      { path: 'marketing/coupon', name: 'MarketingCoupon', component: () => import('@/views/marketing/coupon.vue'), meta: { title: '优惠券' } },
      { path: 'marketing/member-discount', name: 'MemberDiscount', component: () => import('@/views/marketing/member-discount.vue'), meta: { title: '会员折扣' } },
      { path: 'marketing/seckill', name: 'MarketingSeckill', component: () => import('@/views/marketing/seckill.vue'), meta: { title: '限时秒杀' } },
      { path: 'marketing/group', name: 'MarketingGroup', component: () => import('@/views/marketing/group.vue'), meta: { title: '拼团活动' } },
      { path: 'application/deposit', name: 'AppDeposit', component: () => import('@/views/application/deposit.vue'), meta: { title: '充值管理' } },
      { path: 'application/material', name: 'AppMaterial', component: () => import('@/views/application/material.vue'), meta: { title: '素材管理' } },
      { path: 'application/article', name: 'AppArticle', component: () => import('@/views/application/article.vue'), meta: { title: '文章资讯' } },
      { path: 'application/notice', name: 'AppNotice', component: () => import('@/views/application/notice.vue'), meta: { title: '消息管理' } },
      { path: 'application/collect', name: 'AppCollect', component: () => import('@/views/application/collect.vue'), meta: { title: '商品采集' } },
      { path: 'application/kefu', name: 'AppKefu', component: () => import('@/views/application/kefu.vue'), meta: { title: '客服设置' } },
      { path: 'decoration/index', name: 'Decoration', component: () => import('@/views/decoration/index.vue'), meta: { title: '页面装修' } },
      { path: 'finance/income', name: 'FinanceIncome', component: () => import('@/views/finance/income.vue'), meta: { title: '订单收款' } },
      { path: 'finance/refund', name: 'FinanceRefund', component: () => import('@/views/finance/refund.vue'), meta: { title: '退款记录' } },
      { path: 'finance/withdraw', name: 'FinanceWithdraw', component: () => import('@/views/finance/withdraw.vue'), meta: { title: '提现管理' } },
      { path: 'finance/settlement', name: 'FinanceSettlement', component: () => import('@/views/finance/settlement.vue'), meta: { title: '商家结算' } },
      { path: 'channel/index', name: 'Channel', component: () => import('@/views/channel/index.vue'), meta: { title: '渠道设置' } },
      { path: 'org/index', name: 'Org', component: () => import('@/views/org/index.vue'), meta: { title: '组织管理' } },
      { path: 'permission/role', name: 'PermissionRole', component: () => import('@/views/permission/role.vue'), meta: { title: '角色管理' } },
      { path: 'permission/menu', name: 'PermissionMenu', component: () => import('@/views/permission/menu.vue'), meta: { title: '菜单管理' } },
      { path: 'system/config', name: 'SystemConfig', component: () => import('@/views/system/config.vue'), meta: { title: '基础配置' } },
      { path: 'system/payment', name: 'SystemPayment', component: () => import('@/views/system/payment.vue'), meta: { title: '支付配置' } },
      { path: 'system/express', name: 'SystemExpress', component: () => import('@/views/system/express.vue'), meta: { title: '物流配置' } },
      { path: 'system/log', name: 'SystemLog', component: () => import('@/views/system/log.vue'), meta: { title: '操作日志' } },
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
