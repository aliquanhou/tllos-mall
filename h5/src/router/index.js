import { createRouter, createWebHistory } from 'vue-router'
const routes = [
  { path:'/login', component:()=>import('@/views/login/index.vue'), meta:{public:true} },
  { path:'/', component:()=>import('@/layouts/TabBarLayout.vue'), children:[
    { path:'', redirect:'/home' },
    { path:'home', component:()=>import('@/views/home/index.vue') },
    { path:'category', component:()=>import('@/views/category/index.vue') },
    { path:'cart', component:()=>import('@/views/cart/index.vue') },
    { path:'profile', component:()=>import('@/views/user/Profile.vue') },
  ]},
  { path:'/products', component:()=>import('@/views/product/list.vue') },
  { path:'/product/:id', component:()=>import('@/views/product/detail.vue') },
  { path:'/orders', component:()=>import('@/views/order/list.vue') },
  { path:'/address', component:()=>import('@/views/user/Address.vue') },
  { path:'/collects', component:()=>import('@/views/user/Collect.vue') },
  { path:'/coupons', component:()=>import('@/views/user/Coupon.vue') },
]
const router = createRouter({ history:createWebHistory('/h5/'), routes })
router.beforeEach((to,from,next)=>{
  if(to.meta.public) return next()
  if(!localStorage.getItem('tllos_h5_token') && !['/home','/category','/products'].includes(to.path) && !to.path.startsWith('/product/')) return next('/login')
  next()
})
export default router
