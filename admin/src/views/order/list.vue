<template>
  <div class="order-list">
    <el-card shadow="never">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="订单号"><el-input v-model="searchForm.order_no" placeholder="订单号" clearable style="width:200px" @keyup.enter="fetchList" /></el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width:140px">
            <el-option v-for="(label, value) in statusMap" :key="value" :label="label" :value="Number(value)" />
          </el-select>
        </el-form-item>
        <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetSearch">重置</el-button></el-form-item>
      </el-form>
    </el-card>
    <el-row :gutter="16" style="margin-top:16px">
      <el-col :span="4" v-for="(item, key) in stats" :key="key">
        <el-card shadow="never" class="stat-card"><div class="stat-value">{{ item }}</div><div class="stat-label">{{ statLabels[key] }}</div></el-card>
      </el-col>
    </el-row>
    <el-card shadow="never" style="margin-top:16px">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="order_no" label="订单号" width="200" />
        <el-table-column label="商品" min-width="250">
          <template #default="{ row }">
            <div v-for="item in row.items" :key="item.id" class="order-item">
              <el-image :src="item.product_image" fit="cover" style="width:40px;height:40px;border-radius:4px" />
              <div class="item-info"><div class="item-name">{{ item.product_name }}</div><div class="item-sku" v-if="item.sku_text">{{ item.sku_text }}</div></div>
              <div class="item-qty">x{{ item.quantity }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="金额" width="110"><template #default="{ row }"><span class="amount">¥{{ row.pay_amount }}</span></template></el-table-column>
        <el-table-column label="状态" width="100"><template #default="{ row }"><el-tag :type="statusTagType[row.status]" size="small">{{ statusMap[row.status] }}</el-tag></template></el-table-column>
        <el-table-column prop="receiver_name" label="收货人" width="100" />
        <el-table-column prop="created_at" label="下单时间" width="170" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleDetail(row)">详情</el-button>
            <el-button v-if="row.status==1" type="success" link size="small" @click="handleShip(row)">发货</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination"><el-pagination v-model:current-page="page" v-model:page-size="limit" :page-sizes="[10,20,50]" :total="total" layout="total,sizes,prev,pager,next,jumper" @size-change="fetchList" @current-change="fetchList" /></div>
    </el-card>
    <el-dialog v-model="detailVisible" title="订单详情" width="700px">
      <div v-if="currentOrder">
        <el-descriptions :column="2" border size="small">
          <el-descriptions-item label="订单号">{{ currentOrder.order_no }}</el-descriptions-item>
          <el-descriptions-item label="状态"><el-tag :type="statusTagType[currentOrder.status]">{{ statusMap[currentOrder.status] }}</el-tag></el-descriptions-item>
          <el-descriptions-item label="收货人">{{ currentOrder.receiver_name }}</el-descriptions-item>
          <el-descriptions-item label="手机号">{{ currentOrder.receiver_mobile }}</el-descriptions-item>
          <el-descriptions-item label="收货地址" :span="2">{{ currentOrder.province_name }}{{ currentOrder.city_name }}{{ currentOrder.district_name }}{{ currentOrder.receiver_address }}</el-descriptions-item>
          <el-descriptions-item label="商品总额">¥{{ currentOrder.total_amount }}</el-descriptions-item>
          <el-descriptions-item label="运费">¥{{ currentOrder.shipping_fee }}</el-descriptions-item>
          <el-descriptions-item label="实付金额" class="amount">¥{{ currentOrder.pay_amount }}</el-descriptions-item>
          <el-descriptions-item label="支付方式">{{ payTypeMap[currentOrder.pay_type] }}</el-descriptions-item>
          <el-descriptions-item label="物流公司" v-if="currentOrder.express_company">{{ currentOrder.express_company }}</el-descriptions-item>
          <el-descriptions-item label="物流单号" v-if="currentOrder.express_no">{{ currentOrder.express_no }}</el-descriptions-item>
        </el-descriptions>
        <el-divider>商品清单</el-divider>
        <el-table :data="currentOrder.items" size="small" border>
          <el-table-column prop="product_name" label="商品" />
          <el-table-column prop="sku_text" label="规格" width="120" />
          <el-table-column prop="price" label="单价" width="100"><template #default="{ row }">¥{{ row.price }}</template></el-table-column>
          <el-table-column prop="quantity" label="数量" width="80" />
          <el-table-column prop="pay_amount" label="小计" width="100"><template #default="{ row }">¥{{ row.pay_amount }}</template></el-table-column>
        </el-table>
        <el-divider>操作日志</el-divider>
        <el-timeline>
          <el-timeline-item v-for="log in currentOrder.logs" :key="log.id" :timestamp="log.created_at" placement="top">{{ log.action_name }} - {{ log.remark }}</el-timeline-item>
        </el-timeline>
      </div>
    </el-dialog>
    <el-dialog v-model="shipVisible" title="订单发货" width="450px">
      <el-form :model="shipForm" label-width="80px">
        <el-form-item label="物流公司">
          <el-select v-model="shipForm.express_company" placeholder="请选择" style="width:100%">
            <el-option v-for="c in expressCompanies" :key="c" :label="c" :value="c" />
          </el-select>
        </el-form-item>
        <el-form-item label="物流单号"><el-input v-model="shipForm.express_no" placeholder="请输入物流单号" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="shipVisible=false">取消</el-button><el-button type="primary" @click="confirmShip">确认发货</el-button></template>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getOrderList, getOrder, shipOrder } from '@/api/order'
