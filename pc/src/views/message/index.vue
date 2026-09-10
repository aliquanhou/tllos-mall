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
import PageHeader from "@/components/PageHeader.vue"
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import request from '@/utils/request'
const router = useRouter()
const loading = ref(false)
const activeCategory = ref('all')
const categories = [
  { value: 'all', label: '全部消息', icon: 'Bell' },
  { value: 'order', label: '订单消息', icon: 'List' },
  { value: 'after_sale', label: '售后消息', icon: 'RefreshLeft' },
  { value: 'system', label: '系统消息', icon: 'Setting' },
  { value: 'activity', label: '活动消息', icon: 'Present' },
]
const messages = ref([])

// 获取消息列表
const fetchMessages = async () => {
  loading.value = true
  try {
    const res = await request({ url: '/notices', method: 'get' })
    const list = res.data?.list || res.data || []
    // 统一字段格式
    messages.value = list.map(item => ({
      id: item.id,
      type: item.type || item.category || 'system',
      title: item.title || item.name || '',
      content: item.content || item.description || '',
      created_at: item.created_at || item.create_time || '',
      is_read: item.is_read || item.read_status === 1 || false
    }))
  } catch (e) {
    console.error('获取消息列表失败:', e)
    messages.value = []
  } finally {
    loading.value = false
  }
}
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
const viewMessage = async (msg) => {
  msg.is_read = true
  // 调用消息详情API（标记已读）
  try {
    await request({ url: `/notices/${msg.id}`, method: 'get' })
  } catch (e) { console.error(e) }
}
const markAllRead = () => { messages.value.forEach(m => m.is_read = true); ElMessage.success('已全部标记为已读') }
const goOrder = (orderNo) => { router.push('/orders') }
onMounted(() => { fetchMessages() })
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

/* 移动端适配 - 用户中心页 */
@media (max-width: 768px) {
  .container { padding: 0 12px; }
  .user-wrapper { flex-direction: column; }
  .user-sidebar { width: 100%; position: static; }
  .user-content { width: 100%; }
  .menu-item { padding: 12px 16px; font-size: 14px; }
  .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .stat-item { padding: 12px; }
  .stat-number { font-size: 20px; }
  .order-quick { grid-template-columns: repeat(4, 1fr); gap: 8px; }
  .order-item { grid-template-columns: 60px 1fr 60px; gap: 8px; }
  .item-spec, .item-price-label { display: none; }
}

</style>
