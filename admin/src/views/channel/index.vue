<template>
  <el-card shadow="never">
    <template #header><span>渠道设置</span></template>
    <el-tabs v-model="activeTab">
      <el-tab-pane label="小程序渠道" name="miniprogram">
        <el-form :model="miniprogramForm" label-width="140px" style="max-width:700px">
          <el-form-item label="AppID"><el-input v-model="miniprogramForm.app_id" /></el-form-item>
          <el-form-item label="AppSecret"><el-input v-model="miniprogramForm.app_secret" type="password" show-password /></el-form-item>
          <el-form-item label="是否启用"><el-switch v-model="miniprogramForm.enabled" :active-value="1" :inactive-value="0" /></el-form-item>
          <el-form-item><el-button type="primary" @click="handleSave">保存</el-button></el-form-item>
        </el-form>
      </el-tab-pane>
      <el-tab-pane label="APP渠道" name="app">
        <el-form :model="appForm" label-width="140px" style="max-width:700px">
          <el-form-item label="Android包名"><el-input v-model="appForm.android_package" /></el-form-item>
          <el-form-item label="iOS Bundle ID"><el-input v-model="appForm.ios_bundle" /></el-form-item>
          <el-form-item label="下载链接"><el-input v-model="appForm.download_url" /></el-form-item>
          <el-form-item label="是否启用"><el-switch v-model="appForm.enabled" :active-value="1" :inactive-value="0" /></el-form-item>
          <el-form-item><el-button type="primary" @click="handleSave">保存</el-button></el-form-item>
        </el-form>
      </el-tab-pane>
      <el-tab-pane label="H5渠道" name="h5">
        <el-form :model="h5Form" label-width="140px" style="max-width:700px">
          <el-form-item label="H5域名"><el-input v-model="h5Form.domain" /></el-form-item>
          <el-form-item label="分享标题"><el-input v-model="h5Form.share_title" /></el-form-item>
          <el-form-item label="分享描述"><el-input v-model="h5Form.share_desc" type="textarea" :rows="2" /></el-form-item>
          <el-form-item label="是否启用"><el-switch v-model="h5Form.enabled" :active-value="1" :inactive-value="0" /></el-form-item>
          <el-form-item><el-button type="primary" @click="handleSave">保存</el-button></el-form-item>
        </el-form>
      </el-tab-pane>
    </el-tabs>
  </el-card>
</template>
<script setup>
import { ref } from 'vue'
import { ElMessage } from 'element-plus'
const activeTab = ref('miniprogram')
const miniprogramForm = ref({ app_id:'', app_secret:'', enabled:1 })
const appForm = ref({ android_package:'', ios_bundle:'', download_url:'', enabled:1 })
const h5Form = ref({ domain:'', share_title:'', share_desc:'', enabled:1 })
const handleSave = () => { ElMessage.success('保存成功') }
</script>
