<template>
  <div class="collect-page">
    <div class="container">
      <div class="page-header">
        <h2>我的收藏</h2>
        <div class="header-actions">
          <el-button v-if="!editMode" @click="editMode = true">批量管理</el-button>
          <template v-else>
            <el-checkbox v-model="selectAll" @change="toggleSelectAll">全选</el-checkbox>
            <el-button type="danger" :disabled="selectedIds.length === 0" @click="batchCancel">取消收藏</el-button>
            <el-button @click="editMode = false">完成</el-button>
          </template>
        </div>
      </div>
      <div class="collect-grid" v-if="list.length">
        <div class="collect-card" v-for="item in list" :key="item.id" :class="{selected: selectedIds.includes(item.id)}" @click="goDetail(item)">
          <el-checkbox v-if="editMode" class="select-checkbox" :model-value="selectedIds.includes(item.id)" @change="toggleSelect(item.id)" @click.stop />
          <div class="goods-image"><img :src="item.main_image" :alt="item.name" /></div>
          <div class="goods-info">
            <div class="goods-name">{{ item.name }}</div>
            <div class="goods-price-row">
              <span class="goods-price">¥{{ item.price }}</span>
              <span class="goods-market" v-if="item.market_price">¥{{ item.market_price }}</span>
            </div>
            <div class="goods-meta">
              <span>已售{{ item.sales || 0 }}</span>
            </div>
          </div>
          <div class="cancel-btn" v-if="!editMode" @click.stop="handleCancel(item)">
            <el-icon><StarFilled /></el-icon>
          </div>
        </div>
      </div>
      <div class="empty-collect" v-else>
        <el-icon size="80" color="#ddd"><Star /></el-icon>
        <p>暂无收藏商品</p>
        <el-button type="primary" size="large" @click="$router.push('/products')">去逛逛</el-button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import request from '@/utils/request'
const router = useRouter()
const list = ref([])
const editMode = ref(false)
const selectedIds = ref([])
const selectAll = ref(false)
const fetchList = async () => {
  try {
    const res = await request({ url: '/user/collects' })
    list.value = res.data?.list || res.data || []
  } catch (e) { console.error(e) }
}
const goDetail = (item) => { if (!editMode.value) router.push(`/product/${item.goods_id || item.product_id || item.id}`) }
const handleCancel = async (item) => {
  try {
    await ElMessageBox.confirm('确定取消收藏该商品？', '提示', { type: 'warning' })
    await request({ url: '/user/collects/cancel', method: 'post', data: { goods_id: item.goods_id || item.product_id || item.id } })
    ElMessage.success('已取消收藏')
    fetchList()
  } catch (e) {}
}
const toggleSelect = (id) => {
  const idx = selectedIds.value.indexOf(id)
  if (idx > -1) selectedIds.value.splice(idx, 1)
  else selectedIds.value.push(id)
  selectAll.value = selectedIds.value.length === list.value.length
}
const toggleSelectAll = () => {
  if (selectAll.value) selectedIds.value = list.value.map(i => i.id)
  else selectedIds.value = []
}
const batchCancel = async () => {
  try {
    await ElMessageBox.confirm(`确定取消收藏选中的${selectedIds.value.length}件商品？`, '提示', { type: 'warning' })
    ElMessage.success('已取消收藏')
    list.value = list.value.filter(i => !selectedIds.value.includes(i.id))
    selectedIds.value = []
    editMode.value = false
  } catch (e) {}
}
onMounted(fetchList)
</script>
<style scoped>
.collect-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h2 { font-size: 22px; color: #333; margin: 0; }
.header-actions { display: flex; align-items: center; gap: 12px; }
.collect-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; }
.collect-card { background: #fff; border-radius: 8px; overflow: hidden; cursor: pointer; transition: all 0.2s; position: relative; border: 2px solid transparent; }
.collect-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.collect-card.selected { border-color: #e6a23c; }
.select-checkbox { position: absolute; top: 10px; left: 10px; z-index: 10; }
.goods-image { width: 100%; padding-top: 100%; position: relative; background: #fafafa; }
.goods-image img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
.goods-info { padding: 12px; }
.goods-name { font-size: 13px; color: #333; line-height: 1.4; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; margin-bottom: 8px; }
.goods-price-row { display: flex; align-items: baseline; gap: 8px; margin-bottom: 6px; }
.goods-price { font-size: 16px; color: #f56c6c; font-weight: bold; }
.goods-market { font-size: 12px; color: #ccc; text-decoration: line-through; }
.goods-meta { font-size: 11px; color: #999; }
.cancel-btn { position: absolute; top: 10px; right: 10px; width: 32px; height: 32px; background: rgba(255,255,255,0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #f56c6c; cursor: pointer; opacity: 0; transition: opacity 0.2s; }
.collect-card:hover .cancel-btn { opacity: 1; }
.empty-collect { background: #fff; border-radius: 8px; padding: 60px 20px; text-align: center; }
.empty-collect p { color: #999; margin: 16px 0; }

/* ========== 移动端适配 ========== */
@media (max-width: 768px) {
  .collect-page { padding: 10px 0; min-height: calc(100vh - 120px); }
  .container { max-width: 100%; padding: 0 12px; }
  .page-header { flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
  .page-title { font-size: 16px; }
  .header-actions { flex-wrap: wrap; gap: 8px; }
  
  /* 收藏商品网格改2列 */
  .collect-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .collect-card { border-radius: 6px; overflow: hidden; }
  .product-image { height: 140px; }
  .product-info { padding: 8px; }
  .product-name { font-size: 12px; height: 32px; line-height: 1.4; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
  .product-price { font-size: 14px; color: #f56c6c; font-weight: bold; }
  .product-actions { padding: 8px; gap: 6px; border-top: 1px solid #f5f5f5; }
  .product-actions .el-button { font-size: 12px !important; padding: 6px 10px !important; }
  
  .pagination-wrap { margin-top: 10px; overflow-x: auto; }
  .empty-collect { padding: 40px 16px; border-radius: 6px; text-align: center; }
  .empty-collect p { font-size: 13px; margin: 12px 0; }
}

@media (max-width: 480px) {
  .container { padding: 0 8px; }
  .collect-grid { gap: 8px; }
  .product-image { height: 120px; }
}
</style>
