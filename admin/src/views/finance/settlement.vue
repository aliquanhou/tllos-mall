<template>
  <el-card shadow="never">
    <template #header><span>商家结算</span></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="结算单号/商家" clearable style="width:200px" /></el-form-item>
      <el-form-item label="状态">
        <el-select v-model="query.status" placeholder="全部" clearable style="width:120px">
          <el-option :value="0" label="待结算" /><el-option :value="1" label="已结算" /><el-option :value="2" label="已驳回" />
        </el-select>
      </el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
    </el-form>
    <el-row :gutter="16" class="stats-row">
      <el-col :span="6"><el-card shadow="hover"><div class="stat"><div class="val">¥{{ stats.total_amount }}</div><div class="lbl">结算总额</div></div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover"><div class="stat"><div class="val">{{ stats.total_count }}</div><div class="lbl">结算单数</div></div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#e6a23c">{{ stats.pending_count }}</div><div class="lbl">待结算</div></div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#67c23a">{{ stats.settled_count }}</div><div class="lbl">已结算</div></div></el-card></el-col>
    </el-row>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="settlement_no" label="结算单号" width="170" />
      <el-table-column prop="merchant_name" label="商家" min-width="140" />
      <el-table-column label="结算周期" width="200" align="center"><template #default="{ row }">{{ row.start_date?.slice(0,10) }} 至 {{ row.end_date?.slice(0,10) }}</template></el-table-column>
      <el-table-column label="订单数" width="80" align="center"><template #default="{ row }">{{ row.order_count }}</template></el-table-column>
      <el-table-column label="订单金额" width="110" align="center"><template #default="{ row }">¥{{ row.order_amount }}</template></el-table-column>
      <el-table-column label="退款金额" width="100" align="center"><template #default="{ row }"><span style="color:#f56c6c">¥{{ row.refund_amount }}</span></template></el-table-column>
      <el-table-column label="平台佣金" width="100" align="center"><template #default="{ row }">¥{{ row.commission }}</template></el-table-column>
      <el-table-column label="结算金额" width="110" align="center"><template #default="{ row }"><span style="color:#67c23a;font-weight:bold">¥{{ row.settlement_amount }}</span></template></el-table-column>
      <el-table-column label="状态" width="90" align="center"><template #default="{ row }"><el-tag :type="row.status===1?'success':'warning'" size="small">{{ row.status===1?'已结算':'待结算' }}</el-tag></template></el-table-column>
      <el-table-column label="操作" width="120" align="center" fixed="right">
        <template #default="{ row }"><el-button v-if="row.status===0" size="small" type="primary" @click="handleConfirm(row)">确认结算</el-button></template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getSettlementList, confirmSettlement } from '@/api/finance'
const list = ref([]); const total = ref(0); const loading = ref(false)
const stats = ref({ total_count:0, total_amount:0, pending_count:0, pending_amount:0, settled_count:0 })
const query = reactive({ page:1, limit:20, keyword:'', status:null })
const fetchList = async () => { loading.value = true; try { const res = await getSettlementList(query); list.value = res.data.list||[]; total.value = res.data.total||0; stats.value = res.data.stats||stats.value } finally { loading.value = false } }
const resetQuery = () => { Object.assign(query,{page:1,limit:20,keyword:'',status:null}); fetchList() }
const handleConfirm = async row => { await ElMessageBox.confirm(`确定给商家"${row.merchant_name}"结算¥${row.settlement_amount}？`,'确认结算',{type:'warning'}); await confirmSettlement(row.id); ElMessage.success('结算成功'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.search-form{margin-bottom:16px}.stats-row{margin-bottom:16px}.stat{text-align:center;padding:8px 0}.stat .val{font-size:22px;font-weight:bold;color:#303133}.stat .lbl{font-size:13px;color:#909399;margin-top:4px}</style>
