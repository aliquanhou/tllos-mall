import { createRouter, createWebHistory } from 'vue-router'
const routes = [
  { path:'/login', component:()=>import('@/views/login/index.vue'), meta:{public:true} },
  { path:'/search', component:()=>import('@/views/search/index.vue'), meta:{public:true} },
  { path:'/', component:()=>import('@/layouts/PcLayout.vue'), children:[
    { path:'', redirect:'/home' },
    { path:'home', component:()=>import('@/views/home/index.vue') },
    { path:'category', component:()=>import('@/views/category/index.vue') },
    { path:'cart', component:()=>import('@/views/cart/index.vue') },
    { path:'profile', component:()=>import('@/views/user/Profile.vue') },
  ]},
  { path:'/products', component:()=>import('@/views/product/list.vue') },
  { path:'/product/:id', component:()=>import('@/views/product/detail.vue') },
  { path:'/orders', component:()=>import('@/views/order/list.vue') },
  { path:'/order/:id', component:()=>import('@/views/order/detail.vue') },
  { path:'/checkout', component:()=>import('@/views/checkout/index.vue') },
  { path:'/pay/:orderNo', component:()=>import('@/views/pay/index.vue') },
  { path:'/address', component:()=>import('@/views/user/Address.vue') },
  { path:'/address/edit', component:()=>import('@/views/user/AddressEdit.vue') },
  { path:'/collects', component:()=>import('@/views/user/Collect.vue') },
  { path:'/coupons', component:()=>import('@/views/user/Coupon.vue') },
]
const router = createRouter({ history:createWebHistory('/pc/'), routes })
router.beforeEach((to,from,next)=>{
  if(to.meta.public) return next()
  if(!localStorage.getItem('tllos_pc_token') && !['/home','/category','/products','/search'].includes(to.path) && !to.path.startsWith('/product/')) return next('/login')
  next()
})
export default router
