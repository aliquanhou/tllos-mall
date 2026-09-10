<template>
  <el-card shadow="never">
    <template #header><span>提现管理</span></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="商家/开户人/账号" clearable style="width:200px" /></el-form-item>
      <el-form-item label="状态">
        <el-select v-model="query.status" placeholder="全部" clearable style="width:120px">
          <el-option :value="0" label="待审核" /><el-option :value="1" label="已通过" /><el-option :value="2" label="已拒绝" /><el-option :value="3" label="已打款" />
        </el-select>
      </el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
    </el-form>
    <el-row :gutter="16" class="stats-row">
      <el-col :span="5"><el-card shadow="hover"><div class="stat"><div class="val">¥{{ stats.total_amount }}</div><div class="lbl">提现总额</div></div></el-card></el-col>
      <el-col :span="5"><el-card shadow="hover"><div class="stat"><div class="val">{{ stats.total_count }}</div><div class="lbl">提现笔数</div></div></el-card></el-col>
      <el-col :span="5"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#e6a23c">{{ stats.pending_count }}</div><div class="lbl">待审核</div></div></el-card></el-col>
      <el-col :span="5"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#e6a23c">¥{{ stats.pending_amount }}</div><div class="lbl">待审金额</div></div></el-card></el-col>
      <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#67c23a">{{ stats.paid_count }}</div><div class="lbl">已打款</div></div></el-card></el-col>
    </el-row>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="merchant_name" label="商家" min-width="140" />
      <el-table-column label="提现金额" width="110" align="center"><template #default="{ row }"><span style="color:#f56c6c;font-weight:bold">¥{{ row.amount }}</span></template></el-table-column>
      <el-table-column label="手续费" width="90" align="center"><template #default="{ row }">¥{{ row.fee }}</template></el-table-column>
      <el-table-column label="实际到账" width="110" align="center"><template #default="{ row }"><span style="color:#67c23a;font-weight:bold">¥{{ row.actual_amount }}</span></template></el-table-column>
      <el-table-column label="银行信息" min-width="200"><template #default="{ row }">{{ row.bank_name }}<br /><span style="color:#909399;font-size:12px">{{ row.bank_holder }} {{ row.bank_account }}</span></template></el-table-column>
      <el-table-column label="状态" width="90" align="center"><template #default="{ row }"><el-tag :type="statusType[row.status]" size="small">{{ statusText[row.status] }}</el-tag></template></el-table-column>
      <el-table-column prop="created_at" label="申请时间" width="170" align="center" />
      <el-table-column label="操作" width="180" align="center" fixed="right">
        <template #default="{ row }">
          <el-button v-if="row.status === 0" size="small" type="success" @click="handleAudit(row, 1)">通过</el-button>
          <el-button v-if="row.status === 0" size="small" type="danger" @click="handleAudit(row, 2)">拒绝</el-button>
          <el-button v-if="row.status === 1" size="small" type="primary" @click="handlePay(row)">打款</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
    <el-dialog v-model="rejectVisible" title="拒绝提现" width="400px">
      <el-form label-width="80px"><el-form-item label="拒绝原因"><el-input v-model="rejectReason" type="textarea" :rows="3" placeholder="请输入拒绝原因" /></el-form-item></el-form>
      <template #footer><el-button @click="rejectVisible=false">取消</el-button><el-button type="danger" @click="submitReject">确认拒绝</el-button></template>
    </el-dialog>
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getWithdrawList, auditWithdraw, payWithdraw } from '@/api/finance'
const list = ref([]); const total = ref(0); const loading = ref(false)
const stats = ref({ total_count:0, total_amount:0, pending_count:0, pending_amount:0, paid_count:0 })
const query = reactive({ page:1, limit:20, keyword:'', status:null })
const statusText = { 0:'待审核', 1:'已通过', 2:'已拒绝', 3:'已打款' }
const statusType = { 0:'warning', 1:'success', 2:'danger', 3:'info' }
const rejectVisible = ref(false); const rejectReason = ref(''); const currentRow = ref(null)
const fetchList = async () => { loading.value = true; try { const res = await getWithdrawList(query); list.value = res.data.list||[]; total.value = res.data.total||0; stats.value = res.data.stats||stats.value } finally { loading.value = false } }
const resetQuery = () => { Object.assign(query,{page:1,limit:20,keyword:'',status:null}); fetchList() }
const handleAudit = async (row, status) => {
  if (status === 1) { await ElMessageBox.confirm(`确定通过商家"${row.merchant_name}"的提现申请¥${row.amount}？`,'审核通过',{type:'success'}); await auditWithdraw(row.id,{status:1}); ElMessage.success('审核通过') }
  else { currentRow.value = row; rejectReason.value = ''; rejectVisible.value = true }
  fetchList()
}
const submitReject = async () => { if (!rejectReason.value) { ElMessage.warning('请输入拒绝原因'); return }; await auditWithdraw(currentRow.value.id,{status:2,reject_reason:rejectReason.value}); ElMessage.success('已拒绝'); rejectVisible.value=false; fetchList() }
const handlePay = async row => { await ElMessageBox.confirm(`确定给商家"${row.merchant_name}"打款¥${row.actual_amount}？`,'确认打款',{type:'warning'}); await payWithdraw(row.id); ElMessage.success('打款成功'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.search-form{margin-bottom:16px}.stats-row{margin-bottom:16px}.stat{text-align:center;padding:8px 0}.stat .val{font-size:20px;font-weight:bold;color:#303133}.stat .lbl{font-size:13px;color:#909399;margin-top:4px}</style>
