<template>
  <div class="merchant-page">
    <el-card shadow="never">
      <template #header><span>商家列表</span></template>
      <el-form :inline="true" class="search-form">
        <el-form-item label="关键词">
          <el-input v-model="query.keyword" placeholder="店铺名/联系人/电话/公司" clearable style="width: 220px" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="query.status" placeholder="全部" clearable style="width: 120px">
            <el-option :value="0" label="待审核" />
            <el-option :value="1" label="已通过" />
            <el-option :value="2" label="已拒绝" />
            <el-option :value="3" label="已禁用" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="fetchList">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>

      <el-row :gutter="16" class="stats-row">
        <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val">{{ stats.total }}</div><div class="lbl">总商家</div></div></el-card></el-col>
        <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#e6a23c">{{ stats.pending }}</div><div class="lbl">待审核</div></div></el-card></el-col>
        <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#67c23a">{{ stats.approved }}</div><div class="lbl">已通过</div></div></el-card></el-col>
        <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#f56c6c">{{ stats.rejected }}</div><div class="lbl">已拒绝</div></div></el-card></el-col>
        <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#909399">{{ stats.disabled }}</div><div class="lbl">已禁用</div></div></el-card></el-col>
      </el-row>

      <el-table :data="list" border v-loading="loading">
        <el-table-column label="店铺" min-width="200">
          <template #default="{ row }">
            <div class="shop-cell">
              <el-avatar :size="40" :src="row.logo">{{ row.name?.charAt(0) }}</el-avatar>
              <div>
                <div class="shop-name">{{ row.name }}</div>
                <div class="shop-company">{{ row.company_name }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="联系人" width="120">
          <template #default="{ row }">{{ row.contact_name }}<br /><span style="color:#909399;font-size:12px">{{ row.contact_mobile }}</span></template>
        </el-table-column>
        <el-table-column prop="address" label="地址" min-width="150" show-overflow-tooltip />
        <el-table-column label="评分" width="100" align="center">
          <template #default="{ row }"><el-rate :model-value="Number(row.rating)" disabled size="small" /></template>
        </el-table-column>
        <el-table-column label="商品/订单" width="120" align="center">
          <template #default="{ row }">{{ row.product_count }} / {{ row.order_count }}</template>
        </el-table-column>
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="statusType[row.status]" size="small">{{ statusText[row.status] }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="入驻时间" width="160" align="center" />
        <el-table-column label="操作" width="220" align="center" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleDetail(row)">详情</el-button>
            <el-button size="small" type="warning" @click="handleToggle(row)" v-if="row.status === 1 || row.status === 3">{{ row.status === 1 ? '禁用' : '启用' }}</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
    </el-card>

    <el-dialog v-model="detailVisible" title="商家详情" width="700px">
      <el-descriptions :column="2" border v-if="currentMerchant">
        <el-descriptions-item label="店铺名称">{{ currentMerchant.name }}</el-descriptions-item>
        <el-descriptions-item label="公司名称">{{ currentMerchant.company_name }}</el-descriptions-item>
        <el-descriptions-item label="联系人">{{ currentMerchant.contact_name }}</el-descriptions-item>
        <el-descriptions-item label="联系电话">{{ currentMerchant.contact_mobile }}</el-descriptions-item>
        <el-descriptions-item label="联系邮箱">{{ currentMerchant.contact_email }}</el-descriptions-item>
        <el-descriptions-item label="店铺地址">{{ currentMerchant.address }}</el-descriptions-item>
        <el-descriptions-item label="店铺评分">{{ currentMerchant.rating }}</el-descriptions-item>
        <el-descriptions-item label="商家等级">L{{ currentMerchant.level }}</el-descriptions-item>
        <el-descriptions-item label="商品数量">{{ currentMerchant.product_count }}</el-descriptions-item>
        <el-descriptions-item label="订单数量">{{ currentMerchant.order_count }}</el-descriptions-item>
        <el-descriptions-item label="账户余额">¥{{ currentMerchant.balance }}</el-descriptions-item>
        <el-descriptions-item label="冻结金额">¥{{ currentMerchant.frozen }}</el-descriptions-item>
        <el-descriptions-item label="累计收入">¥{{ currentMerchant.total_income }}</el-descriptions-item>
        <el-descriptions-item label="累计结算">¥{{ currentMerchant.total_settlement }}</el-descriptions-item>
        <el-descriptions-item label="店铺简介" :span="2">{{ currentMerchant.description }}</el-descriptions-item>
        <el-descriptions-item label="入驻时间" :span="2">{{ currentMerchant.created_at }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getMerchantList, getMerchantDetail, toggleMerchantStatus, deleteMerchant } from '@/api/merchant'

const list = ref([])
const total = ref(0)
const loading = ref(false)
const stats = ref({ total:0, pending:0, approved:0, rejected:0, disabled:0 })
const query = reactive({ page:1, limit:20, keyword:'', status:null })
const detailVisible = ref(false)
const currentMerchant = ref(null)
const statusText = { 0:'待审核', 1:'已通过', 2:'已拒绝', 3:'已禁用' }
const statusType = { 0:'warning', 1:'success', 2:'danger', 3:'info' }

const fetchList = async () => {
  loading.value = true
  try {
    const res = await getMerchantList(query)
    list.value = res.data.list || []
    total.value = res.data.total || 0
    stats.value = res.data.stats || stats.value
  } finally { loading.value = false }
}
const resetQuery = () => { Object.assign(query, { page:1, limit:20, keyword:'', status:null }); fetchList() }
const handleDetail = async row => { currentMerchant.value = (await getMerchantDetail(row.id)).data; detailVisible.value = true }
const handleToggle = async row => {
  await ElMessageBox.confirm(`确定${row.status === 1 ? '禁用' : '启用'}商家"${row.name}"？`, '提示', { type:'warning' })
  await toggleMerchantStatus(row.id)
  ElMessage.success('操作成功')
  fetchList()
}
const handleDelete = async row => {
  await ElMessageBox.confirm(`确定删除商家"${row.name}"？`, '提示', { type:'warning' })
  await deleteMerchant(row.id)
  ElMessage.success('删除成功')
  fetchList()
}
onMounted(fetchList)
</script>
<style scoped>
.search-form { margin-bottom: 16px; }
.stats-row { margin-bottom: 16px; }
.stat { text-align:center; padding:8px 0; }
.stat .val { font-size:24px; font-weight:bold; color:#303133; }
.stat .lbl { font-size:13px; color:#909399; margin-top:4px; }
.shop-cell { display:flex; align-items:center; gap:10px; }
.shop-name { font-size:14px; font-weight:500; color:#303133; }
.shop-company { font-size:12px; color:#909399; margin-top:2px; }
</style>
