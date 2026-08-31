<template>
  <el-card shadow="never">
    <template #header><span>渠道设置</span></template>
    <el-tabs v-model="activeTab">
      <el-tab-pane label="APP设置" name="app">
        <el-form :model="forms.app" label-width="140px" style="max-width:700px">
          <el-form-item label="APP名称"><el-input v-model="forms.app.app_name" /></el-form-item>
          <el-form-item label="APP图标"><el-input v-model="forms.app.app_logo" /></el-form-item>
          <el-form-item label="APP版本"><el-input v-model="forms.app.app_version" /></el-form-item>
          <el-form-item label="下载地址"><el-input v-model="forms.app.app_download_url" /></el-form-item>
          <el-form-item><el-button type="primary" @click="handleSave('app')">保存</el-button></el-form-item>
        </el-form>
      </el-tab-pane>
      <el-tab-pane label="小程序设置" name="mnp">
        <el-form :model="forms.mnp" label-width="140px" style="max-width:700px">
          <el-form-item label="小程序名称"><el-input v-model="forms.mnp.mnp_name" /></el-form-item>
          <el-form-item label="AppID"><el-input v-model="forms.mnp.mnp_appid" /></el-form-item>
          <el-form-item label="AppSecret"><el-input v-model="forms.mnp.mnp_appsecret" type="password" show-password /></el-form-item>
          <el-form-item><el-button type="primary" @click="handleSave('mnp')">保存</el-button></el-form-item>
        </el-form>
      </el-tab-pane>
      <el-tab-pane label="公众号设置" name="oa">
        <el-form :model="forms.oa" label-width="140px" style="max-width:700px">
          <el-form-item label="公众号名称"><el-input v-model="forms.oa.oa_name" /></el-form-item>
          <el-form-item label="AppID"><el-input v-model="forms.oa.oa_appid" /></el-form-item>
          <el-form-item label="AppSecret"><el-input v-model="forms.oa.oa_appsecret" type="password" show-password /></el-form-item>
          <el-form-item label="Token"><el-input v-model="forms.oa.oa_token" /></el-form-item>
          <el-form-item label="EncodingAESKey"><el-input v-model="forms.oa.oa_aeskey" type="password" show-password /></el-form-item>
          <el-form-item><el-button type="primary" @click="handleSave('oa')">保存</el-button></el-form-item>
        </el-form>
      </el-tab-pane>
      <el-tab-pane label="开放平台" name="open">
        <el-form :model="forms.open" label-width="140px" style="max-width:700px">
          <el-form-item label="AppID"><el-input v-model="forms.open.open_appid" /></el-form-item>
          <el-form-item label="AppSecret"><el-input v-model="forms.open.open_appsecret" type="password" show-password /></el-form-item>
          <el-form-item><el-button type="primary" @click="handleSave('open')">保存</el-button></el-form-item>
        </el-form>
      </el-tab-pane>
      <el-tab-pane label="网页设置" name="web">
        <el-form :model="forms.web" label-width="140px" style="max-width:700px">
          <el-form-item label="网站名称"><el-input v-model="forms.web.web_name" /></el-form-item>
          <el-form-item label="网站地址"><el-input v-model="forms.web.web_url" /></el-form-item>
          <el-form-item><el-button type="primary" @click="handleSave('web')">保存</el-button></el-form-item>
        </el-form>
      </el-tab-pane>
    </el-tabs>
  </el-card>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getChannelConfig, saveChannelConfig } from '@/api/channel'
const activeTab = ref('app')
const forms = reactive({ app:{}, mnp:{}, oa:{}, open:{}, web:{} })
const fetchConfig = async () => {
  for(const ch of ['app','mnp','oa','open','web']){
    const res = await getChannelConfig(ch); forms[ch] = res.data||{}
  }
}
const handleSave = async channel => { await saveChannelConfig(channel, forms[channel]); ElMessage.success('保存成功') }
onMounted(fetchConfig)
</script>
