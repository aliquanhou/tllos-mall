<template>
  <div>
    <el-row :gutter="16" class="stat-cards">
      <el-col :span="4"><el-card shadow="never"><div class="stat-num">{{ stats.total }}</div><div class="stat-label">全部</div></el-card></el-col>
      <el-col :span="4"><el-card shadow="never"><div class="stat-num" style="color:#e6a23c">{{ stats.pending }}</div><div class="stat-label">待审核</div></el-card></el-col>
      <el-col :span="4"><el-card shadow="never"><div class="stat-num" style="color:#409eff">{{ stats.wait_return }}</div><div class="stat-label">待退货</div></el-card></el-col>
      <el-col :span="4"><el-card shadow="never"><div class="stat-num" style="color:#909399">{{ stats.wait_receive }}</div><div class="stat-label">待收货</div></el-card></el-col>
      <el-col :span="4"><el-card shadow="never"><div class="stat-num" style="color:#67c23a">{{ stats.completed }}</div><div class="stat-label">已完成</div></el-card></el-col>
      <el-col :span="4"><el-card shadow="never"><div class="stat-num" style="color:#f56c6c">{{ stats.rejected }}</div><div class="stat-label">已拒绝</div></el-card></el-col>
    </el-row>
    <el-card shadow="never" style="margin-top:16px">
      <el-form :inline="true" class="search-form">
        <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="订单号/原因" clearable style="width:180px" /></el-form-item>
        <el-form-item label="类型"><el-select v-model="query.type" placeholder="全部" clearable style="width:130px">
          <el-option :value="1" label="退货退款" /><el-option :value="2" label="仅退款" /><el-option :value="3" label="换货" /><el-option :value="4" label="补发" />
        </el-select></el-form-item>
        <el-form-item label="状态"><el-select v-model="query.status" placeholder="全部" clearable style="width:120px">
          <el-option :value="0" label="待审核" /><el-option :value="1" label="审核通过" /><el-option :value="2" label="审核拒绝" />
          <el-option :value="3" label="已完成" /><el-option :value="4" label="待退货" /><el-option :value="5" label="已取消" /><el-option :value="6" label="待收货" />
        </el-select></el-form-item>
        <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetSearch">重置</el-button></el-form-item>
      </el-form>
      <el-table :data="list" border v-loading="loading">
        <el-table-column prop="order_no" label="订单号" width="170" />
        <el-table-column label="类型" width="100" align="center"><template #default="{row}">{{ typeText(row.type) }}</template></el-table-column>
        <el-table-column prop="reason" label="售后原因" min-width="120" show-overflow-tooltip />
        <el-table-column label="退款金额" width="100" align="center"><template #default="{row}"><span style="color:#f56c6c">¥{{row.refund_amount}}</span></template></el-table-column>
        <el-table-column label="退货物流" width="160"><template #default="{row}">
          <span v-if="row.return_express_no">{{ row.return_express_company }} {{ row.return_express_no }}</span>
          <span v-else style="color:#c0c4cc">-</span>
        </template></el-table-column>
        <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="statusType(row.status)" size="small">{{ statusText(row.status) }}</el-tag></template></el-table-column>
        <el-table-column prop="created_at" label="申请时间" width="170" align="center" />
        <el-table-column label="操作" width="220" align="center" fixed="right"><template #default="{row}">
          <el-button size="small" @click="handleDetail(row)">详情</el-button>
          <el-button v-if="row.status===0" size="small" type="success" @click="handleAudit(row,1)">通过</el-button>
          <el-button v-if="row.status===0" size="small" type="danger" @click="handleAudit(row,2)">拒绝</el-button>
          <el-button v-if="row.status===6" size="small" type="primary" @click="handleReceive(row)">确认收货</el-button>
          <el-button v-if="row.status===1||row.status===4" size="small" type="warning" @click="handleComplete(row)">强制完成</el-button>
        </template></el-table-column>
      </el-table>
      <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
    </el-card>
    <el-dialog v-model="detailVisible" title="售后详情" width="700px">
      <div v-if="currentDetail">
        <el-descriptions :column="2" border size="small">
          <el-descriptions-item label="售后单号">#{{ currentDetail.info.id }}</el-descriptions-item>
          <el-descriptions-item label="订单号">{{ currentDetail.info.order_no }}</el-descriptions-item>
          <el-descriptions-item label="售后类型">{{ typeText(currentDetail.info.type) }}</el-descriptions-item>
          <el-descriptions-item label="状态"><el-tag :type="statusType(currentDetail.info.status)" size="small">{{ statusText(currentDetail.info.status) }}</el-tag></el-descriptions-item>
          <el-descriptions-item label="退款金额">¥{{ currentDetail.info.refund_amount }}</el-descriptions-item>
          <el-descriptions-item label="申请时间">{{ currentDetail.info.created_at }}</el-descriptions-item>
          <el-descriptions-item label="售后原因">{{ currentDetail.info.reason }}</el-descriptions-item>
          <el-descriptions-item label="审核备注">{{ currentDetail.info.audit_remark || '-' }}</el-descriptions-item>
          <el-descriptions-item label="退货物流" v-if="currentDetail.info.return_express_no">{{ currentDetail.info.return_express_company }} {{ currentDetail.info.return_express_no }}</el-descriptions-item>
          <el-descriptions-item label="退货时间" v-if="currentDetail.info.return_ship_time">{{ currentDetail.info.return_ship_time }}</el-descriptions-item>
          <el-descriptions-item label="确认收货" v-if="currentDetail.info.receive_time">{{ currentDetail.info.receive_time }}</el-descriptions-item>
          <el-descriptions-item label="退款时间" v-if="currentDetail.info.refund_time">{{ currentDetail.info.refund_time }}</el-descriptions-item>
        </el-descriptions>
        <h4 style="margin:16px 0 8px">商品信息</h4>
        <el-table :data="currentDetail.order_items" size="small" border>
          <el-table-column prop="product_name" label="商品名称" />
          <el-table-column prop="sku_text" label="规格" width="120" />
          <el-table-column prop="price" label="单价" width="100" />
          <el-table-column prop="quantity" label="数量" width="80" />
          <el-table-column prop="pay_amount" label="实付" width="100" />
        </el-table>
        <h4 style="margin:16px 0 8px">操作日志</h4>
        <el-timeline>
          <el-timeline-item v-for="log in currentDetail.logs" :key="log.id" :timestamp="log.created_at" placement="top">
            {{ log.action }} <span v-if="log.remark" style="color:#909399">（{{ log.remark }}）</span>
          </el-timeline-item>
        </el-timeline>
      </div>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getAfterSaleList, getAfterSaleDetail, auditAfterSale, completeAfterSale, receiveAfterSale } from '@/api/afterSale'
