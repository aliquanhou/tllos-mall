<template>
  <div class="coupon-page">
    <div class="container">
      <div class="page-header">
        <h2>我的优惠券</h2>
      </div>
      <div class="tab-bar">
        <div class="tab" v-for="tab in tabs" :key="tab.value" :class="{active: activeTab === tab.value}" @click="activeTab = tab.value">
          {{ tab.label }}
          <span class="tab-count" v-if="getCount(tab.value) > 0">({{ getCount(tab.value) }})</span>
        </div>
      </div>
      <div class="coupon-list" v-if="filteredList.length">
        <div class="coupon-card" v-for="item in filteredList" :key="item.id" :class="{used: item.status === 1, expired: item.status === 2}">
          <div class="coupon-left">
            <div class="amount"><span class="currency">¥</span>{{ item.amount }}</div>
            <div class="condition">满{{ item.min_amount }}可用</div>
          </div>
          <div class="coupon-right">
            <div class="coupon-name">{{ item.name }}</div>
            <div class="coupon-time">
              <el-icon><Clock /></el-icon>
              {{ item.start_time }} 至 {{ item.end_time }}
            </div>
            <div class="coupon-desc" v-if="item.description">{{ item.description }}</div>
            <div class="coupon-status">
              <el-tag v-if="item.status === 0" type="success">可使用</el-tag>
              <el-tag v-else-if="item.status === 1" type="info">已使用</el-tag>
              <el-tag v-else type="danger">已过期</el-tag>
            </div>
          </div>
          <div class="coupon-action" v-if="item.status === 0">
            <el-button type="primary" size="small" @click="useCoupon(item)">立即使用</el-button>
          </div>
        </div>
      </div>
      <div class="empty-coupon" v-else>
        <el-icon size="80" color="#ddd"><Ticket /></el-icon>
        <p>暂无{{ activeTab === 'all' ? '' : tabs.find(t => t.value === activeTab)?.label }}优惠券</p>
        <el-button type="primary" size="large" @click="$router.push('/products')">去逛逛领券</el-button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import request from '@/utils/request'
const router = useRouter()
const list = ref([])
const activeTab = ref('available')
const tabs = [
  { value: 'all', label: '全部' },
  { value: 'available', label: '可使用' },
  { value: 'used', label: '已使用' },
  { value: 'expired', label: '已过期' },
]
const filteredList = computed(() => {
  if (activeTab.value === 'all') return list.value
  if (activeTab.value === 'available') return list.value.filter(i => i.status === 0)
  if (activeTab.value === 'used') return list.value.filter(i => i.status === 1)
  if (activeTab.value === 'expired') return list.value.filter(i => i.status === 2)
  return list.value
})
const getCount = (tab) => {
  if (tab === 'all') return list.value.length
  if (tab === 'available') return list.value.filter(i => i.status === 0).length
  if (tab === 'used') return list.value.filter(i => i.status === 1).length
  if (tab === 'expired') return list.value.filter(i => i.status === 2).length
  return 0
}
const fetchList = async () => {
  try {
    const res = await request({ url: '/user/coupons' })
    list.value = res.data?.list || res.data || []
  } catch (e) { console.error(e) }
}
const useCoupon = (item) => { ElMessage.success('正在为您跳转使用优惠券'); router.push('/products') }
onMounted(fetchList)
</script>
<style scoped>
.coupon-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 900px; margin: 0 auto; padding: 0 20px; }
.page-header { margin-bottom: 16px; }
.page-header h2 { font-size: 22px; color: #333; margin: 0; }
.tab-bar { display: flex; gap: 0; background: #fff; border-radius: 8px; padding: 0 20px; margin-bottom: 16px; }
.tab { padding: 14px 24px; font-size: 14px; color: #666; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; }
.tab:hover { color: #e6a23c; }
.tab.active { color: #e6a23c; border-bottom-color: #e6a23c; font-weight: bold; }
.tab-count { font-size: 12px; color: #999; }
.coupon-list { display: flex; flex-direction: column; gap: 16px; }
.coupon-card { display: flex; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: transform 0.2s; }
.coupon-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.1); }
.coupon-left { width: 160px; background: linear-gradient(135deg, #f56c6c, #e64c4c); color: #fff; padding: 24px 16px; text-align: center; display: flex; flex-direction: column; justify-content: center; position: relative; }
.coupon-left::after { content: ''; position: absolute; right: -6px; top: 50%; transform: translateY(-50%); width: 12px; height: 12px; background: #f5f5f5; border-radius: 50%; }
.amount { font-size: 36px; font-weight: bold; }
.amount .currency { font-size: 18px; margin-right: 2px; }
.condition { font-size: 13px; opacity: 0.9; margin-top: 6px; }
.coupon-right { flex: 1; padding: 20px 24px; display: flex; flex-direction: column; justify-content: center; }
.coupon-name { font-size: 17px; font-weight: 600; color: #333; margin-bottom: 10px; }
.coupon-time { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #999; margin-bottom: 6px; }
.coupon-desc { font-size: 13px; color: #666; margin-bottom: 8px; }
.coupon-status { }
.coupon-action { width: 120px; display: flex; align-items: center; justify-content: center; padding: 0 16px; border-left: 1px dashed #eee; }
.coupon-card.used .coupon-left { background: linear-gradient(135deg, #ccc, #bbb); }
.coupon-card.expired .coupon-left { background: linear-gradient(135deg, #999, #888); }
.coupon-card.used, .coupon-card.expired { opacity: 0.7; }
.empty-coupon { background: #fff; border-radius: 8px; padding: 60px 20px; text-align: center; }
.empty-coupon p { color: #999; margin: 16px 0; }

/* ========== 移动端适配 ========== */
@media (max-width: 768px) {
  .coupon-page { padding: 10px 0; min-height: calc(100vh - 120px); }
  .container { max-width: 100%; padding: 0 12px; }
  .page-header { margin-bottom: 10px; }
  .page-title { font-size: 16px; }
  
  /* Tab导航横向滚动 */
  .tab-bar { display: flex; overflow-x: auto; gap: 0; margin-bottom: 10px; background: #fff; border-radius: 6px; padding: 0 8px; }
  .tab { padding: 10px 16px; font-size: 13px; white-space: nowrap; }
  .tab-count { font-size: 11px; }
  
  /* 优惠券卡片改单列 */
  .coupon-list { display: flex; flex-direction: column; gap: 10px; }
  .coupon-card { display: flex; flex-direction: column; border-radius: 6px; overflow: hidden; }
  .coupon-left { width: 100%; padding: 14px; text-align: center; }
  .coupon-amount { font-size: 24px; color: #f56c6c; font-weight: bold; }
  .coupon-condition { font-size: 12px; color: #999; }
  .coupon-right { width: 100%; padding: 12px; }
  .coupon-name { font-size: 14px; margin-bottom: 6px; }
  .coupon-desc { font-size: 12px; color: #666; margin-bottom: 6px; }
  .coupon-time { font-size: 11px; color: #999; margin-bottom: 8px; }
  .coupon-action .el-button { font-size: 12px !important; padding: 6px 14px !important; width: 100%; }
  
  .empty-coupon { padding: 40px 16px; border-radius: 6px; text-align: center; }
  .empty-coupon p { font-size: 13px; margin: 12px 0; }
}

@media (max-width: 480px) {
  .container { padding: 0 8px; }
  .coupon-amount { font-size: 22px; }
}
</style>
