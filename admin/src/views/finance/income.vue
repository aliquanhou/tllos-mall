<template>
  <el-card shadow="never">
    <template #header><span>订单收款</span></template>
    <el-form :inline="true" class="search-form">
      <el-form-item label="关键词"><el-input v-model="query.keyword" placeholder="订单号/用户/手机号" clearable style="width:200px" /></el-form-item>
      <el-form-item label="支付方式">
        <el-select v-model="query.pay_type" placeholder="全部" clearable style="width:120px">
          <el-option :value="1" label="微信支付" /><el-option :value="2" label="支付宝" /><el-option :value="3" label="余额支付" />
        </el-select>
      </el-form-item>
      <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
    </el-form>
    <el-row :gutter="16" class="stats-row">
      <el-col :span="6"><el-card shadow="hover"><div class="stat"><div class="val">¥{{ stats.total_amount }}</div><div class="lbl">收款总额</div></div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover"><div class="stat"><div class="val">{{ stats.total_count }}</div><div class="lbl">收款笔数</div></div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#67c23a">¥{{ stats.today_amount }}</div><div class="lbl">今日收款</div></div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#e6a23c">{{ stats.today_count }}</div><div class="lbl">今日笔数</div></div></el-card></el-col>
    </el-row>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="order_no" label="订单号" width="200" />
      <el-table-column label="用户" width="140"><template #default="{ row }">{{ row.nickname }}<br /><span style="color:#909399;font-size:12px">{{ row.mobile }}</span></template></el-table-column>
      <el-table-column prop="merchant_name" label="商家" min-width="140" show-overflow-tooltip />
      <el-table-column label="支付方式" width="100" align="center"><template #default="{ row }">{{ payTypeText[row.pay_type] || '未知' }}</template></el-table-column>
      <el-table-column label="收款金额" width="120" align="center"><template #default="{ row }"><span style="color:#f56c6c;font-weight:bold">¥{{ row.pay_amount }}</span></template></el-table-column>
      <el-table-column prop="pay_time" label="支付时间" width="170" align="center" />
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getIncomeList } from '@/api/finance'
const list = ref([]); const total = ref(0); const loading = ref(false)
const stats = ref({ total_count:0, total_amount:0, today_count:0, today_amount:0 })
const query = reactive({ page:1, limit:20, keyword:'', pay_type:null })
const payTypeText = { 1:'微信', 2:'支付宝', 3:'余额' }
const fetchList = async () => { loading.value = true; try { const res = await getIncomeList(query); list.value = res.data.list||[]; total.value = res.data.total||0; stats.value = res.data.stats||stats.value } finally { loading.value = false } }
const resetQuery = () => { Object.assign(query,{page:1,limit:20,keyword:'',pay_type:null}); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.search-form{margin-bottom:16px}.stats-row{margin-bottom:16px}.stat{text-align:center;padding:8px 0}.stat .val{font-size:22px;font-weight:bold;color:#303133}.stat .lbl{font-size:13px;color:#909399;margin-top:4px}</style>
