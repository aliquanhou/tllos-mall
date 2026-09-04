<template>
  <div class="audit-page">
    <el-card shadow="never">
      <template #header><span>入驻审核</span></template>
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
        <el-table-column label="联系人" width="140">
          <template #default="{ row }">{{ row.contact_name }}<br /><span style="color:#909399;font-size:12px">{{ row.contact_mobile }}</span></template>
        </el-table-column>
        <el-table-column prop="address" label="地址" min-width="150" show-overflow-tooltip />
        <el-table-column label="营业执照" width="100" align="center">
          <template #default="{ row }">
            <el-button v-if="row.business_license" size="small" type="primary" link @click="viewImage(row.business_license)">查看</el-button>
            <span v-else style="color:#f56c6c">未上传</span>
          </template>
        </el-table-column>
        <el-table-column label="身份证" width="100" align="center">
          <template #default="{ row }">
            <el-button v-if="row.id_card_front" size="small" type="primary" link @click="viewImage(row.id_card_front)">查看</el-button>
            <span v-else style="color:#f56c6c">未上传</span>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="申请时间" width="160" align="center" />
        <el-table-column label="操作" width="200" align="center" fixed="right">
          <template #default="{ row }">
            <el-button size="small" type="success" @click="handleApprove(row)">通过</el-button>
            <el-button size="small" type="danger" @click="handleReject(row)">拒绝</el-button>
          </template>
        </el-table-column>
      </el-table>
      <el-empty v-if="!loading && list.length === 0" description="暂无待审核商家" />
    </el-card>

    <el-dialog v-model="rejectVisible" title="拒绝入驻" width="400px">
      <el-form label-width="80px">
        <el-form-item label="拒绝原因">
          <el-input v-model="rejectReason" type="textarea" :rows="4" placeholder="请输入拒绝原因" maxlength="255" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rejectVisible = false">取消</el-button>
        <el-button type="danger" @click="submitReject">确认拒绝</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="imageVisible" title="证件查看" width="600px">
      <el-image :src="currentImage" fit="contain" style="width:100%;max-height:500px" />
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getMerchantList, auditMerchant } from '@/api/merchant'

const list = ref([])
const loading = ref(false)
const rejectVisible = ref(false)
const rejectReason = ref('')
const currentRow = ref(null)
const imageVisible = ref(false)
const currentImage = ref('')

const fetchList = async () => {
  loading.value = true
  try {
    const res = await getMerchantList({ status: 0, page: 1, limit: 50 })
    list.value = res.data.list || []
  } finally { loading.value = false }
}
const handleApprove = async row => {
  await ElMessageBox.confirm(`确定通过商家"${row.name}"的入驻申请？`, '审核通过', { type: 'success' })
  await auditMerchant(row.id, { status: 1 })
  ElMessage.success('审核通过')
  fetchList()
}
const handleReject = row => { currentRow.value = row; rejectReason.value = ''; rejectVisible.value = true }
const submitReject = async () => {
  if (!rejectReason.value) { ElMessage.warning('请输入拒绝原因'); return }
  await auditMerchant(currentRow.value.id, { status: 2, reject_reason: rejectReason.value })
  ElMessage.success('已拒绝')
  rejectVisible.value = false
  fetchList()
}
const viewImage = url => { currentImage.value = url; imageVisible.value = true }
onMounted(fetchList)
</script>
<style scoped>
.shop-cell { display:flex; align-items:center; gap:10px; }
.shop-name { font-size:14px; font-weight:500; color:#303133; }
.shop-company { font-size:12px; color:#909399; margin-top:2px; }
</style>
