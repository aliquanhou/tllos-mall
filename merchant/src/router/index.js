import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/login', name: 'Login', component: () => import('@/views/Login.vue'), meta: { public: true } },
  {
    path: '/',
    component: () => import('@/layouts/MerchantLayout.vue'),
    redirect: '/dashboard',
    children: [
      { path: 'dashboard', name: 'Dashboard', component: () => import('@/views/Dashboard.vue'), meta: { title: '工作台' } },
      { path: 'product/list', name: 'ProductList', component: () => import('@/views/GoodsList.vue'), meta: { title: '商品管理' } },
      { path: 'order/list', name: 'OrderList', component: () => import('@/views/OrderList.vue'), meta: { title: '订单管理' } },
      { path: 'shop/info', name: 'ShopInfo', component: () => import('@/views/ShopSetting.vue'), meta: { title: '店铺设置' } },
      { path: 'finance/list', name: 'FinanceList', component: () => import('@/views/finance/FinanceList.vue'), meta: { title: '财务管理' } },
      { path: 'marketing/coupon', name: 'MarketingCoupon', component: () => import('@/views/marketing/Coupon.vue'), meta: { title: '优惠券' } },
    ]
  },
]

const router = createRouter({
  history: createWebHistory('/merchant/'),
  routes
})

router.beforeEach((to, from, next) => {
  if (to.meta.public) return next()
  if (!localStorage.getItem('tllos_merchant_token')) return next('/login')
  next()
})

export default router
