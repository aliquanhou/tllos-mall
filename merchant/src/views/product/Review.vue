<template>
  <div class="review-page">
    <div class="page-header">
      <h2>商品评价管理</h2>
      <div class="header-stats">
        <div class="stat-item"><span class="stat-value">{{ stats.total }}</span><span class="stat-label">总评价</span></div>
        <div class="stat-item"><span class="stat-value">{{ stats.good }}</span><span class="stat-label">好评</span></div>
        <div class="stat-item"><span class="stat-value">{{ stats.medium }}</span><span class="stat-label">中评</span></div>
        <div class="stat-item"><span class="stat-value">{{ stats.bad }}</span><span class="stat-label">差评</span></div>
        <div class="stat-item"><span class="stat-value">{{ stats.pending }}</span><span class="stat-label">待回复</span></div>
      </div>
    </div>
    <div class="filter-bar">
      <el-tabs v-model="activeTab" @tab-change="fetchList">
        <el-tab-pane label="全部" name="all" />
        <el-tab-pane label="好评" name="5" />
        <el-tab-pane label="中评" name="3" />
        <el-tab-pane label="差评" name="1" />
        <el-tab-pane label="待回复" name="pending" />
        <el-tab-pane label="已隐藏" name="hidden" />
      </el-tabs>
      <div class="filter-actions">
        <el-input v-model="keyword" placeholder="搜索商品名称/用户昵称" style="width: 220px" clearable @keyup.enter="fetchList" />
        <el-button type="primary" @click="fetchList">搜索</el-button>
      </div>
    </div>
    <div class="review-list">
      <div class="review-card" v-for="item in list" :key="item.id">
        <div class="review-header">
          <div class="user-info">
            <el-avatar :size="40" :src="item.avatar">{{ item.nickname?.charAt(0) }}</el-avatar>
            <div class="user-detail">
              <div class="user-name">{{ item.nickname }}</div>
              <div class="review-time">{{ item.created_at }}</div>
            </div>
          </div>
          <div class="review-rating">
            <el-rate v-model="item.rating" disabled show-score text-color="#ff9900" />
          </div>
        </div>
        <div class="review-goods">
          <img :src="item.main_image" class="goods-img" />
          <div class="goods-name">{{ item.name }}</div>
          <el-tag size="small" type="info">{{ item.specs }}</el-tag>
        </div>
        <div class="review-content">
          <p>{{ item.content }}</p>
          <div class="review-images" v-if="item.images && item.images.length">
            <img v-for="(img, idx) in item.images" :key="idx" :src="img" class="review-img" />
          </div>
        </div>
        <div class="merchant-reply" v-if="item.reply">
          <div class="reply-label">商家回复：</div>
          <div class="reply-content">{{ item.reply }}</div>
        </div>
        <div class="review-actions">
          <el-button size="small" type="primary" @click="replyDialog(item)" v-if="!item.reply">回复评价</el-button>
          <el-button size="small" type="primary" @click="replyDialog(item)" v-else>修改回复</el-button>
          <el-button size="small" type="warning" @click="toggleShow(item)" v-if="item.is_show">隐藏评价</el-button>
          <el-button size="small" type="success" @click="toggleShow(item)" v-else>显示评价</el-button>
        </div>
      </div>
    </div>
    <div class="pagination-wrap">
      <el-pagination v-model:current-page="page" v-model:page-size="limit" :total="total" layout="total, prev, pager, next, jumper" @current-change="fetchList" />
    </div>
    <!-- 回复弹窗 -->
    <el-dialog v-model="showReply" title="回复评价" width="500px">
      <el-form :model="replyForm" label-width="80px">
        <el-form-item label="回复内容">
          <el-input v-model="replyForm.content" type="textarea" :rows="4" placeholder="请输入回复内容，真诚的回复能提升用户满意度" maxlength="500" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showReply = false">取消</el-button>
        <el-button type="primary" @click="submitReply">提交回复</el-button>
      </template>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
