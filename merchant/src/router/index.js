import { createRouter, createWebHistory } from 'vue-router'
const routes = [
  { path: '/login', name: 'Login', component: () => import('@/views/login/index.vue'), meta: { public: true } },
  {
    path: '/', component: () => import('@/layouts/MerchantLayout.vue'), redirect: '/dashboard',
    children: [
      { path: 'dashboard', name: 'Dashboard', component: () => import('@/views/dashboard/index.vue'), meta: { title: 'menu.dashboard', icon: 'Odometer' } },
      { path: 'product/list', name: 'ProductList', component: () => import('@/views/product/list.vue'), meta: { title: 'menu.productList', icon: 'Goods' } },
      { path: 'order/list', name: 'OrderList', component: () => import('@/views/order/list.vue'), meta: { title: 'menu.orderList', icon: 'List' } },
      { path: 'shop/info', name: 'ShopInfo', component: () => import('@/views/shop/info.vue'), meta: { title: 'menu.shopInfo', icon: 'Shop' } },
      { path: 'finance/list', name: 'FinanceList', component: () => import('@/views/finance/list.vue'), meta: { title: 'menu.financeList', icon: 'Money' } },
      { path: 'marketing/coupon', name: 'Coupon', component: () => import('@/views/marketing/coupon.vue'), meta: { title: 'menu.coupon', icon: 'Ticket' } }
    ]
  }
]
const router = createRouter({ history: createWebHistory(), routes })
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('tllos_merchant_token')
  if (to.meta.public) return next()
  if (!token) return next('/login')
  next()
})
export default router
