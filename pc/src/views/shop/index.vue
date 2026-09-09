<template>
  <div class="shop-page">
    <!-- 店铺头部 -->
    <div class="shop-header">
      <div class="container">
        <div class="shop-info">
          <div class="shop-logo"><img :src="shop.logo" :alt="shop.name" /></div>
          <div class="shop-detail">
            <h1 class="shop-name">{{ shop.name }}</h1>
            <div class="shop-meta">
              <span><el-icon><Star /></el-icon> 评分{{ shop.rating }}</span>
              <span><el-icon><Goods /></el-icon> 商品{{ shop.product_count }}件</span>
              <span><el-icon><User /></el-icon> 粉丝{{ shop.fans }}</span>
              <span><el-icon><Clock /></el-icon> 开业{{ shop.open_years }}年</span>
            </div>
            <div class="shop-tags">
              <el-tag type="success" size="small">正品保障</el-tag>
              <el-tag type="warning" size="small">7天无理由</el-tag>
              <el-tag type="primary" size="small">极速发货</el-tag>
            </div>
          </div>
          <div class="shop-actions">
            <el-button type="primary" size="large" :icon="isFollow ? 'Check' : 'Plus'" @click="toggleFollow">
              {{ isFollow ? '已关注' : '关注店铺' }}
            </el-button>
            <el-button size="large"><el-icon><Message /></el-icon> 联系客服</el-button>
          </div>
        </div>
      </div>
    </div>
    <div class="container">
      <!-- 店铺导航 -->
      <div class="shop-nav">
        <span class="nav-item" :class="{active: activeTab === 'home'}" @click="activeTab = 'home'">店铺首页</span>
        <span class="nav-item" :class="{active: activeTab === 'products'}" @click="activeTab = 'products'">全部商品</span>
        <span class="nav-item" :class="{active: activeTab === 'new'}" @click="activeTab = 'new'">新品上架</span>
        <span class="nav-item" :class="{active: activeTab === 'promo'}" @click="activeTab = 'promo'">促销活动</span>
      </div>
      <!-- 店铺内容 -->
      <div class="shop-content">
        <!-- 店铺Banner -->
        <div class="shop-banner" v-if="activeTab === 'home'">
          <div class="banner-slide">
            <h2>店铺大促</h2>
            <p>全场满200减30，更有优惠券等你领</p>
          </div>
        </div>
        <!-- 商品分类 -->
        <div class="shop-categories" v-if="activeTab === 'home'">
          <div class="category-item" v-for="cat in shopCategories" :key="cat.id" @click="activeTab = 'products'">
            <div class="cat-icon"><img :src="cat.icon" :alt="cat.name" /></div>
            <span>{{ cat.name }}</span>
          </div>
        </div>
        <!-- 商品列表 -->
        <div class="product-grid">
          <div class="product-card" v-for="product in products" :key="product.id" @click="$router.push(`/product/${product.id}`)">
            <div class="product-image"><img :src="product.main_image" :alt="product.name" /></div>
            <div class="product-info">
              <div class="product-name">{{ product.name }}</div>
              <div class="product-price">¥{{ product.price }}</div>
              <div class="product-sales">已售{{ product.sales }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { ElMessage } from 'element-plus'
const activeTab = ref('home')
const isFollow = ref(false)
const shop = ref({
  name: '示例旗舰店', logo: '', rating: '4.9', product_count: 256, fans: '12.5万', open_years: 5
})
const shopCategories = ref([
  { id: 1, name: '热销推荐', icon: '' }, { id: 2, name: '新品上市', icon: '' },
  { id: 3, name: '限时特惠', icon: '' }, { id: 4, name: '全部商品', icon: '' },
])
const products = ref(Array.from({ length: 8 }, (_, i) => ({
  id: i + 1, name: `店铺商品${i + 1}`, main_image: '', price: (99 + i * 30).toFixed(2), sales: Math.floor(Math.random() * 1000) + 50
})))
const toggleFollow = () => { isFollow.value = !isFollow.value; ElMessage.success(isFollow.value ? '关注成功' : '已取消关注') }
</script>
<style scoped>
.shop-page { background: #f5f5f5; min-height: calc(100vh - 200px); }
.shop-header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 32px 0; color: #fff; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.shop-info { display: flex; gap: 24px; align-items: center; }
.shop-logo { width: 100px; height: 100px; border-radius: 12px; background: #fff; overflow: hidden; flex-shrink: 0; }
.shop-logo img { width: 100%; height: 100%; object-fit: cover; }
.shop-detail { flex: 1; }
.shop-name { font-size: 26px; margin: 0 0 12px 0; }
.shop-meta { display: flex; gap: 24px; font-size: 14px; opacity: 0.8; margin-bottom: 12px; }
.shop-meta span { display: flex; align-items: center; gap: 4px; }
.shop-tags { display: flex; gap: 8px; }
.shop-actions { display: flex; flex-direction: column; gap: 12px; }
.shop-nav { background: #fff; border-radius: 8px; margin: 20px 0; padding: 0 20px; display: flex; gap: 0; }
.nav-item { padding: 16px 24px; font-size: 15px; color: #666; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; }
.nav-item:hover { color: #e6a23c; }
.nav-item.active { color: #e6a23c; border-bottom-color: #e6a23c; font-weight: bold; }
.shop-content { }
.shop-banner { background: linear-gradient(135deg, #f56c6c, #e64c4c); border-radius: 12px; padding: 48px; margin-bottom: 24px; color: #fff; }
.shop-banner h2 { font-size: 32px; margin: 0 0 12px 0; }
.shop-banner p { font-size: 16px; opacity: 0.9; margin: 0; }
.shop-categories { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.category-item { background: #fff; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.2s; }
.category-item:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.cat-icon { width: 48px; height: 48px; border-radius: 50%; background: #f5f5f5; margin: 0 auto 8px; overflow: hidden; }
.cat-icon img { width: 100%; height: 100%; object-fit: cover; }
.category-item span { font-size: 14px; color: #333; }
.product-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.product-card { background: #fff; border-radius: 8px; overflow: hidden; cursor: pointer; transition: all 0.2s; }
.product-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.product-image { padding-top: 100%; background: #fafafa; position: relative; }
.product-image img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
.product-info { padding: 12px; }
.product-name { font-size: 14px; color: #333; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; margin-bottom: 8px; }
.product-price { font-size: 18px; color: #f56c6c; font-weight: bold; margin-bottom: 4px; }
.product-sales { font-size: 12px; color: #999; }
</style>
