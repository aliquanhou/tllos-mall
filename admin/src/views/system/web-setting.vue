<template>
  <el-card shadow="never">
    <template #header><span>网站设置</span></template>
    <el-tabs v-model="activeTab">
      <el-tab-pane label="基本信息" name="website">
        <el-form :model="websiteForm" label-width="120px" style="max-width:700px">
          <el-form-item label="网站名称"><el-input v-model="websiteForm.website_name" /></el-form-item>
          <el-form-item label="网站LOGO"><el-input v-model="websiteForm.website_logo" /></el-form-item>
          <el-form-item label="备案号"><el-input v-model="websiteForm.website_icp" /></el-form-item>
          <el-form-item label="版权信息"><el-input v-model="websiteForm.copyright" /></el-form-item>
          <el-form-item><el-button type="primary" @click="handleSave('website')">保存</el-button></el-form-item>
        </el-form>
      </el-tab-pane>
      <el-tab-pane label="协议政策" name="agreement">
        <el-form :model="agreementForm" label-width="120px" style="max-width:700px">
          <el-form-item label="用户协议"><el-input v-model="agreementForm.user_agreement" type="textarea" :rows="6" /></el-form-item>
          <el-form-item label="隐私政策"><el-input v-model="agreementForm.privacy_policy" type="textarea" :rows="6" /></el-form-item>
          <el-form-item><el-button type="primary" @click="handleSave('agreement')">保存</el-button></el-form-item>
        </el-form>
      </el-tab-pane>
    </el-tabs>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getWebSetting, saveWebSetting } from '@/api/systemExtra'
const activeTab = ref('website')
const websiteForm = ref({}); const agreementForm = ref({})
const fetchConfig = async () => { const w = await getWebSetting('website'); websiteForm.value = w.data||{}; const a = await getWebSetting('agreement'); agreementForm.value = a.data||{} }
const handleSave = async type => { const form = type==='website'?websiteForm.value:agreementForm.value; await saveWebSetting(type,form); ElMessage.success('保存成功') }
onMounted(fetchConfig)
</script>
