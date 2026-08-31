import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/login', name: 'Login', component: () => import('@/views/login/index.vue'), meta: { public: true } },
  { path: '/', component: () => import('@/layouts/TabBarLayout.vue'), redirect: '/home',
    children: [
      { path: 'home', name: 'Home', component: () => import('@/views/home/index.vue') },
      { path: 'category', name: 'Category', component: () => import('@/views/category/index.vue') },
      { path: 'cart', name: 'Cart', component: () => import('@/views/cart/index.vue') },
      { path: 'user', name: 'User', component: () => import('@/views/user/index.vue') }
    ]
  },
  { path: '/product/:id', name: 'ProductDetail', component: () => import('@/views/product/detail.vue') },
  { path: '/products', name: 'ProductList', component: () => import('@/views/product/list.vue') },
  { path: '/order', name: 'OrderList', component: () => import('@/views/order/list.vue') },
  { path: '/:pathMatch(.*)*', redirect: '/home' }
]

const router = createRouter({ history: createWebHistory(), routes, scrollBehavior: () => ({ top: 0 }) })

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('tllos_h5_token')
  if (to.meta.public) return next()
  if (!token && ['Cart', 'User', 'OrderList'].includes(to.name)) return next('/login')
  next()
})

export default router
