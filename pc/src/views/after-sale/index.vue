<template>
  <div class="after-sale-page">
    <div class="container">
      <div class="page-header">
        <h2>售后管理</h2>
        <el-button type="primary" @click="showApplyDialog = true"><el-icon><Plus /></el-icon> 申请售后</el-button>
      </div>
      <div class="tab-bar">
        <div class="tab" v-for="tab in tabs" :key="tab.value" :class="{active: activeTab === tab.value}" @click="activeTab = tab.value">
          {{ tab.label }}
          <span class="tab-count" v-if="tab.count">({{ tab.count }})</span>
        </div>
      </div>
      <div class="after-sale-list" v-if="afterSaleList.length">
        <div class="after-sale-card" v-for="item in afterSaleList" :key="item.id">
          <div class="card-header">
            <span class="apply-time">申请时间：{{ item.created_at }}</span>
            <span class="after-sale-no">售后单号：{{ item.after_sale_no }}</span>
            <span class="status" :class="'status-' + item.status">{{ statusMap[item.status] }}</span>
          </div>
          <div class="card-body">
            <div class="goods-info" @click="$router.push(`/product/${item.product_id}`)">
              <div class="goods-image"><img :src="item.main_image" :alt="item.name" /></div>
              <div class="goods-detail">
                <div class="goods-name">{{ item.name }}</div>
                <div class="goods-spec" v-if="item.specs">{{ item.specs }}</div>
                <div class="goods-price">¥{{ item.price }} x{{ item.quantity }}</div>
              </div>
            </div>
            <div class="after-sale-info">
              <div class="info-row"><span>售后类型</span><span>{{ typeMap[item.type] }}</span></div>
              <div class="info-row"><span>退款金额</span><span class="refund-amount">¥{{ item.refund_amount }}</span></div>
              <div class="info-row"><span>申请原因</span><span>{{ item.reason }}</span></div>
            </div>
            <div class="after-sale-actions">
              <el-button size="small" @click="viewDetail(item)">查看详情</el-button>
              <el-button type="primary" size="small" v-if="item.status == 0" @click="cancelApply(item)">撤销申请</el-button>
              <el-button type="warning" size="small" v-if="item.status == 1">等待商家处理</el-button>
              <el-button type="success" size="small" v-if="item.status == 3">已完成</el-button>
            </div>
          </div>
        </div>
      </div>
      <div class="empty-list" v-else>
        <el-icon size="64" color="#ddd"><RefreshLeft /></el-icon>
        <p>暂无售后记录</p>
        <el-button type="primary" @click="$router.push('/orders')">去查看订单</el-button>
      </div>
    </div>
    <!-- 申请售后弹窗 -->
    <el-dialog v-model="showApplyDialog" title="申请售后" width="500px">
      <el-form :model="applyForm" label-width="100px">
        <el-form-item label="选择订单">
          <el-select v-model="applyForm.order_id" placeholder="请选择订单" style="width:100%">
            <el-option v-for="order in orders" :key="order.id" :label="order.order_no" :value="order.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="售后类型">
          <el-radio-group v-model="applyForm.type">
            <el-radio :value="1">退货退款</el-radio>
            <el-radio :value="2">仅退款</el-radio>
            <el-radio :value="3">换货</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="退款金额">
          <el-input v-model="applyForm.refund_amount" placeholder="请输入退款金额" prefix-icon="Wallet" />
        </el-form-item>
        <el-form-item label="申请原因">
          <el-select v-model="applyForm.reason" placeholder="请选择原因" style="width:100%">
            <el-option label="质量问题" value="质量问题" />
            <el-option label="发错货" value="发错货" />
            <el-option label="不想要了" value="不想要了" />
            <el-option label="其他" value="其他" />
          </el-select>
        </el-form-item>
        <el-form-item label="问题描述">
          <el-input v-model="applyForm.description" type="textarea" :rows="3" placeholder="请详细描述问题" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showApplyDialog = false">取消</el-button>
        <el-button type="primary" @click="submitApply">提交申请</el-button>
      </template>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
