<template>
  <div class="address-page">
    <div class="container">
      <div class="page-header">
        <h2>收货地址管理</h2>
        <el-button type="primary" @click="showAddDialog = true"><el-icon><Plus /></el-icon> 新增地址</el-button>
      </div>
      <div class="address-list" v-if="addresses.length">
        <div class="address-card" v-for="addr in addresses" :key="addr.id" :class="{default: addr.is_default}">
          <div class="address-info">
            <div class="address-top">
              <span class="receiver">{{ addr.receiver_name }}</span>
              <span class="mobile">{{ addr.receiver_mobile }}</span>
              <el-tag v-if="addr.is_default" type="warning" size="small">默认</el-tag>
            </div>
            <div class="address-detail">{{ addr.province }}{{ addr.city }}{{ addr.district }}{{ addr.detail }}</div>
          </div>
          <div class="address-actions">
            <el-button link type="primary" @click="editAddress(addr)">编辑</el-button>
            <el-button link type="primary" v-if="!addr.is_default" @click="setDefault(addr.id)">设为默认</el-button>
            <el-button link type="danger" @click="deleteAddress(addr.id)">删除</el-button>
          </div>
        </div>
      </div>
      <div class="empty-address" v-else>
        <el-icon size="64" color="#ddd"><Location /></el-icon>
        <p>暂无收货地址</p>
        <el-button type="primary" @click="showAddDialog = true">添加收货地址</el-button>
      </div>
    </div>
    <!-- 新增/编辑地址弹窗 -->
    <el-dialog v-model="showAddDialog" :title="editingId ? '编辑地址' : '新增地址'" width="500px">
      <el-form :model="addressForm" :rules="addressRules" ref="addressFormRef" label-width="80px">
        <el-form-item label="收货人" prop="receiver_name"><el-input v-model="addressForm.receiver_name" placeholder="请输入收货人姓名" /></el-form-item>
        <el-form-item label="手机号" prop="receiver_mobile"><el-input v-model="addressForm.receiver_mobile" placeholder="请输入手机号" maxlength="11" /></el-form-item>
        <el-form-item label="所在地区" prop="region">
          <el-cascader v-model="addressForm.region" :options="regionOptions" placeholder="请选择省/市/区" style="width:100%" />
        </el-form-item>
        <el-form-item label="详细地址" prop="detail"><el-input v-model="addressForm.detail" type="textarea" :rows="2" placeholder="请输入详细地址（街道、门牌号等）" /></el-form-item>
        <el-form-item label="设为默认"><el-switch v-model="addressForm.is_default" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showAddDialog = false">取消</el-button>
        <el-button type="primary" @click="saveAddress">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