const statusMap = {0:'待支付',1:'待发货',2:'待收货',3:'已完成',4:'已取消',5:'退款中',6:'已退款'}
const statusTagType = {0:'warning',1:'primary',2:'success',3:'info',4:'info',5:'danger',6:'danger'}
const payTypeMap = {0:'未支付',1:'微信支付',2:'支付宝',3:'余额支付',4:'银行卡'}
const statLabels = {total:'总订单',wait_pay:'待支付',wait_ship:'待发货',wait_confirm:'待收货',completed:'已完成',total_amount:'总金额(元)'}
const expressCompanies = ['顺丰速运','圆通速递','中通快递','韵达快递','申通快递','百世快递','邮政EMS','京东物流']
const loading = ref(false)
const list = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(20)
const stats = ref({})
const detailVisible = ref(false)
const shipVisible = ref(false)
const currentOrder = ref(null)
const currentOrderId = ref(null)
const shipForm = reactive({ express_company:'', express_no:'' })
const searchForm = reactive({ order_no:'', status:null })
const fetchList = async () => {
  loading.value = true
  try {
    const res = await getOrderList({ page:page.value, limit:limit.value, order_no:searchForm.order_no||undefined, status:searchForm.status!==null?searchForm.status:undefined })
    list.value = res.data.list
    total.value = res.data.total
    stats.value = res.data.stats || {}
  } catch(e) { console.error(e) } finally { loading.value = false }
}
const resetSearch = () => { searchForm.order_no=''; searchForm.status=null; page.value=1; fetchList() }
const handleDetail = async (row) => { try { const res = await getOrder(row.id); currentOrder.value=res.data; detailVisible.value=true } catch(e){console.error(e)} }
const handleShip = (row) => { currentOrderId.value=row.id; shipForm.express_company=''; shipForm.express_no=''; shipVisible.value=true }
const confirmShip = async () => {
  if(!shipForm.express_company||!shipForm.express_no){ElMessage.warning('请填写物流公司和单号');return}
  try { await shipOrder(currentOrderId.value, shipForm); ElMessage.success('发货成功'); shipVisible.value=false; fetchList() } catch(e){console.error(e)}
}
onMounted(fetchList)
</script>
<style scoped>
.stat-card{text-align:center}
.stat-value{font-size:24px;font-weight:bold;color:#303133}
.stat-label{font-size:13px;color:#909399;margin-top:4px}
.order-item{display:flex;align-items:center;gap:8px;padding:4px 0}
.item-info{flex:1}
.item-name{font-size:13px;color:#303133}
.item-sku{font-size:12px;color:#909399}
.item-qty{color:#909399;font-size:13px}
.amount{color:#f56c6c;font-weight:bold}
.pagination{margin-top:20px;display:flex;justify-content:flex-end}
</style>
