<template>
  <div class="coupon-page">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>优惠券管理</span>
          <el-button type="primary" @click="handleAdd">新增优惠券</el-button>
        </div>
      </template>
      <el-form :inline="true" class="search-form">
        <el-form-item label="关键词">
          <el-input v-model="query.keyword" placeholder="优惠券名称" clearable style="width:180px" />
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="query.type" placeholder="全部" clearable style="width:120px">
            <el-option :value="1" label="满减券" />
            <el-option :value="2" label="折扣券" />
            <el-option :value="3" label="无门槛券" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="query.status" placeholder="全部" clearable style="width:120px">
            <el-option :value="1" label="开启" />
            <el-option :value="0" label="关闭" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="fetchList">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>

      <el-row :gutter="16" class="stats-row">
        <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val">{{ stats.total }}</div><div class="lbl">优惠券总数</div></div></el-card></el-col>
        <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#67c23a">{{ stats.active }}</div><div class="lbl">开启中</div></div></el-card></el-col>
        <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#909399">{{ stats.inactive }}</div><div class="lbl">已关闭</div></div></el-card></el-col>
        <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#e6a23c">{{ stats.total_received }}</div><div class="lbl">总领取数</div></div></el-card></el-col>
        <el-col :span="4"><el-card shadow="hover"><div class="stat"><div class="val" style="color:#f56c6c">{{ stats.total_used }}</div><div class="lbl">总使用数</div></div></el-card></el-col>
      </el-row>

      <el-table :data="list" border v-loading="loading">
        <el-table-column label="优惠券" min-width="220">
          <template #default="{ row }">
            <div class="coupon-cell">
              <div class="coupon-amount" :class="typeClass[row.type]">
                <template v-if="row.type === 2">{{ row.discount_rate }}<span style="font-size:14px">折</span></template>
                <template v-else>¥{{ row.discount_amount }}</template>
              </div>
              <div>
                <div class="coupon-name">{{ row.name }}</div>
                <div class="coupon-desc">{{ row.min_amount > 0 ? '满' + row.min_amount + '可用' : '无门槛' }} · {{ typeText[row.type] }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="发放/领取" width="140" align="center">
          <template #default="{ row }">
            <div>{{ row.receive_count }} / {{ row.total_count === 0 ? '不限' : row.total_count }}</div>
            <div style="color:#909399;font-size:12px">已使用: {{ row.used_count }}</div>
          </template>
        </el-table-column>
        <el-table-column label="每人限领" width="100" align="center">
          <template #default="{ row }">{{ row.limit_per_user }}张</template>
        </el-table-column>
        <el-table-column label="有效期" width="200" align="center">
          <template #default="{ row }">
            <div style="font-size:12px">{{ row.start_time?.slice(0,10) }}</div>
            <div style="font-size:12px;color:#909399">至 {{ row.end_time?.slice(0,10) }}</div>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="80" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">{{ row.status === 1 ? '开启' : '关闭' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="240" align="center" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleRecords(row)">领取记录</el-button>
            <el-button size="small" type="warning" @click="handleToggle(row)">{{ row.status === 1 ? '关闭' : '开启' }}</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,20,50]" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" style="margin-top:16px;justify-content:flex-end" />
    </el-card>

    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑优惠券' : '新增优惠券'" width="600px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="优惠券名称">
          <el-input v-model="form.name" placeholder="请输入优惠券名称" maxlength="100" show-word-limit />
        </el-form-item>
        <el-form-item label="优惠券类型">
          <el-radio-group v-model="form.type">
            <el-radio :value="1">满减券</el-radio>
            <el-radio :value="2">折扣券</el-radio>
            <el-radio :value="3">无门槛券</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="优惠金额" v-if="form.type !== 2">
          <el-input-number v-model="form.discount_amount" :min="0" :precision="2" /> 元
        </el-form-item>
        <el-form-item label="折扣率" v-if="form.type === 2">
          <el-input-number v-model="form.discount_rate" :min="1" :max="99" :precision="0" /> 折（如90表示9折）
        </el-form-item>
        <el-form-item label="最低消费" v-if="form.type !== 3">
          <el-input-number v-model="form.min_amount" :min="0" :precision="2" /> 元
        </el-form-item>
        <el-form-item label="发放总量">
          <el-input-number v-model="form.total_count" :min="0" /> 张（0表示不限）
        </el-form-item>
        <el-form-item label="每人限领">
          <el-input-number v-model="form.limit_per_user" :min="1" /> 张
        </el-form-item>
        <el-form-item label="有效期">
          <el-date-picker v-model="dateRange" type="datetimerange" range-separator="至" start-placeholder="开始时间" end-placeholder="结束时间" style="width:100%" />
        </el-form-item>
        <el-form-item label="新人专享">
          <el-switch v-model="form.is_new_user" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="recordsVisible" title="领取记录" width="800px">
      <el-table :data="records" border size="small">
        <el-table-column prop="coupon_name" label="优惠券" min-width="150" />
        <el-table-column prop="nickname" label="用户" width="120" />
        <el-table-column prop="mobile" label="手机号" width="130" />
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : row.status === 2 ? 'info' : 'warning'" size="small">{{ recordStatus[row.status] }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="received_at" label="领取时间" width="160" align="center" />
        <el-table-column prop="used_at" label="使用时间" width="160" align="center">
          <template #default="{ row }">{{ row.used_at || '-' }}</template>
        </el-table-column>
      </el-table>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getCouponList, createCoupon, updateCoupon, toggleCouponStatus, deleteCoupon, getCouponRecords } from '@/api/coupon'

