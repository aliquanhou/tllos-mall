<template>
  <div class="order-list">
    <el-card shadow="never">
      <el-form :inline="true" :model="searchForm">
        <el-form-item><el-input v-model="searchForm.order_no" placeholder="订单号" clearable style="width:200px" /></el-form-item>
        <el-form-item><el-select v-model="searchForm.status" placeholder="全部状态" clearable style="width:120px"><el-option label="待发货" :value="1" /><el-option label="待收货" :value="2" /><el-option label="已完成" :value="3" /></el-select></el-form-item>
        <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button></el-form-item>
      </el-form>
    </el-card>
    <el-card shadow="never" style="margin-top:16px">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="order_no" label="订单号" width="200" />
        <el-table-column label="用户" width="100"><template #default="{row}">{{row.user_name||row.user_id}}</template></el-table-column>
        <el-table-column label="金额" width="100" align="right"><template #default="{row}"><span style="color:#f56c6c">¥{{row.total_amount}}</span></template></el-table-column>
        <el-table-column label="状态" width="100" align="center"><template #default="{row}"><el-tag :type="statusType[row.status]||'info'" size="small">{{statusLabel[row.status]||row.status}}</el-tag></template></el-table-column>
        <el-table-column prop="created_at" label="下单时间" width="170" />
        <el-table-column label="操作" width="150"><template #default="{row}"><el-button size="small" @click="handleDetail(row)">详情</el-button><el-button v-if="row.status===1" size="small" type="primary" @click="handleShip(row)">发货</el-button></template></el-table-column>
      </el-table>
      <div style="margin-top:16px;display:flex;justify-content:flex-end"><el-pagination v-model:current-page="page" v-model:page-size="limit" :total="total" :page-sizes="[10,20,50]" layout="total, prev, pager, next" @current-change="fetchList" /></div>
    </el-card>
    <el-dialog v-model="shipVisible" title="订单发货" width="400px">
      <el-form :model="shipForm" label-width="80px">
        <el-form-item label="快递公司"><el-select v-model="shipForm.express_company" style="width:100%"><el-option label="顺丰速运" value="顺丰速运" /><el-option label="圆通速递" value="圆通速递" /><el-option label="中通快递" value="中通快递" /></el-select></el-form-item>
        <el-form-item label="快递单号"><el-input v-model="shipForm.express_no" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="shipVisible=false">取消</el-button><el-button type="primary" @click="confirmShip">确认发货</el-button></template>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import request from '@/utils/request'
const list = ref([]); const total = ref(0); const loading = ref(false)
const page = ref(1); const limit = ref(20)
const searchForm = reactive({ order_no:'', status:null })
const statusLabel = {1:'待发货',2:'待收货',3:'已完成',4:'已取消'}
const statusType = {1:'warning',2:'primary',3:'success',4:'info'}
const shipVisible = ref(false); const shipForm = reactive({ order_id:null, express_company:'顺丰速运', express_no:'' })
const fetchList = async () => { loading.value=true; try { const res = await request({ url:'/merchant/orders', params:{page:page.value,limit:limit.value,...searchForm} }); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const handleDetail = row => { ElMessage.info('订单号: '+row.order_no) }
const handleShip = row => { shipForm.order_id=row.id; shipForm.express_no=''; shipVisible.value=true }
const confirmShip = async () => { if(!shipForm.express_no){ElMessage.warning('请输入快递单号');return}; await request({url:`/merchant/orders/${shipForm.order_id}/ship`,method:'post',data:shipForm}); ElMessage.success('发货成功'); shipVisible.value=false; fetchList() }
onMounted(fetchList)
</script>
