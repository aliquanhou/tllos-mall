<template>
  <div class="comment-page">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>商品评价管理</span>
        </div>
      </template>
      <el-form :inline="true" class="search-form">
        <el-form-item label="关键词">
          <el-input v-model="query.keyword" placeholder="评价内容/商品/用户" clearable style="width: 200px" />
        </el-form-item>
        <el-form-item label="评分">
          <el-select v-model="query.rating" placeholder="全部" clearable style="width: 120px">
            <el-option v-for="s in [5,4,3,2,1]" :key="s" :value="s" :label="s + '星'" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="query.is_show" placeholder="全部" clearable style="width: 120px">
            <el-option :value="1" label="显示" />
            <el-option :value="0" label="隐藏" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="fetchList">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>

      <el-row :gutter="16" class="stats-row">
        <el-col :span="6"><el-card shadow="hover"><div class="stat-item"><div class="stat-value">{{ stats.total }}</div><div class="stat-label">总评价数</div></div></el-card></el-col>
        <el-col :span="6"><el-card shadow="hover"><div class="stat-item"><div class="stat-value">{{ stats.today }}</div><div class="stat-label">今日新增</div></div></el-card></el-col>
        <el-col :span="6"><el-card shadow="hover"><div class="stat-item"><div class="stat-value">{{ stats.avg_rating }}</div><div class="stat-label">平均评分</div></div></el-card></el-col>
        <el-col :span="6"><el-card shadow="hover"><div class="stat-item"><div class="stat-value">{{ stats.hidden }}</div><div class="stat-label">已隐藏</div></div></el-card></el-col>
      </el-row>

      <el-table :data="list" border v-loading="loading">
        <el-table-column label="商品" min-width="200">
          <template #default="{ row }">
            <div class="product-cell">
              <el-image :src="row.main_image" style="width: 50px; height: 50px" fit="cover" />
              <span class="product-name">{{ row.product_name }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="用户" width="120">
          <template #default="{ row }">{{ row.nickname || '匿名用户' }}</template>
        </el-table-column>
        <el-table-column label="评分" width="120" align="center">
          <template #default="{ row }">
            <el-rate :model-value="row.rating" disabled size="small" />
          </template>
        </el-table-column>
        <el-table-column prop="content" label="评价内容" min-width="200" show-overflow-tooltip />
        <el-table-column label="商家回复" min-width="150" show-overflow-tooltip>
          <template #default="{ row }">
            <span v-if="row.reply" style="color: #67c23a">{{ row.reply }}</span>
            <span v-else style="color: #c0c4cc">未回复</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="80" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_show === 1 ? 'success' : 'info'" size="small">{{ row.is_show === 1 ? '显示' : '隐藏' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="评价时间" width="160" align="center" />
        <el-table-column label="操作" width="200" align="center" fixed="right">
          <template #default="{ row }">
            <el-button size="small" type="primary" @click="handleReply(row)">回复</el-button>
            <el-button size="small" @click="handleToggle(row)">{{ row.is_show === 1 ? '隐藏' : '显示' }}</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="query.page"
        v-model:page-size="query.limit"
        :total="total"
        :page-sizes="[10, 20, 50]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="fetchList"
        @current-change="fetchList"
        style="margin-top: 16px; justify-content: flex-end"
      />
    </el-card>

    <el-dialog v-model="replyDialogVisible" title="回复评价" width="500px">
      <el-form label-width="80px">
        <el-form-item label="评价内容">
          <div style="background: #f5f7fa; padding: 12px; border-radius: 4px; color: #606266">{{ currentComment?.content }}</div>
        </el-form-item>
        <el-form-item label="回复内容">
          <el-input v-model="replyContent" type="textarea" :rows="4" placeholder="请输入回复内容" maxlength="500" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="replyDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitReply">提交回复</el-button>
      </template>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getCommentList, replyComment, toggleCommentShow, deleteComment } from '@/api/comment'

const list = ref([])
const total = ref(0)
const loading = ref(false)
const stats = ref({ total: 0, today: 0, avg_rating: 0, hidden: 0 })
const query = reactive({ page: 1, limit: 20, keyword: '', rating: null, is_show: null })
const replyDialogVisible = ref(false)
const currentComment = ref(null)
const replyContent = ref('')

const fetchList = async () => {
  loading.value = true
  try {
    const res = await getCommentList(query)
    list.value = res.data.list || []
    total.value = res.data.total || 0
    stats.value = res.data.stats || stats.value
  } finally { loading.value = false }
}
const resetQuery = () => {
  Object.assign(query, { page: 1, limit: 20, keyword: '', rating: null, is_show: null })
  fetchList()
}
const handleReply = row => {
  currentComment.value = row
  replyContent.value = row.reply || ''
  replyDialogVisible.value = true
}
const submitReply = async () => {
  if (!replyContent.value) { ElMessage.warning('请输入回复内容'); return }
  await replyComment(currentComment.value.id, { reply: replyContent.value })
  ElMessage.success('回复成功')
  replyDialogVisible.value = false
  fetchList()
}
const handleToggle = async row => {
  await toggleCommentShow(row.id)
  ElMessage.success(row.is_show === 1 ? '已隐藏' : '已显示')
  fetchList()
}
const handleDelete = async row => {
  await ElMessageBox.confirm('确定删除该评价？', '提示', { type: 'warning' })
  await deleteComment(row.id)
  ElMessage.success('删除成功')
  fetchList()
}
onMounted(fetchList)
</script>
<style scoped>
.card-header { display: flex; justify-content: space-between; align-items: center; }
.search-form { margin-bottom: 16px; }
.stats-row { margin-bottom: 16px; }
.stat-item { text-align: center; padding: 8px 0; }
.stat-value { font-size: 24px; font-weight: bold; color: #303133; }
.stat-label { font-size: 13px; color: #909399; margin-top: 4px; }
.product-cell { display: flex; align-items: center; gap: 10px; }
.product-name { font-size: 13px; color: #303133; }
</style>