const addresses = ref([
  { id: 1, receiver_name: '张三', receiver_mobile: '13888888888', province: '广东省', city: '深圳市', district: '南山区', detail: '科技园路1号', is_default: true },
  { id: 2, receiver_name: '李四', receiver_mobile: '13999999999', province: '北京市', city: '北京市', district: '朝阳区', detail: '建国路88号', is_default: false },
])
const showAddDialog = ref(false)
const editingId = ref(null)
const addressFormRef = ref(null)
const addressForm = reactive({ receiver_name: '', receiver_mobile: '', region: [], detail: '', is_default: false })
const regionOptions = [
  { value: '广东省', label: '广东省', children: [{ value: '深圳市', label: '深圳市', children: [{ value: '南山区', label: '南山区' }, { value: '福田区', label: '福田区' }] }] },
  { value: '北京市', label: '北京市', children: [{ value: '北京市', label: '北京市', children: [{ value: '朝阳区', label: '朝阳区' }, { value: '海淀区', label: '海淀区' }] }] },
]
const addressRules = {
  receiver_name: [{ required: true, message: '请输入收货人姓名', trigger: 'blur' }],
  receiver_mobile: [{ required: true, message: '请输入手机号', trigger: 'blur' }, { pattern: /^1[3-9]\d{9}$/, message: '手机号格式不正确', trigger: 'blur' }],
  region: [{ required: true, message: '请选择所在地区', trigger: 'change' }],
  detail: [{ required: true, message: '请输入详细地址', trigger: 'blur' }],
}
const editAddress = (addr) => {
  editingId.value = addr.id
  addressForm.receiver_name = addr.receiver_name
  addressForm.receiver_mobile = addr.receiver_mobile
  addressForm.region = [addr.province, addr.city, addr.district]
  addressForm.detail = addr.detail
  addressForm.is_default = addr.is_default
  showAddDialog.value = true
}
const saveAddress = async () => {
  if (!addressFormRef.value) return
  await addressFormRef.value.validate((valid) => {
    if (!valid) return
    if (editingId.value) {
      const idx = addresses.value.findIndex(a => a.id === editingId.value)
      if (idx > -1) {
        addresses.value[idx] = { ...addresses.value[idx], receiver_name: addressForm.receiver_name, receiver_mobile: addressForm.receiver_mobile, province: addressForm.region[0], city: addressForm.region[1], district: addressForm.region[2], detail: addressForm.detail, is_default: addressForm.is_default }
      }
      ElMessage.success('地址修改成功')
    } else {
      addresses.value.push({ id: Date.now(), receiver_name: addressForm.receiver_name, receiver_mobile: addressForm.receiver_mobile, province: addressForm.region[0], city: addressForm.region[1], district: addressForm.region[2], detail: addressForm.detail, is_default: addressForm.is_default })
      ElMessage.success('地址添加成功')
    }
    showAddDialog.value = false
    resetForm()
  })
}
const setDefault = (id) => { addresses.value.forEach(a => a.is_default = (a.id === id)); ElMessage.success('已设为默认地址') }
const deleteAddress = async (id) => {
  try {
    await ElMessageBox.confirm('确定删除该地址？', '提示', { type: 'warning' })
    addresses.value = addresses.value.filter(a => a.id !== id)
    ElMessage.success('删除成功')
  } catch (e) {}
}
const resetForm = () => { editingId.value = null; addressForm.receiver_name = ''; addressForm.receiver_mobile = ''; addressForm.region = []; addressForm.detail = ''; addressForm.is_default = false }
</script>
<style scoped>
.address-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h2 { font-size: 22px; color: #333; margin: 0; }
.address-list { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.address-card { background: #fff; border-radius: 8px; padding: 20px; border: 2px solid #eee; transition: all 0.2s; }
.address-card:hover { border-color: #e6a23c; }
.address-card.default { border-color: #e6a23c; background: #fdf6ec; }
.address-info { margin-bottom: 12px; }
.address-top { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
.receiver { font-size: 16px; font-weight: bold; color: #333; }
.mobile { font-size: 14px; color: #666; }
.address-detail { font-size: 14px; color: #666; line-height: 1.6; }
.address-actions { display: flex; gap: 16px; padding-top: 12px; border-top: 1px solid #f5f5f5; }
.empty-address { background: #fff; border-radius: 8px; padding: 60px 20px; text-align: center; }
.empty-address p { color: #999; margin: 16px 0; }

/* ========== 移动端适配 ========== */
@media (max-width: 768px) {
  .address-page { padding: 10px 0; min-height: calc(100vh - 120px); }
  .container { max-width: 100%; padding: 0 12px; }
  .page-header { flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
  .page-title { font-size: 16px; }
  .add-btn { font-size: 13px !important; padding: 8px 14px !important; }
  
  /* 地址列表改单列 */
  .address-list { display: grid; grid-template-columns: 1fr; gap: 10px; }
  .address-card { padding: 12px; border-radius: 6px; }
  .address-name { font-size: 14px; font-weight: bold; }
  .address-phone { font-size: 13px; }
  .default-tag { font-size: 10px; padding: 1px 6px; }
  .address-detail { font-size: 12px; line-height: 1.5; margin-top: 6px; }
  .address-actions { gap: 8px; flex-wrap: wrap; padding-top: 10px; margin-top: 10px; border-top: 1px solid #f5f5f5; }
  .address-actions .el-button { font-size: 12px !important; padding: 6px 12px !important; }
  
  .empty-address { padding: 40px 16px; border-radius: 6px; }
  .empty-address p { font-size: 13px; margin: 12px 0; }
}

@media (max-width: 480px) {
  .container { padding: 0 8px; }
  .address-card { padding: 10px; }
}
</style>