const list = ref([])
const total = ref(0)
const loading = ref(false)
const stats = ref({ total:0, active:0, inactive:0, total_received:0, total_used:0 })
const query = reactive({ page:1, limit:20, keyword:'', type:null, status:null })
const dialogVisible = ref(false)
const isEdit = ref(false)
const form = ref({ id:null, name:'', type:1, discount_amount:0, discount_rate:90, min_amount:0, total_count:0, limit_per_user:1, is_new_user:0, status:1 })
const dateRange = ref([])
const recordsVisible = ref(false)
const records = ref([])
const typeText = { 1:'满减券', 2:'折扣券', 3:'无门槛券' }
const typeClass = { 1:'type-reduce', 2:'type-discount', 3:'type-free' }
const recordStatus = { 0:'未使用', 1:'已使用', 2:'已过期' }

const fetchList = async () => {
  loading.value = true
  try {
    const res = await getCouponList(query)
    list.value = res.data.list || []
    total.value = res.data.total || 0
    stats.value = res.data.stats || stats.value
  } finally { loading.value = false }
}
const resetQuery = () => { Object.assign(query, { page:1, limit:20, keyword:'', type:null, status:null }); fetchList() }
const handleAdd = () => {
  isEdit.value = false
  form.value = { id:null, name:'', type:1, discount_amount:0, discount_rate:90, min_amount:0, total_count:0, limit_per_user:1, is_new_user:0, status:1 }
  dateRange.value = []
  dialogVisible.value = true
}
const handleSubmit = async () => {
  if (!form.value.name) { ElMessage.warning('请输入优惠券名称'); return }
  const data = { ...form.value }
  if (dateRange.value && dateRange.value.length === 2) {
    data.start_time = dateRange.value[0]
    data.end_time = dateRange.value[1]
  }
  if (isEdit.value) { await updateCoupon(form.value.id, data); ElMessage.success('更新成功') }
  else { await createCoupon(data); ElMessage.success('创建成功') }
  dialogVisible.value = false
  fetchList()
}
const handleToggle = async row => {
  await toggleCouponStatus(row.id)
  ElMessage.success(row.status === 1 ? '已关闭' : '已开启')
  fetchList()
}
const handleDelete = async row => {
  await ElMessageBox.confirm(`确定删除优惠券"${row.name}"？`, '提示', { type:'warning' })
  await deleteCoupon(row.id)
  ElMessage.success('删除成功')
  fetchList()
}
const handleRecords = async row => {
  const res = await getCouponRecords({ coupon_id: row.id, limit: 50 })
  records.value = res.data.list || []
  recordsVisible.value = true
}
onMounted(fetchList)
</script>
<style scoped>
.card-header { display:flex; justify-content:space-between; align-items:center; }
.search-form { margin-bottom:16px; }
.stats-row { margin-bottom:16px; }
.stat { text-align:center; padding:8px 0; }
.stat .val { font-size:24px; font-weight:bold; color:#303133; }
.stat .lbl { font-size:13px; color:#909399; margin-top:4px; }
.coupon-cell { display:flex; align-items:center; gap:12px; }
.coupon-amount { width:70px; height:50px; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px; font-weight:bold; flex-shrink:0; }
.coupon-amount.type-reduce { background:linear-gradient(135deg,#ff6b6b,#ee5a5a); }
.coupon-amount.type-discount { background:linear-gradient(135deg,#ffa502,#ff7f50); }
.coupon-amount.type-free { background:linear-gradient(135deg,#2ed573,#1dd1a1); }
.coupon-name { font-size:14px; font-weight:500; color:#303133; }
.coupon-desc { font-size:12px; color:#909399; margin-top:4px; }
</style>
