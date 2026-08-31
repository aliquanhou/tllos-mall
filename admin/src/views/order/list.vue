<template>
  <div class="order-list">
    <el-card shadow="never">
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="订单号"><el-input v-model="searchForm.order_no" placeholder="订单号" clearable style="width:180px" @keyup.enter="fetchList" /></el-form-item>
        <el-form-item label="状态"><el-select v-model="searchForm.status" placeholder="全部状态" clearable style="width:120px"><el-option v-for="s in statusOptions" :key="s.value" :label="s.label" :value="s.value" /></el-select></el-form-item>
        <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetSearch">重置</el-button></el-form-item>
      </el-form>
    </el-card>
    <el-card shadow="never" style="margin-top:16px">
      <template #header><div class="card-header"><span>订单列表（共 {{ total }} 单）</span></div></template>
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="order_no" label="订单号" width="200" />
        <el-table-column label="用户" width="120"><template #default="{row}">{{row.user_name||row.user_id}}</template></el-table-column>
        <el-table-column label="商品" min-width="200"><template #default="{row}">{{row.goods_name||'商品信息'}}</template></el-table-column>
        <el-table-column label="金额" width="100" align="right"><template #default="{row}"><span style="color:#f56c6c;font-weight:600">¥{{row.total_amount}}</span></template></el-table-column>
        <el-table-column label="状态" width="100" align="center"><template #default="{row}"><el-tag :type="statusType[row.status]||'info'" size="small">{{statusLabel[row.status]||row.status}}</el-tag></template></el-table-column>
        <el-table-column prop="created_at" label="下单时间" width="170" />
        <el-table-column label="操作" width="200" fixed="right"><template #default="{row}">
          <el-button size="small" @click="handleDetail(row)">详情</el-button>
          <el-button v-if="row.status===1" size="small" type="primary" @click="handleShip(row)">发货</el-button>
          <el-button v-if="row.status===0" size="small" type="danger" @click="handleCancel(row)">取消</el-button>
        </template></el-table-column>
      </el-table>
      <div class="pagination"><el-pagination v-model:current-page="page" v-model:page-size="limit" :page-sizes="[10,20,50]" :total="total" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" /></div>
    </el-card>
    <el-dialog v-model="detailVisible" title="订单详情" width="600px">
      <el-descriptions :column="2" border v-if="currentOrder">
        <el-descriptions-item label="订单号">{{currentOrder.order_no}}</el-descriptions-item>
        <el-descriptions-item label="状态"><el-tag :type="statusType[currentOrder.status]||'info'" size="small">{{statusLabel[currentOrder.status]||currentOrder.status}}</el-tag></el-descriptions-item>
        <el-descriptions-item label="用户">{{currentOrder.user_name||currentOrder.user_id}}</el-descriptions-item>
        <el-descriptions-item label="手机号">{{currentOrder.mobile||'-'}}</el-descriptions-item>
        <el-descriptions-item label="商品金额">¥{{currentOrder.goods_amount||0}}</el-descriptions-item>
        <el-descriptions-item label="运费">¥{{currentOrder.shipping_fee||0}}</el-descriptions-item>
        <el-descriptions-item label="订单金额">¥{{currentOrder.total_amount}}</el-descriptions-item>
        <el-descriptions-item label="支付方式">{{currentOrder.pay_type||'-'}}</el-descriptions-item>
        <el-descriptions-item label="收货地址" :span="2">{{currentOrder.address||'-'}}</el-descriptions-item>
        <el-descriptions-item label="下单时间" :span="2">{{currentOrder.created_at}}</el-descriptions-item>
        <el-descriptions-item label="备注" :span="2">{{currentOrder.remark||'-'}}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>
    <el-dialog v-model="shipVisible" title="订单发货" width="400px">
      <el-form :model="shipForm" label-width="80px">
        <el-form-item label="快递公司"><el-select v-model="shipForm.express_company" style="width:100%"><el-option label="顺丰速运" value="顺丰速运" /><el-option label="圆通速递" value="圆通速递" /><el-option label="中通快递" value="中通快递" /><el-option label="韵达快递" value="韵达快递" /></el-select></el-form-item>
        <el-form-item label="快递单号"><el-input v-model="shipForm.express_no" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="shipVisible=false">取消</el-button><el-button type="primary" @click="confirmShip">确认发货</el-button></template>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getOrderList, getOrderDetail, shipOrder, cancelOrder } from '@/api/order'
const list = ref([]); const total = ref(0); const loading = ref(false)
const page = ref(1); const limit = ref(20)
const searchForm = reactive({ order_no:'', status:null })
const statusOptions = [{value:0,label:'待支付'},{value:1,label:'待发货'},{value:2,label:'待收货'},{value:3,label:'已完成'},{value:4,label:'已取消'}]
const statusLabel = {0:'待支付',1:'待发货',2:'待收货',3:'已完成',4:'已取消'}
const statusType = {0:'warning',1:'primary',2:'success',3:'success',4:'info'}
const detailVisible = ref(false); const currentOrder = ref(null)
const shipVisible = ref(false); const shipForm = reactive({ order_id:null, express_company:'顺丰速运', express_no:'' })
const fetchList = async () => { loading.value=true; try { const res = await getOrderList({page:page.value,limit:limit.value,...searchForm}); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const resetSearch = () => { searchForm.order_no=''; searchForm.status=null; page.value=1; fetchList() }
const handleDetail = async row => { const res = await getOrderDetail(row.id); currentOrder.value=res.data||row; detailVisible.value=true }
const handleShip = row => { shipForm.order_id=row.id; shipForm.express_no=''; shipVisible.value=true }
const confirmShip = async () => { if(!shipForm.express_no){ElMessage.warning('请输入快递单号');return}; await shipOrder(shipForm.order_id,shipForm); ElMessage.success('发货成功'); shipVisible.value=false; fetchList() }
const handleCancel = async row => { await ElMessageBox.confirm('确定取消该订单？','提示',{type:'warning'}); await cancelOrder(row.id); ElMessage.success('取消成功'); fetchList() }
onMounted(fetchList)
</script>
<style scoped>.search-form{margin-bottom:0}.card-header{display:flex;justify-content:space-between;align-items:center}.pagination{margin-top:16px;display:flex;justify-content:flex-end}</style>