const loading = ref(false)
const list = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(10)
const activeTab = ref('all')
const keyword = ref('')
const stats = ref({ total: 128, good: 105, medium: 15, bad: 8, pending: 3 })
const showReply = ref(false)
const currentRow = ref(null)
const replyForm = reactive({ id: null, content: '' })
const fetchList = () => {
  loading.value = true
  setTimeout(() => {
    list.value = [
      { id: 1, nickname: '用户1', avatar: '', created_at: '2026-09-01 14:30:00', rating: 5, name: '示例商品1', specs: '规格：默认', main_image: '', content: '商品质量很好，物流也很快，非常满意的一次购物体验！', images: [], reply: '', is_show: 1 },
      { id: 2, nickname: '用户2', avatar: '', created_at: '2026-09-01 10:00:00', rating: 3, name: '示例商品2', specs: '规格：默认', main_image: '', content: '商品一般般，和描述有些差距，希望能改进。', images: [], reply: '感谢您的反馈，我们会持续改进商品质量', is_show: 1 },
      { id: 3, nickname: '用户3', avatar: '', created_at: '2026-08-31 16:00:00', rating: 1, name: '示例商品3', specs: '规格：默认', main_image: '', content: '收到的商品有破损，非常不满意！', images: [], reply: '', is_show: 1 },
    ]
    total.value = 3
    loading.value = false
  }, 300)
}
const replyDialog = (row) => { currentRow.value = row; replyForm.id = row.id; replyForm.content = row.reply || ''; showReply.value = true }
const submitReply = () => {
  if (!replyForm.content.trim()) { ElMessage.warning('请输入回复内容'); return }
  if (currentRow.value) currentRow.value.reply = replyForm.content
  ElMessage.success('回复已提交')
  showReply.value = false
}
const toggleShow = (row) => {
  row.is_show = row.is_show ? 0 : 1
  ElMessage.success(row.is_show ? '评价已显示' : '评价已隐藏')
}
onMounted(fetchList)
</script>
<style scoped>
.review-page { padding: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.page-header h2 { font-size: 20px; margin: 0; }
.header-stats { display: flex; gap: 20px; }
.stat-item { text-align: center; }
.stat-value { display: block; font-size: 22px; font-weight: bold; color: #e6a23c; }
.stat-label { font-size: 12px; color: #999; }
.filter-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.filter-actions { display: flex; gap: 12px; }
.review-list { display: flex; flex-direction: column; gap: 16px; }
.review-card { background: #fff; border-radius: 8px; padding: 20px; border: 1px solid #f0f0f0; }
.review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.user-info { display: flex; gap: 12px; align-items: center; }
.user-detail { }
.user-name { font-size: 14px; font-weight: 500; color: #333; }
.review-time { font-size: 12px; color: #999; }
.review-goods { display: flex; gap: 10px; align-items: center; padding: 12px; background: #fafafa; border-radius: 6px; margin-bottom: 12px; }
.goods-img { width: 50px; height: 50px; border-radius: 4px; object-fit: cover; }
.goods-name { flex: 1; font-size: 13px; color: #333; }
.review-content { margin-bottom: 12px; }
.review-content p { font-size: 14px; color: #666; line-height: 1.6; margin: 0 0 8px 0; }
.review-images { display: flex; gap: 8px; }
.review-img { width: 80px; height: 80px; border-radius: 4px; object-fit: cover; }
.merchant-reply { background: #fdf6ec; border-radius: 6px; padding: 12px; margin-bottom: 12px; }
.reply-label { font-size: 13px; color: #e6a23c; font-weight: 500; margin-bottom: 4px; }
.reply-content { font-size: 13px; color: #666; line-height: 1.6; }
.review-actions { display: flex; gap: 12px; justify-content: flex-end; padding-top: 12px; border-top: 1px solid #f5f5f5; }
.pagination-wrap { display: flex; justify-content: flex-end; margin-top: 16px; }
</style>
