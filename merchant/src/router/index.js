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
      { path: 'product/create', name: 'ProductCreate', component: () => import('@/views/product/GoodsForm.vue'), meta: { title: '新增商品' } },
      { path: 'product/edit/:id', name: 'ProductEdit', component: () => import('@/views/product/GoodsForm.vue'), meta: { title: '编辑商品' } },
      { path: 'product/category', name: 'ProductCategory', component: () => import('@/views/product/Category.vue'), meta: { title: '商品分类' } },
      { path: 'product/review', name: 'ProductReview', component: () => import('@/views/product/Review.vue'), meta: { title: '商品评价' } },
      { path: 'order/list', name: 'OrderList', component: () => import('@/views/OrderList.vue'), meta: { title: '订单管理' } },
      { path: 'order/detail/:id', name: 'OrderDetail', component: () => import('@/views/order/Detail.vue'), meta: { title: '订单详情' } },
      { path: 'order/after-sale', name: 'AfterSale', component: () => import('@/views/order/AfterSale.vue'), meta: { title: '售后管理' } },
      { path: 'shop/info', name: 'ShopInfo', component: () => import('@/views/ShopSetting.vue'), meta: { title: '店铺设置' } },
      { path: 'finance/list', name: 'FinanceList', component: () => import('@/views/finance/FinanceList.vue'), meta: { title: '财务管理' } },
      { path: 'finance/settlement', name: 'FinanceSettlement', component: () => import('@/views/finance/Settlement.vue'), meta: { title: '结算管理' } },
      { path: 'marketing/coupon', name: 'MarketingCoupon', component: () => import('@/views/marketing/Coupon.vue'), meta: { title: '优惠券' } },
      { path: 'marketing/seckill', name: 'MarketingSeckill', component: () => import('@/views/marketing/Seckill.vue'), meta: { title: '秒杀活动' } },
      { path: 'data/overview', name: 'DataOverview', component: () => import('@/views/data/Overview.vue'), meta: { title: '数据概览' } },
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
