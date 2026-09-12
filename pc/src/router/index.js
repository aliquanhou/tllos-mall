import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  // 公开页面（不需要登录，也不需要底部导航）
  { path:'/login', component:()=>import('@/views/login/index.vue'), meta:{public:true, noLayout:true} },
  { path:'/register', component:()=>import('@/views/login/index.vue'), meta:{public:true, noLayout:true} },
  { path:'/search', component:()=>import('@/views/search/index.vue'), meta:{public:true} },

  // 主布局（所有页面都有底部导航）
  { path:'/', component:()=>import('@/layouts/PcLayout.vue'), children:[
    { path:'', redirect:'/home' },
    { path:'home', component:()=>import('@/views/home/index.vue') },
    { path:'category', component:()=>import('@/views/category/index.vue') },
    { path:'cart', component:()=>import('@/views/cart/index.vue') },
    { path:'profile', component:()=>import('@/views/user/Profile.vue') },
    { path:'products', component:()=>import('@/views/product/list.vue') },
    { path:'product/:id', component:()=>import('@/views/product/detail.vue') },
    { path:'orders', component:()=>import('@/views/order/list.vue') },
    { path:'order/:id', component:()=>import('@/views/order/detail.vue') },
    { path:'checkout', component:()=>import('@/views/checkout/index.vue') },
    { path:'pay/:orderNo', component:()=>import('@/views/pay/index.vue') },
    { path:'pay/result/:orderNo', component:()=>import('@/views/pay/result.vue') },
    { path:'address', component:()=>import('@/views/user/Address.vue') },
    { path:'address/edit', component:()=>import('@/views/user/AddressEdit.vue') },
    { path:'collects', component:()=>import('@/views/user/Collect.vue') },
    { path:'my-reviews', component:()=>import('@/views/user/MyReviews.vue') },
    { path:'coupons', component:()=>import('@/views/user/Coupon.vue') },
    { path:'distribution/apply', component:()=>import('@/views/user/DistributionApply.vue') },
    { path:'after-sale', component:()=>import('@/views/after-sale/index.vue') },
    { path:'review', component:()=>import('@/views/review/index.vue') },
    { path:'messages', component:()=>import('@/views/message/index.vue') },
    { path:'brand', component:()=>import('@/views/brand/index.vue') },
    { path:'flash-sale', component:()=>import('@/views/promotion/flash-sale.vue') },
    { path:'new-arrivals', component:()=>import('@/views/promotion/new-arrivals.vue') },
    { path:'shop/:id', component:()=>import('@/views/shop/index.vue') },
    { path:'help', component:()=>import('@/views/help/index.vue') },
    { path:'about', component:()=>import('@/views/about/index.vue') },
    { path:'contact', component:()=>import('@/views/about/index.vue') },
    { path:'agreement/user', component:()=>import('@/views/agreement/user.vue') },
    { path:'agreement/privacy', component:()=>import('@/views/agreement/privacy.vue') },
  ]},

  // 兼容旧路径重定向
  { path: '/product/list', redirect: '/products' },
  { path: '/order/list', redirect: '/orders' },
  { path: '/privacy', redirect: '/agreement/privacy' },
]

const router = createRouter({ history:createWebHistory('/'), routes })

router.beforeEach((to,from,next)=>{
  if(to.meta.public || to.meta.noLayout) return next()
  if(!localStorage.getItem('tllos_pc_token') && !['/home','/category','/products','/search'].includes(to.path) && !to.path.startsWith('/product/')) return next('/login')
  next()
})

const routes404 = { path: '/:pathMatch(.*)*', component: () => import('@/views/NotFound.vue') }
router.addRoute(routes404)

export default router
