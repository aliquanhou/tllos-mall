import { createRouter, createWebHistory } from 'vue-router'
const routes = [
  { path:'/login', component:()=>import('@/views/Login.vue'), meta:{public:true} },
  { path:'/', component:()=>import('@/layouts/MerchantLayout.vue'), children:[
    { path:'', redirect:'/dashboard' },
    { path:'dashboard', component:()=>import('@/views/Dashboard.vue'), meta:{title:'工作台'} },
    { path:'goods', component:()=>import('@/views/GoodsList.vue'), meta:{title:'商品管理'} },
    { path:'orders', component:()=>import('@/views/OrderList.vue'), meta:{title:'订单管理'} },
    { path:'shop', component:()=>import('@/views/ShopSetting.vue'), meta:{title:'店铺设置'} },
  ]},
]
const router = createRouter({ history:createWebHistory('/merchant/'), routes })
router.beforeEach((to,from,next)=>{
  if(to.meta.public) return next()
  if(!localStorage.getItem('tllos_merchant_token')) return next('/login')
  next()
})
export default router
