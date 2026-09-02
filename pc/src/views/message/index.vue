<template>
  <div class="message-page">
    <div class="container">
      <div class="page-header">
        <h2>消息通知</h2>
        <el-button type="primary" plain @click="markAllRead">全部已读</el-button>
      </div>
      <div class="message-wrapper">
        <aside class="message-sidebar">
          <div class="sidebar-item" v-for="cat in categories" :key="cat.value" :class="{active: activeCategory === cat.value}" @click="activeCategory = cat.value">
            <el-icon :size="20"><component :is="cat.icon" /></el-icon>
            <span>{{ cat.label }}</span>
            <el-badge v-if="getUnreadCount(cat.value) > 0" :value="getUnreadCount(cat.value)" class="unread-badge" />
          </div>
        </aside>
        <div class="message-content">
          <div class="message-list" v-if="filteredMessages.length">
            <div class="message-item" v-for="msg in filteredMessages" :key="msg.id" :class="{unread: !msg.is_read}" @click="viewMessage(msg)">
              <div class="message-icon" :class="'icon-' + msg.type">
                <el-icon :size="20"><component :is="getIcon(msg.type)" /></el-icon>
              </div>
              <div class="message-info">
                <div class="message-title">
                  {{ msg.title }}
                  <span class="unread-dot" v-if="!msg.is_read"></span>
                </div>
                <div class="message-content-text">{{ msg.content }}</div>
                <div class="message-time">{{ msg.created_at }}</div>
              </div>
              <div class="message-action" v-if="msg.order_no">
                <el-button size="small" type="primary" link @click.stop="goOrder(msg.order_no)">查看订单</el-button>
              </div>
            </div>
          </div>
          <div class="empty-message" v-else>
            <el-icon size="64" color="#ddd"><Bell /></el-icon>
            <p>暂无{{ activeCategory === 'all' ? '' : categories.find(c => c.value === activeCategory)?.label }}消息</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
const router = useRouter()
const activeCategory = ref('all')
const categories = [
  { value: 'all', label: '全部消息', icon: 'Bell' },
  { value: 'order', label: '订单消息', icon: 'List' },
  { value: 'after_sale', label: '售后消息', icon: 'RefreshLeft' },
  { value: 'system', label: '系统消息', icon: 'Setting' },
  { value: 'activity', label: '活动消息', icon: 'Present' },
]
const messages = ref([
  { id: 1, type: 'order', title: '订单发货通知', content: '您的订单ORD20260901001已发货，物流公司：顺丰速运，物流单号：SF1234567890', created_at: '2026-09-01 14:30:00', is_read: false, order_no: 'ORD20260901001' },
  { id: 2, type: 'order', title: '订单支付成功', content: '您的订单ORD20260901001支付成功，金额：¥299.00', created_at: '2026-09-01 10:01:00', is_read: true, order_no: 'ORD20260901001' },
  { id: 3, type: 'after_sale', title: '售后审核通过', content: '您的售后申请AS20260901001已审核通过，请尽快寄回商品', created_at: '2026-08-31 16:00:00', is_read: false },
  { id: 4, type: 'system', title: '账户安全提醒', content: '您的账户在新设备上登录，如非本人操作请及时修改密码', created_at: '2026-08-30 09:00:00', is_read: true },
  { id: 5, type: 'activity', title: '限时特惠活动', content: '秋季新品上市，全场满200减30，更有优惠券等你领！', created_at: '2026-08-29 10:00:00', is_read: true },
])
const filteredMessages = computed(() => {
  if (activeCategory.value === 'all') return messages.value
  return messages.value.filter(m => m.type === activeCategory.value)
})
const getUnreadCount = (cat) => {
  if (cat === 'all') return messages.value.filter(m => !m.is_read).length
  return messages.value.filter(m => m.type === cat && !m.is_read).length
}
const getIcon = (type) => {
  const map = { order: 'List', after_sale: 'RefreshLeft', system: 'Setting', activity: 'Present' }
  return map[type] || 'Bell'
}
const viewMessage = (msg) => { msg.is_read = true }
const markAllRead = () => { messages.value.forEach(m => m.is_read = true); ElMessage.success('已全部标记为已读') }
const goOrder = (orderNo) => { router.push('/orders') }
onMounted(() => {})
</script>
<style scoped>
.message-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.page-header h2 { font-size: 22px; color: #333; margin: 0; }
.message-wrapper { display: flex; gap: 16px; align-items: flex-start; }
.message-sidebar { width: 180px; flex-shrink: 0; background: #fff; border-radius: 8px; padding: 8px; position: sticky; top: 20px; }
.sidebar-item { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; color: #666; transition: all 0.2s; position: relative; }
.sidebar-item:hover { background: #fafafa; color: #e6a23c; }
.sidebar-item.active { background: #fdf6ec; color: #e6a23c; font-weight: bold; }
.unread-badge { position: absolute; right: 12px; }
.message-content { flex: 1; min-width: 0; background: #fff; border-radius: 8px; padding: 8px 0; }
.message-list { }
.message-item { display: flex; gap: 16px; padding: 16px 20px; border-bottom: 1px solid #f5f5f5; cursor: pointer; transition: background 0.2s; }
.message-item:hover { background: #fafafa; }
.message-item:last-child { border-bottom: none; }
.message-item.unread { background: #fdfbf7; }
.message-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #fff; }
.icon-order { background: #409eff; }
.icon-after_sale { background: #e6a23c; }
.icon-system { background: #909399; }
.icon-activity { background: #67c23a; }
.message-info { flex: 1; min-width: 0; }
.message-title { font-size: 15px; color: #333; font-weight: 500; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
.unread-dot { width: 8px; height: 8px; background: #f56c6c; border-radius: 50%; flex-shrink: 0; }
.message-content-text { font-size: 13px; color: #666; line-height: 1.5; margin-bottom: 6px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.message-time { font-size: 12px; color: #999; }
.message-action { flex-shrink: 0; }
.empty-message { padding: 60px 20px; text-align: center; }
.empty-message p { color: #999; margin: 16px 0; }
</style>