const activeTab = ref('all')
const showApplyDialog = ref(false)
const tabs = [
  { value: 'all', label: '全部', count: 0 },
  { value: '0', label: '待审核', count: 0 },
  { value: '1', label: '处理中', count: 0 },
  { value: '3', label: '已完成', count: 0 },
  { value: '2', label: '已拒绝', count: 0 },
]
const statusMap = { 0: '待审核', 1: '处理中', 2: '已拒绝', 3: '已完成' }
const typeMap = { 1: '退货退款', 2: '仅退款', 3: '换货' }
const afterSaleList = ref([])
const orders = ref([{ id: 1, order_no: 'ORD20260901001' }])
const applyForm = reactive({ order_id: '', type: 1, refund_amount: '', reason: '', description: '' })
const viewDetail = (item) => { ElMessage.info('查看售后详情：' + item.after_sale_no) }
const cancelApply = async (item) => {
  try {
    await ElMessageBox.confirm('确定撤销该售后申请？', '提示', { type: 'warning' })
    afterSaleList.value = afterSaleList.value.filter(a => a.id !== item.id)
    ElMessage.success('已撤销申请')
  } catch (e) {}
}
const submitApply = () => {
  if (!applyForm.order_id) { ElMessage.warning('请选择订单'); return }
  if (!applyForm.refund_amount) { ElMessage.warning('请输入退款金额'); return }
  ElMessage.success('售后申请已提交')
  showApplyDialog.value = false
}
</script>
<style scoped>
.after-sale-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.page-header h2 { font-size: 22px; color: #333; margin: 0; }
.tab-bar { display: flex; gap: 0; background: #fff; border-radius: 8px; padding: 0 20px; margin-bottom: 16px; }
.tab { padding: 14px 20px; font-size: 14px; color: #666; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; }
.tab:hover { color: #e6a23c; }
.tab.active { color: #e6a23c; border-bottom-color: #e6a23c; font-weight: bold; }
.tab-count { font-size: 12px; color: #999; }
.after-sale-card { background: #fff; border-radius: 8px; margin-bottom: 16px; overflow: hidden; }
.card-header { display: flex; align-items: center; gap: 20px; padding: 12px 20px; background: #fafafa; border-bottom: 1px solid #f0f0f0; font-size: 13px; color: #666; }
.apply-time { }
.after-sale-no { }
.status { margin-left: auto; font-weight: bold; }
.status-0 { color: #e6a23c; }
.status-1 { color: #409eff; }
.status-2 { color: #f56c6c; }
.status-3 { color: #67c23a; }
.card-body { display: flex; padding: 16px 20px; gap: 20px; align-items: center; }
.goods-info { display: flex; gap: 12px; flex: 1; cursor: pointer; }
.goods-image { width: 80px; height: 80px; border-radius: 4px; overflow: hidden; background: #f5f5f5; flex-shrink: 0; }
.goods-image img { width: 100%; height: 100%; object-fit: cover; }
.goods-detail { }
.goods-name { font-size: 14px; color: #333; margin-bottom: 4px; }
.goods-spec { font-size: 12px; color: #999; margin-bottom: 4px; }
.goods-price { font-size: 13px; color: #666; }
.after-sale-info { width: 200px; flex-shrink: 0; }
.info-row { display: flex; justify-content: space-between; font-size: 13px; color: #666; padding: 4px 0; }
.info-row span:first-child { color: #999; }
.refund-amount { color: #f56c6c; font-weight: bold; }
.after-sale-actions { width: 120px; flex-shrink: 0; display: flex; flex-direction: column; gap: 8px; }
.after-sale-actions .el-button { width: 100%; }
.empty-list { background: #fff; border-radius: 8px; padding: 60px 20px; text-align: center; }
.empty-list p { color: #999; margin: 16px 0; }
</style>
