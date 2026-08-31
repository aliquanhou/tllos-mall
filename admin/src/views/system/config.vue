<template>
  <el-card>
    <template #header><span>系统配置</span></template>
    <el-form label-width="120px" style="max-width:600px">
      <el-form-item label="商城名称"><el-input v-model="form.name" /></el-form-item>
      <el-form-item label="默认语言">
        <el-select v-model="form.locale"><el-option label="简体中文" value="zh" /><el-option label="English" value="en" /></el-select>
      </el-form-item>
      <el-form-item label="客服电话"><el-input v-model="form.phone" /></el-form-item>
      <el-form-item label="备案号"><el-input v-model="form.icp" /></el-form-item>
      <el-form-item><el-button type="primary" @click="save">保存配置</el-button></el-form-item>
    </el-form>
  </el-card>
</template>
<script setup>
import { reactive } from 'vue'
import { ElMessage } from 'element-plus'
const form = reactive({ name: 'TLLOS Mall', locale: 'zh', phone: '400-000-0000', icp: '粤ICP备XXXXXXXX号' })
function save() { ElMessage.success('配置已保存（演示）') }
</script>
