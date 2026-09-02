<template>
  <div class="after-sale-page">
    <div class="page-header">
      <h2>售后管理</h2>
      <div class="header-stats">
        <div class="stat-item"><span class="stat-value">{{ stats.pending }}</span><span class="stat-label">待审核</span></div>
        <div class="stat-item"><span class="stat-value">{{ stats.processing }}</span><span class="stat-label">处理中</span></div>
        <div class="stat-item"><span class="stat-value">{{ stats.completed }}</span><span class="stat-label">已完成</span></div>
        <div class="stat-item"><span class="stat-value">{{ stats.rejected }}</span><span class="stat-label">已拒绝</span></div>
      </div>
    </div>
    <div class="filter-bar">
      <el-tabs v-model="activeTab" @tab-change="fetchList">
        <el-tab-pane label="全部" name="all" />
        <el-tab-pane label="待审核" name="0" />
        <el-tab-pane label="处理中" name="1" />
        <el-tab-pane label="已完成" name="3" />
        <el-tab-pane label="已拒绝" name="2" />
      </el-tabs>
      <div class="filter-actions">
        <el-input v-model="keyword" placeholder="搜索售后单号/订单号" style="width: 220px" clearable @keyup.enter="fetchList" />
        <el-button type="primary" @click="fetchList">搜索</el-button>
      </div>
    </div>
    <el-table :data="list" v-loading="loading" stripe style="width: 100%">
      <el-table-column prop="after_sale_no" label="售后单号" width="180" />
      <el-table-column prop="order_no" label="订单号" width="160" />
      <el-table-column label="商品信息" min-width="200">
        <template #default="{ row }">
          <div class="goods-cell">
            <img :src="row.main_image" class="goods-img" />
            <div class="goods-info">
              <div class="goods-name">{{ row.name }}</div>
              <div class="goods-spec">{{ row.specs }}</div>
            </div>
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="type_text" label="售后类型" width="100" />
      <el-table-column prop="refund_amount" label="退款金额" width="100">
        <template #default="{ row }"><span class="refund-amount">¥{{ row.refund_amount }}</span></template>
      </el-table-column>
      <el-table-column prop="reason" label="售后原因" width="120" show-overflow-tooltip />
      <el-table-column prop="status_text" label="状态" width="90">
        <template #default="{ row }">
          <el-tag :type="statusTagType[row.status]" size="small">{{ row.status_text }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="申请时间" width="160" />
      <el-table-column label="操作" width="180" fixed="right">
        <template #default="{ row }">
          <el-button size="small" type="primary" link @click="viewDetail(row)">详情</el-button>
          <el-button v-if="row.status == 0" size="small" type="success" link @click="auditDialog(row, 1)">通过</el-button>
          <el-button v-if="row.status == 0" size="small" type="danger" link @click="auditDialog(row, 2)">拒绝</el-button>
          <el-button v-if="row.status == 1 && row.type == 1" size="small" type="warning" link @click="confirmReceive(row)">确认收货</el-button>
        </template>
      </el-table-column>
    </el-table>
    <div class="pagination-wrap">
      <el-pagination v-model:current-page="page" v-model:page-size="limit" :total="total" layout="total, prev, pager, next, jumper" @current-change="fetchList" />
    </div>
    <!-- 审核弹窗 -->
    <el-dialog v-model="showAudit" :title="auditStatus == 1 ? '审核通过' : '审核拒绝'" width="450px">
      <el-form :model="auditForm" label-width="80px">
        <el-form-item label="审核备注">
          <el-input v-model="auditForm.remark" type="textarea" :rows="3" :placeholder="auditStatus == 1 ? '请输入审核备注（选填）' : '请输入拒绝原因'" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showAudit = false">取消</el-button>
        <el-button type="primary" @click="submitAudit">确认</el-button>
      </template>
    </el-dialog>
    <!-- 详情弹窗 -->
    <el-dialog v-model="showDetail" title="售后详情" width="600px">
      <div v-if="currentRow" class="detail-content">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="售后单号">{{ currentRow.after_sale_no }}</el-descriptions-item>
          <el-descriptions-item label="订单号">{{ currentRow.order_no }}</el-descriptions-item>
          <el-descriptions-item label="售后类型">{{ currentRow.type_text }}</el-descriptions-item>
          <el-descriptions-item label="退款金额">¥{{ currentRow.refund_amount }}</el-descriptions-item>
          <el-descriptions-item label="售后原因">{{ currentRow.reason }}</el-descriptions-item>
          <el-descriptions-item label="状态"><el-tag :type="statusTagType[currentRow.status]" size="small">{{ currentRow.status_text }}</el-tag></el-descriptions-item>
          <el-descriptions-item label="申请时间" :span="2">{{ currentRow.created_at }}</el-descriptions-item>
          <el-descriptions-item label="问题描述" :span="2">{{ currentRow.description }}</el-descriptions-item>
        </el-descriptions>
        <div class="detail-images" v-if="currentRow.images && currentRow.images.length">
          <h4>凭证图片</h4>
          <div class="image-list">
            <img v-for="(img, idx) in currentRow.images" :key="idx" :src="img" class="detail-img" />
          </div>
        </div>
      </div>
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
const limit = ref(20)
const activeTab = ref('all')
const keyword = ref('')
const stats = ref({ pending: 0, processing: 0, completed: 0, rejected: 0 })
const statusTagType = { 0: 'warning', 1: 'primary', 2: 'danger', 3: 'success' }
const showAudit = ref(false)
const showDetail = ref(false)
const auditStatus = ref(1)
const currentRow = ref(null)
const auditForm = reactive({ id: null, remark: '' })
const fetchList = () => {
  loading.value = true
  setTimeout(() => {
    list.value = [
      { id: 1, after_sale_no: 'AS20260901001', order_no: 'ORD20260901001', name: '示例商品1', specs: '规格：默认', main_image: '', type: 1, type_text: '退货退款', refund_amount: '99.00', reason: '质量问题', description: '商品收到后发现有破损', images: [], status: 0, status_text: '待审核', created_at: '2026-09-01 14:30:00' },
      { id: 2, after_sale_no: 'AS20260901002', order_no: 'ORD20260901002', name: '示例商品2', specs: '规格：默认', main_image: '', type: 2, type_text: '仅退款', refund_amount: '50.00', reason: '不想要了', description: '拍错了，申请退款', images: [], status: 1, status_text: '处理中', created_at: '2026-09-01 10:00:00' },
      { id: 3, after_sale_no: 'AS20260831001', order_no: 'ORD20260831001', name: '示例商品3', specs: '规格：默认', main_image: '', type: 1, type_text: '退货退款', refund_amount: '199.00', reason: '发错货', description: '收到的商品和订单不符', images: [], status: 3, status_text: '已完成', created_at: '2026-08-31 16:00:00' },
    ]
    total.value = 3
    stats.value = { pending: 1, processing: 1, completed: 1, rejected: 0 }
    loading.value = false
  }, 300)
}
const viewDetail = (row) => { currentRow.value = row; showDetail.value = true }
const auditDialog = (row, status) => { currentRow.value = row; auditStatus.value = status; auditForm.id = row.id; auditForm.remark = ''; showAudit.value = true }
const submitAudit = () => {
  if (auditStatus.value == 2 && !auditForm.remark) { ElMessage.warning('请输入拒绝原因'); return }
  ElMessage.success(auditStatus.value == 1 ? '已审核通过' : '已拒绝')
  showAudit.value = false
  fetchList()
}
const confirmReceive = (row) => { ElMessage.success('已确认收货，退款将在1-3个工作日内到账'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>
.after-sale-page { padding: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.page-header h2 { font-size: 20px; margin: 0; }
.header-stats { display: flex; gap: 24px; }
.stat-item { text-align: center; }
.stat-value { display: block; font-size: 24px; font-weight: bold; color: #e6a23c; }
.stat-label { font-size: 12px; color: #999; }
.filter-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.filter-actions { display: flex; gap: 12px; }
.goods-cell { display: flex; gap: 10px; align-items: center; }
.goods-img { width: 50px; height: 50px; border-radius: 4px; object-fit: cover; }
.goods-info { }
.goods-name { font-size: 13px; color: #333; }
.goods-spec { font-size: 12px; color: #999; }
.refund-amount { color: #f56c6c; font-weight: bold; }
.pagination-wrap { display: flex; justify-content: flex-end; margin-top: 16px; }
.detail-content h4 { margin: 16px 0 8px 0; font-size: 14px; }
.image-list { display: flex; gap: 10px; flex-wrap: wrap; }
.detail-img { width: 80px; height: 80px; border-radius: 4px; object-fit: cover; }
</style>
