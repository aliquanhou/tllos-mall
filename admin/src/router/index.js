import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/login', name: 'Login', component: () => import('@/views/login/index.vue'), meta: { public: true } },
  {
    path: '/',
    component: () => import('@/layouts/AdminLayout.vue'),
    redirect: '/dashboard',
    children: [
      { path: 'dashboard', name: 'Dashboard', component: () => import('@/views/dashboard/index.vue'), meta: { title: 'menu.dashboard', icon: 'Odometer' } },
      { path: 'user/list', name: 'UserList', component: () => import('@/views/user/list.vue'), meta: { title: 'menu.userList', icon: 'User' } },
      { path: 'product/list', name: 'ProductList', component: () => import('@/views/product/list.vue'), meta: { title: 'menu.productList', icon: 'Goods' } },
      { path: 'order/list', name: 'OrderList', component: () => import('@/views/order/list.vue'), meta: { title: 'menu.orderList', icon: 'List' } },
      { path: 'merchant/list', name: 'MerchantList', component: () => import('@/views/merchant/list.vue'), meta: { title: 'menu.merchantList', icon: 'Shop' } },
      { path: 'system/config', name: 'SystemConfig', component: () => import('@/views/system/config.vue'), meta: { title: 'menu.config', icon: 'Setting' } }
    ]
  }
]

const router = createRouter({ history: createWebHistory(import.meta.env.BASE_URL), routes })

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('tllos_admin_token')
  if (to.meta.public) { next(); return }
  if (!token) { next('/login'); return }
  next()
})

export default router