const list = ref([]); const total = ref(0); const loading = ref(false)
const stats = reactive({ total:0, pending:0, wait_return:0, wait_receive:0, completed:0, rejected:0 })
const query = reactive({ page:1, limit:20, keyword:'', type:null, status:null })
const detailVisible = ref(false); const currentDetail = ref(null)
const typeText = t => ({1:'退货退款',2:'仅退款',3:'换货',4:'补发'}[t] || '未知')
const statusText = s => ({0:'待审核',1:'审核通过',2:'审核拒绝',3:'已完成',4:'待退货',5:'已取消',6:'待收货'}[s] || '未知')
const statusType = s => ({0:'warning',1:'success',2:'danger',3:'info',4:'primary',5:'info',6:'warning'}[s] || '')
const fetchList = async () => { loading.value=true; try { const res = await getAfterSaleList(query); list.value=res.data.list||[]; total.value=res.data.total||0; Object.assign(stats, res.data.stats||{}) } finally { loading.value=false } }
const resetSearch = () => { query.keyword=''; query.type=null; query.status=null; query.page=1; fetchList() }
const handleDetail = async row => { const res = await getAfterSaleDetail(row.id); currentDetail.value = res.data; detailVisible.value=true }
const handleAudit = async (row, status) => {
  let remark = ''
  if (status === 2) { const { value } = await ElMessageBox.prompt('请输入拒绝原因', '审核拒绝', { confirmButtonText:'确定', cancelButtonText:'取消' }); remark = value }
  await ElMessageBox.confirm(`确定${status===1?'通过':'拒绝'}售后申请？`, '提示', { type: status===1?'success':'warning' })
  await auditAfterSale(row.id, { status, audit_remark: remark })
  ElMessage.success(status===1?'已通过':'已拒绝'); fetchList()
}
const handleReceive = async row => { await ElMessageBox.confirm('确认收到退货商品？确认后将完成退款。', '提示', { type:'info' }); await receiveAfterSale(row.id); ElMessage.success('已确认收货，退款完成'); fetchList() }
const handleComplete = async row => { await ElMessageBox.confirm('确定强制完成售后？将跳过退货环节。', '提示', { type:'warning' }); await completeAfterSale(row.id); ElMessage.success('已完成'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>
.stat-cards{margin-bottom:4px}.stat-num{font-size:28px;font-weight:bold;text-align:center}.stat-label{text-align:center;color:#909399;font-size:13px;margin-top:4px}.search-form{margin-bottom:16px}
</style>
